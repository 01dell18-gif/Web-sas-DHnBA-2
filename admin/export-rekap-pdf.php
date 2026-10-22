<?php
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../vendor/dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

require_admin();

$selectedClassId = (int)($_GET['class_id'] ?? 0);
$selectedTahun = trim($_GET['tahun_ajaran'] ?? '2026/2027');
$selectedSemester = trim($_GET['semester'] ?? 'Ganjil');

if ($selectedClassId <= 0) {
    $firstClass = dbFetch("SELECT id FROM kelas WHERE aktif = 1 ORDER BY tingkat DESC, nama_kelas ASC LIMIT 1");
    if ($firstClass) {
        $selectedClassId = (int)$firstClass['id'];
    }
}

$classInfo = dbFetch("SELECT * FROM kelas WHERE id = :id", ['id' => $selectedClassId]);
$namaKelas = $classInfo['nama_kelas'] ?? "Kelas {$selectedClassId}";
$tingkat = $classInfo['tingkat'] ?? 'XII';
$namaSekolah = get_setting('nama_sekolah', 'SMK NEGERI 1 KLUNGKUNG');

$students = dbFetchAll("SELECT * FROM siswa WHERE class_id = :cid ORDER BY nama ASC", ['cid' => $selectedClassId]);

$absensiSessions = dbFetchAll("
    SELECT id, mata_pelajaran, tanggal_ujian, data_siswa 
    FROM absensi 
    WHERE class_id = :cid AND tahun_ajaran = :th AND semester = :sem
    ORDER BY tanggal_ujian ASC, id ASC
", [
    'cid' => $selectedClassId,
    'th' => $selectedTahun,
    'sem' => $selectedSemester
]);

$seenMapel = [];
$attendanceMap = [];

foreach ($absensiSessions as $sess) {
    $mp = trim($sess['mata_pelajaran']);
    if (!isset($seenMapel[$mp])) {
        $seenMapel[$mp] = [
            'id' => $sess['id'],
            'nama' => $mp,
            'tanggal' => !empty($sess['tanggal_ujian']) ? date('d/m/Y', strtotime($sess['tanggal_ujian'])) : '-'
        ];
    }
    $dataSt = json_decode($sess['data_siswa'] ?? '[]', true) ?: [];
    foreach ($dataSt as $st) {
        $sid = (int)($st['id'] ?? 0);
        $status = $st['status'] ?? 'Hadir';
        $code = 'H';
        if ($status === 'Dispensasi') $code = 'D';
        elseif ($status === 'Izin') $code = 'I';
        elseif ($status === 'Sakit') $code = 'S';
        elseif ($status === 'Tidak Hadir' || $status === 'Alpa') $code = 'A';
        $attendanceMap[$sid][$mp] = $code;
    }
}

$subjectList = array_values($seenMapel);
$totalMapel = count($subjectList);

// Prepare Kop Header Base64
$kopPath = __DIR__ . '/../' . get_setting('kop_image_path', 'assets/img/kop-header.png');
$kopBase64 = '';
if (file_exists($kopPath)) {
    $type = pathinfo($kopPath, PATHINFO_EXTENSION);
    $data = file_get_contents($kopPath);
    $kopBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
}

// Logo Base64 Fallback
$logoPath = __DIR__ . '/../' . get_setting('logo_path', 'storage/uploads/logo/logo_default.png');
$logoBase64 = '';
if (file_exists($logoPath)) {
    $type = pathinfo($logoPath, PATHINFO_EXTENSION);
    $data = file_get_contents($logoPath);
    $logoBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
}

// Calculate total per subject
$subjectTotals = [];
foreach ($subjectList as $s) {
    $mpName = $s['nama'];
    $cntH = 0;
    foreach ($students as $st) {
        $code = $attendanceMap[$st['id']][$mpName] ?? '-';
        if ($code === 'H' || $code === 'D') {
            $cntH++;
        }
    }
    $subjectTotals[$mpName] = $cntH;
}

ob_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Kehadiran - <?= htmlspecialchars($namaKelas) ?> (<?= htmlspecialchars($selectedSemester) ?>)</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 12mm 12mm 12mm 12mm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8pt;
            color: #0f172a;
            line-height: 1.25;
            margin: 0;
            padding: 0;
        }
        .header-kop {
            width: 100%;
            margin-bottom: 8px;
            border-bottom: 2px solid #000000;
            padding-bottom: 4px;
        }
        .header-kop img {
            width: 100%;
            max-height: 85px;
            display: block;
        }
        .title-box {
            text-align: center;
            margin-bottom: 10px;
        }
        .title-main {
            font-size: 11pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #0f172a;
            margin: 0 0 2px 0;
        }
        .title-sub {
            font-size: 9pt;
            font-weight: bold;
            color: #334155;
            margin: 0;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 8px;
            font-size: 8pt;
        }
        .meta-table td {
            padding: 2px 4px;
            vertical-align: top;
        }
        .table-data {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            font-size: 7.5pt;
        }
        .table-data th, .table-data td {
            border: 0.5pt solid #475569;
            padding: 3px 3px;
            text-align: center;
            vertical-align: middle;
        }
        .table-data th {
            background-color: #f1f5f9;
            font-weight: bold;
            color: #0f172a;
        }
        .table-data th.th-mapel {
            max-width: 55px;
            word-wrap: break-word;
            font-size: 6.8pt;
            padding: 2px 2px;
            background-color: #f8fafc;
        }
        .table-data td.text-left {
            text-align: left;
            padding-left: 5px;
        }
        .code-H { background-color: #dcfce7; color: #166534; font-weight: bold; }
        .code-D { background-color: #dbeafe; color: #1e40af; font-weight: bold; }
        .code-I { background-color: #fef3c7; color: #92400e; font-weight: bold; }
        .code-S { background-color: #ede9fe; color: #5b21b6; font-weight: bold; }
        .code-A { background-color: #fee2e2; color: #991b1b; font-weight: bold; }
        .code-empty { color: #cbd5e1; }
        
        .legend-box {
            font-size: 7.5pt;
            margin-top: 6px;
            display: inline-block;
            border: 0.5pt solid #94a3b8;
            padding: 4px 8px;
            background: #f8fafc;
        }
        .legend-item {
            margin-right: 12px;
            display: inline-block;
        }
        .signature-table {
            width: 100%;
            margin-top: 15px;
            font-size: 8pt;
            page-break-inside: avoid;
        }
        .signature-table td {
            text-align: center;
            vertical-align: top;
            width: 33.33%;
        }
        .signature-space {
            height: 48px;
        }
    </style>
</head>
<body>

    <!-- KOP SURAT -->
    <div class="header-kop">
        <?php if (!empty($kopBase64)): ?>
            <img src="<?= $kopBase64 ?>" alt="Kop Surat">
        <?php else: ?>
            <div style="text-align: center; padding: 4px 0;">
                <div style="font-size: 13pt; font-weight: bold;"><?= htmlspecialchars($namaSekolah) ?></div>
                <div style="font-size: 8pt; color: #475569;">SISTEM ADMINISTRASI UJIAN SEKOLAH (SAS)</div>
            </div>
        <?php endif; ?>
    </div>

    <!-- JUDUL LAPORAN -->
    <div class="title-box">
        <div class="title-main">LAPORAN REKAPITULASI KEHADIRAN SISWA PESERTA UJIAN</div>
        <div class="title-sub">SUMATIF AKHIR SEMESTER (SAS) <?= strtoupper(htmlspecialchars($selectedSemester)) ?> - TAHUN AJARAN <?= htmlspecialchars($selectedTahun) ?></div>
    </div>

    <!-- METADATA KELAS -->
    <table class="meta-table">
        <tr>
            <td style="width: 12%;"><strong>Kelas / Tingkat</strong></td>
            <td style="width: 38%;">: <strong><?= htmlspecialchars($namaKelas) ?></strong> (Tingkat <?= htmlspecialchars($tingkat) ?>)</td>
            <td style="width: 18%;"><strong>Total Mata Pelajaran</strong></td>
            <td style="width: 32%;">: <?= $totalMapel ?> Mapel Terjadwal</td>
        </tr>
        <tr>
            <td><strong>Semester</strong></td>
            <td>: <?= htmlspecialchars($selectedSemester) ?></td>
            <td><strong>Jumlah Peserta Ujian</strong></td>
            <td>: <?= count($students) ?> Siswa Terdaftar</td>
        </tr>
    </table>

    <!-- TABEL MATRIKS REKAP -->
    <table class="table-data">
        <thead>
            <tr>
                <th rowspan="2" style="width: 20px;">No</th>
                <th rowspan="2" style="width: 55px;">NIS</th>
                <th rowspan="2" style="width: 150px; text-align: left; padding-left: 5px;">Nama Lengkap Siswa</th>
                <?php if ($totalMapel > 0): ?>
                    <th colspan="<?= $totalMapel ?>">Mata Pelajaran yang Diujikan</th>
                <?php else: ?>
                    <th>Mata Pelajaran</th>
                <?php endif; ?>
                <th colspan="5">Total Presensi</th>
                <th rowspan="2" style="width: 48px;">% Hadir</th>
            </tr>
            <tr>
                <?php if ($totalMapel > 0): ?>
                    <?php foreach ($subjectList as $s): ?>
                        <th class="th-mapel" title="<?= htmlspecialchars($s['nama']) ?>">
                            <div style="font-size: 6.8pt; line-height: 1.1; font-weight: bold;">
                                <?= htmlspecialchars(mb_strimwidth($s['nama'], 0, 18, '..')) ?>
                            </div>
                            <div style="font-size: 6pt; color: #64748b; font-weight: normal; margin-top: 1px;">
                                <?= $s['tanggal'] ?>
                            </div>
                        </th>
                    <?php endforeach; ?>
                <?php else: ?>
                    <th style="font-style: italic; color: #94a3b8; font-weight: normal;">Belum ada sesi ujian</th>
                <?php endif; ?>
                <th style="width: 18px; background: #dcfce7; color: #166534;" title="Hadir">H</th>
                <th style="width: 18px; background: #dbeafe; color: #1e40af;" title="Dispensasi">D</th>
                <th style="width: 18px; background: #fef3c7; color: #92400e;" title="Izin">I</th>
                <th style="width: 18px; background: #ede9fe; color: #5b21b6;" title="Sakit">S</th>
                <th style="width: 18px; background: #fee2e2; color: #991b1b;" title="Alpa / Tanpa Keterangan">A</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($students)): ?>
                <tr>
                    <td colspan="<?= 3 + max(1, $totalMapel) + 6 ?>" style="padding: 12px; color: #64748b; font-style: italic;">
                        Tidak ada siswa terdaftar pada kelas ini.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($students as $idx => $st): 
                    $sid = (int)$st['id'];
                    $cH = 0; $cD = 0; $cI = 0; $cS = 0; $cA = 0;
                    foreach ($subjectList as $s) {
                        $mpName = $s['nama'];
                        $code = $attendanceMap[$sid][$mpName] ?? '-';
                        if ($code === 'H') $cH++;
                        elseif ($code === 'D') $cD++;
                        elseif ($code === 'I') $cI++;
                        elseif ($code === 'S') $cS++;
                        elseif ($code === 'A') $cA++;
                    }
                    $persen = $totalMapel > 0 ? round((($cH + $cD) / $totalMapel) * 100, 1) : 0;
                    $persenColor = $persen >= 90 ? '#166534' : ($persen >= 75 ? '#854d0e' : '#991b1b');
                ?>
                    <tr>
                        <td><?= $idx + 1 ?></td>
                        <td><?= htmlspecialchars($st['nis'] ?: '-') ?></td>
                        <td class="text-left"><strong><?= htmlspecialchars($st['nama']) ?></strong></td>
                        <?php if ($totalMapel > 0): ?>
                            <?php foreach ($subjectList as $s): 
                                $mpName = $s['nama'];
                                $code = $attendanceMap[$sid][$mpName] ?? '-';
                                $classCode = ($code !== '-') ? "code-{$code}" : 'code-empty';
                            ?>
                                <td class="<?= $classCode ?>"><?= $code ?></td>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <td style="color: #94a3b8;">-</td>
                        <?php endif; ?>
                        <td style="background: #f0fdf4; font-weight: bold;"><?= $cH ?></td>
                        <td style="background: #eff6ff; font-weight: bold;"><?= $cD ?></td>
                        <td style="background: #fffbeb; font-weight: bold;"><?= $cI ?></td>
                        <td style="background: #faf5ff; font-weight: bold;"><?= $cS ?></td>
                        <td style="background: #fef2f2; font-weight: bold; <?= $cA > 0 ? 'color: #dc2626;' : '' ?>"><?= $cA ?></td>
                        <td style="font-weight: bold; color: <?= $persenColor ?>;"><?= $persen ?>%</td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <?php if ($totalMapel > 0 && !empty($students)): ?>
        <tfoot>
            <tr style="background: #f8fafc; font-weight: bold;">
                <td colspan="3" style="text-align: right; padding-right: 6px;">Total Siswa Hadir / Sesi:</td>
                <?php foreach ($subjectList as $s): ?>
                    <td style="color: #1e40af;"><?= $subjectTotals[$s['nama']] ?? 0 ?></td>
                <?php endforeach; ?>
                <td colspan="6" style="background: #f1f5f9; font-size: 7pt; color: #475569;">Laporan Resmi SAS</td>
            </tr>
        </tfoot>
        <?php endif; ?>
    </table>

    <!-- LEGENDA KETERANGAN -->
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-top: 4px;">
        <div class="legend-box">
            <span style="font-weight: bold; margin-right: 8px;">Keterangan Status:</span>
            <span class="legend-item"><strong style="color: #166534;">H</strong> = Hadir</span>
            <span class="legend-item"><strong style="color: #1e40af;">D</strong> = Dispensasi</span>
            <span class="legend-item"><strong style="color: #92400e;">I</strong> = Izin</span>
            <span class="legend-item"><strong style="color: #5b21b6;">S</strong> = Sakit</span>
            <span class="legend-item"><strong style="color: #991b1b;">A</strong> = Alpa (Tanpa Keterangan)</span>
            <span class="legend-item" style="color: #64748b;">(Rumus: (H+D) ÷ Total Mapel × 100%)</span>
        </div>
    </div>

    <!-- TANDA TANGAN RESMI -->
    <table class="signature-table">
        <tr>
            <td>
                Mengetahui,<br>
                Kepala Sekolah
                <div class="signature-space"></div>
                <strong><u>I Wayan Sudana, S.Pd., M.Pd.</u></strong><br>
                NIP. 197004121995121001
            </td>
            <td>
                Ketua Panitia Ujian SAS,
                <div class="signature-space"></div>
                <strong><u>I Made Rai Supartha, S.Kom.</u></strong><br>
                NIP. 198205162010011021
            </td>
            <td>
                Klungkung, <?= date('d F Y') ?><br>
                Wali Kelas <?= htmlspecialchars($namaKelas) ?>
                <div class="signature-space"></div>
                <strong><u>( .................................................. )</u></strong><br>
                NIP. -
            </td>
        </tr>
    </table>

</body>
</html>
<?php
$html = ob_get_clean();

$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);
$options->set('defaultFont', 'Arial');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

$pdfOutput = $dompdf->output();
$safeFilename = "Rekap_Kehadiran_" . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $namaKelas) . "_{$selectedSemester}.pdf";

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $safeFilename . '"');
header('Content-Length: ' . strlen($pdfOutput));
echo $pdfOutput;
exit;
