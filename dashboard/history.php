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

$statusColors = ['sehat' => 'success', 'kering' => 'warning', 'layu' => 'info', 'sakit' => 'danger'];
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Riwayat - SISTOMATE</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>body{background:#f4f7f4}.card-m{border:none;border-radius:1rem;box-shadow:0 6px 16px rgba(0,0,0,.06)}</style>
</head>
<body>
<?= navbar('history', $user['name']) ?>

<main class="container pb-5">
  <h1 class="h4 fw-bold mb-3">Riwayat Data</h1>

  <!-- Filter tanggal + export -->
  <div class="card card-m p-3 mb-4">
    <form method="get" action="history.php" class="row g-2 align-items-end">
      <div class="col-6 col-md-3">
        <label class="form-label small mb-1" for="date_from">Dari tanggal</label>
        <input type="date" class="form-control" id="date_from" name="date_from"
               value="<?= e($dateFrom) ?>">
      </div>
      <div class="col-6 col-md-3">
        <label class="form-label small mb-1" for="date_to">Sampai tanggal</label>
        <input type="date" class="form-control" id="date_to" name="date_to"
               value="<?= e($dateTo) ?>">
      </div>
      <div class="col-md-3"><button class="btn btn-success w-100">Terapkan Filter</button></div>
    </form>
    <div class="mt-2 small">
      <a class="text-decoration-none" href="export-csv.php?jenis=sensor&date_from=<?= e($dateFrom) ?>&date_to=<?= e($dateTo) ?>">⬇️ Export sensor (.csv)</a>
      &nbsp;·&nbsp;
      <a class="text-decoration-none" href="export-csv.php?jenis=actuator&date_from=<?= e($dateFrom) ?>&date_to=<?= e($dateTo) ?>">⬇️ Export aktuator (.csv)</a>
    </div>
  </div>

  <!-- Tabel sensor -->
  <div class="card card-m p-3 mb-4">
    <h2 class="h6 fw-bold">🌡️ Data Sensor (maks 200 baris terbaru)</h2>
    <div class="table-responsive">
      <table class="table table-sm table-striped align-middle mb-0">
        <thead class="table-light"><tr>
          <th>Waktu</th><th>Perangkat</th><th>Suhu (°C)</th><th>Kelemb. Udara (%)</th>
          <th>Kelemb. Tanah (%)</th><th>RGB</th><th>Status</th>
        </tr></thead>
        <tbody>
        <?php if (!$rowsSensor): ?>
          <tr><td colspan="7" class="text-center text-muted py-3">Tidak ada data pada rentang ini.</td></tr>
        <?php endif; ?>
        <?php foreach ($rowsSensor as $r): ?>
          <tr>
            <td class="text-nowrap"><?= e($r['recorded_at']) ?></td>
            <td><?= e($r['device_name']) ?></td>
            <td><?= e((string)$r['air_temperature']) ?></td>
            <td><?= e((string)$r['air_humidity']) ?></td>
            <td><?= e((string)$r['soil_moisture']) ?></td>
            <td>
              <span class="d-inline-block rounded" style="width:14px;height:14px;background:rgb(
                <?= (int)$r['rgb_red'] ?>,<?= (int)$r['rgb_green'] ?>,<?= (int)$r['rgb_blue'] ?>)"></span>
              <span class="small text-muted"><?= (int)$r['rgb_red'] ?>/<?= (int)$r['rgb_green'] ?>/<?= (int)$r['rgb_blue'] ?></span>
            </td>
            <td><span class="badge bg-<?= e($statusColors[$r['plant_status']] ?? 'secondary') ?>"><?= e($r['plant_status']) ?></span></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Tabel aktuator -->
  <div class="card card-m p-3">
    <h2 class="h6 fw-bold">⚡ Riwayat Aktuator (maks 200 baris terbaru)</h2>
    <div class="table-responsive">
      <table class="table table-sm table-striped align-middle mb-0">
        <thead class="table-light"><tr>
          <th>Waktu</th><th>Perangkat</th><th>Aktuator</th><th>Status</th>
        </tr></thead>
        <tbody>
        <?php if (!$rowsActuator): ?>
          <tr><td colspan="4" class="text-center text-muted py-3">Tidak ada data pada rentang ini.</td></tr>
        <?php endif; ?>
        <?php foreach ($rowsActuator as $r): ?>
          <tr>
            <td class="text-nowrap"><?= e($r['executed_at']) ?></td>
            <td><?= e($r['device_name']) ?></td>
            <td><?= e($r['actuator_name']) ?></td>
            <td><span class="badge bg-<?= $r['status'] === 'ON' ? 'success' : 'secondary' ?>"><?= e($r['status']) ?></span></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>
</body>
</html>
