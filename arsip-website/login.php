<?php
// login.php - autentikasi dashboard SISTOMATE (email + password -> tabel users).
require_once __DIR__ . '/db.php';

session_start();
// Sudah login? langsung ke dashboard.
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
<title>Masuk - SISTOMATE</title>
<link rel="icon" type="image/svg+xml" href="favicon.svg">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Instrument+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="theme.css?v=35">
</head>
<body>
<a class="skip-link" href="#main">Lewati ke konten utama</a>
<header class="site-header" id="siteHeader">
  <div class="header-inner">
    <a class="brand" href="index.html">
      <?= brand_logo_svg() ?>
      <span>SISTOMATE</span>
    </a>
    <nav class="navbar" aria-label="Navigasi masuk">
      <a href="index.html">Beranda</a>
      <a href="login.php" class="on" aria-current="page">Masuk</a>
    </nav>
    <?php if (file_exists(__DIR__ . '/demo.php')): ?>
      <div class="header-right">
        <a class="btn sm" href="demo.php">Mode Demo</a>
      </div>
    <?php endif; ?>
  </div>
</header>
<main class="login-page" id="main" tabindex="-1">
  <div class="card login-card">
    <div class="login-head">
      <!-- The Botanical Crest (Opsi A) -->
      <div class="mark">
        <img src="logo.svg" alt="" width="52" height="52" decoding="sync">
      </div>
      <h1>SISTOMATE</h1>
      <p>Monitoring &amp; otomasi tanaman tomat</p>
    </div>

    <?php if ($error !== ''): ?>
      <div class="alert err" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="login.php" id="loginForm" novalidate>
      <div class="field">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" autocomplete="username"
               inputmode="email" spellcheck="false" autocorrect="off" autocapitalize="none"
               enterkeyhint="next" required autofocus
               aria-invalid="false" aria-errormessage="email-err"
               value="<?= e($_POST['email'] ?? '') ?>">
        <span class="error-msg" id="email-err" role="alert">Masukkan alamat email yang valid.</span>
      </div>
      <div class="field mt-block">
        <label for="password">Password</label>
        <div class="pw-wrap">
          <input type="password" id="password" name="password" autocomplete="current-password"
                 enterkeyhint="done" required aria-invalid="false" aria-errormessage="pw-err">
          <button type="button" class="pw-eye" id="pwEye" aria-label="Tampilkan password" aria-pressed="false">
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
        <span class="error-msg" id="pw-err" role="alert">Password wajib diisi.</span>
      </div>
      <div class="mt-block">
        <button type="submit" class="btn block" id="loginBtn">Masuk ke dashboard</button>
      </div>
    </form>

    <?php if (file_exists(__DIR__ . '/demo.php')): ?>
      <div class="login-demo">
        <div class="login-demo-copy">
          <b>Akses Demo</b>
          <span>Masuk langsung tanpa akun</span>
        </div>
        <div class="login-demo-actions">
          <a class="btn ghost sm" href="demo.php">Masuk demo</a>
        </div>
      </div>
    <?php endif; ?>

    <div class="login-foot">
      <a class="link" href="index.html">&larr; Kembali ke beranda</a>
    </div>
  </div>
</main>
<script>
const siteHdr = document.getElementById('siteHeader');
if (siteHdr) {
  const syncHdr = () => siteHdr.classList.toggle('scrolled', window.scrollY > 4);
  syncHdr();
  addEventListener('scroll', syncHdr, { passive: true });
}
const pwInput = document.getElementById('password');
const pwBtn = document.getElementById('pwEye');
pwBtn.addEventListener('click', () => {
  const show = pwInput.type === 'password';
  pwInput.type = show ? 'text' : 'password';
  pwInput.focus({ preventScroll: true });
  pwBtn.setAttribute('aria-pressed', show ? 'true' : 'false');
  pwBtn.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');
  pwBtn.querySelector('.eye-open').style.display = show ? 'none' : '';
  pwBtn.querySelector('.eye-shut').style.display = show ? '' : 'none';
});

// Validasi setelah interaksi agar tidak mengganggu sebelum mengetik
const form = document.getElementById('loginForm');
const emailInput = document.getElementById('email');
function syncValidity(input, msgEl) {
  const ok = input.checkValidity();
  input.setAttribute('aria-invalid', ok ? 'false' : 'true');
  if (msgEl) {
    msgEl.textContent = input.validity.valueMissing
      ? (input.id === 'email' ? 'Email wajib diisi.' : 'Password wajib diisi.')
      : 'Format email belum sesuai.';
  }
  return ok;
}
[
  [emailInput, document.getElementById('email-err')],
  [pwInput, document.getElementById('pw-err')]
].forEach(([el, errEl]) => {
  let touched = false;
  el.addEventListener('blur', () => {
    if (el.value !== '' || touched) { touched = true; syncValidity(el, errEl); }
  });
  el.addEventListener('input', () => {
    if (touched || el.getAttribute('aria-invalid') === 'true') syncValidity(el, errEl);
  });
});
form.addEventListener('submit', e => {
  const okEmail = syncValidity(emailInput, document.getElementById('email-err'));
  const okPw = syncValidity(pwInput, document.getElementById('pw-err'));
  if (!okEmail || !okPw) {
    e.preventDefault();
    (!okEmail ? emailInput : pwInput).focus();
  }
});
</script>
</body>
</html>
