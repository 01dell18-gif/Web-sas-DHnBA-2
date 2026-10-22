<?php
require_once __DIR__ . '/../config/functions.php';
require_admin();

// Download CSV template
if (isset($_GET['template'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="template_impor_siswa.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['NIS', 'NISN', 'Nama Siswa', 'Kelas']);
    fputcsv($out, ['13741', '0061234567', 'I Putu Gede Pratama', 'XII TKJ']);
    fputcsv($out, ['13742', '0061234568', 'Ni Kadek Ayu Lestari', 'XII TKJ']);
    fputcsv($out, ['13743', '', 'I Made Arya Wijaya', 'XII RPL 1']);
    fclose($out);
    exit;
}

$pageTitle = 'Impor Data Siswa';
$previewRows = [];
$importedCount = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $step = $_POST['step'] ?? 'upload';

    if (!empty($_FILES['file_siswa']['tmp_name'])) {
        $file = $_FILES['file_siswa']['tmp_name'];
        $origName = $_FILES['file_siswa']['name'];
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

        $rows = [];
        if ($ext === 'csv') {
            if (($handle = fopen($file, 'r')) !== false) {
                $header = fgetcsv($handle, 1000, ',');
                // Clean BOM if any
                if ($header && isset($header[0])) {
                    $header[0] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $header[0]);
                }
                while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                    if (!empty($data[2]) || !empty($data[1]) || !empty($data[0])) {
                        $rows[] = [
                            'nis' => trim($data[0] ?? ''),
                            'nisn' => trim($data[1] ?? ''),
                            'nama' => trim($data[2] ?? ($data[1] ?? '')),
                            'kelas' => trim($data[3] ?? ($data[2] ?? ''))
                        ];
                    }
                }
                fclose($handle);
            }
        } else {
            // For XLSX/XLS, parse basic XML if zip or read line strings
            // If zip, read xl/worksheets/sheet1.xml and xl/sharedStrings.xml
            $zip = new ZipArchive();
            if ($zip->open($file) === true) {
                $sharedStrings = [];
                if (($xmlString = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
                    $xml = simplexml_load_string($xmlString);
                    foreach ($xml->si as $si) {
                        $sharedStrings[] = (string)($si->t ?? $si->r->t ?? '');
                    }
                }
                if (($sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml')) !== false) {
                    $xml = simplexml_load_string($sheetXml);
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
                        if (count($cols) >= 3) {
                            $rows[] = [
                                'nis' => $cols[0] ?? '',
                                'nisn' => $cols[1] ?? '',
                                'nama' => $cols[2] ?? '',
                                'kelas' => $cols[3] ?? ''
                            ];
                        }
                    }
                }
                $zip->close();
            }
        }

        // Process rows into database
        if (!empty($rows)) {
            $classCache = [];
            $allKelas = dbFetchAll("SELECT id, UPPER(nama_kelas) as uname FROM kelas");
            foreach ($allKelas as $ak) {
                $classCache[$ak['uname']] = $ak['id'];
            }

            foreach ($rows as $r) {
                $nama = $r['nama'];
                $kelasName = $r['kelas'];
                if (!$nama || !$kelasName) continue;

                $uname = strtoupper($kelasName);
                if (!isset($classCache[$uname])) {
                    // Create new class automatically
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

                // Insert student
                dbInsert('siswa', [
                    'class_id' => $cid,
                    'nis' => $r['nis'] ?? '',
                    'nisn' => $r['nisn'] ?? '',
                    'nama' => $nama,
                    'aktif' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                $importedCount++;
            }

            log_activity('IMPORT_SISWA', "Mengimpor {$importedCount} data siswa dari berkas {$origName}.");
            set_flash('success', "Berhasil mengimpor {$importedCount} siswa ke dalam sistem.");
            header('Location: /admin/siswa.php');
            exit;
        } else {
            set_flash('danger', 'Tidak ada data valid yang dapat dibaca dari berkas tersebut. Pastikan format kolom sesuai template.');
        }
    } else {
        set_flash('danger', 'Silakan pilih berkas Excel atau CSV terlebih dahulu.');
    }
}

require_once __DIR__ . '/layout_header.php';
?>

<div style="margin-bottom: 0.75rem;">
    <h2>Impor Data Siswa</h2>
    <p style="color: var(--text-muted); font-size: 0.9rem;">
        Unggah data siswa dalam format Excel (.xlsx) atau CSV dengan pencocokan kelas otomatis.
    </p>
</div>

<!-- Form Unggah Berkas -->
<div class="card" style="max-width: 700px;">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <span class="card-title">Unggah Berkas Siswa</span>
        <a href="/admin/import-siswa.php?template=1" class="btn btn-secondary btn-sm">
            📥 Unduh Template CSV
        </a>
    </div>
    <div class="card-body">
        <form method="POST" action="/admin/import-siswa.php" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="step" value="upload">

            <div class="form-group">
                <label class="form-label" for="file_siswa">Pilih File Excel / CSV <span class="required">*</span></label>
                <input type="file" name="file_siswa" id="file_siswa" class="form-control" accept=".xlsx,.xls,.csv" required>
                <div class="form-hint">
                    Format yang didukung: <strong>.xlsx, .xls, .csv</strong> (Maksimal 5 MB).
                </div>
            </div>

            <div style="background: var(--slate-50); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 1rem; margin-bottom: 0.7rem;">
                <div style="font-weight: 600; font-size: 0.85rem; margin-bottom: 0.5rem; color: var(--slate-800);">
                    Format Kolom yang Dibutuhkan:
                </div>
                <ul style="font-size: 0.85rem; color: var(--slate-600); padding-left: 1.25rem;">
                    <li><strong>NIS</strong>: Nomor Induk Siswa (Opsional)</li>
                    <li><strong>NISN</strong>: Nomor Induk Siswa Nasional (Opsional)</li>
                    <li><strong>Nama Siswa</strong>: Nama lengkap siswa (Wajib)</li>
                    <li><strong>Kelas</strong>: Nama kelas, misal: <em>XII RPL 1</em> (Wajib, akan dibuat otomatis jika belum ada)</li>
                </ul>
            </div>

            <button type="submit" class="btn btn-primary btn-block">
                Unggah & Proses Impor Data
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
