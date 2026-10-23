<?php
require_once __DIR__ . '/../config/functions.php';
require_admin();

$pageTitle = 'Kelola Data Kelas';

$totalSiswaGlobal = (int)dbValue("SELECT COUNT(*) FROM siswa");
$totalClassesAll = (int)dbValue("SELECT COUNT(*) FROM kelas");
$aktifClassesCount = (int)dbValue("SELECT COUNT(*) FROM kelas WHERE aktif = 1");
$nonaktifClassesCount = (int)dbValue("SELECT COUNT(*) FROM kelas WHERE aktif = 0");
$totalClassesWithStudents = (int)dbValue("SELECT COUNT(DISTINCT class_id) FROM siswa WHERE class_id > 0");

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    // Aksi 1: Sinkronkan daftar kelas dari data siswa peserta ujian
    if ($action === 'sync_from_siswa') {
        $studentClasses = dbFetchAll("
            SELECT DISTINCT s.class_id, s.nama_kelas 
            FROM siswa s 
            WHERE s.class_id > 0 OR (s.nama_kelas IS NOT NULL AND s.nama_kelas != '')
        ");
        $syncedNew = 0;
        foreach ($studentClasses as $sc) {
            $cid = (int)$sc['class_id'];
            $cName = trim((string)($sc['nama_kelas'] ?? ''));
            if ($cid > 0) {
                $existK = dbFetch("SELECT id, nama_kelas FROM kelas WHERE id = :id", ['id' => $cid]);
                if ($existK) {
                    dbUpdate('siswa', ['nama_kelas' => $existK['nama_kelas']], 'class_id = :cid AND (nama_kelas IS NULL OR nama_kelas = \'\')', ['cid' => $cid]);
                }
            } elseif ($cName !== '') {
                $existK = dbFetch("SELECT id FROM kelas WHERE UPPER(TRIM(nama_kelas)) = :n", ['n' => strtoupper($cName)]);
                if ($existK) {
                    dbUpdate('siswa', ['class_id' => $existK['id'], 'nama_kelas' => $cName], 'nama_kelas = :cn', ['cn' => $cName]);
                } else {
                    $tingkat = 'XII';
                    if (stripos($cName, 'X ') === 0 || strtoupper(substr($cName, 0, 2)) === 'X ') $tingkat = 'X';
                    elseif (stripos($cName, 'XI ') === 0 || strtoupper(substr($cName, 0, 3)) === 'XI ') $tingkat = 'XI';
                    $newId = dbInsert('kelas', [
                        'nama_kelas' => $cName,
                        'tingkat' => $tingkat,
                        'program_keahlian' => '',
                        'aktif' => 1,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                    dbUpdate('siswa', ['class_id' => $newId, 'nama_kelas' => $cName], 'nama_kelas = :cn', ['cn' => $cName]);
                    $syncedNew++;
                }
            }
        }
        log_activity('SYNC_KELAS_SISWA', "Menyinkronkan daftar kelas langsung dari data siswa peserta ujian. {$syncedNew} kelas baru ditambahkan.");
        set_flash('success', "Daftar kelas berhasil disinkronkan langsung dari data siswa peserta ujian.");
        header('Location: /admin/kelas.php');
        exit;
    }

    // Aksi 2: Simpan atau Edit Data Kelas (Otomatis perbarui seluruh data siswa terkait)
    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $namaKelas = trim($_POST['nama_kelas'] ?? '');
        $tingkat = trim($_POST['tingkat'] ?? '');
        $programKeahlian = trim($_POST['program_keahlian'] ?? '');
        $aktif = isset($_POST['aktif']) ? 1 : 0;

        if ($namaKelas === '') {
            set_flash('danger', 'Nama kelas tidak boleh kosong.');
        } else {
            if ($id > 0) {
                $oldClass = dbFetch("SELECT * FROM kelas WHERE id = :id", ['id' => $id]);
                $oldNamaKelas = $oldClass ? $oldClass['nama_kelas'] : '';
                $oldTingkat = $oldClass ? $oldClass['tingkat'] : '';

                // 1. Update tabel master kelas
                dbUpdate('kelas', [
                    'nama_kelas' => $namaKelas,
                    'tingkat' => $tingkat,
                    'program_keahlian' => $programKeahlian,
                    'aktif' => $aktif,
                    'updated_at' => date('Y-m-d H:i:s')
                ], 'id = :id', ['id' => $id]);

                // 2. OTOMATIS PERBARUI SELURUH DATA SISWA DI KELAS INI
                $affectedStudents = (int)dbValue("SELECT COUNT(*) FROM siswa WHERE class_id = :cid", ['cid' => $id]);
                
                dbUpdate('siswa', [
                    'nama_kelas' => $namaKelas,
                    'updated_at' => date('Y-m-d H:i:s')
                ], 'class_id = :cid', ['cid' => $id]);

                // 3. Update arsip absensi dan berita acara yang terhubung dengan kelas ini
                if ($oldNamaKelas !== '' && ($oldNamaKelas !== $namaKelas || $oldTingkat !== $tingkat)) {
                    // Update absensi
                    dbUpdate('absensi', [
                        'nama_kelas' => $namaKelas,
                        'tingkat' => $tingkat,
                        'updated_at' => date('Y-m-d H:i:s')
                    ], 'class_id = :cid OR nama_kelas = :old_name', [
                        'cid' => $id,
                        'old_name' => $oldNamaKelas
                    ]);

                    // Update berita acara
                    dbUpdate('berita_acara', [
                        'nama_kelas' => $namaKelas,
                        'tingkat' => $tingkat,
                        'updated_at' => date('Y-m-d H:i:s')
                    ], 'class_id = :cid OR nama_kelas = :old_name', [
                        'cid' => $id,
                        'old_name' => $oldNamaKelas
                    ]);

                    // Update data_siswa JSON pada absensi jika ada
                    $absensiRows = dbFetchAll("SELECT id, data_siswa FROM absensi WHERE class_id = :cid OR nama_kelas = :new_name", [
                        'cid' => $id,
                        'new_name' => $namaKelas
                    ]);
                    foreach ($absensiRows as $ab) {
                        if (!empty($ab['data_siswa']) && strpos($ab['data_siswa'], $oldNamaKelas) !== false) {
                            $newJson = str_replace($oldNamaKelas, $namaKelas, $ab['data_siswa']);
                            dbUpdate('absensi', ['data_siswa' => $newJson], 'id = :aid', ['aid' => $ab['id']]);
                        }
                    }
                }

                log_activity('UPDATE_KELAS', "Memperbarui kelas {$oldNamaKelas} menjadi {$namaKelas} (ID #{$id}). Seluruh {$affectedStudents} siswa otomatis diperbarui ke kelas baru.");
                set_flash('success', "Data kelas {$namaKelas} berhasil diperbarui! Seluruh {$affectedStudents} siswa di kelas ini telah otomatis diperbarui.");
            } else {
                $newId = dbInsert('kelas', [
                    'nama_kelas' => $namaKelas,
                    'tingkat' => $tingkat,
                    'program_keahlian' => $programKeahlian,
                    'aktif' => $aktif,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                log_activity('CREATE_KELAS', "Menambahkan kelas {$namaKelas} (ID #{$newId})");
                set_flash('success', "Kelas baru {$namaKelas} berhasil ditambahkan.");
            }
        }
        header('Location: /admin/kelas.php');
        exit;
    }

    if ($action === 'toggle_active') {
        $id = (int)($_POST['id'] ?? 0);
        $status = (int)($_POST['status'] ?? 0);
        dbUpdate('kelas', ['aktif' => $status, 'updated_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $id]);
        log_activity('TOGGLE_KELAS', "Mengubah status aktif kelas ID #{$id} menjadi {$status}");
        
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || isset($_POST['ajax'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'id' => $id, 'status' => $status]);
            exit;
        }

        set_flash('success', 'Status aktif kelas berhasil diperbarui.');
        header('Location: /admin/kelas.php');
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $kInfo = dbFetch("SELECT nama_kelas FROM kelas WHERE id = :id", ['id' => $id]);
        if ($kInfo) {
            $cntSiswa = (int)dbValue("SELECT COUNT(*) FROM siswa WHERE class_id = :cid", ['cid' => $id]);
            dbDelete('siswa', 'class_id = :cid', ['cid' => $id]);
            dbDelete('kelas', 'id = :id', ['id' => $id]);
            log_activity('DELETE_KELAS', "Menghapus kelas {$kInfo['nama_kelas']} beserta {$cntSiswa} siswanya.");
            set_flash('success', "Kelas {$kInfo['nama_kelas']} beserta seluruh data siswanya ({$cntSiswa} siswa) berhasil dihapus.");
        }
        header('Location: /admin/kelas.php');
        exit;
    }

    if ($action === 'delete_all') {
        $countK = (int)dbValue("SELECT COUNT(*) FROM kelas");
        $countS = (int)dbValue("SELECT COUNT(*) FROM siswa");
        getDb()->exec("DELETE FROM siswa");
        getDb()->exec("DELETE FROM kelas");
        if (DB_TYPE === 'sqlite') {
            getDb()->exec("DELETE FROM sqlite_sequence WHERE name IN ('kelas', 'siswa')");
        }
        log_activity('CLEAR_ALL_KELAS', "Menghapus seluruh kelas ({$countK}) dan siswa ({$countS}) untuk reset.");
        set_flash('success', "Seluruh data kelas ({$countK} kelas) beserta siswanya berhasil dikosongkan.");
        header('Location: /admin/kelas.php');
        exit;
    }
}

// Mode Edit Kelas
$editId = (int)($_GET['edit'] ?? 0);
$editClass = null;
$classStudents = [];
if ($editId > 0) {
    $editClass = dbFetch("SELECT * FROM kelas WHERE id = :id", ['id' => $editId]);
    if ($editClass) {
        $classStudents = dbFetchAll("
            SELECT id, nis, nisn, nama, aktif 
            FROM siswa 
            WHERE class_id = :cid 
            ORDER BY nama ASC
        ", ['cid' => $editId]);
    }
}

// Menentukan Sumber Pengambilan Daftar Kelas:
// Jika ada data siswa di database, default daftar kelas diambil dari data siswa peserta ujian
$viewSource = $_GET['source'] ?? ($totalSiswaGlobal > 0 ? 'siswa' : 'all');

if ($totalSiswaGlobal > 0 && $viewSource === 'siswa') {
    // Ambil daftar kelas dari menu Kelola Data Siswa Peserta Ujian / database data siswa
    $classes = dbFetchAll("
        SELECT 
            k.id,
            k.nama_kelas,
            k.tingkat,
            k.program_keahlian,
            k.aktif,
            COUNT(s.id) as total_siswa,
            SUM(CASE WHEN s.aktif = 1 THEN 1 ELSE 0 END) as total_siswa_aktif
        FROM kelas k
        INNER JOIN siswa s ON s.class_id = k.id
        GROUP BY k.id, k.nama_kelas, k.tingkat, k.program_keahlian, k.aktif
        ORDER BY k.tingkat DESC, k.nama_kelas ASC
    ");
} else {
    // Ambil seluruh master kelas
    $classes = dbFetchAll("
        SELECT 
            k.*, 
            (SELECT COUNT(*) FROM siswa s WHERE s.class_id = k.id) as total_siswa,
            (SELECT COUNT(*) FROM siswa s WHERE s.class_id = k.id AND s.aktif = 1) as total_siswa_aktif
        FROM kelas k 
        ORDER BY k.tingkat DESC, k.nama_kelas ASC
    ");
}

require_once __DIR__ . '/layout_header.php';
?>

<div class="page-header-card">
    <div class="page-header-content">
        <div class="page-header-icon-box">
            <i class="bi bi-mortarboard-fill"></i>
        </div>
        <div>
            <h1 class="page-header-title">Kelola Data Kelas</h1>
            <p class="page-header-subtitle">
                Daftar kelas yang terhubung dengan data siswa peserta ujian untuk absensi dan berita acara.
            </p>
        </div>
    </div>
    <div class="page-header-actions" style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
        <form method="POST" action="/admin/kelas.php" style="display: inline; margin: 0;">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="sync_from_siswa">
            <button type="submit" class="btn btn-secondary btn-sm" title="Sinkronkan daftar kelas langsung dari data siswa peserta ujian">
                <i class="bi bi-arrow-repeat"></i> <span>Sinkron dari Siswa</span>
            </button>
        </form>
        <a href="/admin/siswa.php" class="btn btn-outline-primary btn-sm" title="Buka Menu Kelola Data Siswa Peserta Ujian">
            <i class="bi bi-people-fill"></i> <span>Data Siswa</span>
        </a>
        <?php if (!empty($classes) || $totalClassesAll > 0): ?>
            <button type="button" class="btn btn-danger-soft btn-sm" onclick="confirmResetAllKelas()" title="Kosongkan seluruh data kelas untuk persiapan periode baru">
                <i class="bi bi-trash3-fill"></i> <span>Hapus Semua Data Kelas</span>
            </button>
        <?php endif; ?>
    </div>
</div>

<form id="formDeleteAllKelas" method="POST" action="/admin/kelas.php" style="display: none;">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="delete_all">
</form>

<!-- Stat Cards -->
<div class="stats-grid">
    <div class="stat-card-modern">
        <div class="stat-accent-bar" style="background: linear-gradient(90deg, #4f46e5, #6366f1);"></div>
        <div class="stat-icon stat-icon-indigo">
            <i class="bi bi-mortarboard-fill"></i>
        </div>
        <div>
            <div class="stat-value"><?= $totalClassesAll ?></div>
            <div class="stat-label">Total Data Kelas</div>
            <span class="stat-meta-pill" style="background: #eef2ff; color: #4338ca;">
                <i class="bi bi-check-circle"></i> Terdaftar
            </span>
        </div>
    </div>

    <div class="stat-card-modern">
        <div class="stat-accent-bar" style="background: linear-gradient(90deg, #059669, #10b981);"></div>
        <div class="stat-icon" style="background: linear-gradient(135deg, #ecfdf5, #d1fae5); color: #059669;">
            <i class="bi bi-check-circle-fill"></i>
        </div>
        <div>
            <div class="stat-value"><?= $aktifClassesCount ?></div>
            <div class="stat-label">Kelas Aktif</div>
            <span class="stat-meta-pill" style="background: #ecfdf5; color: #059669;">
                Dapat Dipilih Publik
            </span>
        </div>
    </div>

    <div class="stat-card-modern">
        <div class="stat-accent-bar" style="background: linear-gradient(90deg, #0284c7, #38bdf8);"></div>
        <div class="stat-icon stat-icon-sky">
            <i class="bi bi-people-fill"></i>
        </div>
        <div>
            <div class="stat-value"><?= $totalSiswaGlobal ?></div>
            <div class="stat-label">Total Siswa Terdaftar</div>
            <span class="stat-meta-pill" style="background: #f0f9ff; color: #0284c7;">
                Terhubung di Kelas
            </span>
        </div>
    </div>

    <div class="stat-card-modern">
        <div class="stat-accent-bar" style="background: linear-gradient(90deg, #64748b, #94a3b8);"></div>
        <div class="stat-icon" style="background: linear-gradient(135deg, #f1f5f9, #e2e8f0); color: #475569;">
            <i class="bi bi-slash-circle-fill"></i>
        </div>
        <div>
            <div class="stat-value"><?= $nonaktifClassesCount ?></div>
            <div class="stat-label">Kelas Nonaktif</div>
            <span class="stat-meta-pill" style="background: #f1f5f9; color: #475569;">
                Tidak Ditampilkan
            </span>
        </div>
    </div>
</div>

<div class="split-layout" style="height: auto;">
    <!-- Formulir Tambah / Edit Kelas -->
    <div style="flex: 0 0 380px;">
        <div class="card">
            <div class="card-header">
                <span class="card-title"><?= $editClass ? '✏️ Edit Data Kelas' : '➕ Tambah Kelas Baru' ?></span>
            </div>
            <div class="card-body">
                <?php if ($editClass): ?>
                    <div class="alert alert-info" style="margin-bottom: 1.25rem; font-size: 0.85rem; line-height: 1.45; padding: 0.75rem 0.85rem;">
                        <div style="display: flex; gap: 0.5rem; align-items: flex-start;">
                            <i class="bi bi-info-circle-fill text-primary" style="font-size: 1.1rem; flex-shrink: 0; margin-top: 1px;"></i>
                            <div>
                                <strong>Sinkronisasi Otomatis Siswa:</strong> Kelas ini memiliki <strong><?= count($classStudents) ?> siswa</strong> di menu Kelola Data Siswa Peserta Ujian. 
                                Saat data kelas ini disimpan, <strong>seluruh <?= count($classStudents) ?> siswa akan otomatis berubah</strong> mengikuti nama kelas baru.
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="/admin/kelas.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save">
                    <?php if ($editClass): ?>
                        <input type="hidden" name="id" value="<?= $editClass['id'] ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label class="form-label" for="nama_kelas">Nama Kelas <span class="required">*</span></label>
                        <input type="text" name="nama_kelas" id="nama_kelas" class="form-control" 
                               value="<?= htmlspecialchars($editClass['nama_kelas'] ?? '') ?>" placeholder="Contoh: XII RPL 1" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="tingkat">Tingkat</label>
                        <select name="tingkat" id="tingkat" class="form-select">
                            <option value="">-- Pilih Tingkat --</option>
                            <option value="X" <?= ($editClass['tingkat'] ?? '') === 'X' ? 'selected' : '' ?>>Tingkat X</option>
                            <option value="XI" <?= ($editClass['tingkat'] ?? '') === 'XI' ? 'selected' : '' ?>>Tingkat XI</option>
                            <option value="XII" <?= ($editClass['tingkat'] ?? 'XII') === 'XII' ? 'selected' : '' ?>>Tingkat XII</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="program_keahlian">Program Keahlian</label>
                        <input type="text" name="program_keahlian" id="program_keahlian" class="form-control" 
                               value="<?= htmlspecialchars($editClass['program_keahlian'] ?? '') ?>" placeholder="Contoh: Rekayasa Perangkat Lunak">
                    </div>

                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.9rem;">
                            <input type="checkbox" name="aktif" value="1" <?= ($editClass['aktif'] ?? 1) == 1 ? 'checked' : '' ?>>
                            Kelas Aktif (Dapat dipilih publik)
                        </label>
                    </div>

                    <div style="display: flex; gap: 0.5rem; margin-top: 1.5rem;">
                        <button type="submit" class="btn btn-primary" style="flex: 1;">
                            <?= $editClass ? 'Simpan Perubahan' : 'Tambah Kelas' ?>
                        </button>
                        <?php if ($editClass): ?>
                            <a href="/admin/kelas.php" class="btn btn-secondary">Batal</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($editClass && !empty($classStudents)): ?>
            <!-- Daftar Siswa yang Terhubung dengan Kelas Ini -->
            <div class="card" style="margin-top: 1rem;">
                <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; padding: 0.65rem 1rem;">
                    <span class="card-title" style="font-size: 0.9rem;">
                        👥 Siswa di Kelas Ini (<?= count($classStudents) ?>)
                    </span>
                    <a href="/admin/siswa.php?class_id=<?= $editClass['id'] ?>" class="btn btn-sm btn-outline-primary" style="font-size: 0.78rem; padding: 0.2rem 0.5rem;">
                        <i class="bi bi-box-arrow-up-right"></i> Kelola Siswa
                    </a>
                </div>
                <div class="card-body" style="padding: 0; max-height: 280px; overflow-y: auto;">
                    <table class="table" style="font-size: 0.82rem; margin: 0;">
                        <thead>
                            <tr>
                                <th style="width: 35px; text-align: center;">No</th>
                                <th>Nama Siswa</th>
                                <th style="width: 75px; text-align: center;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($classStudents as $idx => $cs): ?>
                                <tr>
                                    <td style="text-align: center; color: var(--text-muted);"><?= $idx + 1 ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($cs['nama']) ?></strong>
                                        <?php if ($cs['nis']): ?>
                                            <br><small class="text-muted">NIS: <?= htmlspecialchars($cs['nis']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <span class="badge <?= $cs['aktif'] ? 'badge-success' : 'badge-danger' ?>" style="font-size: 0.72rem;">
                                            <?= $cs['aktif'] ? 'Aktif' : 'Nonaktif' ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Tabel Daftar Kelas -->
    <div style="flex: 1;">
        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
                <div>
                    <span class="card-title">
                        <?= ($totalSiswaGlobal > 0 && $viewSource === 'siswa') 
                            ? 'Daftar Kelas dari Data Siswa Peserta Ujian (' . count($classes) . ' Kelas)' 
                            : 'Daftar Seluruh Kelas (' . count($classes) . ' Kelas)' ?>
                    </span>
                    <div style="font-size: 0.82rem; color: var(--text-muted); margin-top: 3px;">
                        <?php if ($totalSiswaGlobal > 0): ?>
                            <i class="bi bi-database-check text-success"></i> Mengambil data dari menu Kelola Data Siswa Peserta Ujian (<?= $totalSiswaGlobal ?> siswa di database).
                        <?php else: ?>
                            <i class="bi bi-info-circle text-info"></i> Belum ada data siswa di database. Menampilkan daftar master kelas.
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($totalSiswaGlobal > 0): ?>
                    <div class="btn-group" role="group" style="display: flex; gap: 4px;">
                        <a href="/admin/kelas.php?source=siswa" class="btn btn-sm <?= $viewSource === 'siswa' ? 'btn-primary' : 'btn-secondary' ?>" title="Tampilkan kelas yang memiliki data siswa">
                            <i class="bi bi-people-fill"></i> <span>Dari Data Siswa (<?= $totalClassesWithStudents ?>)</span>
                        </a>
                        <a href="/admin/kelas.php?source=all" class="btn btn-sm <?= $viewSource === 'all' ? 'btn-primary' : 'btn-secondary' ?>" title="Tampilkan semua kelas termasuk yang belum ada siswanya">
                            <i class="bi bi-grid-fill"></i> <span>Semua Master (<?= $totalClassesAll ?>)</span>
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <div class="card-body" style="padding: 0;">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nama Kelas</th>
                                <th>Tingkat</th>
                                <th>Program Keahlian</th>
                                <th style="text-align: center;">Jumlah Siswa</th>
                                <th style="text-align: center; width: 140px;"><i class="bi bi-check2-square"></i> Status (Ceklis)</th>
                                <th style="text-align: center; width: 105px;">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($classes)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                                        <i class="bi bi-folder-x" style="font-size: 2rem; display: block; margin-bottom: 0.5rem;"></i>
                                        Tidak ada data kelas yang dapat ditampilkan.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($classes as $c): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($c['nama_kelas']) ?></strong></td>
                                        <td><?= htmlspecialchars($c['tingkat']) ?></td>
                                        <td><?= htmlspecialchars($c['program_keahlian']) ?></td>
                                        <td style="text-align: center;">
                                            <a href="/admin/siswa.php?class_id=<?= $c['id'] ?>" class="badge badge-info" title="Lihat Siswa Kelas Ini di Menu Siswa">
                                                <?= (int)$c['total_siswa'] ?> Siswa
                                            </a>
                                        </td>
                                        <td style="text-align: center;">
                                            <label class="ceklis-status-pill <?= $c['aktif'] ? 'is-active' : 'is-inactive' ?>" 
                                                   id="ceklis_box_kelas_<?= $c['id'] ?>" 
                                                   title="Klik ceklis untuk mengubah status kelas ini">
                                                <input type="checkbox" 
                                                       class="ceklis-kelas-input" 
                                                       id="check_kelas_<?= $c['id'] ?>" 
                                                       <?= $c['aktif'] ? 'checked' : '' ?> 
                                                       onchange="quickToggleKelas(<?= $c['id'] ?>, this)">
                                                <span class="ceklis-box">
                                                    <i class="bi bi-check-lg ceklis-check-icon"></i>
                                                </span>
                                                <span class="ceklis-text" id="ceklis_text_kelas_<?= $c['id'] ?>">
                                                    <?= $c['aktif'] ? 'Aktif' : 'Nonaktif' ?>
                                                </span>
                                            </label>
                                        </td>
                                        <td style="text-align: center; white-space: nowrap;">
                                            <div class="action-btn-group">
                                                <a href="/admin/kelas.php?edit=<?= $c['id'] ?>" class="btn-icon-action btn-icon-edit" title="Edit Kelas">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <form method="POST" action="/admin/kelas.php" style="display: inline; margin: 0;" 
                                                      onsubmit="return confirm('Peringatan: Menghapus kelas akan menghapus seluruh data siswa di kelas ini. Lanjutkan?')">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                                    <button type="submit" class="btn-icon-action btn-icon-delete" title="Hapus Kelas">
                                                        <i class="bi bi-trash3"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function quickToggleKelas(id, checkbox) {
    if (typeof id === 'object' && id !== null && id.nodeType) {
        const tmp = checkbox;
        checkbox = id;
        id = tmp;
    }
    const isChecked = checkbox.checked ? 1 : 0;
    const box = document.getElementById('ceklis_box_kelas_' + id);
    const text = document.getElementById('ceklis_text_kelas_' + id);

    // Optimistic UI update
    if (isChecked) {
        if (box) {
            box.classList.remove('is-inactive');
            box.classList.add('is-active');
        }
        if (text) text.textContent = 'Aktif';
    } else {
        if (box) {
            box.classList.remove('is-active');
            box.classList.add('is-inactive');
        }
        if (text) text.textContent = 'Nonaktif';
    }

    const csrfToken = '<?= csrf_token() ?>';
    const formData = new FormData();
    formData.append('action', 'toggle_active');
    formData.append('id', id);
    formData.append('status', isChecked);
    formData.append('csrf_token', csrfToken);
    formData.append('ajax', '1');

    fetch('/admin/kelas.php', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data && data.success) {
            try {
                if (typeof showQuickStatusToast === 'function') {
                    showQuickStatusToast('Status kelas berhasil ' + (isChecked ? 'diaktifkan' : 'dinonaktifkan'));
                }
            } catch (e) {}
        } else {
            revertToggle();
        }
    })
    .catch(err => {
        console.error('Toggle error:', err);
        revertToggle();
    });

    function revertToggle() {
        checkbox.checked = !isChecked;
        if (checkbox.checked) {
            if (box) { box.classList.remove('is-inactive'); box.classList.add('is-active'); }
            if (text) text.textContent = 'Aktif';
        } else {
            if (box) { box.classList.remove('is-active'); box.classList.add('is-inactive'); }
            if (text) text.textContent = 'Nonaktif';
        }
        try {
            if (typeof showQuickStatusToast === 'function') {
                showQuickStatusToast('Gagal memperbarui status');
            }
        } catch (e) {}
    }
}
window.quickToggleKelas = quickToggleKelas;

function confirmResetAllKelas() {
    showConfirmResetModal(
        'formDeleteAllKelas',
        'Hapus Semua Data Kelas',
        'PERINGATAN: Tindakan ini akan MENGHAPUS SELURUH data kelas beserta seluruh data siswa di dalamnya dari sistem untuk memulai dari awal.'
    );
}
</script>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
