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
        <!-- Dashboard Standalone -->
        <a href="/admin/index.php" class="admin-menu-item <?= $currentScript === 'index.php' ? 'active' : '' ?>" style="margin-bottom: 0.5rem;">
            <i class="bi bi-speedometer2"></i> <span>Dashboard</span>
        </a>

        <!-- GRUP 1: DATA MASTER -->
        <?php 
        $isMasterActive = in_array($currentScript, ['kelas.php', 'guru.php', 'mapel.php']); 
        ?>
        <div class="sidebar-nav-group <?= $isMasterActive ? 'is-open' : '' ?>">
            <button type="button" class="sidebar-group-header <?= $isMasterActive ? 'has-active' : '' ?>" onclick="toggleSidebarGroup(this)">
                <span class="group-header-left">
                    <i class="group-icon bi bi-buildings"></i>
                    <span class="group-title">Data Master</span>
                </span>
                <i class="group-arrow bi bi-caret-down-fill"></i>
            </button>
            <div class="sidebar-group-sub">
                <div class="sidebar-tree-line"></div>
                <a href="/admin/kelas.php" class="sidebar-sub-item <?= $currentScript === 'kelas.php' ? 'active' : '' ?>">
                    <span class="sidebar-sub-bullet"></span>
                    <span class="sidebar-sub-text">Data Kelas</span>
                </a>
                <a href="/admin/guru.php" class="sidebar-sub-item <?= $currentScript === 'guru.php' ? 'active' : '' ?>">
                    <span class="sidebar-sub-bullet"></span>
                    <span class="sidebar-sub-text">Data Guru & Pengawas</span>
                </a>
                <a href="/admin/mapel.php" class="sidebar-sub-item <?= $currentScript === 'mapel.php' ? 'active' : '' ?>">
                    <span class="sidebar-sub-bullet"></span>
                    <span class="sidebar-sub-text">Data Mapel</span>
                </a>
            </div>
        </div>

        <!-- GRUP 2: DATA PESERTA -->
        <?php 
        $isPesertaActive = in_array($currentScript, ['siswa.php', 'import-siswa.php', 'import-excel.php']); 
        ?>
        <div class="sidebar-nav-group <?= $isPesertaActive ? 'is-open' : '' ?>">
            <button type="button" class="sidebar-group-header <?= $isPesertaActive ? 'has-active' : '' ?>" onclick="toggleSidebarGroup(this)">
                <span class="group-header-left">
                    <i class="group-icon bi bi-mortarboard"></i>
                    <span class="group-title">Data Peserta</span>
                </span>
                <i class="group-arrow bi bi-caret-down-fill"></i>
            </button>
            <div class="sidebar-group-sub">
                <div class="sidebar-tree-line"></div>
                <a href="/admin/siswa.php" class="sidebar-sub-item <?= $currentScript === 'siswa.php' ? 'active' : '' ?>">
                    <span class="sidebar-sub-bullet"></span>
                    <span class="sidebar-sub-text">Kelola Data Siswa</span>
                </a>
                <a href="/admin/import-siswa.php" class="sidebar-sub-item <?= ($currentScript === 'import-siswa.php' || $currentScript === 'import-excel.php') ? 'active' : '' ?>">
                    <span class="sidebar-sub-bullet"></span>
                    <span class="sidebar-sub-text">Impor Data Excel</span>
                </a>
            </div>
        </div>

        <!-- GRUP 3: REKAP DAN LAPORAN -->
        <?php 
        $isRekapActive = in_array($currentScript, ['rekap-kehadiran.php', 'absensi.php', 'berita-acara.php', 'rekonsiliasi.php']); 
        ?>
        <div class="sidebar-nav-group <?= $isRekapActive ? 'is-open' : '' ?>">
            <button type="button" class="sidebar-group-header <?= $isRekapActive ? 'has-active' : '' ?>" onclick="toggleSidebarGroup(this)">
                <span class="group-header-left">
                    <i class="group-icon bi bi-bar-chart-line"></i>
                    <span class="group-title">Rekap dan Laporan</span>
                </span>
                <i class="group-arrow bi bi-caret-down-fill"></i>
            </button>
            <div class="sidebar-group-sub">
                <div class="sidebar-tree-line"></div>
                <a href="/admin/rekap-kehadiran.php" class="sidebar-sub-item <?= $currentScript === 'rekap-kehadiran.php' ? 'active' : '' ?>">
                    <span class="sidebar-sub-bullet"></span>
                    <span class="sidebar-sub-text">Rekap Kehadiran</span>
                </a>
                <a href="/admin/absensi.php" class="sidebar-sub-item <?= $currentScript === 'absensi.php' ? 'active' : '' ?>">
                    <span class="sidebar-sub-bullet"></span>
                    <span class="sidebar-sub-text">Arsip Daftar Hadir</span>
                </a>
                <a href="/admin/berita-acara.php" class="sidebar-sub-item <?= $currentScript === 'berita-acara.php' ? 'active' : '' ?>">
                    <span class="sidebar-sub-bullet"></span>
                    <span class="sidebar-sub-text">Arsip Berita Acara</span>
                </a>
                <a href="/admin/rekonsiliasi.php" class="sidebar-sub-item <?= $currentScript === 'rekonsiliasi.php' ? 'active' : '' ?>">
                    <span class="sidebar-sub-bullet"></span>
                    <span class="sidebar-sub-text">Rekonsiliasi Dokumen</span>
                </a>
            </div>
        </div>

        <!-- GRUP 4: PENGATURAN SISTEM -->
        <?php if (is_admin()): ?>
            <?php 
            $isPengaturanActive = in_array($currentScript, ['template-ujian.php', 'pengaturan.php', 'users.php']); 
            ?>
            <div class="sidebar-nav-group <?= $isPengaturanActive ? 'is-open' : '' ?>">
                <button type="button" class="sidebar-group-header <?= $isPengaturanActive ? 'has-active' : '' ?>" onclick="toggleSidebarGroup(this)">
                    <span class="group-header-left">
                        <i class="group-icon bi bi-sliders"></i>
                        <span class="group-title">Pengaturan</span>
                    </span>
                    <i class="group-arrow bi bi-caret-down-fill"></i>
                </button>
                <div class="sidebar-group-sub">
                    <div class="sidebar-tree-line"></div>
                    <a href="/admin/template-ujian.php" class="sidebar-sub-item <?= $currentScript === 'template-ujian.php' ? 'active' : '' ?>">
                        <span class="sidebar-sub-bullet"></span>
                        <span class="sidebar-sub-text">Template Ujian</span>
                    </a>
                    <a href="/admin/pengaturan.php" class="sidebar-sub-item <?= $currentScript === 'pengaturan.php' ? 'active' : '' ?>">
                        <span class="sidebar-sub-bullet"></span>
                        <span class="sidebar-sub-text">Pengaturan Kop & Drive</span>
                    </a>
                    <a href="/admin/users.php" class="sidebar-sub-item <?= $currentScript === 'users.php' ? 'active' : '' ?>">
                        <span class="sidebar-sub-bullet"></span>
                        <span class="sidebar-sub-text">Manajemen User</span>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </aside>

    <main class="admin-main">
        <?= render_flash() ?>
