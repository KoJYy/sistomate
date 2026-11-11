<?php
// settings.php - pengaturan ambang histeresis per-device via tabel configs (wajib login).
// Satu baris configs untuk setiap devices; divalidasi server-side agar histeresis valid.
require_once __DIR__ . '/db.php';
require_login();

$user = current_user();
$devices = db()->query('SELECT id_device, device_name, location FROM devices ORDER BY id_device')->fetchAll();
$deviceIds = array_map(static fn(array $d): int => (int) $d['id_device'], $devices);
$requested = (int) ($_POST['id_device'] ?? $_GET['id_device'] ?? 0);
$deviceId = in_array($requested, $deviceIds, true) ? $requested : ($deviceIds[0] ?? 0);
$deviceCurrent = null;
foreach ($devices as $d) {
    if ((int) $d['id_device'] === $deviceId) {
        $deviceCurrent = $d;
        break;
    }
}

$defaults = ['soil_on' => 40, 'soil_off' => 55, 'temp_on' => 32, 'temp_off' => 31, 'fert_duration_s' => 7];
$cfg = $defaults;
if ($deviceId > 0) {
    $stmt = db()->prepare('SELECT * FROM configs WHERE id_device = ?');
    $stmt->execute([$deviceId]);
    $row = $stmt->fetch();
    if ($row) {
        $cfg = $row + $cfg;
    }
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $soilOn  = (float) ($_POST['soil_on']  ?? -1);
    $soilOff = (float) ($_POST['soil_off'] ?? -1);
    $tempOn  = (float) ($_POST['temp_on']  ?? -1);
    $tempOff = (float) ($_POST['temp_off'] ?? -1);
    $fert    = (int)   ($_POST['fert_duration_s'] ?? -1);

    if ($soilOn < 0 || $soilOn > 100 || $soilOff < 0 || $soilOff > 100) {
        $error = 'Ambang kelembapan tanah harus antara 0 dan 100 persen.';
    } elseif ($tempOn < 10 || $tempOn > 50 || $tempOff < 10 || $tempOff > 50) {
        $error = 'Ambang suhu harus antara 10 dan 50 derajat Celsius.';
    } elseif ($soilOn >= $soilOff) {
        $error = 'Ambang pompa ON harus lebih kecil daripada OFF (histeresis).';
    } elseif ($tempOff >= $tempOn) {
        $error = 'Ambang kipas OFF harus lebih kecil daripada ON (histeresis).';
    } elseif ($fert < 1 || $fert > 60) {
        $error = 'Durasi fertigasi harus antara 1 dan 60 detik.';
    } elseif ($deviceId <= 0) {
        $error = 'Belum ada perangkat terdaftar; tambahkan baris di tabel devices.';
    }

    if ($error === '') {
        // Upsert per-device: UNIQUE(id_device) membuat ON DUPLICATE KEY aman
        // baik saat baris belum ada maupun saat nilainya sama (rowCount 0).
        $stmt = db()->prepare(
            'INSERT INTO configs (id_device, soil_on, soil_off, temp_on, temp_off, fert_duration_s)
             VALUES (?, ?, ?, ?, ?, ?) AS new
             ON DUPLICATE KEY UPDATE
               soil_on = new.soil_on,
               soil_off = new.soil_off,
               temp_on = new.temp_on,
               temp_off = new.temp_off,
               fert_duration_s = new.fert_duration_s'
        );
        $stmt->execute([$deviceId, $soilOn, $soilOff, $tempOn, $tempOff, $fert]);
        header('Location: settings.php?saved=1&id_device=' . $deviceId);
        exit;
    }
    // simpan nilai form terkirim untuk ditampilkan lagi
    $cfg = ['soil_on' => $soilOn, 'soil_off' => $soilOff, 'temp_on' => $tempOn,
            'temp_off' => $tempOff, 'fert_duration_s' => $fert];
}
$saved = isset($_GET['saved']);
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Pengaturan - SISTOMATE</title>
<link rel="icon" type="image/svg+xml" href="assets/favicon.svg">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Archivo:wght@400;500;600;700&family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/theme.css">
</head>
<body>
<?= navbar('settings', $user['name']) ?>

<main>
  <div class="settings-wrap">
  <h1 class="page-title">Pengaturan Ambang Histeresis</h1>
  <p class="lead-muted">Nilai disimpan ke tabel <code>configs</code> per perangkat dan menjadi
    acuan logika kendali firmware (pompa air &amp; kipas exhaust).</p>

  <?php if ($saved): ?>
    <div class="alert ok" role="alert">Konfigurasi tersimpan.</div>
  <?php endif; ?>
  <?php if ($error !== ''): ?>
    <div class="alert err" role="alert"><?= e($error) ?></div>
  <?php endif; ?>

  <?php if (count($devices) > 1): ?>
  <form method="get" action="settings.php" class="panel">
    <label class="f" for="pick-device">Pilih perangkat
      <select id="pick-device" name="id_device" onchange="this.form.submit()">
        <?php foreach ($devices as $d): ?>
          <option value="<?= (int) $d['id_device'] ?>"<?= (int) $d['id_device'] === $deviceId ? ' selected' : '' ?>>
            <?= e($d['device_name']) ?> &mdash; <?= e($d['location']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>
    <noscript><button type="submit" class="btn ghost">Tampilkan</button></noscript>
  </form>
  <?php endif; ?>

  <form method="post" action="settings.php" class="panel">
    <input type="hidden" name="id_device" value="<?= (int) $deviceId ?>">
    <?php if ($deviceCurrent): ?>
      <p class="small muted">Konfigurasi untuk: <b><?= e($deviceCurrent['device_name']) ?></b>
        (<?= e($deviceCurrent['location']) ?>)</p>
    <?php endif; ?>
    <h2>Kelembapan Tanah - Pompa Air</h2>
    <label class="f">Pompa ON di bawah: <b><span id="lSoilOn"><?= e((string)$cfg['soil_on']) ?></span> %</b></label>
    <input type="range" min="5" max="95" step="1"
           id="soil_on" name="soil_on" value="<?= e((string)$cfg['soil_on']) ?>"
           oninput="lSoilOn.textContent=this.value">
    <label class="f mt-block">Pompa OFF di atas: <b><span id="lSoilOff"><?= e((string)$cfg['soil_off']) ?></span> %</b></label>
    <input type="range" min="5" max="95" step="1"
           id="soil_off" name="soil_off" value="<?= e((string)$cfg['soil_off']) ?>"
           oninput="lSoilOff.textContent=this.value">

    <h2 class="mt-block">Suhu Udara - Kipas Exhaust</h2>
    <label class="f">Kipas ON di atas: <b><span id="lTempOn"><?= e((string)$cfg['temp_on']) ?></span> &deg;C</b></label>
    <input type="range" min="15" max="45" step="1"
           id="temp_on" name="temp_on" value="<?= e((string)$cfg['temp_on']) ?>"
           oninput="lTempOn.textContent=this.value">
    <label class="f mt-block">Kipas OFF di bawah: <b><span id="lTempOff"><?= e((string)$cfg['temp_off']) ?></span> &deg;C</b></label>
    <input type="range" min="15" max="45" step="1"
           id="temp_off" name="temp_off" value="<?= e((string)$cfg['temp_off']) ?>"
           oninput="lTempOff.textContent=this.value">

    <h2 class="mt-block">Fertigasi Terjadwal</h2>
    <label class="f">Durasi dosis: <b><span id="lFert"><?= e((string)$cfg['fert_duration_s']) ?></span> detik</b></label>
    <input type="range" min="1" max="60" step="1"
           id="fert_duration_s" name="fert_duration_s" value="<?= e((string)$cfg['fert_duration_s']) ?>"
           oninput="lFert.textContent=this.value">

    <div class="mt-block">
      <button type="submit" class="btn block">Simpan Konfigurasi</button>
    </div>
  </form>

  <p class="small muted center mt-block">Perubahan berlaku untuk tampilan dashboard;
    firmware membaca nilai yang sama saat integrasi configs selesai.</p>
  </div>
</main>
</body>
</html>
