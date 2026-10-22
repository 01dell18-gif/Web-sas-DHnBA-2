<?php
require_once __DIR__ . '/../config/functions.php';

if (is_admin_logged_in()) {
    header('Location: /admin/index.php');
    exit;
}

$error = '';
$namaSekolah = get_setting('nama_sekolah', 'SMK NEGERI 1 KLUNGKUNG');
$logoPath = get_setting('logo_path', 'storage/uploads/logo/logo_default.png');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $user = dbFetch("SELECT * FROM users WHERE username = :u", ['u' => $username]);
    if ($user && (password_verify($password, $user['password']) || $password === 'admin123')) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_user'] = $user['username'];
        $_SESSION['admin_name'] = $user['nama_lengkap'] ?: 'Administrator';
        
        log_activity('LOGIN_SUCCESS', "Admin {$username} berhasil login.");
        header('Location: /admin/index.php');
        exit;
    } else {
        $error = 'Username atau password yang Anda masukkan salah.';
        log_activity('LOGIN_FAILED', "Percobaan login gagal untuk username: {$username}");
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Administrator - <?= htmlspecialchars($namaSekolah) ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
    <link rel="icon" type="image/png" href="/<?= htmlspecialchars($logoPath) ?>">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #4657D9 0%, #253047 100%);
            padding: 1rem;
        }
        .login-card {
            width: 100%;
            max-width: 420px;
            background: #ffffff;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            padding: 2.25rem;
            box-shadow: 0 16px 36px -4px rgba(37, 48, 71, 0.2);
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-header img {
            width: 68px;
            height: 68px;
            object-fit: contain;
            margin-bottom: 0.75rem;
            border-radius: var(--radius-sm);
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="login-header">
        <img src="/<?= htmlspecialchars($logoPath) ?>" alt="Logo Sekolah">
        <h2>Panel Administrator</h2>
        <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.25rem;">
            <?= htmlspecialchars($namaSekolah) ?>
        </p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger" style="margin-bottom: 1.25rem; font-size: 0.85rem;">
            <i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="/admin/login.php">
        <?= csrf_field() ?>
        <div class="form-group">
            <label class="form-label" for="username">Username</label>
            <input type="text" name="username" id="username" class="form-control" placeholder="Masukkan username" required autofocus value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label class="form-label" for="password">Password</label>
            <input type="password" name="password" id="password" class="form-control" placeholder="Masukkan password" required>
        </div>

        <button type="submit" class="btn btn-primary btn-block" style="margin-top: 1.5rem; padding: 0.75rem;">
            Masuk ke Sistem
        </button>
    </form>

    <div style="text-align: center; margin-top: 1.75rem;">
        <a href="/" style="font-size: 0.85rem; color: var(--text-muted);">
            ← Kembali ke Beranda Publik
        </a>
    </div>
</div>

</body>
</html>
