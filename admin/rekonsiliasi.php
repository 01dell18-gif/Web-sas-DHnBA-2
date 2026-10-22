<?php
$pageTitle = 'Audit & Rekonsiliasi Dokumen';
require_once __DIR__ . '/layout_header.php';

// Fetch all absensi and berita acara records
$allDH = dbFetchAll("SELECT * FROM absensi ORDER BY tanggal_ujian DESC, id DESC");
$allBA = dbFetchAll("SELECT * FROM berita_acara ORDER BY tanggal_ujian DESC, id DESC");

// Group sessions by: tanggal_ujian + '||' + class_id + '||' + LOWER(TRIM(mata_pelajaran))
$sessions = [];

foreach ($allDH as $dh) {
    $mpClean = strtolower(trim($dh['mata_pelajaran']));
    $key = $dh['tanggal_ujian'] . '||' . $dh['class_id'] . '||' . $mpClean;
    if (!isset($sessions[$key])) {
        $sessions[$key] = [
            'tanggal_ujian' => $dh['tanggal_ujian'],
            'class_id' => $dh['class_id'],
            'nama_kelas' => $dh['nama_kelas'],
            'tingkat' => $dh['tingkat'],
            'mata_pelajaran' => $dh['mata_pelajaran'],
            'dh' => [],
            'ba' => []
        ];
    }
    $sessions[$key]['dh'][] = $dh;
}

foreach ($allBA as $ba) {
    $mpClean = strtolower(trim($ba['mata_pelajaran']));
    $key = $ba['tanggal_ujian'] . '||' . $ba['class_id'] . '||' . $mpClean;
    if (!isset($sessions[$key])) {
        $sessions[$key] = [
            'tanggal_ujian' => $ba['tanggal_ujian'],
            'class_id' => $ba['class_id'],
            'nama_kelas' => $ba['nama_kelas'],
            'tingkat' => $ba['tingkat'],
            'mata_pelajaran' => $ba['mata_pelajaran'],
            'dh' => [],
            'ba' => []
        ];
    }
    $sessions[$key]['ba'][] = $ba;
}

// Classify status for each session
$totalSessions = count($sessions);
$countMissingBA = 0;
$countMissingDH = 0;
$countDuplicate = 0;
$countComplete = 0;

foreach ($sessions as $key => &$sess) {
    $cDH = count($sess['dh']);
    $cBA = count($sess['ba']);
    if ($cDH > 1 || $cBA > 1) {
        $sess['status'] = 'duplicate';
        $countDuplicate++;
    } elseif ($cDH > 0 && $cBA === 0) {
        $sess['status'] = 'missing_ba';
        $countMissingBA++;
    } elseif ($cBA > 0 && $cDH === 0) {
        $sess['status'] = 'missing_dh';
        $countMissingDH++;
    } else {
        $sess['status'] = 'complete';
        $countComplete++;
    }
}
unset($sess);

$countMismatch = $countMissingBA + $countMissingDH + $countDuplicate;

// Filter
$statusFilter = $_GET['status'] ?? 'mismatch';
$filteredSessions = [];

foreach ($sessions as $key => $sess) {
    if ($statusFilter === 'all') {
        $filteredSessions[] = $sess;
    } elseif ($statusFilter === 'mismatch') {
        if ($sess['status'] !== 'complete') {
            $filteredSessions[] = $sess;
        }
    } else {
        if ($sess['status'] === $statusFilter) {
            $filteredSessions[] = $sess;
        }
    }
}
?>

<!-- Modern Header Banner -->
<div class="page-header-card">
    <div class="page-header-content">
        <div class="page-header-icon-box">
            <i class="bi bi-shield-check"></i>
        </div>
        <div>
            <h1 class="page-header-title">Audit & Rekonsiliasi Dokumen</h1>
            <p class="page-header-subtitle">
                Pencocokan otomatis antara Arsip Daftar Hadir dan Berita Acara untuk melacak sesi yang belum lengkap atau terinput ganda.
            </p>
        </div>
    </div>
    <div class="page-header-actions">
        <a href="/admin/absensi.php" class="btn btn-secondary btn-sm">
            <i class="bi bi-file-earmark-check"></i> <span>Arsip Daftar Hadir</span>
        </a>
        <a href="/admin/berita-acara.php" class="btn btn-secondary btn-sm">
            <i class="bi bi-archive"></i> <span>Arsip Berita Acara</span>
        </a>
    </div>
</div>

<!-- Kartu Ringkasan Status Ultra-Modern -->
<div class="stats-grid">
    <div class="stat-card-modern">
        <div class="stat-accent-bar" style="background: linear-gradient(90deg, #4f46e5, #6366f1);"></div>
        <div class="stat-icon stat-icon-indigo">
            <i class="bi bi-collection-fill"></i>
        </div>
        <div>
            <div class="stat-value"><?= $totalSessions ?></div>
            <div class="stat-label">Total Sesi Ujian</div>
            <span class="stat-meta-pill" style="background: #eef2ff; color: #4338ca;">
                <i class="bi bi-activity"></i> Semua Terdeteksi
            </span>
        </div>
    </div>

    <div class="stat-card-modern">
        <div class="stat-accent-bar" style="background: linear-gradient(90deg, #e11d48, #f43f5e);"></div>
        <div class="stat-icon stat-icon-rose">
            <i class="bi bi-exclamation-triangle-fill"></i>
        </div>
        <div>
            <div class="stat-value" style="color: #e11d48;"><?= $countMismatch ?></div>
            <div class="stat-label">Perlu Tindakan</div>
            <span class="stat-meta-pill" style="background: #fff1f2; color: #e11d48;">
                <i class="bi bi-clock-history"></i> Segera Dilengkapi
            </span>
        </div>
    </div>

    <div class="stat-card-modern">
        <div class="stat-accent-bar" style="background: linear-gradient(90deg, #d97706, #f59e0b);"></div>
        <div class="stat-icon stat-icon-amber">
            <i class="bi bi-clipboard-x-fill"></i>
        </div>
        <div>
            <div class="stat-value"><?= $countMissingBA ?></div>
            <div class="stat-label">Kurang Berita Acara</div>
            <span class="stat-meta-pill" style="background: #fffbeb; color: #b45309;">
                <i class="bi bi-arrow-right"></i> Butuh BA
            </span>
        </div>
    </div>

    <div class="stat-card-modern">
        <div class="stat-accent-bar" style="background: linear-gradient(90deg, #0284c7, #38bdf8);"></div>
        <div class="stat-icon stat-icon-sky">
            <i class="bi bi-file-earmark-x-fill"></i>
        </div>
        <div>
            <div class="stat-value"><?= $countMissingDH ?></div>
            <div class="stat-label">Kurang Daftar Hadir</div>
            <span class="stat-meta-pill" style="background: #f0f9ff; color: #0284c7;">
                <i class="bi bi-arrow-right"></i> Butuh Presensi
            </span>
        </div>
    </div>

    <div class="stat-card-modern">
        <div class="stat-accent-bar" style="background: linear-gradient(90deg, #7c3aed, #a855f7);"></div>
        <div class="stat-icon stat-icon-purple">
            <i class="bi bi-copy"></i>
        </div>
        <div>
            <div class="stat-value"><?= $countDuplicate ?></div>
            <div class="stat-label">Sesi Duplikat</div>
            <span class="stat-meta-pill" style="background: #f5f3ff; color: #7c3aed;">
                <i class="bi bi-intersect"></i> Input Ganda
            </span>
        </div>
    </div>
</div>

<!-- Modern Segmented Filter Controls -->
<div style="margin-bottom: 0.85rem;">
    <div class="filter-segmented-wrapper">
        <a href="?status=mismatch" 
           class="filter-tab-item <?= $statusFilter === 'mismatch' ? 'active-mismatch' : '' ?>">
            <i class="bi bi-exclamation-circle-fill"></i>
            <span>Tampilkan Selisih / Masalah</span>
            <span class="filter-counter"><?= $countMismatch ?></span>
        </a>
        <a href="?status=missing_ba" 
           class="filter-tab-item <?= $statusFilter === 'missing_ba' ? 'active-missing-ba' : '' ?>">
            <i class="bi bi-clipboard-x"></i>
            <span>Kurang Berita Acara</span>
            <span class="filter-counter"><?= $countMissingBA ?></span>
        </a>
        <a href="?status=missing_dh" 
           class="filter-tab-item <?= $statusFilter === 'missing_dh' ? 'active-missing-dh' : '' ?>">
            <i class="bi bi-file-earmark-x"></i>
            <span>Kurang Daftar Hadir</span>
            <span class="filter-counter"><?= $countMissingDH ?></span>
        </a>
        <a href="?status=duplicate" 
           class="filter-tab-item <?= $statusFilter === 'duplicate' ? 'active-duplicate' : '' ?>">
            <i class="bi bi-copy"></i>
            <span>Duplikat</span>
            <span class="filter-counter"><?= $countDuplicate ?></span>
        </a>
        <a href="?status=complete" 
           class="filter-tab-item <?= $statusFilter === 'complete' ? 'active-complete' : '' ?>">
            <i class="bi bi-check-circle-fill"></i>
            <span>Lengkap</span>
            <span class="filter-counter"><?= $countComplete ?></span>
        </a>
        <a href="?status=all" 
           class="filter-tab-item <?= $statusFilter === 'all' ? 'active-all' : '' ?>">
            <i class="bi bi-grid-fill"></i>
            <span>Semua Sesi</span>
            <span class="filter-counter"><?= $totalSessions ?></span>
        </a>
    </div>
</div>

<!-- Tabel Hasil Rekonsiliasi Ultra-Modern -->
<div class="table-modern-card">
    <div class="table-modern-card-header">
        <div style="display: flex; align-items: center; gap: 0.65rem;">
            <i class="bi bi-table" style="color: var(--primary); font-size: 1.1rem;"></i>
            <span style="font-weight: 700; color: var(--text-heading); font-size: 0.925rem;">
                Daftar Hasil Audit & Rekonsiliasi
            </span>
            <span class="badge" style="background: #f1f5f9; color: #475569; font-weight: 700; font-size: 0.72rem;">
                <?= count($filteredSessions) ?> Sesi Ditemukan
            </span>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table" style="margin-bottom: 0;">
            <thead>
                <tr>
                    <th style="width: 44px; text-align: center;">No</th>
                    <th style="min-width: 130px;">Tanggal Pelaksanaan</th>
                    <th style="min-width: 90px;">Kelas</th>
                    <th style="min-width: 200px;">Mata Pelajaran Ujian</th>
                    <th style="min-width: 160px; text-align: center;">Status Audit</th>
                    <th style="min-width: 200px;">Daftar Hadir</th>
                    <th style="min-width: 200px;">Berita Acara</th>
                    <th style="min-width: 110px; text-align: right;">Tindakan</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($filteredSessions)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 2.5rem 1rem;">
                            <div style="font-size: 2rem; margin-bottom: 0.5rem;">🎉</div>
                            <div style="font-weight: 700; color: var(--text-heading); font-size: 1rem;">Semua Sesi Telah Cocok!</div>
                            <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 2px;">
                                Tidak ada dokumen ujian yang selisih atau bermasalah pada kategori ini.
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($filteredSessions as $idx => $s): ?>
                        <tr>
                            <td style="text-align: center; color: var(--text-subtle); font-weight: 600;"><?= $idx + 1 ?></td>
                            <td>
                                <span style="font-weight: 700; color: var(--text-heading);">
                                    <?= date('d/m/Y', strtotime($s['tanggal_ujian'])) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge-class-pill"><?= htmlspecialchars($s['nama_kelas']) ?></span>
                            </td>
                            <td>
                                <span style="font-weight: 700; color: var(--text-heading); font-size: 0.865rem;">
                                    <?= htmlspecialchars($s['mata_pelajaran']) ?>
                                </span>
                            </td>
                            <td style="text-align: center;">
                                <?php if ($s['status'] === 'complete'): ?>
                                    <span class="status-pill status-pill-success">
                                        <span class="dot-pulse dot-success"></span> Lengkap (1:1)
                                    </span>
                                <?php elseif ($s['status'] === 'missing_ba'): ?>
                                    <span class="status-pill status-pill-warning">
                                        <span class="dot-pulse dot-warning"></span> Kurang Berita Acara
                                    </span>
                                <?php elseif ($s['status'] === 'missing_dh'): ?>
                                    <span class="status-pill status-pill-danger">
                                        <span class="dot-pulse dot-danger"></span> Kurang Daftar Hadir
                                    </span>
                                <?php elseif ($s['status'] === 'duplicate'): ?>
                                    <span class="status-pill status-pill-purple">
                                        <span class="dot-pulse dot-purple"></span> Duplikat Sesi
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($s['dh'])): ?>
                                    <?php foreach ($s['dh'] as $d): ?>
                                        <div>
                                            <a href="/admin/absensi.php?action=view_single&id=<?= $d['id'] ?>" target="_blank" class="doc-chip doc-chip-dh" title="Buka PDF Daftar Hadir #<?= $d['id'] ?>">
                                                <i class="bi bi-file-earmark-check"></i>
                                                <span>DH #<?= $d['id'] ?></span>
                                                <span class="doc-chip-sub">(<?= htmlspecialchars($d['nama_pengawas_1']) ?>)</span>
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="doc-chip-empty">
                                        <i class="bi bi-x-circle-fill"></i> Belum Ada
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($s['ba'])): ?>
                                    <?php foreach ($s['ba'] as $b): ?>
                                        <div>
                                            <a href="/admin/berita-acara.php?action=view_single&id=<?= $b['id'] ?>" target="_blank" class="doc-chip doc-chip-ba" title="Buka PDF Berita Acara #<?= $b['id'] ?>">
                                                <i class="bi bi-clipboard-data"></i>
                                                <span>BA #<?= $b['id'] ?></span>
                                                <span class="doc-chip-sub">(<?= htmlspecialchars($b['nama_pengawas_1']) ?>)</span>
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="doc-chip-empty">
                                        <i class="bi bi-x-circle-fill"></i> Belum Ada
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right; white-space: nowrap;">
                                <?php if ($s['status'] === 'missing_ba'): ?>
                                    <a href="/berita-acara.php" target="_blank" class="btn-action-glow btn-action-glow-indigo">
                                        <i class="bi bi-plus-lg"></i> Buat BA
                                    </a>
                                <?php elseif ($s['status'] === 'missing_dh'): ?>
                                    <a href="/absensi.php" target="_blank" class="btn-action-glow btn-action-glow-indigo">
                                        <i class="bi bi-plus-lg"></i> Buat DH
                                    </a>
                                <?php elseif ($s['status'] === 'duplicate'): ?>
                                    <span class="status-pill status-pill-purple" style="font-size: 0.72rem; padding: 2px 8px;">
                                        Periksa Sesi
                                    </span>
                                <?php else: ?>
                                    <span class="action-done-chip">
                                        <i class="bi bi-check-circle-fill"></i> Lengkap
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
