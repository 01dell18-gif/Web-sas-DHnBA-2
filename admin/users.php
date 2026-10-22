<?php
require_once __DIR__ . '/../config/functions.php';
require_admin();
require_admin_role();

// Handle Export Excel (.xls)
if (isset($_GET['action']) && $_GET['action'] === 'export_excel') {
    $allUsers = dbFetchAll("SELECT * FROM users ORDER BY role ASC, username ASC");
    $headers = ['No', 'Username', 'Nama Lengkap', 'Hak Akses / Peran', 'Password', 'Tanggal Dibuat'];
    $rows = [];
    foreach ($allUsers as $idx => $u) {
        $rows[] = [
            $idx + 1,
            $u['username'],
            $u['nama_lengkap'],
            $u['role'] === 'admin' ? 'Administrator' : 'Operator',
            $u['password_plain'] ?: '••••••••',
            $u['created_at'] ?? '-'
        ];
    }
    log_activity('EXPORT_EXCEL', "Mengekspor daftar pengguna ke format Excel (.xls)");
    export_excel_xls("Data_Pengguna_SAS_" . date('Ymd_His') . ".xls", $headers, $rows, [1, 4]);
}

// Handle Download Template Excel (.xls)
if (isset($_GET['action']) && $_GET['action'] === 'template_excel') {
    $headers = ['Username', 'Nama Lengkap', 'Hak Akses (admin / operator)', 'Password'];
    $sampleRows = [
        ['operator_tsm', 'Operator TSM', 'operator', 'operator123'],
        ['panitia_dkv', 'Panitia DKV', 'operator', 'dkv2026'],
        ['admin_utama', 'Administrator Cadangan', 'admin', 'admin123']
    ];
    export_excel_xls("template_impor_pengguna.xls", $headers, $sampleRows, [0, 3]);
}

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

    // Handle Import Excel
    if ($action === 'import_excel') {
        if (!empty($_FILES['file_user_excel']['tmp_name'])) {
            $file = $_FILES['file_user_excel']['tmp_name'];
            $rows = parse_spreadsheet_rows($file);

            if (!empty($rows)) {
                $createdCount = 0;
                $updatedCount = 0;

                foreach ($rows as $cols) {
                    // Check if col 0 is sequence No
                    if (count($cols) >= 4 && is_numeric($cols[0]) && !is_numeric($cols[1])) {
                        $uName = strtolower(trim($cols[1] ?? ''));
                        $nama = trim($cols[2] ?? '');
                        $role = strtolower(trim($cols[3] ?? 'operator'));
                        $pass = trim($cols[4] ?? 'password123');
                    } else {
                        $uName = strtolower(trim($cols[0] ?? ''));
                        $nama = trim($cols[1] ?? '');
                        $role = strtolower(trim($cols[2] ?? 'operator'));
                        $pass = trim($cols[3] ?? 'password123');
                    }

                    if ($uName === '') continue;
                    if ($nama === '') $nama = ucfirst($uName);
                    if (!in_array($role, ['admin', 'operator'])) {
                        $role = 'operator';
                    }
                    if ($pass === '') $pass = 'password123';

                    $existing = dbFetch("SELECT id FROM users WHERE username = :u", ['u' => $uName]);
                    if ($existing) {
                        dbUpdate('users', [
                            'nama_lengkap' => $nama,
                            'role' => $role,
                            'password' => password_hash($pass, PASSWORD_BCRYPT),
                            'password_plain' => $pass,
                            'updated_at' => date('Y-m-d H:i:s')
                        ], 'id = :id', ['id' => $existing['id']]);
                        $updatedCount++;
                    } else {
                        dbInsert('users', [
                            'username' => $uName,
                            'nama_lengkap' => $nama,
                            'role' => $role,
                            'password' => password_hash($pass, PASSWORD_BCRYPT),
                            'password_plain' => $pass,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
                        $createdCount++;
                    }
                }

                $total = $createdCount + $updatedCount;
                log_activity('IMPORT_USERS', "Mengimpor {$total} data pengguna dari Excel ({$createdCount} baru, {$updatedCount} diperbarui)");
                set_flash('success', "Berhasil memproses {$total} pengguna ({$createdCount} pengguna baru ditambahkan, {$updatedCount} diperbarui).");
            } else {
                set_flash('danger', 'Berkas Excel tidak memuat data yang valid atau format kolom tidak sesuai.');
            }
        } else {
            set_flash('danger', 'Silakan pilih berkas Excel (.xls) untuk diunggah.');
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
    <div class="page-header-actions" style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
        <a href="/admin/users.php?action=export_excel" class="btn btn-secondary btn-sm" title="Ekspor daftar pengguna ke format Excel .xls">
            <i class="bi bi-file-earmark-arrow-down"></i> <span>Ekspor Excel (.xls)</span>
        </a>
        <button type="button" class="btn btn-success btn-sm" onclick="openModal('modalImportUsers')" title="Impor pengguna dari format Excel .xls">
            <i class="bi bi-file-earmark-excel"></i> <span>Impor Excel (.xls)</span>
        </button>
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
                        <div style="position: relative; display: flex; align-items: center;">
                            <input type="password" name="password" id="password" class="form-control" style="padding-right: 42px;"
                                   placeholder="<?= $editUser ? 'Masukkan password baru...' : 'Minimal 5 karakter' ?>" <?= $editUser ? '' : 'required' ?>>
                            <button type="button" id="btnToggleFormPassword" onclick="toggleFormPasswordVisibility()" 
                                    style="position: absolute; right: 8px; background: none; border: none; color: var(--slate-500); cursor: pointer; padding: 6px; display: flex; align-items: center; justify-content: center; font-size: 1.15rem; transition: color 0.15s ease;" 
                                    title="Lihat Password">
                                <i id="iconToggleFormPassword" class="bi bi-eye"></i>
                            </button>
                        </div>
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
<!-- MODAL IMPOR USER DARI EXCEL -->
<div id="modalImportUsers" class="custom-modal" style="display: none;">
    <div style="background: #ffffff; width: 90%; max-width: 480px; border-radius: 14px; padding: 1.5rem; margin-top: 25px; margin-bottom: 2rem; box-shadow: 0 20px 40px rgba(0,0,0,0.25);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px solid var(--border); padding-bottom: 0.65rem;">
            <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--slate-900); margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                <i class="bi bi-file-earmark-excel text-success"></i> Impor Pengguna (.xls)
            </h3>
            <button type="button" class="btn btn-secondary btn-sm" onclick="closeModal('modalImportUsers')">✕</button>
        </div>

        <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 1.25rem;">
            Unggah berkas spreadsheet <code>.xls</code> / <code>.xlsx</code> untuk menambahkan atau memperbarui akun pengguna sistem secara massal.
        </p>

        <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: 8px; padding: 0.85rem 1rem; margin-bottom: 1.25rem; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <strong style="font-size: 0.85rem; color: var(--slate-800); display: block;">Format Template Excel</strong>
                <span style="font-size: 0.75rem; color: var(--slate-500);">Kolom: Username, Nama, Hak Akses, Password</span>
            </div>
            <a href="/admin/users.php?action=template_excel" class="btn btn-outline-primary btn-sm" style="white-space: nowrap;">
                <i class="bi bi-download"></i> Unduh Template
            </a>
        </div>

        <form method="POST" action="/admin/users.php" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="import_excel">
            <div class="form-group" style="margin-bottom: 1.25rem;">
                <label class="form-label">Pilih Berkas Excel (.xls / .xlsx / .csv)</label>
                <input type="file" name="file_user_excel" class="form-control" accept=".xls,.xlsx,.csv" required>
            </div>
            <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalImportUsers')">Batal</button>
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-upload"></i> Unggah & Impor Pengguna
                </button>
            </div>
        </form>
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

function toggleFormPasswordVisibility() {
    const input = document.getElementById('password');
    const icon = document.getElementById('iconToggleFormPassword');
    if (!input || !icon) return;
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
        icon.parentElement.title = "Sembunyikan Password";
    } else {
        input.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
        icon.parentElement.title = "Lihat Password";
    }
}
</script>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
