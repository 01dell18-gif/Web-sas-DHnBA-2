<?php
require_once __DIR__ . '/../config/functions.php';
require_admin();

$pageTitle = 'Kelola Data Mata Pelajaran';

// Handle Export Excel (.xls)
if (isset($_GET['action']) && $_GET['action'] === 'export_excel') {
    $searchQ = trim($_GET['q'] ?? '');
    $kelompokFilter = trim($_GET['kelompok'] ?? '');
    $statusFilter = trim($_GET['status'] ?? '');

    $where = "1=1";
    $params = [];
    if ($searchQ !== '') {
        $where .= " AND (nama_mapel LIKE :q OR kode_mapel LIKE :q)";
        $params['q'] = "%{$searchQ}%";
    }
    if ($kelompokFilter !== '') {
        $where .= " AND kelompok = :kel";
        $params['kel'] = $kelompokFilter;
    }
    if ($statusFilter !== '') {
        $where .= " AND aktif = :st";
        $params['st'] = (int)$statusFilter;
    }

    $mapels = dbFetchAll("SELECT * FROM mapel WHERE {$where} ORDER BY kelompok ASC, nama_mapel ASC", $params);

    $headers = ['No', 'Kode Mapel', 'Nama Mata Pelajaran', 'Kelompok Kurikulum', 'Status'];
    $rows = [];
    foreach ($mapels as $idx => $m) {
        $rows[] = [
            $idx + 1,
            $m['kode_mapel'] ?: '-',
            $m['nama_mapel'],
            $m['kelompok'] ?? 'Kejuruan',
            $m['aktif'] == 1 ? 'Aktif' : 'Nonaktif'
        ];
    }
    log_activity('EXPORT_EXCEL', "Mengekspor data mata pelajaran ke format Excel (.xls)");
    export_excel_xls("Data_Mata_Pelajaran_" . date('Ymd_His') . ".xls", $headers, $rows, [1]);
}

// Handle Download Template Excel (.xls)
if (isset($_GET['action']) && $_GET['action'] === 'template_excel') {
    $headers = ['Kode Mapel', 'Nama Mata Pelajaran', 'Kelompok (Umum / Kejuruan / Muatan Lokal)'];
    $sampleRows = [
        ['MP-001', 'Bahasa Indonesia', 'Umum'],
        ['MP-002', 'Bahasa Inggris', 'Umum'],
        ['MP-003', 'Matematika', 'Umum'],
        ['MP-006', 'Muatan Lokal Bahasa Bali', 'Muatan Lokal'],
        ['MP-029', 'Pemrograman Web dan Perangkat Bergerak', 'Kejuruan']
    ];
    export_excel_xls("template_impor_mapel.xls", $headers, $sampleRows, [0]);
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $namaMapel = trim($_POST['nama_mapel'] ?? '');
        $kodeMapel = trim($_POST['kode_mapel'] ?? '');
        $kelompok = trim($_POST['kelompok'] ?? 'Kejuruan');
        $aktif = isset($_POST['aktif']) ? 1 : 0;

        if ($namaMapel === '') {
            set_flash('danger', 'Nama mata pelajaran tidak boleh kosong.');
        } else {
            if ($kodeMapel === '') {
                $maxId = (int)dbValue("SELECT MAX(id) FROM mapel");
                $kodeMapel = sprintf("MP-%03d", $maxId + 1);
            }

            if ($id > 0) {
                dbUpdate('mapel', [
                    'nama_mapel' => $namaMapel,
                    'kode_mapel' => $kodeMapel,
                    'kelompok' => $kelompok,
                    'aktif' => $aktif,
                    'updated_at' => date('Y-m-d H:i:s')
                ], 'id = :id', ['id' => $id]);
                log_activity('UPDATE_MAPEL', "Memperbarui mata pelajaran {$namaMapel} (ID #{$id})");
                set_flash('success', "Mata pelajaran {$namaMapel} berhasil diperbarui.");
            } else {
                $newId = dbInsert('mapel', [
                    'nama_mapel' => $namaMapel,
                    'kode_mapel' => $kodeMapel,
                    'kelompok' => $kelompok,
                    'aktif' => $aktif,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                log_activity('CREATE_MAPEL', "Menambahkan mata pelajaran baru {$namaMapel} (ID #{$newId})");
                set_flash('success', "Mata pelajaran {$namaMapel} berhasil ditambahkan.");
            }
        }
        header('Location: /admin/mapel.php');
        exit;
    }

    if ($action === 'toggle_active') {
        $id = (int)($_POST['id'] ?? 0);
        $status = (int)($_POST['status'] ?? 0);
        dbUpdate('mapel', ['aktif' => $status, 'updated_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $id]);
        log_activity('TOGGLE_MAPEL', "Mengubah status aktif mapel ID #{$id} menjadi {$status}");

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
                  || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
                  || isset($_POST['ajax']);
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'id' => $id,
                'status' => $status,
                'message' => 'Status aktif mata pelajaran berhasil diperbarui.'
            ]);
            exit;
        }

        set_flash('success', 'Status aktif mata pelajaran berhasil diperbarui.');
        header('Location: /admin/mapel.php');
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $mInfo = dbFetch("SELECT nama_mapel FROM mapel WHERE id = :id", ['id' => $id]);
        if ($mInfo) {
            dbDelete('mapel', 'id = :id', ['id' => $id]);
            log_activity('DELETE_MAPEL', "Menghapus mata pelajaran {$mInfo['nama_mapel']}");
            set_flash('success', "Mata pelajaran {$mInfo['nama_mapel']} berhasil dihapus.");
        }
        header('Location: /admin/mapel.php');
        exit;
    }

    if ($action === 'delete_all') {
        $count = (int)dbValue("SELECT COUNT(*) FROM mapel");
        getDb()->exec("DELETE FROM mapel");
        if (DB_TYPE === 'sqlite') {
            getDb()->exec("DELETE FROM sqlite_sequence WHERE name = 'mapel'");
        }
        log_activity('CLEAR_ALL_MAPEL', "Menghapus seluruh data mata pelajaran ({$count} data) untuk reset.");
        set_flash('success', "Seluruh data mata pelajaran ({$count} mapel) berhasil dikosongkan.");
        header('Location: /admin/mapel.php');
        exit;
    }

    if ($action === 'import_excel') {
        if (!empty($_FILES['file_mapel_excel']['tmp_name'])) {
            $file = $_FILES['file_mapel_excel']['tmp_name'];
            $rows = parse_spreadsheet_rows($file);

            if (!empty($rows)) {
                $createdCount = 0;
                $updatedCount = 0;

                foreach ($rows as $cols) {
                    if (count($cols) >= 3 && is_numeric($cols[0]) && !is_numeric($cols[1])) {
                        $kode = trim($cols[1] ?? '');
                        $nama = trim($cols[2] ?? '');
                        $kelompok = trim($cols[3] ?? 'Kejuruan');
                    } elseif (count($cols) >= 2) {
                        $kode = trim($cols[0] ?? '');
                        $nama = trim($cols[1] ?? '');
                        $kelompok = trim($cols[2] ?? 'Kejuruan');
                    } else {
                        $kode = '';
                        $nama = trim($cols[0] ?? '');
                        $kelompok = 'Kejuruan';
                    }

                    if ($nama === '') continue;
                    if ($kelompok === '') $kelompok = 'Kejuruan';

                    $existing = dbFetch("SELECT id FROM mapel WHERE LOWER(nama_mapel) = LOWER(:n)", ['n' => $nama]);
                    if ($existing) {
                        dbUpdate('mapel', [
                            'kode_mapel' => $kode ?: dbValue("SELECT kode_mapel FROM mapel WHERE id = :id", ['id' => $existing['id']]),
                            'kelompok' => $kelompok,
                            'updated_at' => date('Y-m-d H:i:s')
                        ], 'id = :id', ['id' => $existing['id']]);
                        $updatedCount++;
                    } else {
                        if ($kode === '') {
                            $maxId = (int)dbValue("SELECT MAX(id) FROM mapel");
                            $kode = sprintf("MP-%03d", $maxId + 1);
                        }
                        dbInsert('mapel', [
                            'kode_mapel' => $kode,
                            'nama_mapel' => $nama,
                            'kelompok' => $kelompok,
                            'aktif' => 1,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
                        $createdCount++;
                    }
                }

                $total = $createdCount + $updatedCount;
                log_activity('IMPORT_MAPEL', "Mengimpor {$total} data mapel dari Excel ({$createdCount} baru, {$updatedCount} diperbarui)");
                set_flash('success', "Berhasil memproses {$total} mata pelajaran ({$createdCount} baru ditambahkan, {$updatedCount} diperbarui).");
            } else {
                set_flash('danger', 'Berkas Excel tidak memuat data yang valid atau format kolom tidak sesuai.');
            }
        } else {
            set_flash('danger', 'Silakan pilih berkas Excel (.xls) untuk diunggah.');
        }
        header('Location: /admin/mapel.php');
        exit;
    }
}

// Edit mode
$editId = (int)($_GET['edit'] ?? 0);
$editMapel = null;
if ($editId > 0) {
    $editMapel = dbFetch("SELECT * FROM mapel WHERE id = :id", ['id' => $editId]);
}

// Search and filter
$searchQ = trim($_GET['q'] ?? '');
$kelompokFilter = trim($_GET['kelompok'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$where = "1=1";
$params = [];
if ($searchQ !== '') {
    $where .= " AND (nama_mapel LIKE :q OR kode_mapel LIKE :q)";
    $params['q'] = "%{$searchQ}%";
}
if ($kelompokFilter !== '') {
    $where .= " AND kelompok = :kel";
    $params['kel'] = $kelompokFilter;
}
if ($statusFilter !== '') {
    $where .= " AND aktif = :st";
    $params['st'] = (int)$statusFilter;
}

$mapels = dbFetchAll("SELECT * FROM mapel WHERE {$where} ORDER BY kelompok ASC, nama_mapel ASC", $params);

// Stats
$totalMapel = (int)dbValue("SELECT COUNT(*) FROM mapel");
$aktifMapel = (int)dbValue("SELECT COUNT(*) FROM mapel WHERE aktif = 1");
$umumMapel = (int)dbValue("SELECT COUNT(*) FROM mapel WHERE kelompok = 'Umum'");
$kejuruanMapel = (int)dbValue("SELECT COUNT(*) FROM mapel WHERE kelompok = 'Kejuruan'");

require_once __DIR__ . '/layout_header.php';
?>

<div class="page-header-card">
    <div class="page-header-content">
        <div class="page-header-icon-box">
            <i class="bi bi-journal-bookmark-fill"></i>
        </div>
        <div>
            <h1 class="page-header-title">Data Mata Pelajaran Ujian</h1>
            <p class="page-header-subtitle">
                Master data mata pelajaran kurikulum yang digunakan untuk pilihan otomatis pada Daftar Hadir dan Berita Acara.
            </p>
        </div>
    </div>
    <div class="page-header-actions" style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
        <a href="/admin/mapel.php?action=export_excel<?= !empty($searchQ) ? '&q=' . urlencode($searchQ) : '' ?><?= !empty($kelompokFilter) ? '&kelompok=' . urlencode($kelompokFilter) : '' ?><?= $statusFilter !== '' ? '&status=' . urlencode($statusFilter) : '' ?>" class="btn btn-secondary btn-sm" title="Ekspor data mata pelajaran ke format Excel .xls">
            <i class="bi bi-file-earmark-arrow-down"></i> <span>Ekspor Excel (.xls)</span>
        </a>
        <a href="/admin/import-siswa.php?type=mapel" class="btn btn-success btn-sm" title="Impor data mata pelajaran melalui menu Impor Data Excel (.xls)">
            <i class="bi bi-file-earmark-excel"></i> <span>Impor Excel (.xls)</span>
        </a>
        <?php if ($totalMapel > 0): ?>
            <button type="button" class="btn btn-danger-soft btn-sm" onclick="confirmResetAllMapel()" title="Kosongkan seluruh data mata pelajaran">
                <i class="bi bi-trash3-fill"></i> <span>Hapus Semua Data Mapel</span>
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Form Hidden Delete All -->
<form id="formDeleteAllMapel" method="POST" action="/admin/mapel.php" style="display: none;">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="delete_all">
</form>

<!-- STATS CARDS -->
<div class="stats-grid">
    <div class="stat-card-modern">
        <div class="stat-accent-bar" style="background: linear-gradient(90deg, #3b82f6, #60a5fa);"></div>
        <div class="stat-icon stat-icon-blue">
            <i class="bi bi-journal-text"></i>
        </div>
        <div>
            <div class="stat-value"><?= $totalMapel ?></div>
            <div class="stat-label">Total Mata Pelajaran</div>
            <span class="stat-meta-pill" style="background: #eff6ff; color: #1e40af;">
                <i class="bi bi-check2-circle"></i> Terdaftar
            </span>
        </div>
    </div>

    <div class="stat-card-modern">
        <div class="stat-accent-bar" style="background: linear-gradient(90deg, #10b981, #34d399);"></div>
        <div class="stat-icon stat-icon-green">
            <i class="bi bi-check2-all"></i>
        </div>
        <div>
            <div class="stat-value"><?= $aktifMapel ?></div>
            <div class="stat-label">Mapel Aktif Dipakai</div>
            <span class="stat-meta-pill" style="background: #ecfdf5; color: #065f46;">
                <i class="bi bi-eye"></i> Pilihan Form
            </span>
        </div>
    </div>

    <div class="stat-card-modern">
        <div class="stat-accent-bar" style="background: linear-gradient(90deg, #6366f1, #818cf8);"></div>
        <div class="stat-icon stat-icon-indigo">
            <i class="bi bi-book-half"></i>
        </div>
        <div>
            <div class="stat-value"><?= $umumMapel ?></div>
            <div class="stat-label">Muatan Umum</div>
            <span class="stat-meta-pill" style="background: #eef2ff; color: #3730a3;">
                Nasional & Mulok
            </span>
        </div>
    </div>

    <div class="stat-card-modern">
        <div class="stat-accent-bar" style="background: linear-gradient(90deg, #f59e0b, #fbbf24);"></div>
        <div class="stat-icon stat-icon-orange">
            <i class="bi bi-tools"></i>
        </div>
        <div>
            <div class="stat-value"><?= $kejuruanMapel ?></div>
            <div class="stat-label">Kejuruan / Produktif</div>
            <span class="stat-meta-pill" style="background: #fffbeb; color: #92400e;">
                Konsentrasi Keahlian
            </span>
        </div>
    </div>
</div>

<div class="split-layout" style="height: auto; align-items: flex-start; gap: 1rem;">
    <!-- KOLOM KIRI: FORM TAMBAH / EDIT MAPEL -->
    <div style="flex: 0 0 340px; min-width: 300px;">
        <div class="card" style="border-top: 3px solid var(--primary);">
            <div class="card-header">
                <span class="card-title">
                    <i class="bi <?= $editMapel ? 'bi-pencil-square' : 'bi-plus-circle' ?>"></i>
                    <?= $editMapel ? 'Edit Mata Pelajaran' : 'Tambah Mata Pelajaran' ?>
                </span>
            </div>
            <div class="card-body">
                <form method="POST" action="/admin/mapel.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save">
                    <?php if ($editMapel): ?>
                        <input type="hidden" name="id" value="<?= $editMapel['id'] ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label class="form-label" for="kode_mapel">Kode Mata Pelajaran</label>
                        <input type="text" name="kode_mapel" id="kode_mapel" class="form-control" 
                               value="<?= htmlspecialchars($editMapel['kode_mapel'] ?? '') ?>" 
                               placeholder="Contoh: MP-001 (Opsional)">
                        <span class="form-hint">Kosongkan untuk kode otomatis.</span>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="nama_mapel">Nama Mata Pelajaran <span class="required">*</span></label>
                        <input type="text" name="nama_mapel" id="nama_mapel" class="form-control" 
                               value="<?= htmlspecialchars($editMapel['nama_mapel'] ?? '') ?>" 
                               placeholder="Contoh: Pemrograman Web" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="kelompok">Kelompok Kurikulum <span class="required">*</span></label>
                        <select name="kelompok" id="kelompok" class="form-select" required>
                            <option value="Umum" <?= ($editMapel['kelompok'] ?? 'Kejuruan') === 'Umum' ? 'selected' : '' ?>>Umum (Nasional)</option>
                            <option value="Kejuruan" <?= ($editMapel['kelompok'] ?? 'Kejuruan') === 'Kejuruan' ? 'selected' : '' ?>>Kejuruan / Produktif</option>
                            <option value="Muatan Lokal" <?= ($editMapel['kelompok'] ?? 'Kejuruan') === 'Muatan Lokal' ? 'selected' : '' ?>>Muatan Lokal</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="checkbox-label" style="cursor: pointer; display: flex; align-items: center; gap: 0.5rem; margin-top: 0.5rem;">
                            <input type="checkbox" name="aktif" value="1" <?= (!$editMapel || $editMapel['aktif'] == 1) ? 'checked' : '' ?> style="width: 17px; height: 17px;">
                            <span style="font-size: 0.85rem; font-weight: 500;">Aktifkan untuk Pilihan Ujian</span>
                        </label>
                        <span class="form-hint">Mata pelajaran aktif akan tampil di saran form ujian.</span>
                    </div>

                    <div style="display: flex; gap: 0.5rem; margin-top: 1.25rem;">
                        <button type="submit" class="btn btn-primary" style="flex: 1;">
                            <i class="bi bi-check2-circle"></i> <?= $editMapel ? 'Simpan Perubahan' : 'Tambah Mapel' ?>
                        </button>
                        <?php if ($editMapel): ?>
                            <a href="/admin/mapel.php" class="btn btn-secondary">Batal</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- KOLOM KANAN: TABEL DAFTAR MAPEL -->
    <div style="flex: 1; min-width: 0;">
        <div class="card">
            <div class="card-header" style="flex-wrap: wrap; gap: 0.75rem; justify-content: space-between;">
                <span class="card-title">
                    <i class="bi bi-list-columns-reverse"></i> Daftar Mata Pelajaran (<?= count($mapels) ?>)
                </span>

                <form method="GET" action="/admin/mapel.php" style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap; margin: 0;">
                    <div style="position: relative;">
                        <input type="text" name="q" class="form-control form-control-sm" placeholder="Cari nama / kode..." value="<?= htmlspecialchars($searchQ) ?>" style="padding-left: 28px; width: 170px;">
                        <i class="bi bi-search" style="position: absolute; left: 9px; top: 50%; transform: translateY(-50%); font-size: 0.8rem; color: var(--slate-400);"></i>
                    </div>

                    <select name="kelompok" class="form-select form-select-sm" style="width: 140px;" onchange="this.form.submit()">
                        <option value="">Semua Kelompok</option>
                        <option value="Umum" <?= $kelompokFilter === 'Umum' ? 'selected' : '' ?>>Umum</option>
                        <option value="Kejuruan" <?= $kelompokFilter === 'Kejuruan' ? 'selected' : '' ?>>Kejuruan</option>
                        <option value="Muatan Lokal" <?= $kelompokFilter === 'Muatan Lokal' ? 'selected' : '' ?>>Muatan Lokal</option>
                    </select>

                    <select name="status" class="form-select form-select-sm" style="width: 120px;" onchange="this.form.submit()">
                        <option value="">Semua Status</option>
                        <option value="1" <?= $statusFilter === '1' ? 'selected' : '' ?>>Aktif</option>
                        <option value="0" <?= $statusFilter === '0' ? 'selected' : '' ?>>Nonaktif</option>
                    </select>

                    <?php if ($searchQ !== '' || $kelompokFilter !== '' || $statusFilter !== ''): ?>
                        <a href="/admin/mapel.php" class="btn btn-secondary btn-sm" title="Reset Filter">✕</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="card-body" style="padding: 0;">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="width: 45px; text-align: center;">No</th>
                                <th style="width: 100px;">Kode</th>
                                <th>Nama Mata Pelajaran</th>
                                <th style="width: 150px;">Kelompok</th>
                                <th style="text-align: center; width: 140px;"><i class="bi bi-check2-square"></i> Status (Ceklis)</th>
                                <th style="width: 90px; text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($mapels)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 2.5rem; color: var(--slate-400);">
                                        <i class="bi bi-journal-x" style="font-size: 2rem; display: block; margin-bottom: 0.5rem;"></i>
                                        Tidak ada mata pelajaran yang ditemukan.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($mapels as $idx => $m): ?>
                                    <tr>
                                        <td style="text-align: center; font-size: 0.8rem; color: var(--slate-500);">
                                            <?= $idx + 1 ?>
                                        </td>
                                        <td>
                                            <span style="font-family: monospace; font-size: 0.775rem; background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-weight: 600; color: #475569;">
                                                <?= htmlspecialchars($m['kode_mapel'] ?: '-') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div style="font-weight: 600; color: var(--slate-800); font-size: 0.875rem;">
                                                <?= htmlspecialchars($m['nama_mapel']) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php
                                             $badgeBg = '#eff6ff';
                                             $badgeColor = '#1d4ed8';
                                             if ($m['kelompok'] === 'Kejuruan') {
                                                 $badgeBg = '#fef3c7';
                                                 $badgeColor = '#b45309';
                                             } elseif ($m['kelompok'] === 'Muatan Lokal') {
                                                 $badgeBg = '#f3e8ff';
                                                 $badgeColor = '#7e22ce';
                                             }
                                             ?>
                                            <span style="background: <?= $badgeBg ?>; color: <?= $badgeColor ?>; font-size: 0.725rem; font-weight: 700; padding: 3px 8px; border-radius: 999px;">
                                                <?= htmlspecialchars($m['kelompok']) ?>
                                            </span>
                                        </td>
                                        <td style="text-align: center;">
                                            <label class="ceklis-status-pill <?= $m['aktif'] ? 'is-active' : 'is-inactive' ?>" 
                                                   id="ceklis_box_mapel_<?= $m['id'] ?>" 
                                                   title="Klik ceklis untuk mengubah status mata pelajaran ini">
                                                <input type="checkbox" 
                                                       class="ceklis-mapel-input" 
                                                       id="check_mapel_<?= $m['id'] ?>" 
                                                       <?= $m['aktif'] ? 'checked' : '' ?> 
                                                       onchange="quickToggleMapel(<?= $m['id'] ?>, this)">
                                                <span class="ceklis-box">
                                                    <i class="bi bi-check-lg ceklis-check-icon"></i>
                                                </span>
                                                <span class="ceklis-text" id="ceklis_text_mapel_<?= $m['id'] ?>">
                                                    <?= $m['aktif'] ? 'Aktif' : 'Nonaktif' ?>
                                                </span>
                                            </label>
                                        </td>
                                        <td style="text-align: center; white-space: nowrap;">
                                            <div class="action-btn-group">
                                                <a href="/admin/mapel.php?edit=<?= $m['id'] ?>" class="btn-icon-action btn-icon-edit" title="Edit Mata Pelajaran">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <form method="POST" action="/admin/mapel.php" style="display: inline; margin: 0;" 
                                                      onsubmit="return confirm('Hapus mata pelajaran <?= htmlspecialchars($m['nama_mapel'], ENT_QUOTES) ?>?')">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= $m['id'] ?>">
                                                    <button type="submit" class="btn-icon-action btn-icon-delete" title="Hapus Mapel">
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

<!-- MODAL IMPOR MAPEL DARI EXCEL -->
<div id="modalImportMapel" class="custom-modal" style="display: none;">
    <div style="background: #ffffff; width: 90%; max-width: 480px; border-radius: 14px; padding: 1.5rem; margin-top: 25px; margin-bottom: 2rem; box-shadow: 0 20px 40px rgba(0,0,0,0.25);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px solid var(--border); padding-bottom: 0.65rem;">
            <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--slate-900); margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                <i class="bi bi-file-earmark-excel text-success"></i> Impor Mata Pelajaran (.xls)
            </h3>
            <button type="button" class="btn btn-secondary btn-sm" onclick="closeModal('modalImportMapel')">✕</button>
        </div>

        <div style="background: #e0f2fe; border: 1px solid #bae6fd; border-radius: 8px; padding: 0.75rem 0.95rem; margin-bottom: 1rem; font-size: 0.825rem; color: #0369a1; display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 0.45rem;">
                <i class="bi bi-info-circle-fill" style="font-size: 1.1rem; flex-shrink: 0;"></i>
                <span>Tersedia menu terpadu Impor Data Excel khusus Mapel.</span>
            </div>
            <a href="/admin/import-siswa.php?type=mapel" class="btn btn-primary btn-sm" style="white-space: nowrap;">
                <i class="bi bi-box-arrow-up-right"></i> Buka Menu Impor Excel Mapel
            </a>
        </div>

        <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 1.25rem;">
            Unggah berkas spreadsheet <code>.xls</code> / <code>.xlsx</code> untuk menambahkan atau memperbarui data mata pelajaran secara massal.
        </p>

        <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: 8px; padding: 0.85rem 1rem; margin-bottom: 1.25rem; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <strong style="font-size: 0.85rem; color: var(--slate-800); display: block;">Format Template Excel</strong>
                <span style="font-size: 0.75rem; color: var(--slate-500);">Kolom: Kode Mapel, Nama Mapel, Kelompok</span>
            </div>
            <a href="/admin/import-siswa.php?template=mapel" class="btn btn-outline-primary btn-sm" style="white-space: nowrap;">
                <i class="bi bi-download"></i> Unduh Template Mapel (.xls)
            </a>
        </div>

        <form method="POST" action="/admin/mapel.php" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="import_excel">
            <div class="form-group" style="margin-bottom: 1.25rem;">
                <label class="form-label">Pilih Berkas Excel (.xls / .xlsx / .csv)</label>
                <input type="file" name="file_mapel_excel" class="form-control" accept=".xls,.xlsx,.csv" required>
            </div>
            <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalImportMapel')">Batal</button>
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-upload"></i> Unggah & Impor Mapel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function confirmResetAllMapel() {
    showConfirmResetModal(
        'formDeleteAllMapel',
        'Hapus Seluruh Data Mata Pelajaran',
        'PERINGATAN: Tindakan ini akan MENGHAPUS SELURUH data mata pelajaran dari sistem untuk memulai dari awal.'
    );
}

function quickToggleMapel(id, checkbox) {
    if (typeof id === 'object' && id !== null && id.nodeType) {
        const tmp = checkbox;
        checkbox = id;
        id = tmp;
    }
    const isChecked = checkbox.checked ? 1 : 0;
    const box = document.getElementById('ceklis_box_mapel_' + id);
    const text = document.getElementById('ceklis_text_mapel_' + id);

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
    const fd = new FormData();
    fd.append('action', 'toggle_active');
    fd.append('id', id);
    fd.append('status', isChecked);
    fd.append('csrf_token', csrfToken);
    fd.append('ajax', '1');

    fetch('/admin/mapel.php', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: fd
    })
    .then(res => res.json())
    .then(data => {
        if (data && data.success) {
            try {
                if (typeof showQuickStatusToast === 'function') {
                    showQuickStatusToast('Status mapel berhasil ' + (isChecked ? 'diaktifkan' : 'dinonaktifkan'));
                }
            } catch (e) {}
        } else {
            revertToggle();
        }
    })
    .catch(err => {
        console.error('Toggle mapel error:', err);
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
                showQuickStatusToast('Gagal mengubah status mapel');
            }
        } catch (e) {}
    }
}
window.quickToggleMapel = quickToggleMapel;
window.toggleMapelStatus = quickToggleMapel;
</script>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
