<?php
$pageTitle = 'Laporan Rekap Kehadiran Siswa';
require_once __DIR__ . '/layout_header.php';

$classes = dbFetchAll("SELECT * FROM kelas ORDER BY tingkat DESC, nama_kelas ASC");
$selectedClassId = (int)($_GET['class_id'] ?? ($classes[0]['id'] ?? 15));
$selectedTahun = trim($_GET['tahun_ajaran'] ?? '2026/2027');
$selectedSemester = trim($_GET['semester'] ?? 'Ganjil');

$classInfo = dbFetch("SELECT * FROM kelas WHERE id = :id", ['id' => $selectedClassId]);
$namaKelas = $classInfo['nama_kelas'] ?? "Kelas {$selectedClassId}";

// Fetch all students in this class
$students = dbFetchAll("SELECT * FROM siswa WHERE class_id = :cid ORDER BY nama ASC", ['cid' => $selectedClassId]);

// Fetch distinct subjects tested in this class
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

// Fetch attendance sessions for this class
$absensiSessions = dbFetchAll("
    SELECT id, mata_pelajaran, data_siswa 
    FROM absensi 
    WHERE class_id = :cid AND tahun_ajaran = :th AND semester = :sem
", [
    'cid' => $selectedClassId,
    'th' => $selectedTahun,
    'sem' => $selectedSemester
]);

// Map student attendance per subject: $attendanceMap[student_id][mapel] = 'H' / 'I' / 'S' / 'A' / 'D'
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
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 1.25rem; font-weight: 700; color: var(--slate-900); margin-bottom: 0.25rem;">
            📑 Laporan Rekap Kehadiran Siswa
        </h1>
        <p style="font-size: 0.8rem; color: var(--slate-600);">
            Rekapitulasi kehadiran ujian seluruh mata pelajaran per kelas (Format Resmi & Ekspor Excel).
        </p>
    </div>
    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
        <a href="/admin/export-rekap-excel.php?class_id=<?= $selectedClassId ?>&tahun_ajaran=<?= urlencode($selectedTahun) ?>&semester=<?= urlencode($selectedSemester) ?>" 
           class="btn btn-success" title="Download Spreadsheet Excel">
            📊 Ekspor Excel (.xlsx)
        </a>
        <a href="/absensi.php" target="_blank" class="btn btn-primary">
            ➕ Input / Buat Absensi Baru
        </a>
    </div>
</div>

<!-- Filter Kelas & Periode -->
<div class="card" style="margin-bottom: 0.75rem;">
    <div class="card-body" style="padding: 1.25rem;">
        <form method="GET" action="/admin/rekap-kehadiran.php" style="display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 200px;">
                <label class="form-label" for="filterClassId">Pilih Kelas</label>
                <select name="class_id" id="filterClassId" class="form-select" onchange="this.form.submit()">
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $selectedClassId == $c['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['nama_kelas']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="width: 170px;">
                <label class="form-label" for="filterTahun">Tahun Ajaran</label>
                <input type="text" name="tahun_ajaran" id="filterTahun" class="form-control" 
                       value="<?= htmlspecialchars($selectedTahun) ?>" placeholder="2026/2027" onchange="this.form.submit()">
            </div>

            <div style="width: 140px;">
                <label class="form-label" for="filterSemester">Semester</label>
                <select name="semester" id="filterSemester" class="form-select" onchange="this.form.submit()">
                    <option value="Ganjil" <?= $selectedSemester === 'Ganjil' ? 'selected' : '' ?>>Ganjil</option>
                    <option value="Genap" <?= $selectedSemester === 'Genap' ? 'selected' : '' ?>>Genap</option>
                </select>
            </div>

            <div>
                <button type="submit" class="btn btn-secondary">
                    🔍 Tampilkan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Tampilan Lembar Rekap Matrix -->
<div class="card">
    <div class="card-body" style="padding: 1.5rem;">
        <div style="text-align: center; margin-bottom: 0.75rem; text-transform: uppercase;">
            <h2 style="font-size: 1.25rem; font-weight: 800; color: #000; margin: 0 0 4px 0; letter-spacing: 0.5px;">
                REKAP KEHADIRAN SISWA
            </h2>
            <h3 style="font-size: 1.1rem; font-weight: 700; color: #000; margin: 0 0 4px 0;">
                SUMATIF AKHIR SEMESTER (SAS)
            </h3>
            <h4 style="font-size: 1rem; font-weight: 700; color: #000; margin: 0 0 16px 0;">
                SEMESTER <?= strtoupper(htmlspecialchars($selectedSemester)) ?> TAHUN AJAR <?= htmlspecialchars($selectedTahun) ?>
            </h4>
            <div style="text-align: left; font-weight: 700; font-size: 0.95rem; color: #000;">
                KELAS : <?= htmlspecialchars($namaKelas) ?>
            </div>
        </div>

        <div class="table-responsive" style="border: 1px solid #000;">
            <table class="table" style="width: 100%; border-collapse: collapse; margin-bottom: 0; font-size: 0.825rem; text-align: center;">
                <thead>
                    <tr style="background: #f1f5f9; border-bottom: 2px solid #000;">
                        <th style="border: 1px solid #000; width: 35px; vertical-align: middle;" rowspan="2">No</th>
                        <th style="border: 1px solid #000; width: 85px; vertical-align: middle;" rowspan="2">NIS</th>
                        <th style="border: 1px solid #000; text-align: left; vertical-align: middle; min-width: 220px;" rowspan="2">Nama Peserta Didik</th>
                        <?php if (!empty($subjects)): ?>
                            <th style="border: 1px solid #000;" colspan="<?= count($subjects) ?>">Mata Pelajaran Ujian</th>
                        <?php else: ?>
                            <th style="border: 1px solid #000;">Mata Pelajaran</th>
                        <?php endif; ?>
                        <th style="border: 1px solid #000;" colspan="5">Rekapitulasi</th>
                    </tr>
                    <tr style="background: #f8fafc;">
                        <?php if (!empty($subjects)): ?>
                            <?php foreach ($subjects as $mp): ?>
                                <th style="border: 1px solid #000; font-size: 0.725rem; max-width: 90px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= htmlspecialchars($mp) ?>">
                                    <?= htmlspecialchars(mb_substr($mp, 0, 12)) ?>..
                                </th>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <th style="border: 1px solid #000; font-size: 0.75rem; color: var(--text-muted);">(Belum ada mapel)</th>
                        <?php endif; ?>
                        <th style="border: 1px solid #000; width: 32px; background: #ecfdf5; color: #065f46;">H</th>
                        <th style="border: 1px solid #000; width: 32px; background: #eff6ff; color: #1e40af;">D</th>
                        <th style="border: 1px solid #000; width: 32px; background: #fffbe3; color: #854d0e;">I</th>
                        <th style="border: 1px solid #000; width: 32px; background: #fef2f2; color: #991b1b;">S</th>
                        <th style="border: 1px solid #000; width: 32px; background: #fee2e2; color: #b91c1c;">A</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($students)): ?>
                        <tr>
                            <td colspan="<?= 3 + max(1, count($subjects)) + 5 ?>" style="padding: 2rem; color: var(--text-muted);">
                                Tidak ada siswa terdaftar pada kelas ini.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($students as $idx => $st): ?>
                            <?php
                            $cH = 0; $cD = 0; $cI = 0; $cS = 0; $cA = 0;
                            ?>
                            <tr>
                                <td style="border: 1px solid #000;"><?= $idx + 1 ?></td>
                                <td style="border: 1px solid #000;"><?= htmlspecialchars($st['nis'] ?: '-') ?></td>
                                <td style="border: 1px solid #000; text-align: left; font-weight: 500;">
                                    <?= htmlspecialchars($st['nama']) ?>
                                </td>
                                <?php if (!empty($subjects)): ?>
                                    <?php foreach ($subjects as $mp): ?>
                                        <?php
                                        $val = $attendanceMap[$st['id']][$mp] ?? 'H'; // Default to H if attended
                                        if ($val === 'H') $cH++;
                                        elseif ($val === 'D') $cD++;
                                        elseif ($val === 'I') $cI++;
                                        elseif ($val === 'S') $cS++;
                                        elseif ($val === 'A') $cA++;
                                        ?>
                                        <td style="border: 1px solid #000; font-weight: <?= $val !== 'H' ? 'bold' : 'normal' ?>; color: <?= $val === 'A' ? '#dc2626' : ($val === 'H' ? '#000' : '#d97706') ?>;">
                                            <?= $val ?>
                                        </td>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <td style="border: 1px solid #000; color: var(--text-muted);">-</td>
                                <?php endif; ?>
                                <td style="border: 1px solid #000; background: #f0fdf4; font-weight: 600;"><?= $cH ?></td>
                                <td style="border: 1px solid #000;"><?= $cD ?></td>
                                <td style="border: 1px solid #000;"><?= $cI ?></td>
                                <td style="border: 1px solid #000;"><?= $cS ?></td>
                                <td style="border: 1px solid #000; color: <?= $cA > 0 ? '#dc2626' : '#000' ?>; font-weight: <?= $cA > 0 ? '700' : '400' ?>;"><?= $cA ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div style="margin-top: 1rem; font-size: 0.8rem; color: var(--slate-600); display: flex; gap: 1rem; flex-wrap: wrap;">
            <span><strong>Keterangan:</strong></span>
            <span><strong style="color: #16a34a;">H</strong> = Hadir</span>
            <span><strong style="color: #2563eb;">D</strong> = Dispensasi</span>
            <span><strong style="color: #d97706;">I</strong> = Izin</span>
            <span><strong style="color: #ea580c;">S</strong> = Sakit</span>
            <span><strong style="color: #dc2626;">A</strong> = Alpa / Tidak Hadir</span>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
