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
      <input type="password" id="password" name="password" required>
      <div class="mt-block">
        <button type="submit" class="btn block">Masuk</button>
      </div>
    </form>
    <div class="center mt-block">
      <a class="link" href="index.html">&larr; Kembali ke beranda</a>
    </div>
  </div>
</div>
</body>
</html>
