<?php
$pageTitle = 'Kelola Data Siswa';
require_once __DIR__ . '/layout_header.php';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $classId = (int)($_POST['class_id'] ?? 0);
        $nama = trim($_POST['nama'] ?? '');
        $nis = trim($_POST['nis'] ?? '');
        $nisn = trim($_POST['nisn'] ?? '');
        $aktif = isset($_POST['aktif']) ? 1 : 0;

        if ($classId <= 0 || $nama === '') {
            set_flash('danger', 'Nama siswa dan kelas wajib diisi.');
        } else {
            if ($id > 0) {
                dbUpdate('siswa', [
                    'class_id' => $classId,
                    'nama' => $nama,
                    'nis' => $nis,
                    'nisn' => $nisn,
                    'aktif' => $aktif,
                    'updated_at' => date('Y-m-d H:i:s')
                ], 'id = :id', ['id' => $id]);
                log_activity('UPDATE_SISWA', "Memperbarui data siswa {$nama} (ID #{$id})");
                set_flash('success', "Data siswa {$nama} berhasil diperbarui.");
            } else {
                $newId = dbInsert('siswa', [
                    'class_id' => $classId,
                    'nama' => $nama,
                    'nis' => $nis,
                    'nisn' => $nisn,
                    'aktif' => $aktif,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                log_activity('CREATE_SISWA', "Menambahkan siswa baru {$nama} (ID #{$newId})");
                set_flash('success', "Siswa {$nama} berhasil ditambahkan.");
            }
        }
        $redirectUrl = '/admin/siswa.php' . ($classId > 0 ? "?class_id={$classId}" : '');
        header("Location: {$redirectUrl}");
        exit;
    }

    if ($action === 'toggle_active') {
        $id = (int)($_POST['id'] ?? 0);
        $status = (int)($_POST['status'] ?? 0);
        $retClass = (int)($_POST['return_class_id'] ?? 0);
        dbUpdate('siswa', ['aktif' => $status, 'updated_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $id]);
        set_flash('success', 'Status siswa berhasil diperbarui.');
        header('Location: /admin/siswa.php' . ($retClass > 0 ? "?class_id={$retClass}" : ''));
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $retClass = (int)($_POST['return_class_id'] ?? 0);
        $sInfo = dbFetch("SELECT nama FROM siswa WHERE id = :id", ['id' => $id]);
        if ($sInfo) {
            dbDelete('siswa', 'id = :id', ['id' => $id]);
            log_activity('DELETE_SISWA', "Menghapus siswa {$sInfo['nama']} (ID #{$id})");
            set_flash('success', "Siswa {$sInfo['nama']} berhasil dihapus.");
        }
        header('Location: /admin/siswa.php' . ($retClass > 0 ? "?class_id={$retClass}" : ''));
        exit;
    }
}

// Filter and edit mode
$selectedClassId = (int)($_GET['class_id'] ?? 0);
$editId = (int)($_GET['edit'] ?? 0);
$editStudent = null;
if ($editId > 0) {
    $editStudent = dbFetch("SELECT * FROM siswa WHERE id = :id", ['id' => $editId]);
    if ($editStudent && $selectedClassId === 0) {
        $selectedClassId = (int)$editStudent['class_id'];
    }
}

$classes = dbFetchAll("SELECT * FROM kelas ORDER BY tingkat DESC, nama_kelas ASC");

$whereSql = "1=1";
$params = [];
if ($selectedClassId > 0) {
    $whereSql .= " AND s.class_id = :cid";
    $params['cid'] = $selectedClassId;
}

$students = dbFetchAll("
    SELECT s.*, k.nama_kelas 
    FROM siswa s 
    LEFT JOIN kelas k ON s.class_id = k.id 
    WHERE {$whereSql} 
    ORDER BY k.nama_kelas ASC, s.nama ASC
", $params);
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h2>Kelola Data Siswa</h2>
        <p style="color: var(--text-muted); font-size: 0.9rem;">
            Daftar siswa peserta ujian yang dikelompokkan per kelas.
        </p>
    </div>
    <div style="display: flex; gap: 0.5rem;">
        <a href="/admin/import-siswa.php" class="btn btn-success">
            📥 Impor dari Excel / CSV
        </a>
    </div>
</div>

<div class="split-layout" style="height: auto;">
    <!-- Form Tambah / Edit Siswa -->
    <div style="flex: 0 0 360px;">
        <div class="card">
            <div class="card-header">
                <span class="card-title"><?= $editStudent ? '✏️ Edit Data Siswa' : '➕ Tambah Siswa Baru' ?></span>
            </div>
            <div class="card-body">
                <form method="POST" action="/admin/siswa.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save">
                    <?php if ($editStudent): ?>
                        <input type="hidden" name="id" value="<?= $editStudent['id'] ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label class="form-label" for="class_id">Kelas <span class="required">*</span></label>
                        <select name="class_id" id="class_id" class="form-select" required>
                            <option value="">-- Pilih Kelas --</option>
                            <?php foreach ($classes as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= ($editStudent ? $editStudent['class_id'] : $selectedClassId) == $c['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['nama_kelas']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="nama">Nama Lengkap Siswa <span class="required">*</span></label>
                        <input type="text" name="nama" id="nama" class="form-control" 
                               value="<?= htmlspecialchars($editStudent['nama'] ?? '') ?>" placeholder="Nama lengkap siswa" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="nis">NIS (Nomor Induk Siswa)</label>
                        <input type="text" name="nis" id="nis" class="form-control" 
                               value="<?= htmlspecialchars($editStudent['nis'] ?? '') ?>" placeholder="Opsional">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="nisn">NISN</label>
                        <input type="text" name="nisn" id="nisn" class="form-control" 
                               value="<?= htmlspecialchars($editStudent['nisn'] ?? '') ?>" placeholder="Opsional">
                    </div>

                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.9rem;">
                            <input type="checkbox" name="aktif" value="1" <?= ($editStudent['aktif'] ?? 1) == 1 ? 'checked' : '' ?>>
                            Siswa Aktif (Ikut Ujian)
                        </label>
                    </div>

                    <div style="display: flex; gap: 0.5rem; margin-top: 1.5rem;">
                        <button type="submit" class="btn btn-primary" style="flex: 1;">
                            <?= $editStudent ? 'Simpan Perubahan' : 'Tambah Siswa' ?>
                        </button>
                        <?php if ($editStudent): ?>
                            <a href="/admin/siswa.php?class_id=<?= $selectedClassId ?>" class="btn btn-secondary">Batal</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Filter & Daftar Siswa -->
    <div style="flex: 1;">
        <!-- Bar Filter -->
        <div class="card" style="margin-bottom: 1rem;">
            <div class="card-body" style="padding: 1rem;">
                <form method="GET" action="/admin/siswa.php" style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 180px;">
                        <select name="class_id" class="form-select" onchange="this.form.submit()">
                            <option value="0">-- Semua Kelas --</option>
                            <?php foreach ($classes as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= $selectedClassId == $c['id'] ? 'selected' : '' ?>>
                                    Kelas <?= htmlspecialchars($c['nama_kelas']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if ($selectedClassId > 0): ?>
                        <a href="/admin/siswa.php" class="btn btn-secondary btn-sm" style="display: flex; align-items: center;">
                            Reset Filter
                        </a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Tabel Siswa -->
        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <span class="card-title">Daftar Siswa (<?= count($students) ?>)</span>
            </div>
            <div class="card-body" style="padding: 0;">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="width: 40px; text-align: center;">No</th>
                                <th style="width: 90px;">NIS</th>
                                <th>Nama Lengkap</th>
                                <th>Kelas</th>
                                <th style="text-align: center;">Status</th>
                                <th style="text-align: right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($students)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 1.5rem;">
                                        Tidak ada data siswa ditemukan.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($students as $idx => $s): ?>
                                    <tr>
                                        <td style="text-align: center; color: var(--text-muted);"><?= $idx + 1 ?></td>
                                        <td><?= htmlspecialchars($s['nis'] ?: '-') ?></td>
                                        <td><strong><?= htmlspecialchars($s['nama']) ?></strong></td>
                                        <td><span class="badge badge-info"><?= htmlspecialchars($s['nama_kelas'] ?? '-') ?></span></td>
                                        <td style="text-align: center;">
                                            <form method="POST" action="/admin/siswa.php" style="display: inline;">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="toggle_active">
                                                <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                                <input type="hidden" name="status" value="<?= $s['aktif'] ? 0 : 1 ?>">
                                                <input type="hidden" name="return_class_id" value="<?= $selectedClassId ?>">
                                                <button type="submit" class="badge <?= $s['aktif'] ? 'badge-success' : 'badge-danger' ?>" 
                                                        style="border: none; cursor: pointer;">
                                                    <?= $s['aktif'] ? 'Aktif' : 'Nonaktif' ?>
                                                </button>
                                            </form>
                                        </td>
                                        <td style="text-align: right; white-space: nowrap;">
                                            <a href="/admin/siswa.php?edit=<?= $s['id'] ?>&class_id=<?= $selectedClassId ?>" class="btn btn-secondary btn-sm">Edit</a>
                                            <form method="POST" action="/admin/siswa.php" style="display: inline;" 
                                                  onsubmit="return confirm('Hapus siswa <?= htmlspecialchars($s['nama'], ENT_QUOTES) ?>?')">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                                <input type="hidden" name="return_class_id" value="<?= $selectedClassId ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                            </form>
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

<?php require_once __DIR__ . '/layout_footer.php'; ?>
