<?php
$pageTitle = 'Arsip Berita Acara Ujian';
require_once __DIR__ . '/../config/functions.php';
require_admin();

// Handle Single Download
if (isset($_GET['action']) && $_GET['action'] === 'download_single') {
    $id = (int)($_GET['id'] ?? 0);
    $rec = dbFetch("SELECT * FROM berita_acara WHERE id = :id", ['id' => $id]);
    if ($rec && !empty($rec['pdf_path'])) {
        $fullPath = BASE_PATH . '/' . $rec['pdf_path'];
        if (file_exists($fullPath)) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . basename($fullPath) . '"');
            header('Content-Length: ' . filesize($fullPath));
            readfile($fullPath);
            exit;
        }
    }
    set_flash('danger', 'Berkas PDF tidak ditemukan di server.');
    header('Location: /admin/berita-acara.php');
    exit;
}

// Handle Single View Inline
if (isset($_GET['action']) && $_GET['action'] === 'view_single') {
    $id = (int)($_GET['id'] ?? 0);
    $rec = dbFetch("SELECT * FROM berita_acara WHERE id = :id", ['id' => $id]);
    if ($rec && !empty($rec['pdf_path'])) {
        $fullPath = BASE_PATH . '/' . $rec['pdf_path'];
        if (file_exists($fullPath)) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . basename($fullPath) . '"');
            header('Content-Length: ' . filesize($fullPath));
            readfile($fullPath);
            exit;
        }
    }
    die('Berkas PDF tidak ditemukan di server.');
}

// Handle ZIP Download
if ((isset($_GET['action']) && $_GET['action'] === 'download_zip') || (isset($_POST['action']) && $_POST['action'] === 'download_zip')) {
    $ids = [];
    $zipFilename = 'Arsip_Berita_Acara_' . date('Ymd_His') . '.zip';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['ids'])) {
        $ids = array_map('intval', $_POST['ids']);
    } elseif (isset($_GET['mode'])) {
        $mode = $_GET['mode'];
        if ($mode === 'tingkat') {
            $t = $_GET['tingkat'] ?? 'XII';
            $rows = dbFetchAll("SELECT id FROM berita_acara WHERE tingkat = :t", ['t' => $t]);
            $ids = array_column($rows, 'id');
            $zipFilename = "Arsip_Berita_Acara_Tingkat_{$t}_" . date('Ymd') . ".zip";
        } elseif ($mode === 'kelas') {
            $cid = (int)($_GET['class_id'] ?? 0);
            $rows = dbFetchAll("SELECT id, nama_kelas FROM berita_acara WHERE class_id = :cid", ['cid' => $cid]);
            $ids = array_column($rows, 'id');
            $kName = $rows[0]['nama_kelas'] ?? 'Kelas';
            $zipFilename = "Arsip_Berita_Acara_{$kName}_" . date('Ymd') . ".zip";
        } elseif ($mode === 'tanggal') {
            $tgl = $_GET['tanggal'] ?? date('Y-m-d');
            $rows = dbFetchAll("SELECT id FROM berita_acara WHERE tanggal_ujian = :tgl", ['tgl' => $tgl]);
            $ids = array_column($rows, 'id');
            $zipFilename = "Arsip_Berita_Acara_Tanggal_{$tgl}.zip";
        }
    }

    if (!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $docs = dbFetchAll("SELECT * FROM berita_acara WHERE id IN ({$placeholders})", $ids);

        $tmpZip = tempnam(sys_get_temp_dir(), 'zip');
        $zip = new ZipArchive();
        if ($zip->open($tmpZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            foreach ($docs as $d) {
                if (!empty($d['pdf_path'])) {
                    $p = BASE_PATH . '/' . $d['pdf_path'];
                    if (file_exists($p)) {
                        $zip->addFile($p, basename($p));
                    }
                }
            }
            $zip->close();

            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $zipFilename . '"');
            header('Content-Length: ' . filesize($tmpZip));
            readfile($tmpZip);
            @unlink($tmpZip);
            exit;
        }
    }
    set_flash('danger', 'Tidak ada berkas yang ditemukan untuk diunduh.');
    header('Location: /admin/berita-acara.php');
    exit;
}

// Handle Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    verify_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $rec = dbFetch("SELECT * FROM berita_acara WHERE id = :id", ['id' => $id]);
    if ($rec) {
        if (!empty($rec['pdf_path'])) {
            $f = BASE_PATH . '/' . $rec['pdf_path'];
            if (file_exists($f)) @unlink($f);
        }
        // Delete uploaded photos if any
        if (!empty($rec['foto_dokumentasi'])) {
            $photos = json_decode($rec['foto_dokumentasi'], true) ?: [];
            foreach ($photos as $ph) {
                $pF = BASE_PATH . '/' . $ph;
                if (file_exists($pF)) @unlink($pF);
            }
        }
        dbDelete('berita_acara', 'id = :id', ['id' => $id]);
        log_activity('DELETE_BA', "Menghapus arsip berita acara {$rec['nama_kelas']} - {$rec['mata_pelajaran']} (ID #{$id})");
        set_flash('success', "Arsip berita acara {$rec['nama_kelas']} - {$rec['mata_pelajaran']} berhasil dihapus.");
    }
    header('Location: /admin/berita-acara.php');
    exit;
}

// Handle Delete All (Reset Semua Arsip Berita Acara)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_all') {
    verify_csrf();
    $allDocs = dbFetchAll("SELECT id, pdf_path, foto_dokumentasi FROM berita_acara");
    $count = count($allDocs);
    foreach ($allDocs as $doc) {
        if (!empty($doc['pdf_path'])) {
            $f = BASE_PATH . '/' . $doc['pdf_path'];
            if (file_exists($f)) @unlink($f);
        }
        if (!empty($doc['foto_dokumentasi'])) {
            $photos = json_decode($doc['foto_dokumentasi'], true) ?: [];
            foreach ($photos as $ph) {
                $pF = BASE_PATH . '/' . $ph;
                if (file_exists($pF)) @unlink($pF);
            }
        }
    }
    // Clean storage/berita-acara/ directory
    $dirBa = BASE_PATH . '/storage/berita-acara';
    if (is_dir($dirBa)) {
        foreach (glob($dirBa . '/*.pdf') as $f) {
            @unlink($f);
        }
    }
    // Clean storage/uploads/kegiatan/ directory
    $dirPhotos = BASE_PATH . '/storage/uploads/kegiatan';
    if (is_dir($dirPhotos)) {
        foreach (glob($dirPhotos . '/*') as $f) {
            if (is_file($f)) @unlink($f);
        }
    }
    getDb()->exec("DELETE FROM berita_acara");
    if (DB_TYPE === 'sqlite') {
        getDb()->exec("DELETE FROM sqlite_sequence WHERE name = 'berita_acara'");
    }
    log_activity('CLEAR_ALL_BA', "Menghapus seluruh arsip berita acara ({$count} dokumen), file PDF, dan foto kegiatan untuk reset.");
    set_flash('success', "Seluruh arsip berita acara ({$count} dokumen) dan berkas fisiknya berhasil dihapus dan dikosongkan.");
    header('Location: /admin/berita-acara.php');
    exit;
}

// Filters
$tingkatFilter = trim($_GET['tingkat'] ?? '');
$classFilter = (int)($_GET['class_id'] ?? 0);
$tanggalFilter = trim($_GET['tanggal'] ?? '');
$searchQ = trim($_GET['q'] ?? '');

$whereSql = "1=1";
$params = [];

if ($tingkatFilter !== '') {
    $whereSql .= " AND tingkat = :tingkat";
    $params['tingkat'] = $tingkatFilter;
}
if ($classFilter > 0) {
    $whereSql .= " AND class_id = :class_id";
    $params['class_id'] = $classFilter;
}
if ($tanggalFilter !== '') {
    $whereSql .= " AND tanggal_ujian = :tanggal";
    $params['tanggal'] = $tanggalFilter;
}
if ($searchQ !== '') {
    $whereSql .= " AND (mata_pelajaran LIKE :q OR nama_kelas LIKE :q OR nama_pengawas_1 LIKE :q)";
    $params['q'] = "%{$searchQ}%";
}

$documents = dbFetchAll("SELECT * FROM berita_acara WHERE {$whereSql} ORDER BY tanggal_ujian DESC, id DESC", $params);

// Statistics
$totalDocs = (int)dbValue("SELECT COUNT(*) FROM berita_acara");
$totalX = (int)dbValue("SELECT COUNT(*) FROM berita_acara WHERE tingkat = 'X'");
$totalXI = (int)dbValue("SELECT COUNT(*) FROM berita_acara WHERE tingkat = 'XI'");
$totalXII = (int)dbValue("SELECT COUNT(*) FROM berita_acara WHERE tingkat = 'XII'");
$totalSizeBytes = (int)dbValue("SELECT SUM(file_size) FROM berita_acara");
$totalSizeMB = round($totalSizeBytes / (1024 * 1024), 1);

$classes = dbFetchAll("SELECT * FROM kelas ORDER BY tingkat DESC, nama_kelas ASC");

require_once __DIR__ . '/layout_header.php';
?>

<div class="page-header-card">
    <div class="page-header-content">
        <div class="page-header-icon-box">
            <i class="bi bi-archive-fill"></i>
        </div>
        <div>
            <h1 class="page-header-title">Arsip Berita Acara Ujian</h1>
            <p class="page-header-subtitle">
                Kelola, telusuri, dan unduh berkas PDF Berita Acara Pelaksanaan Ujian lengkap dengan foto dokumentasi dan tanda tangan pengawas.
            </p>
        </div>
    </div>
    <div class="page-header-actions" style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
        <a href="/berita-acara.php" target="_blank" class="btn btn-primary btn-sm" title="Buka Formulir Berita Acara">
            <i class="bi bi-pencil-square"></i> <span>Buat Berita Acara ↗</span>
        </a>
        <?php if ($totalDocs > 0): ?>
            <button type="button" class="btn btn-danger-soft btn-sm" onclick="confirmResetAllBA()" title="Kosongkan seluruh arsip berita acara untuk persiapan ujian baru">
                <i class="bi bi-trash3-fill"></i> <span>Hapus Semua Arsip Berita Acara</span>
            </button>
        <?php endif; ?>
    </div>
</div>

<form id="formDeleteAllBA" method="POST" action="/admin/berita-acara.php" style="display: none;">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="delete_all">
</form>

<!-- KARTU STATISTIK RINGKAS -->
<div class="stats-grid">
    <div class="stat-card-modern">
        <div class="stat-accent-bar" style="background: linear-gradient(90deg, #d97706, #f59e0b);"></div>
        <div class="stat-icon stat-icon-amber">
            <i class="bi bi-clipboard-data-fill"></i>
        </div>
        <div>
            <div class="stat-value"><?= $totalDocs ?></div>
            <div class="stat-label">Total Arsip Berita Acara</div>
            <span class="stat-meta-pill" style="background: #fffbeb; color: #b45309;">
                <i class="bi bi-journal-check"></i> Terverifikasi
            </span>
        </div>
    </div>
    <div class="stat-card-modern">
        <div class="stat-accent-bar" style="background: linear-gradient(90deg, #4f46e5, #6366f1);"></div>
        <div class="stat-icon stat-icon-indigo">
            <i class="bi bi-mortarboard-fill"></i>
        </div>
        <div>
            <div class="stat-value" style="font-size: 1.15rem;">
                X: <strong><?= $totalX ?></strong> | XI: <strong><?= $totalXI ?></strong> | XII: <strong><?= $totalXII ?></strong>
            </div>
            <div class="stat-label">Distribusi Tingkat</div>
            <span class="stat-meta-pill" style="background: #eef2ff; color: #4338ca;">
                3 Tingkatan Kelas
            </span>
        </div>
    </div>
    <div class="stat-card-modern">
        <div class="stat-accent-bar" style="background: linear-gradient(90deg, #64748b, #94a3b8);"></div>
        <div class="stat-icon" style="background: linear-gradient(135deg, #f1f5f9, #e2e8f0); color: #475569;">
            <i class="bi bi-hdd-fill"></i>
        </div>
        <div>
            <div class="stat-value"><?= $totalSizeMB ?> <span style="font-size: 0.825rem; font-weight: 600; color: var(--slate-500);">MB</span></div>
            <div class="stat-label">Total Ukuran PDF</div>
            <span class="stat-meta-pill" style="background: #f1f5f9; color: #475569;">
                Kapasitas Disk
            </span>
        </div>
    </div>
</div>

<!-- BILAH UNDUH CEPAT (ZIP) -->
<div class="card" style="margin-bottom: 0.75rem; border-left: 4px solid #b45309; background: #fffbeb;">
    <div class="card-body" style="padding: 0.65rem 0.85rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
                <strong style="color: #b45309; font-size: 1rem; display: flex; align-items: center; gap: 0.4rem;">
                    📦 Unduh Sekaligus Berupa File ZIP:
                </strong>
                <span style="font-size: 0.85rem; color: #92400e;">
                    Unduh seluruh berkas PDF Berita Acara yang tersimpan dalam paket arsip .ZIP.
                </span>
            </div>
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; position: relative;">
                <!-- Tombol Unduh Tingkat -->
                <div style="position: relative; display: inline-block;">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="toggleDropdown('dropTingkatBA')">
                        🎓 Per Tingkat ▾
                    </button>
                    <div id="dropTingkatBA" class="custom-dropdown-menu" style="display: none; position: absolute; right: 0; top: 100%; background: #fff; border: 1px solid #cbd5e1; border-radius: 6px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); min-width: 180px; z-index: 50; padding: 4px 0; margin-top: 4px;">
                        <a href="/admin/berita-acara.php?action=download_zip&mode=tingkat&tingkat=X" class="dropdown-item" style="display: block; padding: 6px 12px; font-size: 0.85rem; text-decoration: none; color: #333;">📦 Tingkat X (<?= $totalX ?> Dokumen)</a>
                        <a href="/admin/berita-acara.php?action=download_zip&mode=tingkat&tingkat=XI" class="dropdown-item" style="display: block; padding: 6px 12px; font-size: 0.85rem; text-decoration: none; color: #333;">📦 Tingkat XI (<?= $totalXI ?> Dokumen)</a>
                        <a href="/admin/berita-acara.php?action=download_zip&mode=tingkat&tingkat=XII" class="dropdown-item" style="display: block; padding: 6px 12px; font-size: 0.85rem; text-decoration: none; color: #333;">📦 Tingkat XII (<?= $totalXII ?> Dokumen)</a>
                    </div>
                </div>

                <!-- Tombol Unduh Per Kelas -->
                <button type="button" class="btn btn-secondary btn-sm" onclick="openDownloadKelasModalBA()">
                    🏫 Per Kelas ▾
                </button>

                <!-- Tombol Unduh Per Tanggal -->
                <button type="button" class="btn btn-secondary btn-sm" onclick="openDownloadTanggalModalBA()">
                    📅 Per Tanggal ▾
                </button>
            </div>
        </div>
    </div>
</div>

<!-- FORMULIR FILTER & PENCARIAN -->
<div class="card" style="margin-bottom: 0.75rem;">
    <div class="card-header">
        <span class="card-title">🔍 Filter Data Arsip Berita Acara</span>
    </div>
    <div class="card-body">
        <form method="GET" action="/admin/berita-acara.php" class="grid-4" style="gap: 1rem; align-items: flex-end;">
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label" for="filter_tingkat">Tingkat Kelas</label>
                <select name="tingkat" id="filter_tingkat" class="form-select">
                    <option value="">-- Semua Tingkat --</option>
                    <option value="X" <?= $tingkatFilter === 'X' ? 'selected' : '' ?>>Tingkat X</option>
                    <option value="XI" <?= $tingkatFilter === 'XI' ? 'selected' : '' ?>>Tingkat XI</option>
                    <option value="XII" <?= $tingkatFilter === 'XII' ? 'selected' : '' ?>>Tingkat XII</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label" for="filter_class_id">Kelas</label>
                <select name="class_id" id="filter_class_id" class="form-select">
                    <option value="">-- Semua Kelas --</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $classFilter == $c['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['nama_kelas']) ?> (<?= htmlspecialchars($c['tingkat']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label" for="filter_tanggal">Tanggal Ujian</label>
                <input type="date" name="tanggal" id="filter_tanggal" class="form-control" value="<?= htmlspecialchars($tanggalFilter) ?>">
            </div>

            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label" for="q">Cari Mapel / Pengawas</label>
                <input type="text" name="q" id="q" class="form-control" placeholder="Ketik kata kunci..." value="<?= htmlspecialchars($searchQ) ?>">
            </div>

            <div style="grid-column: 1 / -1; display: flex; gap: 0.5rem; justify-content: flex-end;">
                <button type="submit" class="btn btn-primary btn-sm">
                    🔍 Terapkan Filter
                </button>
                <?php if ($tingkatFilter || $classFilter || $tanggalFilter || $searchQ): ?>
                    <a href="/admin/berita-acara.php" class="btn btn-secondary btn-sm">
                        Reset Filter
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- TABEL DATA ARSIP BERITA ACARA -->
<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
        <div>
            <span class="card-title">Daftar Dokumen Berita Acara</span>
            <span style="font-size: 0.8rem; color: var(--text-muted); margin-left: 0.5rem;">
                (Menampilkan <strong><?= count($documents) ?></strong> dokumen)
            </span>
        </div>

        <!-- Tombol Aksi Batch untuk Checkbox -->
        <div id="batchActionToolbar" style="display: none; align-items: center; gap: 0.5rem;">
            <span style="font-size: 0.85rem; color: var(--slate-700);">
                Terpilih: <strong id="selectedCountDisplay">0</strong> dokumen
            </span>
            <button type="button" class="btn btn-primary btn-sm" onclick="submitBatchDownloadZip()">
                📦 Unduh Terpilih (.ZIP)
            </button>
        </div>
    </div>

    <div class="card-body" style="padding: 0;">
        <form id="formBatchZip" method="POST" action="/admin/berita-acara.php">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="download_zip">
            <div class="table-responsive">
                <table class="table" style="margin-bottom: 0;">
                    <thead>
                        <tr>
                            <th style="width: 40px; text-align: center;">
                                <input type="checkbox" id="checkSelectAll" title="Pilih Semua Baris" onchange="toggleSelectAll(this)">
                            </th>
                            <th style="width: 45px; text-align: center;">No</th>
                            <th>Tanggal & Waktu</th>
                            <th>Kelas</th>
                            <th>Mata Pelajaran & Ruang</th>
                            <th style="text-align: center;">Rekap Peserta</th>
                            <th>Pengawas</th>
                            <th style="text-align: center;">Dokumentasi</th>
                            <th style="text-align: right; width: 90px;">Ukuran PDF</th>
                            <th style="text-align: center; width: 170px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($documents)): ?>
                            <tr>
                                <td colspan="10" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                                    Tidak ada dokumen berita acara yang sesuai dengan kriteria filter.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($documents as $idx => $doc): ?>
                                <?php
                                $photos = json_decode($doc['foto_dokumentasi'] ?? '[]', true) ?: [];
                                ?>
                                <tr>
                                    <td style="text-align: center;">
                                        <input type="checkbox" name="ids[]" value="<?= $doc['id'] ?>" class="row-checkbox" onchange="updateBatchToolbar()">
                                    </td>
                                    <td style="text-align: center; color: var(--text-muted); font-size: 0.85rem;">
                                        <?= $idx + 1 ?>
                                    </td>
                                    <td>
                                        <strong style="color: var(--slate-900);"><?= date('d/m/Y', strtotime($doc['tanggal_ujian'])) ?></strong>
                                        <div style="font-size: 0.75rem; color: var(--text-muted);">
                                            <?= htmlspecialchars($doc['jam_mulai']) ?> - <?= htmlspecialchars($doc['jam_selesai']) ?> WITA
                                        </div>
                                    </td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 0.35rem;">
                                            <span class="badge badge-success" style="font-size: 0.7rem; padding: 2px 6px;">
                                                <?= htmlspecialchars($doc['tingkat']) ?>
                                            </span>
                                            <strong><?= htmlspecialchars($doc['nama_kelas']) ?></strong>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-weight: 600; color: var(--slate-800);"><?= htmlspecialchars($doc['mata_pelajaran']) ?></div>
                                        <div style="font-size: 0.75rem; color: var(--text-muted);">
                                            Ruang: <strong><?= htmlspecialchars($doc['ruang']) ?></strong>
                                        </div>
                                    </td>
                                    <td style="text-align: center;">
                                        <span class="badge <?= $doc['peserta_hadir'] == $doc['peserta_seharusnya'] ? 'badge-success' : 'badge-warning' ?>" style="font-size: 0.8rem;">
                                            <?= $doc['peserta_hadir'] ?> / <?= $doc['peserta_seharusnya'] ?>
                                        </span>
                                        <?php if ($doc['peserta_tidak_hadir'] > 0): ?>
                                            <div style="font-size: 0.7rem; color: #dc2626; margin-top: 2px;">
                                                <?= $doc['peserta_tidak_hadir'] ?> Tidak Hadir
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="font-size: 0.85rem; font-weight: 500;">
                                            <?= htmlspecialchars($doc['nama_pengawas_1']) ?>
                                        </div>
                                    </td>
                                    <td style="text-align: center;">
                                        <?php if (!empty($photos)): ?>
                                            <button type="button" class="btn btn-secondary btn-sm" style="padding: 2px 7px; font-size: 0.75rem;" 
                                                    onclick='showPhotoGallery(<?= json_encode($photos) ?>, "<?= htmlspecialchars($doc['nama_kelas'] . ' - ' . $doc['mata_pelajaran'], ENT_QUOTES) ?>")'>
                                                📸 <?= count($photos) ?> Foto
                                            </button>
                                        <?php else: ?>
                                            <span style="font-size: 0.75rem; color: var(--text-muted);">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: right; font-size: 0.8rem; color: var(--slate-600); white-space: nowrap;">
                                        <?= round($doc['file_size'] / 1024) ?> KB
                                    </td>
                                    <td style="text-align: center; white-space: nowrap;">
                                        <div style="display: inline-flex; gap: 4px;">
                                            <a href="/admin/berita-acara.php?action=download_single&id=<?= $doc['id'] ?>" 
                                               class="btn btn-primary btn-sm" style="padding: 4px 8px; font-size: 0.75rem;" title="Unduh PDF Berita Acara">
                                                📥 Unduh
                                            </a>
                                            <a href="/admin/berita-acara.php?action=view_single&id=<?= $doc['id'] ?>" target="_blank"
                                               class="btn btn-secondary btn-sm" style="padding: 4px 8px; font-size: 0.75rem;" title="Lihat di Tab Baru">
                                                👁️
                                            </a>
                                            <button type="button" class="btn btn-secondary btn-sm" style="padding: 4px 8px; font-size: 0.75rem;" 
                                                     title="Lihat Detail" onclick='showRecordDetail(<?= json_encode($doc, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                                🔍
                                            </button>
                                            <form method="POST" action="/admin/berita-acara.php" style="display: inline;" 
                                                  onsubmit="return confirm('Hapus berita acara <?= htmlspecialchars($doc['nama_kelas'], ENT_QUOTES) ?> - <?= htmlspecialchars($doc['mata_pelajaran'], ENT_QUOTES) ?>?')">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= $doc['id'] ?>">
                                                <button type="submit" class="btn btn-danger btn-sm" style="padding: 4px 8px; font-size: 0.75rem;" title="Hapus Berita Acara">
                                                    🗑️
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
        </form>
    </div>
</div>

<!-- Modal Unduh Per Kelas -->
<div id="modalDownloadKelasBA" class="custom-modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: #fff; width: 90%; max-width: 440px; border-radius: 12px; padding: 1.5rem; box-shadow: 0 10px 25px rgba(0,0,0,0.2);">
        <h3 style="font-size: 1.15rem; margin-bottom: 0.5rem;">📦 Unduh ZIP Per Kelas</h3>
        <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1rem;">Pilih kelas untuk mengunduh seluruh Berita Acara dalam kelas tersebut:</p>
        <form method="GET" action="/admin/berita-acara.php">
            <input type="hidden" name="action" value="download_zip">
            <input type="hidden" name="mode" value="kelas">
            <div class="form-group">
                <label class="form-label">Kelas</label>
                <select name="class_id" class="form-select" required>
                    <option value="">-- Pilih Kelas --</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nama_kelas']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display: flex; gap: 0.5rem; justify-content: flex-end; margin-top: 1.5rem;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalDownloadKelasBA')">Batal</button>
                <button type="submit" class="btn btn-primary">Unduh ZIP</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Unduh Per Tanggal -->
<div id="modalDownloadTanggalBA" class="custom-modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: #fff; width: 90%; max-width: 440px; border-radius: 12px; padding: 1.5rem; box-shadow: 0 10px 25px rgba(0,0,0,0.2);">
        <h3 style="font-size: 1.15rem; margin-bottom: 0.5rem;">📅 Unduh ZIP Per Tanggal</h3>
        <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1rem;">Pilih tanggal pelaksanaan ujian:</p>
        <form method="GET" action="/admin/berita-acara.php">
            <input type="hidden" name="action" value="download_zip">
            <input type="hidden" name="mode" value="tanggal">
            <div class="form-group">
                <label class="form-label">Tanggal Ujian</label>
                <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div style="display: flex; gap: 0.5rem; justify-content: flex-end; margin-top: 1.5rem;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalDownloadTanggalBA')">Batal</button>
                <button type="submit" class="btn btn-primary">Unduh ZIP</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Galeri Foto -->
<div id="modalPhotoGallery" class="custom-modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: #fff; width: 90%; max-width: 750px; border-radius: 12px; padding: 1.5rem; max-height: 90vh; overflow-y: auto; box-shadow: 0 10px 25px rgba(0,0,0,0.3);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px solid var(--border); padding-bottom: 0.5rem;">
            <h3 id="galleryModalTitle" style="font-size: 1.1rem; margin: 0;">📸 Dokumentasi Pelaksanaan Ujian</h3>
            <button type="button" class="btn btn-secondary btn-sm" onclick="closeModal('modalPhotoGallery')">✕</button>
        </div>
        <div id="galleryPhotoContainer" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 12px;"></div>
    </div>
</div>

<!-- Modal Detail Record -->
<div id="modalDetailRecord" class="custom-modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: #fff; width: 90%; max-width: 600px; border-radius: 12px; padding: 1.5rem; max-height: 90vh; overflow-y: auto; box-shadow: 0 10px 25px rgba(0,0,0,0.2);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px solid var(--border); padding-bottom: 0.5rem;">
            <h3 style="font-size: 1.15rem; margin: 0;">Detail Berita Acara Ujian</h3>
            <button type="button" class="btn btn-secondary btn-sm" onclick="closeModal('modalDetailRecord')">✕</button>
        </div>
        <div id="modalDetailContent" style="font-size: 0.9rem; line-height: 1.6;"></div>
    </div>
</div>

<script>
function toggleDropdown(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
}

function openDownloadKelasModalBA() {
    document.getElementById('modalDownloadKelasBA').style.display = 'flex';
}
function openDownloadTanggalModalBA() {
    document.getElementById('modalDownloadTanggalBA').style.display = 'flex';
}
function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}

function toggleSelectAll(master) {
    const checkboxes = document.querySelectorAll('.row-checkbox');
    checkboxes.forEach(cb => cb.checked = master.checked);
    updateBatchToolbar();
}

function updateBatchToolbar() {
    const checked = document.querySelectorAll('.row-checkbox:checked');
    const count = checked.length;
    const toolbar = document.getElementById('batchActionToolbar');
    const display = document.getElementById('selectedCountDisplay');
    if (count > 0) {
        toolbar.style.display = 'flex';
        display.textContent = count;
    } else {
        toolbar.style.display = 'none';
    }
}

function submitBatchDownloadZip() {
    document.getElementById('formBatchZip').submit();
}

function showPhotoGallery(photos, title) {
    document.getElementById('galleryModalTitle').textContent = '📸 Dokumentasi: ' + title;
    const container = document.getElementById('galleryPhotoContainer');
    container.innerHTML = '';
    photos.forEach((p, idx) => {
        const item = document.createElement('div');
        item.style.border = '1px solid #e2e8f0';
        item.style.borderRadius = '8px';
        item.style.overflow = 'hidden';
        item.style.background = '#f8fafc';
        item.innerHTML = `
            <a href="/${p}" target="_blank" title="Klik untuk perbesar">
                <img src="/${p}" style="width: 100%; height: 200px; object-fit: cover; display: block;">
            </a>
            <div style="padding: 6px 10px; font-size: 0.75rem; color: #64748b; text-align: center;">Foto Bukti #${idx + 1}</div>
        `;
        container.appendChild(item);
    });
    document.getElementById('modalPhotoGallery').style.display = 'flex';
}

function showRecordDetail(doc) {
    const content = `
        <table class="table" style="font-size: 0.85rem;">
            <tr><th style="width: 35%;">ID Dokumen</th><td>#${doc.id}</td></tr>
            <tr><th>Nama Ujian</th><td>${doc.nama_ujian} (${doc.tahun_ajaran})</td></tr>
            <tr><th>Kelas & Tingkat</th><td><strong>${doc.nama_kelas}</strong> (Tingkat ${doc.tingkat})</td></tr>
            <tr><th>Mata Pelajaran</th><td><strong>${doc.mata_pelajaran}</strong></td></tr>
            <tr><th>Jadwal & Ruang</th><td>${doc.tanggal_ujian} | ${doc.jam_mulai} - ${doc.jam_selesai} WITA | Ruang: ${doc.ruang}</td></tr>
            <tr><th>Kehadiran Peserta</th><td>Hadir: <strong>${doc.peserta_hadir}</strong> dari ${doc.peserta_seharusnya} (Tidak Hadir: ${doc.peserta_tidak_hadir})</td></tr>
            <tr><th>Catatan Pelaksanaan</th><td><em>${doc.catatan_pelaksanaan || '-'}</em></td></tr>
            <tr><th>Pengawas I</th><td>${doc.nama_pengawas_1} ${doc.nip_pengawas_1 ? '(NIP. ' + doc.nip_pengawas_1 + ')' : ''}</td></tr>
            <tr><th>Pengawas II</th><td>${doc.nama_pengawas_2 || '-'}</td></tr>
            <tr><th>Lokasi Berkas</th><td><code>${doc.pdf_path}</code></td></tr>
            <tr><th>Ukuran Berkas</th><td>${Math.round(doc.file_size / 1024)} KB</td></tr>
            <tr><th>Dibuat Pada</th><td>${doc.created_at}</td></tr>
        </table>
        <div style="display: flex; gap: 0.5rem; justify-content: flex-end; margin-top: 1rem;">
            <a href="/admin/berita-acara.php?action=download_single&id=${doc.id}" class="btn btn-primary btn-sm">📥 Unduh PDF</a>
            <a href="/admin/berita-acara.php?action=view_single&id=${doc.id}" target="_blank" class="btn btn-secondary btn-sm">👁️ Lihat Tab Baru</a>
        </div>
    `;
    document.getElementById('modalDetailContent').innerHTML = content;
    document.getElementById('modalDetailRecord').style.display = 'flex';
}

function confirmResetAllBA() {
    const code = prompt("PERINGATAN: Tindakan ini akan MENGHAPUS SELURUH berkas arsip berita acara (database, file PDF, dan foto kegiatan) dari server untuk memulai dari awal.\n\nKetik 'HAPUS' (huruf besar) untuk melanjutkan:");
    if (code === 'HAPUS') {
        document.getElementById('formDeleteAllBA').submit();
    } else if (code !== null) {
        alert("Konfirmasi batal: Kata kunci tidak sesuai.");
    }
}
</script>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
