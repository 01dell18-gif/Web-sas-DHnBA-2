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
    <div style="display: flex; align-items: center;">
        <button type="button" class="admin-mobile-toggle" id="btnToggleAdminMenu" onclick="toggleAdminMenu(event)" aria-label="Toggle Menu Admin">
            <i class="bi bi-list"></i>
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
        <span class="admin-welcome-text" style="font-size: 0.85rem; color: var(--slate-600);">
            Halo, <strong><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Administrator SAS SMKN 1 Klungkung') ?></strong>
        </span>
        <a href="/" target="_blank" class="btn btn-secondary btn-sm" title="Buka Halaman Publik">
            <i class="bi bi-globe2"></i> Web Publik
        </a>
        <a href="/admin/logout.php" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin keluar dari sistem?')">
            <i class="bi bi-box-arrow-right"></i> Keluar
        </a>
    </div>
</header>

<div class="admin-sidebar-layout">
    <aside class="admin-sidebar">
        <a href="/admin/index.php" class="admin-menu-item <?= $currentScript === 'index.php' ? 'active' : '' ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a href="/admin/kelas.php" class="admin-menu-item <?= $currentScript === 'kelas.php' ? 'active' : '' ?>">
            <i class="bi bi-mortarboard"></i> Data Kelas
        </a>
        <a href="/admin/siswa.php" class="admin-menu-item <?= $currentScript === 'siswa.php' ? 'active' : '' ?>">
            <i class="bi bi-person-lines-fill"></i> Data Siswa
        </a>
        <a href="/admin/import-siswa.php" class="admin-menu-item <?= $currentScript === 'import-siswa.php' ? 'active' : '' ?>">
            <i class="bi bi-file-earmark-spreadsheet"></i> Impor Excel / CSV
        </a>
        <a href="/admin/rekap-kehadiran.php" class="admin-menu-item <?= $currentScript === 'rekap-kehadiran.php' ? 'active' : '' ?>">
            <i class="bi bi-card-checklist"></i> Rekap Kehadiran Siswa
        </a>
        <a href="/admin/absensi.php" class="admin-menu-item <?= $currentScript === 'absensi.php' ? 'active' : '' ?>">
            <i class="bi bi-file-earmark-check"></i> Arsip Daftar Hadir
        </a>
        <a href="/admin/berita-acara.php" class="admin-menu-item <?= $currentScript === 'berita-acara.php' ? 'active' : '' ?>">
            <i class="bi bi-archive"></i> Arsip Berita Acara
        </a>
        <a href="/admin/rekonsiliasi.php" class="admin-menu-item <?= $currentScript === 'rekonsiliasi.php' ? 'active' : '' ?>">
            <i class="bi bi-shield-check"></i> Rekonsiliasi Dokumen
        </a>
        <a href="/admin/template-ujian.php" class="admin-menu-item <?= $currentScript === 'template-ujian.php' ? 'active' : '' ?>">
            <i class="bi bi-file-earmark-richtext"></i> Template Ujian
        </a>
        <a href="/admin/pengaturan.php" class="admin-menu-item <?= $currentScript === 'pengaturan.php' ? 'active' : '' ?>">
            <i class="bi bi-sliders"></i> Pengaturan Kop & Drive
        </a>
        <a href="/admin/password.php" class="admin-menu-item <?= $currentScript === 'password.php' ? 'active' : '' ?>">
            <i class="bi bi-key"></i> Ganti Password
        </a>
    </aside>

    <main class="admin-main">
        <?= render_flash() ?>
