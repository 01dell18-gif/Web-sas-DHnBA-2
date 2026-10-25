<?php
/**
 * Arsip Dokumen Ujian (Pelihat Pengawas)
 * Menampilkan rekapitulasi Daftar Hadir dan Berita Acara yang telah tersimpan di Database
 * Generator Absensi dan Berita Acara Ujian - SMK Negeri 1 Klungkung
 */

require_once __DIR__ . '/config/functions.php';

// Handle View Single PDF (Inline Streaming untuk Pengawas)
if (isset($_GET['action']) && $_GET['action'] === 'view') {
    $type = $_GET['type'] ?? 'absensi';
    $id = (int)($_GET['id'] ?? 0);

    if (in_array($type, ['absensi', 'dh'])) {
        $rec = dbFetch("SELECT * FROM absensi WHERE id = :id", ['id' => $id]);
        if ($rec) {
            $fullPath = !empty($rec['pdf_path']) ? BASE_PATH . '/' . $rec['pdf_path'] : '';
            if (empty($fullPath) || !file_exists($fullPath)) {
                $fullPath = generate_absensi_pdf_file($rec['id']);
            }
            if ($fullPath && file_exists($fullPath)) {
                while (ob_get_level()) { ob_end_clean(); }
                header_remove('Pragma');
                header_remove('Expires');
                header('Content-Type: application/pdf');
                header('Content-Disposition: inline; filename="' . basename($fullPath) . '"');
                header('Content-Transfer-Encoding: binary');
                header('Accept-Ranges: bytes');
                header('Cache-Control: private, max-age=0, must-revalidate');
                header('Content-Length: ' . filesize($fullPath));
                readfile($fullPath);
                exit;
            }
        }
    } elseif (in_array($type, ['berita_acara', 'berita-acara', 'ba'])) {
        $rec = dbFetch("SELECT * FROM berita_acara WHERE id = :id", ['id' => $id]);
        if ($rec) {
            $fullPath = !empty($rec['pdf_path']) ? BASE_PATH . '/' . $rec['pdf_path'] : '';
            if (empty($fullPath) || !file_exists($fullPath)) {
                $fullPath = generate_berita_acara_pdf_file($rec['id']);
            }
            if ($fullPath && file_exists($fullPath)) {
                while (ob_get_level()) { ob_end_clean(); }
                header_remove('Pragma');
                header_remove('Expires');
                header('Content-Type: application/pdf');
                header('Content-Disposition: inline; filename="' . basename($fullPath) . '"');
                header('Content-Transfer-Encoding: binary');
                header('Accept-Ranges: bytes');
                header('Cache-Control: private, max-age=0, must-revalidate');
                header('Content-Length: ' . filesize($fullPath));
                readfile($fullPath);
                exit;
            }
        }
    }

    // Jika berkas tidak ditemukan
    http_response_code(404);
    die("Dokumen tidak ditemukan atau belum tersimpan di database.");
}

// Handle Download Single PDF
if (isset($_GET['action']) && $_GET['action'] === 'download') {
    $type = $_GET['type'] ?? 'absensi';
    $id = (int)($_GET['id'] ?? 0);

    if (in_array($type, ['absensi', 'dh'])) {
        $rec = dbFetch("SELECT * FROM absensi WHERE id = :id", ['id' => $id]);
        if ($rec) {
            $fullPath = !empty($rec['pdf_path']) ? BASE_PATH . '/' . $rec['pdf_path'] : '';
            if (empty($fullPath) || !file_exists($fullPath)) {
                $fullPath = generate_absensi_pdf_file($rec['id']);
            }
            if ($fullPath && file_exists($fullPath)) {
                while (ob_get_level()) { ob_end_clean(); }
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="' . basename($fullPath) . '"');
                header('Content-Length: ' . filesize($fullPath));
                readfile($fullPath);
                exit;
            }
        }
    } elseif (in_array($type, ['berita_acara', 'berita-acara', 'ba'])) {
        $rec = dbFetch("SELECT * FROM berita_acara WHERE id = :id", ['id' => $id]);
        if ($rec) {
            $fullPath = !empty($rec['pdf_path']) ? BASE_PATH . '/' . $rec['pdf_path'] : '';
            if (empty($fullPath) || !file_exists($fullPath)) {
                $fullPath = generate_berita_acara_pdf_file($rec['id']);
            }
            if ($fullPath && file_exists($fullPath)) {
                while (ob_get_level()) { ob_end_clean(); }
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="' . basename($fullPath) . '"');
                header('Content-Length: ' . filesize($fullPath));
                readfile($fullPath);
                exit;
            }
        }
    }

    http_response_code(404);
    die("Dokumen tidak ditemukan.");
}

// Data Header Sekolah
$namaSekolah = get_setting('nama_sekolah', 'SMK NEGERI FYJ Channel');
$logoPath = get_setting('logo_path', 'storage/uploads/logo/logo_default.png');

// Ambil seluruh data absensi dan berita acara dari Database
$allDH = dbFetchAll("SELECT * FROM absensi ORDER BY tanggal_ujian DESC, id DESC");
$allBA = dbFetchAll("SELECT * FROM berita_acara ORDER BY tanggal_ujian DESC, id DESC");
$allKelas = dbFetchAll("SELECT * FROM kelas WHERE aktif = 1 ORDER BY tingkat ASC, nama_kelas ASC");

// Kelompokkan sesi berdasarkan tanggal_ujian || class_id || LOWER(TRIM(mata_pelajaran))
$sessions = [];
$dateOptions = [];

foreach ($allDH as $dh) {
    $dateOptions[$dh['tanggal_ujian']] = true;
    $mpClean = strtolower(trim($dh['mata_pelajaran']));
    $key = $dh['tanggal_ujian'] . '||' . $dh['class_id'] . '||' . $mpClean;
    if (!isset($sessions[$key])) {
        $sessions[$key] = [
            'tanggal_ujian' => $dh['tanggal_ujian'],
            'class_id' => $dh['class_id'],
            'nama_kelas' => $dh['nama_kelas'],
            'tingkat' => $dh['tingkat'],
            'mata_pelajaran' => $dh['mata_pelajaran'],
            'ruang' => $dh['ruang'] ?? '',
            'sesi' => $dh['sesi'] ?? '',
            'dh' => [],
            'ba' => []
        ];
    }
    if (empty($sessions[$key]['ruang']) && !empty($dh['ruang'])) {
        $sessions[$key]['ruang'] = $dh['ruang'];
    }
    $sessions[$key]['dh'][] = $dh;
}

foreach ($allBA as $ba) {
    $dateOptions[$ba['tanggal_ujian']] = true;
    $mpClean = strtolower(trim($ba['mata_pelajaran']));
    $key = $ba['tanggal_ujian'] . '||' . $ba['class_id'] . '||' . $mpClean;
    if (!isset($sessions[$key])) {
        $sessions[$key] = [
            'tanggal_ujian' => $ba['tanggal_ujian'],
            'class_id' => $ba['class_id'],
            'nama_kelas' => $ba['nama_kelas'],
            'tingkat' => $ba['tingkat'],
            'mata_pelajaran' => $ba['mata_pelajaran'],
            'ruang' => $ba['ruang'] ?? '',
            'sesi' => '',
            'dh' => [],
            'ba' => []
        ];
    }
    if (empty($sessions[$key]['ruang']) && !empty($ba['ruang'])) {
        $sessions[$key]['ruang'] = $ba['ruang'];
    }
    $sessions[$key]['ba'][] = $ba;
}

// Urutkan opsi tanggal menurun
krsort($dateOptions);

// Analisis Status untuk Setiap Sesi
$totalSessions = count($sessions);
$countComplete = 0;
$countMissingBA = 0;
$countMissingDH = 0;
$countDuplicate = 0;

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

$countIncomplete = $countMissingBA + $countMissingDH + $countDuplicate;

// Filter Request Parameters
$filterSearch = trim($_GET['q'] ?? '');
$filterTanggal = trim($_GET['tanggal'] ?? '');
$filterTingkat = trim($_GET['tingkat'] ?? '');
$filterClassId = (int)($_GET['class_id'] ?? 0);
$filterStatus = trim($_GET['status'] ?? 'all');

// Terapkan Filter
$filteredSessions = [];

foreach ($sessions as $key => $sess) {
    // Filter Pencarian Teks
    if ($filterSearch !== '') {
        $searchHaystack = strtolower(
            $sess['mata_pelajaran'] . ' ' . 
            $sess['nama_kelas'] . ' ' . 
            $sess['ruang'] . ' ' . 
            $sess['tanggal_ujian']
        );
        // Tambahkan nama pengawas dari DH dan BA
        foreach ($sess['dh'] as $d) {
            $searchHaystack .= ' ' . strtolower($d['nama_pengawas_1'] . ' ' . ($d['nama_pengawas_2'] ?? ''));
        }
        foreach ($sess['ba'] as $b) {
            $searchHaystack .= ' ' . strtolower($b['nama_pengawas_1'] . ' ' . ($b['nama_pengawas_2'] ?? ''));
        }
        if (strpos($searchHaystack, strtolower($filterSearch)) === false) {
            continue;
        }
    }

    // Filter Tanggal
    if ($filterTanggal !== '' && $sess['tanggal_ujian'] !== $filterTanggal) {
        continue;
    }

    // Filter Tingkat
    if ($filterTingkat !== '' && $sess['tingkat'] !== $filterTingkat) {
        continue;
    }

    // Filter Kelas
    if ($filterClassId > 0 && (int)$sess['class_id'] !== $filterClassId) {
        continue;
    }

    // Filter Status Kelengkapan
    if ($filterStatus === 'complete' && $sess['status'] !== 'complete') {
        continue;
    } elseif ($filterStatus === 'missing_ba' && $sess['status'] !== 'missing_ba') {
        continue;
    } elseif ($filterStatus === 'missing_dh' && $sess['status'] !== 'missing_dh') {
        continue;
    } elseif ($filterStatus === 'mismatch' && $sess['status'] === 'complete') {
        continue;
    } elseif ($filterStatus === 'duplicate' && $sess['status'] !== 'duplicate') {
        continue;
    }

    $filteredSessions[] = $sess;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arsip Dokumen Ujian - <?= htmlspecialchars($namaSekolah) ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
    <link rel="icon" type="image/png" href="/<?= htmlspecialchars($logoPath) ?>">
    <style>
        .arsip-header-banner {
            background: linear-gradient(135deg, #1e1b4b 0%, #312e81 40%, #4338ca 100%);
            color: #ffffff;
            padding: 2.25rem 1.25rem 2.5rem 1.25rem;
            border-bottom: 3px solid #4f46e5;
            position: relative;
            overflow: hidden;
        }
        .arsip-header-banner::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 250px;
            height: 250px;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.25) 0%, rgba(99, 102, 241, 0) 70%);
            border-radius: 50%;
            pointer-events: none;
        }
        .arsip-container {
            max-width: 1240px;
            margin: -1.75rem auto 3rem auto;
            padding: 0 1rem;
        }
        
        /* Stats Cards */
        .stats-summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1rem;
            margin-bottom: 1.25rem;
        }
        .stat-box {
            background: #ffffff;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            padding: 1.15rem 1.25rem;
            box-shadow: var(--shadow-sm);
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            position: relative;
            overflow: hidden;
            text-decoration: none;
            color: inherit;
        }
        .stat-box:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        .stat-box-accent {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
        }
        .stat-icon-wrap {
            width: 48px;
            height: 48px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            flex-shrink: 0;
        }
        .stat-number {
            font-size: 1.55rem;
            font-weight: 800;
            line-height: 1.2;
            color: var(--text-heading);
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .stat-title {
            font-size: 0.785rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Filter Section Card */
        .filter-panel-card {
            background: #ffffff;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            padding: 1.25rem;
            box-shadow: var(--shadow-sm);
            margin-bottom: 1.25rem;
        }
        .filter-grid {
            display: grid;
            grid-template-columns: 2fr 1.2fr 1fr 1.2fr 1.2fr auto;
            gap: 0.75rem;
            align-items: flex-end;
        }
        @media (max-width: 992px) {
            .filter-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
        @media (max-width: 576px) {
            .filter-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Status Pills */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: var(--radius-pill);
            font-size: 0.76rem;
            font-weight: 700;
            letter-spacing: 0.2px;
            white-space: nowrap;
        }
        .status-complete {
            background: #dcfce7;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }
        .status-missing-ba {
            background: #fef3c7;
            color: #b45309;
            border: 1px solid #fde68a;
        }
        .status-missing-dh {
            background: #e0f2fe;
            color: #0369a1;
            border: 1px solid #bae6fd;
        }
        .status-duplicate {
            background: #f3e8ff;
            color: #7e22ce;
            border: 1px solid #e9d5ff;
        }

        /* Action Buttons */
        .btn-view-doc {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: var(--radius-sm);
            font-size: 0.785rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.18s ease;
        }
        .btn-view-dh {
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
        }
        .btn-view-dh:hover {
            background: #dbeafe;
            color: #1e40af;
            transform: translateY(-1px);
        }
        .btn-view-ba {
            background: #fffbeb;
            color: #b45309;
            border: 1px solid #fde68a;
        }
        .btn-view-ba:hover {
            background: #fef3c7;
            color: #92400e;
            transform: translateY(-1px);
        }

        .table-arsip {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: #ffffff;
        }
        .table-arsip th {
            background: #f8fafc;
            color: #475569;
            font-size: 0.775rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 0.85rem 0.95rem;
            border-bottom: 2px solid var(--border);
            white-space: nowrap;
        }
        .table-arsip td {
            padding: 0.85rem 0.95rem;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
            font-size: 0.84rem;
        }
        .table-arsip tbody tr:hover {
            background-color: #f8faff;
        }

        .badge-kelas {
            background: #f1f5f9;
            color: #1e293b;
            font-weight: 700;
            font-size: 0.775rem;
            padding: 3px 8px;
            border-radius: var(--radius-xs);
            border: 1px solid #e2e8f0;
            white-space: nowrap;
        }

        /* Mobile Card View */
        .mobile-session-card {
            display: none;
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 1rem;
            margin-bottom: 0.85rem;
            box-shadow: var(--shadow-sm);
        }
        @media (max-width: 860px) {
            .table-desktop-wrap {
                display: none;
            }
            .mobile-session-card {
                display: block;
            }
        }
    </style>
</head>
<body>

<!-- Header Navbar Public -->
<header class="navbar">
    <div class="navbar-brand">
        <a href="/" class="brand-link">
            <img src="/<?= htmlspecialchars($logoPath) ?>" alt="Logo Sekolah" class="navbar-logo">
            <div class="brand-text">
                <span class="brand-title"><?= htmlspecialchars($namaSekolah) ?></span>
                <span class="brand-subtitle">Generator Administrasi Ujian</span>
            </div>
        </a>
    </div>
    <button type="button" class="mobile-nav-toggle btn-sidebar-toggle" id="btnToggleNav" onclick="toggleMobileNav(event)" aria-label="Toggle Menu">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <line x1="3" y1="12" x2="21" y2="12"></line>
            <line x1="3" y1="18" x2="21" y2="18"></line>
        </svg>
    </button>
    <ul class="nav-links" id="navLinks">
        <li><a href="/" class="nav-link"><i class="bi bi-house"></i> Beranda</a></li>
        <li><a href="/absensi.php" class="nav-link"><i class="bi bi-clipboard-check"></i> Daftar Hadir</a></li>
        <li><a href="/berita-acara.php" class="nav-link"><i class="bi bi-file-earmark-text"></i> Berita Acara</a></li>
        <li><a href="/arsip.php" class="nav-link active"><i class="bi bi-archive"></i> Arsip Dokumen</a></li>
        <li><a href="/admin/login.php" class="btn btn-secondary btn-sm"><i class="bi bi-shield-lock"></i> Admin</a></li>
    </ul>
</header>

<!-- Hero Section -->
<section class="arsip-header-banner">
    <div class="container" style="max-width: 1240px; position: relative; z-index: 2;">
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
            <div>
                <span style="display: inline-flex; align-items: center; gap: 6px; background: rgba(255, 255, 255, 0.15); backdrop-filter: blur(8px); padding: 4px 12px; border-radius: 9999px; font-size: 0.775rem; font-weight: 600; color: #e0e7ff; margin-bottom: 0.65rem;">
                    <i class="bi bi-eye-fill"></i> Pelihat Dokumen Pengawas &bull; Database Server SAS
                </span>
                <h1 style="color: #ffffff; font-size: 1.75rem; font-weight: 800; margin-bottom: 0.35rem; letter-spacing: -0.02em;">
                    Arsip Dokumen Ujian
                </h1>
                <p style="color: #c7d2fe; font-size: 0.92rem; max-width: 760px; line-height: 1.5;">
                    Periksa dan pastikan lembar Daftar Hadir serta Berita Acara yang telah diisi sudah tersimpan aman di database sekolah. Pengawas dapat langsung melihat pratinjau PDF berkas resmi.
                </p>
            </div>
            <div style="display: flex; gap: 0.65rem; align-items: center; flex-wrap: wrap;">
                <a href="/absensi.php" class="btn btn-secondary btn-sm" style="background: rgba(255,255,255,0.15); color: #ffffff; border-color: rgba(255,255,255,0.25);">
                    <i class="bi bi-plus-lg"></i> Isi Daftar Hadir
                </a>
                <a href="/berita-acara.php" class="btn btn-secondary btn-sm" style="background: rgba(255,255,255,0.15); color: #ffffff; border-color: rgba(255,255,255,0.25);">
                    <i class="bi bi-plus-lg"></i> Isi Berita Acara
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Main Container -->
<main class="arsip-container">

    <!-- Ringkasan Statistik Dokumen Tersimpan -->
    <div class="stats-summary-grid">
        <a href="?status=all" class="stat-box" title="Lihat Semua Sesi Ujian">
            <div class="stat-box-accent" style="background: linear-gradient(90deg, #4f46e5, #6366f1);"></div>
            <div class="stat-icon-wrap" style="background: #eef2ff; color: #4f46e5;">
                <i class="bi bi-collection-fill"></i>
            </div>
            <div>
                <div class="stat-number"><?= $totalSessions ?></div>
                <div class="stat-title">Total Sesi Ujian</div>
            </div>
        </a>

        <a href="?status=complete" class="stat-box" title="Lihat Sesi Lengkap (DH & BA)">
            <div class="stat-box-accent" style="background: linear-gradient(90deg, #10b981, #34d399);"></div>
            <div class="stat-icon-wrap" style="background: #ecfdf5; color: #10b981;">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            <div>
                <div class="stat-number" style="color: #059669;"><?= $countComplete ?></div>
                <div class="stat-title">Lengkap (DH & BA)</div>
            </div>
        </a>

        <a href="?status=missing_ba" class="stat-box" title="Lihat Sesi yang Kurang Berita Acara">
            <div class="stat-box-accent" style="background: linear-gradient(90deg, #f59e0b, #fbbf24);"></div>
            <div class="stat-icon-wrap" style="background: #fffbeb; color: #f59e0b;">
                <i class="bi bi-file-earmark-text"></i>
            </div>
            <div>
                <div class="stat-number" style="color: #d97706;"><?= $countMissingBA ?></div>
                <div class="stat-title">Hanya Daftar Hadir</div>
            </div>
        </a>

        <a href="?status=missing_dh" class="stat-box" title="Lihat Sesi yang Kurang Daftar Hadir">
            <div class="stat-box-accent" style="background: linear-gradient(90deg, #0284c7, #38bdf8);"></div>
            <div class="stat-icon-wrap" style="background: #f0f9ff; color: #0284c7;">
                <i class="bi bi-clipboard-check"></i>
            </div>
            <div>
                <div class="stat-number" style="color: #0284c7;"><?= $countMissingDH ?></div>
                <div class="stat-title">Hanya Berita Acara</div>
            </div>
        </a>
    </div>

    <!-- Panel Filter & Pencarian -->
    <div class="filter-panel-card">
        <form method="GET" action="/arsip.php" id="formFilter">
            <div class="filter-grid">
                <!-- 1. Pencarian Kata Kunci -->
                <div>
                    <label class="form-label" style="font-size: 0.775rem; font-weight: 700; color: #475569;">Pencarian Sesi / Pengawas</label>
                    <div style="position: relative;">
                        <input type="text" name="q" class="form-control" style="font-size: 0.85rem; padding-left: 2rem;" 
                               placeholder="Mapel, Pengawas, Ruang..." value="<?= htmlspecialchars($filterSearch) ?>">
                        <i class="bi bi-search" style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: #94a3b8;"></i>
                    </div>
                </div>

                <!-- 2. Filter Tanggal Ujian -->
                <div>
                    <label class="form-label" style="font-size: 0.775rem; font-weight: 700; color: #475569;">Tanggal Ujian</label>
                    <select name="tanggal" class="form-select" style="font-size: 0.85rem;" onchange="document.getElementById('formFilter').submit();">
                        <option value="">Semua Tanggal</option>
                        <?php foreach (array_keys($dateOptions) as $dOpt): ?>
                            <option value="<?= $dOpt ?>" <?= $filterTanggal === $dOpt ? 'selected' : '' ?>>
                                <?= date('d/m/Y', strtotime($dOpt)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- 3. Filter Tingkat -->
                <div>
                    <label class="form-label" style="font-size: 0.775rem; font-weight: 700; color: #475569;">Tingkat</label>
                    <select name="tingkat" class="form-select" style="font-size: 0.85rem;" onchange="document.getElementById('formFilter').submit();">
                        <option value="">Semua</option>
                        <option value="X" <?= $filterTingkat === 'X' ? 'selected' : '' ?>>Kelas X</option>
                        <option value="XI" <?= $filterTingkat === 'XI' ? 'selected' : '' ?>>Kelas XI</option>
                        <option value="XII" <?= $filterTingkat === 'XII' ? 'selected' : '' ?>>Kelas XII</option>
                    </select>
                </div>

                <!-- 4. Filter Kelas Spesifik -->
                <div>
                    <label class="form-label" style="font-size: 0.775rem; font-weight: 700; color: #475569;">Kelas</label>
                    <select name="class_id" class="form-select" style="font-size: 0.85rem;" onchange="document.getElementById('formFilter').submit();">
                        <option value="0">Semua Kelas</option>
                        <?php foreach ($allKelas as $k): ?>
                            <option value="<?= $k['id'] ?>" <?= $filterClassId === (int)$k['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($k['nama_kelas']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- 5. Filter Status Dokumen -->
                <div>
                    <label class="form-label" style="font-size: 0.775rem; font-weight: 700; color: #475569;">Status Dokumen</label>
                    <select name="status" class="form-select" style="font-size: 0.85rem;" onchange="document.getElementById('formFilter').submit();">
                        <option value="all" <?= $filterStatus === 'all' ? 'selected' : '' ?>>Semua Status</option>
                        <option value="complete" <?= $filterStatus === 'complete' ? 'selected' : '' ?>>Lengkap (DH & BA)</option>
                        <option value="missing_ba" <?= $filterStatus === 'missing_ba' ? 'selected' : '' ?>>Hanya Daftar Hadir</option>
                        <option value="missing_dh" <?= $filterStatus === 'missing_dh' ? 'selected' : '' ?>>Hanya Berita Acara</option>
                        <option value="mismatch" <?= $filterStatus === 'mismatch' ? 'selected' : '' ?>>Belum Lengkap</option>
                        <option value="duplicate" <?= $filterStatus === 'duplicate' ? 'selected' : '' ?>>Duplikat Sesi</option>
                    </select>
                </div>

                <!-- 6. Tombol Aksi -->
                <div style="display: flex; gap: 0.4rem;">
                    <button type="submit" class="btn btn-primary" style="padding: 0.5rem 0.9rem; font-size: 0.85rem;">
                        <i class="bi bi-funnel-fill"></i> Terapkan
                    </button>
                    <?php if ($filterSearch !== '' || $filterTanggal !== '' || $filterTingkat !== '' || $filterClassId > 0 || $filterStatus !== 'all'): ?>
                        <a href="/arsip.php" class="btn btn-secondary" style="padding: 0.5rem 0.75rem; font-size: 0.85rem;" title="Reset Filter">
                            <i class="bi bi-x-circle"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>

    <!-- Informasi Hasil Filter -->
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.85rem; padding: 0 0.25rem;">
        <div style="font-weight: 700; color: var(--text-heading); font-size: 0.95rem;">
            <i class="bi bi-table" style="color: var(--primary);"></i> Rekapitulasi Berkas Dokumen Ujian
            <span class="badge" style="background: #e2e8f0; color: #334155; font-size: 0.75rem; font-weight: 700; margin-left: 6px;">
                <?= count($filteredSessions) ?> Sesi Ditemukan
            </span>
        </div>
        <div style="font-size: 0.8rem; color: var(--text-muted);">
            <i class="bi bi-database-check" style="color: #10b981;"></i> Tersinkronisasi dengan Database
        </div>
    </div>

    <!-- TAMPILAN DESKTOP: Tabel Modern & Responsif -->
    <div class="card table-desktop-wrap" style="border: 1px solid var(--border); box-shadow: var(--shadow-sm); overflow: hidden; margin-bottom: 2rem;">
        <div class="table-responsive">
            <table class="table-arsip">
                <thead>
                    <tr>
                        <th style="width: 44px; text-align: center;">No</th>
                        <th style="min-width: 110px;">Tanggal</th>
                        <th style="min-width: 100px;">Kelas / Ruang</th>
                        <th style="min-width: 200px;">Mata Pelajaran Ujian</th>
                        <th style="min-width: 150px; text-align: center;">Status Dokumen</th>
                        <th style="min-width: 200px;">Daftar Hadir (DH)</th>
                        <th style="min-width: 200px;">Berita Acara (BA)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($filteredSessions)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 3rem 1rem;">
                                <div style="font-size: 2.25rem; margin-bottom: 0.5rem;">🔍</div>
                                <div style="font-weight: 700; color: var(--text-heading); font-size: 1.05rem;">Tidak Ada Dokumen yang Cocok</div>
                                <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 4px;">
                                    Silakan ubah kata kunci atau bersihkan filter untuk menampilkan data lainnya.
                                </div>
                                <div style="margin-top: 1rem;">
                                    <a href="/arsip.php" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-counterclockwise"></i> Tampilkan Semua Sesi</a>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($filteredSessions as $idx => $s): ?>
                            <tr>
                                <td style="text-align: center; color: var(--text-muted); font-weight: 600;"><?= $idx + 1 ?></td>
                                <td>
                                    <div style="font-weight: 700; color: var(--text-heading);">
                                        <?= date('d/m/Y', strtotime($s['tanggal_ujian'])) ?>
                                    </div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">
                                        <?= date('l', strtotime($s['tanggal_ujian'])) ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge-kelas"><?= htmlspecialchars($s['nama_kelas']) ?></span>
                                    <?php if (!empty($s['ruang'])): ?>
                                        <div style="font-size: 0.74rem; color: var(--text-muted); margin-top: 3px;">
                                            <i class="bi bi-geo-alt"></i> <?= htmlspecialchars($s['ruang']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="font-weight: 700; color: var(--text-heading); font-size: 0.885rem;">
                                        <?= htmlspecialchars($s['mata_pelajaran']) ?>
                                    </div>
                                    <div style="font-size: 0.74rem; color: var(--text-muted);">
                                        Tingkat <?= htmlspecialchars($s['tingkat']) ?>
                                    </div>
                                </td>
                                <td style="text-align: center;">
                                    <?php if ($s['status'] === 'complete'): ?>
                                        <span class="status-badge status-complete">
                                            <i class="bi bi-check2-circle"></i> Lengkap (DH & BA)
                                        </span>
                                    <?php elseif ($s['status'] === 'missing_ba'): ?>
                                        <span class="status-badge status-missing-ba">
                                            <i class="bi bi-exclamation-circle"></i> Hanya Daftar Hadir
                                        </span>
                                    <?php elseif ($s['status'] === 'missing_dh'): ?>
                                        <span class="status-badge status-missing-dh">
                                            <i class="bi bi-info-circle"></i> Hanya Berita Acara
                                        </span>
                                    <?php elseif ($s['status'] === 'duplicate'): ?>
                                        <span class="status-badge status-duplicate">
                                            <i class="bi bi-copy"></i> Duplikat Sesi
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($s['dh'])): ?>
                                        <?php foreach ($s['dh'] as $d): ?>
                                            <div style="margin-bottom: 4px;">
                                                <a href="/arsip.php?action=view&type=absensi&id=<?= $d['id'] ?>" target="_blank" class="btn-view-doc btn-view-dh" title="Klik untuk Pratinjau PDF Daftar Hadir #<?= $d['id'] ?>">
                                                    <i class="bi bi-file-earmark-pdf-fill"></i>
                                                    <span>DH #<?= $d['id'] ?></span>
                                                    <span style="font-weight: normal; opacity: 0.85;">(<?= htmlspecialchars($d['nama_pengawas_1']) ?>)</span>
                                                </a>
                                                <div style="font-size: 0.72rem; color: #64748b; margin-left: 2px;">
                                                    Hadir: <?= $d['peserta_hadir'] ?>/<?= $d['total_peserta'] ?> siswa
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div style="color: #ef4444; font-size: 0.78rem; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
                                            <i class="bi bi-x-circle"></i> Belum ada Daftar Hadir
                                        </div>
                                        <div style="margin-top: 3px;">
                                            <a href="/absensi.php" target="_blank" class="btn btn-secondary btn-sm" style="font-size: 0.72rem; padding: 2px 7px;">
                                                <i class="bi bi-plus"></i> Buat DH
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($s['ba'])): ?>
                                        <?php foreach ($s['ba'] as $b): ?>
                                            <div style="margin-bottom: 4px;">
                                                <a href="/arsip.php?action=view&type=berita_acara&id=<?= $b['id'] ?>" target="_blank" class="btn-view-doc btn-view-ba" title="Klik untuk Pratinjau PDF Berita Acara #<?= $b['id'] ?>">
                                                    <i class="bi bi-file-earmark-text-fill"></i>
                                                    <span>BA #<?= $b['id'] ?></span>
                                                    <span style="font-weight: normal; opacity: 0.85;">(<?= htmlspecialchars($b['nama_pengawas_1']) ?>)</span>
                                                </a>
                                                <div style="font-size: 0.72rem; color: #64748b; margin-left: 2px;">
                                                    Jam: <?= htmlspecialchars($b['jam_mulai']) ?> - <?= htmlspecialchars($b['jam_selesai']) ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div style="color: #d97706; font-size: 0.78rem; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
                                            <i class="bi bi-x-circle"></i> Belum ada Berita Acara
                                        </div>
                                        <div style="margin-top: 3px;">
                                            <a href="/berita-acara.php" target="_blank" class="btn btn-secondary btn-sm" style="font-size: 0.72rem; padding: 2px 7px;">
                                                <i class="bi bi-plus"></i> Buat BA
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- TAMPILAN MOBILE: Kartu Per Sesi yang Rapi -->
    <div class="mobile-cards-wrap" style="margin-bottom: 2rem;">
        <?php if (empty($filteredSessions)): ?>
            <div class="mobile-session-card" style="text-align: center; padding: 2.5rem 1rem;">
                <div style="font-size: 2rem; margin-bottom: 0.5rem;">🔍</div>
                <div style="font-weight: 700; color: var(--text-heading);">Tidak Ada Dokumen yang Cocok</div>
                <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 4px;">
                    Ubah filter pencarian untuk menemukan sesi ujian lainnya.
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($filteredSessions as $idx => $s): ?>
                <div class="mobile-session-card">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                        <div>
                            <span class="badge-kelas"><?= htmlspecialchars($s['nama_kelas']) ?></span>
                            <span style="font-size: 0.75rem; color: var(--text-muted); margin-left: 4px;">
                                <?= date('d/m/Y', strtotime($s['tanggal_ujian'])) ?>
                            </span>
                        </div>
                        <div>
                            <?php if ($s['status'] === 'complete'): ?>
                                <span class="status-badge status-complete"><i class="bi bi-check2"></i> Lengkap</span>
                            <?php elseif ($s['status'] === 'missing_ba'): ?>
                                <span class="status-badge status-missing-ba"><i class="bi bi-exclamation"></i> Kurang BA</span>
                            <?php elseif ($s['status'] === 'missing_dh'): ?>
                                <span class="status-badge status-missing-dh"><i class="bi bi-info"></i> Kurang DH</span>
                            <?php elseif ($s['status'] === 'duplicate'): ?>
                                <span class="status-badge status-duplicate">Duplikat</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div style="font-weight: 800; color: var(--text-heading); font-size: 0.95rem; margin-bottom: 0.65rem;">
                        <?= htmlspecialchars($s['mata_pelajaran']) ?>
                    </div>

                    <!-- Dokumen Daftar Hadir -->
                    <div style="background: #f8fafc; border-radius: var(--radius-sm); padding: 0.6rem 0.75rem; margin-bottom: 0.5rem; border: 1px solid #e2e8f0;">
                        <div style="font-size: 0.74rem; font-weight: 700; color: #475569; margin-bottom: 4px; display: flex; align-items: center; justify-content: space-between;">
                            <span><i class="bi bi-clipboard-check"></i> DAFTAR HADIR</span>
                            <?php if (empty($s['dh'])): ?>
                                <span style="color: #ef4444; font-weight: 600;">Belum Ada</span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($s['dh'])): ?>
                            <?php foreach ($s['dh'] as $d): ?>
                                <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 4px;">
                                    <div style="font-size: 0.78rem; color: #334155;">
                                        DH #<?= $d['id'] ?> (<?= htmlspecialchars($d['nama_pengawas_1']) ?>)
                                    </div>
                                    <a href="/arsip.php?action=view&type=absensi&id=<?= $d['id'] ?>" target="_blank" class="btn-view-doc btn-view-dh" style="padding: 2px 8px; font-size: 0.75rem;">
                                        <i class="bi bi-eye"></i> Lihat PDF
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <a href="/absensi.php" class="btn btn-secondary btn-sm" style="font-size: 0.72rem; padding: 2px 8px; margin-top: 2px;">
                                <i class="bi bi-plus-lg"></i> Isi Sekarang
                            </a>
                        <?php endif; ?>
                    </div>

                    <!-- Dokumen Berita Acara -->
                    <div style="background: #f8fafc; border-radius: var(--radius-sm); padding: 0.6rem 0.75rem; border: 1px solid #e2e8f0;">
                        <div style="font-size: 0.74rem; font-weight: 700; color: #475569; margin-bottom: 4px; display: flex; align-items: center; justify-content: space-between;">
                            <span><i class="bi bi-file-earmark-text"></i> BERITA ACARA</span>
                            <?php if (empty($s['ba'])): ?>
                                <span style="color: #d97706; font-weight: 600;">Belum Ada</span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($s['ba'])): ?>
                            <?php foreach ($s['ba'] as $b): ?>
                                <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 4px;">
                                    <div style="font-size: 0.78rem; color: #334155;">
                                        BA #<?= $b['id'] ?> (<?= htmlspecialchars($b['nama_pengawas_1']) ?>)
                                    </div>
                                    <a href="/arsip.php?action=view&type=berita_acara&id=<?= $b['id'] ?>" target="_blank" class="btn-view-doc btn-view-ba" style="padding: 2px 8px; font-size: 0.75rem;">
                                        <i class="bi bi-eye"></i> Lihat PDF
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <a href="/berita-acara.php" class="btn btn-secondary btn-sm" style="font-size: 0.72rem; padding: 2px 8px; margin-top: 2px;">
                                <i class="bi bi-plus-lg"></i> Isi Sekarang
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</main>

<footer style="background: #ffffff; border-top: 1px solid var(--border); padding: 1.5rem; text-align: center; font-size: 0.825rem; color: var(--text-muted);">
    <div class="container">
        <div><strong><?= htmlspecialchars($namaSekolah) ?></strong> &bull; Administrasi Ujian Sekolah</div>
        <div style="margin-top: 4px; font-size: 0.75rem; color: #94a3b8;">
            Seluruh data lembar presensi dan berita acara tersimpan secara terpusat pada sistem database sekolah.
        </div>
    </div>
</footer>

<script src="/assets/js/app.js"></script>
</body>
</html>
