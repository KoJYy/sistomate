<?php
// export-csv.php - stream data riwayat sebagai berkas .csv (wajib login).
// Parameter: jenis=sensor|actuator (default sensor), date_from & date_to (opsional, Y-m-d).
require_once __DIR__ . '/db.php';
require_login();

function valid_date(?string $s): ?string
{
    if (!$s) return null;
    $d = DateTime::createFromFormat('Y-m-d', $s);
    return ($d && $d->format('Y-m-d') === $s) ? $s : null;
}
$jenis     = ($_GET['jenis'] ?? 'sensor') === 'actuator' ? 'actuator' : 'sensor';
$dateFrom  = valid_date($_GET['date_from'] ?? null);
$dateTo    = valid_date($_GET['date_to'] ?? null);
$conds = [];
$args  = [];
if ($dateFrom) { $conds[] = ($jenis === 'sensor' ? 'recorded_at' : 'executed_at') . ' >= ?'; $args[] = $dateFrom . ' 00:00:00'; }
if ($dateTo)   { $conds[] = ($jenis === 'sensor' ? 'recorded_at' : 'executed_at') . ' <= ?'; $args[] = $dateTo . ' 23:59:59'; }
$where = $conds ? ' WHERE ' . implode(' AND ', $conds) : '';

if ($jenis === 'sensor') {
    $stmt = db()->prepare(
        'SELECT recorded_at, air_temperature, air_humidity, soil_moisture,
                rgb_red, rgb_green, rgb_blue, plant_status, id_device
         FROM sensor_logs' . $where . ' ORDER BY recorded_at DESC'
    );
    $filename = 'sistomate-sensor-' . date('Ymd-His') . '.csv';
} else {
    $stmt = db()->prepare(
        'SELECT executed_at, actuator_name, status, id_device
         FROM actuator_logs' . $where . ' ORDER BY executed_at DESC'
    );
    $filename = 'sistomate-aktuator-' . date('Ymd-His') . '.csv';
}
$stmt->execute($args);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$out = fopen('php://output', 'w');
$first = true;
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if ($first) { fputcsv($out, array_keys($row), ';'); $first = false; }
    fputcsv($out, array_values($row), ';');
}
fclose($out);
exit;
