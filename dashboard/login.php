<?php
// login.php - autentikasi dashboard SISTOMATE (email + password -> tabel users).
require_once __DIR__ . '/db.php';

session_start();
// Sudah login? langsung ke app.
if (!empty($_SESSION['user_id'])) {
    header('Location: app.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    $pass  = (string) ($_POST['password'] ?? '');

    $stmt = db()->prepare('SELECT id_user, name, password_hash FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($pass, $user['password_hash'])) {
        session_regenerate_id(true);          // cegah session fixation
        $_SESSION['user_id'] = (int) $user['id_user'];
        $_SESSION['user_name'] = $user['name'];
        header('Location: app.php');
        exit;
    }
    $error = 'Email atau password salah.';
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login - SISTOMATE</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Archivo:wght@400;500;600;700&family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="theme.css">
<style>
  /* tombol lihat sandi: menempel di kanan input password */
  .pw-wrap { position: relative; }
  .pw-wrap input { padding-right: 44px; }
  .pw-eye {
    position: absolute; right: 6px; top: 50%; transform: translateY(-50%);
    display: grid; place-items: center;
    width: 34px; height: 34px;
    background: none; border: 0; border-radius: 8px;
    color: var(--muted); cursor: pointer;
    transition: color .15s, background .15s;
  }
  .pw-eye:hover, .pw-eye:focus-visible { color: var(--ink); background: var(--line-soft); }
  .pw-eye svg {
    width: 19px; height: 19px;
    fill: none; stroke: currentColor;
    stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round;
  }
</style>
</head>
<body>
<div class="login-wrap">
  <div class="panel login-card">
    <div class="center">
      <div class="mark">&#127793;</div>
      <h1>SISTOMATE</h1>
      <p class="muted small">Monitoring &amp; Otomasi Greenhouse Tomat</p>
    </div>
    <?php if ($error !== ''): ?>
      <div class="alert err mt-block" role="alert"><?= e($error) ?></div>
    <?php endif; ?>
    <form method="post" action="login.php">
      <label class="f mt-block" for="email">Email</label>
      <input type="email" id="email" name="email"
             required autofocus value="<?= e($_POST['email'] ?? '') ?>">
      <label class="f" for="password">Password</label>
      <div class="pw-wrap">
        <input type="password" id="password" name="password" required>
        <button type="button" class="pw-eye" id="pwEye" aria-label="Tampilkan password"
                onpointerdown="pwShow(true)" onpointerup="pwShow(false)" onpointerleave="pwShow(false)">
          <svg class="eye-open" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M2 12s3.5-6.5 10-6.5S22 12 22 12s-3.5 6.5-10 6.5S2 12 2 12z"/>
            <circle cx="12" cy="12" r="2.8"/>
          </svg>
          <svg class="eye-shut" viewBox="0 0 24 24" aria-hidden="true" style="display:none">
            <path d="M3 3l18 18"/>
            <path d="M10.6 6.2A10.9 10.9 0 0 1 12 6c6.5 0 10 6 10 6a18.4 18.4 0 0 1-3.1 3.9"/>
            <path d="M6.6 7.9C3.9 9.6 2 12 2 12s3.5 6.5 10 6.5c1.4 0 2.7-.3 3.8-.8"/>
            <path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/>
          </svg>
        </button>
      </div>
      <div class="mt-block">
        <button type="submit" class="btn block">Masuk</button>
      </div>
    </form>
    <div class="center mt-block">
      <a class="link" href="index.html">&larr; Kembali ke beranda</a>
    </div>
  </div>
</div>
<script>
function pwShow(show) {
  const i = document.getElementById('password');
  i.type = show ? 'text' : 'password';
  document.querySelector('.eye-open').style.display = show ? 'none' : '';
  document.querySelector('.eye-shut').style.display = show ? '' : 'none';
}
</script>
</body>
</html>
