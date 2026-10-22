<?php
require_once __DIR__ . '/../config/functions.php';
require_admin();

$selectedClassId = (int)($_GET['class_id'] ?? 0);
$selectedTahun = trim($_GET['tahun_ajaran'] ?? '2026/2027');
$selectedSemester = trim($_GET['semester'] ?? 'Ganjil');

$classInfo = dbFetch("SELECT * FROM kelas WHERE id = :id", ['id' => $selectedClassId]);
$namaKelas = $classInfo['nama_kelas'] ?? "Kelas {$selectedClassId}";
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

$safeFilename = "Rekap_Kehadiran_" . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $namaKelas) . "_{$selectedSemester}.xls";

header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"{$safeFilename}\"");
header("Pragma: no-cache");
header("Expires: 0");
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <!--[if gte mso 9]>
    <xml>
        <x:ExcelWorkbook>
            <x:ExcelWorksheets>
                <x:ExcelWorksheet>
                    <x:Name>Rekap Kehadiran</x:Name>
                    <x:WorksheetOptions>
                        <x:DisplayGridlines/>
                    </x:WorksheetOptions>
                </x:ExcelWorksheet>
            </x:ExcelWorksheets>
        </x:ExcelWorkbook>
    </xml>
    <![endif]-->
    <style>
        table { border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; }
        th, td { border: 1px solid #000000; padding: 6px 8px; font-size: 10pt; }
        th { background-color: #f1f5f9; font-weight: bold; text-align: center; vertical-align: middle; }
        .center { text-align: center; vertical-align: middle; }
        .left { text-align: left; vertical-align: middle; }
        .text-cell { mso-number-format: "\@"; }
        .title-sekolah { font-size: 14pt; font-weight: bold; text-align: center; border: none; }
        .title-laporan { font-size: 12pt; font-weight: bold; text-align: center; border: none; }
        .title-meta { font-size: 11pt; font-weight: bold; text-align: center; border: none; }
        .hadir { background-color: #dcfce7; color: #15803d; font-weight: bold; }
        .dispensasi { background-color: #dbeafe; color: #1d4ed8; font-weight: bold; }
        .izin { background-color: #fef3c7; color: #b45309; font-weight: bold; }
        .sakit { background-color: #ede9fe; color: #6d28d9; font-weight: bold; }
        .alpa { background-color: #fee2e2; color: #b91c1c; font-weight: bold; }
        .empty-val { color: #94a3b8; }
    </style>
</head>
<body>
    <table>
        <tr>
            <td colspan="<?= 3 + max(1, $totalMapel) + 1 ?>" class="title-sekolah">
                <?= htmlspecialchars($namaSekolah) ?>
            </td>
        </tr>
        <tr>
            <td colspan="<?= 3 + max(1, $totalMapel) + 1 ?>" class="title-laporan">
                REKAP KEHADIRAN SISWA - SUMATIF AKHIR SEMESTER (SAS)
            </td>
        </tr>
        <tr>
            <td colspan="<?= 3 + max(1, $totalMapel) + 1 ?>" class="title-meta">
                SEMESTER <?= strtoupper(htmlspecialchars($selectedSemester)) ?> TAHUN AJARAN <?= htmlspecialchars($selectedTahun) ?> - KELAS: <?= htmlspecialchars($namaKelas) ?>
            </td>
        </tr>
        <tr><td colspan="<?= 3 + max(1, $totalMapel) + 1 ?>" style="border: none;"></td></tr>
        <thead>
            <tr>
                <th rowspan="2" style="width: 40px;">NO</th>
                <th rowspan="2" style="width: 100px;">NIS</th>
                <th rowspan="2" style="width: 250px;">NAMA PESERTA DIDIK</th>
                <?php if ($totalMapel > 0): ?>
                    <?php foreach ($subjectList as $s): ?>
                        <th style="min-width: 80px;"><?= htmlspecialchars($s['nama']) ?></th>
                    <?php endforeach; ?>
                <?php else: ?>
                    <th rowspan="2">MATA PELAJARAN</th>
                <?php endif; ?>
                <th rowspan="2" style="width: 120px;">PERSENTASE KEHADIRAN</th>
            </tr>
            <?php if ($totalMapel > 0): ?>
                <tr>
                    <?php foreach ($subjectList as $s): ?>
                        <th style="font-size: 9pt; background-color: #f8fafc;"><?= htmlspecialchars($s['tanggal']) ?></th>
                    <?php endforeach; ?>
                </tr>
            <?php endif; ?>
        </thead>
        <tbody>
            <?php if (empty($students)): ?>
                <tr>
                    <td colspan="<?= 3 + max(1, $totalMapel) + 1 ?>" class="center" style="padding: 15px; color: #64748b;">
                        Tidak ada data siswa terdaftar di kelas <?= htmlspecialchars($namaKelas) ?>.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($students as $idx => $st): ?>
                    <?php
                    $cH = 0; $cD = 0; $cI = 0; $cS = 0; $cA = 0;
                    ?>
                    <tr>
                        <td class="center"><?= $idx + 1 ?></td>
                        <td class="center text-cell"><?= htmlspecialchars($st['nis'] ?: '-') ?></td>
                        <td class="left"><?= htmlspecialchars($st['nama']) ?></td>
                        <?php if ($totalMapel > 0): ?>
                            <?php foreach ($subjectList as $s): ?>
                                <?php
                                $val = $attendanceMap[$st['id']][$s['nama']] ?? null;
                                if ($val === 'H') {
                                    $cH++;
                                    $class = 'hadir';
                                } elseif ($val === 'D') {
                                    $cD++;
                                    $class = 'dispensasi';
                                } elseif ($val === 'I') {
                                    $cI++;
                                    $class = 'izin';
                                } elseif ($val === 'S') {
                                    $cS++;
                                    $class = 'sakit';
                                } elseif ($val === 'A') {
                                    $cA++;
                                    $class = 'alpa';
                                } else {
                                    $class = 'empty-val';
                                    $val = '-';
                                }
                                ?>
                                <td class="center <?= $class ?>"><?= $val ?></td>
                            <?php endforeach; ?>
                            <?php
                            $pct = $totalMapel > 0 ? round((($cH + $cD) / $totalMapel) * 100) . '%' : '0%';
                            ?>
                            <td class="center" style="font-weight: bold;"><?= $pct ?></td>
                        <?php else: ?>
                            <td class="center empty-val">-</td>
                            <td class="center" style="font-weight: bold;">-</td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    <br/>
    <table style="border: none; font-size: 9pt;">
        <tr>
            <td style="border: none; font-weight: bold;" colspan="4">Keterangan Status Kehadiran:</td>
        </tr>
        <tr>
            <td style="border: none;"><strong style="color:#15803d;">H</strong> = Hadir</td>
            <td style="border: none;"><strong style="color:#1d4ed8;">D</strong> = Dispensasi</td>
            <td style="border: none;"><strong style="color:#b45309;">I</strong> = Izin</td>
            <td style="border: none;"><strong style="color:#6d28d9;">S</strong> = Sakit</td>
            <td style="border: none;"><strong style="color:#b91c1c;">A</strong> = Alpa / Tidak Hadir</td>
        </tr>
        <tr>
            <td style="border: none; color: #64748b;" colspan="5">* Formula Persentase Kehadiran = (Hadir + Dispensasi) / Total Mata Pelajaran &times; 100%</td>
        </tr>
    </table>
</body>
</html>
