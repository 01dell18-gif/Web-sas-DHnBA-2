<?php
require_once __DIR__ . '/config/functions.php';
require_once __DIR__ . '/vendor/dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Method Not Allowed');
}

verify_csrf();

$templateId = (int)($_POST['template_id'] ?? 1);
$namaUjian = trim($_POST['nama_ujian'] ?? 'Sumatif Akhir Semester (SAS) Ganjil');
$tahunAjaran = trim($_POST['tahun_ajaran'] ?? '2026/2027');
$semester = trim($_POST['semester'] ?? 'Ganjil');
$tanggalUjian = trim($_POST['tanggal_ujian'] ?? date('Y-m-d'));
$mataPelajaran = trim($_POST['mata_pelajaran'] ?? '');
$ruang = trim($_POST['ruang'] ?? 'Lab 1');
$jamMulai = trim($_POST['jam_mulai'] ?? '07:30');
$jamSelesai = trim($_POST['jam_selesai'] ?? '09:30');
$classId = (int)($_POST['class_id'] ?? 0);

$classInfo = dbFetch("SELECT * FROM kelas WHERE id = :id", ['id' => $classId]);
$namaKelas = trim($_POST['nama_kelas'] ?? ($classInfo['nama_kelas'] ?? "Kelas {$classId}"));
$tingkat = $classInfo['tingkat'] ?? 'XII';

$seharusnya = (int)($_POST['peserta_seharusnya'] ?? 0);
$hadir = (int)($_POST['peserta_hadir'] ?? 0);
$tidakHadir = (int)($_POST['peserta_tidak_hadir'] ?? 0);
$blangkoBA = (int)($_POST['blangko_berita_acara'] ?? 1);
$blangkoDH = (int)($_POST['blangko_daftar_hadir'] ?? 1);
$catatan = trim($_POST['catatan_pelaksanaan'] ?? 'Pelaksanaan ujian berjalan tertib, aman, dan lancar tanpa kendala teknis.');

$namaPengawas1 = trim($_POST['nama_pengawas_1'] ?? '');
$nipPengawas1 = trim($_POST['nip_pengawas_1'] ?? '');
$namaPengawas2 = trim($_POST['nama_pengawas_2'] ?? '');
$nipPengawas2 = trim($_POST['nip_pengawas_2'] ?? '');

$signatureMode = trim($_POST['signature_mode'] ?? 'digital');
$ttd1 = trim($_POST['ttd_pengawas_1_data'] ?? '');
$ttd2 = trim($_POST['ttd_pengawas_2_data'] ?? '');
$action = trim($_POST['action'] ?? 'download');

// Handle photo uploads
$savedPhotos = [];
$docDir = __DIR__ . '/storage/uploads/dokumentasi';
if (!is_dir($docDir)) {
    mkdir($docDir, 0777, true);
}

if (!empty($_FILES['foto_kegiatan']['name'][0])) {
    $count = count($_FILES['foto_kegiatan']['name']);
    for ($i = 0; $i < min($count, 4); $i++) {
        if ($_FILES['foto_kegiatan']['error'][$i] === UPLOAD_ERR_OK) {
            $tmp = $_FILES['foto_kegiatan']['tmp_name'][$i];
            $ext = strtolower(pathinfo($_FILES['foto_kegiatan']['name'][$i], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $ext = 'jpg';
            }
            $targetName = 'dok_ba_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '_' . ($i + 1) . '.' . $ext;
            $dest = $docDir . '/' . $targetName;
            if (move_uploaded_file($tmp, $dest)) {
                $savedPhotos[] = 'storage/uploads/dokumentasi/' . $targetName;
            }
        }
    }
}

// Fetch active template
$tpl = dbFetch("SELECT * FROM template_ujian WHERE id = :id", ['id' => $templateId]);
if (!$tpl) {
    $tpl = dbFetch("SELECT * FROM template_ujian WHERE aktif = 1 LIMIT 1");
}

$namaSekolah = get_setting('nama_sekolah', 'SMK NEGERI 1 KLUNGKUNG');
$kopPath = __DIR__ . '/' . get_setting('kop_image_path', 'assets/img/kop-header.png');
$kopBase64 = '';
if (file_exists($kopPath)) {
    $type = pathinfo($kopPath, PATHINFO_EXTENSION);
    $data = file_get_contents($kopPath);
    $kopBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
}

// Date breakdown for placeholder interpolation
$timestamp = strtotime($tanggalUjian);
$hariArr = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
$bulanArr = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

$hari = $hariArr[date('w', $timestamp)] ?? '';
$tglNum = (int)date('j', $timestamp);
$blnName = $bulanArr[(int)date('n', $timestamp)] ?? '';
$thnNum = date('Y', $timestamp);
$tglTeks = terbilang($tglNum);
$thnTeks = terbilang($thnNum);
$hariName = $hari;
$hariTanggalIndo = "{$tglNum} {$blnName} {$thnNum}";

$vars = [
    'nama_ujian' => $namaUjian,
    'tahun_ajaran' => $tahunAjaran,
    'hari' => $hari,
    'tanggal' => (string)$tglNum,
    'tanggal_teks' => $tglTeks,
    'bulan' => $blnName,
    'tahun' => (string)$thnNum,
    'tahun_teks' => $thnTeks,
    'jam_mulai' => $jamMulai,
    'jam_selesai' => $jamSelesai,
    'sekolah' => $namaSekolah,
    'mata_pelajaran' => $mataPelajaran,
    'kelas' => $namaKelas,
    'ruang' => $ruang,
    'peserta_seharusnya' => (string)$seharusnya,
    'peserta_hadir' => (string)$hadir,
    'peserta_tidak_hadir' => (string)$tidakHadir,
    'blangko_berita_acara' => (string)$blangkoBA,
    'blangko_daftar_hadir' => (string)$blangkoDH,
    'catatan_pelaksanaan' => $catatan ?: 'Pelaksanaan ujian berjalan tertib, aman, dan lancar tanpa kendala teknis.'
];

$rawRedaksi = $tpl['redaksi_isi'] ?? '';
foreach ($vars as $k => $v) {
    $valHtml = ($k === 'catatan_pelaksanaan') ? nl2br(htmlspecialchars($v)) : htmlspecialchars($v);
    $rawRedaksi = str_ireplace('[' . $k . ']', $valHtml, $rawRedaksi);
}

$judulDoc = $tpl['judul_berita_acara'] ?? "BERITA ACARA\r\nPENYELENGGARAAN SUMATIF AKHIR SEMESTER (SAS) GANJIL\r\nTAHUN AJARAN [tahun_ajaran]";
foreach ($vars as $k => $v) {
    $judulDoc = str_ireplace('[' . $k . ']', htmlspecialchars($v), $judulDoc);
}
$judulDocHtml = nl2br($judulDoc);

// Convert photos to base64 for PDF embedding
$photoBase64List = [];
foreach ($savedPhotos as $rel) {
    $full = __DIR__ . '/' . $rel;
    if (file_exists($full)) {
        $pType = pathinfo($full, PATHINFO_EXTENSION);
        $pData = file_get_contents($full);
        $photoBase64List[] = 'data:image/' . $pType . ';base64,' . base64_encode($pData);
    }
}

ob_start();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Berita Acara - <?= htmlspecialchars($namaKelas) ?> - <?= htmlspecialchars($mataPelajaran) ?></title>
<style>
    @page {
        size: A4 portrait;
        margin: 12mm 15mm 12mm 15mm;
    }
    body {
        font-family: Arial, Helvetica, sans-serif;
        font-size: 9.5pt;
        color: #000;
        margin: 0;
        padding: 0;
        line-height: 1.4;
    }
    .kop {
        text-align: center;
        margin-bottom: 8px;
    }
    .kop img {
        width: 100%;
        max-height: 95px;
    }
    .doc-title {
        text-align: center;
        font-weight: bold;
        font-size: 11.5pt;
        line-height: 1.3;
        margin-bottom: 14px;
        text-transform: uppercase;
    }
    .redaksi-body {
        font-size: 9.5pt;
        line-height: 1.5;
        text-align: justify;
    }
    .redaksi-body ol, .redaksi-body ul {
        margin-top: 4px;
        margin-bottom: 8px;
        padding-left: 20px;
    }
    .redaksi-body li {
        margin-bottom: 6px;
    }
    .signature-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
        page-break-inside: avoid;
        font-size: 9.5pt;
    }
    .signature-table td {
        text-align: center;
        vertical-align: top;
        padding: 4px;
    }
    .sig-space {
        height: 60px;
    }
    .sig-img {
        max-height: 55px;
        max-width: 140px;
    }
    .page-break {
        page-break-before: always;
    }
    .meta-box-lp {
        border-bottom: 1.5px solid #000;
        padding-bottom: 6px;
        margin-bottom: 14px;
        font-size: 9pt;
        line-height: 1.45;
    }
    .photo-grid {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }
    .photo-grid td {
        width: 50%;
        padding: 6px;
        text-align: center;
        vertical-align: top;
    }
    .photo-box {
        border: 1px solid #cbd5e1;
        padding: 4px;
        background: #f8fafc;
        border-radius: 4px;
    }
    .photo-box img {
        width: 100%;
        max-height: 190px;
        object-fit: cover;
        display: block;
    }
</style>
</head>
<body>

<!-- HALAMAN 1: BERITA ACARA RESMI -->
<div class="kop">
    <?php if ($kopBase64): ?>
        <img src="<?= $kopBase64 ?>">
    <?php else: ?>
        <div style="font-size: 13pt; font-weight: bold;"><?= htmlspecialchars($namaSekolah) ?></div>
        <hr style="border-top: 2px solid #000; margin-top: 4px;">
    <?php endif; ?>
</div>

<div class="doc-title">
    <?= $judulDocHtml ?>
</div>

<div class="redaksi-body">
    <?= $rawRedaksi ?>
</div>

<table class="signature-table">
    <tr>
        <td style="width: 50%;">
            <?php if (!empty($namaPengawas2)): ?>
                <div>Pengawas II,</div>
                <div class="sig-space">
                    <?php if ($signatureMode === 'digital' && !empty($ttd2)): ?>
                        <img class="sig-img" src="<?= $ttd2 ?>">
                    <?php endif; ?>
                </div>
                <div style="font-weight: bold; text-decoration: underline;"><?= htmlspecialchars($namaPengawas2) ?></div>
                <div style="font-size: 8.5pt; color: #555;">NIP. <?= htmlspecialchars($nipPengawas2 ?: '-') ?></div>
            <?php endif; ?>
        </td>
        <td style="width: 50%;">
            <div>Pengawas I,</div>
            <div class="sig-space">
                <?php if ($signatureMode === 'digital' && !empty($ttd1)): ?>
                    <img class="sig-img" src="<?= $ttd1 ?>">
                <?php endif; ?>
            </div>
            <div style="font-weight: bold; text-decoration: underline;"><?= htmlspecialchars($namaPengawas1) ?></div>
            <div style="font-size: 8.5pt; color: #555;">NIP. <?= htmlspecialchars($nipPengawas1 ?: '-') ?></div>
        </td>
    </tr>
</table>

<!-- HALAMAN 2: LAMPIRAN FOTO BUKTI PELAKSANAAN -->
<div class="page-break"></div>

<div class="kop">
    <?php if ($kopBase64): ?>
        <img src="<?= $kopBase64 ?>">
    <?php endif; ?>
</div>

<div class="doc-title" style="margin-bottom: 8px;">
    LAMPIRAN BUKTI PELAKSANAAN
</div>

<div class="meta-box-lp">
    <table style="width: 100%;">
        <tr>
            <td style="width: 55%; vertical-align: top;">
                <strong>Nama Ujian:</strong> <?= htmlspecialchars($namaUjian) ?><br>
                <strong>Mata Pelajaran:</strong> <?= htmlspecialchars($mataPelajaran) ?>
            </td>
            <td style="width: 45%; vertical-align: top;">
                <strong>Kelas / Ruang:</strong> <?= htmlspecialchars($namaKelas) ?> / <?= htmlspecialchars($ruang) ?><br>
                <strong>Hari / Tanggal:</strong> <?= htmlspecialchars($hariName ? "{$hariName}, {$hariTanggalIndo}" : $hariTanggalIndo) ?>
            </td>
        </tr>
    </table>
</div>

<?php if (!empty($photoBase64List)): ?>
    <table class="photo-grid">
        <tr>
            <?php foreach ($photoBase64List as $idx => $pDataUrl): ?>
                <?php if ($idx > 0 && $idx % 2 === 0): ?>
                    </tr><tr>
                <?php endif; ?>
                <td>
                    <div class="photo-box">
                        <img src="<?= $pDataUrl ?>">
                        <div style="font-size: 8pt; color: #64748b; margin-top: 3px;">Foto Dokumentasi #<?= $idx + 1 ?></div>
                    </div>
                </td>
            <?php endforeach; ?>
            <?php if (count($photoBase64List) % 2 !== 0): ?>
                <td></td>
            <?php endif; ?>
        </tr>
    </table>
<?php else: ?>
    <div style="text-align: center; color: #64748b; padding: 40px; border: 1px dashed #cbd5e1; border-radius: 6px; margin-bottom: 20px;">
        (Tidak ada foto bukti dokumentasi yang dilampirkan)
    </div>
<?php endif; ?>

<table class="signature-table" style="margin-top: 20px;">
    <tr>
        <td style="width: 50%;">
            <?php if (!empty($namaPengawas2)): ?>
                <div>Pengawas II,</div>
                <div class="sig-space">
                    <?php if ($signatureMode === 'digital' && !empty($ttd2)): ?>
                        <img class="sig-img" src="<?= $ttd2 ?>">
                    <?php endif; ?>
                </div>
                <div style="font-weight: bold; text-decoration: underline;"><?= htmlspecialchars($namaPengawas2) ?></div>
                <div style="font-size: 8.5pt; color: #555;">NIP. <?= htmlspecialchars($nipPengawas2 ?: '-') ?></div>
            <?php endif; ?>
        </td>
        <td style="width: 50%;">
            <div>Pengawas I,</div>
            <div class="sig-space">
                <?php if ($signatureMode === 'digital' && !empty($ttd1)): ?>
                    <img class="sig-img" src="<?= $ttd1 ?>">
                <?php endif; ?>
            </div>
            <div style="font-weight: bold; text-decoration: underline;"><?= htmlspecialchars($namaPengawas1) ?></div>
            <div style="font-size: 8.5pt; color: #555;">NIP. <?= htmlspecialchars($nipPengawas1 ?: '-') ?></div>
        </td>
    </tr>
</table>

</body>
</html>
<?php
$html = ob_get_clean();

// Generate PDF
$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$pdfOutput = $dompdf->output();

// Save file
$cleanMapel = preg_replace('/[^a-zA-Z0-9_\-]/', ' ', $mataPelajaran);
$cleanMapel = trim(preg_replace('/\s+/', ' ', $cleanMapel));
$filename = "BA-SAS GANJIL 26-27-{$namaKelas}-{$cleanMapel}-{$tanggalUjian}.pdf";
$relativeDir = 'storage/berita-acara';
$fullDir = __DIR__ . '/' . $relativeDir;
if (!is_dir($fullDir)) {
    mkdir($fullDir, 0777, true);
}
$fullPath = $fullDir . '/' . $filename;
file_put_contents($fullPath, $pdfOutput);

// Save to database
$relPdfPath = "{$relativeDir}/{$filename}";
$fileSize = strlen($pdfOutput);

$newId = dbInsert('berita_acara', [
    'template_id' => $templateId,
    'nama_ujian' => $namaUjian,
    'tahun_ajaran' => $tahunAjaran,
    'semester' => $semester,
    'class_id' => $classId,
    'nama_kelas' => $namaKelas,
    'tingkat' => $tingkat,
    'mata_pelajaran' => $mataPelajaran,
    'tanggal_ujian' => $tanggalUjian,
    'jam_mulai' => $jamMulai,
    'jam_selesai' => $jamSelesai,
    'ruang' => $ruang,
    'peserta_seharusnya' => $seharusnya,
    'peserta_hadir' => $hadir,
    'peserta_tidak_hadir' => $tidakHadir,
    'blangko_berita_acara' => $blangkoBA,
    'blangko_daftar_hadir' => $blangkoDH,
    'catatan_pelaksanaan' => $catatan,
    'nama_pengawas_1' => $namaPengawas1,
    'nip_pengawas_1' => $nipPengawas1,
    'nama_pengawas_2' => $namaPengawas2,
    'nip_pengawas_2' => $nipPengawas2,
    'signature_mode' => $signatureMode,
    'ttd_pengawas_1' => $ttd1,
    'ttd_pengawas_2' => $ttd2,
    'foto_dokumentasi' => json_encode($savedPhotos, JSON_UNESCAPED_UNICODE),
    'pdf_path' => $relPdfPath,
    'file_size' => $fileSize,
    'created_at' => date('Y-m-d H:i:s'),
    'updated_at' => date('Y-m-d H:i:s')
]);

log_activity('CREATE_BA', "Membuat Berita Acara {$namaKelas} ({$mataPelajaran}) - ID #{$newId}");

// Output PDF
header('Content-Type: application/pdf');
if ($action === 'view') {
    header('Content-Disposition: inline; filename="' . $filename . '"');
} else {
    header('Content-Disposition: attachment; filename="' . $filename . '"');
}
header('Content-Length: ' . strlen($pdfOutput));
echo $pdfOutput;
exit;
