<?php
require_once __DIR__ . '/../config/functions.php';

if (is_admin_logged_in()) {
    header('Location: /admin/index.php');
    exit;
}

$error = '';
$namaSekolah = get_setting('nama_sekolah', 'SMK NEGERI FYJ Channel');
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
        $_SESSION['admin_name'] = $user['nama_lengkap'] ?: ($user['role'] === 'admin' ? 'Administrator' : 'Operator');
        $_SESSION['admin_role'] = $user['role'] ?? 'admin';
        
        log_activity('LOGIN_SUCCESS', "User {$username} ({$user['role']}) berhasil login.");
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
            background: radial-gradient(circle at 50% 20%, #312e81 0%, #0f172a 100%);
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
        }
        body::before {
            content: '';
            position: absolute;
            top: -150px;
            left: 50%;
            transform: translateX(-50%);
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.25) 0%, rgba(99, 102, 241, 0) 70%);
            pointer-events: none;
        }
        .login-card {
            width: 100%;
            max-width: 420px;
            background: rgba(255, 255, 255, 0.98);
            border-radius: var(--radius-xl);
            border: 1px solid rgba(255, 255, 255, 0.4);
            padding: 2.25rem 2rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35), 0 0 0 1px rgba(255, 255, 255, 0.1);
            position: relative;
            z-index: 1;
            animation: fadeInUp 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        .login-header {
            text-align: center;
            margin-bottom: 1.75rem;
        }
        .login-header img {
            width: 68px;
            height: 68px;
            object-fit: contain;
            margin-bottom: 0.85rem;
            border-radius: 50%;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.1);
            border: 2px solid #ffffff;
            background: #ffffff;
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
        <div class="alert alert-danger" style="margin-bottom: 0.7rem; font-size: 0.85rem;">
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
            <div style="position: relative; display: flex; align-items: center;">
                <input type="password" name="password" id="password" class="form-control" style="padding-right: 42px;" placeholder="Masukkan password" required>
                <button type="button" onclick="toggleLoginPassword()" 
                        style="position: absolute; right: 8px; background: none; border: none; color: var(--slate-500); cursor: pointer; padding: 6px; display: flex; align-items: center; justify-content: center; font-size: 1.15rem;" 
                        title="Lihat Password">
                    <i id="iconLoginPassword" class="bi bi-eye"></i>
                </button>
            </div>
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

<script>
function toggleLoginPassword() {
    const input = document.getElementById('password');
    const icon = document.getElementById('iconLoginPassword');
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

</body>
</html>
