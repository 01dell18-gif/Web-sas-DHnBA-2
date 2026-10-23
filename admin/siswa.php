<?php
require_once __DIR__ . '/../config/functions.php';
require_admin();

$pageTitle = 'Kelola Data Siswa';

// Handle Export Excel (.xls)
if (isset($_GET['action']) && $_GET['action'] === 'export_excel') {
    $selectedClassId = (int)($_GET['class_id'] ?? 0);
    $whereSql = "1=1";
    $params = [];
    $titleSuffix = "Semua_Kelas";
    if ($selectedClassId > 0) {
        $whereSql .= " AND s.class_id = :cid";
        $params['cid'] = $selectedClassId;
        $cName = dbValue("SELECT nama_kelas FROM kelas WHERE id = :cid", ['cid' => $selectedClassId]);
        if ($cName) {
            $titleSuffix = preg_replace('/[^a-zA-Z0-9_-]/', '_', $cName);
        }
    }

    $students = dbFetchAll("
        SELECT s.*, k.nama_kelas 
        FROM siswa s 
        LEFT JOIN kelas k ON s.class_id = k.id 
        WHERE {$whereSql} 
        ORDER BY k.nama_kelas ASC, s.nama ASC
    ", $params);

    $headers = ['No', 'Kelas', 'NIS', 'NISN', 'Nama Siswa', 'Status'];
    $rows = [];
    foreach ($students as $idx => $st) {
        $rows[] = [
            $idx + 1,
            $st['nama_kelas'] ?? '-',
            $st['nis'] ?: '-',
            $st['nisn'] ?: '-',
            $st['nama'],
            $st['aktif'] == 1 ? 'Aktif' : 'Nonaktif'
        ];
    }
    log_activity('EXPORT_EXCEL', "Mengekspor data siswa ke format Excel (.xls) [{$titleSuffix}]");
    export_excel_xls("Data_Siswa_{$titleSuffix}_" . date('Ymd_His') . ".xls", $headers, $rows, [2, 3]);
}

// Handle Download Template Excel (.xls)
if (isset($_GET['action']) && $_GET['action'] === 'template_excel') {
    $headers = ['NIS', 'NISN', 'Nama Siswa', 'Kelas'];
    $sampleRows = [
        ['13741', '0061234567', 'I Putu Gede Pratama', 'XII RPL 1'],
        ['13742', '0061234568', 'Ni Kadek Ayu Lestari', 'XII TKJ'],
        ['13743', '-', 'I Made Arya Wijaya', 'XII DKV']
    ];
    export_excel_xls("template_impor_siswa.xls", $headers, $sampleRows, [0, 1]);
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    // Handle Import Excel
    if ($action === 'import_excel') {
        if (!empty($_FILES['file_siswa_excel']['tmp_name'])) {
            $file = $_FILES['file_siswa_excel']['tmp_name'];
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

                    // Deteksi baris header jika belum ditentukan
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
                            continue; // Lewati baris header
                        }
                    }

                    // Ambil nilai kolom berdasarkan headerMap atau fallback posisi
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
                        // Heuristik posisi: jika col 0 adalah nomor urut
                        if (count($cols) >= 5 && is_numeric($cols[0]) && !is_numeric($cols[1])) {
                            // Format export: No, Kelas, NIS, NISN, Nama
                            $kelasName = $cols[1] ?? '';
                            $nis = $cols[2] ?? '';
                            $nisn = $cols[3] ?? '';
                            $nama = $cols[4] ?? '';
                        } elseif (count($cols) >= 4) {
                            // Format template: NIS, NISN, Nama, Kelas
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

                    // Pastikan kelas terdaftar
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

                    // Cek apakah siswa sudah ada di kelas ini
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
                            'class_id' => $cid,
                            'nama_kelas' => $kelasName,
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
                            'nama_kelas' => $kelasName,
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
                log_activity('IMPORT_SISWA', "Mengimpor {$importedCount} data siswa baru dan memperbarui {$updatedCount} data dari berkas Excel.");
                set_flash('success', "Berhasil memproses {$total} data siswa ({$importedCount} siswa baru ditambahkan, {$updatedCount} diperbarui).");
                header('Location: /admin/siswa.php');
                exit;
            } else {
                set_flash('danger', 'Berkas kosong atau tidak ada data yang dapat dibaca. Pastikan format kolom sesuai template.');
            }
        } else {
            set_flash('danger', 'Silakan pilih berkas Excel (.xls/.xlsx/.csv) terlebih dahulu.');
        }
        header('Location: /admin/siswa.php');
        exit;
    }

    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $classId = (int)($_POST['class_id'] ?? 0);
        $namaKelasBaru = trim($_POST['kelas_baru'] ?? '');
        $nama = trim($_POST['nama'] ?? '');
        $nis = trim($_POST['nis'] ?? '');
        $nisn = trim($_POST['nisn'] ?? '');
        $aktif = isset($_POST['aktif']) ? 1 : 0;

        if ($classId <= 0 && $namaKelasBaru !== '') {
            $existK = dbFetch("SELECT id, nama_kelas FROM kelas WHERE UPPER(TRIM(nama_kelas)) = :n", ['n' => strtoupper($namaKelasBaru)]);
            if ($existK) {
                $classId = (int)$existK['id'];
                $className = $existK['nama_kelas'];
            } else {
                $tingkat = 'XII';
                if (stripos($namaKelasBaru, 'X ') === 0 || strtoupper(substr($namaKelasBaru, 0, 2)) === 'X ') $tingkat = 'X';
                elseif (stripos($namaKelasBaru, 'XI ') === 0 || strtoupper(substr($namaKelasBaru, 0, 3)) === 'XI ') $tingkat = 'XI';
                $classId = dbInsert('kelas', [
                    'nama_kelas' => $namaKelasBaru,
                    'tingkat' => $tingkat,
                    'program_keahlian' => '',
                    'aktif' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                $className = $namaKelasBaru;
            }
        } else {
            $className = (string)dbValue("SELECT nama_kelas FROM kelas WHERE id = :id", ['id' => $classId]);
        }

        if ($classId <= 0 || $nama === '') {
            set_flash('danger', 'Nama siswa dan kelas wajib diisi.');
        } else {
            if ($id > 0) {
                dbUpdate('siswa', [
                    'class_id' => $classId,
                    'nama_kelas' => $className,
                    'nama' => $nama,
                    'nis' => $nis,
                    'nisn' => $nisn,
                    'aktif' => $aktif,
                    'updated_at' => date('Y-m-d H:i:s')
                ], 'id = :id', ['id' => $id]);
                log_activity('UPDATE_SISWA', "Memperbarui data siswa {$nama} (ID #{$id})");
                set_flash('success', "Data siswa {$nama} berhasil diperbarui.");
            } else {
                $newId = dbInsert('siswa', [
                    'class_id' => $classId,
                    'nama_kelas' => $className,
                    'nama' => $nama,
                    'nis' => $nis,
                    'nisn' => $nisn,
                    'aktif' => $aktif,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                log_activity('CREATE_SISWA', "Menambahkan siswa baru {$nama} (ID #{$newId})");
                set_flash('success', "Siswa {$nama} berhasil ditambahkan.");
            }
        }
        $redirectUrl = '/admin/siswa.php' . ($classId > 0 ? "?class_id={$classId}" : '');
        header("Location: {$redirectUrl}");
        exit;
    }

    if ($action === 'toggle_active') {
        $id = (int)($_POST['id'] ?? 0);
        $status = (int)($_POST['status'] ?? 0);
        $retClass = (int)($_POST['return_class_id'] ?? 0);
        dbUpdate('siswa', ['aktif' => $status, 'updated_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $id]);
        
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || isset($_POST['ajax'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'id' => $id, 'status' => $status]);
            exit;
        }

        set_flash('success', 'Status siswa berhasil diperbarui.');
        header('Location: /admin/siswa.php' . ($retClass > 0 ? "?class_id={$retClass}" : ''));
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $retClass = (int)($_POST['return_class_id'] ?? 0);
        $sInfo = dbFetch("SELECT nama FROM siswa WHERE id = :id", ['id' => $id]);
        if ($sInfo) {
            dbDelete('siswa', 'id = :id', ['id' => $id]);
            log_activity('DELETE_SISWA', "Menghapus siswa {$sInfo['nama']} (ID #{$id})");
            set_flash('success', "Siswa {$sInfo['nama']} berhasil dihapus.");
        }
        header('Location: /admin/siswa.php' . ($retClass > 0 ? "?class_id={$retClass}" : ''));
        exit;
    }

    if ($action === 'delete_all') {
        $filterClass = (int)($_POST['filter_class_id'] ?? 0);
        if ($filterClass > 0) {
            $count = (int)dbValue("SELECT COUNT(*) FROM siswa WHERE class_id = :cid", ['cid' => $filterClass]);
            dbDelete('siswa', 'class_id = :cid', ['cid' => $filterClass]);
            log_activity('CLEAR_CLASS_SISWA', "Menghapus seluruh siswa pada kelas ID #{$filterClass} ({$count} data)");
            set_flash('success', "Seluruh data siswa pada kelas terpilih ({$count} siswa) berhasil dihapus.");
            header('Location: /admin/siswa.php?class_id=' . $filterClass);
            exit;
        } else {
            $count = (int)dbValue("SELECT COUNT(*) FROM siswa");
            getDb()->exec("DELETE FROM siswa");
            if (DB_TYPE === 'sqlite') {
                getDb()->exec("DELETE FROM sqlite_sequence WHERE name = 'siswa'");
            }
            log_activity('CLEAR_ALL_SISWA', "Menghapus seluruh data siswa ({$count} data) untuk reset.");
            set_flash('success', "Seluruh data siswa ({$count} siswa) berhasil dikosongkan.");
            header('Location: /admin/siswa.php');
            exit;
        }
    }
}

// Filter and edit mode
$selectedClassId = (int)($_GET['class_id'] ?? 0);
$editId = (int)($_GET['edit'] ?? 0);
$editStudent = null;
if ($editId > 0) {
    $editStudent = dbFetch("SELECT * FROM siswa WHERE id = :id", ['id' => $editId]);
    if ($editStudent && $selectedClassId === 0) {
        $selectedClassId = (int)$editStudent['class_id'];
    }
}

$classes = dbFetchAll("SELECT * FROM kelas ORDER BY tingkat DESC, nama_kelas ASC");

$whereSql = "1=1";
$params = [];
if ($selectedClassId > 0) {
    $whereSql .= " AND s.class_id = :cid";
    $params['cid'] = $selectedClassId;
}

$students = dbFetchAll("
    SELECT s.*, k.nama_kelas 
    FROM siswa s 
    LEFT JOIN kelas k ON s.class_id = k.id 
    WHERE {$whereSql} 
    ORDER BY k.nama_kelas ASC, s.nama ASC
", $params);

$totalSiswaGlobal = (int)dbValue("SELECT COUNT(*) FROM siswa");

require_once __DIR__ . '/layout_header.php';
?>

<div class="page-header-card">
    <div class="page-header-content">
        <div class="page-header-icon-box">
            <i class="bi bi-people-fill"></i>
        </div>
        <div>
            <h1 class="page-header-title">Kelola Data Siswa Peserta Ujian</h1>
            <p class="page-header-subtitle">
                Daftar siswa peserta ujian yang dikelompokkan per kelas untuk absensi otomatis.
            </p>
        </div>
    </div>
    <div class="page-header-actions" style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
        <a href="/admin/siswa.php?action=export_excel<?= $selectedClassId > 0 ? '&class_id=' . $selectedClassId : '' ?>" class="btn btn-secondary btn-sm" title="Ekspor data siswa ke format Excel .xls">
            <i class="bi bi-file-earmark-arrow-down"></i> <span>Ekspor Excel (.xls)</span>
        </a>
        <a href="/admin/import-siswa.php?type=siswa" class="btn btn-success btn-sm" title="Impor data siswa melalui menu Impor Data Excel (.xls)">
            <i class="bi bi-file-earmark-excel"></i> <span>Impor Excel (.xls)</span>
        </a>
        <?php if ($totalSiswaGlobal > 0): ?>
            <button type="button" class="btn btn-danger-soft btn-sm" onclick="confirmResetAllSiswa(<?= $selectedClassId ?>)" title="Kosongkan data siswa untuk persiapan ujian baru">
                <i class="bi bi-trash3-fill"></i> 
                <span><?= $selectedClassId > 0 ? 'Hapus Siswa Kelas Ini' : 'Hapus Semua Data Siswa' ?></span>
            </button>
        <?php endif; ?>
    </div>
</div>

<form id="formDeleteAllSiswa" method="POST" action="/admin/siswa.php" style="display: none;">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="delete_all">
    <input type="hidden" name="filter_class_id" value="<?= $selectedClassId ?>">
</form>

<div class="split-layout" style="height: auto;">
    <!-- Form Tambah / Edit Siswa -->
    <div style="flex: 0 0 360px;">
        <div class="card">
            <div class="card-header">
                <span class="card-title"><?= $editStudent ? '✏️ Edit Data Siswa' : '➕ Tambah Siswa Baru' ?></span>
            </div>
            <div class="card-body">
                <form method="POST" action="/admin/siswa.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save">
                    <?php if ($editStudent): ?>
                        <input type="hidden" name="id" value="<?= $editStudent['id'] ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem;">
                            <label class="form-label" for="class_id" style="margin-bottom: 0;">Kelas <span class="required">*</span></label>
                            <button type="button" class="btn btn-link btn-sm" id="btnToggleNewClass" onclick="toggleNewClassInput()" style="padding: 0; font-size: 0.78rem; text-decoration: none;">
                                <i class="bi bi-plus-circle"></i> + Ketik Kelas Baru
                            </button>
                        </div>
                        <select name="class_id" id="class_id" class="form-select" <?= empty($classes) ? '' : 'required' ?>>
                            <option value="">-- Pilih Kelas --</option>
                            <?php foreach ($classes as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= ($editStudent ? $editStudent['class_id'] : $selectedClassId) == $c['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['nama_kelas']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="wrapperNewClass" style="<?= empty($classes) ? 'display: block; margin-top: 0.5rem;' : 'display: none; margin-top: 0.5rem;' ?>">
                            <input type="text" name="kelas_baru" id="kelas_baru" class="form-control" placeholder="Ketik nama kelas baru (contoh: XII RPL 1)" <?= empty($classes) ? 'required' : '' ?>>
                            <small class="text-muted" style="font-size: 0.76rem; display: block; margin-top: 3px;">
                                <i class="bi bi-info-circle"></i> Kelas baru akan otomatis tersimpan ke data kelas.
                            </small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="nama">Nama Lengkap Siswa <span class="required">*</span></label>
                        <input type="text" name="nama" id="nama" class="form-control" 
                               value="<?= htmlspecialchars($editStudent['nama'] ?? '') ?>" placeholder="Nama lengkap siswa" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="nis">NIS (Nomor Induk Siswa)</label>
                        <input type="text" name="nis" id="nis" class="form-control" 
                               value="<?= htmlspecialchars($editStudent['nis'] ?? '') ?>" placeholder="Opsional">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="nisn">NISN</label>
                        <input type="text" name="nisn" id="nisn" class="form-control" 
                               value="<?= htmlspecialchars($editStudent['nisn'] ?? '') ?>" placeholder="Opsional">
                    </div>

                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.9rem;">
                            <input type="checkbox" name="aktif" value="1" <?= ($editStudent['aktif'] ?? 1) == 1 ? 'checked' : '' ?>>
                            Siswa Aktif (Ikut Ujian)
                        </label>
                    </div>

                    <div style="display: flex; gap: 0.5rem; margin-top: 1.5rem;">
                        <button type="submit" class="btn btn-primary" style="flex: 1;">
                            <?= $editStudent ? 'Simpan Perubahan' : 'Tambah Siswa' ?>
                        </button>
                        <?php if ($editStudent): ?>
                            <a href="/admin/siswa.php?class_id=<?= $selectedClassId ?>" class="btn btn-secondary">Batal</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Filter & Daftar Siswa -->
    <div style="flex: 1;">
        <!-- Bar Filter -->
        <div class="card" style="margin-bottom: 1rem;">
            <div class="card-body" style="padding: 1rem;">
                <form method="GET" action="/admin/siswa.php" style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 180px;">
                        <select name="class_id" class="form-select" onchange="this.form.submit()">
                            <option value="0">-- Semua Kelas --</option>
                            <?php foreach ($classes as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= $selectedClassId == $c['id'] ? 'selected' : '' ?>>
                                    Kelas <?= htmlspecialchars($c['nama_kelas']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if ($selectedClassId > 0): ?>
                        <a href="/admin/siswa.php" class="btn btn-secondary btn-sm" style="display: flex; align-items: center;">
                            Reset Filter
                        </a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Tabel Siswa -->
        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <span class="card-title">Daftar Siswa (<?= count($students) ?>)</span>
            </div>
            <div class="card-body" style="padding: 0;">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="width: 40px; text-align: center;">No</th>
                                <th style="width: 90px;">NIS</th>
                                <th>Nama Lengkap</th>
                                <th>Kelas</th>
                                <th style="text-align: center; width: 140px;"><i class="bi bi-check2-square"></i> Status (Ceklis)</th>
                                <th style="text-align: center; width: 105px;">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($students)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 1.5rem;">
                                        Tidak ada data siswa ditemukan.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($students as $idx => $s): ?>
                                    <tr>
                                        <td style="text-align: center; color: var(--text-muted);"><?= $idx + 1 ?></td>
                                        <td><?= htmlspecialchars($s['nis'] ?: '-') ?></td>
                                        <td><strong><?= htmlspecialchars($s['nama']) ?></strong></td>
                                        <td><span class="badge badge-info"><?= htmlspecialchars($s['nama_kelas'] ?? '-') ?></span></td>
                                        <td style="text-align: center;">
                                            <label class="ceklis-status-pill <?= $s['aktif'] ? 'is-active' : 'is-inactive' ?>" 
                                                   id="ceklis_box_siswa_<?= $s['id'] ?>" 
                                                   title="Klik ceklis untuk mengubah status aktif/nonaktif siswa">
                                                <input type="checkbox" 
                                                       class="ceklis-siswa-input" 
                                                       id="check_siswa_<?= $s['id'] ?>" 
                                                       <?= $s['aktif'] ? 'checked' : '' ?> 
                                                       onchange="quickToggleSiswa(<?= $s['id'] ?>, this)">
                                                <span class="ceklis-box">
                                                    <i class="bi bi-check-lg ceklis-check-icon"></i>
                                                </span>
                                                <span class="ceklis-text" id="ceklis_text_siswa_<?= $s['id'] ?>">
                                                    <?= $s['aktif'] ? 'Aktif' : 'Nonaktif' ?>
                                                </span>
                                            </label>
                                        </td>
                                        <td style="text-align: center; white-space: nowrap;">
                                            <div class="action-btn-group">
                                                <a href="/admin/siswa.php?edit=<?= $s['id'] ?>&class_id=<?= $selectedClassId ?>" class="btn-icon-action btn-icon-edit" title="Edit Data Siswa">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <form method="POST" action="/admin/siswa.php" style="display: inline; margin: 0;" 
                                                      onsubmit="return confirm('Hapus siswa <?= htmlspecialchars($s['nama'], ENT_QUOTES) ?>?')">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                                    <input type="hidden" name="return_class_id" value="<?= $selectedClassId ?>">
                                                    <button type="submit" class="btn-icon-action btn-icon-delete" title="Hapus Siswa">
                                                        <i class="bi bi-trash3"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL IMPOR SISWA DARI EXCEL -->
<div id="modalImportSiswa" class="custom-modal" style="display: none;">
    <div class="custom-modal-backdrop" onclick="closeImportSiswaModal()"></div>
    <div class="notification-popup-card" style="max-width: 580px; position: relative; z-index: 10;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px solid var(--border); padding-bottom: 0.75rem;">
            <div style="display: flex; align-items: center; gap: 0.65rem;">
                <div style="width: 36px; height: 36px; border-radius: 8px; background: #ecfdf5; color: #059669; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                    <i class="bi bi-file-earmark-excel"></i>
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 1.05rem; font-weight: 700; color: var(--slate-800);">Impor Data Siswa (.xls)</h3>
                    <p style="margin: 0; font-size: 0.78rem; color: var(--slate-500);">Unggah berkas Excel untuk menambahkan peserta ujian</p>
                </div>
            </div>
            <button type="button" class="btn btn-secondary btn-sm" onclick="closeImportSiswaModal()" style="border: none; background: transparent; font-size: 1.25rem; cursor: pointer; color: var(--slate-400);">✕</button>
        </div>

        <div style="background: #e0f2fe; border: 1px solid #bae6fd; border-radius: 8px; padding: 0.75rem 0.95rem; margin-bottom: 1rem; font-size: 0.825rem; color: #0369a1; display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 0.45rem;">
                <i class="bi bi-info-circle-fill" style="font-size: 1.1rem; flex-shrink: 0;"></i>
                <span>Tersedia menu terpadu Impor Data Excel khusus Siswa.</span>
            </div>
            <a href="/admin/import-siswa.php?type=siswa" class="btn btn-primary btn-sm" style="white-space: nowrap;">
                <i class="bi bi-box-arrow-up-right"></i> Buka Menu Impor Excel Siswa
            </a>
        </div>

        <div style="margin-bottom: 1.25rem; display: flex; justify-content: space-between; align-items: center; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 0.75rem 1rem;">
            <div>
                <strong style="font-size: 0.85rem; color: #1e293b; display: block;">Format Template Siswa</strong>
                <span style="font-size: 0.75rem; color: #64748b;">Gunakan template resmi agar pencocokan kolom presisi.</span>
            </div>
            <a href="/admin/siswa.php?action=template_excel" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.78rem;">
                <i class="bi bi-download"></i> Unduh Template
            </a>
        </div>

        <form method="POST" action="/admin/siswa.php" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="import_excel">

            <div class="form-group" style="margin-bottom: 1rem;">
                <label class="form-label" for="file_siswa_excel" style="font-weight: 600;">Pilih Berkas Spreadsheet (.xls / .xlsx / .csv) <span class="required">*</span></label>
                <input type="file" name="file_siswa_excel" id="file_siswa_excel" class="form-control" accept=".xls,.xlsx,.csv" required style="padding: 0.5rem;">
                <div class="form-hint" style="font-size: 0.75rem; color: #64748b; margin-top: 0.35rem;">
                    Didukung: Excel 97-2003 (.xls), Excel (.xlsx), atau CSV (Maksimal 5 MB).
                </div>
            </div>

            <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 0.75rem 0.95rem; margin-bottom: 1.25rem; font-size: 0.8rem; color: #1e40af; line-height: 1.5;">
                <strong>Panduan Kolom:</strong> Kolom yang didukung: <em>NIS</em>, <em>NISN</em>, <em>Nama Siswa</em>, <em>Kelas</em>. Kelas baru akan dibuat secara otomatis bila belum ada di sistem.
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.65rem;">
                <button type="button" class="btn btn-secondary" onclick="closeImportSiswaModal()">Batal</button>
                <button type="submit" class="btn btn-success" style="display: inline-flex; align-items: center; gap: 0.4rem;">
                    <i class="bi bi-cloud-arrow-up-fill"></i> Unggah & Proses Impor
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openImportSiswaModal() {
    const modal = document.getElementById('modalImportSiswa');
    if (modal) modal.style.display = 'flex';
}

function closeImportSiswaModal() {
    const modal = document.getElementById('modalImportSiswa');
    if (modal) modal.style.display = 'none';
}

function quickToggleSiswa(id, checkbox) {
    if (typeof id === 'object' && id !== null && id.nodeType) {
        const tmp = checkbox;
        checkbox = id;
        id = tmp;
    }
    const isChecked = checkbox.checked ? 1 : 0;
    const box = document.getElementById('ceklis_box_siswa_' + id);
    const text = document.getElementById('ceklis_text_siswa_' + id);

    // Optimistic UI update
    if (isChecked) {
        if (box) {
            box.classList.remove('is-inactive');
            box.classList.add('is-active');
        }
        if (text) text.textContent = 'Aktif';
    } else {
        if (box) {
            box.classList.remove('is-active');
            box.classList.add('is-inactive');
        }
        if (text) text.textContent = 'Nonaktif';
    }

    const csrfToken = '<?= csrf_token() ?>';
    const formData = new FormData();
    formData.append('action', 'toggle_active');
    formData.append('id', id);
    formData.append('status', isChecked);
    formData.append('csrf_token', csrfToken);
    formData.append('ajax', '1');

    fetch('/admin/siswa.php', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data && data.success) {
            try {
                if (typeof showQuickStatusToast === 'function') {
                    showQuickStatusToast('Status siswa berhasil ' + (isChecked ? 'diaktifkan' : 'dinonaktifkan'));
                }
            } catch (e) {}
        } else {
            revertToggle();
        }
    })
    .catch(err => {
        console.error('Toggle error:', err);
        revertToggle();
    });

    function revertToggle() {
        checkbox.checked = !isChecked;
        if (checkbox.checked) {
            if (box) { box.classList.remove('is-inactive'); box.classList.add('is-active'); }
            if (text) text.textContent = 'Aktif';
        } else {
            if (box) { box.classList.remove('is-active'); box.classList.add('is-inactive'); }
            if (text) text.textContent = 'Nonaktif';
        }
        try {
            if (typeof showQuickStatusToast === 'function') {
                showQuickStatusToast('Gagal memperbarui status');
            }
        } catch (e) {}
    }
}
window.quickToggleSiswa = quickToggleSiswa;

function confirmResetAllSiswa(classId) {
    const targetText = classId > 0 ? "seluruh siswa pada KELAS INI" : "SELURUH data siswa di SEMUA KELAS";
    showConfirmResetModal(
        'formDeleteAllSiswa',
        'Hapus Semua Data Siswa',
        'PERINGATAN: Tindakan ini akan MENGHAPUS ' + targetText + ' dari sistem secara permanen untuk memulai dari awal.'
    );
}

function toggleNewClassInput() {
    var w = document.getElementById('wrapperNewClass');
    var sel = document.getElementById('class_id');
    var inp = document.getElementById('kelas_baru');
    if (!w) return;
    if (w.style.display === 'none') {
        w.style.display = 'block';
        if (sel) sel.removeAttribute('required');
        if (inp) {
            inp.setAttribute('required', 'required');
            inp.focus();
        }
    } else {
        w.style.display = 'none';
        if (inp) {
            inp.value = '';
            inp.removeAttribute('required');
        }
        if (sel && sel.options.length > 1) sel.setAttribute('required', 'required');
    }
}
window.toggleNewClassInput = toggleNewClassInput;
</script>

<?php require_once __DIR__ . '/layout_footer.php'; ?>

