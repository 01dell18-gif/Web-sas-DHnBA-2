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

$distinctMapelRows = dbFetchAll("
    SELECT DISTINCT mata_pelajaran 
    FROM absensi 
    WHERE class_id = :cid AND tahun_ajaran = :th AND semester = :sem 
    ORDER BY id ASC
", [
    'cid' => $selectedClassId,
    'th' => $selectedTahun,
    'sem' => $selectedSemester
]);
$subjects = array_column($distinctMapelRows, 'mata_pelajaran');

$absensiSessions = dbFetchAll("
    SELECT id, mata_pelajaran, data_siswa 
    FROM absensi 
    WHERE class_id = :cid AND tahun_ajaran = :th AND semester = :sem
", [
    'cid' => $selectedClassId,
    'th' => $selectedTahun,
    'sem' => $selectedSemester
]);

$attendanceMap = [];
foreach ($absensiSessions as $sess) {
    $mp = $sess['mata_pelajaran'];
    $dataSt = json_decode($sess['data_siswa'] ?? '[]', true) ?: [];
    foreach ($dataSt as $st) {
        $sid = $st['id'] ?? 0;
        $status = $st['status'] ?? 'Hadir';
        $code = 'H';
        if ($status === 'Dispensasi') $code = 'D';
        elseif ($status === 'Izin') $code = 'I';
        elseif ($status === 'Sakit') $code = 'S';
        elseif ($status === 'Tidak Hadir' || $status === 'Alpa') $code = 'A';
        $attendanceMap[$sid][$mp] = $code;
    }
}

$safeFilename = "Rekap_Kehadiran_" . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $namaKelas) . "_{$selectedSemester}.xls";

header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"{$safeFilename}\"");
header("Pragma: no-cache");
header("Expires: 0");
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style>
        table { border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; }
        th, td { border: 1px solid #000; padding: 6px; }
        th { background-color: #e2e8f0; font-weight: bold; text-align: center; }
        .center { text-align: center; }
        .left { text-align: left; }
        .title { font-size: 14pt; font-weight: bold; text-align: center; border: none; }
    </style>
</head>
<body>
    <table>
        <tr>
            <td colspan="<?= 3 + max(1, count($subjects)) + 5 ?>" class="title">
                <?= htmlspecialchars($namaSekolah) ?>
            </td>
        </tr>
        <tr>
            <td colspan="<?= 3 + max(1, count($subjects)) + 5 ?>" class="title" style="font-size: 12pt;">
                REKAPITULASI KEHADIRAN SISWA UJIAN SEMESTER <?= strtoupper(htmlspecialchars($selectedSemester)) ?>
            </td>
        </tr>
        <tr>
            <td colspan="<?= 3 + max(1, count($subjects)) + 5 ?>" class="title" style="font-size: 11pt;">
                TAHUN AJARAN <?= htmlspecialchars($selectedTahun) ?> - KELAS: <?= htmlspecialchars($namaKelas) ?>
            </td>
        </tr>
        <tr><td colspan="<?= 3 + max(1, count($subjects)) + 5 ?>" style="border:none;"></td></tr>
        <thead>
            <tr>
                <th rowspan="2">No</th>
                <th rowspan="2">NIS</th>
                <th rowspan="2">Nama Lengkap Siswa</th>
                <?php if (!empty($subjects)): ?>
                    <th colspan="<?= count($subjects) ?>">Mata Pelajaran</th>
                <?php else: ?>
                    <th>Mata Pelajaran</th>
                <?php endif; ?>
                <th colspan="5">Rekapitulasi</th>
            </tr>
            <tr>
                <?php if (!empty($subjects)): ?>
                    <?php foreach ($subjects as $mp): ?>
                        <th><?= htmlspecialchars($mp) ?></th>
                    <?php endforeach; ?>
                <?php else: ?>
                    <th>-</th>
                <?php endif; ?>
                <th style="background:#ecfdf5;">H</th>
                <th style="background:#eff6ff;">D</th>
                <th style="background:#fffbe3;">I</th>
                <th style="background:#fef2f2;">S</th>
                <th style="background:#fee2e2;">A</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($students as $idx => $st): ?>
                <?php
                $cH = 0; $cD = 0; $cI = 0; $cS = 0; $cA = 0;
                ?>
                <tr>
                    <td class="center"><?= $idx + 1 ?></td>
                    <td class="center">'<?= htmlspecialchars($st['nis']) ?></td>
                    <td class="left"><?= htmlspecialchars($st['nama']) ?></td>
                    <?php if (!empty($subjects)): ?>
                        <?php foreach ($subjects as $mp): ?>
                            <?php
                            $val = $attendanceMap[$st['id']][$mp] ?? 'H';
                            if ($val === 'H') $cH++;
                            elseif ($val === 'D') $cD++;
                            elseif ($val === 'I') $cI++;
                            elseif ($val === 'S') $cS++;
                            elseif ($val === 'A') $cA++;
                            ?>
                            <td class="center"><?= $val ?></td>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <td class="center">-</td>
                    <?php endif; ?>
                    <td class="center" style="font-weight:bold;"><?= $cH ?></td>
                    <td class="center"><?= $cD ?></td>
                    <td class="center"><?= $cI ?></td>
                    <td class="center"><?= $cS ?></td>
                    <td class="center" style="font-weight:bold; color:red;"><?= $cA ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
