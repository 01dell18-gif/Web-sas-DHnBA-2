<?php
$pageTitle = 'Dashboard Administrator';
require_once __DIR__ . '/layout_header.php';

$totalKelas = (int)dbValue("SELECT COUNT(*) FROM kelas");
$aktifKelas = (int)dbValue("SELECT COUNT(*) FROM kelas WHERE aktif = 1");

$totalSiswa = (int)dbValue("SELECT COUNT(*) FROM siswa");
$aktifSiswa = (int)dbValue("SELECT COUNT(*) FROM siswa WHERE aktif = 1");

$totalAbsensi = (int)dbValue("SELECT COUNT(*) FROM absensi");
$totalBeritaAcara = (int)dbValue("SELECT COUNT(*) FROM berita_acara");
$selisih = abs($totalAbsensi - $totalBeritaAcara);

$driveUrl = get_setting('google_drive_folder_url', 'https://drive.google.com');

$logs = dbFetchAll("SELECT * FROM activity_logs ORDER BY id DESC LIMIT 10");
?>

<div class="page-header-card">
    <div class="page-header-content">
        <div class="page-header-icon-box">
            <i class="bi bi-speedometer2"></i>
        </div>
        <div>
            <h1 class="page-header-title">Dashboard Administrator</h1>
            <p class="page-header-subtitle">
                Selamat datang di sistem Generator Absensi dan Berita Acara Ujian <?= htmlspecialchars($namaSekolah) ?>.
            </p>
        </div>
    </div>
    <div class="page-header-actions">
        <a href="/admin/rekonsiliasi.php" class="btn btn-secondary btn-sm">
            <i class="bi bi-shield-check"></i> <span>Audit & Rekonsiliasi</span>
        </a>
    </div>
</div>

<?php if ($selisih > 0): ?>
<div style="background: linear-gradient(135deg, #fff1f2 0%, #ffe4e6 100%); border: 1px solid #fecdd3; border-left: 4px solid #e11d48; border-radius: 14px; padding: 0.85rem 1.15rem; margin-bottom: 0.85rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; box-shadow: 0 4px 12px rgba(225, 29, 72, 0.08);">
    <div style="display: flex; align-items: center; gap: 0.85rem;">
        <div style="width: 44px; height: 44px; border-radius: 12px; background: #fee2e2; color: #e11d48; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0; box-shadow: 0 2px 6px rgba(225, 29, 72, 0.2);">
            <i class="bi bi-exclamation-triangle-fill"></i>
        </div>
        <div>
            <div style="font-weight: 800; color: #9f1239; font-size: 0.95rem;">
                Terdeteksi Selisih Jumlah Dokumen Ujian!
            </div>
            <div style="color: #4c0519; font-size: 0.835rem; margin-top: 2px;">
                Daftar Hadir: <strong><?= $totalAbsensi ?></strong> vs Berita Acara: <strong><?= $totalBeritaAcara ?></strong> (Selisih: <strong><?= $selisih ?> dokumen</strong>).
                Ada sesi yang belum dibuat Berita Acara-nya atau pengawas menginput ganda.
            </div>
        </div>
    </div>
    <div>
        <a href="/admin/rekonsiliasi.php?status=mismatch" class="btn-action-glow btn-action-glow-indigo" style="background: linear-gradient(135deg, #e11d48, #be123c); box-shadow: 0 3px 10px rgba(225, 29, 72, 0.3);">
            <i class="bi bi-search"></i> Telusuri & Lihat Pelakunya ➔
        </a>
    </div>
</div>
<?php endif; ?>

<div class="stats-grid">
    <div class="stat-card-modern">
        <div class="stat-accent-bar" style="background: linear-gradient(90deg, #4f46e5, #6366f1);"></div>
        <div class="stat-icon stat-icon-indigo">
            <i class="bi bi-mortarboard-fill"></i>
        </div>
        <div>
            <div class="stat-value"><?= $aktifKelas ?> <span style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">/ <?= $totalKelas ?></span></div>
            <div class="stat-label">Kelas Aktif / Total</div>
            <span class="stat-meta-pill" style="background: #eef2ff; color: #4338ca;">
                <i class="bi bi-check-circle"></i> Terdaftar
            </span>
        </div>
    </div>

    <div class="stat-card-modern">
        <div class="stat-accent-bar" style="background: linear-gradient(90deg, #059669, #10b981);"></div>
        <div class="stat-icon" style="background: linear-gradient(135deg, #ecfdf5, #d1fae5); color: #059669;">
            <i class="bi bi-people-fill"></i>
        </div>
        <div>
            <div class="stat-value"><?= $aktifSiswa ?> <span style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">/ <?= $totalSiswa ?></span></div>
            <div class="stat-label">Siswa Aktif / Total</div>
            <span class="stat-meta-pill" style="background: #ecfdf5; color: #059669;">
                <i class="bi bi-person-check"></i> Siap Ujian
            </span>
        </div>
    </div>

    <div class="stat-card-modern">
        <div class="stat-accent-bar" style="background: linear-gradient(90deg, #0284c7, #38bdf8);"></div>
        <div class="stat-icon stat-icon-sky">
            <i class="bi bi-file-earmark-check-fill"></i>
        </div>
        <div>
            <div class="stat-value"><?= $totalAbsensi ?></div>
            <div class="stat-label">Arsip Daftar Hadir</div>
            <span class="stat-meta-pill" style="background: #f0f9ff; color: #0284c7;">
                <i class="bi bi-archive"></i> Tersimpan
            </span>
        </div>
    </div>

    <div class="stat-card-modern">
        <div class="stat-accent-bar" style="background: linear-gradient(90deg, #d97706, #f59e0b);"></div>
        <div class="stat-icon stat-icon-amber">
            <i class="bi bi-clipboard-data-fill"></i>
        </div>
        <div>
            <div class="stat-value"><?= $totalBeritaAcara ?></div>
            <div class="stat-label">Arsip Berita Acara</div>
            <span class="stat-meta-pill" style="background: #fffbeb; color: #b45309;">
                <i class="bi bi-journal-check"></i> Terverifikasi
            </span>
        </div>
    </div>

    <div class="stat-card-modern">
        <div class="stat-accent-bar" style="background: linear-gradient(90deg, #1a73e8, #4285f4);"></div>
        <div class="stat-icon" style="background: linear-gradient(135deg, #e8f0fe, #d2e3fc); color: #1a73e8;">
            <i class="bi bi-google"></i>
        </div>
        <div>
            <div style="font-size: 0.875rem; font-weight: 700; margin-bottom: 4px; color: var(--text-heading);">Google Drive</div>
            <a href="<?= htmlspecialchars($driveUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-drive btn-sm" style="border-radius: 8px;">
                Buka Cloud ↗
            </a>
        </div>
    </div>
</div>

<div class="grid-2">
    <!-- Pintasan Cepat -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">Aksi & Pintasan Cepat</span>
        </div>
        <div class="card-body" style="display: flex; flex-direction: column; gap: 0.75rem;">
            <a href="/admin/absensi.php" class="btn btn-primary">
                📑 Kelola & Unduh Arsip Daftar Hadir (.ZIP / .PDF)
            </a>
            <a href="/admin/berita-acara.php" class="btn btn-primary" style="background-color: #b45309; border-color: #b45309;">
                📋 Kelola & Unduh Berita Acara (.ZIP / .PDF)
            </a>
            <a href="/admin/rekonsiliasi.php" class="btn btn-secondary" style="border-color: #4657d9; color: #4657d9; background: #eef0fd; font-weight: 600;">
                🛡️ Audit & Rekonsiliasi Kelengkapan Dokumen (Cek Selisih)
            </a>
            <a href="/admin/rekap-kehadiran.php" class="btn btn-secondary">
                📊 Matriks Rekap Kehadiran Siswa
            </a>
            <a href="/admin/import-siswa.php" class="btn btn-secondary">
                📥 Impor Data Siswa dari Excel/CSV
            </a>
            <a href="/admin/kelas.php" class="btn btn-secondary">
                🏫 Kelola Data Kelas
            </a>
            <a href="/admin/siswa.php" class="btn btn-secondary">
                👨‍🎓 Kelola Data Siswa
            </a>
            <?php if (is_admin()): ?>
                <a href="/admin/template-ujian.php" class="btn btn-secondary">
                    📝 Atur Template & Redaksi Berita Acara
                </a>
                <a href="/admin/users.php" class="btn btn-secondary">
                    👥 Manajemen User & Hak Akses
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Log Aktivitas Terakhir -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">Log Aktivitas Terbaru</span>
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Aktivitas</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="3" style="text-align: center; color: var(--text-muted); padding: 1.5rem;">
                                    Belum ada aktivitas tercatat.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td style="font-size: 0.8rem; white-space: nowrap;">
                                        <?= date('d/m/Y H:i', strtotime($log['waktu'])) ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-info"><?= htmlspecialchars($log['aktivitas']) ?></span>
                                    </td>
                                    <td style="font-size: 0.825rem;"><?= htmlspecialchars($log['keterangan']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
