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

$namaUjian = trim($_POST['nama_ujian'] ?? 'Sumatif Akhir Semester (SAS) Ganjil');
$tahunAjaran = trim($_POST['tahun_ajaran'] ?? '2026/2027');
$semester = trim($_POST['semester'] ?? 'Ganjil');
$tanggalUjian = trim($_POST['tanggal_ujian'] ?? date('Y-m-d'));
$mataPelajaran = trim($_POST['mata_pelajaran'] ?? '');
$sesi = trim($_POST['sesi'] ?? '1');
$ruang = trim($_POST['ruang'] ?? 'Lab 1');
$jamMulai = trim($_POST['jam_mulai'] ?? '07:30');
$jamSelesai = trim($_POST['jam_selesai'] ?? '09:30');
$classId = (int)($_POST['class_id'] ?? 0);

$namaPengawas1 = trim($_POST['nama_pengawas_1'] ?? '');
$nipPengawas1 = trim($_POST['nip_pengawas_1'] ?? '');
$namaPengawas2 = trim($_POST['nama_pengawas_2'] ?? '');
$nipPengawas2 = trim($_POST['nip_pengawas_2'] ?? '');

$signatureMode = trim($_POST['signature_mode'] ?? 'digital');
$ttd1 = trim($_POST['ttd_pengawas_1_data'] ?? '');
$ttd2 = trim($_POST['ttd_pengawas_2_data'] ?? '');
$action = trim($_POST['action'] ?? 'download');

$classInfo = dbFetch("SELECT * FROM kelas WHERE id = :id", ['id' => $classId]);
$namaKelas = $classInfo['nama_kelas'] ?? "Kelas {$classId}";
$tingkat = $classInfo['tingkat'] ?? 'XII';

// Parse students attendance
$studentsInput = $_POST['students'] ?? [];
$studentsList = [];
$hadir = 0;
$dispensasi = 0;
$izin = 0;
$sakit = 0;
$tidakHadir = 0;

foreach ($studentsInput as $s) {
    $stStatus = trim($s['status'] ?? 'Hadir');
    $studentsList[] = [
        'id' => $s['id'] ?? 0,
        'nama' => $s['nama'] ?? '',
        'nis' => $s['nis'] ?? '',
        'status' => $stStatus
    ];
    if ($stStatus === 'Hadir') $hadir++;
    elseif ($stStatus === 'Dispensasi') $dispensasi++;
    elseif ($stStatus === 'Izin') $izin++;
    elseif ($stStatus === 'Sakit') $sakit++;
    else $tidakHadir++;
}
$totalPeserta = count($studentsList);

// Prepare Kop Surat Base64
$kopPath = __DIR__ . '/' . get_setting('kop_image_path', 'assets/img/kop-header.png');
$kopBase64 = '';
if (file_exists($kopPath)) {
    $type = pathinfo($kopPath, PATHINFO_EXTENSION);
    $data = file_get_contents($kopPath);
    $kopBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
}

// Prepare HTML for Dompdf
$hariTanggalIndo = format_date_indo($tanggalUjian);
$hariArr = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
$hariName = $hariArr[date('w', strtotime($tanggalUjian))] ?? '';

ob_start();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Daftar Hadir - <?= htmlspecialchars($namaKelas) ?> - <?= htmlspecialchars($mataPelajaran) ?></title>
<style>
    @page {
        size: A4 portrait;
        margin: 12mm 15mm 12mm 15mm;
    }
    body {
        font-family: Arial, Helvetica, sans-serif;
        font-size: 9pt;
        color: #000;
        margin: 0;
        padding: 0;
        line-height: 1.3;
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
        line-height: 1.25;
        margin-bottom: 12px;
        text-transform: uppercase;
    }
    .meta-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 10px;
        font-size: 9pt;
    }
    .meta-table td {
        padding: 2px 1px;
        vertical-align: top;
    }
    .data-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 12px;
        font-size: 8.5pt;
    }
    .data-table th, .data-table td {
        border: 1px solid #000;
        padding: 4px 6px;
    }
    .data-table th {
        background-color: #f1f3f9;
        text-align: center;
        font-weight: bold;
    }
    .rekap-box {
        border: 1px solid #000;
        padding: 6px 10px;
        margin-bottom: 14px;
        page-break-inside: avoid;
    }
    .rekap-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 4px;
        text-align: center;
        font-size: 8.5pt;
    }
    .rekap-table td {
        border: 1px solid #000;
        padding: 3px 6px;
    }
    .signature-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
        page-break-inside: avoid;
        font-size: 9pt;
    }
    .signature-table td {
        text-align: center;
        vertical-align: top;
        padding: 4px;
    }
    .sig-space {
        height: 55px;
    }
    .sig-img {
        max-height: 50px;
        max-width: 130px;
    }
</style>
</head>
<body>

<div class="kop">
    <?php if ($kopBase64): ?>
        <img src="<?= $kopBase64 ?>">
    <?php else: ?>
        <div style="font-size: 13pt; font-weight: bold;">SMK NEGERI 1 KLUNGKUNG</div>
        <div style="font-size: 9pt;">Generator Absensi & Berita Acara Ujian</div>
        <hr style="border-top: 2px solid #000; margin-top: 4px;">
    <?php endif; ?>
</div>

<div class="doc-title">
    DAFTAR HADIR PESERTA UJIAN<br>
    <?= htmlspecialchars($namaUjian) ?><br>
    TAHUN AJARAN <?= htmlspecialchars($tahunAjaran) ?>
</div>

<table class="meta-table">
    <tr>
        <td style="width: 16%;">Hari / Tanggal</td>
        <td style="width: 2%;">:</td>
        <td style="width: 38%;"><strong><?= htmlspecialchars($hariName ? "{$hariName}, {$hariTanggalIndo}" : $hariTanggalIndo) ?></strong></td>
        <td style="width: 14%;">Kelas</td>
        <td style="width: 2%;">:</td>
        <td style="width: 28%;"><strong><?= htmlspecialchars($namaKelas) ?></strong></td>
    </tr>
    <tr>
        <td>Mata Pelajaran</td>
        <td>:</td>
        <td><strong><?= htmlspecialchars($mataPelajaran) ?></strong></td>
        <td>Sesi / Ruang</td>
        <td>:</td>
        <td><?= htmlspecialchars($sesi) ?> / <?= htmlspecialchars($ruang) ?></td>
    </tr>
    <tr>
        <td>Waktu Ujian</td>
        <td>:</td>
        <td><?= htmlspecialchars($jamMulai) ?> - <?= htmlspecialchars($jamSelesai) ?> WITA</td>
        <td></td><td></td><td></td>
    </tr>
</table>

<table class="data-table">
    <thead>
        <tr>
            <th style="width: 30px;">No</th>
            <th style="width: 90px;">NIS</th>
            <th>Nama Peserta Didik</th>
            <th style="width: 110px;">Kehadiran</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($studentsList as $idx => $st): ?>
        <tr>
            <td style="text-align: center;"><?= $idx + 1 ?></td>
            <td style="text-align: center;"><?= htmlspecialchars($st['nis'] ?: '-') ?></td>
            <td><?= htmlspecialchars($st['nama']) ?></td>
            <td style="text-align: center; font-weight: <?= $st['status'] !== 'Hadir' ? 'bold' : 'normal' ?>; color: <?= $st['status'] !== 'Hadir' ? '#dc2626' : '#000' ?>;">
                <?= htmlspecialchars($st['status']) ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<div class="rekap-box">
    <strong>Rekapitulasi Kehadiran:</strong>
    <table class="rekap-table">
        <tr style="background-color: #f8fafc; font-weight: bold;">
            <td>Hadir</td>
            <td>Dispensasi</td>
            <td>Izin</td>
            <td>Sakit</td>
            <td>Tidak Hadir</td>
            <td>Total Siswa</td>
        </tr>
        <tr>
            <td><strong><?= $hadir ?></strong></td>
            <td><strong><?= $dispensasi ?></strong></td>
            <td><strong><?= $izin ?></strong></td>
            <td><strong><?= $sakit ?></strong></td>
            <td><strong style="color: red;"><?= $tidakHadir ?></strong></td>
            <td><strong><?= $totalPeserta ?></strong></td>
        </tr>
    </table>
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
                <div style="font-size: 8pt; color: #555;">NIP. <?= htmlspecialchars($nipPengawas2 ?: '-') ?></div>
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
            <div style="font-size: 8pt; color: #555;">NIP. <?= htmlspecialchars($nipPengawas1 ?: '-') ?></div>
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
$filename = "DH-SAS GANJIL 26-27-{$namaKelas}-{$cleanMapel}-{$tanggalUjian}.pdf";
$relativeDir = 'storage/absensi';
$fullDir = __DIR__ . '/' . $relativeDir;
if (!is_dir($fullDir)) {
    mkdir($fullDir, 0777, true);
}
$fullPath = $fullDir . '/' . $filename;
file_put_contents($fullPath, $pdfOutput);

// Save to database
$relPdfPath = "{$relativeDir}/{$filename}";
$fileSize = strlen($pdfOutput);

$newId = dbInsert('absensi', [
    'nama_ujian' => $namaUjian,
    'tahun_ajaran' => $tahunAjaran,
    'semester' => $semester,
    'class_id' => $classId,
    'nama_kelas' => $namaKelas,
    'tingkat' => $tingkat,
    'mata_pelajaran' => $mataPelajaran,
    'sesi' => $sesi,
    'ruang' => $ruang,
    'tanggal_ujian' => $tanggalUjian,
    'jam_mulai' => $jamMulai,
    'jam_selesai' => $jamSelesai,
    'peserta_hadir' => $hadir,
    'peserta_dispensasi' => $dispensasi,
    'peserta_izin' => $izin,
    'peserta_sakit' => $sakit,
    'peserta_tidak_hadir' => $tidakHadir,
    'total_peserta' => $totalPeserta,
    'nama_pengawas_1' => $namaPengawas1,
    'nip_pengawas_1' => $nipPengawas1,
    'nama_pengawas_2' => $namaPengawas2,
    'nip_pengawas_2' => $nipPengawas2,
    'signature_mode' => $signatureMode,
    'ttd_pengawas_1' => $ttd1,
    'ttd_pengawas_2' => $ttd2,
    'pdf_path' => $relPdfPath,
    'file_size' => $fileSize,
    'data_siswa' => json_encode($studentsList, JSON_UNESCAPED_UNICODE),
    'created_at' => date('Y-m-d H:i:s'),
    'updated_at' => date('Y-m-d H:i:s')
]);

log_activity('CREATE_DH', "Membuat Daftar Hadir {$namaKelas} ({$mataPelajaran}) - ID #{$newId}");

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
