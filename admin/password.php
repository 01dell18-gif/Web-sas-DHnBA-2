<?php
require_once __DIR__ . '/../config/functions.php';
require_admin();

// Handle POST change password
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $oldPassword = (string)($_POST['old_password'] ?? '');
    $newPassword = (string)($_POST['new_password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');

    $userId = (int)($_SESSION['admin_id'] ?? 0);
    $user = null;
    if ($userId > 0) {
        $user = dbFetch("SELECT * FROM users WHERE id = :id", ['id' => $userId]);
    }
    if (!$user && !empty($_SESSION['admin_user'])) {
        $user = dbFetch("SELECT * FROM users WHERE username = :u", ['u' => $_SESSION['admin_user']]);
        if ($user) {
            $userId = (int)$user['id'];
        }
    }

    if (!$user) {
        set_flash('danger', 'Akun pengguna tidak ditemukan dalam database.');
        header('Location: /admin/password.php');
        exit;
    }

    // Verify old password against bcrypt hash, plain fallback, or initial defaults
    $isOldValid = password_verify($oldPassword, $user['password'])
        || (!empty($user['password_plain']) && $oldPassword === $user['password_plain'])
        || ($user['role'] === 'admin' && $oldPassword === 'admin123')
        || ($user['role'] === 'operator' && $oldPassword === 'operator123');

    if (!$isOldValid) {
        set_flash('danger', 'Password saat ini yang Anda masukkan salah. Silakan coba lagi.');
        header('Location: /admin/password.php');
        exit;
    } elseif (strlen($newPassword) < 5) {
        set_flash('danger', 'Password baru minimal harus terdiri dari 5 karakter.');
        header('Location: /admin/password.php');
        exit;
    } elseif ($newPassword !== $confirmPassword) {
        set_flash('danger', 'Konfirmasi password baru tidak cocok dengan password baru.');
        header('Location: /admin/password.php');
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
        header('Location: /admin/password.php');
        exit;
    }
}

$pageTitle = 'Ganti Password Akun';
require_once __DIR__ . '/layout_header.php';
?>

<div class="page-header-card">
    <div class="page-header-content">
        <div class="page-header-icon-box">
            <i class="bi bi-key-fill"></i>
        </div>
        <div>
            <h1 class="page-header-title">Ganti Password Akun</h1>
            <p class="page-header-subtitle">
                Perbarui kata sandi akun <strong><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Pengguna') ?></strong> (<?= is_admin() ? 'Administrator' : 'Operator' ?>) untuk menjaga keamanan sistem.
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

<div style="display: grid; grid-template-columns: minmax(0, 520px) minmax(0, 380px); gap: 1.25rem; align-items: flex-start; max-width: 960px;">
    <!-- Formulir Ubah Password -->
    <div class="card" style="border-top: 4px solid var(--primary);">
        <div class="card-header" style="display: flex; align-items: center; justify-content: space-between;">
            <span class="card-title"><i class="bi bi-shield-lock-fill"></i> Formulir Ubah Kata Sandi</span>
            <span class="badge" style="background: var(--primary-light); color: var(--primary); font-size: 0.75rem; font-weight: 700;">
                Enkripsi Bcrypt
            </span>
        </div>
        <div class="card-body">
            <form method="POST" action="/admin/password.php" id="formPassword">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label class="form-label" for="old_password">Password Saat Ini <span class="required">*</span></label>
                    <div style="position: relative;">
                        <input type="password" name="old_password" id="old_password" class="form-control" style="padding-right: 42px;" required autofocus autocomplete="current-password">
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
                    <div class="form-hint" style="color: var(--text-muted); font-size: 0.8rem; margin-top: 4px;">
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

                <div style="margin-top: 1.5rem; display: flex; gap: 0.75rem;">
                    <button type="submit" class="btn btn-primary btn-block" style="padding: 0.75rem; font-weight: 700;">
                        <i class="bi bi-check2-circle"></i> Perbarui Password
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Informasi Akun Saat Ini -->
    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="bi bi-person-badge"></i> Akun Anda</span>
        </div>
        <div class="card-body" style="font-size: 0.875rem;">
            <div style="display: flex; align-items: center; gap: 0.85rem; margin-bottom: 1.25rem;">
                <div style="width: 52px; height: 52px; border-radius: 14px; background: linear-gradient(135deg, #4f46e5, #6366f1); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0; box-shadow: 0 4px 10px rgba(79, 70, 229, 0.25);">
                    <i class="bi bi-person-fill"></i>
                </div>
                <div>
                    <div style="font-weight: 800; color: #0f172a; font-size: 1rem;">
                        <?= htmlspecialchars($_SESSION['admin_name'] ?? 'Pengguna') ?>
                    </div>
                    <div style="color: #64748b; font-size: 0.825rem;">
                        Username: <code><?= htmlspecialchars($_SESSION['admin_user'] ?? '-') ?></code>
                    </div>
                </div>
            </div>

            <div style="border-top: 1px solid var(--border); padding-top: 0.85rem;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                    <span style="color: var(--text-muted);">Peran / Akses:</span>
                    <span class="<?= is_admin() ? 'badge-role-admin' : 'badge-role-operator' ?>">
                        <?= is_admin() ? '👑 Administrator' : '🛡️ Operator' ?>
                    </span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                    <span style="color: var(--text-muted);">Status Sesi:</span>
                    <span style="color: #059669; font-weight: 700;"><i class="bi bi-circle-fill" style="font-size: 0.55rem; vertical-align: middle;"></i> Aktif Terverifikasi</span>
                </div>
            </div>

            <div style="margin-top: 1.25rem; padding: 0.85rem; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 10px; color: #475569; font-size: 0.815rem; line-height: 1.5;">
                <i class="bi bi-info-circle-fill" style="color: #3b82f6;"></i>
                Setelah mengganti password, Anda tetap dapat melanjutkan penggunaan sistem tanpa perlu login ulang. Simpan password baru di tempat yang aman.
            </div>
        </div>
    </div>
</div>

<script>
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
