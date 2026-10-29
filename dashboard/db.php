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
    $right .= '<span class="uname">' . e($userName) . '</span>';
    $right .= '<a class="logout" href="logout.php">Logout</a>';
    return '<header>'
        . '<div class="brand-group">'
        .   '<div class="brand"><svg class="brand-logo" viewBox="0 0 64 64" aria-hidden="true"><path d="M32 25.5c9.8 0 17.8 6.7 17.8 16 0 6.2-3.2 11.7-8.2 14.9-2.9 1.8-6.1 2.8-9.6 2.8-3.5 0-6.7-1-9.6-2.8-5-3.2-8.2-8.7-8.2-14.9 0-9.3 8-16 17.8-16z" fill="#167D47"/><path d="M18.6 43.5c-.8-6.7 1.6-13 6.6-17.4-3.3 4.3-4.7 9.5-3.9 14.6.6 4.1 2.5 7.8 5.3 10.5-3.5-1.4-6.5-3.9-8-7.7z" fill="#22A55F" opacity=".55"/><g fill="#E8F4EC" stroke="#0E4E2C" stroke-width="1.5" stroke-linejoin="round"><path d="M32 21.6C28.4 18.2 27.9 12 32 7 36.1 12 35.6 18.2 32 21.6Z"/><path d="M32 21.6C28.4 18.2 27.9 12 32 7 36.1 12 35.6 18.2 32 21.6Z" transform="rotate(72 32 19.5)"/><path d="M32 21.6C28.4 18.2 27.9 12 32 7 36.1 12 35.6 18.2 32 21.6Z" transform="rotate(-72 32 19.5)"/><path d="M32 21.6C28.4 18.2 27.9 12 32 7 36.1 12 35.6 18.2 32 21.6Z" transform="rotate(144 32 19.5)"/><path d="M32 21.6C28.4 18.2 27.9 12 32 7 36.1 12 35.6 18.2 32 21.6Z" transform="rotate(-144 32 19.5)"/></g><path d="M29.7 20.4L30.3 9.6Q32 8.2 33.7 9.8L34.3 20.5Z" fill="#0E4E2C"/><circle cx="32" cy="19.5" r="2.2" fill="#16241B"/></svg>SISTOMATE <span>tanaman tomat</span></div>'
        .   '<nav class="navbar">' . $nav . '</nav>'
        . '</div>'
        . '<div class="header-right">' . $right . '</div>'
        . '</header>';
}
