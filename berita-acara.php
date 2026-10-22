<?php
require_once __DIR__ . '/config/functions.php';

$namaSekolah = get_setting('nama_sekolah', 'SMK NEGERI 1 KLUNGKUNG');
$logoPath = get_setting('logo_path', 'storage/uploads/logo/logo_default.png');
$kopPath = get_setting('kop_image_path', 'assets/img/kop-header.png');

$templates = dbFetchAll("SELECT * FROM template_ujian WHERE aktif = 1 ORDER BY id DESC");
$classes = dbFetchAll("SELECT * FROM kelas WHERE aktif = 1 ORDER BY tingkat DESC, nama_kelas ASC");
$gurus = dbFetchAll("SELECT * FROM guru WHERE aktif = 1 ORDER BY nama ASC");
$mapels = dbFetchAll("SELECT * FROM mapel WHERE aktif = 1 ORDER BY nama_mapel ASC");

$defaultTpl = $templates[0] ?? [
    'id' => 1,
    'nama_ujian' => 'Sumatif Akhir Semester (SAS) Ganjil',
    'tahun_ajaran' => '2026/2027'
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Berita Acara Ujian - <?= htmlspecialchars($namaSekolah) ?></title>
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
                <span class="brand-subtitle">Generator Berita Acara Pelaksanaan Ujian</span>
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
        <li><a href="/berita-acara.php" class="nav-link active"><i class="bi bi-file-earmark-text"></i> Berita Acara</a></li>
        <li><a href="/admin/login.php" class="btn btn-secondary btn-sm"><i class="bi bi-shield-lock"></i> Admin</a></li>
    </ul>
</header>

<div class="container" style="padding-top: 1rem; padding-bottom: 2rem;">
    <!-- Mobile Tabs -->
    <div class="mobile-tabs">
        <button type="button" class="mobile-tab-btn active" data-tab="form"><i class="bi bi-pencil-square"></i> Formulir Input</button>
        <button type="button" class="mobile-tab-btn" data-tab="preview"><i class="bi bi-file-earmark-pdf"></i> Pratinjau Kertas A4</button>
    </div>

    <div class="split-layout">
        <!-- PANEL FORMULIR (KIRI) -->
        <div class="panel-form" id="panelForm">
            <form id="formBeritaAcara" method="POST" action="/generate-berita-acara.php" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="ttd_pengawas_1_data" id="ttd_pengawas_1_data">
                <input type="hidden" name="ttd_pengawas_2_data" id="ttd_pengawas_2_data">
                <input type="hidden" name="template_id" id="template_id" value="<?= $defaultTpl['id'] ?>">

                <!-- 1. Identitas & Template Ujian -->
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
                                    <option value="<?= $t['id'] ?>" <?= $t['id'] == $defaultTpl['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($t['nama_ujian']) ?> (<?= htmlspecialchars($t['tahun_ajaran']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>

                        <div class="grid-2">
                            <div class="form-group">
                                <label class="form-label" for="nama_ujian">Nama Ujian <span class="required">*</span></label>
                                <input type="text" name="nama_ujian" id="nama_ujian" class="form-control" 
                                       value="<?= htmlspecialchars($defaultTpl['nama_ujian']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="tahun_ajaran">Tahun Ajaran <span class="required">*</span></label>
                                <input type="text" name="tahun_ajaran" id="tahun_ajaran" class="form-control" 
                                       value="<?= htmlspecialchars($defaultTpl['tahun_ajaran']) ?>" placeholder="Misal: 2026/2027" required>
                            </div>
                        </div>

                        <div class="grid-2">
                            <div class="form-group">
                                <label class="form-label" for="tanggal_ujian">Tanggal Pelaksanaan <span class="required">*</span></label>
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

                        <div class="grid-2">
                            <div class="form-group">
                                <label class="form-label" for="ruang">Ruang Ujian <span class="required">*</span></label>
                                <input type="text" name="ruang" id="ruang" class="form-control" placeholder="Contoh: Lab Komputer 1" value="Lab 1" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Waktu Mulai - Selesai (Format 24 Jam) <span class="required">*</span></label>
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

                <!-- 2. Peserta & Kelas Ujian -->
                <div class="card">
                    <div class="card-header">
                        <span class="card-title"><i class="bi bi-people"></i> 2. Kelas & Kehadiran Peserta</span>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label class="form-label" for="class_id">Pilih Kelas Ujian <span class="required">*</span></label>
                            <input type="hidden" name="nama_kelas" id="nama_kelas">
                            <select name="class_id" id="class_id" class="form-select" required>
                                <option value="">-- Pilih Kelas Aktif --</option>
                                <?php foreach ($classes as $c): ?>
                                    <option value="<?= $c['id'] ?>" data-nama="<?= htmlspecialchars($c['nama_kelas']) ?>">
                                        <?= htmlspecialchars($c['nama_kelas']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="grid-3">
                            <div class="form-group">
                                <label class="form-label" for="peserta_seharusnya">Peserta Seharusnya <span class="required">*</span></label>
                                <input type="number" name="peserta_seharusnya" id="peserta_seharusnya" class="form-control" value="0" min="0" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="peserta_hadir">Peserta Hadir <span class="required">*</span></label>
                                <input type="number" name="peserta_hadir" id="peserta_hadir" class="form-control" value="0" min="0" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="peserta_tidak_hadir">Tidak Hadir <span class="required">*</span></label>
                                <input type="number" name="peserta_tidak_hadir" id="peserta_tidak_hadir" class="form-control" value="0" min="0" required>
                            </div>
                        </div>

                        <div id="pesertaValidationAlert" class="alert alert-warning" style="display: none; font-size: 0.85rem; padding: 0.65rem 0.85rem; margin-top: 0.5rem;">
                        </div>

                        <div class="grid-2" style="margin-top: 0.5rem;">
                            <div class="form-group">
                                <label class="form-label" for="blangko_berita_acara">Blangko Berita Acara (Eks)</label>
                                <input type="number" name="blangko_berita_acara" id="blangko_berita_acara" class="form-control" value="1" min="1">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="blangko_daftar_hadir">Blangko Daftar Hadir (Eks)</label>
                                <input type="number" name="blangko_daftar_hadir" id="blangko_daftar_hadir" class="form-control" value="1" min="1">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="catatan_pelaksanaan">Catatan / Kejadian Khusus Selama Ujian</label>
                            <textarea name="catatan_pelaksanaan" id="catatan_pelaksanaan" class="form-control" rows="2" placeholder="Pelaksanaan ujian berjalan tertib, aman, dan lancar tanpa kendala teknis.">Pelaksanaan ujian berjalan tertib, aman, dan lancar tanpa kendala teknis.</textarea>
                        </div>
                    </div>
                </div>

                <!-- 3. Foto Dokumentasi Kegiatan -->
                <div class="card">
                    <div class="card-header">
                        <span class="card-title"><i class="bi bi-camera"></i> 3. Dokumentasi Bukti Pelaksanaan (Foto)</span>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label class="form-label" for="foto_kegiatan">Unggah Foto Ujian (Maksimal 4 Foto)</label>
                            <input type="file" name="foto_kegiatan[]" id="foto_kegiatan" class="form-control" accept="image/*" multiple>
                            <div class="form-hint">Dapat memilih langsung dari kamera HP atau galeri foto. Foto akan otomatis dikompresi agar hemat kuota dan proses cepat.</div>
                        </div>

                        <div id="fotoPreviewBox" style="display: none; margin-top: 1rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                <span style="font-size: 0.85rem; font-weight: 600; color: var(--slate-700);">
                                    Foto Terpilih (<span id="fotoCount">0</span>):
                                </span>
                                <button type="button" id="btnClearPhotos" class="btn btn-secondary btn-sm" style="padding: 2px 8px; font-size: 0.75rem;">
                                    <i class="bi bi-trash"></i> Hapus Foto
                                </button>
                            </div>
                            <div id="fotoThumbnailsList" style="display: flex; gap: 0.75rem; flex-wrap: wrap;"></div>
                        </div>
                    </div>
                </div>

                <!-- 4. Identitas & Tanda Tangan Pengawas -->
                <div class="card">
                    <div class="card-header">
                        <span class="card-title"><i class="bi bi-pen"></i> 4. Identitas & Tanda Tangan Pengawas</span>
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
                        <i class="bi bi-file-earmark-arrow-down"></i> Unduh PDF Berita Acara
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
                    <span><i class="bi bi-file-earmark-check"></i> Pratinjau Kertas A4 (2 Halaman)</span>
                </div>
                <div style="display: flex; align-items: center; gap: 0.4rem;">
                    <button type="button" id="btnZoomToggle" class="btn btn-sm" style="font-size: 0.75rem; padding: 3px 10px; background: rgba(255,255,255,0.15); color: #ffffff; border: 1px solid rgba(255,255,255,0.3); cursor: pointer;" onclick="togglePreviewFit()" title="Sesuaikan lebar halaman">
                        <i class="bi bi-aspect-ratio"></i> Pas Lebar
                    </button>
                </div>
            </div>

            <div class="a4-wrapper" id="a4Wrapper" style="display: flex !important; flex-direction: column !important; align-items: center !important; justify-content: flex-start !important; width: 100% !important; gap: 30px !important; box-sizing: border-box !important;">
                
                <!-- HALAMAN 1: BERITA ACARA RESMI -->
                <div class="a4-page" id="a4Page" style="margin: 0 auto !important; width: 210mm !important; min-height: 297mm !important; box-sizing: border-box !important; flex-shrink: 0 !important;">
                    <!-- KOP SURAT SEKOLAH -->
                    <div class="kop-surat" style="border-bottom: none; padding-bottom: 0; margin-bottom: 8px; text-align: center;">
                        <img src="/<?= htmlspecialchars($kopPath) ?>" alt="Kop Surat Resmi" style="width: 100%; max-height: 105px; object-fit: contain; display: block; margin: 0 auto;">
                    </div>

                    <!-- JUDUL DOKUMEN -->
                    <div class="doc-title" id="pvJudulDoc">
                        BERITA ACARA<br>
                        PENYELENGGARAAN SUMATIF AKHIR SEMESTER (SAS) GANJIL<br>
                        TAHUN AJARAN <span id="pvTahunAjaran">2026/2027</span>
                    </div>

                    <!-- REDAKSI BERITA ACARA -->
                    <div id="pvBeritaAcaraContent" style="font-size: 10.5pt; line-height: 1.6; text-align: justify; margin: 15px 0;">
                    </div>

                    <!-- AREA TANDA TANGAN PENGAWAS -->
                    <div class="signature-section" style="margin-top: 30px;">
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

                <!-- HALAMAN 2: LAMPIRAN FOTO BUKTI PELAKSANAAN -->
                <div class="a4-page" id="a4Page2" style="margin: 0 auto !important; width: 210mm !important; min-height: 297mm !important; box-sizing: border-box !important; flex-shrink: 0 !important;">
                    <div class="kop-surat" style="border-bottom: none; padding-bottom: 0; margin-bottom: 12px; text-align: center;">
                        <img src="/<?= htmlspecialchars($kopPath) ?>" alt="Kop Surat Resmi" style="width: 100%; max-height: 105px; object-fit: contain; display: block; margin: 0 auto;">
                    </div>

                    <div class="doc-title" style="margin: 6px 0 10px 0;">
                        LAMPIRAN BUKTI PELAKSANAAN
                    </div>

                    <div id="pvLampiranInfo" style="border-bottom: 1.5px solid #000; padding-bottom: 6px; margin-bottom: 14px; font-size: 10pt; line-height: 1.45;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <td style="width: 55%; vertical-align: top;">
                                    <strong>Nama Ujian:</strong> <span id="pvLpNamaUjian">-</span><br>
                                    <strong>Mata Pelajaran:</strong> <span id="pvLpMapel">-</span>
                                </td>
                                <td style="width: 45%; vertical-align: top;">
                                    <strong>Kelas / Ruang:</strong> <span id="pvLpKelas">-</span> / <span id="pvLpRuang">-</span><br>
                                    <strong>Hari / Tanggal:</strong> <span id="pvLpTanggal">-</span>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- GRID FOTO LIVE PREVIEW -->
                    <div id="pvFotoGrid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-bottom: 25px;">
                    </div>

                    <!-- AREA TANDA TANGAN PENGAWAS PADA LAMPIRAN -->
                    <div class="signature-section" style="margin-top: 25px;">
                        <div class="signature-row" id="pvSignatureRow2" style="display: flex; justify-content: space-between;">
                            <div class="signature-col" id="pvColPengawas2_Page2" style="width: 45%; text-align: center; display: none;">
                                <div>Pengawas II,</div>
                                <div class="signature-space">
                                    <img id="pvSignImg2_Page2" style="display: none;" alt="TTD Pengawas 2">
                                </div>
                                <div class="signature-name" id="pvNamaPengawas2_Page2">Pengawas II</div>
                                <div class="signature-nip" id="pvNipPengawas2_Page2">NIP. -</div>
                            </div>
                            <div class="signature-col" id="pvColPengawas1_Page2" style="width: 45%; text-align: center; margin-left: auto;">
                                <div>Pengawas I,</div>
                                <div class="signature-space">
                                    <img id="pvSignImg1_Page2" style="display: none;" alt="TTD Pengawas 1">
                                </div>
                                <div class="signature-name" id="pvNamaPengawas1_Page2">Pengawas I</div>
                                <div class="signature-nip" id="pvNipPengawas1_Page2">NIP. -</div>
                            </div>
                        </div>
                    </div>

                    <div style="text-align: center; font-size: 8pt; color: #94a3b8; margin-top: 35px; border-top: 1px dashed #e2e8f0; padding-top: 6px;">
                        — Akhir Lembar Lampiran Bukti Pelaksanaan (Halaman 2) —
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
window.EXAM_TEMPLATES = <?= json_encode($templates, JSON_UNESCAPED_UNICODE) ?>;
window.SCHOOL_NAME = <?= json_encode($namaSekolah, JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="/assets/js/app.js"></script>
<script src="/assets/js/signature-pad.js"></script>
<script src="/assets/js/berita-acara.js"></script>
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

function confirmResetForm() {
    window.showConfirmDialog('Apakah Anda yakin ingin mengosongkan kembali formulir Berita Acara?', function() {
        document.getElementById('formBeritaAcara').reset();
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
