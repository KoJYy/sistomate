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
            : (file_exists(__DIR__ . '/../../.env') ? __DIR__ . '/../../.env'
            : '/etc/sistomate-ingest.env')));
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
// Single source of truth utk SEMUA halaman (app/history/gallery/settings).
// Sidebar vertikal di sebelah kiri desktop, responsif di mobile.
// $live=true secara default agar badge status jam LIVE tampil konsisten
// di seluruh halaman dashboard (Dashboard, Riwayat, Galeri, Pengaturan).
function navbar(string $active, string $userName, bool $live = true): string
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
        'gallery' => [
            'href' => 'gallery.php',
            'label' => 'Galeri',
            'icon' => '<svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>'
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
            . '</div>'
            . '<script>(function(){function u(){var c=document.getElementById("clock");if(c)c.textContent=new Date().toLocaleTimeString("id-ID",{hour12:false});}u();setInterval(u,1000);})();</script>';
    }

    $userInitial = mb_strtoupper(mb_substr($userName !== '' ? $userName : 'U', 0, 1));

    return '<aside class="sidebar">'
        . '<div class="sidebar-brand">'
        .   '<a href="app.php" class="brand">'
        .     '<img src="../assets/figma/logo-navbar.png" alt="SISTOMATE" class="brand-logo-img" width="200" height="50">'
        .     '<span class="brand-sub">tanaman tomat</span>'
        .   '</a>'
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

/**
 * Memisahkan awalan [Sehat|Layu|Sakit] dari kolom gallery.caption (VARCHAR 255)
 * agar tetap kompatibel penuh dengan skema tabel inti tanpa migrasi DDL.
 */
function parse_gallery_caption(?string $raw): array
{
    $text = trim((string) $raw);
    if (preg_match('/^\[(sehat|layu|sakit)\]\s*(.*)$/iu', $text, $m)) {
        return ['status' => strtolower($m[1]), 'text' => trim($m[2])];
    }
    return ['status' => '', 'text' => $text];
}

/**
 * Memastikan image_url menunjuk ke berkas lokal valid di dalam direktori dashboard
 * sehingga path traversal atau seed path yang belum ada berkasnya tampil aman.
 */
function resolve_gallery_image(?string $imageUrl, string $baseDir = __DIR__): ?string
{
    $url = trim((string) $imageUrl);
    if ($url === '' || str_contains($url, "\0") || str_contains($url, '..')) {
        return null;
    }
    if (preg_match('/^[a-z][a-z0-9+.-]*:/i', $url) || str_starts_with($url, '//')) {
        return null;
    }
    $rel = ltrim(str_replace('\\', '/', $url), '/');
    $baseReal = realpath($baseDir);
    $fullReal = realpath($baseDir . '/' . $rel);
    if ($baseReal === false || $fullReal === false || !is_file($fullReal)) {
        return null;
    }
    $prefix = rtrim($baseReal, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    if (!str_starts_with($fullReal, $prefix)) {
        return null;
    }
    return $rel;
}
