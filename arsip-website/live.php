<?php
// live.php - JSON endpoint telemetri terbaru SISTOMATE untuk polling dashboard (GET, read-only).
// Memakai koneksi MySQL PDO dari db.php dan otomatis memakai simulasi demo-data bila mode demo aktif.
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');
header('Cache-Control: no-store');

try {
    $pdo = db();

    if (db_is_demo()) {
        $cfg = demo_cfg();
        $now = time();
        $historyRows = demo_sensor_rows($cfg, $now - 600, $now, 10, 60);
        $history = array_reverse(array_map(static fn(array $r): array => [
            'recorded_at' => $r['recorded_at'],
            'soil_moisture' => $r['soil_moisture'],
            'air_temperature' => $r['air_temperature'],
        ], $historyRows));
        $latest = demo_reading($now, $cfg);
        $actuators = demo_actuator_latest($cfg, $now);
        $todayStart = strtotime(date('Y-m-d 00:00:00', $now));
        $today = [
            'sensor_rows' => max(1, (int) floor(($now - $todayStart) / 5)),
            'actuator_events' => count(demo_actuator_events($cfg, $todayStart, $now, 30)),
        ];

        echo json_encode([
            'ok' => true,
            'latest' => $latest,
            'history' => $history,
            'actuators' => $actuators,
            'today' => $today,
            'device' => ['device_name' => 'SISTOMATE-01', 'location' => 'Greenhouse Polije'],
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $deviceId = (int) ($_GET['id_device'] ?? 0);
    if ($deviceId <= 0) {
        $deviceId = (int) ($pdo->query('SELECT id_device FROM sensor_logs ORDER BY recorded_at DESC, id_log DESC LIMIT 1')->fetchColumn() ?: 0);
    }
    if ($deviceId <= 0) {
        $deviceId = (int) ($pdo->query('SELECT MIN(id_device) FROM devices')->fetchColumn() ?: 0);
    }

    $latest = null;
    $history = [];
    $actuators = [];
    $today = ['sensor_rows' => 0, 'actuator_events' => 0];
    $device = null;

    if ($deviceId > 0) {
        $stmtLatest = $pdo->prepare(
            'SELECT recorded_at, air_temperature, air_humidity, soil_moisture,
                    rgb_red, rgb_green, rgb_blue, plant_status, id_device
             FROM sensor_logs
             WHERE id_device = ?
             ORDER BY recorded_at DESC, id_log DESC LIMIT 1'
        );
        $stmtLatest->execute([$deviceId]);
        $latest = $stmtLatest->fetch() ?: null;

        // 60 bacaan terakhir untuk grafik tren, diurutkan lama -> baru
        $stmtHist = $pdo->prepare(
            'SELECT recorded_at, soil_moisture, air_temperature
             FROM sensor_logs
             WHERE id_device = ?
             ORDER BY recorded_at DESC, id_log DESC LIMIT 60'
        );
        $stmtHist->execute([$deviceId]);
        $history = array_reverse($stmtHist->fetchAll());

        // Ambil 10 event terbaru + pastikan status terakhir setiap kanal aktuator tetap muncul
        $stmtAct = $pdo->prepare(
            'SELECT actuator_name, status, executed_at
             FROM actuator_logs
             WHERE id_device = ?
             ORDER BY executed_at DESC, id_action DESC LIMIT 10'
        );
        $stmtAct->execute([$deviceId]);
        $actuators = $stmtAct->fetchAll();

        $seen = [];
        foreach ($actuators as $a) {
            $seen[strtolower($a['actuator_name'])] = true;
        }
        $stmtPerAct = $pdo->prepare(
            'SELECT a.actuator_name, a.status, a.executed_at
             FROM actuator_logs a
             WHERE a.id_action IN (
               SELECT MAX(id_action) FROM actuator_logs WHERE id_device = ? GROUP BY actuator_name
             )'
        );
        $stmtPerAct->execute([$deviceId]);
        foreach ($stmtPerAct->fetchAll() as $row) {
            if (empty($seen[strtolower($row['actuator_name'])])) {
                $actuators[] = $row;
            }
        }

        $stmtToday = $pdo->prepare(
            'SELECT
                (SELECT COUNT(*) FROM sensor_logs WHERE id_device = ? AND recorded_at >= CURDATE()) AS sensor_rows,
                (SELECT COUNT(*) FROM actuator_logs WHERE id_device = ? AND executed_at >= CURDATE()) AS actuator_events'
        );
        $stmtToday->execute([$deviceId, $deviceId]);
        $today = $stmtToday->fetch() ?: $today;

        $stmtDev = $pdo->prepare('SELECT device_name, location FROM devices WHERE id_device = ?');
        $stmtDev->execute([$deviceId]);
        $device = $stmtDev->fetch() ?: null;
    }

    echo json_encode([
        'ok' => true,
        'latest' => $latest,
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
