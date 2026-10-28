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
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  body { min-height: 100vh; display: grid; place-items: center;
         background: linear-gradient(135deg, #e8f5e9, #fff8e1); }
  .login-card { max-width: 400px; width: 100%; border: none; border-radius: 1rem;
                box-shadow: 0 10px 30px rgba(0,0,0,.08); }
</style>
</head>
<body>
<div class="card login-card p-4">
  <div class="text-center mb-3">
    <div style="font-size:2.5rem">🌱</div>
    <h1 class="h4 fw-bold mb-0">SISTOMATE</h1>
    <small class="text-muted">Monitoring &amp; Otomasi Greenhouse Tomat</small>
  </div>
  <?php if ($error !== ''): ?>
    <div class="alert alert-danger py-2" role="alert"><?= e($error) ?></div>
  <?php endif; ?>
  <form method="post" action="login.php">
    <div class="mb-3">
      <label for="email" class="form-label">Email</label>
      <input type="email" class="form-control" id="email" name="email"
             required autofocus value="<?= e($_POST['email'] ?? '') ?>">
    </div>
    <div class="mb-3">
      <label for="password" class="form-label">Password</label>
      <input type="password" class="form-control" id="password" name="password" required>
    </div>
    <button type="submit" class="btn btn-success w-100">Masuk</button>
  </form>
  <div class="text-center mt-3">
    <a href="index.html" class="small text-decoration-none">← Kembali ke beranda</a>
  </div>
</div>
</body>
</html>
