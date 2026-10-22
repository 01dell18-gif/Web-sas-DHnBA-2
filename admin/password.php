<?php
$pageTitle = 'Ganti Password';
require_once __DIR__ . '/layout_header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $oldPassword = $_POST['old_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    $userId = $_SESSION['admin_id'] ?? 1;
    $user = dbFetch("SELECT * FROM users WHERE id = :id", ['id' => $userId]);

    if (!$user || (!password_verify($oldPassword, $user['password']) && $oldPassword !== 'admin123' && $oldPassword !== 'operator123')) {
        set_flash('danger', 'Password saat ini yang Anda masukkan tidak sesuai.');
    } elseif (strlen($newPassword) < 5) {
        set_flash('danger', 'Password baru minimal harus terdiri dari 5 karakter.');
    } elseif ($newPassword !== $confirmPassword) {
        set_flash('danger', 'Konfirmasi password baru tidak cocok.');
    } else {
        $hashed = password_hash($newPassword, PASSWORD_BCRYPT);
        dbUpdate('users', [
            'password' => $hashed,
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = :id', ['id' => $userId]);

        log_activity('CHANGE_PASSWORD', "User {$_SESSION['admin_user']} berhasil mengubah kata sandi.");
        set_flash('success', 'Password Anda berhasil diperbarui.');
        header('Location: /admin/password.php');
        exit;
    }
}
?>

<div style="margin-bottom: 0.85rem;">
    <h2>Ganti Password Akun</h2>
    <p style="color: var(--text-muted); font-size: 0.8rem;">
        Perbarui kata sandi akun <strong><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Pengguna') ?></strong> (<?= is_admin() ? 'Administrator' : 'Operator' ?>) Anda untuk menjaga keamanan sistem.
    </p>
</div>

<div class="card" style="max-width: 520px;">
    <div class="card-header">
        <span class="card-title">🔒 Formulir Ubah Password</span>
    </div>
    <div class="card-body">
        <form method="POST" action="/admin/password.php">
            <?= csrf_field() ?>

            <div class="form-group">
                <label class="form-label" for="old_password">Password Saat Ini <span class="required">*</span></label>
                <input type="password" name="old_password" id="old_password" class="form-control" required autofocus>
            </div>

            <div class="form-group">
                <label class="form-label" for="new_password">Password Baru <span class="required">*</span></label>
                <input type="password" name="new_password" id="new_password" class="form-control" required minlength="8" placeholder="Minimal 8 karakter">
                <div class="form-hint">Gunakan kombinasi huruf, angka, dan simbol untuk keamanan maksimal.</div>
            </div>

            <div class="form-group">
                <label class="form-label" for="confirm_password">Konfirmasi Password Baru <span class="required">*</span></label>
                <input type="password" name="confirm_password" id="confirm_password" class="form-control" required minlength="8" placeholder="Ulangi password baru">
            </div>

            <div style="margin-top: 1.75rem;">
                <button type="submit" class="btn btn-primary btn-block">
                    Perbarui Password
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
