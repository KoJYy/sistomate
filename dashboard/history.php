<?php
// history.php - riwayat sensor_logs & actuator_logs dengan filter tanggal (wajib login).
require_once __DIR__ . '/db.php';
require_login();

$user = current_user();

function valid_date(?string $s): ?string
{
    if (!$s) return null;
    $d = DateTime::createFromFormat('Y-m-d', $s);
    return ($d && $d->format('Y-m-d') === $s) ? $s : null;
}
$dateFrom = valid_date($_GET['date_from'] ?? null) ?? date('Y-m-d', strtotime('-7 days'));
$dateTo   = valid_date($_GET['date_to'] ?? null)   ?? date('Y-m-d');
$fromTs = $dateFrom . ' 00:00:00';
$toTs   = $dateTo   . ' 23:59:59';

$sensor = db()->prepare(
    'SELECT s.recorded_at, s.air_temperature, s.air_humidity, s.soil_moisture,
            s.rgb_red, s.rgb_green, s.rgb_blue, s.plant_status, d.device_name
     FROM sensor_logs s
     JOIN devices d ON d.id_device = s.id_device
     WHERE s.recorded_at BETWEEN ? AND ?
     ORDER BY s.recorded_at DESC LIMIT 200'
);
$sensor->execute([$fromTs, $toTs]);
$rowsSensor = $sensor->fetchAll();

$actuator = db()->prepare(
    'SELECT a.executed_at, a.actuator_name, a.status, d.device_name
     FROM actuator_logs a
     JOIN devices d ON d.id_device = a.id_device
     WHERE a.executed_at BETWEEN ? AND ?
     ORDER BY a.executed_at DESC LIMIT 200'
);
$actuator->execute([$fromTs, $toTs]);
$rowsActuator = $actuator->fetchAll();

$statusPill = ['sehat' => 'sehat', 'kering' => 'kering', 'layu' => 'layu', 'sakit' => 'sakit'];
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Riwayat - SISTOMATE</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Archivo:wght@400;500;600;700&family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="theme.css">
</head>
<body>
<?= navbar('history', $user['name']) ?>

<main>
  <h1 class="page-title">Riwayat Data</h1>

  <!-- Filter tanggal + export -->
  <div class="panel">
    <form method="get" action="history.php" class="row-inline">
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
      <a class="link" href="export-csv.php?jenis=sensor&date_from=<?= e($dateFrom) ?>&date_to=<?= e($dateTo) ?>">&#11015;&#65039; Export sensor (.csv)</a>
      &nbsp;&middot;&nbsp;
      <a class="link" href="export-csv.php?jenis=actuator&date_from=<?= e($dateFrom) ?>&date_to=<?= e($dateTo) ?>">&#11015;&#65039; Export aktuator (.csv)</a>
    </div>
  </div>

  <!-- Tabel sensor -->
  <div class="panel">
    <h2>&#127777;&#65039; Data Sensor (maks 200 baris terbaru)</h2>
    <table class="tbl">
      <thead><tr>
        <th>Waktu</th><th>Perangkat</th><th>Suhu (&deg;C)</th><th>Kelemb. Udara (%)</th>
        <th>Kelemb. Tanah (%)</th><th>RGB</th><th>Status</th>
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
            <span class="rgb-chip" style="background:rgb(
              <?= (int)$r['rgb_red'] ?>,<?= (int)$r['rgb_green'] ?>,<?= (int)$r['rgb_blue'] ?>)"></span>
            <span class="rgb-val"><?= (int)$r['rgb_red'] ?>/<?= (int)$r['rgb_green'] ?>/<?= (int)$r['rgb_blue'] ?></span>
          </td>
          <td><span class="pill <?= e($statusPill[$r['plant_status']] ?? 'off') ?>"><?= e($r['plant_status']) ?></span></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Tabel aktuator -->
  <div class="panel">
    <h2>&#9889; Riwayat Aktuator (maks 200 baris terbaru)</h2>
    <table class="tbl">
      <thead><tr>
        <th>Waktu</th><th>Perangkat</th><th>Aktuator</th><th>Status</th>
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
</main>
</body>
</html>
