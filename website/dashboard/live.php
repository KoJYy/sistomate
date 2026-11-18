<?php
// live.php - data terbaru SISTOMATE buat dashboard realtime (read-only, GET)
// Env: /etc/sistomate-ingest.env (sama dengan ingest.php)
header('Content-Type: application/json');
header('Cache-Control: no-store');

$path = getenv('SISTOMATE_ENV') ?: '/etc/sistomate-ingest.env';
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

    // "Terbaru" = waktu bacaan, bukan urutan insert. Backfill data lama (id baru)
    // tidak boleh menimpa tampilan live.
    $latest = $pdo->query(
        'SELECT recorded_at, air_temperature, air_humidity, soil_moisture,
                rgb_red, rgb_green, rgb_blue, plant_status, id_device
         FROM sensor_logs ORDER BY recorded_at DESC, id_log DESC LIMIT 1'
    )->fetch();
    $deviceId = $latest ? (int) $latest['id_device'] : 0;

    // 60 bacaan terakhir perangkat yang tampil (interval 2 dtk = jendela 120 detik), urut lama -> baru
    $stmt = $pdo->prepare(
        'SELECT recorded_at, soil_moisture, air_temperature
         FROM sensor_logs WHERE id_device = ?
         ORDER BY recorded_at DESC, id_log DESC LIMIT 60'
    );
    $stmt->execute([$deviceId]);
    $history = array_reverse($stmt->fetchAll());

    $stmt = $pdo->prepare(
        'SELECT actuator_name, status, executed_at
         FROM actuator_logs WHERE id_device = ?
         ORDER BY id_action DESC LIMIT 5'
    );
    $stmt->execute([$deviceId]);
    $actuators = $stmt->fetchAll();

    $stmt = $pdo->prepare(
        'SELECT
            (SELECT COUNT(*) FROM sensor_logs WHERE id_device = ? AND recorded_at >= CURDATE())  AS sensor_rows,
            (SELECT COUNT(*) FROM actuator_logs WHERE id_device = ? AND executed_at >= CURDATE()) AS actuator_events'
    );
    $stmt->execute([$deviceId, $deviceId]);
    $today = $stmt->fetch();

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
