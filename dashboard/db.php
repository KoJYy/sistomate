<?php
// db.php - koneksi PDO bersama + penjaga sesi untuk seluruh halaman web SISTOMATE.
// Env dibaca dari /etc/sistomate-ingest.env (produksi) atau variabel lingkungan
// SISTOMATE_ENV (dipakai harness uji tools/test-web.sh -> DB test terisolasi).

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $path = getenv('SISTOMATE_ENV') ?: '/etc/sistomate-ingest.env';
        $env = parse_ini_file($path);
        if ($env === false) {
            http_response_code(500);
            exit('Konfigurasi database tidak terbaca.');
        }
        $pdo = new PDO(
            "mysql:host={$env['DB_HOST']};dbname={$env['DB_NAME']};charset=utf8mb4",
            $env['DB_USER'],
            $env['DB_PASS'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    }
    return $pdo;
}

function e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

// Wajib login: belum login -> dilempar ke login.php.
function require_login(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

// Data pengguna yang sedang login (untuk navbar dsb.).
function current_user(): array
{
    $stmt = db()->prepare('SELECT id_user, name, email FROM users WHERE id_user = ?');
    $stmt->execute([(int) $_SESSION['user_id']]);
    $row = $stmt->fetch();
    return $row ?: ['id_user' => (int) $_SESSION['user_id'], 'name' => 'Pengguna', 'email' => ''];
}

// Bagian navbar bersama (aktif = halaman berjalan). Tema Archivo/sage.
// Single source of truth utk SEMUA halaman (app/history/settings) -> posisi nav
// TERKUNCI sama (tak melompat). Struktur flex 2 sisi:
//   kiri  .brand-group = brand + nav pil BERDAMPINGAN (gap 24px)
//   kanan .header-right = [slot live bila $live] + nama user + Logout
// $live=true hanya di app.php: render elemen dgn id clock/dot/livetxt persis
// agar JS app.php (setInterval jam + indikator koneksi) tetap menemukannya.
function navbar(string $active, string $userName, bool $live = false): string
{
    $items = [
        'app'      => ['app.php',      'Dashboard'],
        'history'  => ['history.php',  'Riwayat'],
        'settings' => ['settings.php', 'Pengaturan'],
    ];
    $nav = '';
    foreach ($items as $key => [$href, $label]) {
        $cls = $key === $active ? ' class="on"' : '';
        $nav .= '<a href="' . $href . '"' . $cls . '>' . e($label) . '</a>';
    }
    $right = '';
    if ($live) {
        $right .= '<div class="live">'
            . '<span class="dot" id="dot"></span>'
            . '<b id="livetxt">LIVE</b>'
            . '<span id="clock">--:--:--</span>'
            . '</div>';
    }
    $right .= '<span class="uname">&#128100; ' . e($userName) . '</span>';
    $right .= '<a class="logout" href="logout.php">Logout</a>';
    return '<header>'
        . '<div class="brand-group">'
        .   '<div class="brand">SISTOMATE <span>greenhouse tomat</span></div>'
        .   '<nav class="navbar">' . $nav . '</nav>'
        . '</div>'
        . '<div class="header-right">' . $right . '</div>'
        . '</header>';
}
