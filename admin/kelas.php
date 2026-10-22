<?php
$pageTitle = 'Kelola Data Kelas';
require_once __DIR__ . '/layout_header.php';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

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
                dbUpdate('kelas', [
                    'nama_kelas' => $namaKelas,
                    'tingkat' => $tingkat,
                    'program_keahlian' => $programKeahlian,
                    'aktif' => $aktif,
                    'updated_at' => date('Y-m-d H:i:s')
                ], 'id = :id', ['id' => $id]);
                log_activity('UPDATE_KELAS', "Memperbarui kelas {$namaKelas} (ID #{$id})");
                set_flash('success', "Data kelas {$namaKelas} berhasil diperbarui.");
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
        set_flash('success', 'Status aktif kelas berhasil diperbarui.');
        header('Location: /admin/kelas.php');
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $kInfo = dbFetch("SELECT nama_kelas FROM kelas WHERE id = :id", ['id' => $id]);
        if ($kInfo) {
            dbDelete('siswa', 'class_id = :cid', ['cid' => $id]);
            dbDelete('kelas', 'id = :id', ['id' => $id]);
            log_activity('DELETE_KELAS', "Menghapus kelas {$kInfo['nama_kelas']} beserta seluruh siswanya.");
            set_flash('success', "Kelas {$kInfo['nama_kelas']} berhasil dihapus.");
        }
        header('Location: /admin/kelas.php');
        exit;
    }
}

// Check edit mode
$editId = (int)($_GET['edit'] ?? 0);
$editClass = null;
if ($editId > 0) {
    $editClass = dbFetch("SELECT * FROM kelas WHERE id = :id", ['id' => $editId]);
}

$classes = dbFetchAll("
    SELECT k.*, (SELECT COUNT(*) FROM siswa s WHERE s.class_id = k.id) as total_siswa 
    FROM kelas k 
    ORDER BY k.tingkat DESC, k.nama_kelas ASC
");
?>

<div class="page-header-card">
    <div class="page-header-content">
        <div class="page-header-icon-box">
            <i class="bi bi-mortarboard-fill"></i>
        </div>
        <div>
            <h1 class="page-header-title">Kelola Data Kelas</h1>
            <p class="page-header-subtitle">
                Daftar kelas aktif yang dapat dipilih oleh pengawas saat membuat dokumen absensi dan berita acara.
            </p>
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
    </div>

    <!-- Tabel Daftar Kelas -->
    <div style="flex: 1;">
        <div class="card">
            <div class="card-header">
                <span class="card-title">Daftar Seluruh Kelas (<?= count($classes) ?>)</span>
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
                                <th style="text-align: center;">Status</th>
                                <th style="text-align: right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($classes as $c): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($c['nama_kelas']) ?></strong></td>
                                    <td><?= htmlspecialchars($c['tingkat']) ?></td>
                                    <td><?= htmlspecialchars($c['program_keahlian']) ?></td>
                                    <td style="text-align: center;">
                                        <a href="/admin/siswa.php?class_id=<?= $c['id'] ?>" class="badge badge-info" title="Lihat Siswa">
                                            <?= $c['total_siswa'] ?> Siswa
                                        </a>
                                    </td>
                                    <td style="text-align: center;">
                                        <form method="POST" action="/admin/kelas.php" style="display: inline;">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="toggle_active">
                                            <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                            <input type="hidden" name="status" value="<?= $c['aktif'] ? 0 : 1 ?>">
                                            <button type="submit" class="badge <?= $c['aktif'] ? 'badge-success' : 'badge-danger' ?>" 
                                                    style="border: none; cursor: pointer;" title="Klik untuk ubah status">
                                                <?= $c['aktif'] ? 'Aktif' : 'Nonaktif' ?>
                                            </button>
                                        </form>
                                    </td>
                                    <td style="text-align: right; white-space: nowrap;">
                                        <a href="/admin/kelas.php?edit=<?= $c['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                                        <form method="POST" action="/admin/kelas.php" style="display: inline;" 
                                              onsubmit="return confirm('Peringatan: Menghapus kelas akan menghapus seluruh data siswa di kelas ini. Lanjutkan?')">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
