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
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  body { background: #f4f7f4; }
  .card-m { border: none; border-radius: 1rem; box-shadow: 0 6px 16px rgba(0,0,0,.06); }
  input[type=range] { accent-color: #198754; }
</style>
</head>
<body>
<?= navbar('settings', $user['name']) ?>

<main class="container pb-5" style="max-width:720px">
  <h1 class="h4 fw-bold mb-1">Pengaturan Ambang Histeresis</h1>
  <p class="text-muted small mb-3">Nilai disimpan ke tabel <code>configs</code> dan menjadi acuan
    logika kendali firmware (pompa air &amp; kipas exhaust).</p>

  <?php if ($saved): ?>
    <div class="alert alert-success py-2" role="alert">✅ Konfigurasi tersimpan.</div>
  <?php endif; ?>
  <?php if ($error !== ''): ?>
    <div class="alert alert-danger py-2" role="alert"><?= e($error) ?></div>
  <?php endif; ?>

  <form method="post" action="settings.php" class="card card-m p-4">
    <h2 class="h6 fw-bold">💧 Kelembapan Tanah - Pompa Air</h2>
    <label class="form-label">Pompa ON di bawah: <b><span id="lSoilOn"><?= e((string)$cfg['soil_on']) ?></span> %</b></label>
    <input type="range" class="form-range" min="5" max="95" step="1"
           id="soil_on" name="soil_on" value="<?= e((string)$cfg['soil_on']) ?>"
           oninput="lSoilOn.textContent=this.value">
    <label class="form-label mt-2">Pompa OFF di atas: <b><span id="lSoilOff"><?= e((string)$cfg['soil_off']) ?></span> %</b></label>
    <input type="range" class="form-range" min="5" max="95" step="1"
           id="soil_off" name="soil_off" value="<?= e((string)$cfg['soil_off']) ?>"
           oninput="lSoilOff.textContent=this.value">

    <hr>
    <h2 class="h6 fw-bold">🌡️ Suhu Udara - Kipas Exhaust</h2>
    <label class="form-label">Kipas ON di atas: <b><span id="lTempOn"><?= e((string)$cfg['temp_on']) ?></span> °C</b></label>
    <input type="range" class="form-range" min="15" max="45" step="1"
           id="temp_on" name="temp_on" value="<?= e((string)$cfg['temp_on']) ?>"
           oninput="lTempOn.textContent=this.value">
    <label class="form-label mt-2">Kipas OFF di bawah: <b><span id="lTempOff"><?= e((string)$cfg['temp_off']) ?></span> °C</b></label>
    <input type="range" class="form-range" min="15" max="45" step="1"
           id="temp_off" name="temp_off" value="<?= e((string)$cfg['temp_off']) ?>"
           oninput="lTempOff.textContent=this.value">

    <hr>
    <h2 class="h6 fw-bold">🧪 Fertigasi Terjadwal</h2>
    <label class="form-label">Durasi dosis: <b><span id="lFert"><?= e((string)$cfg['fert_duration_s']) ?></span> detik</b></label>
    <input type="range" class="form-range" min="1" max="60" step="1"
           id="fert_duration_s" name="fert_duration_s" value="<?= e((string)$cfg['fert_duration_s']) ?>"
           oninput="lFert.textContent=this.value">

    <button type="submit" class="btn btn-success w-100 mt-4">💾 Simpan Konfigurasi</button>
  </form>

  <p class="small text-muted mt-3 text-center">Perubahan berlaku untuk tampilan dashboard;
    firmware membaca nilai yang sama saat integrasi configs selesai.</p>
</main>
</body>
</html>
