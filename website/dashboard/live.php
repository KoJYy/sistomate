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
        } else {
            $firstDev = $pdo->query('SELECT id_device, device_name, location FROM devices ORDER BY id_device ASC LIMIT 1')->fetch();
            if ($firstDev) {
                $did = (int) $firstDev['id_device'];
                $device = ['device_name' => $firstDev['device_name'], 'location' => $firstDev['location']];
            }
        }
    }

    $cfg = ['soil_on' => 40, 'soil_off' => 60, 'temp_on' => 27, 'temp_off' => 25, 'fert_duration_s' => 7];

    if ($did > 0) {
        // 60 bacaan terakhir perangkat yang tampil (interval 2 dtk = jendela 120 detik), urut lama -> baru
        $stHist = $pdo->prepare(
            'SELECT recorded_at, soil_moisture, air_temperature
             FROM sensor_logs WHERE id_device = ? ORDER BY recorded_at DESC, id_log DESC LIMIT 60'
        );
        $stHist->execute([$did]);
        $history = array_reverse($stHist->fetchAll());

        $stAct = $pdo->prepare(
            'SELECT actuator_name, status, executed_at
             FROM actuator_logs WHERE id_device = ? ORDER BY executed_at DESC, id_action DESC LIMIT 5'
        );
        $stAct->execute([$did]);
        $actuators = $stAct->fetchAll();

        // State TERKINI per aktuator (bukan 5 event terakhir): chatter satu aktuator
        // tidak boleh menyembunyikan aktuator lain dari kartu dashboard.
        $stState = $pdo->prepare(
            'SELECT a.actuator_name, a.status, a.executed_at
             FROM actuator_logs a
             JOIN (SELECT actuator_name, MAX(id_action) AS mx
                   FROM actuator_logs WHERE id_device = ? GROUP BY actuator_name) m
               ON m.mx = a.id_action'
        );
        $stState->execute([$did]);
        $actuatorStates = $stState->fetchAll();

        $stToday = $pdo->prepare(
            'SELECT
                (SELECT COUNT(*) FROM sensor_logs WHERE id_device = ? AND recorded_at >= CURDATE()) AS sensor_rows,
                (SELECT COUNT(*) FROM actuator_logs WHERE id_device = ? AND executed_at >= CURDATE()) AS actuator_events'
        );
        $stToday->execute([$did, $did]);
        $today = $stToday->fetch();

        try {
            $stCfg = $pdo->prepare(
                'SELECT soil_on, soil_off, temp_on, temp_off, fert_duration_s
                 FROM configs WHERE id_device = ? LIMIT 1'
            );
            $stCfg->execute([$did]);
            $cfgRow = $stCfg->fetch();
            if ($cfgRow) {
                $cfg = [
                    'soil_on' => (float) $cfgRow['soil_on'],
                    'soil_off' => (float) $cfgRow['soil_off'],
                    'temp_on' => (float) $cfgRow['temp_on'],
                    'temp_off' => (float) $cfgRow['temp_off'],
                    'fert_duration_s' => (int) $cfgRow['fert_duration_s'],
                ];
            }
        } catch (\Throwable $e) {
            // Tabel configs opsional; pakai nilai bawaan bila belum dibuat
        }
    } else {
        $history = [];
        $actuators = [];
        $actuatorStates = [];
        $today = ['sensor_rows' => 0, 'actuator_events' => 0];
    }

    echo json_encode([
        'ok' => true,
        'latest' => $latest ?: null,
        'history' => $history,
        'actuators' => $actuators,
        'actuator_states' => $actuatorStates,
        'today' => $today,
        'device' => $device,
        'config' => $cfg,
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    error_log('live: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false]);
}
