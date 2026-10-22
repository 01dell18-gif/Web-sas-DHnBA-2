<?php
require_once __DIR__ . '/../config/functions.php';
require_admin();

$type = $_GET['type'] ?? 'absensi';
if (!in_array($type, ['absensi', 'berita-acara', 'berita_acara', 'dh', 'ba'])) {
    $type = 'absensi';
}

$id = (int)($_GET['id'] ?? 0);

if ($type === 'berita-acara' || $type === 'berita_acara' || $type === 'ba') {
    $table = 'berita_acara';
    $docTypeName = 'Berita Acara Ujian';
    $docBadge = 'BA';
    $streamUrl = "/admin/berita-acara.php?action=view_single&id={$id}";
    $downloadUrl = "/admin/berita-acara.php?action=download_single&id={$id}";
    $doc = dbFetch("SELECT * FROM berita_acara WHERE id = :id", ['id' => $id]);
} else {
    $table = 'absensi';
    $docTypeName = 'Daftar Hadir Ujian';
    $docBadge = 'DH';
    $streamUrl = "/admin/absensi.php?action=view_single&id={$id}";
    $downloadUrl = "/admin/absensi.php?action=download_single&id={$id}";
    $doc = dbFetch("SELECT * FROM absensi WHERE id = :id", ['id' => $id]);
}

$title = $doc ? "Pratinjau {$docTypeName} - {$doc['nama_kelas']} ({$doc['mata_pelajaran']})" : "Dokumen Tidak Ditemukan";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> | SAS SMKN 1 Klungkung</title>
    <link rel="icon" type="image/png" href="/assets/img/logo.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body, html {
            height: 100%;
            width: 100%;
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0f172a;
            color: #f8fafc;
            overflow: hidden;
        }

        .preview-topbar {
            height: 56px;
            background: #1e293b;
            border-bottom: 1px solid #334155;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1.25rem;
            position: relative;
            z-index: 10;
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            min-width: 0;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-shrink: 0;
        }

        .doc-badge {
            background: #3b82f6;
            color: #ffffff;
            font-size: 0.72rem;
            font-weight: 800;
            padding: 2px 7px;
            border-radius: 4px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .doc-badge.ba {
            background: #4f46e5;
        }

        .doc-title {
            font-size: 0.925rem;
            font-weight: 700;
            color: #f1f5f9;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 480px;
        }

        .meta-pill {
            background: #334155;
            color: #cbd5e1;
            font-size: 0.75rem;
            padding: 3px 8px;
            border-radius: 999px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .btn-preview {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            font-size: 0.825rem;
            font-weight: 600;
            padding: 0.45rem 0.85rem;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            border: 1px solid transparent;
            transition: all 0.15s ease;
            line-height: 1;
        }

        .btn-preview-secondary {
            background: #334155;
            color: #e2e8f0;
            border-color: #475569;
        }
        .btn-preview-secondary:hover {
            background: #475569;
            color: #ffffff;
        }

        .btn-preview-primary {
            background: #2563eb;
            color: #ffffff;
            border-color: #1d4ed8;
        }
        .btn-preview-primary:hover {
            background: #1d4ed8;
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.4);
        }

        .btn-preview-close {
            background: rgba(239, 68, 68, 0.12);
            color: #f87171;
            border-color: rgba(239, 68, 68, 0.25);
        }
        .btn-preview-close:hover {
            background: #ef4444;
            color: #ffffff;
        }

        .preview-viewer-container {
            width: 100%;
            height: calc(100vh - 56px);
            background: #525659;
            position: relative;
        }

        .preview-iframe {
            width: 100%;
            height: 100%;
            border: none;
            display: block;
        }

        .error-container {
            padding: 3rem 1.5rem;
            text-align: center;
            max-width: 520px;
            margin: 4rem auto;
            background: #1e293b;
            border-radius: 12px;
            border: 1px solid #334155;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        .error-icon {
            font-size: 3rem;
            color: #f87171;
            margin-bottom: 1rem;
        }

        /* Foto Modal jika BA */
        .photo-gallery-modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.85);
            z-index: 100;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        .photo-gallery-box {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            width: 100%;
            max-width: 840px;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
    </style>
</head>
<body>

    <?php if (!$doc): ?>
        <div class="error-container">
            <div class="error-icon"><i class="bi bi-exclamation-octagon-fill"></i></div>
            <h2 style="font-size: 1.25rem; margin-bottom: 0.5rem; color: #fff;">Dokumen Tidak Ditemukan</h2>
            <p style="color: #94a3b8; font-size: 0.875rem; margin-bottom: 1.5rem;">
                Berkas arsip dokumen ujian dengan ID #<?= $id ?> tidak ditemukan di dalam basis data sistem.
            </p>
            <button onclick="window.close()" class="btn-preview btn-preview-secondary" style="margin: 0 auto;">
                <i class="bi bi-x-lg"></i> Tutup Tab Ini
            </button>
        </div>
    <?php else: ?>
        <?php
        $fullPath = BASE_PATH . '/' . ($doc['pdf_path'] ?? '');
        $fileExists = !empty($doc['pdf_path']) && file_exists($fullPath);
        $photos = !empty($doc['foto_dokumentasi']) ? json_decode($doc['foto_dokumentasi'], true) : [];
        if (!is_array($photos)) $photos = [];
        ?>

        <header class="preview-topbar">
            <div class="topbar-left">
                <button onclick="window.close()" class="btn-preview btn-preview-close" title="Tutup Tab Pratinjau">
                    <i class="bi bi-x-lg"></i> Tutup
                </button>

                <span class="doc-badge <?= $docBadge === 'BA' ? 'ba' : '' ?>">
                    <?= $docBadge ?> #<?= $doc['id'] ?>
                </span>

                <div class="doc-title" title="<?= htmlspecialchars($doc['nama_kelas'] . ' - ' . $doc['mata_pelajaran']) ?>">
                    <strong><?= htmlspecialchars($doc['nama_kelas']) ?></strong> &bull; <?= htmlspecialchars($doc['mata_pelajaran']) ?>
                </div>

                <span class="meta-pill" style="display: none; @media(min-width: 768px){ display: inline-flex; }">
                    <i class="bi bi-calendar3"></i> <?= date('d/m/Y', strtotime($doc['tanggal_ujian'])) ?>
                </span>
                <span class="meta-pill" style="display: none; @media(min-width: 900px){ display: inline-flex; }">
                    <i class="bi bi-geo-alt"></i> Ruang: <?= htmlspecialchars($doc['ruang']) ?>
                </span>
            </div>

            <div class="topbar-right">
                <?php if (!empty($photos)): ?>
                    <button type="button" class="btn-preview btn-preview-secondary" onclick="openPhotoModal()" title="Lihat Foto Dokumentasi Pelaksanaan Ujian">
                        <i class="bi bi-images"></i> <span>Foto (<?= count($photos) ?>)</span>
                    </button>
                <?php endif; ?>

                <?php if ($fileExists): ?>
                    <a href="<?= $streamUrl ?>" target="_blank" class="btn-preview btn-preview-secondary" title="Buka PDF di Tab Baru Terpisah">
                        <i class="bi bi-box-arrow-up-right"></i> <span>Tab Baru</span>
                    </a>
                    <button type="button" class="btn-preview btn-preview-secondary" onclick="printPdfDocument()" title="Cetak / Print Langsung Dokumen">
                        <i class="bi bi-printer-fill"></i> <span>Cetak</span>
                    </button>
                    <a href="<?= $downloadUrl ?>" class="btn-preview btn-preview-primary" title="Unduh Berkas PDF Resmi">
                        <i class="bi bi-download"></i> <span>Unduh PDF</span>
                    </a>
                <?php endif; ?>
            </div>
        </header>

        <main class="preview-viewer-container">
            <?php if (!$fileExists): ?>
                <div class="error-container">
                    <div class="error-icon"><i class="bi bi-file-earmark-x-fill"></i></div>
                    <h2 style="font-size: 1.25rem; margin-bottom: 0.5rem; color: #fff;">Berkas PDF Tidak Ditemukan</h2>
                    <p style="color: #94a3b8; font-size: 0.875rem; margin-bottom: 1rem;">
                        File fisik <code><?= htmlspecialchars($doc['pdf_path'] ?? 'path kosong') ?></code> tidak ditemukan pada penyimpanan server.
                    </p>
                    <button onclick="window.close()" class="btn-preview btn-preview-secondary" style="margin: 0 auto;">
                        <i class="bi bi-x-lg"></i> Tutup Tab Ini
                    </button>
                </div>
            <?php else: ?>
                <object id="pdfObject" data="<?= $streamUrl ?>#toolbar=1&navpanes=1" type="application/pdf" style="width: 100%; height: 100%; border: none; display: block;">
                    <iframe id="pdfFrame" class="preview-iframe" src="<?= $streamUrl ?>" title="Pratinjau PDF">
                        <div class="error-container">
                            <div class="error-icon"><i class="bi bi-file-earmark-pdf"></i></div>
                            <h2 style="font-size: 1.25rem; margin-bottom: 0.5rem; color: #fff;">Pratinjau Memerlukan Buka Tab Langsung</h2>
                            <p style="color: #94a3b8; font-size: 0.875rem; margin-bottom: 1.5rem;">
                                Browser Anda memerlukan akses tab langsung untuk merender dokumen PDF ini.
                            </p>
                            <a href="<?= $streamUrl ?>" target="_blank" class="btn-preview btn-preview-primary">
                                <i class="bi bi-box-arrow-up-right"></i> Buka Dokumen Langsung di Tab Baru
                            </a>
                        </div>
                    </iframe>
                </object>
            <?php endif; ?>
        </main>

        <?php if (!empty($photos)): ?>
            <div id="modalPhotoGallery" class="photo-gallery-modal" onclick="closePhotoModal(event)">
                <div class="photo-gallery-box" onclick="event.stopPropagation()">
                    <div style="padding: 1rem 1.25rem; border-bottom: 1px solid #334155; display: flex; justify-content: space-between; align-items: center;">
                        <h3 style="font-size: 1rem; color: #f8fafc; font-weight: 700;">
                            📸 Foto Dokumentasi: <?= htmlspecialchars($doc['nama_kelas'] . ' - ' . $doc['mata_pelajaran']) ?>
                        </h3>
                        <button onclick="closePhotoModal()" style="background: none; border: none; color: #94a3b8; font-size: 1.25rem; cursor: pointer;">&times;</button>
                    </div>
                    <div style="padding: 1.25rem; overflow-y: auto; display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1rem;">
                        <?php foreach ($photos as $idx => $p): ?>
                            <div style="background: #0f172a; border-radius: 8px; overflow: hidden; border: 1px solid #334155;">
                                <img src="/<?= htmlspecialchars($p) ?>" alt="Dokumentasi Ujian #<?= $idx + 1 ?>" style="width: 100%; height: 200px; object-fit: cover; display: block;">
                                <div style="padding: 0.5rem 0.75rem; font-size: 0.75rem; color: #94a3b8; text-align: center;">
                                    Foto Pelaksanaan #<?= $idx + 1 ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <script>
        function printPdfDocument() {
            const frame = document.getElementById('pdfFrame');
            try {
                if (frame && frame.contentWindow) {
                    frame.contentWindow.focus();
                    frame.contentWindow.print();
                } else {
                    window.print();
                }
            } catch (err) {
                // If cross-origin or restricted, fallback to direct open or window print
                window.print();
            }
        }

        function openPhotoModal() {
            const m = document.getElementById('modalPhotoGallery');
            if (m) m.style.display = 'flex';
        }

        function closePhotoModal() {
            const m = document.getElementById('modalPhotoGallery');
            if (m) m.style.display = 'none';
        }
        </script>
    <?php endif; ?>

</body>
</html>
