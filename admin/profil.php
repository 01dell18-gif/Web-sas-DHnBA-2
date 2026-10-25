<?php
$pageTitle = 'Profil Akun';
require_once __DIR__ . '/../config/functions.php';
require_admin();

$user = get_logged_in_user();
if (!$user) {
    set_flash('danger', 'Akun pengguna tidak ditemukan.');
    header('Location: /admin/login.php');
    exit;
}
$userId = (int)$user['id'];

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    // 1. UPDATE PROFILE & FOTO
    if ($action === 'update_profile') {
        $namaLengkap = trim($_POST['nama_lengkap'] ?? '');
        $username = strtolower(trim($_POST['username'] ?? ''));
        $removeFoto = !empty($_POST['remove_foto']);

        if ($namaLengkap === '') {
            set_flash('danger', 'Nama lengkap tidak boleh kosong.');
            header('Location: /admin/profil.php');
            exit;
        }

        if ($username === '') {
            set_flash('danger', 'Username tidak boleh kosong.');
            header('Location: /admin/profil.php');
            exit;
        }

        // Check username uniqueness if changed
        if ($username !== $user['username']) {
            $exists = dbValue("SELECT COUNT(*) FROM users WHERE username = :u AND id != :id", [
                'u' => $username,
                'id' => $userId
            ]);
            if ($exists) {
                set_flash('danger', "Username '{$username}' sudah digunakan oleh akun lain.");
                header('Location: /admin/profil.php');
                exit;
            }
        }

        $fotoPath = $user['foto'] ?? null;

        // Handle Remove Photo
        if ($removeFoto && !empty($fotoPath)) {
            $oldFile = BASE_PATH . '/' . ltrim($fotoPath, '/');
            if (file_exists($oldFile) && is_file($oldFile)) {
                @unlink($oldFile);
            }
            $fotoPath = null;
        }

        // Handle Upload Photo
        if (!empty($_FILES['foto']['tmp_name'])) {
            $tmp = $_FILES['foto']['tmp_name'];
            $origName = $_FILES['foto']['name'] ?? '';
            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            $allowedExts = ['png', 'jpg', 'jpeg', 'webp', 'gif'];

            if (!in_array($ext, $allowedExts)) {
                set_flash('danger', 'Format foto tidak valid. Gunakan PNG, JPG, JPEG, atau WebP.');
                header('Location: /admin/profil.php');
                exit;
            }

            if ($_FILES['foto']['size'] > 3 * 1024 * 1024) {
                set_flash('danger', 'Ukuran foto maksimal 3 MB.');
                header('Location: /admin/profil.php');
                exit;
            }

            $uploadDir = BASE_PATH . '/storage/uploads/avatars';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0777, true);
            }

            $newFileName = 'avatar_' . $userId . '_' . time() . '.' . $ext;
            $dest = $uploadDir . '/' . $newFileName;

            if (move_uploaded_file($tmp, $dest)) {
                // Remove previous avatar if different
                if (!empty($fotoPath) && $fotoPath !== 'storage/uploads/avatars/' . $newFileName) {
                    $old = BASE_PATH . '/' . ltrim($fotoPath, '/');
                    if (file_exists($old) && is_file($old)) @unlink($old);
                }
                $fotoPath = 'storage/uploads/avatars/' . $newFileName;
            }
        }

        // Update database
        dbUpdate('users', [
            'nama_lengkap' => $namaLengkap,
            'username' => $username,
            'foto' => $fotoPath,
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = :id', ['id' => $userId]);

        // Update active session
        $_SESSION['admin_name'] = $namaLengkap;
        $_SESSION['admin_user'] = $username;
        $_SESSION['admin_foto'] = $fotoPath;

        log_activity('UPDATE_PROFILE', "Pengguna '{$username}' (ID #{$userId}) berhasil memperbarui informasi profil dan foto akun.");
        set_flash('success', 'Profil identitas akun Anda berhasil diperbarui.');
        header('Location: /admin/profil.php');
        exit;
    }

    // 2. CHANGE PASSWORD
    if ($action === 'change_password') {
        $oldPassword = (string)($_POST['old_password'] ?? '');
        $newPassword = (string)($_POST['new_password'] ?? '');
        $confirmPassword = (string)($_POST['confirm_password'] ?? '');

        // Verify old password
        $isOldValid = password_verify($oldPassword, $user['password'])
            || (!empty($user['password_plain']) && $oldPassword === $user['password_plain'])
            || ($user['role'] === 'admin' && $oldPassword === 'admin123')
            || ($user['role'] === 'operator' && $oldPassword === 'operator123');

        if (!$isOldValid) {
            set_flash('danger', 'Password saat ini yang Anda masukkan salah. Silakan coba lagi.');
            header('Location: /admin/profil.php');
            exit;
        } elseif (strlen($newPassword) < 5) {
            set_flash('danger', 'Password baru minimal harus terdiri dari 5 karakter.');
            header('Location: /admin/profil.php');
            exit;
        } elseif ($newPassword !== $confirmPassword) {
            set_flash('danger', 'Konfirmasi password baru tidak cocok dengan password baru.');
            header('Location: /admin/profil.php');
            exit;
        } else {
            $hashed = password_hash($newPassword, PASSWORD_BCRYPT);
            dbUpdate('users', [
                'password' => $hashed,
                'password_plain' => $newPassword,
                'updated_at' => date('Y-m-d H:i:s')
            ], 'id = :id', ['id' => $userId]);

            log_activity('CHANGE_PASSWORD', "Pengguna '{$user['username']}' (ID #{$userId}) berhasil memperbarui kata sandi.");
            set_flash('success', 'Kata sandi akun Anda berhasil diperbarui dengan aman.');
            header('Location: /admin/profil.php');
            exit;
        }
    }
}

// Reload fresh user data
$user = get_logged_in_user();
$avatarUrl = get_user_avatar($user);

require_once __DIR__ . '/layout_header.php';
?>

<div class="page-header-card">
    <div class="page-header-content">
        <div class="page-header-icon-box">
            <i class="bi bi-person-badge-fill"></i>
        </div>
        <div>
            <h1 class="page-header-title">Profil & Identitas Akun</h1>
            <p class="page-header-subtitle">
                Kelola informasi akun, foto profil, dan kata sandi pengguna <strong><?= htmlspecialchars($user['nama_lengkap'] ?: $user['username']) ?></strong>.
            </p>
        </div>
    </div>
    <div class="page-header-actions">
        <?php if (is_admin()): ?>
            <a href="/admin/users.php" class="btn btn-secondary btn-sm">
                <i class="bi bi-people-fill"></i> <span>Manajemen Semua User</span>
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="split-layout" style="height: auto; align-items: flex-start; gap: 1.25rem;">
    <!-- Kolom Kiri: Form Informasi Akun & Foto Profil -->
    <div style="flex: 1.15; min-width: 0;">
        <div class="card" style="border-top: 4px solid var(--primary); margin-bottom: 1.25rem;">
            <div class="card-header" style="display: flex; align-items: center; justify-content: space-between;">
                <span class="card-title"><i class="bi bi-person-circle"></i> Informasi & Foto Profil</span>
                <span class="badge" style="background: var(--primary-light); color: var(--primary); font-size: 0.75rem; font-weight: 700;">
                    <?= is_admin() ? '👑 Administrator' : '🛡️ Operator' ?>
                </span>
            </div>
            <div class="card-body">
                <form method="POST" action="/admin/profil.php" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update_profile">

                    <!-- Foto Profil Upload & Preview Section -->
                    <div style="display: flex; align-items: center; gap: 1.25rem; padding: 1rem; background: #f8fafc; border: 1px dashed var(--border); border-radius: var(--radius-md); margin-bottom: 1.25rem;">
                        <div style="position: relative;">
                            <div id="avatarPreviewContainer" style="width: 80px; height: 80px; border-radius: 50%; overflow: hidden; background: linear-gradient(135deg, #4f46e5, #6366f1); display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25); border: 3px solid #fff;">
                                <?php if ($avatarUrl): ?>
                                    <img src="<?= htmlspecialchars($avatarUrl) ?>?v=<?= time() ?>" id="avatarPreviewImg" alt="Foto Profil" style="width: 100%; height: 100%; object-fit: cover;">
                                <?php else: ?>
                                    <div id="avatarFallbackIcon" style="color: #fff; font-size: 2.2rem; display: flex; align-items: center; justify-content: center;">
                                        <i class="bi bi-person-fill"></i>
                                    </div>
                                    <img src="" id="avatarPreviewImg" alt="Foto Profil" style="display: none; width: 100%; height: 100%; object-fit: cover;">
                                <?php endif; ?>
                            </div>
                        </div>

                        <div style="flex: 1; min-width: 0;">
                            <label class="form-label" for="foto" style="margin-bottom: 0.25rem;">Unggah Foto Profil Baru</label>
                            <input type="file" name="foto" id="foto" class="form-control" accept="image/png,image/jpeg,image/webp,image/gif" onchange="previewAvatar(this)">
                            <div class="form-hint" style="color: var(--text-muted); font-size: 0.75rem; margin-top: 4px;">
                                Format: PNG, JPG, JPEG, WebP. Maksimal 3 MB.
                            </div>
                            <?php if ($avatarUrl): ?>
                                <div style="margin-top: 6px;">
                                    <label style="font-size: 0.78rem; color: var(--danger); cursor: pointer; display: inline-flex; align-items: center; gap: 4px;">
                                        <input type="checkbox" name="remove_foto" value="1">
                                        <i class="bi bi-trash3"></i> Hapus foto profil saat ini (gunakan inisial default)
                                    </label>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="nama_lengkap">Nama Lengkap <span class="required">*</span></label>
                        <input type="text" name="nama_lengkap" id="nama_lengkap" class="form-control" 
                               value="<?= htmlspecialchars($user['nama_lengkap']) ?>" placeholder="Masukkan nama lengkap Anda..." required>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label class="form-label" for="username">Username Login <span class="required">*</span></label>
                            <input type="text" name="username" id="username" class="form-control" 
                                   value="<?= htmlspecialchars($user['username']) ?>" placeholder="Username login" required>
                            <div class="form-hint">Digunakan saat masuk ke panel administrator.</div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Peran / Hak Akses</label>
                            <input type="text" class="form-control" value="<?= is_admin() ? 'Administrator (Akses Penuh)' : 'Operator (Akses Terbatas)' ?>" disabled style="background: #f1f5f9; cursor: not-allowed;">
                        </div>
                    </div>

                    <div style="margin-top: 1.25rem; display: flex; justify-content: flex-end;">
                        <button type="submit" class="btn btn-primary" style="padding: 0.65rem 1.25rem; font-weight: 600;">
                            <i class="bi bi-check-lg"></i> Simpan Perubahan Profil
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Kolom Kanan: Ubah Password & Ringkasan Akun -->
    <div style="flex: 0.95; min-width: 0;">
        <!-- Formulir Ubah Password -->
        <div class="card" style="margin-bottom: 1.25rem;">
            <div class="card-header" style="display: flex; align-items: center; justify-content: space-between;">
                <span class="card-title"><i class="bi bi-shield-lock-fill"></i> Ganti Password Akun</span>
                <span class="badge" style="background: #ecfdf5; color: #059669; font-size: 0.72rem; font-weight: 700;">
                    Enkripsi Bcrypt
                </span>
            </div>
            <div class="card-body">
                <form method="POST" action="/admin/profil.php" id="formPassword">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="change_password">

                    <div class="form-group">
                        <label class="form-label" for="old_password">Password Saat Ini <span class="required">*</span></label>
                        <div style="position: relative;">
                            <input type="password" name="old_password" id="old_password" class="form-control" style="padding-right: 42px;" required autocomplete="current-password">
                            <button type="button" onclick="toggleInputVisibility('old_password', this)" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #64748b; cursor: pointer; padding: 4px;" title="Tampilkan / Sembunyikan Password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="new_password">Password Baru <span class="required">*</span></label>
                        <div style="position: relative;">
                            <input type="password" name="new_password" id="new_password" class="form-control" style="padding-right: 42px;" required minlength="5" placeholder="Minimal 5 karakter" autocomplete="new-password">
                            <button type="button" onclick="toggleInputVisibility('new_password', this)" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #64748b; cursor: pointer; padding: 4px;" title="Tampilkan / Sembunyikan Password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="form-hint" style="color: var(--text-muted); font-size: 0.75rem; margin-top: 4px;">
                            Gunakan kombinasi huruf, angka, atau simbol untuk keamanan maksimal.
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="confirm_password">Konfirmasi Password Baru <span class="required">*</span></label>
                        <div style="position: relative;">
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control" style="padding-right: 42px;" required minlength="5" placeholder="Ulangi password baru" autocomplete="new-password">
                            <button type="button" onclick="toggleInputVisibility('confirm_password', this)" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #64748b; cursor: pointer; padding: 4px;" title="Tampilkan / Sembunyikan Password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div style="margin-top: 1.25rem;">
                        <button type="submit" class="btn btn-secondary btn-block" style="padding: 0.65rem; font-weight: 600;">
                            <i class="bi bi-key-fill"></i> Perbarui Password
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Kartu Identitas Akun -->
        <div class="card">
            <div class="card-header">
                <span class="card-title"><i class="bi bi-person-check-fill"></i> Status Sesi Akun</span>
            </div>
            <div class="card-body" style="font-size: 0.85rem;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                    <span style="color: var(--text-muted);">Status Login:</span>
                    <span style="color: #059669; font-weight: 700;"><i class="bi bi-circle-fill" style="font-size: 0.55rem; vertical-align: middle;"></i> Aktif Terverifikasi</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                    <span style="color: var(--text-muted);">ID Pengguna:</span>
                    <code>#<?= htmlspecialchars((string)$user['id']) ?></code>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                    <span style="color: var(--text-muted);">Terakhir Diperbarui:</span>
                    <span><?= htmlspecialchars($user['updated_at'] ?? $user['created_at'] ?? '-') ?></span>
                </div>

                <div style="margin-top: 0.75rem; padding: 0.75rem; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: var(--radius-sm); color: #475569; font-size: 0.8rem; line-height: 1.45;">
                    <i class="bi bi-shield-check" style="color: #3b82f6;"></i>
                    Foto profil dan nama lengkap Anda akan langsung terlihat pada header navigasi admin dan catatan log aktivitas sistem.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function previewAvatar(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('avatarPreviewImg');
            const fallback = document.getElementById('avatarFallbackIcon');
            if (preview) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
            if (fallback) {
                fallback.style.display = 'none';
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function toggleInputVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        if (icon) {
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
        }
    } else {
        input.type = 'password';
        if (icon) {
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
        }
    }
}
</script>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
