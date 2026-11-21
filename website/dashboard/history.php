<?php
// history.php - riwayat sensor_logs & actuator_logs dengan filter tanggal & batas baris (wajib login).
require_once __DIR__ . '/db.php';
require_login();

$user = current_user();

function valid_date(?string $s): ?string
{
    if (!$s) return null;
    $d = DateTime::createFromFormat('Y-m-d', $s);
    return ($d && $d->format('Y-m-d') === $s) ? $s : null;
}

$limitOptions = [
    '10'  => '10 baris',
    '30'  => '30 baris',
    '50'  => '50 baris',
    '100' => '100 baris',
    '200' => '200 baris',
    'all' => 'Semua data',
];

function valid_limit(?string $raw, array $options): string
{
    $val = strtolower(trim((string) $raw));
    return array_key_exists($val, $options) ? $val : '10';
}

function render_limit_form(
    string $anchor,
    string $selectId,
    string $selectName,
    string $current,
    array $options,
    string $dateFrom,
    string $dateTo,
    string $otherName,
    string $otherValue
): string {
    $html = '<form method="get" action="history.php#' . e($anchor) . '" class="limit-form">'
        . '<input type="hidden" name="date_from" value="' . e($dateFrom) . '">'
        . '<input type="hidden" name="date_to" value="' . e($dateTo) . '">'
        . '<input type="hidden" name="' . e($otherName) . '" value="' . e($otherValue) . '">'
        . '<label class="small muted" for="' . e($selectId) . '">Tampilkan</label>'
        . '<select id="' . e($selectId) . '" name="' . e($selectName) . '" onchange="this.form.submit()">';
    foreach ($options as $val => $label) {
        $sel = (string) $val === $current ? ' selected' : '';
        $html .= '<option value="' . e((string) $val) . '"' . $sel . '>' . e($label) . '</option>';
    }
    $html .= '</select>'
        . '<noscript><button type="submit" class="btn ghost">Terapkan</button></noscript>'
        . '</form>';
    return $html;
}

$dateFrom = valid_date($_GET['date_from'] ?? null) ?? date('Y-m-d', strtotime('-30 days'));
$dateTo   = valid_date($_GET['date_to'] ?? null)   ?? date('Y-m-d');
$fromTs = $dateFrom . ' 00:00:00';
$toTs   = $dateTo   . ' 23:59:59';

$limitSensor   = valid_limit($_GET['limit_sensor'] ?? null, $limitOptions);
$limitActuator = valid_limit($_GET['limit_actuator'] ?? null, $limitOptions);
$sensorLimitSql   = $limitSensor === 'all' ? '' : ' LIMIT ' . (int) $limitSensor;
$actuatorLimitSql = $limitActuator === 'all' ? '' : ' LIMIT ' . (int) $limitActuator;

$stmtTotalSensor = db()->prepare('SELECT COUNT(*) FROM sensor_logs WHERE recorded_at BETWEEN ? AND ?');
$stmtTotalSensor->execute([$fromTs, $toTs]);
$totalSensor = (int) $stmtTotalSensor->fetchColumn();

$sensor = db()->prepare(
    'SELECT s.recorded_at, s.air_temperature, s.air_humidity, s.soil_moisture,
            s.rgb_red, s.rgb_green, s.rgb_blue, s.plant_status, d.device_name
     FROM sensor_logs s
     JOIN devices d ON d.id_device = s.id_device
     WHERE s.recorded_at BETWEEN ? AND ?
     ORDER BY s.recorded_at DESC, s.id_log DESC' . $sensorLimitSql
);
$sensor->execute([$fromTs, $toTs]);
$rowsSensor = $sensor->fetchAll();

$stmtTotalActuator = db()->prepare('SELECT COUNT(*) FROM actuator_logs WHERE executed_at BETWEEN ? AND ?');
$stmtTotalActuator->execute([$fromTs, $toTs]);
$totalActuator = (int) $stmtTotalActuator->fetchColumn();

$actuator = db()->prepare(
    'SELECT a.executed_at, a.actuator_name, a.status, d.device_name
     FROM actuator_logs a
     JOIN devices d ON d.id_device = a.id_device
     WHERE a.executed_at BETWEEN ? AND ?
     ORDER BY a.executed_at DESC, a.id_action DESC' . $actuatorLimitSql
);
$actuator->execute([$fromTs, $toTs]);
$rowsActuator = $actuator->fetchAll();

$statusPill = ['sehat' => 'sehat', 'layu' => 'layu', 'sakit' => 'sakit'];
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Riwayat - SISTOMATE</title>
<link rel="icon" type="image/svg+xml" href="../assets/favicon.svg">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Archivo:wght@400;500;600;700&family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/theme.css">
</head>
<body class="has-sidebar">
<?= navbar('history', $user['name']) ?>

<main class="main-content">
  <h1 class="page-title">Riwayat Data</h1>

  <!-- Filter tanggal + export -->
  <div class="panel">
    <form method="get" action="history.php" class="row-inline">
      <input type="hidden" name="limit_sensor" value="<?= e($limitSensor) ?>">
      <input type="hidden" name="limit_actuator" value="<?= e($limitActuator) ?>">
      <div>
        <label class="f" for="date_from">Dari tanggal</label>
        <input type="date" id="date_from" name="date_from"
               value="<?= e($dateFrom) ?>">
      </div>
      <div>
        <label class="f" for="date_to">Sampai tanggal</label>
        <input type="date" id="date_to" name="date_to"
               value="<?= e($dateTo) ?>">
      </div>
      <div><button class="btn">Terapkan Filter</button></div>
    </form>
    <div class="mt-block small">
      <a class="link" href="export-csv.php?jenis=sensor&date_from=<?= e($dateFrom) ?>&date_to=<?= e($dateTo) ?>">Export sensor (.csv)</a>
      &nbsp;&middot;&nbsp;
      <a class="link" href="export-csv.php?jenis=actuator&date_from=<?= e($dateFrom) ?>&date_to=<?= e($dateTo) ?>">Export aktuator (.csv)</a>
    </div>
  </div>

  <!-- Tabel sensor -->
  <div class="panel" id="tabel-sensor">
    <div class="table-head">
      <div class="table-title-group">
        <h2 id="sensor-title">Data Sensor</h2>
        <span class="small muted">Menampilkan <?= count($rowsSensor) ?> dari <?= $totalSensor ?> baris</span>
      </div>
      <?= render_limit_form(
          'tabel-sensor',
          'limit_sensor',
          'limit_sensor',
          $limitSensor,
          $limitOptions,
          $dateFrom,
          $dateTo,
          'limit_actuator',
          $limitActuator
      ) ?>
    </div>
    <div class="table-wrapper" tabindex="0" role="region" aria-labelledby="sensor-title">
      <table class="tbl">
        <thead><tr>
          <th scope="col">Waktu</th><th scope="col">Perangkat</th><th scope="col">Suhu (&deg;C)</th><th scope="col">Kelemb. Udara (%)</th>
          <th scope="col">Kelemb. Tanah (%)</th><th scope="col">RGB</th><th scope="col">Kondisi Tanaman</th>
        </tr></thead>
        <tbody>
        <?php if (!$rowsSensor): ?>
          <tr><td colspan="7" class="center muted" style="padding:20px 10px">Tidak ada data pada rentang ini.</td></tr>
        <?php endif; ?>
        <?php foreach ($rowsSensor as $r): ?>
          <tr>
            <td><?= e($r['recorded_at']) ?></td>
            <td><?= e($r['device_name']) ?></td>
            <td><?= e((string)$r['air_temperature']) ?></td>
            <td><?= e((string)$r['air_humidity']) ?></td>
            <td><?= e((string)$r['soil_moisture']) ?></td>
            <td>
              <span class="rgb-chip <?= e($statusPill[$r['plant_status']] ?? 'off') ?>"
                    title="Warna status: <?= e($r['plant_status']) ?>"></span>
              <span class="rgb-val"><?= (int)$r['rgb_red'] ?>/<?= (int)$r['rgb_green'] ?>/<?= (int)$r['rgb_blue'] ?></span>
            </td>
            <td><span class="pill <?= e($statusPill[$r['plant_status']] ?? 'off') ?>"><?= e($r['plant_status']) ?></span></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Tabel aktuator -->
  <div class="panel" id="tabel-aktuator">
    <div class="table-head">
      <div class="table-title-group">
        <h2 id="actuator-title">Riwayat Aktuator</h2>
        <span class="small muted">Menampilkan <?= count($rowsActuator) ?> dari <?= $totalActuator ?> baris</span>
      </div>
      <?= render_limit_form(
          'tabel-aktuator',
          'limit_actuator',
          'limit_actuator',
          $limitActuator,
          $limitOptions,
          $dateFrom,
          $dateTo,
          'limit_sensor',
          $limitSensor
      ) ?>
    </div>
    <div class="table-wrapper" tabindex="0" role="region" aria-labelledby="actuator-title">
      <table class="tbl">
        <thead><tr>
          <th scope="col">Waktu</th><th scope="col">Perangkat</th><th scope="col">Aktuator</th><th scope="col">Status</th>
        </tr></thead>
        <tbody>
        <?php if (!$rowsActuator): ?>
          <tr><td colspan="4" class="center muted" style="padding:20px 10px">Tidak ada data pada rentang ini.</td></tr>
        <?php endif; ?>
        <?php foreach ($rowsActuator as $r): ?>
          <tr>
            <td><?= e($r['executed_at']) ?></td>
            <td><?= e($r['device_name']) ?></td>
            <td><?= e($r['actuator_name']) ?></td>
            <td><span class="pill <?= $r['status'] === 'ON' ? 'on' : 'off' ?>"><?= e($r['status']) ?></span></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>
</body>
</html>
