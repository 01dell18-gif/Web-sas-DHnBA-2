<?php
require_once __DIR__ . '/../config/functions.php';
require_admin();

$pageTitle = 'Laporan Rekap Kehadiran Siswa';

// Handle Simpan / Perbarui Absensi Mapel
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_subject_attendance') {
    verify_csrf();
    $targetCid = (int)($_POST['class_id'] ?? 0);
    $tahun = trim($_POST['tahun_ajaran'] ?? '2026/2027');
    $semester = trim($_POST['semester'] ?? 'Ganjil');
    $mapel = trim($_POST['mata_pelajaran'] ?? '');
    $tanggal = trim($_POST['tanggal_ujian'] ?? date('Y-m-d'));
    $studentsInput = $_POST['students'] ?? [];

    if ($targetCid > 0 && !empty($mapel)) {
        $classInfo = dbFetch("SELECT * FROM kelas WHERE id = :id", ['id' => $targetCid]);
        $namaKelas = $classInfo['nama_kelas'] ?? "Kelas {$targetCid}";
        $tingkat = $classInfo['tingkat'] ?? 'XII';

        $allStudents = dbFetchAll("SELECT * FROM siswa WHERE class_id = :cid ORDER BY nama ASC", ['cid' => $targetCid]);
        $dataSiswa = [];
        $cH = 0; $cD = 0; $cI = 0; $cS = 0; $cA = 0;

        foreach ($allStudents as $st) {
            $sid = (int)$st['id'];
            $status = $studentsInput[$sid] ?? 'Hadir';
            if ($status === 'Hadir') $cH++;
            elseif ($status === 'Dispensasi') $cD++;
            elseif ($status === 'Izin') $cI++;
            elseif ($status === 'Sakit') $cS++;
            else $cA++;

            $dataSiswa[] = [
                'id' => $sid,
                'nis' => $st['nis'],
                'nama' => $st['nama'],
                'status' => $status,
                'catatan' => ''
            ];
        }
        $totalPeserta = count($allStudents);

        // Cek apakah sesi mapel ini sudah ada sebelumnya pada kelas dan periode yang sama
        $existing = dbFetch("
            SELECT id FROM absensi 
            WHERE class_id = :cid AND tahun_ajaran = :th AND semester = :sem AND mata_pelajaran = :mp
        ", [
            'cid' => $targetCid,
            'th' => $tahun,
            'sem' => $semester,
            'mp' => $mapel
        ]);

        if ($existing) {
            dbUpdate('absensi', [
                'tanggal_ujian' => $tanggal,
                'peserta_hadir' => $cH,
                'peserta_dispensasi' => $cD,
                'peserta_izin' => $cI,
                'peserta_sakit' => $cS,
                'peserta_tidak_hadir' => $cA,
                'total_peserta' => $totalPeserta,
                'data_siswa' => json_encode($dataSiswa),
                'updated_at' => date('Y-m-d H:i:s')
            ], 'id = :id', ['id' => $existing['id']]);
            log_activity('UPDATE_MAPEL_ABSENSI', "Memperbarui absensi mapel {$mapel} kelas {$namaKelas}");
            set_flash('success', "Absensi mata pelajaran {$mapel} untuk {$namaKelas} berhasil diperbarui.");
        } else {
            dbInsert('absensi', [
                'nama_ujian' => "Sumatif Akhir Semester (SAS) {$semester}",
                'tahun_ajaran' => $tahun,
                'semester' => $semester,
                'tingkat' => $tingkat,
                'class_id' => $targetCid,
                'nama_kelas' => $namaKelas,
                'mata_pelajaran' => $mapel,
                'sesi' => '1',
                'ruang' => $namaKelas,
                'tanggal_ujian' => $tanggal,
                'jam_mulai' => '07:30',
                'jam_selesai' => '09:30',
                'peserta_hadir' => $cH,
                'peserta_dispensasi' => $cD,
                'peserta_izin' => $cI,
                'peserta_sakit' => $cS,
                'peserta_tidak_hadir' => $cA,
                'total_peserta' => $totalPeserta,
                'nama_pengawas_1' => '-',
                'nip_pengawas_1' => '',
                'nama_pengawas_2' => '',
                'nip_pengawas_2' => '',
                'signature_mode' => 'manual',
                'pdf_path' => '',
                'file_size' => 0,
                'data_siswa' => json_encode($dataSiswa),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            log_activity('INPUT_MAPEL_ABSENSI', "Menambahkan absensi mapel {$mapel} untuk {$namaKelas}");
            set_flash('success', "Absensi mata pelajaran {$mapel} untuk {$namaKelas} berhasil ditambahkan ke rekap.");
        }
    }
    header("Location: /admin/rekap-kehadiran.php?class_id={$targetCid}&tahun_ajaran=" . urlencode($tahun) . "&semester=" . urlencode($semester));
    exit;
}

// Handle Hapus Mapel Tertentu dari Rekap
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_subject') {
    verify_csrf();
    $sessionId = (int)($_POST['session_id'] ?? 0);
    $targetCid = (int)($_GET['class_id'] ?? 0);
    if ($sessionId > 0) {
        $sess = dbFetch("SELECT * FROM absensi WHERE id = :id", ['id' => $sessionId]);
        if ($sess) {
            if (!empty($sess['pdf_path'])) {
                $f = BASE_PATH . '/' . $sess['pdf_path'];
                if (file_exists($f)) @unlink($f);
            }
            dbDelete('absensi', 'id = :id', ['id' => $sessionId]);
            log_activity('DELETE_SUBJECT_ABSENSI', "Menghapus mapel {$sess['mata_pelajaran']} dari rekap kelas {$sess['nama_kelas']}");
            set_flash('success', "Mata pelajaran {$sess['mata_pelajaran']} berhasil dihapus dari rekap kelas.");
            $targetCid = (int)$sess['class_id'];
        }
    }
    header("Location: /admin/rekap-kehadiran.php?class_id={$targetCid}");
    exit;
}

// Handle Reset Presensi Kelas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_class_absensi') {
    verify_csrf();
    $targetCid = (int)($_POST['target_class_id'] ?? 0);
    if ($targetCid > 0) {
        $docs = dbFetchAll("SELECT id, pdf_path FROM absensi WHERE class_id = :cid", ['cid' => $targetCid]);
        foreach ($docs as $d) {
            if (!empty($d['pdf_path'])) {
                $f = BASE_PATH . '/' . $d['pdf_path'];
                if (file_exists($f)) @unlink($f);
            }
        }
        dbDelete('absensi', 'class_id = :cid', ['cid' => $targetCid]);
        $count = count($docs);
        log_activity('CLEAR_CLASS_ABSENSI', "Menghapus seluruh presensi kelas ID #{$targetCid} ({$count} dokumen)");
        set_flash('success', "Seluruh presensi pada kelas terpilih ({$count} dokumen) berhasil dikosongkan.");
    }
    header('Location: /admin/rekap-kehadiran.php?class_id=' . $targetCid);
    exit;
}

$classes = dbFetchAll("SELECT * FROM kelas ORDER BY tingkat DESC, nama_kelas ASC");
$selectedClassId = (int)($_GET['class_id'] ?? ($classes[0]['id'] ?? 15));
$selectedTahun = trim($_GET['tahun_ajaran'] ?? '2026/2027');
$selectedSemester = trim($_GET['semester'] ?? 'Ganjil');

$classInfo = dbFetch("SELECT * FROM kelas WHERE id = :id", ['id' => $selectedClassId]);
$namaKelas = $classInfo['nama_kelas'] ?? "Kelas {$selectedClassId}";

// Ambil seluruh siswa di kelas ini
$students = dbFetchAll("SELECT * FROM siswa WHERE class_id = :cid ORDER BY nama ASC", ['cid' => $selectedClassId]);

// Ambil seluruh sesi ujian kelas ini untuk mapel dan presensi
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

// Datalist rekomendasi mapel dari database
$allKnownMapels = dbFetchAll("SELECT DISTINCT mata_pelajaran FROM absensi WHERE mata_pelajaran IS NOT NULL AND mata_pelajaran != '' ORDER BY mata_pelajaran ASC");

require_once __DIR__ . '/layout_header.php';
?>

<div class="page-header-card">
    <div class="page-header-content">
        <div class="page-header-icon-box">
            <i class="bi bi-card-checklist"></i>
        </div>
        <div>
            <h1 class="page-header-title">📑 Laporan Rekap Kehadiran Siswa</h1>
            <p class="page-header-subtitle">
                Rekapitulasi kehadiran ujian seluruh mata pelajaran per kelas (Format Resmi & Ekspor Excel).
            </p>
        </div>
    </div>
    <div class="page-header-actions" style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
        <a href="/admin/export-rekap-pdf.php?class_id=<?= $selectedClassId ?>&tahun_ajaran=<?= urlencode($selectedTahun) ?>&semester=<?= urlencode($selectedSemester) ?>" 
           target="_blank" class="btn btn-secondary btn-sm" title="Pratinjau & Cetak Laporan PDF di Tab Baru">
            <i class="bi bi-file-earmark-pdf-fill" style="color: #ef4444;"></i> <span>Preview PDF (Cetak)</span>
        </a>
        <a href="/admin/export-rekap-excel.php?class_id=<?= $selectedClassId ?>&tahun_ajaran=<?= urlencode($selectedTahun) ?>&semester=<?= urlencode($selectedSemester) ?>" 
           class="btn btn-success btn-sm" title="Download Spreadsheet Excel">
            <i class="bi bi-file-earmark-excel"></i> <span>Ekspor Excel (.xls)</span>
        </a>
        <button type="button" class="btn btn-primary btn-sm" onclick="openInputModal()">
            <i class="bi bi-plus-circle-fill"></i> <span>Input / Perbarui Mapel</span>
        </button>
        <?php if (!empty($subjectList)): ?>
            <button type="button" class="btn btn-danger-soft btn-sm" onclick="confirmResetClassAbsensi(<?= $selectedClassId ?>, '<?= htmlspecialchars($namaKelas, ENT_QUOTES) ?>')" title="Kosongkan data presensi kelas ini">
                <i class="bi bi-trash3-fill"></i> <span>Hapus Presensi Kelas Ini</span>
            </button>
        <?php endif; ?>
    </div>
</div>

<form id="formResetClassAbsensi" method="POST" action="/admin/rekap-kehadiran.php" style="display: none;">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="delete_class_absensi">
    <input type="hidden" name="target_class_id" value="<?= $selectedClassId ?>">
</form>

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
                <div style="position: relative;">
                    <input type="text" name="tahun_ajaran" id="filterTahun" class="form-control" 
                           value="<?= htmlspecialchars($selectedTahun) ?>" placeholder="2026/2027" list="listTahunAjaran" onchange="this.form.submit()">
                    <datalist id="listTahunAjaran">
                        <option value="2026/2027"></option>
                        <option value="2025/2026"></option>
                    </datalist>
                </div>
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

<!-- Tampilan Lembar Rekap Matrix Sesuai Format Resmi Gambar -->
<div class="card">
    <div class="card-body" style="padding: 1.5rem;">
        <!-- Kop Judul Laporan -->
        <div style="text-align: center; margin-bottom: 1.5rem; text-transform: uppercase;">
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

        <!-- Tabel Matrix Rekap Kehadiran -->
        <div class="table-responsive" style="border: 1px solid #000;">
            <table class="table" style="width: 100%; border-collapse: collapse; margin-bottom: 0; font-size: 0.825rem; text-align: center;">
                <thead>
                    <tr style="background-color: #f8fafc; border-bottom: 1px solid #000;">
                        <th rowspan="2" style="width: 45px; text-align: center; vertical-align: middle; border: 1px solid #000; font-weight: 700;">NO</th>
                        <th rowspan="2" style="width: 90px; text-align: center; vertical-align: middle; border: 1px solid #000; font-weight: 700;">NIS</th>
                        <th rowspan="2" style="text-align: left; vertical-align: middle; border: 1px solid #000; font-weight: 700; min-width: 180px; padding-left: 10px;">NAMA</th>
                        
                        <?php if (!empty($subjectList)): ?>
                            <?php foreach ($subjectList as $s): ?>
                                <th style="text-align: center; vertical-align: bottom; border: 1px solid #000; min-width: 65px; max-width: 85px; padding: 6px 4px; font-weight: 700;">
                                    <div style="writing-mode: vertical-rl; transform: rotate(180deg); margin: 6px auto; white-space: nowrap; font-size: 0.8rem; max-height: 120px;">
                                        <?= htmlspecialchars($s['nama']) ?>
                                    </div>
                                </th>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <th rowspan="2" style="border: 1px solid #000; color: #64748b; font-style: italic; vertical-align: middle;">
                                (Belum ada mata pelajaran terdaftar untuk kelas ini)
                            </th>
                        <?php endif; ?>

                        <th rowspan="2" style="width: 110px; text-align: center; vertical-align: middle; border: 1px solid #000; font-weight: 700;">
                            <div style="writing-mode: vertical-rl; transform: rotate(180deg); margin: 6px auto; white-space: nowrap; font-size: 0.8rem; max-height: 130px;">
                                PERSENTASE KEHADIRAN
                            </div>
                        </th>
                    </tr>

                    <?php if (!empty($subjectList)): ?>
                        <tr style="background-color: #f8fafc; border-bottom: 1px solid #000;">
                            <?php foreach ($subjectList as $s): ?>
                                <th style="font-size: 0.725rem; font-weight: 600; text-align: center; border: 1px solid #000; padding: 4px; white-space: nowrap;">
                                    <?= htmlspecialchars($s['tanggal']) ?>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    <?php endif; ?>
                </thead>
                <tbody>
                    <?php if (empty($students)): ?>
                        <tr>
                            <td colspan="<?= 3 + max(1, count($subjectList)) + 1 ?>" style="padding: 2rem; color: var(--text-muted);">
                                Tidak ada peserta didik yang terdaftar di kelas <?= htmlspecialchars($namaKelas) ?>.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($students as $idx => $st): ?>
                            <?php
                            $cH = 0; $cD = 0; $cI = 0; $cS = 0; $cA = 0;
                            ?>
                            <tr style="border-bottom: 1px solid #000;">
                                <td style="text-align: center; border: 1px solid #000; font-weight: 600;"><?= $idx + 1 ?></td>
                                <td style="text-align: center; border: 1px solid #000;"><?= htmlspecialchars($st['nis'] ?: '-') ?></td>
                                <td style="text-align: left; border: 1px solid #000; padding-left: 10px; font-weight: 500; white-space: nowrap;">
                                    <?= htmlspecialchars($st['nama']) ?>
                                </td>

                                <?php if (!empty($subjectList)): ?>
                                    <?php foreach ($subjectList as $s): ?>
                                        <?php
                                        $val = $attendanceMap[$st['id']][$s['nama']] ?? null;
                                        if ($val === 'H') {
                                            $cH++;
                                            $style = 'color: #15803d; background-color: #dcfce7;';
                                            $titleText = 'Hadir';
                                        } elseif ($val === 'D') {
                                            $cD++;
                                            $style = 'color: #1d4ed8; background-color: #dbeafe;';
                                            $titleText = 'Dispensasi';
                                        } elseif ($val === 'I') {
                                            $cI++;
                                            $style = 'color: #b45309; background-color: #fef3c7;';
                                            $titleText = 'Izin';
                                        } elseif ($val === 'S') {
                                            $cS++;
                                            $style = 'color: #6d28d9; background-color: #ede9fe;';
                                            $titleText = 'Sakit';
                                        } elseif ($val === 'A') {
                                            $cA++;
                                            $style = 'color: #b91c1c; background-color: #fee2e2;';
                                            $titleText = 'Alpa / Tidak Hadir';
                                        } else {
                                            $style = 'color: #94a3b8; background-color: transparent;';
                                            $titleText = 'Belum Ada Data';
                                            $val = '-';
                                        }
                                        ?>
                                        <td style="text-align: center; border: 1px solid #000; font-weight: 700; <?= $style ?>" 
                                            title="<?= htmlspecialchars($st['nama'] . ' - ' . $s['nama'] . ': ' . $titleText) ?>">
                                            <?= $val ?>
                                        </td>
                                    <?php endforeach; ?>

                                    <?php
                                    $totalM = count($subjectList);
                                    $percent = $totalM > 0 ? round((($cH + $cD) / $totalM) * 100) . '%' : '0%';
                                    ?>
                                    <td style="text-align: center; border: 1px solid #000; font-weight: 700;">
                                        <?= $percent ?>
                                    </td>
                                <?php else: ?>
                                    <td style="text-align: center; border: 1px solid #000; color: #94a3b8;">-</td>
                                    <td style="text-align: center; border: 1px solid #000; font-weight: 700;">-</td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Legenda Kode Kehadiran Sesuai Gambar -->
        <div style="margin-top: 1.25rem; display: flex; gap: 1.25rem; font-size: 0.85rem; flex-wrap: wrap; align-items: center;">
            <span style="font-weight: 700; color: var(--slate-700);">Keterangan Status:</span>
            <span><strong style="color: #15803d; background: #dcfce7; padding: 2px 8px; border-radius: 4px;">H</strong> = Hadir</span>
            <span><strong style="color: #b45309; background: #fef3c7; padding: 2px 8px; border-radius: 4px;">D</strong> = Dispensasi</span>
            <span><strong style="color: #0369a1; background: #e0f2fe; padding: 2px 8px; border-radius: 4px;">I</strong> = Izin</span>
            <span><strong style="color: #6d28d9; background: #ede9fe; padding: 2px 8px; border-radius: 4px;">S</strong> = Sakit</span>
            <span><strong style="color: #b91c1c; background: #fee2e2; padding: 2px 8px; border-radius: 4px;">A</strong> = Alpa / Tidak Hadir</span>
            <span style="margin-left: auto; color: var(--slate-500); font-size: 0.8rem;">
                * Persentase Kehadiran = (Hadir + Dispensasi) / Total Mapel &times; 100%
            </span>
        </div>

        <!-- Daftar Kelola Sesi Mapel (Chips dengan tombol hapus ❌) -->
        <?php if (!empty($subjectList)): ?>
            <div style="margin-top: 2rem; border-top: 1px solid var(--border); padding-top: 1.25rem;">
                <h5 style="font-size: 0.95rem; font-weight: 700; margin-bottom: 0.75rem; color: var(--slate-800);">
                    Kelola Mata Pelajaran Terdaftar (<?= count($subjectList) ?> Mapel)
                </h5>
                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    <?php foreach ($subjectList as $s): ?>
                        <div style="background: var(--slate-100); border: 1px solid var(--border); padding: 0.4rem 0.75rem; border-radius: var(--radius-sm); display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem;">
                            <strong><?= htmlspecialchars($s['nama']) ?></strong>
                            <span style="color: var(--slate-500); font-size: 0.75rem;">(<?= htmlspecialchars($s['tanggal']) ?>)</span>
                            <form method="POST" action="/admin/rekap-kehadiran.php?class_id=<?= $selectedClassId ?>" style="display: inline;" onsubmit="return confirm('Hapus mapel <?= htmlspecialchars($s['nama'], ENT_QUOTES) ?> dari rekap kelas ini?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete_subject">
                                <input type="hidden" name="session_id" value="<?= $s['id'] ?>">
                                <button type="submit" style="background: none; border: none; color: var(--danger); cursor: pointer; font-size: 0.85rem;" title="Hapus Mapel Ini">
                                    ❌
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- MODAL INPUT / EDIT ABSENSI MAPEL (TOP ALIGNED) -->
<div id="modalInputMapel" class="custom-modal">
    <div style="background: #fff; max-width: 650px; width: 100%; border-radius: var(--radius-md); box-shadow: var(--shadow-lg); max-height: 85vh; display: flex; flex-direction: column; margin-top: 15px; margin-bottom: 2rem; overflow: hidden;">
        <div style="padding: 1rem 1.25rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
            <h3 style="font-size: 1.1rem; font-weight: 700; margin: 0; color: var(--slate-900);">
                Input / Perbarui Absensi Mapel
            </h3>
            <button type="button" onclick="closeInputModal()" style="border: none; background: transparent; font-size: 1.25rem; cursor: pointer; color: var(--slate-600);">&times;</button>
        </div>
        <form method="POST" action="/admin/rekap-kehadiran.php?class_id=<?= $selectedClassId ?>" style="display: flex; flex-direction: column; overflow: hidden; height: 100%;">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save_subject_attendance">
            <input type="hidden" name="class_id" value="<?= $selectedClassId ?>">
            <input type="hidden" name="tahun_ajaran" value="<?= htmlspecialchars($selectedTahun) ?>">
            <input type="hidden" name="semester" value="<?= htmlspecialchars($selectedSemester) ?>">

            <div style="padding: 1.25rem; overflow-y: auto; flex: 1;">
                <div class="alert alert-info" style="font-size: 0.85rem; margin-bottom: 1rem;">
                    💡 <strong>Otomatis Valid:</strong> Jika nama mata pelajaran sama (misal: "Matematika"), sistem akan langsung memperbarui data yang sudah ada dan tidak membuat kolom ganda.
                </div>

                <div class="grid-2" style="margin-bottom: 1rem; display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <label class="form-label" for="inpMapel">Nama Mata Pelajaran <span class="required" style="color: var(--danger);">*</span></label>
                        <input type="text" name="mata_pelajaran" id="inpMapel" class="form-control" 
                               placeholder="Contoh: Matematika" required list="listMapel">
                        <datalist id="listMapel">
                            <?php foreach ($allKnownMapels as $km): ?>
                                <option value="<?= htmlspecialchars($km['mata_pelajaran']) ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    <div>
                        <label class="form-label" for="inpTgl">Tanggal Ujian <span class="required" style="color: var(--danger);">*</span></label>
                        <input type="date" name="tanggal_ujian" id="inpTgl" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                    <label class="form-label" style="margin: 0; font-weight: 600;">Presensi Siswa Kelas <?= htmlspecialchars($namaKelas) ?>:</label>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="setAllModalStatus('Hadir')">
                        ✅ Set Semua Hadir
                    </button>
                </div>

                <div style="max-height: 280px; overflow-y: auto; border: 1px solid var(--border); border-radius: var(--radius-sm);">
                    <table class="table" style="font-size: 0.825rem; margin-bottom: 0;">
                        <thead>
                            <tr style="position: sticky; top: 0; background: #fff; z-index: 1;">
                                <th style="width: 35px; text-align: center;">No</th>
                                <th>Nama Siswa</th>
                                <th style="width: 150px; text-align: center;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($students)): ?>
                                <tr>
                                    <td colspan="3" style="text-align: center; color: var(--text-muted); padding: 1.5rem;">
                                        Tidak ada siswa terdaftar di kelas ini.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($students as $sIdx => $st): ?>
                                    <tr>
                                        <td style="text-align: center;"><?= $sIdx + 1 ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($st['nama']) ?></strong>
                                            <div style="font-size: 0.75rem; color: var(--slate-500);"><?= htmlspecialchars($st['nis'] ?: '-') ?></div>
                                        </td>
                                        <td>
                                            <select name="students[<?= $st['id'] ?>]" class="form-select form-select-sm modal-status-select">
                                                <option value="Hadir" selected>Hadir (H)</option>
                                                <option value="Dispensasi">Dispensasi (D)</option>
                                                <option value="Izin">Izin (I)</option>
                                                <option value="Sakit">Sakit (S)</option>
                                                <option value="Tidak Hadir">Tidak Hadir (A)</option>
                                            </select>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div style="padding: 1rem 1.25rem; background: var(--slate-50); border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 0.5rem;">
                <button type="button" class="btn btn-secondary" onclick="closeInputModal()">Batal</button>
                <button type="submit" class="btn btn-primary">💾 Simpan Absensi Mapel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openInputModal() {
    const modal = document.getElementById('modalInputMapel');
    if (modal) modal.style.display = 'flex';
}

function closeInputModal() {
    const modal = document.getElementById('modalInputMapel');
    if (modal) modal.style.display = 'none';
}

function setAllModalStatus(status) {
    document.querySelectorAll('.modal-status-select').forEach(sel => {
        sel.value = status;
    });
}

function confirmResetClassAbsensi(cid, className) {
    showConfirmResetModal(
        'formResetClassAbsensi',
        'Hapus Presensi Kelas ' + className,
        'PERINGATAN: Tindakan ini akan MENGHAPUS SELURUH data presensi/daftar hadir kelas ' + className + ' dari sistem.'
    );
}
</script>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
