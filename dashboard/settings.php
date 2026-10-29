<?php
// settings.php - pengaturan ambang histeresis via tabel configs (wajib login).
// Slider disimpan ke configs id=1; divalidasi server-side agar tetap histeresis valid.
require_once __DIR__ . '/db.php';
require_login();

$user = current_user();
$cfg = db()->query('SELECT * FROM configs WHERE id = 1')->fetch()
    ?: ['soil_on' => 40, 'soil_off' => 55, 'temp_on' => 32, 'temp_off' => 31, 'fert_duration_s' => 7];

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
    }

    if ($error === '') {
        $stmt = db()->prepare(
            'UPDATE configs SET soil_on=?, soil_off=?, temp_on=?, temp_off=?, fert_duration_s=?
             WHERE id=1'
        );
        $stmt->execute([$soilOn, $soilOff, $tempOn, $tempOff, $fert]);
        if ($stmt->rowCount() === 0) {
            db()->prepare(
                'INSERT INTO configs (id, soil_on, soil_off, temp_on, temp_off, fert_duration_s)
                 VALUES (1, ?, ?, ?, ?, ?)'
            )->execute([$soilOn, $soilOff, $tempOn, $tempOff, $fert]);
        }
        header('Location: settings.php?saved=1');
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
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Archivo:wght@400;500;600;700&family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="theme.css">
</head>
<body>
<?= navbar('settings', $user['name']) ?>

<main>
  <div class="settings-wrap">
  <h1 class="page-title">Pengaturan Ambang Histeresis</h1>
  <p class="lead-muted">Nilai disimpan ke tabel <code>configs</code> dan menjadi acuan
    logika kendali firmware (pompa air &amp; kipas exhaust).</p>

  <?php if ($saved): ?>
    <div class="alert ok" role="alert">Konfigurasi tersimpan.</div>
  <?php endif; ?>
  <?php if ($error !== ''): ?>
    <div class="alert err" role="alert"><?= e($error) ?></div>
  <?php endif; ?>

  <form method="post" action="settings.php" class="panel">
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
