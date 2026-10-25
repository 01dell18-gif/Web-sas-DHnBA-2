<?php
require_once __DIR__ . '/../config/functions.php';
require_admin();

$namaSekolah = get_setting('nama_sekolah', 'SMK NEGERI FYJ Channel');
$logoPath = get_setting('logo_path', 'storage/uploads/logo/logo_default.png');
$currentScript = basename($_SERVER['PHP_SELF']);
$pageTitle = $pageTitle ?? 'Panel Administrator';
$currentUser = get_logged_in_user();
$userAvatarUrl = get_user_avatar($currentUser);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - <?= htmlspecialchars($namaSekolah) ?></title>
    <link rel="stylesheet" href="/assets/css/app.css?v=<?= file_exists(__DIR__ . '/../assets/css/app.css') ? filemtime(__DIR__ . '/../assets/css/app.css') : time() ?>">
    <link rel="icon" type="image/png" href="/<?= htmlspecialchars($logoPath) ?>">
    <script>
    // Early synchronous handlers for accordion and sidebar collapse
    window.toggleSidebarGroup = function(button, event) {
        if (event) {
            try { event.preventDefault(); event.stopPropagation(); } catch (e) {}
        }
        var btn = button || this;
        if (!btn) return false;
        var group = btn.closest('.sidebar-nav-group');
        if (group) {
            group.classList.toggle('is-open');
        }
        return false;
    };

    window.toggleAdminSidebar = function(event) {
        if (event) {
            try { event.preventDefault(); event.stopPropagation(); } catch (e) {}
        }
        if (window.innerWidth <= 900) {
            document.body.classList.toggle('mobile-sidebar-open');
        } else {
            document.body.classList.toggle('sidebar-collapsed');
            try {
                var isCollapsed = document.body.classList.contains('sidebar-collapsed');
                localStorage.setItem('admin_sidebar_collapsed', isCollapsed ? 'true' : 'false');
            } catch (err) {}
        }
        return false;
    };

    window.hapusCacheWeb = function(event) {
        if (event) {
            try { event.preventDefault(); event.stopPropagation(); } catch (e) {}
        }
        var btn = document.getElementById('btnHapusCacheWeb');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="bi bi-arrow-repeat" style="display:inline-block; animation: spin 0.75s linear infinite;"></i>';
        }

        try {
            sessionStorage.clear();
            var preserveKeys = ['admin_sidebar_collapsed'];
            for (var i = localStorage.length - 1; i >= 0; i--) {
                var key = localStorage.key(i);
                if (key && preserveKeys.indexOf(key) === -1) {
                    localStorage.removeItem(key);
                }
            }
        } catch (err) {}

        if ('caches' in window) {
            try {
                caches.keys().then(function(names) {
                    names.forEach(function(name) { caches.delete(name); });
                });
            } catch (err) {}
        }

        fetch('/api/clear-cache.php', { method: 'POST', cache: 'no-store' })
            .catch(function() {})
            .finally(function() {
                var sep = window.location.href.indexOf('?') !== -1 ? '&' : '?';
                var cleanUrl = window.location.href.replace(/([?&])_t=\d+/, '');
                window.location.href = cleanUrl + sep + '_t=' + Date.now();
            });
    };

    // Global event delegation for reliable click handling
    document.addEventListener('DOMContentLoaded', function() {
        document.addEventListener('click', function(e) {
            var header = e.target.closest('.sidebar-group-header');
            if (header) {
                e.preventDefault();
                var group = header.closest('.sidebar-nav-group');
                if (group) {
                    group.classList.toggle('is-open');
                }
            }
        });
    });
    </script>
    <style>
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    </style>
</head>
<body>

<header class="navbar admin-navbar">
    <div class="admin-navbar-left">
        <!-- Brand Header Section: Sejajar persis dengan Sidebar (240px) -->
        <div class="admin-brand-box">
            <a href="/admin/index.php" class="brand-link">
                <img src="/<?= htmlspecialchars($logoPath) ?>" alt="Logo Sekolah" class="navbar-logo">
                <div class="brand-text">
                    <span class="brand-title"><?= htmlspecialchars($namaSekolah) ?></span>
                    <span class="brand-subtitle">Admin Panel Administrasi</span>
                </div>
            </a>
        </div>

        <!-- Tombol Hamburger / Titik Tiga Toggle Sidebar di Navbar -->
        <div class="admin-header-actions">
            <button type="button" class="btn-sidebar-toggle" id="btnToggleSidebar" onclick="toggleAdminSidebar(event)" aria-label="Toggle Sidebar" title="Sembunyikan / Tampilkan Menu">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>
        </div>
    </div>
    <div class="admin-navbar-right">
        <a href="/admin/profil.php" class="user-profile-chip admin-welcome-text" title="Buka Profil Akun" style="text-decoration: none; cursor: pointer;">
            <div class="user-avatar-icon" style="overflow: hidden; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: #e0e7ff; flex-shrink: 0;">
                <?php if ($userAvatarUrl): ?>
                    <img src="<?= htmlspecialchars($userAvatarUrl) ?>?v=<?= time() ?>" alt="Foto Profil" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                <?php else: ?>
                    <i class="bi bi-person-fill" style="color: #4f46e5; font-size: 1.15rem;"></i>
                <?php endif; ?>
            </div>
            <div class="user-info-text">
                <span class="user-name"><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Administrator') ?></span>
                <span class="<?= is_admin() ? 'badge-role-admin' : 'badge-role-operator' ?>">
                    <?= is_admin() ? '👑 Admin' : '🛡️ Operator' ?>
                </span>
            </div>
        </a>
        <button type="button" class="btn btn-secondary btn-sm" id="btnHapusCacheWeb" onclick="hapusCacheWeb(event)" title="Hapus Cache Web & Segarkan Halaman" aria-label="Hapus Cache Web" style="padding: 0.45rem 0.65rem; border-radius: var(--radius-sm); display: inline-flex; align-items: center; justify-content: center; cursor: pointer;">
            <i class="bi bi-trash3"></i>
        </button>
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

        <!-- GRUP 1: DATA MASTER (Disatukan dengan Kelola Siswa & Impor Excel) -->
        <?php 
        $isMasterActive = in_array($currentScript, ['kelas.php', 'siswa.php', 'guru.php', 'mapel.php', 'import-siswa.php', 'import-excel.php']); 
        ?>
        <div class="sidebar-nav-group <?= $isMasterActive ? 'is-open' : '' ?>">
            <button type="button" class="sidebar-group-header <?= $isMasterActive ? 'has-active' : '' ?>" onclick="toggleSidebarGroup(this, event)">
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
                <a href="/admin/siswa.php" class="sidebar-sub-item <?= $currentScript === 'siswa.php' ? 'active' : '' ?>">
                    <span class="sidebar-sub-bullet"></span>
                    <span class="sidebar-sub-text">Kelola Data Siswa</span>
                </a>
                <a href="/admin/guru.php" class="sidebar-sub-item <?= $currentScript === 'guru.php' ? 'active' : '' ?>">
                    <span class="sidebar-sub-bullet"></span>
                    <span class="sidebar-sub-text">Data Guru & Pengawas</span>
                </a>
                <a href="/admin/mapel.php" class="sidebar-sub-item <?= $currentScript === 'mapel.php' ? 'active' : '' ?>">
                    <span class="sidebar-sub-bullet"></span>
                    <span class="sidebar-sub-text">Data Mapel</span>
                </a>
                <a href="/admin/import-siswa.php" class="sidebar-sub-item <?= ($currentScript === 'import-siswa.php' || $currentScript === 'import-excel.php') ? 'active' : '' ?>">
                    <span class="sidebar-sub-bullet"></span>
                    <span class="sidebar-sub-text">Impor Data Excel</span>
                </a>
            </div>
        </div>

        <!-- GRUP 2: REKAP DAN LAPORAN -->
        <?php 
        $isRekapActive = in_array($currentScript, ['rekap-kehadiran.php', 'absensi.php', 'berita-acara.php', 'rekonsiliasi.php']); 
        ?>
        <div class="sidebar-nav-group <?= $isRekapActive ? 'is-open' : '' ?>">
            <button type="button" class="sidebar-group-header <?= $isRekapActive ? 'has-active' : '' ?>" onclick="toggleSidebarGroup(this, event)">
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

        <!-- GRUP 3: PENGATURAN SISTEM (ADMIN) -->
        <?php if (is_admin()): ?>
            <?php 
            $isPengaturanActive = in_array($currentScript, ['template-ujian.php', 'pengaturan.php', 'users.php', 'profil.php', 'password.php']); 
            ?>
            <div class="sidebar-nav-group <?= $isPengaturanActive ? 'is-open' : '' ?>">
                <button type="button" class="sidebar-group-header <?= $isPengaturanActive ? 'has-active' : '' ?>" onclick="toggleSidebarGroup(this, event)">
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
                    <a href="/admin/profil.php" class="sidebar-sub-item <?= in_array($currentScript, ['profil.php', 'password.php']) ? 'active' : '' ?>">
                        <span class="sidebar-sub-bullet"></span>
                        <span class="sidebar-sub-text">Profil</span>
                    </a>
                </div>
            </div>
        <?php else: ?>
            <!-- Menu Akun untuk Operator -->
            <a href="/admin/profil.php" class="admin-menu-item <?= in_array($currentScript, ['profil.php', 'password.php']) ? 'active' : '' ?>" style="margin-top: 0.5rem;">
                <i class="bi bi-person-badge-fill"></i> <span>Profil</span>
            </a>
        <?php endif; ?>
    </aside>

    <main class="admin-main">
        <?= render_flash() ?>
