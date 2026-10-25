<?php
require_once __DIR__ . '/config/functions.php';

$namaSekolah = get_setting('nama_sekolah', 'SMK NEGERI FYJ Channel');
$logoPath = get_setting('logo_path', 'storage/uploads/logo/logo_default.png');
$kopPath = get_setting('kop_image_path', 'assets/img/kop-header.png');

$templates = dbFetchAll("SELECT * FROM template_ujian WHERE aktif = 1 ORDER BY id DESC");
$classes = dbFetchAll("SELECT * FROM kelas WHERE aktif = 1 ORDER BY tingkat DESC, nama_kelas ASC");
$gurus = dbFetchAll("SELECT * FROM guru WHERE aktif = 1 ORDER BY nama ASC");
$mapels = dbFetchAll("SELECT * FROM mapel WHERE aktif = 1 ORDER BY nama_mapel ASC");

$defaultTpl = $templates[0] ?? [
    'nama_ujian' => 'Sumatif Akhir Semester (SAS) Ganjil',
    'tahun_ajaran' => '2026/2027'
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Hadir Ujian - <?= htmlspecialchars($namaSekolah) ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
    <link rel="stylesheet" href="/assets/css/preview-a4.css">
    <link rel="icon" type="image/png" href="/<?= htmlspecialchars($logoPath) ?>">
</head>
<body>

<header class="navbar">
    <div class="navbar-brand">
        <a href="/" class="brand-link">
            <img src="/<?= htmlspecialchars($logoPath) ?>" alt="Logo Sekolah" class="navbar-logo">
            <div class="brand-text">
                <span class="brand-title"><?= htmlspecialchars($namaSekolah) ?></span>
                <span class="brand-subtitle">Generator Daftar Hadir Ujian</span>
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
        <li><a href="/absensi.php" class="nav-link active"><i class="bi bi-clipboard-check"></i> Daftar Hadir</a></li>
        <li><a href="/berita-acara.php" class="nav-link"><i class="bi bi-file-earmark-text"></i> Berita Acara</a></li>
        <li><a href="/arsip.php" class="nav-link"><i class="bi bi-archive"></i> Arsip Dokumen</a></li>
        <li><a href="/admin/login.php" class="btn btn-secondary btn-sm"><i class="bi bi-shield-lock"></i> Admin</a></li>
    </ul>
</header>

<div class="container" style="padding-top: 1rem; padding-bottom: 2rem;">
    <!-- Mobile Tabs Switcher -->
    <div class="mobile-tabs">
        <button type="button" class="mobile-tab-btn active" data-tab="form"><i class="bi bi-pencil-square"></i> Formulir Input</button>
        <button type="button" class="mobile-tab-btn" data-tab="preview"><i class="bi bi-file-earmark-pdf"></i> Pratinjau Kertas A4</button>
    </div>

    <div class="split-layout">
        <!-- PANEL FORMULIR (KIRI) -->
        <div class="panel-form" id="panelForm">
            <form id="formAbsensi" method="POST" action="/generate-absensi.php">
                <?= csrf_field() ?>
                <input type="hidden" name="ttd_pengawas_1_data" id="ttd_pengawas_1_data">
                <input type="hidden" name="ttd_pengawas_2_data" id="ttd_pengawas_2_data">

                <!-- 1. Identitas Ujian -->
                <div class="card">
                    <div class="card-header">
                        <span class="card-title"><i class="bi bi-calendar-event"></i> 1. Identitas & Jadwal Ujian</span>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($templates)): ?>
                        <div class="form-group">
                            <label class="form-label" for="selectTemplate">Pilih Template Ujian</label>
                            <select id="selectTemplate" class="form-select" onchange="applyExamTemplate(this.value)">
                                <?php foreach ($templates as $t): ?>
                                    <option value="<?= $t['id'] ?>" 
                                            data-nama="<?= htmlspecialchars($t['nama_ujian']) ?>" 
                                            data-tahun="<?= htmlspecialchars($t['tahun_ajaran']) ?>"
                                            data-semester="Ganjil">
                                        <?= htmlspecialchars($t['nama_ujian']) ?> (<?= htmlspecialchars($t['tahun_ajaran']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>

                        <div class="form-group">
                            <label class="form-label" for="nama_ujian">Nama Ujian <span class="required">*</span></label>
                            <input type="text" name="nama_ujian" id="nama_ujian" class="form-control" 
                                   value="<?= htmlspecialchars($defaultTpl['nama_ujian']) ?>" required>
                        </div>

                        <div class="grid-2">
                            <div class="form-group">
                                <label class="form-label" for="tahun_ajaran">Tahun Ajaran <span class="required">*</span></label>
                                <input type="text" name="tahun_ajaran" id="tahun_ajaran" class="form-control" 
                                       value="<?= htmlspecialchars($defaultTpl['tahun_ajaran']) ?>" placeholder="Misal: 2026/2027" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="semester">Semester <span class="required">*</span></label>
                                <select name="semester" id="semester" class="form-select" required>
                                    <option value="Ganjil" selected>Ganjil</option>
                                    <option value="Genap">Genap</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid-2">
                            <div class="form-group">
                                <label class="form-label" for="tanggal_ujian">Tanggal Ujian <span class="required">*</span></label>
                                <input type="date" name="tanggal_ujian" id="tanggal_ujian" class="form-control" 
                                       value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="mata_pelajaran">Mata Pelajaran <span class="required">*</span></label>
                                <input type="text" name="mata_pelajaran" id="mata_pelajaran" class="form-control input-mapel-search" 
                                       list="datalist_mapel" placeholder="Ketik atau pilih mata pelajaran..." autocomplete="off" required>
                                <datalist id="datalist_mapel">
                                    <?php foreach ($mapels as $m): ?>
                                        <option value="<?= htmlspecialchars($m['nama_mapel']) ?>">
                                            <?= !empty($m['kode_mapel']) ? '[' . htmlspecialchars($m['kode_mapel']) . '] ' : '' ?><?= htmlspecialchars($m['nama_mapel']) ?><?= !empty($m['kelompok']) ? ' (' . htmlspecialchars($m['kelompok']) . ')' : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </datalist>
                            </div>
                        </div>

                        <div class="grid-3">
                            <div class="form-group">
                                <label class="form-label" for="sesi">Sesi</label>
                                <input type="text" name="sesi" id="sesi" class="form-control" placeholder="Contoh: 1" value="1">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="ruang">Ruang</label>
                                <input type="text" name="ruang" id="ruang" class="form-control" placeholder="Contoh: Lab RPL" value="Lab 1">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Waktu Mulai - Selesai (Format 24 Jam)</label>
                                <div style="display: flex; gap: 6px; align-items: center;">
                                    <input type="text" name="jam_mulai" id="jam_mulai" class="form-control time-24h" 
                                           value="07:30" placeholder="07:30" maxlength="5" pattern="^([01]?[0-9]|2[0-3]):[0-5][0-9]$" title="Format jam 24 jam HH:mm (Contoh: 07:30 atau 13:30)" required>
                                    <span style="font-weight: bold; color: var(--slate-600);">-</span>
                                    <input type="text" name="jam_selesai" id="jam_selesai" class="form-control time-24h" 
                                           value="09:30" placeholder="09:30" maxlength="5" pattern="^([01]?[0-9]|2[0-3]):[0-5][0-9]$" title="Format jam 24 jam HH:mm (Contoh: 09:30 atau 15:00)" required>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Pemilihan Kelas & Kehadiran Siswa -->
                <div class="card">
                    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
                        <span class="card-title"><i class="bi bi-people"></i> 2. Peserta & Status Kehadiran</span>
                        <button type="button" id="btnSetAllHadir" class="btn btn-secondary btn-sm">
                            <i class="bi bi-check2-all"></i> Set Semua Hadir
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label class="form-label" for="class_id">Pilih Kelas Ujian <span class="required">*</span></label>
                            <select name="class_id" id="class_id" class="form-select" required>
                                <option value="">-- Pilih Kelas Aktif --</option>
                                <?php foreach ($classes as $c): ?>
                                    <option value="<?= $c['id'] ?>">
                                        <?= htmlspecialchars($c['nama_kelas']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Indikator Loading -->
                        <div id="studentLoading" style="display: none; text-align: center; padding: 1.5rem; color: var(--primary);">
                            <i class="bi bi-arrow-repeat spin"></i> Memuat daftar peserta ujian...
                        </div>

                        <!-- Container Siswa -->
                        <div id="studentsContainer">
                            <div style="text-align: center; color: var(--text-muted); padding: 1.5rem;">
                                Silakan pilih kelas di atas untuk memuat daftar siswa.
                            </div>
                        </div>

                        <!-- Rekapitulasi Kehadiran Form -->
                        <div style="background: var(--slate-50); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 0.85rem 1rem; margin-top: 1rem;">
                            <div style="font-weight: 700; font-size: 0.85rem; color: var(--text-heading); margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.4rem;">
                                <i class="bi bi-pie-chart" style="color: var(--primary);"></i> Rekapitulasi Kehadiran:
                            </div>
                            <div style="display: flex; gap: 0.85rem; flex-wrap: wrap; font-size: 0.85rem;">
                                <span>Hadir: <strong id="rekapHadirForm">0</strong></span>
                                <span>Dispensasi: <strong id="rekapDispensasiForm">0</strong></span>
                                <span>Izin: <strong id="rekapIzinForm">0</strong></span>
                                <span>Sakit: <strong id="rekapSakitForm">0</strong></span>
                                <span>Tidak Hadir: <strong id="rekapTidakHadirForm" style="color: var(--danger);">0</strong></span>
                                <span>Total: <strong id="rekapTotalForm">0</strong></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Identitas & Tanda Tangan Pengawas -->
                <div class="card">
                    <div class="card-header">
                        <span class="card-title"><i class="bi bi-pen"></i> 3. Identitas & Tanda Tangan Pengawas</span>
                    </div>
                    <div class="card-body">
                        <div class="grid-2">
                            <div>
                                <h4 style="font-size: 0.9rem; margin-bottom: 0.4rem; color: var(--primary);">
                                    <i class="bi bi-person-check-fill"></i> Pengawas I
                                </h4>
                                <div class="form-group">
                                    <label class="form-label" for="nama_pengawas_1">
                                        Nama Lengkap <span class="required">*</span>
                                        <span style="font-weight: normal; font-size: 0.72rem; color: var(--text-muted);">(Ketik nama / pilih guru)</span>
                                    </label>
                                    <input type="text" name="nama_pengawas_1" id="nama_pengawas_1" list="list_guru_pengawas" 
                                           class="form-control input-guru-search" 
                                           placeholder="Ketik untuk mencari atau pilih guru..." 
                                           autocomplete="off" required 
                                           oninput="handleGuruSearch(1, this)" 
                                           onchange="handleGuruSearch(1, this)">
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="nip_pengawas_1">NIP / NIPPPK (Opsional)</label>
                                    <input type="text" name="nip_pengawas_1" id="nip_pengawas_1" class="form-control" placeholder="19800101... atau -">
                                </div>
                            </div>
                            <div>
                                <h4 style="font-size: 0.9rem; margin-bottom: 0.4rem; color: var(--primary);">
                                    <i class="bi bi-person-check"></i> Pengawas II
                                </h4>
                                <div class="form-group">
                                    <label class="form-label" for="nama_pengawas_2">
                                        Nama Lengkap (Opsional)
                                        <span style="font-weight: normal; font-size: 0.72rem; color: var(--text-muted);">(Ketik nama / pilih guru)</span>
                                    </label>
                                    <input type="text" name="nama_pengawas_2" id="nama_pengawas_2" list="list_guru_pengawas" 
                                           class="form-control input-guru-search" 
                                           placeholder="Ketik untuk mencari atau pilih guru..." 
                                           autocomplete="off" 
                                           oninput="handleGuruSearch(2, this)" 
                                           onchange="handleGuruSearch(2, this)">
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="nip_pengawas_2">NIP / NIPPPK (Opsional)</label>
                                    <input type="text" name="nip_pengawas_2" id="nip_pengawas_2" class="form-control" placeholder="19850202... atau -">
                                </div>
                            </div>
                        </div>

                        <!-- Datalist Master Guru untuk Pencarian Cepat Langsung di Kolom Nama -->
                        <datalist id="list_guru_pengawas">
                            <?php foreach ($gurus as $g): ?>
                                <option value="<?= htmlspecialchars($g['nama']) ?>">
                                    <?= htmlspecialchars($g['nip'] && $g['nip'] !== '-' ? 'NIP. ' . $g['nip'] : 'Guru / Pengawas') ?>
                                </option>
                            <?php endforeach; ?>
                        </datalist>

                        <!-- Mode Tanda Tangan -->
                        <div class="form-group" style="margin-top: 1rem;">
                            <label class="form-label">Mode Tanda Tangan Pengawas:</label>
                            <div style="display: flex; gap: 1.5rem; flex-wrap: wrap;">
                                <label style="display: flex; align-items: center; gap: 0.4rem; cursor: pointer; font-size: 0.9rem;">
                                    <input type="radio" name="signature_mode" value="digital" checked>
                                    <strong>Mode A:</strong> Gambar Tanda Tangan di Layar
                                </label>
                                <label style="display: flex; align-items: center; gap: 0.4rem; cursor: pointer; font-size: 0.9rem;">
                                    <input type="radio" name="signature_mode" value="manual">
                                    <strong>Mode B:</strong> Tanda Tangan Manual (Setelah Dicetak)
                                </label>
                            </div>
                        </div>

                        <!-- Canvas Box (Mode A) -->
                        <div id="canvasContainer">
                            <div class="grid-2">
                                <div>
                                    <label class="form-label" style="font-size: 0.85rem;">Tanda Tangan Pengawas I:</label>
                                    <div class="signature-box">
                                        <canvas id="canvas_ttd_pengawas_1"></canvas>
                                    </div>
                                    <div class="signature-tools">
                                        <button type="button" id="btnClearTtd1" class="btn btn-secondary btn-sm"><i class="bi bi-eraser"></i> Bersihkan / Ulangi</button>
                                        <span style="font-size: 0.75rem; color: var(--text-muted);">Gunakan sentuhan / mouse</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="form-label" style="font-size: 0.85rem;">Tanda Tangan Pengawas II:</label>
                                    <div class="signature-box">
                                        <canvas id="canvas_ttd_pengawas_2"></canvas>
                                    </div>
                                    <div class="signature-tools">
                                        <button type="button" id="btnClearTtd2" class="btn btn-secondary btn-sm"><i class="bi bi-eraser"></i> Bersihkan / Ulangi</button>
                                        <span style="font-size: 0.75rem; color: var(--text-muted);">Gunakan sentuhan / mouse</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Notice Box (Mode B) -->
                        <div id="manualSignNotice" style="display: none; background: var(--info-light); border: 1px solid #BFDBFE; padding: 0.85rem 1rem; border-radius: var(--radius-md); font-size: 0.85rem; color: #1E40AF;">
                            <i class="bi bi-info-circle-fill" style="margin-right: 0.35rem;"></i> <strong>Mode Manual Dipilih:</strong> Dokumen PDF akan menyediakan area tanda tangan kosong yang cukup untuk dibubuhi tanda tangan pena basah setelah dicetak.
                        </div>
                    </div>
                </div>

                <!-- Tombol Aksi Utama -->
                <div class="form-actions">
                    <button type="submit" name="action" value="download" class="btn btn-primary" style="flex: 2; padding: 0.85rem; font-size: 1rem;">
                        <i class="bi bi-file-earmark-arrow-down"></i> Unduh PDF Daftar Hadir
                    </button>
                    <button type="submit" name="action" value="view" formtarget="_blank" class="btn btn-secondary" style="padding: 0.85rem;" title="Buka di Tab Baru">
                        <i class="bi bi-box-arrow-up-right"></i> Tab Baru
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="confirmResetForm()" style="padding: 0.85rem;">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset
                    </button>
                </div>
            </form>
        </div>

        <!-- PANEL PRATINJAU KERTAS A4 (KANAN) -->
        <div class="panel-preview" id="panelPreview">
            <div class="panel-preview-header" style="position: sticky; top: 0; z-index: 20; background: #253047; color: #ffffff; width: 100%; padding: 0.65rem 1rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem; box-shadow: 0 2px 6px rgba(37, 48, 71, 0.15);">
                <div style="font-size: 0.85rem; font-weight: 700; display: flex; align-items: center; gap: 0.4rem;">
                    <span><i class="bi bi-file-earmark-check"></i> Pratinjau Kertas A4 (Live Synchronized)</span>
                </div>
                <div style="display: flex; align-items: center; gap: 0.4rem;">
                    <button type="button" id="btnZoomToggle" class="btn btn-sm" style="font-size: 0.75rem; padding: 3px 10px; background: rgba(255,255,255,0.15); color: #ffffff; border: 1px solid rgba(255,255,255,0.3); cursor: pointer;" onclick="togglePreviewFit()" title="Sesuaikan lebar halaman dengan layar">
                        <i class="bi bi-aspect-ratio"></i> Pas Lebar
                    </button>
                </div>
            </div>

            <div class="a4-wrapper" id="a4Wrapper" style="display: flex !important; flex-direction: column !important; align-items: center !important; justify-content: flex-start !important; width: 100% !important; gap: 30px !important; box-sizing: border-box !important;">
                <div class="a4-page" id="a4Page" style="margin: 0 auto !important; width: 210mm !important; min-height: 297mm !important; box-sizing: border-box !important; flex-shrink: 0 !important;">
                    <!-- KOP SURAT SEKOLAH -->
                    <div id="pvKopSurat">
                        <?= render_kop_surat_html(false) ?>
                    </div>

                    <!-- JUDUL DOKUMEN -->
                    <div class="doc-title">
                        DAFTAR HADIR PESERTA UJIAN<br>
                        <span id="pvNamaUjian">SUMATIF AKHIR SEMESTER (SAS) GANJIL</span><br>
                        TAHUN AJARAN <span id="pvTahunAjaran">2026/2027</span>
                    </div>

                    <!-- METADATA UJIAN -->
                    <table class="meta-table">
                        <tr>
                            <td style="width: 16%;">Hari / Tanggal</td>
                            <td style="width: 2%;">:</td>
                            <td style="width: 38%;"><strong id="pvHariTanggal">-</strong></td>
                            <td style="width: 14%;">Kelas</td>
                            <td style="width: 2%;">:</td>
                            <td style="width: 28%;"><strong id="pvKelas">-</strong></td>
                        </tr>
                        <tr>
                            <td>Mata Pelajaran</td>
                            <td>:</td>
                            <td><strong id="pvMataPelajaran">-</strong></td>
                            <td>Sesi / Ruang</td>
                            <td>:</td>
                            <td><span id="pvSesi">-</span> / <span id="pvRuang">-</span></td>
                        </tr>
                        <tr>
                            <td>Waktu Ujian</td>
                            <td>:</td>
                            <td><span id="pvWaktu">-</span></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                    </table>

                    <!-- TABEL SISWA -->
                    <table class="attendance-table">
                        <thead>
                            <tr>
                                <th style="width: 35px;">No</th>
                                <th style="width: 85px;">NIS</th>
                                <th>Nama Peserta Didik</th>
                                <th style="width: 100px;">Kehadiran</th>
                            </tr>
                        </thead>
                        <tbody id="pvStudentTbody">
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 2rem; color: #666;">
                                    (Silakan pilih kelas pada formulir di sebelah kiri)
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- REKAPITULASI KEHADIRAN -->
                    <div class="rekap-box">
                        <div style="font-weight: bold; font-size: 9.5pt;">Rekapitulasi Kehadiran:</div>
                        <table class="rekap-table">
                            <tr>
                                <td>Hadir</td>
                                <td>Dispensasi</td>
                                <td>Izin</td>
                                <td>Sakit</td>
                                <td>Tidak Hadir</td>
                                <td><strong>Total Siswa</strong></td>
                            </tr>
                            <tr>
                                <td><strong id="pvRekapHadir">0</strong></td>
                                <td><strong id="pvRekapDispensasi">0</strong></td>
                                <td><strong id="pvRekapIzin">0</strong></td>
                                <td><strong id="pvRekapSakit">0</strong></td>
                                <td><strong id="pvRekapTidakHadir" style="color: red;">0</strong></td>
                                <td><strong id="pvRekapTotal">0</strong></td>
                            </tr>
                        </table>
                    </div>

                    <!-- AREA TANDA TANGAN PENGAWAS -->
                    <div class="signature-section">
                        <div class="signature-row" id="pvSignatureRow" style="display: flex; justify-content: space-between;">
                            <div class="signature-col" id="pvColPengawas2" style="width: 45%; text-align: center; display: none;">
                                <div>Pengawas II,</div>
                                <div class="signature-space">
                                    <img id="pvSignImg2" style="display: none;" alt="TTD Pengawas 2">
                                </div>
                                <div class="signature-name" id="pvNamaPengawas2">Pengawas II</div>
                                <div class="signature-nip" id="pvNipPengawas2">NIP. -</div>
                            </div>
                            <div class="signature-col" id="pvColPengawas1" style="width: 45%; text-align: center; margin-left: auto;">
                                <div>Pengawas I,</div>
                                <div class="signature-space">
                                    <img id="pvSignImg1" style="display: none;" alt="TTD Pengawas 1">
                                </div>
                                <div class="signature-name" id="pvNamaPengawas1">Pengawas I</div>
                                <div class="signature-nip" id="pvNipPengawas1">NIP. -</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="/assets/js/app.js"></script>
<script src="/assets/js/signature-pad.js"></script>
<script src="/assets/js/absensi.js"></script>
<script>
window.GURU_DATA = <?= json_encode(array_column($gurus, 'nip', 'nama'), JSON_UNESCAPED_UNICODE) ?>;

function handleGuruSearch(num, el) {
    const val = el.value.trim();
    if (window.GURU_DATA && window.GURU_DATA.hasOwnProperty(val)) {
        const nipInput = document.getElementById('nip_pengawas_' + num);
        if (nipInput) {
            const nipVal = window.GURU_DATA[val];
            nipInput.value = (nipVal && nipVal !== '-') ? nipVal : '';
            nipInput.dispatchEvent(new Event('input', { bubbles: true }));
        }
    }
}
function autoFillGuru(num, el) {
    handleGuruSearch(num, el);
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.input-guru-search, .input-mapel-search').forEach(inp => {
        inp.addEventListener('focus', function() {
            if (this.showPicker) {
                try { this.showPicker(); } catch(e) {}
            }
        });
    });
});

function applyExamTemplate(tplId) {
    const select = document.getElementById('selectTemplate');
    const opt = select.options[select.selectedIndex];
    if (opt) {
        document.getElementById('nama_ujian').value = opt.getAttribute('data-nama');
        document.getElementById('tahun_ajaran').value = opt.getAttribute('data-tahun');
        const sem = opt.getAttribute('data-semester');
        if (sem && document.getElementById('semester')) {
            document.getElementById('semester').value = sem;
            document.getElementById('semester').dispatchEvent(new Event('change'));
        }
        document.getElementById('nama_ujian').dispatchEvent(new Event('input'));
        document.getElementById('tahun_ajaran').dispatchEvent(new Event('input'));
    }
}

function confirmResetForm() {
    window.showConfirmDialog('Apakah Anda yakin ingin mengosongkan kembali seluruh isian formulir?', function() {
        document.getElementById('formAbsensi').reset();
        window.location.reload();
    }, 'Kosongkan Formulir', true);
}

function togglePreviewFit() {
    const wrapper = document.getElementById('a4Wrapper');
    if (!wrapper) return;
    wrapper.classList.toggle('fit-width');
}
</script>

</body>
</html>
