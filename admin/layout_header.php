<?php
require_once __DIR__ . '/../config/functions.php';
require_admin();

$namaSekolah = get_setting('nama_sekolah', 'SMK NEGERI 1 KLUNGKUNG');
$logoPath = get_setting('logo_path', 'storage/uploads/logo/logo_default.png');
$currentScript = basename($_SERVER['PHP_SELF']);
$pageTitle = $pageTitle ?? 'Panel Administrator';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - <?= htmlspecialchars($namaSekolah) ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
    <link rel="icon" type="image/png" href="/<?= htmlspecialchars($logoPath) ?>">
</head>
<body>

<header class="navbar">
    <div style="display: flex; align-items: center; gap: 0.75rem;">
        <button type="button" class="btn-sidebar-toggle" id="btnToggleSidebar" onclick="toggleAdminSidebar(event)" aria-label="Toggle Sidebar" title="Sembunyikan / Tampilkan Menu">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
        </button>
        <div class="navbar-brand">
            <a href="/admin/index.php" class="brand-link">
                <img src="/<?= htmlspecialchars($logoPath) ?>" alt="Logo Sekolah" class="navbar-logo">
                <div class="brand-text">
                    <span class="brand-title"><?= htmlspecialchars($namaSekolah) ?></span>
                    <span class="brand-subtitle">Admin Panel Administrasi</span>
                </div>
            </a>
        </div>
    </div>
    <div style="display: flex; align-items: center; gap: 0.75rem;">
        <div class="user-profile-chip admin-welcome-text">
            <div class="user-avatar-icon">
                <i class="bi bi-person-fill"></i>
            </div>
            <div class="user-info-text">
                <span class="user-name"><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Administrator') ?></span>
                <span class="<?= is_admin() ? 'badge-role-admin' : 'badge-role-operator' ?>">
                    <?= is_admin() ? '👑 Admin' : '🛡️ Operator' ?>
                </span>
            </div>
        </div>
        <a href="/" target="_blank" class="btn btn-secondary btn-sm" title="Buka Halaman Publik">
            <i class="bi bi-globe2"></i> <span>Web Publik</span>
        </a>
        <a href="/admin/logout.php" class="btn btn-danger-soft btn-sm" data-confirm="Apakah Anda yakin ingin keluar dari sistem aplikasi?" data-confirm-title="Konfirmasi Keluar (Logout)" title="Keluar dari sesi admin">
            <i class="bi bi-box-arrow-right"></i> <span>Keluar</span>
        </a>
    </div>
</header>

<div class="admin-sidebar-layout">
    <aside class="admin-sidebar">
        <div class="sidebar-section-title">RINGKASAN</div>
        <a href="/admin/index.php" class="admin-menu-item <?= $currentScript === 'index.php' ? 'active' : '' ?>">
            <i class="bi bi-speedometer2"></i> <span>Dashboard</span>
        </a>

        <div class="sidebar-section-title">DATA MASTER</div>
        <a href="/admin/kelas.php" class="admin-menu-item <?= $currentScript === 'kelas.php' ? 'active' : '' ?>">
            <i class="bi bi-mortarboard"></i> <span>Data Kelas</span>
        </a>
        <a href="/admin/siswa.php" class="admin-menu-item <?= $currentScript === 'siswa.php' ? 'active' : '' ?>">
            <i class="bi bi-person-lines-fill"></i> <span>Data Siswa</span>
        </a>
        <a href="/admin/guru.php" class="admin-menu-item <?= $currentScript === 'guru.php' ? 'active' : '' ?>">
            <i class="bi bi-person-badge"></i> <span>Data Guru</span>
        </a>
        <a href="/admin/mapel.php" class="admin-menu-item <?= $currentScript === 'mapel.php' ? 'active' : '' ?>">
            <i class="bi bi-journal-bookmark-fill"></i> <span>Data Mapel</span>
        </a>
        <a href="/admin/import-siswa.php" class="admin-menu-item <?= ($currentScript === 'import-siswa.php' || $currentScript === 'import-excel.php') ? 'active' : '' ?>">
            <i class="bi bi-file-earmark-spreadsheet"></i> <span>Impor Excel</span>
        </a>

        <div class="sidebar-section-title">DOKUMEN & AUDIT</div>
        <a href="/admin/rekap-kehadiran.php" class="admin-menu-item <?= $currentScript === 'rekap-kehadiran.php' ? 'active' : '' ?>">
            <i class="bi bi-card-checklist"></i> <span>Rekap Kehadiran</span>
        </a>
        <a href="/admin/absensi.php" class="admin-menu-item <?= $currentScript === 'absensi.php' ? 'active' : '' ?>">
            <i class="bi bi-file-earmark-check"></i> <span>Arsip Daftar Hadir</span>
        </a>
        <a href="/admin/berita-acara.php" class="admin-menu-item <?= $currentScript === 'berita-acara.php' ? 'active' : '' ?>">
            <i class="bi bi-archive"></i> <span>Arsip Berita Acara</span>
        </a>
        <a href="/admin/rekonsiliasi.php" class="admin-menu-item <?= $currentScript === 'rekonsiliasi.php' ? 'active' : '' ?>">
            <i class="bi bi-shield-check"></i> <span>Rekonsiliasi Dokumen</span>
        </a>

        <div class="sidebar-section-title">PENGATURAN</div>
        <?php if (is_admin()): ?>
            <a href="/admin/template-ujian.php" class="admin-menu-item <?= $currentScript === 'template-ujian.php' ? 'active' : '' ?>">
                <i class="bi bi-file-earmark-richtext"></i> <span>Template Ujian</span>
            </a>
            <a href="/admin/pengaturan.php" class="admin-menu-item <?= $currentScript === 'pengaturan.php' ? 'active' : '' ?>">
                <i class="bi bi-sliders"></i> <span>Pengaturan Kop & Drive</span>
            </a>
            <a href="/admin/users.php" class="admin-menu-item <?= $currentScript === 'users.php' ? 'active' : '' ?>">
                <i class="bi bi-people"></i> <span>Manajemen User</span>
            </a>
        <?php endif; ?>
    </aside>

    <main class="admin-main">
        <?= render_flash() ?>
