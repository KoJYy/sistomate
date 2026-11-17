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
$today    = date('Y-m-d');
$dateFrom = valid_date($_GET['date_from'] ?? null) ?? date('Y-m-d', strtotime('-6 days'));
$dateTo   = valid_date($_GET['date_to'] ?? null)   ?? $today;
$rangeSwapped = false;
if ($dateFrom > $dateTo) {
    [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
    $rangeSwapped = true;
}
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

// "2026-11-12 12:44:02" -> "12 Nov 2026 12:44:02" (bulan bahasa Indonesia)
function fmt_waktu(?string $s): string
{
    if (!$s) return '-';
    $bulan = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $d = DateTime::createFromFormat('Y-m-d H:i:s', $s);
    if (!$d) return (string) $s;
    return $d->format('j') . ' ' . $bulan[(int) $d->format('n')] . ' ' . $d->format('Y H:i:s');
}

// Pintasan rentang: [label, date_from, date_to]
$presets = [
    ['Hari ini', $today, $today],
    ['7 hari', date('Y-m-d', strtotime('-6 days')), $today],
    ['30 hari', date('Y-m-d', strtotime('-29 days')), $today],
];
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Riwayat - SISTOMATE</title>
<link rel="icon" type="image/svg+xml" href="favicon.svg">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Instrument+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="theme.css?v=35">
</head>
<body>
<?= navbar('history', $user['name']) ?>

<main class="wrap page" id="main" tabindex="-1">
  <div class="page-head">
    <div>
      <p class="cap">Riwayat</p>
      <h1 class="page-title">Riwayat Data</h1>
      <p class="page-lead">Arsip pembacaan sensor dan kejadian aktuator pada rentang tanggal terpilih.</p>
    </div>
    <div class="page-actions">
      <a class="btn ghost sm" href="export-csv.php?jenis=sensor&date_from=<?= e($dateFrom) ?>&date_to=<?= e($dateTo) ?>">Ekspor sensor</a>
      <a class="btn ghost sm" href="export-csv.php?jenis=actuator&date_from=<?= e($dateFrom) ?>&date_to=<?= e($dateTo) ?>">Ekspor aktuator</a>
    </div>
  </div>

  <div class="stack">
  <?php if ($rangeSwapped): ?>
    <div class="alert ok" role="status">Rentang tanggal dibalik otomatis agar tanggal mulai tidak melebihi tanggal akhir.</div>
  <?php endif; ?>
  <!-- Filter tanggal + pintasan rentang -->
  <search aria-label="Filter rentang tanggal">
    <form method="get" action="history.php" class="panel filter">
      <div class="field">
        <label for="date_from">Dari tanggal</label>
        <input type="date" id="date_from" name="date_from" max="<?= e($today) ?>" value="<?= e($dateFrom) ?>">
      </div>
      <div class="field">
        <label for="date_to">Sampai tanggal</label>
        <input type="date" id="date_to" name="date_to" max="<?= e($today) ?>" value="<?= e($dateTo) ?>">
      </div>
      <div class="field" style="align-self:end">
        <button class="btn" type="submit">Terapkan</button>
      </div>
      <div class="presets">
        <?php foreach ($presets as [$label, $pf, $pt]): ?>
          <a href="history.php?date_from=<?= e($pf) ?>&date_to=<?= e($pt) ?>"
             class="<?= ($pf === $dateFrom && $pt === $dateTo) ? 'active' : '' ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
      </div>
    </form>
  </search>

  <!-- Tabel sensor -->
  <section class="panel table-panel" aria-labelledby="tbl-sensor-title">
    <div class="panel-head">
      <h2 class="panel-title" id="tbl-sensor-title">Data sensor</h2>
      <span class="badge-soft"><?= count($rowsSensor) ?> baris</span>
    </div>
    <div class="tbl-wrap" tabindex="0" role="region" aria-labelledby="tbl-sensor-title">
      <table class="tbl">
        <caption class="visually-hidden">Data sensor pada rentang tanggal <?= e($dateFrom) ?> sampai <?= e($dateTo) ?></caption>
        <thead><tr>
          <th scope="col">Waktu</th><th scope="col">Perangkat</th><th scope="col" class="num">Suhu (&deg;C)</th>
          <th scope="col" class="num">RH (%)</th><th scope="col" class="num">Tanah (%)</th><th scope="col">RGB</th><th scope="col" class="right">Status</th>
        </tr></thead>
        <tbody>
        <?php if (!$rowsSensor): ?>
          <tr class="empty-row"><td colspan="7">Tidak ada data pada rentang ini.</td></tr>
        <?php endif; ?>
        <?php foreach ($rowsSensor as $r): ?>
          <tr>
            <td class="time"><?= e(fmt_waktu($r['recorded_at'])) ?></td>
            <td><?= e($r['device_name']) ?></td>
            <td class="num"><?= e(number_format((float)$r['air_temperature'], 1, ',', '.')) ?></td>
            <td class="num"><?= e((string)round((float)$r['air_humidity'])) ?></td>
            <td class="num"><?= e((string)round((float)$r['soil_moisture'])) ?></td>
            <td>
              <span class="rgb-chip" style="background:rgb(<?= (int)$r['rgb_red'] ?>,<?= (int)$r['rgb_green'] ?>,<?= (int)$r['rgb_blue'] ?>)"></span>
              <span class="rgb-val"><?= (int)$r['rgb_red'] ?>/<?= (int)$r['rgb_green'] ?>/<?= (int)$r['rgb_blue'] ?></span>
            </td>
            <td class="right"><span class="pill <?= e($statusPill[$r['plant_status']] ?? 'off') ?>"><?= e($r['plant_status']) ?></span></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <p class="tbl-scroll-hint" aria-hidden="true">Geser tabel ke samping untuk melihat semua kolom &rarr;</p>
  </section>

  <!-- Tabel aktuator -->
  <section class="panel table-panel" aria-labelledby="tbl-act-title">
    <div class="panel-head">
      <h2 class="panel-title" id="tbl-act-title">Riwayat aktuator</h2>
      <span class="badge-soft"><?= count($rowsActuator) ?> baris</span>
    </div>
    <div class="tbl-wrap" tabindex="0" role="region" aria-labelledby="tbl-act-title">
      <table class="tbl">
        <caption class="visually-hidden">Riwayat kejadian aktuator pada rentang tanggal <?= e($dateFrom) ?> sampai <?= e($dateTo) ?></caption>
        <thead><tr>
          <th scope="col">Waktu</th><th scope="col">Perangkat</th><th scope="col">Aktuator</th><th scope="col" class="right">Status</th>
        </tr></thead>
        <tbody>
        <?php if (!$rowsActuator): ?>
          <tr class="empty-row"><td colspan="4">Tidak ada data pada rentang ini.</td></tr>
        <?php endif; ?>
        <?php foreach ($rowsActuator as $r): ?>
          <tr>
            <td class="time"><?= e(fmt_waktu($r['executed_at'])) ?></td>
            <td><?= e($r['device_name']) ?></td>
            <td><?= e($r['actuator_name']) ?></td>
            <td class="right"><span class="pill <?= $r['status'] === 'ON' ? 'on' : 'off' ?>"><?= e($r['status']) ?></span></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <p class="tbl-scroll-hint" aria-hidden="true">Geser tabel ke samping untuk melihat semua kolom &rarr;</p>
  </section>
  </div>
</main>

<script>
  const hdr = document.getElementById('siteHeader');
  if (hdr) {
    const syncHdr = () => hdr.classList.toggle('scrolled', window.scrollY > 4);
    syncHdr();
    addEventListener('scroll', syncHdr, { passive: true });
  }
  const dateFromEl = document.getElementById('date_from');
  const dateToEl = document.getElementById('date_to');
  if (dateFromEl && dateToEl) {
    const syncDates = () => {
      const todayMax = dateToEl.getAttribute('max') || '';
      dateFromEl.max = dateToEl.value || todayMax;
      dateToEl.min = dateFromEl.value || '';
    };
    syncDates();
    dateFromEl.addEventListener('change', syncDates);
    dateToEl.addEventListener('change', syncDates);
  }
  // isyarat geser vertikal & horizontal pada tabel
  document.querySelectorAll('.tbl-wrap').forEach(w => {
    const sync = () => {
      w.classList.toggle('is-scrollable', w.scrollHeight > w.clientHeight + 2);
      w.classList.toggle('at-end', w.scrollTop + w.clientHeight >= w.scrollHeight - 4);
      w.classList.toggle('has-x', w.scrollWidth > w.clientWidth + 4);
    };
    sync();
    w.addEventListener('scroll', sync, { passive: true });
    addEventListener('resize', sync);
  });
</script>
</body>
</html>
