<?php
require_once __DIR__ . '/../config/functions.php';
require_admin();

// Download Excel (.xls) Templates
if (isset($_GET['template'])) {
    $tplType = trim($_GET['template']);
    
    if ($tplType === 'guru') {
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="template_impor_guru.xls"');
        header('Pragma: no-cache');
        header('Expires: 0');
        ?>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style>
        table { border-collapse: collapse; font-family: Arial, sans-serif; font-size: 11pt; }
        th { background-color: #4f46e5; color: #ffffff; font-weight: bold; padding: 8px 12px; border: 1px solid #3730a3; text-align: left; }
        td { padding: 6px 12px; border: 1px solid #cbd5e1; mso-number-format:"\@"; }
    </style>
</head>
<body>
    <table>
        <thead>
            <tr>
                <th>Nama Lengkap Guru</th>
                <th>NIP / NIPPPK</th>
                <th>Jabatan</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Dra. Ni Made Wardani, M.Pd.</td>
                <td style="mso-number-format:'\@';">196805121994032008</td>
                <td>Guru / Pengawas</td>
            </tr>
            <tr>
                <td>I Ketut Suartana, S.Kom.</td>
                <td style="mso-number-format:'\@';">198203152010011019</td>
                <td>Guru / Pengawas</td>
            </tr>
            <tr>
                <td>Ni Putu Eka Santi, S.Pd.</td>
                <td style="mso-number-format:'\@';">-</td>
                <td>Guru / Pengawas</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
<?php
        exit;
    } elseif ($tplType === 'mapel') {
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="template_impor_mapel.xls"');
        header('Pragma: no-cache');
        header('Expires: 0');
        ?>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style>
        table { border-collapse: collapse; font-family: Arial, sans-serif; font-size: 11pt; }
        th { background-color: #0284c7; color: #ffffff; font-weight: bold; padding: 8px 12px; border: 1px solid #0369a1; text-align: left; }
        td { padding: 6px 12px; border: 1px solid #cbd5e1; mso-number-format:"\@"; }
    </style>
</head>
<body>
    <table>
        <thead>
            <tr>
                <th>Kode Mapel</th>
                <th>Nama Mata Pelajaran</th>
                <th>Kelompok</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="mso-number-format:'\@';">BIN-X</td>
                <td>Bahasa Indonesia</td>
                <td>Umum</td>
            </tr>
            <tr>
                <td style="mso-number-format:'\@';">MAT-X</td>
                <td>Matematika</td>
                <td>Umum</td>
            </tr>
            <tr>
                <td style="mso-number-format:'\@';">RPL-01</td>
                <td>Pemrograman Web</td>
                <td>Kejuruan</td>
            </tr>
            <tr>
                <td style="mso-number-format:'\@';">ML-BLI</td>
                <td>Bahasa Bali</td>
                <td>Muatan Lokal</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
<?php
        exit;
    } else {
        // Template Siswa (.xls)
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="template_impor_siswa.xls"');
        header('Pragma: no-cache');
        header('Expires: 0');
        ?>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style>
        table { border-collapse: collapse; font-family: Arial, sans-serif; font-size: 11pt; }
        th { background-color: #059669; color: #ffffff; font-weight: bold; padding: 8px 12px; border: 1px solid #047857; text-align: left; }
        td { padding: 6px 12px; border: 1px solid #cbd5e1; mso-number-format:"\@"; }
    </style>
</head>
<body>
    <table>
        <thead>
            <tr>
                <th>NIS</th>
                <th>NISN</th>
                <th>Nama Siswa</th>
                <th>Kelas</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="mso-number-format:'\@';">13741</td>
                <td style="mso-number-format:'\@';">0061234567</td>
                <td>I Putu Gede Pratama</td>
                <td>XII TKJ</td>
            </tr>
            <tr>
                <td style="mso-number-format:'\@';">13742</td>
                <td style="mso-number-format:'\@';">0061234568</td>
                <td>Ni Kadek Ayu Lestari</td>
                <td>XII TKJ</td>
            </tr>
            <tr>
                <td style="mso-number-format:'\@';">13743</td>
                <td style="mso-number-format:'\@';">-</td>
                <td>I Made Arya Wijaya</td>
                <td>XII RPL 1</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
<?php
        exit;
    }
}

/**
 * Universal spreadsheet parser for HTML-based .xls, XLSX zip, and CSV/TSV
 */
function parseSpreadsheetRows($filePath, $origName) {
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
            $data = str_getcsv($line, $bestDelim);
            if ($firstRow) {
                $firstRow = false;
                continue;
            }
            if (!empty(array_filter($data, function($v) { return trim($v) !== ''; }))) {
                $rows[] = array_map('trim', $data);
            }
        }
    }

    return $rows;
}

$pageTitle = 'Impor Data Excel (Siswa, Guru & Mapel)';
$activeTab = in_array($_GET['type'] ?? '', ['guru', 'mapel']) ? $_GET['type'] : 'siswa';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $target = $_POST['target'] ?? 'siswa';

    if ($target === 'guru') {
        $fileInputName = !empty($_FILES['file_guru']['tmp_name']) ? 'file_guru' : 'file_excel';
        if (!empty($_FILES[$fileInputName]['tmp_name'])) {
            $file = $_FILES[$fileInputName]['tmp_name'];
            $origName = $_FILES[$fileInputName]['name'];
            $rows = parseSpreadsheetRows($file, $origName);

            if (!empty($rows)) {
                $importedCount = 0;
                $updatedCount = 0;

                foreach ($rows as $cols) {
                    // Detect if column 0 is a sequence No
                    if (count($cols) >= 3 && is_numeric($cols[0]) && !is_numeric($cols[1])) {
                        $nama = $cols[1] ?? '';
                        $nip = $cols[2] ?? '-';
                        $jabatan = $cols[3] ?? 'Guru / Pengawas';
                    } elseif (count($cols) >= 2) {
                        $nama = $cols[0] ?? '';
                        $nip = $cols[1] ?? '-';
                        $jabatan = $cols[2] ?? 'Guru / Pengawas';
                    } else {
                        $nama = $cols[0] ?? '';
                        $nip = '-';
                        $jabatan = 'Guru / Pengawas';
                    }

                    $nama = trim($nama);
                    $nip = trim($nip);
                    $jabatan = trim($jabatan);

                    if ($nama === '' || in_array(strtolower($nama), ['nama', 'nama guru', 'nama lengkap', 'nama lengkap guru', 'no', 'nomor'])) {
                        continue;
                    }
                    if ($nip === '' || $nip === '0') {
                        $nip = '-';
                    }
                    if ($jabatan === '') {
                        $jabatan = 'Guru / Pengawas';
                    }

                    // Check existing guru
                    $existing = dbFetch("SELECT id FROM guru WHERE UPPER(TRIM(nama)) = :n", ['n' => strtoupper($nama)]);
                    if ($existing) {
                        dbUpdate('guru', [
                            'nip' => $nip,
                            'jabatan' => $jabatan,
                            'aktif' => 1,
                            'updated_at' => date('Y-m-d H:i:s')
                        ], 'id = :id', ['id' => $existing['id']]);
                        $updatedCount++;
                    } else {
                        dbInsert('guru', [
                            'nama' => $nama,
                            'nip' => $nip,
                            'jabatan' => $jabatan,
                            'aktif' => 1,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
                        $importedCount++;
                    }
                }

                $totalProcessed = $importedCount + $updatedCount;
                log_activity('IMPORT_GURU', "Mengimpor {$importedCount} guru baru dan memperbarui {$updatedCount} guru dari berkas {$origName}.");
                set_flash('success', "Berhasil memproses {$totalProcessed} data guru ({$importedCount} data baru ditambahkan, {$updatedCount} diperbarui).");
                header('Location: /admin/guru.php');
                exit;
            } else {
                set_flash('danger', 'Tidak ada data valid yang dapat dibaca dari berkas tersebut. Pastikan format kolom sesuai template.');
            }
        } else {
            set_flash('danger', 'Silakan pilih berkas Excel (.xls) guru terlebih dahulu.');
        }
    } elseif ($target === 'mapel') {
        $fileInputName = !empty($_FILES['file_mapel']['tmp_name']) ? 'file_mapel' : 'file_excel';
        if (!empty($_FILES[$fileInputName]['tmp_name'])) {
            $file = $_FILES[$fileInputName]['tmp_name'];
            $origName = $_FILES[$fileInputName]['name'];
            $rows = parse_spreadsheet_rows($file, false);
            if (empty($rows)) {
                $rows = parseSpreadsheetRows($file, $origName);
            }

            if (!empty($rows)) {
                $importedCount = 0;
                $updatedCount = 0;

                foreach ($rows as $cols) {
                    if (count($cols) >= 4 && is_numeric($cols[0]) && !empty($cols[1])) {
                        $kode = $cols[1] ?? '';
                        $nama = $cols[2] ?? '';
                        $kelompok = $cols[3] ?? 'Umum';
                    } elseif (count($cols) >= 3) {
                        $kode = $cols[0] ?? '';
                        $nama = $cols[1] ?? '';
                        $kelompok = $cols[2] ?? 'Umum';
                    } elseif (count($cols) === 2) {
                        $kode = $cols[0] ?? '';
                        $nama = $cols[1] ?? '';
                        $kelompok = 'Umum';
                    } else {
                        $kode = '';
                        $nama = $cols[0] ?? '';
                        $kelompok = 'Umum';
                    }

                    $kode = trim($kode);
                    $nama = trim($nama);
                    $kelompok = trim($kelompok);

                    if ($nama === '' || in_array(strtolower($nama), ['nama', 'nama mapel', 'nama mata pelajaran', 'mata pelajaran', 'no', 'nomor'])) {
                        continue;
                    }
                    if ($kelompok === '') $kelompok = 'Umum';

                    $existing = dbFetch("SELECT id FROM mapel WHERE UPPER(TRIM(nama_mapel)) = :n", ['n' => strtoupper($nama)]);
                    if ($existing) {
                        dbUpdate('mapel', [
                            'kode_mapel' => $kode,
                            'kelompok' => $kelompok,
                            'aktif' => 1,
                            'updated_at' => date('Y-m-d H:i:s')
                        ], 'id = :id', ['id' => $existing['id']]);
                        $updatedCount++;
                    } else {
                        dbInsert('mapel', [
                            'kode_mapel' => $kode,
                            'nama_mapel' => $nama,
                            'kelompok' => $kelompok,
                            'aktif' => 1,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
                        $importedCount++;
                    }
                }

                $totalProcessed = $importedCount + $updatedCount;
                log_activity('IMPORT_MAPEL', "Mengimpor {$importedCount} mapel baru dan memperbarui {$updatedCount} mapel dari berkas {$origName}.");
                set_flash('success', "Berhasil memproses {$totalProcessed} mata pelajaran ({$importedCount} baru, {$updatedCount} diperbarui).");
                header('Location: /admin/mapel.php');
                exit;
            } else {
                set_flash('danger', 'Tidak ada data valid yang dapat dibaca dari berkas tersebut. Pastikan format kolom sesuai template.');
            }
        } else {
            set_flash('danger', 'Silakan pilih berkas Excel (.xls) mapel terlebih dahulu.');
        }
    } else {
        // Target: Siswa
        $fileInputName = !empty($_FILES['file_siswa']['tmp_name']) ? 'file_siswa' : 'file_excel';
        if (!empty($_FILES[$fileInputName]['tmp_name'])) {
            $file = $_FILES[$fileInputName]['tmp_name'];
            $origName = $_FILES[$fileInputName]['name'];
            $rows = parse_spreadsheet_rows($file, true);

            if (!empty($rows)) {
                $classCache = [];
                $allKelas = dbFetchAll("SELECT id, UPPER(TRIM(nama_kelas)) as uname FROM kelas");
                foreach ($allKelas as $ak) {
                    $classCache[$ak['uname']] = $ak['id'];
                }

                $importedCount = 0;
                $updatedCount = 0;
                $headerMap = null;

                foreach ($rows as $cols) {
                    if (empty(array_filter($cols, function($v) { return trim((string)$v) !== ''; }))) {
                        continue;
                    }

                    // Deteksi baris header
                    if ($headerMap === null) {
                        $lowerRow = array_map(function($c) { return strtolower(trim((string)$c)); }, $cols);
                        $hasHeaderKeywords = false;
                        foreach ($lowerRow as $val) {
                            if (strpos($val, 'nama') !== false || strpos($val, 'siswa') !== false || strpos($val, 'kelas') !== false) {
                                $hasHeaderKeywords = true;
                                break;
                            }
                        }

                        if ($hasHeaderKeywords) {
                            $headerMap = [];
                            foreach ($lowerRow as $idx => $val) {
                                if (strpos($val, 'nama') !== false || strpos($val, 'siswa') !== false) {
                                    $headerMap['nama'] = $idx;
                                } elseif (strpos($val, 'nisn') !== false) {
                                    $headerMap['nisn'] = $idx;
                                } elseif (strpos($val, 'nis') !== false) {
                                    $headerMap['nis'] = $idx;
                                } elseif (strpos($val, 'kelas') !== false || strpos($val, 'rombel') !== false) {
                                    $headerMap['kelas'] = $idx;
                                }
                            }
                            continue; // Lewati header
                        }
                    }

                    $nama = '';
                    $kelasName = '';
                    $nis = '';
                    $nisn = '';

                    if ($headerMap !== null && isset($headerMap['nama'])) {
                        $nama = $cols[$headerMap['nama']] ?? '';
                        $kelasName = isset($headerMap['kelas']) ? ($cols[$headerMap['kelas']] ?? '') : '';
                        $nis = isset($headerMap['nis']) ? ($cols[$headerMap['nis']] ?? '') : '';
                        $nisn = isset($headerMap['nisn']) ? ($cols[$headerMap['nisn']] ?? '') : '';
                    } else {
                        if (count($cols) >= 5 && is_numeric($cols[0]) && !is_numeric($cols[1])) {
                            $kelasName = $cols[1] ?? '';
                            $nis = $cols[2] ?? '';
                            $nisn = $cols[3] ?? '';
                            $nama = $cols[4] ?? '';
                        } elseif (count($cols) >= 4) {
                            $nis = $cols[0] ?? '';
                            $nisn = $cols[1] ?? '';
                            $nama = $cols[2] ?? '';
                            $kelasName = $cols[3] ?? '';
                        } elseif (count($cols) === 3) {
                            $nis = $cols[0] ?? '';
                            $nama = $cols[1] ?? '';
                            $kelasName = $cols[2] ?? '';
                        } else {
                            $nama = $cols[0] ?? '';
                            $kelasName = $cols[1] ?? '';
                        }
                    }

                    $nama = trim((string)$nama);
                    $kelasName = trim((string)$kelasName);
                    $nis = trim((string)$nis);
                    $nisn = trim((string)$nisn);

                    if ($nama === '' || in_array(strtolower($nama), ['nama', 'nama siswa', 'nama lengkap', 'no', 'nomor'])) {
                        continue;
                    }
                    if ($kelasName === '') {
                        $kelasName = 'Umum';
                    }

                    $uname = strtoupper($kelasName);
                    if (!isset($classCache[$uname])) {
                        $tingkat = 'XII';
                        if (stripos($kelasName, 'X ') === 0 || strtoupper(substr($kelasName, 0, 2)) === 'X ') $tingkat = 'X';
                        elseif (stripos($kelasName, 'XI ') === 0 || strtoupper(substr($kelasName, 0, 3)) === 'XI ') $tingkat = 'XI';

                        $newCid = dbInsert('kelas', [
                            'nama_kelas' => $kelasName,
                            'tingkat' => $tingkat,
                            'program_keahlian' => '',
                            'aktif' => 1,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
                        $classCache[$uname] = $newCid;
                    }
                    $cid = $classCache[$uname];

                    $existing = null;
                    if ($nis !== '' && $nis !== '-') {
                        $existing = dbFetch("SELECT id FROM siswa WHERE nis = :nis AND class_id = :cid", ['nis' => $nis, 'cid' => $cid]);
                    }
                    if (!$existing) {
                        $existing = dbFetch("SELECT id FROM siswa WHERE LOWER(TRIM(nama)) = LOWER(:n) AND class_id = :cid", [
                            'n' => $nama,
                            'cid' => $cid
                        ]);
                    }

                    if ($existing) {
                        dbUpdate('siswa', [
                            'nis' => $nis,
                            'nisn' => $nisn,
                            'nama' => $nama,
                            'aktif' => 1,
                            'updated_at' => date('Y-m-d H:i:s')
                        ], 'id = :id', ['id' => $existing['id']]);
                        $updatedCount++;
                    } else {
                        dbInsert('siswa', [
                            'class_id' => $cid,
                            'nis' => $nis,
                            'nisn' => $nisn,
                            'nama' => $nama,
                            'aktif' => 1,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
                        $importedCount++;
                    }
                }

                $total = $importedCount + $updatedCount;
                log_activity('IMPORT_SISWA', "Mengimpor {$importedCount} data siswa baru dan memperbarui {$updatedCount} data dari berkas {$origName}.");
                set_flash('success', "Berhasil memproses {$total} data siswa ({$importedCount} baru, {$updatedCount} diperbarui) ke dalam sistem.");
                header('Location: /admin/siswa.php');
                exit;
            } else {
                set_flash('danger', 'Tidak ada data valid yang dapat dibaca dari berkas tersebut. Pastikan format kolom sesuai template.');
            }
        } else {
            set_flash('danger', 'Silakan pilih berkas Excel (.xls) siswa terlebih dahulu.');
        }
    }
}

require_once __DIR__ . '/layout_header.php';
?>

<!-- Modern Header Card -->
<div class="page-header-card">
    <div class="page-header-content">
        <div class="page-header-icon-box">
            <i class="bi bi-file-earmark-spreadsheet-fill"></i>
        </div>
        <div>
            <h1 class="page-header-title">Impor Data Excel</h1>
            <p class="page-header-subtitle">
                Unggah data siswa dan data guru pengawas dalam format spreadsheet Excel (.xls) dengan pencocokan otomatis.
            </p>
        </div>
    </div>
</div>

<!-- Tab Navigasi Pilihan Impor -->
<div style="display: flex; gap: 0.65rem; margin-bottom: 1.5rem; flex-wrap: wrap;">
    <button type="button" class="btn <?= $activeTab === 'siswa' ? 'btn-primary' : 'btn-secondary' ?>" 
            id="tabBtnSiswa" onclick="switchImportTab('siswa')" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.6rem 1.25rem;">
        <i class="bi bi-people-fill"></i>
        <span>Unggah Berkas Siswa (.xls)</span>
    </button>
    <button type="button" class="btn <?= $activeTab === 'guru' ? 'btn-primary' : 'btn-secondary' ?>" 
            id="tabBtnGuru" onclick="switchImportTab('guru')" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.6rem 1.25rem;">
        <i class="bi bi-person-badge-fill"></i>
        <span>Unggah Berkas Guru (.xls)</span>
    </button>
    <button type="button" class="btn <?= $activeTab === 'mapel' ? 'btn-primary' : 'btn-secondary' ?>" 
            id="tabBtnMapel" onclick="switchImportTab('mapel')" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.6rem 1.25rem;">
        <i class="bi bi-journal-bookmark-fill"></i>
        <span>Unggah Berkas Mapel (.xls)</span>
    </button>
</div>

<!-- SECTION 1: UNGGAH BERKAS SISWA -->
<div class="card" id="cardImportSiswa" style="max-width: 720px; <?= $activeTab === 'siswa' ? '' : 'display: none;' ?>">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
        <span class="card-title">
            <i class="bi bi-mortarboard-fill" style="color: var(--primary);"></i> Unggah Berkas Siswa
        </span>
        <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
            <a href="/admin/siswa.php" class="btn btn-secondary btn-sm" title="Kembali ke Kelola Data Siswa Peserta Ujian">
                <i class="bi bi-arrow-left"></i> Kembali ke Data Siswa
            </a>
            <a href="/admin/import-siswa.php?template=siswa" class="btn btn-secondary btn-sm" title="Unduh format spreadsheet siswa">
                <i class="bi bi-download"></i> Unduh Template Siswa (.xls)
            </a>
        </div>
    </div>
    <div class="card-body">
        <form method="POST" action="/admin/import-siswa.php" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="target" value="siswa">

            <div class="form-group">
                <label class="form-label" for="file_siswa">Pilih Berkas Excel (.xls) Siswa <span class="required">*</span></label>
                <input type="file" name="file_siswa" id="file_siswa" class="form-control" accept=".xls,.xlsx,.csv" required>
                <div class="form-hint">
                    Format utama yang didukung: <strong>.xls (Spreadsheet Excel)</strong>, .xlsx, .csv (Maksimal 5 MB).
                </div>
            </div>

            <div style="background: var(--slate-50); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 1.1rem; margin-bottom: 1.25rem;">
                <div style="font-weight: 700; font-size: 0.875rem; margin-bottom: 0.5rem; color: var(--slate-800);">
                    Format Kolom Spreadsheet Siswa:
                </div>
                <ul style="font-size: 0.85rem; color: var(--slate-600); padding-left: 1.25rem; line-height: 1.6;">
                    <li><strong>NIS</strong>: Nomor Induk Siswa (Opsional)</li>
                    <li><strong>NISN</strong>: Nomor Induk Siswa Nasional (Opsional)</li>
                    <li><strong>Nama Siswa</strong>: Nama lengkap siswa peserta ujian (Wajib)</li>
                    <li><strong>Kelas</strong>: Nama kelas, contoh: <em>XII RPL 1</em> (Wajib, dibuat otomatis jika belum ada)</li>
                </ul>
            </div>

            <button type="submit" class="btn btn-primary btn-block">
                <i class="bi bi-cloud-arrow-up-fill"></i> Unggah & Proses Impor Siswa
            </button>
        </form>
    </div>
</div>

<!-- SECTION 2: UNGGAH BERKAS GURU -->
<div class="card" id="cardImportGuru" style="max-width: 720px; <?= $activeTab === 'guru' ? '' : 'display: none;' ?>">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
        <span class="card-title">
            <i class="bi bi-person-badge-fill" style="color: #4f46e5;"></i> Unggah Berkas Guru
        </span>
        <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
            <a href="/admin/guru.php" class="btn btn-secondary btn-sm" title="Kembali ke Data Guru & Pengawas Ujian">
                <i class="bi bi-arrow-left"></i> Kembali ke Data Guru
            </a>
            <a href="/admin/import-siswa.php?template=guru" class="btn btn-secondary btn-sm" title="Unduh format spreadsheet guru">
                <i class="bi bi-download"></i> Unduh Template Guru (.xls)
            </a>
        </div>
    </div>
    <div class="card-body">
        <form method="POST" action="/admin/import-siswa.php" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="target" value="guru">

            <div class="form-group">
                <label class="form-label" for="file_guru">Pilih Berkas Excel (.xls) Guru <span class="required">*</span></label>
                <input type="file" name="file_guru" id="file_guru" class="form-control" accept=".xls,.xlsx,.csv" required>
                <div class="form-hint">
                    Format utama yang didukung: <strong>.xls (Spreadsheet Excel)</strong>, .xlsx, .csv (Maksimal 5 MB).
                </div>
            </div>

            <div style="background: var(--slate-50); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 1.1rem; margin-bottom: 1.25rem;">
                <div style="font-weight: 700; font-size: 0.875rem; margin-bottom: 0.5rem; color: var(--slate-800);">
                    Format Kolom Spreadsheet Guru:
                </div>
                <ul style="font-size: 0.85rem; color: var(--slate-600); padding-left: 1.25rem; line-height: 1.6;">
                    <li><strong>Nama Lengkap Guru</strong>: Nama guru pengawas beserta gelar (Wajib, misal: <em>Dra. Ni Made Wardani, M.Pd.</em>)</li>
                    <li><strong>NIP / NIPPPK</strong>: Nomor Induk Pegawai (Opsional, isi tanda strip <code>-</code> jika belum ada)</li>
                    <li><strong>Jabatan</strong>: Jabatan/Keterangan penugasan (Opsional, bawaan: <em>Guru / Pengawas</em>)</li>
                </ul>
            </div>

            <button type="submit" class="btn btn-primary btn-block" style="background: #4f46e5; border-color: #4f46e5;">
                <i class="bi bi-cloud-arrow-up-fill"></i> Unggah & Proses Impor Guru
            </button>
        </form>
    </div>
</div>

<!-- SECTION 3: UNGGAH BERKAS MAPEL -->
<div class="card" id="cardImportMapel" style="max-width: 720px; <?= $activeTab === 'mapel' ? '' : 'display: none;' ?>">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
        <span class="card-title">
            <i class="bi bi-journal-bookmark-fill" style="color: #0284c7;"></i> Unggah Berkas Mapel
        </span>
        <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
            <a href="/admin/mapel.php" class="btn btn-secondary btn-sm" title="Kembali ke Kelola Data Mata Pelajaran">
                <i class="bi bi-arrow-left"></i> Kembali ke Data Mapel
            </a>
            <a href="/admin/import-siswa.php?template=mapel" class="btn btn-secondary btn-sm" title="Unduh format spreadsheet mata pelajaran">
                <i class="bi bi-download"></i> Unduh Template Mapel (.xls)
            </a>
        </div>
    </div>
    <div class="card-body">
        <form method="POST" action="/admin/import-siswa.php" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="target" value="mapel">

            <div class="form-group">
                <label class="form-label" for="file_mapel">Pilih Berkas Excel (.xls) Mata Pelajaran <span class="required">*</span></label>
                <input type="file" name="file_mapel" id="file_mapel" class="form-control" accept=".xls,.xlsx,.csv" required>
                <div class="form-hint">
                    Format utama yang didukung: <strong>.xls (Spreadsheet Excel)</strong>, .xlsx, .csv (Maksimal 5 MB).
                </div>
            </div>

            <div style="background: var(--slate-50); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 1.1rem; margin-bottom: 1.25rem;">
                <div style="font-weight: 700; font-size: 0.875rem; margin-bottom: 0.5rem; color: var(--slate-800);">
                    Format Kolom Spreadsheet Mata Pelajaran:
                </div>
                <ul style="font-size: 0.85rem; color: var(--slate-600); padding-left: 1.25rem; line-height: 1.6;">
                    <li><strong>Kode Mapel</strong>: Kode singkatan/identifikasi (Opsional, misal: <em>BIN-X</em>, <em>RPL-01</em>)</li>
                    <li><strong>Nama Mata Pelajaran</strong>: Nama lengkap mata pelajaran (Wajib, misal: <em>Bahasa Indonesia</em>, <em>Pemrograman Web</em>)</li>
                    <li><strong>Kelompok</strong>: Kelompok mata pelajaran (Opsional, pilihan: <em>Umum</em>, <em>Kejuruan</em>, <em>Muatan Lokal</em>)</li>
                </ul>
            </div>

            <button type="submit" class="btn btn-primary btn-block" style="background: #0284c7; border-color: #0284c7;">
                <i class="bi bi-cloud-arrow-up-fill"></i> Unggah & Proses Impor Mapel
            </button>
        </form>
    </div>
</div>

<script>
function switchImportTab(type) {
    const cardSiswa = document.getElementById('cardImportSiswa');
    const cardGuru = document.getElementById('cardImportGuru');
    const cardMapel = document.getElementById('cardImportMapel');
    const btnSiswa = document.getElementById('tabBtnSiswa');
    const btnGuru = document.getElementById('tabBtnGuru');
    const btnMapel = document.getElementById('tabBtnMapel');

    [cardSiswa, cardGuru, cardMapel].forEach(c => { if(c) c.style.display = 'none'; });
    [btnSiswa, btnGuru, btnMapel].forEach(b => { if(b) b.className = 'btn btn-secondary'; });

    if (type === 'guru') {
        if (cardGuru) cardGuru.style.display = 'block';
        if (btnGuru) btnGuru.className = 'btn btn-primary';
    } else if (type === 'mapel') {
        if (cardMapel) cardMapel.style.display = 'block';
        if (btnMapel) btnMapel.className = 'btn btn-primary';
    } else {
        type = 'siswa';
        if (cardSiswa) cardSiswa.style.display = 'block';
        if (btnSiswa) btnSiswa.className = 'btn btn-primary';
    }
    window.history.replaceState(null, '', '/admin/import-siswa.php?type=' + type);
}
</script>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
