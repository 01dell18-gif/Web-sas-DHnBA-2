<?php
/**
 * Global Helper Functions & Session / CSRF Management
 * Generator Absensi dan Berita Acara Ujian - SMK Negeri 1 Klungkung
 */

require_once __DIR__ . '/database.php';

$sessionDir = BASE_PATH . '/storage/sessions';
if (!is_dir($sessionDir)) {
    @mkdir($sessionDir, 0777, true);
}
if (is_dir($sessionDir) && is_writable($sessionDir)) {
    session_save_path($sessionDir);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Generate or get CSRF token
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Output hidden CSRF input tag
 */
function csrf_field(): string {
    $token = csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Verify CSRF token
 */
function verify_csrf(): bool {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            http_response_code(403);
            die('Error: Token keamanan (CSRF) tidak valid atau sesi telah berakhir. Silakan muat ulang halaman.');
        }
    }
    return true;
}

/**
 * Flash messages
 */
function set_flash(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

function get_flash(): ?array {
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function render_flash(): string {
    $flash = get_flash();
    if (!$flash) return '';
    $type = htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8');
    $msg = htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8');
    return "<div class=\"alert alert-{$type}\" style=\"margin-bottom: 1.25rem;\">{$msg}</div>";
}

/**
 * Auth helpers
 */
function is_admin_logged_in(): bool {
    return !empty($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function require_admin(): void {
    if (!is_admin_logged_in()) {
        header('Location: /admin/login.php');
        exit;
    }
}

/**
 * Settings helpers
 */
function get_setting(string $key, $default = '') {
    static $cached = null;
    if ($cached === null) {
        $cached = [];
        try {
            $rows = dbFetchAll("SELECT setting_key, setting_value FROM pengaturan");
            foreach ($rows as $r) {
                $cached[$r['setting_key']] = $r['setting_value'];
            }
        } catch (\Exception $e) {
            // DB might not be initialized yet
        }
    }
    return $cached[$key] ?? $default;
}

function save_setting(string $key, string $value): void {
    $exists = dbValue("SELECT COUNT(*) FROM pengaturan WHERE setting_key = :k", ['k' => $key]);
    if ($exists) {
        dbUpdate("pengaturan", ['setting_value' => $value], "setting_key = :k", ['k' => $key]);
    } else {
        dbInsert("pengaturan", ['setting_key' => $key, 'setting_value' => $value]);
    }
}

/**
 * Activity log helper
 */
function log_activity(string $activity, string $keterangan): void {
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        dbInsert('activity_logs', [
            'waktu' => date('Y-m-d H:i:s'),
            'aktivitas' => $activity,
            'keterangan' => $keterangan,
            'ip_address' => $ip
        ]);
    } catch (\Exception $e) {
        // Ignore log errors
    }
}

/**
 * Indonesian Date & Number Helpers
 */
function format_date_indo(?string $dateStr): string {
    if (!$dateStr) return '-';
    $timestamp = strtotime($dateStr);
    if (!$timestamp) return $dateStr;
    
    $bulanIndo = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    
    $d = date('j', $timestamp);
    $m = $bulanIndo[(int)date('n', $timestamp)] ?? date('F', $timestamp);
    $y = date('Y', $timestamp);
    
    return "{$d} {$m} {$y}";
}

function terbilang($n): string {
    $n = abs((int)$n);
    $huruf = ["", "satu", "dua", "tiga", "empat", "lima", "enam", "tujuh", "delapan", "sembilan", "sepuluh", "sebelas"];
    if ($n < 12) return $huruf[$n];
    if ($n < 20) return terbilang($n - 10) . " belas";
    if ($n < 100) return terbilang((int)($n / 10)) . " puluh" . ($n % 10 !== 0 ? " " . terbilang($n % 10) : "");
    if ($n < 200) return "seratus" . ($n - 100 !== 0 ? " " . terbilang($n - 100) : "");
    if ($n < 1000) return terbilang((int)($n / 100)) . " ratus" . ($n % 100 !== 0 ? " " . terbilang($n % 100) : "");
    if ($n < 2000) return "seribu" . ($n - 1000 !== 0 ? " " . terbilang($n - 1000) : "");
    if ($n < 1000000) return terbilang((int)($n / 1000)) . " ribu" . ($n % 1000 !== 0 ? " " . terbilang($n % 1000) : "");
    return (string)$n;
}
