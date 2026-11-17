<?php
// db.php - koneksi PDO bersama + penjaga sesi untuk seluruh halaman web SISTOMATE.
// Env dibaca dari /etc/sistomate-ingest.env (produksi) atau variabel lingkungan
// SISTOMATE_ENV (dipakai harness uji tools/test-web.sh -> DB test terisolasi).

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $path = getenv('SISTOMATE_ENV')
            ?: (file_exists(__DIR__ . '/../.env') ? __DIR__ . '/../.env'
            : (file_exists(__DIR__ . '/.env') ? __DIR__ . '/.env'
            : '/etc/sistomate-ingest.env'));
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
// Single source of truth utk SEMUA halaman (app/history/settings).
// Sidebar vertikal di sebelah kiri desktop, responsif di mobile.
// $live=true hanya di app.php: render elemen dgn id clock/dot/livetxt persis
// agar JS app.php (setInterval jam + indikator koneksi) tetap menemukannya.
function navbar(string $active, string $userName, bool $live = false): string
{
    $items = [
        'app' => [
            'href' => 'app.php',
            'label' => 'Dashboard',
            'icon' => '<svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>'
        ],
        'history' => [
            'href' => 'history.php',
            'label' => 'Riwayat',
            'icon' => '<svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/></svg>'
        ],
        'settings' => [
            'href' => 'settings.php',
            'label' => 'Pengaturan',
            'icon' => '<svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>'
        ],
    ];

    $nav = '';
    foreach ($items as $key => $item) {
        $isOn = $key === $active;
        $cls = 'nav-item' . ($isOn ? ' on' : '');
        $aria = $isOn ? ' aria-current="page"' : '';
        $nav .= '<a href="' . $item['href'] . '" class="' . $cls . '"' . $aria . '>'
            . $item['icon']
            . '<span>' . e($item['label']) . '</span>'
            . '</a>';
    }

    $liveHtml = '';
    if ($live) {
        $liveHtml = '<div class="live">'
            . '<span class="dot" id="dot"></span>'
            . '<b id="livetxt">LIVE</b>'
            . '<span id="clock">--:--:--</span>'
            . '</div>';
    }

    $userInitial = mb_strtoupper(mb_substr($userName !== '' ? $userName : 'U', 0, 1));

    return '<aside class="sidebar">'
        . '<div class="sidebar-brand">'
        .   '<a href="app.php" class="brand">'
        .     '<svg class="brand-logo" viewBox="0 0 64 64" aria-hidden="true"><path d="M32 25.5c9.8 0 17.8 6.7 17.8 16 0 6.2-3.2 11.7-8.2 14.9-2.9 1.8-6.1 2.8-9.6 2.8-3.5 0-6.7-1-9.6-2.8-5-3.2-8.2-8.7-8.2-14.9 0-9.3 8-16 17.8-16z" fill="#167D47"/><path d="M18.6 43.5c-.8-6.7 1.6-13 6.6-17.4-3.3 4.3-4.7 9.5-3.9 14.6.6 4.1 2.5 7.8 5.3 10.5-3.5-1.4-6.5-3.9-8-7.7z" fill="#22A55F" opacity=".55"/><g fill="#E8F4EC" stroke="#0E4E2C" stroke-width="1.5" stroke-linejoin="round"><path d="M32 21.6C28.4 18.2 27.9 12 32 7 36.1 12 35.6 18.2 32 21.6Z"/><path d="M32 21.6C28.4 18.2 27.9 12 32 7 36.1 12 35.6 18.2 32 21.6Z" transform="rotate(72 32 19.5)"/><path d="M32 21.6C28.4 18.2 27.9 12 32 7 36.1 12 35.6 18.2 32 21.6Z" transform="rotate(-72 32 19.5)"/><path d="M32 21.6C28.4 18.2 27.9 12 32 7 36.1 12 35.6 18.2 32 21.6Z" transform="rotate(144 32 19.5)"/><path d="M32 21.6C28.4 18.2 27.9 12 32 7 36.1 12 35.6 18.2 32 21.6Z" transform="rotate(-144 32 19.5)"/></g><path d="M29.7 20.4L30.3 9.6Q32 8.2 33.7 9.8L34.3 20.5Z" fill="#0E4E2C"/><circle cx="32" cy="19.5" r="2.2" fill="#16241B"/></svg>'
        .     '<div class="brand-text">'
        .       '<div class="brand-name">SISTOMATE</div>'
        .       '<span class="brand-sub">tanaman tomat</span>'
        .     '</div>'
        .   '</a>'
        .   '<div class="inst-logos" aria-label="Afiliasi Akademik">'
        .     '<img src="assets/logo-jti.svg" alt="Jurusan Teknologi Informasi Politeknik Negeri Jember" class="inst-logo jti">'
        .     '<img src="assets/logo-trk.svg" alt="Teknologi Rekayasa Komputer" class="inst-logo trk">'
        .   '</div>'
        . '</div>'
        . '<nav class="sidebar-nav" aria-label="Navigasi Utama">'
        .   $nav
        . '</nav>'
        . '<div class="sidebar-footer">'
        .   $liveHtml
        .   '<div class="sidebar-user">'
        .     '<div class="user-avatar" aria-hidden="true">' . e($userInitial) . '</div>'
        .     '<div class="user-info"><span class="uname">' . e($userName) . '</span></div>'
        .     '<a class="logout-btn" href="logout.php" title="Logout" aria-label="Logout">'
        .       '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg>'
        .     '</a>'
        .   '</div>'
        . '</div>'
        . '</aside>';
}
