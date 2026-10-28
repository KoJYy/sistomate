<?php
// live.php — data terbaru SISTOMATE buat dashboard realtime (read-only, GET)
// Env: /etc/sistomate-ingest.env (sama dengan ingest.php)
header('Content-Type: application/json');
header('Cache-Control: no-store');

$env = parse_ini_file('/etc/sistomate-ingest.env');
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

    $latest = $pdo->query(
        'SELECT recorded_at, air_temperature, air_humidity, soil_moisture,
                rgb_red, rgb_green, rgb_blue, plant_status, id_device
         FROM sensor_logs ORDER BY id_log DESC LIMIT 1'
    )->fetch();

    // 60 bacaan terakhir (interval 2 dtk = jendela 120 detik), urut lama -> baru
    $history = $pdo->query(
        'SELECT recorded_at, soil_moisture, air_temperature
         FROM sensor_logs ORDER BY id_log DESC LIMIT 60'
    )->fetchAll();
    $history = array_reverse($history);

    $actuators = $pdo->query(
        'SELECT actuator_name, status, executed_at
         FROM actuator_logs ORDER BY id_action DESC LIMIT 5'
    )->fetchAll();

    $today = $pdo->query(
        'SELECT
            (SELECT COUNT(*) FROM sensor_logs WHERE recorded_at >= CURDATE())  AS sensor_rows,
            (SELECT COUNT(*) FROM actuator_logs WHERE executed_at >= CURDATE()) AS actuator_events'
    )->fetch();

    $device = null;
    if ($latest) {
        $stmt = $pdo->prepare('SELECT device_name, location FROM devices WHERE id_device = ?');
        $stmt->execute([(int)$latest['id_device']]);
        $device = $stmt->fetch() ?: null;
    }

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
