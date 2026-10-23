<?php
require_once __DIR__ . '/../config/functions.php';
require_admin();

$pageTitle = 'Kelola Data Kelas';

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
            dbDelete('siswa', 'class_id = :cid', ['cid' => $id]);
            dbDelete('kelas', 'id = :id', ['id' => $id]);
            log_activity('DELETE_KELAS', "Menghapus kelas {$kInfo['nama_kelas']} beserta seluruh siswanya.");
            set_flash('success', "Kelas {$kInfo['nama_kelas']} berhasil dihapus.");
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
                Daftar kelas aktif yang dapat dipilih oleh pengawas saat membuat dokumen absensi dan berita acara.
            </p>
        </div>
    </div>
    <div class="page-header-actions">
        <?php if (!empty($classes)): ?>
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
                                <th style="text-align: center; width: 140px;"><i class="bi bi-check2-square"></i> Status (Ceklis)</th>
                                <th style="text-align: center; width: 105px;">AKSI</th>
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
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function quickToggleKelas(id, checkbox) {
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

    const csrfToken = document.querySelector('input[name="csrf_token"]') ? document.querySelector('input[name="csrf_token"]').value : '';
    const formData = new FormData();
    formData.append('action', 'toggle_active');
    formData.append('id', id);
    formData.append('status', isChecked);
    formData.append('csrf_token', csrfToken);
    formData.append('ajax', '1');

    fetch('/admin/kelas.php', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (data && data.success) {
            showQuickStatusToast('Status kelas berhasil ' + (isChecked ? 'diaktifkan' : 'dinonaktifkan'));
        } else {
            revertToggle();
        }
    })
    .catch(() => {
        revertToggle();
    });

    function revertToggle() {
        checkbox.checked = !checkbox.checked;
        if (checkbox.checked) {
            if (box) { box.classList.remove('is-inactive'); box.classList.add('is-active'); }
            if (text) text.textContent = 'Aktif';
        } else {
            if (box) { box.classList.remove('is-active'); box.classList.add('is-inactive'); }
            if (text) text.textContent = 'Nonaktif';
        }
        showQuickStatusToast('Gagal memperbarui status');
    }
}

function confirmResetAllKelas() {
    showConfirmResetModal(
        'formDeleteAllKelas',
        'Hapus Semua Data Kelas',
        'PERINGATAN: Tindakan ini akan MENGHAPUS SELURUH data kelas beserta seluruh data siswa di dalamnya dari sistem untuk memulai dari awal.'
    );
}
</script>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
