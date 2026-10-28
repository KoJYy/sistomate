<?php
// ingest.php — terima data SISTOMATE dari ESP32 (HTTP POST JSON)
// Env: /etc/sistomate-ingest.env (API_KEY, DB_*)
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'POST only']);
    exit;
}

$env = parse_ini_file('/etc/sistomate-ingest.env');
if ($env === false) {
    error_log('ingest: env file tidak terbaca');
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
    exit;
}

$key = $_SERVER['HTTP_X_API_KEY'] ?? '';
if (!hash_equals($env['API_KEY'], $key)) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$in = json_decode(file_get_contents('php://input'), true);
$type = $in['type'] ?? '';

$VALID_STATUS = ['sehat', 'kering', 'layu', 'sakit'];
$VALID_ONOFF  = ['ON', 'OFF'];

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

    if ($type === 'sensor') {
        $status = $in['plant_status'] ?? '';
        if (!in_array($status, $VALID_STATUS, true)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid plant_status']);
            exit;
        }
        $stmt = $pdo->prepare(
            'INSERT INTO sensor_logs
             (recorded_at, air_temperature, air_humidity, soil_moisture,
              rgb_red, rgb_green, rgb_blue, plant_status, id_device)
             VALUES (NOW(), ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            (float)($in['air_temperature'] ?? 0),
            (float)($in['air_humidity'] ?? 0),
            (float)($in['soil_moisture'] ?? 0),
            (int)($in['rgb_red'] ?? 0),
            (int)($in['rgb_green'] ?? 0),
            (int)($in['rgb_blue'] ?? 0),
            $status,
            (int)($in['device_id'] ?? 1),
        ]);
    } elseif ($type === 'actuator') {
        $st = $in['status'] ?? '';
        if (!in_array($st, $VALID_ONOFF, true)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid status']);
            exit;
        }
        $stmt = $pdo->prepare(
            'INSERT INTO actuator_logs
             (actuator_name, status, executed_at, id_device)
             VALUES (?, ?, NOW(), ?)'
        );
        $stmt->execute([
            substr((string)($in['actuator_name'] ?? ''), 0, 100),
            $st,
            (int)($in['device_id'] ?? 1),
        ]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid type']);
        exit;
    }

    echo json_encode(['status' => 'ok']);
} catch (Exception $e) {
    error_log('ingest: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
