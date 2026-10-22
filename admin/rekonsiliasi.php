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

<div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; margin-bottom: 0.75rem;">
    <div>
        <h2><i class="bi bi-shield-check" style="color: var(--primary);"></i> Audit & Rekonsiliasi Dokumen</h2>
        <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 4px;">
            Pencocokan otomatis antara Arsip Daftar Hadir dan Berita Acara untuk melacak sesi yang belum lengkap atau terinput ganda.
        </p>
    </div>
    <div style="display: flex; gap: 0.5rem;">
        <a href="/admin/absensi.php" class="btn btn-secondary btn-sm">
            <i class="bi bi-file-earmark-check"></i> Arsip Daftar Hadir
        </a>
        <a href="/admin/berita-acara.php" class="btn btn-secondary btn-sm">
            <i class="bi bi-archive"></i> Arsip Berita Acara
        </a>
    </div>
</div>

<!-- Kartu Ringkasan Status -->
<div class="stats-grid" style="margin-bottom: 0.75rem;">
    <div class="stat-card" style="border-left: 4px solid var(--primary);">
        <div class="stat-icon" style="background: #eef0fd; color: var(--primary); font-size: 1.4rem;">
            <i class="bi bi-collection"></i>
        </div>
        <div>
            <div class="stat-value"><?= $totalSessions ?></div>
            <div class="stat-label">Total Sesi Ujian Terdeteksi</div>
        </div>
    </div>

    <div class="stat-card" style="border-left: 4px solid #ef4444;">
        <div class="stat-icon" style="background: #fee2e2; color: #dc2626; font-size: 1.4rem;">
            <i class="bi bi-exclamation-triangle-fill"></i>
        </div>
        <div>
            <div class="stat-value" style="color: #dc2626;"><?= $countMismatch ?></div>
            <div class="stat-label">Perlu Tindakan (Selisih / Masalah)</div>
        </div>
    </div>

    <div class="stat-card" style="border-left: 4px solid #f59e0b;">
        <div class="stat-icon" style="background: #fef3c7; color: #b45309; font-size: 1.4rem;">
            <i class="bi bi-clipboard-x"></i>
        </div>
        <div>
            <div class="stat-value"><?= $countMissingBA ?></div>
            <div class="stat-label">Kurang Berita Acara</div>
        </div>
    </div>

    <div class="stat-card" style="border-left: 4px solid #3b82f6;">
        <div class="stat-icon" style="background: #dbeafe; color: #1d4ed8; font-size: 1.4rem;">
            <i class="bi bi-file-earmark-x"></i>
        </div>
        <div>
            <div class="stat-value"><?= $countMissingDH ?></div>
            <div class="stat-label">Kurang Daftar Hadir</div>
        </div>
    </div>

    <div class="stat-card" style="border-left: 4px solid #8b5cf6;">
        <div class="stat-icon" style="background: #f3e8ff; color: #7e22ce; font-size: 1.4rem;">
            <i class="bi bi-copy"></i>
        </div>
        <div>
            <div class="stat-value"><?= $countDuplicate ?></div>
            <div class="stat-label">Sesi Dobel / Duplikat</div>
        </div>
    </div>
</div>

<!-- Filter & Tab Cepat -->
<div class="card" style="margin-bottom: 0.75rem;">
    <div class="card-body" style="padding: 0.65rem 0.85rem;">
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; border-bottom: 1px solid var(--border); padding-bottom: 0.85rem;">
            <a href="?status=mismatch" 
               class="btn btn-sm <?= $statusFilter === 'mismatch' ? 'btn-danger' : 'btn-secondary' ?>">
                <i class="bi bi-exclamation-circle"></i> Tampilkan Selisih / Masalah (<?= $countMismatch ?>)
            </a>
            <a href="?status=missing_ba" 
               class="btn btn-sm <?= $statusFilter === 'missing_ba' ? 'btn-warning' : 'btn-secondary' ?>">
                <i class="bi bi-clipboard-x"></i> Kurang Berita Acara (<?= $countMissingBA ?>)
            </a>
            <a href="?status=missing_dh" 
               class="btn btn-sm <?= $statusFilter === 'missing_dh' ? 'btn-primary' : 'btn-secondary' ?>">
                <i class="bi bi-file-earmark-x"></i> Kurang Daftar Hadir (<?= $countMissingDH ?>)
            </a>
            <a href="?status=duplicate" 
               class="btn btn-sm <?= $statusFilter === 'duplicate' ? 'btn-primary' : 'btn-secondary' ?>">
                <i class="bi bi-copy"></i> Duplikat (<?= $countDuplicate ?>)
            </a>
            <a href="?status=complete" 
               class="btn btn-sm <?= $statusFilter === 'complete' ? 'btn-success' : 'btn-secondary' ?>">
                <i class="bi bi-check-all"></i> Lengkap (<?= $countComplete ?>)
            </a>
            <a href="?status=all" 
               class="btn btn-sm <?= $statusFilter === 'all' ? 'btn-primary' : 'btn-secondary' ?>">
                <i class="bi bi-list-ul"></i> Semua Sesi (<?= $totalSessions ?>)
            </a>
        </div>
    </div>
</div>

<!-- Tabel Hasil Rekonsiliasi -->
<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <span class="card-title">Daftar Hasil Audit & Rekonsiliasi (<?= count($filteredSessions) ?> Sesi)</span>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="table" style="font-size: 0.85rem; margin-bottom: 0;">
                <thead>
                    <tr>
                        <th style="width: 40px; text-align: center;">No</th>
                        <th>Tanggal Pelaksanaan</th>
                        <th>Kelas</th>
                        <th>Mata Pelajaran Ujian</th>
                        <th style="text-align: center;">Status Audit</th>
                        <th>Daftar Hadir</th>
                        <th>Berita Acara</th>
                        <th style="text-align: right;">Tindakan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($filteredSessions)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                                Tidak ada sesi ujian pada kategori ini.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($filteredSessions as $idx => $s): ?>
                            <tr>
                                <td style="text-align: center; color: var(--text-muted);"><?= $idx + 1 ?></td>
                                <td><strong><?= date('d/m/Y', strtotime($s['tanggal_ujian'])) ?></strong></td>
                                <td>
                                    <span class="badge badge-info"><?= htmlspecialchars($s['nama_kelas']) ?></span>
                                </td>
                                <td><strong><?= htmlspecialchars($s['mata_pelajaran']) ?></strong></td>
                                <td style="text-align: center;">
                                    <?php if ($s['status'] === 'complete'): ?>
                                        <span class="badge badge-success">✓ Lengkap (1:1)</span>
                                    <?php elseif ($s['status'] === 'missing_ba'): ?>
                                        <span class="badge badge-danger">⚠️ Kurang Berita Acara</span>
                                    <?php elseif ($s['status'] === 'missing_dh'): ?>
                                        <span class="badge badge-warning">⚠️ Kurang Daftar Hadir</span>
                                    <?php elseif ($s['status'] === 'duplicate'): ?>
                                        <span class="badge badge-warning">⚠️ Duplikat Dokumen</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($s['dh'])): ?>
                                        <?php foreach ($s['dh'] as $d): ?>
                                            <div>
                                                <a href="/admin/absensi.php?action=view_single&id=<?= $d['id'] ?>" target="_blank" style="text-decoration: none; color: var(--primary);">
                                                    📑 DH #<?= $d['id'] ?> (<?= htmlspecialchars($d['nama_pengawas_1']) ?>)
                                                </a>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span style="color: #dc2626; font-weight: bold;">✕ Belum Ada</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($s['ba'])): ?>
                                        <?php foreach ($s['ba'] as $b): ?>
                                            <div>
                                                <a href="/admin/berita-acara.php?action=view_single&id=<?= $b['id'] ?>" target="_blank" style="text-decoration: none; color: #b45309;">
                                                    📋 BA #<?= $b['id'] ?> (<?= htmlspecialchars($b['nama_pengawas_1']) ?>)
                                                </a>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span style="color: #dc2626; font-weight: bold;">✕ Belum Ada</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right; white-space: nowrap;">
                                    <?php if ($s['status'] === 'missing_ba'): ?>
                                        <a href="/berita-acara.php" target="_blank" class="btn btn-primary btn-sm" style="padding: 3px 8px; font-size: 0.75rem;">
                                            ➕ Buat BA
                                        </a>
                                    <?php elseif ($s['status'] === 'missing_dh'): ?>
                                        <a href="/absensi.php" target="_blank" class="btn btn-primary btn-sm" style="padding: 3px 8px; font-size: 0.75rem;">
                                            ➕ Buat DH
                                        </a>
                                    <?php else: ?>
                                        <span style="font-size: 0.8rem; color: #16a34a;">Lengkap</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
