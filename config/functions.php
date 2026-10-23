<?php
/**
 * Global Helper Functions & Session / CSRF Management
 * Generator Absensi dan Berita Acara Ujian - SMK Negeri 1 Klungkung
 */

require_once __DIR__ . '/database.php';

if (file_exists(BASE_PATH . '/vendor/autoload.php')) {
    require_once BASE_PATH . '/vendor/autoload.php';
} elseif (file_exists(BASE_PATH . '/vendor/dompdf/autoload.inc.php')) {
    require_once BASE_PATH . '/vendor/dompdf/autoload.inc.php';
}

use Dompdf\Dompdf;
use Dompdf\Options;

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

function terbilang(int|float $n): string {
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
function parse_spreadsheet_rows(string $filePath, bool $includeHeader = true): array {
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
                    if ($isHeader && !$includeHeader) {
                        $isHeader = false;
                        continue;
                    }
                    $isHeader = false;
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
                        if ($firstRow && !$includeHeader) {
                            $firstRow = false;
                            continue;
                        }
                        $firstRow = false;
                        $cols = [];
                        // Handle sparse cells correctly by mapping column letter (A, B, C...) to index
                        foreach ($r->c as $c) {
                            $rAttr = (string)($c['r'] ?? '');
                            $colLetters = preg_replace('/[0-9]/', '', $rAttr);
                            $colIdx = 0;
                            if ($colLetters !== '') {
                                for ($i = 0; $i < strlen($colLetters); $i++) {
                                    $colIdx = $colIdx * 26 + (ord(strtoupper($colLetters[$i])) - ord('A') + 1);
                                }
                                $colIdx -= 1;
                            } else {
                                $colIdx = count($cols);
                            }

                            $val = (string)$c->v;
                            if ((string)$c['t'] === 's' && isset($sharedStrings[(int)$val])) {
                                $val = $sharedStrings[(int)$val];
                            }
                            $cols[$colIdx] = trim($val);
                        }
                        // Fill gaps
                        if (!empty($cols)) {
                            $maxIdx = max(array_keys($cols));
                            $normalizedRow = [];
                            for ($i = 0; $i <= $maxIdx; $i++) {
                                $normalizedRow[$i] = $cols[$i] ?? '';
                            }
                            if (!empty(array_filter($normalizedRow, function($v) { return $v !== ''; }))) {
                                $rows[] = $normalizedRow;
                            }
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
            if ($firstRow && !$includeHeader) {
                $firstRow = false;
                continue;
            }
            $firstRow = false;
            $cleanRow = array_map('trim', $row);
            if (!empty(array_filter($cleanRow, function($v) { return $v !== ''; }))) {
                $rows[] = $cleanRow;
            }
        }
    }

    return $rows;
}

/**
 * Auto-generate and cache physical PDF for an Absensi record if missing
 */
function generate_absensi_pdf_file(int $id): ?string {
    $rec = dbFetch("SELECT * FROM absensi WHERE id = :id", ['id' => $id]);
    if (!$rec) return null;

    if (file_exists(BASE_PATH . '/vendor/autoload.php')) {
        require_once BASE_PATH . '/vendor/autoload.php';
    } elseif (file_exists(BASE_PATH . '/vendor/dompdf/autoload.inc.php')) {
        require_once BASE_PATH . '/vendor/dompdf/autoload.inc.php';
    }
    if (!class_exists('Dompdf\Dompdf')) return null;

    $namaUjian = $rec['nama_ujian'] ?: 'Sumatif Akhir Semester (SAS) Ganjil';
    $tahunAjaran = $rec['tahun_ajaran'] ?: '2026/2027';
    $tanggalUjian = $rec['tanggal_ujian'] ?: date('Y-m-d');
    $mataPelajaran = $rec['mata_pelajaran'] ?: 'Mata Pelajaran';
    $sesi = $rec['sesi'] ?: '1';
    $ruang = $rec['ruang'] ?: 'Ruang Ujian';
    $jamMulai = $rec['jam_mulai'] ?: '07:30';
    $jamSelesai = $rec['jam_selesai'] ?: '09:30';
    $namaKelas = $rec['nama_kelas'] ?: 'Kelas';
    $namaPengawas1 = $rec['nama_pengawas_1'] ?: '-';
    $nipPengawas1 = $rec['nip_pengawas_1'] ?: '';
    $namaPengawas2 = $rec['nama_pengawas_2'] ?: '';
    $nipPengawas2 = $rec['nip_pengawas_2'] ?: '';
    $ttd1 = $rec['ttd_pengawas_1'] ?: '';
    $ttd2 = $rec['ttd_pengawas_2'] ?: '';

    $studentsList = json_decode($rec['data_siswa'] ?? '[]', true) ?: [];
    if (empty($studentsList) && !empty($rec['class_id'])) {
        $stDb = dbFetchAll("SELECT id, nis, nama FROM siswa WHERE class_id = :cid ORDER BY nama ASC", ['cid' => $rec['class_id']]);
        foreach ($stDb as $s) {
            $studentsList[] = [
                'id' => $s['id'],
                'nis' => $s['nis'],
                'nama' => $s['nama'],
                'status' => 'Hadir'
            ];
        }
    }

    $hadir = (int)($rec['peserta_hadir'] ?: count($studentsList));
    $dispensasi = (int)($rec['peserta_dispensasi'] ?: 0);
    $izin = (int)($rec['peserta_izin'] ?: 0);
    $sakit = (int)($rec['peserta_sakit'] ?: 0);
    $tidakHadir = (int)($rec['peserta_tidak_hadir'] ?: 0);
    $totalPeserta = (int)($rec['total_peserta'] ?: count($studentsList));

    $kopPath = BASE_PATH . '/' . get_setting('kop_image_path', 'assets/img/kop-header.png');
    $kopBase64 = '';
    if (file_exists($kopPath)) {
        $type = pathinfo($kopPath, PATHINFO_EXTENSION);
        $data = file_get_contents($kopPath);
        $kopBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
    }

    $hariTanggalIndo = format_date_indo($tanggalUjian);
    $hariArr = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    $hariName = $hariArr[date('w', strtotime($tanggalUjian))] ?? '';

    ob_start();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
    <meta charset="UTF-8">
    <title>Daftar Hadir - <?= htmlspecialchars($namaKelas) ?> - <?= htmlspecialchars($mataPelajaran) ?></title>
    <style>
        @page { size: A4 portrait; margin: 12mm 15mm 12mm 15mm; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 9pt; color: #000; margin: 0; padding: 0; line-height: 1.3; }
        .kop { text-align: center; margin-bottom: 8px; }
        .kop img { width: 100%; max-height: 95px; }
        .doc-title { text-align: center; font-weight: bold; font-size: 11.5pt; line-height: 1.25; margin-bottom: 12px; text-transform: uppercase; }
        .meta-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 9pt; }
        .meta-table td { padding: 2px 1px; vertical-align: top; }
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; font-size: 8.5pt; }
        .data-table th, .data-table td { border: 1px solid #000; padding: 4px 6px; }
        .data-table th { background-color: #f1f3f9; text-align: center; font-weight: bold; }
        .rekap-box { border: 1px solid #000; padding: 6px 10px; margin-bottom: 14px; page-break-inside: avoid; }
        .rekap-table { width: 100%; border-collapse: collapse; margin-top: 4px; text-align: center; font-size: 8.5pt; }
        .rekap-table td { border: 1px solid #000; padding: 3px 6px; }
        .signature-table { width: 100%; border-collapse: collapse; margin-top: 10px; page-break-inside: avoid; font-size: 9pt; }
        .signature-table td { text-align: center; vertical-align: top; padding: 4px; }
        .sig-space { height: 55px; }
        .sig-img { max-height: 50px; max-width: 130px; }
    </style>
    </head>
    <body>
    <div class="kop">
        <?php if ($kopBase64): ?>
            <img src="<?= $kopBase64 ?>">
        <?php else: ?>
            <div style="font-size: 13pt; font-weight: bold;">SMK NEGERI 1 KLUNGKUNG</div>
            <div style="font-size: 9pt;">Generator Absensi & Berita Acara Ujian</div>
            <hr style="border-top: 2px solid #000; margin-top: 4px;">
        <?php endif; ?>
    </div>
    <div class="doc-title">
        DAFTAR HADIR PESERTA UJIAN<br>
        <?= htmlspecialchars($namaUjian) ?><br>
        TAHUN AJARAN <?= htmlspecialchars($tahunAjaran) ?>
    </div>
    <table class="meta-table">
        <tr>
            <td style="width: 16%;">Hari / Tanggal</td>
            <td style="width: 2%;">:</td>
            <td style="width: 38%;"><strong><?= htmlspecialchars($hariName ? "{$hariName}, {$hariTanggalIndo}" : $hariTanggalIndo) ?></strong></td>
            <td style="width: 14%;">Kelas</td>
            <td style="width: 2%;">:</td>
            <td style="width: 28%;"><strong><?= htmlspecialchars($namaKelas) ?></strong></td>
        </tr>
        <tr>
            <td>Mata Pelajaran</td>
            <td>:</td>
            <td><strong><?= htmlspecialchars($mataPelajaran) ?></strong></td>
            <td>Sesi / Ruang</td>
            <td>:</td>
            <td><?= htmlspecialchars($sesi) ?> / <?= htmlspecialchars($ruang) ?></td>
        </tr>
        <tr>
            <td>Waktu Ujian</td>
            <td>:</td>
            <td><?= htmlspecialchars($jamMulai) ?> - <?= htmlspecialchars($jamSelesai) ?> WITA</td>
            <td></td><td></td><td></td>
        </tr>
    </table>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 30px;">No</th>
                <th style="width: 90px;">NIS</th>
                <th>Nama Peserta Didik</th>
                <th style="width: 110px;">Kehadiran</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($studentsList as $idx => $st): ?>
            <tr>
                <td style="text-align: center;"><?= $idx + 1 ?></td>
                <td style="text-align: center;"><?= htmlspecialchars($st['nis'] ?: '-') ?></td>
                <td><?= htmlspecialchars($st['nama']) ?></td>
                <td style="text-align: center; font-weight: <?= ($st['status'] ?? 'Hadir') !== 'Hadir' ? 'bold' : 'normal' ?>; color: <?= ($st['status'] ?? 'Hadir') !== 'Hadir' ? '#dc2626' : '#000' ?>;">
                    <?= htmlspecialchars($st['status'] ?? 'Hadir') ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <div class="rekap-box">
        <strong>Rekapitulasi Kehadiran:</strong>
        <table class="rekap-table">
            <tr>
                <td>Hadir: <strong><?= $hadir ?></strong></td>
                <td>Dispensasi: <strong><?= $dispensasi ?></strong></td>
                <td>Izin: <strong><?= $izin ?></strong></td>
                <td>Sakit: <strong><?= $sakit ?></strong></td>
                <td>Tidak Hadir: <strong><?= $tidakHadir ?></strong></td>
                <td style="background-color: #f8fafc;">Total Peserta: <strong><?= $totalPeserta ?></strong></td>
            </tr>
        </table>
    </div>
    <table class="signature-table">
        <tr>
            <td style="width: 50%;">
                Pengawas I,
                <div class="sig-space">
                    <?php if ($ttd1 && strpos($ttd1, 'data:image') === 0): ?>
                        <img src="<?= $ttd1 ?>" class="sig-img">
                    <?php endif; ?>
                </div>
                <strong><u><?= htmlspecialchars($namaPengawas1) ?></u></strong><br>
                <?= $nipPengawas1 && $nipPengawas1 !== '-' ? 'NIP. ' . htmlspecialchars($nipPengawas1) : '' ?>
            </td>
            <td style="width: 50%;">
                Pengawas II,
                <div class="sig-space">
                    <?php if ($ttd2 && strpos($ttd2, 'data:image') === 0): ?>
                        <img src="<?= $ttd2 ?>" class="sig-img">
                    <?php endif; ?>
                </div>
                <strong><u><?= htmlspecialchars($namaPengawas2 ?: '-') ?></u></strong><br>
                <?= $nipPengawas2 && $nipPengawas2 !== '-' ? 'NIP. ' . htmlspecialchars($nipPengawas2) : '' ?>
            </td>
        </tr>
    </table>
    </body>
    </html>
    <?php
    $html = ob_get_clean();

    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $options->set('isHtml5ParserEnabled', true);
    $options->set('chroot', BASE_PATH);

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $pdfOutput = $dompdf->output();

    $cleanMapel = preg_replace('/[^a-zA-Z0-9_\-]/', ' ', $mataPelajaran);
    $cleanMapel = trim(preg_replace('/\s+/', ' ', $cleanMapel));
    $filename = "DH-SAS GANJIL 26-27-{$namaKelas}-{$cleanMapel}-{$tanggalUjian}.pdf";
    $dir = BASE_PATH . '/storage/absensi';
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    $fullPath = $dir . '/' . $filename;
    file_put_contents($fullPath, $pdfOutput);

    dbUpdate('absensi', [
        'pdf_path' => "storage/absensi/{$filename}",
        'file_size' => strlen($pdfOutput)
    ], 'id = :id', ['id' => $id]);

    return $fullPath;
}

/**
 * Auto-generate and cache physical PDF for a Berita Acara record if missing
 */
function generate_berita_acara_pdf_file(int $id): ?string {
    $rec = dbFetch("SELECT * FROM berita_acara WHERE id = :id", ['id' => $id]);
    if (!$rec) return null;

    if (file_exists(BASE_PATH . '/vendor/autoload.php')) {
        require_once BASE_PATH . '/vendor/autoload.php';
    } elseif (file_exists(BASE_PATH . '/vendor/dompdf/autoload.inc.php')) {
        require_once BASE_PATH . '/vendor/dompdf/autoload.inc.php';
    }
    if (!class_exists('Dompdf\Dompdf')) return null;

    $templateId = (int)($rec['template_id'] ?: 1);
    $tpl = dbFetch("SELECT * FROM template_ujian WHERE id = :id", ['id' => $templateId]);
    if (!$tpl) {
        $tpl = dbFetch("SELECT * FROM template_ujian WHERE aktif = 1 LIMIT 1");
    }

    $namaUjian = $rec['nama_ujian'] ?: 'Sumatif Akhir Semester (SAS) Ganjil';
    $tahunAjaran = $rec['tahun_ajaran'] ?: '2026/2027';
    $tanggalUjian = $rec['tanggal_ujian'] ?: date('Y-m-d');
    $mataPelajaran = $rec['mata_pelajaran'] ?: 'Mata Pelajaran';
    $namaKelas = $rec['nama_kelas'] ?: 'Kelas';
    $ruang = $rec['ruang'] ?: 'Ruang Ujian';
    $jamMulai = $rec['jam_mulai'] ?: '07:30';
    $jamSelesai = $rec['jam_selesai'] ?: '09:30';
    $seharusnya = (int)($rec['peserta_seharusnya'] ?: 0);
    $hadir = (int)($rec['peserta_hadir'] ?: 0);
    $tidakHadir = (int)($rec['peserta_tidak_hadir'] ?: 0);
    $blangkoBA = (int)($rec['blangko_berita_acara'] ?: 1);
    $blangkoDH = (int)($rec['blangko_daftar_hadir'] ?: 1);
    $catatan = $rec['catatan_pelaksanaan'] ?: 'Pelaksanaan ujian berjalan tertib, aman, dan lancar tanpa kendala teknis.';
    $namaPengawas1 = $rec['nama_pengawas_1'] ?: '-';
    $nipPengawas1 = $rec['nip_pengawas_1'] ?: '';
    $namaPengawas2 = $rec['nama_pengawas_2'] ?: '';
    $nipPengawas2 = $rec['nip_pengawas_2'] ?: '';
    $ttd1 = $rec['ttd_pengawas_1'] ?: '';
    $ttd2 = $rec['ttd_pengawas_2'] ?: '';

    $savedPhotos = [];
    if (!empty($rec['foto_dokumentasi'])) {
        $photos = json_decode($rec['foto_dokumentasi'], true);
        if (is_array($photos)) {
            $savedPhotos = $photos;
        }
    }

    $namaSekolah = get_setting('nama_sekolah', 'SMK NEGERI 1 KLUNGKUNG');
    $kopPath = BASE_PATH . '/' . get_setting('kop_image_path', 'assets/img/kop-header.png');
    $kopBase64 = '';
    if (file_exists($kopPath)) {
        $type = pathinfo($kopPath, PATHINFO_EXTENSION);
        $data = file_get_contents($kopPath);
        $kopBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
    }

    $timestamp = strtotime($tanggalUjian);
    $hariArr = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    $bulanArr = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

    $hari = $hariArr[date('w', $timestamp)] ?? '';
    $tglNum = (int)date('j', $timestamp);
    $blnName = $bulanArr[(int)date('n', $timestamp)] ?? '';
    $thnNum = date('Y', $timestamp);
    $tglTeks = terbilang($tglNum);
    $thnTeks = terbilang($thnNum);

    $vars = [
        'nama_ujian' => $namaUjian,
        'tahun_ajaran' => $tahunAjaran,
        'hari' => $hari,
        'tanggal' => (string)$tglNum,
        'tanggal_teks' => $tglTeks,
        'bulan' => $blnName,
        'tahun' => (string)$thnNum,
        'tahun_teks' => $thnTeks,
        'jam_mulai' => $jamMulai,
        'jam_selesai' => $jamSelesai,
        'sekolah' => $namaSekolah,
        'mata_pelajaran' => $mataPelajaran,
        'kelas' => $namaKelas,
        'ruang' => $ruang,
        'peserta_seharusnya' => (string)$seharusnya,
        'peserta_hadir' => (string)$hadir,
        'peserta_tidak_hadir' => (string)$tidakHadir,
        'blangko_berita_acara' => (string)$blangkoBA,
        'blangko_daftar_hadir' => (string)$blangkoDH,
        'catatan_pelaksanaan' => $catatan
    ];

    $rawRedaksi = $tpl['redaksi_isi'] ?? '';
    foreach ($vars as $k => $v) {
        $valHtml = ($k === 'catatan_pelaksanaan') ? nl2br(htmlspecialchars($v)) : htmlspecialchars($v);
        $rawRedaksi = str_ireplace('[' . $k . ']', $valHtml, $rawRedaksi);
    }

    $judulDoc = $tpl['judul_berita_acara'] ?? "BERITA ACARA\r\nPENYELENGGARAAN SUMATIF AKHIR SEMESTER (SAS) GANJIL\r\nTAHUN AJARAN [tahun_ajaran]";
    foreach ($vars as $k => $v) {
        $judulDoc = str_ireplace('[' . $k . ']', htmlspecialchars($v), $judulDoc);
    }
    $judulDocHtml = nl2br($judulDoc);

    $photoBase64List = [];
    foreach ($savedPhotos as $rel) {
        $full = BASE_PATH . '/' . $rel;
        if (file_exists($full)) {
            $pType = pathinfo($full, PATHINFO_EXTENSION);
            $pData = file_get_contents($full);
            $photoBase64List[] = 'data:image/' . $pType . ';base64,' . base64_encode($pData);
        }
    }

    ob_start();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
    <meta charset="UTF-8">
    <title>Berita Acara - <?= htmlspecialchars($namaKelas) ?> - <?= htmlspecialchars($mataPelajaran) ?></title>
    <style>
        @page { size: A4 portrait; margin: 12mm 15mm 12mm 15mm; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 9.5pt; color: #000; margin: 0; padding: 0; line-height: 1.4; }
        .kop { text-align: center; margin-bottom: 8px; }
        .kop img { width: 100%; max-height: 95px; }
        .doc-title { text-align: center; font-weight: bold; font-size: 11.5pt; line-height: 1.3; margin-bottom: 14px; text-transform: uppercase; }
        .redaksi-body { font-size: 9.5pt; line-height: 1.5; text-align: justify; }
        .redaksi-body ol, .redaksi-body ul { margin-top: 4px; margin-bottom: 8px; padding-left: 20px; }
        .redaksi-body li { margin-bottom: 6px; }
        .signature-table { width: 100%; border-collapse: collapse; margin-top: 14px; page-break-inside: avoid; font-size: 9.5pt; }
        .signature-table td { text-align: center; vertical-align: top; padding: 4px; }
        .sig-space { height: 55px; }
        .sig-img { max-height: 50px; max-width: 130px; }
        .photos-box { margin-top: 14px; page-break-inside: avoid; border-top: 1px dashed #cbd5e1; padding-top: 10px; }
        .photos-title { font-size: 9pt; font-weight: bold; margin-bottom: 8px; }
        .photo-grid { width: 100%; border-collapse: collapse; }
        .photo-grid td { width: 50%; padding: 4px; text-align: center; vertical-align: middle; }
        .photo-grid img { max-width: 100%; max-height: 140px; border: 1px solid #94a3b8; }
    </style>
    </head>
    <body>
    <div class="kop">
        <?php if ($kopBase64): ?>
            <img src="<?= $kopBase64 ?>">
        <?php else: ?>
            <div style="font-size: 13pt; font-weight: bold;">SMK NEGERI 1 KLUNGKUNG</div>
            <div style="font-size: 9pt;">Generator Absensi & Berita Acara Ujian</div>
            <hr style="border-top: 2px solid #000; margin-top: 4px;">
        <?php endif; ?>
    </div>
    <div class="doc-title"><?= $judulDocHtml ?></div>
    <div class="redaksi-body"><?= $rawRedaksi ?></div>
    <table class="signature-table">
        <tr>
            <td style="width: 50%;">
                Pengawas I,
                <div class="sig-space">
                    <?php if ($ttd1 && strpos($ttd1, 'data:image') === 0): ?>
                        <img src="<?= $ttd1 ?>" class="sig-img">
                    <?php endif; ?>
                </div>
                <strong><u><?= htmlspecialchars($namaPengawas1) ?></u></strong><br>
                <?= $nipPengawas1 && $nipPengawas1 !== '-' ? 'NIP. ' . htmlspecialchars($nipPengawas1) : '' ?>
            </td>
            <td style="width: 50%;">
                Pengawas II,
                <div class="sig-space">
                    <?php if ($ttd2 && strpos($ttd2, 'data:image') === 0): ?>
                        <img src="<?= $ttd2 ?>" class="sig-img">
                    <?php endif; ?>
                </div>
                <strong><u><?= htmlspecialchars($namaPengawas2 ?: '-') ?></u></strong><br>
                <?= $nipPengawas2 && $nipPengawas2 !== '-' ? 'NIP. ' . htmlspecialchars($nipPengawas2) : '' ?>
            </td>
        </tr>
    </table>
    <?php if (!empty($photoBase64List)): ?>
    <div class="photos-box">
        <div class="photos-title">Dokumentasi Pelaksanaan Ujian:</div>
        <table class="photo-grid">
            <tr>
            <?php foreach ($photoBase64List as $idx => $pBase): ?>
                <?php if ($idx > 0 && $idx % 2 === 0): ?></tr><tr><?php endif; ?>
                <td><img src="<?= $pBase ?>"></td>
            <?php endforeach; ?>
            </tr>
        </table>
    </div>
    <?php endif; ?>
    </body>
    </html>
    <?php
    $html = ob_get_clean();

    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $options->set('isHtml5ParserEnabled', true);
    $options->set('chroot', BASE_PATH);

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $pdfOutput = $dompdf->output();

    $cleanMapel = preg_replace('/[^a-zA-Z0-9_\-]/', ' ', $mataPelajaran);
    $cleanMapel = trim(preg_replace('/\s+/', ' ', $cleanMapel));
    $filename = "BA-SAS GANJIL 26-27-{$namaKelas}-{$cleanMapel}-{$tanggalUjian}.pdf";
    $dir = BASE_PATH . '/storage/berita-acara';
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    $fullPath = $dir . '/' . $filename;
    file_put_contents($fullPath, $pdfOutput);

    dbUpdate('berita_acara', [
        'pdf_path' => "storage/berita-acara/{$filename}",
        'file_size' => strlen($pdfOutput)
    ], 'id = :id', ['id' => $id]);

    return $fullPath;
}



