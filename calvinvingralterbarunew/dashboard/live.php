<?php
// live.php - data terbaru SISTOMATE buat dashboard realtime (read-only, GET; opsional ?id_device=)
// Env: /etc/sistomate-ingest.env (sama dengan ingest.php)
header('Content-Type: application/json');
header('Cache-Control: no-store');

$path = getenv('SISTOMATE_ENV')
    ?: (file_exists(__DIR__ . '/../.env') ? __DIR__ . '/../.env'
    : (file_exists(__DIR__ . '/.env') ? __DIR__ . '/.env'
    : (file_exists(__DIR__ . '/../../.env') ? __DIR__ . '/../../.env'
    : '/etc/sistomate-ingest.env')));
$env = parse_ini_file($path);
if ($env === false) {
    error_log('live: env file tidak terbaca');
    http_response_code(500);
    echo json_encode(['ok' => false]);
    exit;
}

try {
    $pdo = new PDO(
        "mysql:host={$env['DB_HOST']};dbname={$env['DB_NAME']};charset=utf8mb4",
        $env['DB_USER'],
        $env['DB_PASS'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    $reqDevice = (int) ($_GET['id_device'] ?? 0);
    $devRow = null;
    if ($reqDevice > 0) {
        $stmtDev = $pdo->prepare('SELECT id_device, device_name, location FROM devices WHERE id_device = ?');
        $stmtDev->execute([$reqDevice]);
        $devRow = $stmtDev->fetch() ?: null;
    }

    // "Terbaru" = waktu bacaan, bukan urutan insert, supaya backfill tidak menimpa live.
    if ($devRow) {
        $did = (int) $devRow['id_device'];
        $device = ['device_name' => $devRow['device_name'], 'location' => $devRow['location']];

        $stLatest = $pdo->prepare(
            'SELECT recorded_at, air_temperature, air_humidity, soil_moisture,
                    rgb_red, rgb_green, rgb_blue, plant_status, id_device
             FROM sensor_logs WHERE id_device = ? ORDER BY recorded_at DESC, id_log DESC LIMIT 1'
        );
        $stLatest->execute([$did]);
        $latest = $stLatest->fetch();
    } else {
        $latest = $pdo->query(
            'SELECT recorded_at, air_temperature, air_humidity, soil_moisture,
                    rgb_red, rgb_green, rgb_blue, plant_status, id_device
             FROM sensor_logs ORDER BY recorded_at DESC, id_log DESC LIMIT 1'
        )->fetch();
        $did = $latest ? (int) $latest['id_device'] : 0;

        $device = null;
        if ($latest) {
            $stmt = $pdo->prepare('SELECT device_name, location FROM devices WHERE id_device = ?');
            $stmt->execute([$did]);
            $device = $stmt->fetch() ?: null;
        }
    }

    // 60 bacaan terakhir perangkat yang tampil (interval 2 dtk = jendela 120 detik), urut lama -> baru
    $stHist = $pdo->prepare(
        'SELECT recorded_at, soil_moisture, air_temperature
         FROM sensor_logs WHERE id_device = ? ORDER BY recorded_at DESC, id_log DESC LIMIT 60'
    );
    $stHist->execute([$did]);
    $history = array_reverse($stHist->fetchAll());

    $stAct = $pdo->prepare(
        'SELECT actuator_name, status, executed_at
         FROM actuator_logs WHERE id_device = ? ORDER BY id_action DESC LIMIT 5'
    );
    $stAct->execute([$did]);
    $actuators = $stAct->fetchAll();

    $stToday = $pdo->prepare(
        'SELECT
            (SELECT COUNT(*) FROM sensor_logs WHERE id_device = ? AND recorded_at >= CURDATE()) AS sensor_rows,
            (SELECT COUNT(*) FROM actuator_logs WHERE id_device = ? AND executed_at >= CURDATE()) AS actuator_events'
    );
    $stToday->execute([$did, $did]);
    $today = $stToday->fetch();

    echo json_encode([
        'ok' => true,
        'latest' => $latest ?: null,
        'history' => $history,
        'actuators' => $actuators,
        'today' => $today,
        'device' => $device,
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    error_log('live: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false]);
}
