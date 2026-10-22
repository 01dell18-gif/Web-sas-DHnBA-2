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

if (!ob_get_level()) {
    ob_start();
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
    $rawMsg = $flash['message'];
    $msg = htmlspecialchars($rawMsg, ENT_QUOTES, 'UTF-8');

    $title = 'Pemberitahuan';
    $icon = 'bi-info-circle-fill';
    $iconColor = '#3b82f6';
    $bgColor = '#eff6ff';
    $borderColor = '#bfdbfe';

    if ($type === 'success') {
        $title = 'Berhasil!';
        $icon = 'bi-check-circle-fill';
        $iconColor = '#10b981';
        $bgColor = '#ecfdf5';
        $borderColor = '#a7f3d0';
    } elseif ($type === 'danger' || $type === 'error') {
        $title = 'Perhatian / Terjadi Kesalahan';
        $icon = 'bi-exclamation-triangle-fill';
        $iconColor = '#ef4444';
        $bgColor = '#fef2f2';
        $borderColor = '#fecaca';
    } elseif ($type === 'warning') {
        $title = 'Peringatan';
        $icon = 'bi-exclamation-circle-fill';
        $iconColor = '#f59e0b';
        $bgColor = '#fffbeb';
        $borderColor = '#fde68a';
    }

    return '
    <!-- FLASH NOTIFICATION POPUP MODAL -->
    <div id="flashNotificationModal" class="custom-modal" style="display: flex; z-index: 999999;">
        <div class="notification-popup-card" style="border-top: 4px solid ' . $iconColor . ';">
            <div style="display: flex; align-items: flex-start; gap: 0.9rem;">
                <div style="width: 44px; height: 44px; border-radius: 50%; background: ' . $bgColor . '; border: 1px solid ' . $borderColor . '; color: ' . $iconColor . '; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0;">
                    <i class="bi ' . $icon . '"></i>
                </div>
                <div style="flex: 1;">
                    <h3 style="font-size: 1.1rem; font-weight: 700; color: #0f172a; margin: 0 0 0.35rem 0;">' . $title . '</h3>
                    <p style="font-size: 0.885rem; color: #475569; line-height: 1.5; margin: 0;">' . $msg . '</p>
                </div>
                <button type="button" onclick="closeFlashNotificationModal()" style="background: none; border: none; font-size: 1.35rem; color: #94a3b8; cursor: pointer; padding: 0 4px; line-height: 1; border-radius: 6px;" title="Tutup">&times;</button>
            </div>
            <div style="display: flex; justify-content: flex-end; margin-top: 1.25rem;">
                <button type="button" class="btn btn-primary btn-sm" onclick="closeFlashNotificationModal()" style="min-width: 90px;">
                    OK, Mengerti
                </button>
            </div>
        </div>
    </div>
    <script>
    function closeFlashNotificationModal() {
        const el = document.getElementById("flashNotificationModal");
        if (el) {
            el.style.opacity = "0";
            el.style.transition = "opacity 0.22s ease";
            setTimeout(() => { el.style.display = "none"; }, 220);
        }
    }
    // Auto-close after 5.5 seconds
    setTimeout(closeFlashNotificationModal, 5500);
    </script>
    ';
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

function get_current_user_role(): string {
    return $_SESSION['admin_role'] ?? 'admin';
}

function is_admin(): bool {
    return is_admin_logged_in() && (($_SESSION['admin_role'] ?? 'admin') === 'admin');
}

function is_operator(): bool {
    return is_admin_logged_in() && (($_SESSION['admin_role'] ?? '') === 'operator');
}

function require_admin_role(): void {
    require_admin();
    if (!is_admin()) {
        set_flash('danger', 'Akses ditolak. Fitur ini hanya dapat diakses oleh Administrator.');
        header('Location: /admin/index.php');
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

/**
 * Universal ZIP Creator: Uses ZipArchive if available, falls back to pure PHP PKZIP packer.
 * Guarantees zero fatal errors even if extension=zip is not loaded in PHP.
 */
function create_zip_archive(string $zipFilePath, array $files): bool {
    // Mode 1: Native PHP ZipArchive if extension loaded
    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            foreach ($files as $name => $path) {
                if (file_exists($path)) {
                    $entryName = is_string($name) ? $name : basename($path);
                    $zip->addFile($path, $entryName);
                }
            }
            $zip->close();
            if (file_exists($zipFilePath) && filesize($zipFilePath) > 0) {
                return true;
            }
        }
    }

    // Mode 2: Pure PHP PKZIP Packer (No extensions required)
    $time = time();
    $dtime = ((date('H', $time) << 11) | (date('i', $time) << 5) | (date('s', $time) >> 1)) & 0xFFFF;
    $ddate = (((date('Y', $time) - 1980) << 9) | (date('m', $time) << 5) | date('d', $time)) & 0xFFFF;

    $datasec = [];
    $ctrl_dir = [];
    $offset = 0;

    foreach ($files as $name => $path) {
        if (!file_exists($path)) continue;
        $entryName = is_string($name) ? $name : basename($path);
        $entryName = str_replace('\\', '/', $entryName);
        $data = file_get_contents($path);
        $unc_len = strlen($data);
        $crc = crc32($data);

        if (function_exists('gzdeflate')) {
            $c_data = gzdeflate($data);
            $c_len = strlen($c_data);
            $method = 8;
        } else {
            $c_data = $data;
            $c_len = $unc_len;
            $method = 0;
        }

        // Local header: 30 bytes + name + data
        $fr = pack('VvvvvvVVVvv', 0x04034b50, 20, 0, $method, $dtime, $ddate, $crc, $c_len, $unc_len, strlen($entryName), 0);
        $fr .= $entryName . $c_data;
        $datasec[] = $fr;

        // Central directory record: 46 bytes + name
        $cdrec = pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, 0, $method, $dtime, $ddate, $crc, $c_len, $unc_len, strlen($entryName), 0, 0, 0, 0, 32, $offset);
        $cdrec .= $entryName;
        $ctrl_dir[] = $cdrec;

        $offset += strlen($fr);
    }

    if (empty($datasec)) return false;

    $ctrldir_data = implode('', $ctrl_dir);
    $data_data = implode('', $datasec);

    // End of central directory record: 22 bytes
    $eocd = pack('VvvvvVVv', 0x06054b50, 0, 0, count($ctrl_dir), count($ctrl_dir), strlen($ctrldir_data), strlen($data_data), 0);

    return (file_put_contents($zipFilePath, $data_data . $ctrldir_data . $eocd) !== false);
}

/**
 * Universal Excel .xls Exporter
 * Generates official XML/HTML table spreadsheet with proper UTF-8 and mso-number-format.
 */
function export_excel_xls(string $filename, array $headers, array $rows, array $textColIndexes = []): void {
    while (ob_get_level()) { ob_end_clean(); }
    header_remove('Pragma');
    header_remove('Expires');
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate');

    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head>';
    echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
    echo '<style>';
    echo 'table { border-collapse: collapse; font-family: Arial, sans-serif; font-size: 10pt; }';
    echo 'th { background-color: #3b82f6; color: #ffffff; font-weight: bold; padding: 8px 12px; border: 1px solid #1d4ed8; text-align: left; }';
    echo 'td { padding: 6px 12px; border: 1px solid #cbd5e1; }';
    echo '.text-cell { mso-number-format: "\@"; text-align: left; }';
    echo '.center-cell { text-align: center; }';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    echo '<table>';
    echo '<thead><tr>';
    foreach ($headers as $h) {
        echo '<th>' . htmlspecialchars($h, ENT_QUOTES, 'UTF-8') . '</th>';
    }
    echo '</tr></thead>';
    echo '<tbody>';
    foreach ($rows as $r) {
        echo '<tr>';
        foreach ($r as $idx => $val) {
            $isText = in_array($idx, $textColIndexes);
            $class = $isText ? ' class="text-cell"' : '';
            echo '<td' . $class . '>' . htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8') . '</td>';
        }
        echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';
    echo '</body>';
    echo '</html>';
    exit;
}

/**
 * Universal spreadsheet row parser for HTML-based .xls, XLSX, and CSV/TSV
 */
function parse_spreadsheet_rows(string $filePath): array {
    $rows = [];
    $raw = @file_get_contents($filePath);
    if ($raw === false) return [];

    // 1. Check if HTML table-based .xls
    if (stripos($raw, '<table') !== false) {
        if (preg_match_all('~<tr[^>]*>(.*?)</tr>~is', $raw, $trMatches)) {
            $isHeader = true;
            foreach ($trMatches[1] as $tr) {
                if (preg_match_all('~<t[dh][^>]*>(.*?)</t[dh]>~is', $tr, $cellMatches)) {
                    $row = [];
                    foreach ($cellMatches[1] as $cell) {
                        $text = strip_tags($cell);
                        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                        $text = trim(preg_replace('/\s+/', ' ', $text));
                        $row[] = $text;
                    }
                    if ($isHeader) {
                        $isHeader = false;
                        continue;
                    }
                    if (!empty(array_filter($row, function($v) { return $v !== ''; }))) {
                        $rows[] = $row;
                    }
                }
            }
        }
        if (!empty($rows)) {
            return $rows;
        }
    }

    // 2. Check if XLSX (Zip Archive)
    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($filePath) === true) {
            $sharedStrings = [];
            if (($xmlString = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
                $xml = @simplexml_load_string($xmlString);
                if ($xml && isset($xml->si)) {
                    foreach ($xml->si as $si) {
                        $sharedStrings[] = (string)($si->t ?? $si->r->t ?? '');
                    }
                }
            }
            if (($sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml')) !== false) {
                $xml = @simplexml_load_string($sheetXml);
                if ($xml && isset($xml->sheetData->row)) {
                    $firstRow = true;
                    foreach ($xml->sheetData->row as $r) {
                        if ($firstRow) {
                            $firstRow = false;
                            continue;
                        }
                        $cols = [];
                        foreach ($r->c as $c) {
                            $val = (string)$c->v;
                            if ((string)$c['t'] === 's' && isset($sharedStrings[(int)$val])) {
                                $val = $sharedStrings[(int)$val];
                            }
                            $cols[] = trim($val);
                        }
                        if (!empty(array_filter($cols, function($v) { return $v !== ''; }))) {
                            $rows[] = $cols;
                        }
                    }
                }
            }
            $zip->close();
            if (!empty($rows)) {
                return $rows;
            }
        }
    }

    // 3. Fallback: Delimited text (CSV, TSV, or Semicolon)
    $lines = preg_split('/\r\n|\r|\n/', trim($raw));
    if (!empty($lines)) {
        $firstLine = $lines[0];
        $delims = [',', "\t", ';'];
        $bestDelim = ',';
        $maxCount = 0;
        foreach ($delims as $d) {
            $cnt = substr_count($firstLine, $d);
            if ($cnt > $maxCount) {
                $maxCount = $cnt;
                $bestDelim = $d;
            }
        }
        $firstRow = true;
        foreach ($lines as $line) {
            if (trim($line) === '') continue;
            $row = str_getcsv($line, $bestDelim);
            if ($firstRow) {
                $firstRow = false;
                continue;
            }
            $cleanRow = array_map('trim', $row);
            if (!empty(array_filter($cleanRow, function($v) { return $v !== ''; }))) {
                $rows[] = $cleanRow;
            }
        }
    }

    return $rows;
}


