<?php
// db.php - koneksi PDO MySQL + penjaga sesi bersama untuk SISTOMATE.
// Urutan konfigurasi env:
//   1) getenv('SISTOMATE_ENV') jika diset
//   2) dashboard/.env.local atau .env (untuk pengembangan lokal / Laragon)
//   3) /etc/sistomate-ingest.env (produksi Linux)
// Jika file env lokal tidak tersedia / DB offline saat pengembangan tanpa SISTOMATE_ENV,
// sistem otomatis memakai fallback DemoPDO (demo-data.php) agar demo web tetap berjalan.

function sistomate_env(): ?array
{
    static $loaded = false;
    static $env = null;
    if ($loaded) {
        return $env;
    }
    $loaded = true;

    $explicit = getenv('SISTOMATE_ENV');
    if ($explicit !== false && $explicit !== '') {
        $parsed = is_readable($explicit) ? @parse_ini_file($explicit) : false;
        if ($parsed === false) {
            http_response_code(500);
            exit('Konfigurasi database (SISTOMATE_ENV) tidak terbaca.');
        }
        $env = $parsed;
        return $env;
    }

    foreach ([__DIR__ . '/.env.local', dirname(__DIR__) . '/.env', '/etc/sistomate-ingest.env'] as $candidate) {
        if (@is_file($candidate) && @is_readable($candidate)) {
            $parsed = @parse_ini_file($candidate);
            if (is_array($parsed) && !empty($parsed['DB_NAME'])) {
                $env = $parsed;
                return $env;
            }
        }
    }
    return null;
}

$GLOBALS['_sistomate_is_demo'] = false;

function db_is_demo(): bool
{
    db();
    return (bool) ($GLOBALS['_sistomate_is_demo'] ?? false);
}

class DemoStmt
{
    private string $sql;
    private array $args = [];
    private ?array $cache = null;
    private int $pos = 0;

    public function __construct(string $sql)
    {
        $this->sql = strtolower(trim($sql));
    }

    public function execute($args = []): bool
    {
        $this->args = is_array($args) ? array_values($args) : [];
        $this->cache = null;
        $this->pos = 0;
        if (str_starts_with($this->sql, 'update configs') || str_starts_with($this->sql, 'insert into configs')) {
            $this->data();
        }
        return true;
    }

    public function fetch(int $mode = 0)
    {
        $rows = $this->data();
        return $rows[$this->pos++] ?? false;
    }

    public function fetchColumn(int $column = 0)
    {
        $row = $this->fetch();
        if (!is_array($row)) {
            return false;
        }
        $vals = array_values($row);
        return $vals[$column] ?? false;
    }

    public function fetchAll(): array
    {
        $rows = $this->data();
        $this->pos = count($rows);
        return $rows;
    }

    public function rowCount(): int
    {
        return count($this->data());
    }

    private function data(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }
        $this->cache = $this->build();
        return $this->cache;
    }

    /** @return array{0:int,1:int} */
    private function resolveRange(): array
    {
        $sql = $this->sql;
        $now = time();
        $fromStr = null;
        $toStr = null;

        if (str_contains($sql, 'between ? and ?')) {
            $fromStr = $this->args[0] ?? null;
            $toStr = $this->args[1] ?? null;
        } else {
            $idx = 0;
            if (str_contains($sql, '>= ?')) {
                $fromStr = $this->args[$idx++] ?? null;
            }
            if (str_contains($sql, '<= ?')) {
                $toStr = $this->args[$idx++] ?? null;
            }
        }

        $from = $fromStr !== null ? strtotime((string) $fromStr) : strtotime(date('Y-m-d 00:00:00', $now - 6 * 86400));
        $to = $toStr !== null ? strtotime((string) $toStr) : $now;
        if ($from === false) {
            $from = strtotime(date('Y-m-d 00:00:00', $now - 6 * 86400));
        }
        if ($to === false) {
            $to = $now;
        }
        if ($from > $now || $from > $to) {
            return [1, 0];
        }
        return [$from, min($to, $now)];
    }

    private function build(): array
    {
        $sql = $this->sql;
        $cfg = demo_cfg();

        if (str_contains($sql, 'select id_device from sensor_logs')) {
            return [['id_device' => 1]];
        }
        if (str_contains($sql, 'select min(id_device) from devices')) {
            return [['id_device' => 1]];
        }
        if (str_contains($sql, 'select * from configs')) {
            return [$cfg + ['id_device' => (int) ($this->args[0] ?? 1)]];
        }
        if (str_starts_with($sql, 'update configs')) {
            demo_cfg_save([
                'soil_on' => (float) ($this->args[0] ?? $cfg['soil_on']),
                'soil_off' => (float) ($this->args[1] ?? $cfg['soil_off']),
                'temp_on' => (float) ($this->args[2] ?? $cfg['temp_on']),
                'temp_off' => (float) ($this->args[3] ?? $cfg['temp_off']),
                'fert_duration_s' => (int) ($this->args[4] ?? $cfg['fert_duration_s']),
            ]);
            return [['ok' => 1]];
        }
        if (str_starts_with($sql, 'insert into configs')) {
            $offset = count($this->args) >= 6 ? 1 : 0;
            demo_cfg_save([
                'soil_on' => (float) ($this->args[$offset] ?? $cfg['soil_on']),
                'soil_off' => (float) ($this->args[$offset + 1] ?? $cfg['soil_off']),
                'temp_on' => (float) ($this->args[$offset + 2] ?? $cfg['temp_on']),
                'temp_off' => (float) ($this->args[$offset + 3] ?? $cfg['temp_off']),
                'fert_duration_s' => (int) ($this->args[$offset + 4] ?? $cfg['fert_duration_s']),
            ]);
            return [['ok' => 1]];
        }
        if (str_contains($sql, 'from users')) {
            $u = demo_user();
            if (str_contains($sql, 'where email = ?')) {
                $email = strtolower(trim((string) ($this->args[0] ?? '')));
                if ($email !== '' && $email !== strtolower($u['email'])) {
                    return [];
                }
            }
            return [$u];
        }
        if (str_contains($sql, 'from devices')) {
            return [['id_device' => 1, 'device_name' => 'SISTOMATE-01', 'location' => 'Greenhouse Polije']];
        }
        if (str_contains($sql, 'from sensor_logs')) {
            [$from, $to] = $this->resolveRange();
            $limit = str_contains($sql, 'limit 200') ? 200 : 1000;
            $step = $limit === 200 ? 300 : 600;
            return demo_sensor_rows($cfg, $from, $to, $step, $limit);
        }
        if (str_contains($sql, 'from actuator_logs')) {
            [$from, $to] = $this->resolveRange();
            $events = demo_actuator_events($cfg, $from, $to, 30);
            if (str_contains($sql, 'limit 200')) {
                return array_slice($events, 0, 200);
            }
            return $events;
        }
        return [];
    }
}

class DemoPDO
{
    public function prepare(string $sql): DemoStmt
    {
        return new DemoStmt($sql);
    }

    public function query(string $sql): DemoStmt
    {
        $stmt = new DemoStmt($sql);
        $stmt->execute([]);
        return $stmt;
    }
}

/** @return PDO|DemoPDO */
function db()
{
    static $conn = null;
    if ($conn !== null) {
        return $conn;
    }

    $env = sistomate_env();
    $hasExplicitEnv = getenv('SISTOMATE_ENV') !== false && getenv('SISTOMATE_ENV') !== '';

    if ($env !== null) {
        try {
            $dsn = "mysql:host={$env['DB_HOST']};dbname={$env['DB_NAME']};charset=utf8mb4";
            $conn = new PDO($dsn, $env['DB_USER'], $env['DB_PASS'] ?? '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            $GLOBALS['_sistomate_is_demo'] = false;
            return $conn;
        } catch (PDOException $e) {
            error_log('db: koneksi MySQL gagal - ' . $e->getMessage());
            if ($hasExplicitEnv) {
                http_response_code(500);
                exit('Koneksi database gagal.');
            }
        }
    }

    require_once __DIR__ . '/demo-data.php';
    $GLOBALS['_sistomate_is_demo'] = true;
    $conn = new DemoPDO();
    return $conn;
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
    return $row ?: [
        'id_user' => (int) $_SESSION['user_id'],
        'name' => $_SESSION['user_name'] ?? 'Pengguna',
        'email' => '',
    ];
}

// Ikon botani SISTOMATE bersama (sumber tunggal logo.svg agar identik dgn landing).
function brand_logo_svg(): string
{
    return '<img class="brand-logo" src="logo.svg" alt="" width="26" height="26" decoding="sync">';
}

// Pasangan logo resmi JTI Polije dan Prodi TRK.
function org_logos_html(string $cls = 'header-orgs'): string
{
    return '<div class="' . e($cls) . '" aria-label="Afiliasi jurusan dan program studi">'
        . '<a href="https://jti.polije.ac.id/" target="_blank" rel="noopener noreferrer" title="Jurusan Teknologi Informasi &mdash; Politeknik Negeri Jember">'
        . '<img class="logo-jti" src="logo-jti.svg" alt="Logo Politeknik Negeri Jember dan Jurusan Teknologi Informasi" width="1335" height="224" decoding="sync">'
        . '</a>'
        . '<span class="header-orgs-sep" aria-hidden="true"></span>'
        . '<a href="https://jti.polije.ac.id/prodi/TRK" target="_blank" rel="noopener noreferrer" title="Program Studi Teknologi Rekayasa Komputer &mdash; JTI Polije">'
        . '<img class="logo-trk" src="logo-trk.svg" alt="Logo Program Studi Teknologi Rekayasa Komputer Polije" width="1060" height="830" decoding="sync">'
        . '</a>'
        . '</div>';
}

// Bagian navbar bersama (aktif = halaman berjalan).
// Single source of truth untuk SEMUA halaman (app/history/settings) -> posisi nav terkunci sama.
function navbar(string $active, string $userName, bool $live = false): string
{
    $items = [
        'app'      => ['app.php',      'Dashboard'],
        'history'  => ['history.php',  'Riwayat'],
        'settings' => ['settings.php', 'Pengaturan'],
    ];
    $nav = '';
    foreach ($items as $key => [$href, $label]) {
        $cls = $key === $active ? ' class="on" aria-current="page"' : '';
        $nav .= '<a href="' . $href . '"' . $cls . '>' . e($label) . '</a>';
    }
    $right = '<span class="uname" title="' . e($userName) . '">' . e($userName) . '</span>'
        . '<span class="nav-sep" aria-hidden="true"></span>'
        . '<a class="logout" href="index.html">Beranda</a>'
        . '<a class="logout" href="logout.php">Keluar</a>';
    return '<a class="skip-link" href="#main">Lewati ke konten utama</a>'
        . '<header class="site-header" id="siteHeader">'
        . '<div class="header-inner">'
        .   '<a class="brand" href="app.php">' . brand_logo_svg() . '<span>SISTOMATE</span></a>'
        .   '<nav class="navbar" aria-label="Navigasi utama">' . $nav . '</nav>'
        .   '<div class="header-right">' . $right . '</div>'
        . '</div>'
        . '</header>';
}
