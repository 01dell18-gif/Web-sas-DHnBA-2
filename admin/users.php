<?php
require_once __DIR__ . '/../config/functions.php';
require_admin();
require_admin_role();

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $username = strtolower(trim($_POST['username'] ?? ''));
        $nama = trim($_POST['nama_lengkap'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $role = trim($_POST['role'] ?? 'operator');
        if (!in_array($role, ['admin', 'operator'])) {
            $role = 'operator';
        }

        if ($username === '' || $password === '' || $nama === '') {
            set_flash('danger', 'Username, nama lengkap, dan password wajib diisi.');
        } elseif (strlen($password) < 5) {
            set_flash('danger', 'Password minimal 5 karakter.');
        } else {
            $exists = dbValue("SELECT COUNT(*) FROM users WHERE username = :u", ['u' => $username]);
            if ($exists) {
                set_flash('danger', "Username '{$username}' sudah digunakan. Silakan pilih username lain.");
            } else {
                $newId = dbInsert('users', [
                    'username' => $username,
                    'password' => password_hash($password, PASSWORD_BCRYPT),
                    'password_plain' => $password,
                    'nama_lengkap' => $nama,
                    'role' => $role,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                log_activity('CREATE_USER', "Menambahkan pengguna baru: {$username} ({$role})");
                set_flash('success', "Pengguna '{$username}' dengan hak akses {$role} berhasil ditambahkan.");
                header('Location: /admin/users.php');
                exit;
            }
        }
    }

    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $nama = trim($_POST['nama_lengkap'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $role = trim($_POST['role'] ?? 'operator');
        if (!in_array($role, ['admin', 'operator'])) {
            $role = 'operator';
        }

        $user = dbFetch("SELECT * FROM users WHERE id = :id", ['id' => $id]);
        if ($user) {
            // Cegah admin mendowngrade akunnya sendiri saat sedang login
            if ($id === (int)$_SESSION['admin_id'] && $role !== 'admin') {
                set_flash('danger', 'Anda tidak dapat mengubah hak akses akun Anda sendiri dari Administrator.');
            } else {
                $data = [
                    'nama_lengkap' => $nama,
                    'role' => $role,
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                if ($password !== '') {
                    if (strlen($password) < 5) {
                        set_flash('danger', 'Password baru minimal 5 karakter.');
                        header("Location: /admin/users.php?edit={$id}");
                        exit;
                    }
                    $data['password'] = password_hash($password, PASSWORD_BCRYPT);
                    $data['password_plain'] = $password;
                }

                dbUpdate('users', $data, 'id = :id', ['id' => $id]);
                log_activity('UPDATE_USER', "Memperbarui data pengguna: {$user['username']}");
                set_flash('success', "Data pengguna '{$user['username']}' berhasil diperbarui.");
                header('Location: /admin/users.php');
                exit;
            }
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id === (int)$_SESSION['admin_id']) {
            set_flash('danger', 'Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif.');
        } else {
            $user = dbFetch("SELECT username FROM users WHERE id = :id", ['id' => $id]);
            if ($user) {
                dbDelete('users', 'id = :id', ['id' => $id]);
                log_activity('DELETE_USER', "Menghapus pengguna: {$user['username']}");
                set_flash('success', "Pengguna '{$user['username']}' berhasil dihapus.");
            }
        }
        header('Location: /admin/users.php');
        exit;
    }
}

// Data edit jika ada query param
$editId = (int)($_GET['edit'] ?? 0);
$editUser = null;
if ($editId > 0) {
    $editUser = dbFetch("SELECT * FROM users WHERE id = :id", ['id' => $editId]);
}

$users = dbFetchAll("SELECT * FROM users ORDER BY role ASC, username ASC");

$pageTitle = 'Manajemen User';
require_once __DIR__ . '/layout_header.php';
?>

<div class="page-header-card">
    <div class="page-header-content">
        <div class="page-header-icon-box">
            <i class="bi bi-people-fill"></i>
        </div>
        <div>
            <h1 class="page-header-title">Manajemen Pengguna & Hak Akses</h1>
            <p class="page-header-subtitle">
                Kelola akun pengguna sistem dengan pembagian hak akses <strong>Administrator</strong> (Penuh) dan <strong>Operator</strong> (Operasional Ujian).
            </p>
        </div>
    </div>
</div>

<div class="split-layout" style="height: auto; align-items: flex-start; gap: 1rem;">
    <!-- KOLOM KIRI: FORM TAMBAH / EDIT USER -->
    <div style="flex: 0 0 340px; min-width: 300px;">
        <div class="card" style="border-top: 3px solid var(--primary);">
            <div class="card-header">
                <span class="card-title">
                    <i class="bi <?= $editUser ? 'bi-pencil-square' : 'bi-person-plus' ?>"></i>
                    <?= $editUser ? 'Edit Pengguna' : 'Tambah Pengguna Baru' ?>
                </span>
            </div>
            <div class="card-body">
                <form method="POST" action="/admin/users.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="<?= $editUser ? 'update' : 'create' ?>">
                    <?php if ($editUser): ?>
                        <input type="hidden" name="id" value="<?= $editUser['id'] ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label class="form-label" for="username">Username <?= $editUser ? '' : '<span class="required">*</span>' ?></label>
                        <input type="text" name="username" id="username" class="form-control" 
                               value="<?= htmlspecialchars($editUser['username'] ?? '') ?>" 
                               placeholder="Contoh: panitia_ujian" <?= $editUser ? 'disabled style="background: var(--slate-100);"' : 'required' ?>>
                        <?php if ($editUser): ?>
                            <span class="form-hint">Username tidak dapat diubah.</span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="nama_lengkap">Nama Lengkap <span class="required">*</span></label>
                        <input type="text" name="nama_lengkap" id="nama_lengkap" class="form-control" 
                               value="<?= htmlspecialchars($editUser['nama_lengkap'] ?? '') ?>" 
                               placeholder="Nama guru / panitia" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="role">Hak Akses / Peran <span class="required">*</span></label>
                        <select name="role" id="role" class="form-select" required>
                            <option value="operator" <?= ($editUser['role'] ?? 'operator') === 'operator' ? 'selected' : '' ?>>
                                Operator (Operasional Ujian & Rekap)
                            </option>
                            <option value="admin" <?= ($editUser['role'] ?? '') === 'admin' ? 'selected' : '' ?>>
                                Administrator (Akses Penuh Seluruh Sistem)
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">
                            <?= $editUser ? 'Ganti Password (Kosongkan jika tetap)' : 'Password <span class="required">*</span>' ?>
                        </label>
                        <input type="password" name="password" id="password" class="form-control" 
                               placeholder="<?= $editUser ? 'Masukkan password baru...' : 'Minimal 5 karakter' ?>" <?= $editUser ? '' : 'required' ?>>
                    </div>

                    <div style="display: flex; gap: 0.5rem; margin-top: 1rem;">
                        <button type="submit" class="btn btn-primary" style="flex: 1;">
                            <i class="bi bi-check2-circle"></i> <?= $editUser ? 'Simpan Perubahan' : 'Tambah Pengguna' ?>
                        </button>
                        <?php if ($editUser): ?>
                            <a href="/admin/users.php" class="btn btn-secondary">Batal</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- INFO PERBEDAAN HAK AKSES -->
        <div class="card" style="background: #f8fafc;">
            <div class="card-header" style="background: transparent;">
                <span class="card-title" style="font-size: 0.85rem;"><i class="bi bi-shield-lock"></i> Hak Akses Sistem</span>
            </div>
            <div class="card-body" style="font-size: 0.775rem; line-height: 1.5; color: var(--slate-700);">
                <div style="margin-bottom: 0.5rem;">
                    <strong style="color: #065F46;">👑 Administrator:</strong><br>
                    Akses penuh ke seluruh menu (Data Siswa, Kelas, Template Ujian, Pengaturan Kop & Drive, serta Manajemen User).
                </div>
                <div>
                    <strong style="color: #1E40AF;">🛡️ Operator:</strong><br>
                    Akses untuk data operasional harian (Kelas, Siswa, Rekap Nilai/Kehadiran, Cetak/Arsip Dokumen, dan Ganti Password Pribadi).
                </div>
            </div>
        </div>
    </div>

    <!-- KOLOM KANAN: TABEL DAFTAR PENGGUNA -->
    <div style="flex: 1; min-width: 0;">
        <div class="card">
            <div class="card-header">
                <span class="card-title">
                    <i class="bi bi-people"></i> Daftar Pengguna Terdaftar (<?= count($users) ?>)
                </span>
            </div>
            <div class="card-body" style="padding: 0;">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="width: 40px; text-align: center;">No</th>
                                <th>Username</th>
                                <th>Password</th>
                                <th>Nama Lengkap</th>
                                <th style="text-align: center;">Hak Akses</th>
                                <th>Tanggal Dibuat</th>
                                <th style="text-align: center; width: 105px;">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $idx => $u): ?>
                                <tr>
                                    <td style="text-align: center; color: var(--text-muted); font-size: 0.75rem;">
                                        <?= $idx + 1 ?>
                                    </td>
                                    <td>
                                        <div style="font-weight: 700; color: var(--text-heading);">
                                            <?= htmlspecialchars($u['username']) ?>
                                            <?php if ((int)$u['id'] === (int)$_SESSION['admin_id']): ?>
                                                <span class="badge badge-info" style="font-size: 0.65rem; padding: 1px 5px; margin-left: 3px;">Anda</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="display: inline-flex; align-items: center; gap: 0.4rem; background: #f8fafc; border: 1px solid #e2e8f0; padding: 2px 8px; border-radius: 6px; font-family: monospace; font-size: 0.85rem;">
                                            <span id="passText_<?= $u['id'] ?>" data-password="<?= htmlspecialchars($u['password_plain'] ?: 'admin123') ?>" style="min-width: 65px; display: inline-block;">••••••••</span>
                                            <button type="button" class="btn-icon-action btn-icon-view" style="width: 24px; height: 24px; font-size: 0.75rem; padding: 0; display: inline-flex; align-items: center; justify-content: center;" 
                                                    onclick="togglePasswordVisibility(<?= $u['id'] ?>)" title="Lihat / Sembunyikan Password">
                                                <i id="passIcon_<?= $u['id'] ?>" class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($u['nama_lengkap'] ?: '-') ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <?php if ($u['role'] === 'admin'): ?>
                                            <span class="badge badge-success">👑 Administrator</span>
                                        <?php else: ?>
                                            <span class="badge badge-info">🛡️ Operator</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-size: 0.75rem; color: var(--text-muted);">
                                        <?= !empty($u['created_at']) ? date('d/m/Y H:i', strtotime($u['created_at'])) : '-' ?>
                                    </td>
                                    <td style="text-align: center; white-space: nowrap;">
                                        <div class="action-btn-group">
                                            <a href="/admin/users.php?edit=<?= $u['id'] ?>" class="btn-icon-action btn-icon-edit" title="Edit Pengguna">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <?php if ((int)$u['id'] !== (int)$_SESSION['admin_id']): ?>
                                                <form method="POST" action="/admin/users.php" style="display: inline; margin: 0;" 
                                                      onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun <?= htmlspecialchars($u['username']) ?>?')">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                                    <button type="submit" class="btn-icon-action btn-icon-delete" title="Hapus Pengguna">
                                                        <i class="bi bi-trash3"></i>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <button type="button" class="btn-icon-action btn-icon-delete" disabled title="Tidak dapat menghapus akun sendiri (sedang login)">
                                                    <i class="bi bi-lock"></i>
                                                </button>
                                            <?php endif; ?>
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
function togglePasswordVisibility(userId) {
    const textEl = document.getElementById('passText_' + userId);
    const iconEl = document.getElementById('passIcon_' + userId);
    if (!textEl || !iconEl) return;
    const realPass = textEl.getAttribute('data-password');
    if (textEl.textContent === '••••••••') {
        textEl.textContent = realPass;
        iconEl.classList.remove('bi-eye');
        iconEl.classList.add('bi-eye-slash');
        iconEl.parentElement.title = "Sembunyikan Password";
    } else {
        textEl.textContent = '••••••••';
        iconEl.classList.remove('bi-eye-slash');
        iconEl.classList.add('bi-eye');
        iconEl.parentElement.title = "Lihat Password";
    }
}
</script>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
