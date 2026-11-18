<?php
// settings.php - pengaturan ambang histeresis per-device via tabel configs (wajib login).
// Satu baris configs per perangkat (relasi 1:1 lewat id_device); divalidasi server-side.
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

// angka rapi: 40.0 -> "40", 40.5 -> "40.5"
$fmt = fn($v) => rtrim(rtrim(number_format((float) $v, 1, '.', ''), '0'), '.');
$soilOn = (float) $cfg['soil_on'];
$soilOff = (float) $cfg['soil_off'];
$tempOn = (float) $cfg['temp_on'];
$tempOff = (float) $cfg['temp_off'];
$fert = (int) $cfg['fert_duration_s'];
// zona pita histeresis di skala 15-45 derajat C (sesuai min/max slider suhu)
$pctSuhu = fn($t) => max(0.0, min(100.0, ((float) $t - 15) / 30 * 100));
$tempOffPct = $pctSuhu($tempOff);
$tempOnPct = $pctSuhu($tempOn);
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Pengaturan - SISTOMATE</title>
<link rel="icon" type="image/svg+xml" href="favicon.svg">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Instrument+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="theme.css?v=35">
</head>
<body>
<?= navbar('settings', $user['name']) ?>

<main class="wrap page" id="main" tabindex="-1">
  <div class="page-head">
    <div>
      <p class="cap">Konfigurasi</p>
      <h1 class="page-title">Ambang Histeresis</h1>
      <p class="page-lead">
        Atur batas kerja pompa air, kipas exhaust, dan durasi fertigasi per perangkat.
        <?php if ($deviceCurrent): ?>
          &mdash; <b><?= e($deviceCurrent['device_name']) ?></b> (<?= e($deviceCurrent['location']) ?>)
        <?php endif; ?>
      </p>
    </div>
  </div>

  <div class="settings-wrap">
    <?php if ($saved): ?>
      <div class="alert ok" role="status">Konfigurasi tersimpan.</div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
      <div class="alert err" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if (count($devices) > 1): ?>
      <form method="get" action="settings.php" class="panel setting-group">
        <div class="panel-head">
          <h2 class="panel-title">Perangkat aktif</h2>
          <span class="badge-soft">Relasi 1:1 configs</span>
        </div>
        <div class="mt-block">
          <label class="f" for="pick-device">Pilih perangkat ESP32</label>
          <select id="pick-device" name="id_device" onchange="this.form.submit()">
            <?php foreach ($devices as $d): ?>
              <option value="<?= (int) $d['id_device'] ?>"<?= (int) $d['id_device'] === $deviceId ? ' selected' : '' ?>>
                <?= e($d['device_name']) ?> &mdash; <?= e($d['location']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <noscript><div class="mt-block"><button type="submit" class="btn ghost sm">Tampilkan</button></div></noscript>
      </form>
    <?php endif; ?>

    <form method="post" action="settings.php" class="stack" id="cfgForm" novalidate>
      <input type="hidden" name="id_device" value="<?= (int) $deviceId ?>">
      <!-- Kelembapan tanah -->
      <section class="panel setting-group">
        <div class="panel-head">
          <h2 class="panel-title">Kelembapan tanah</h2>
          <span class="badge-soft">Pompa air</span>
        </div>
        <p class="hint">Menyalakan pompa saat tanah kering dan mematikan setelah media cukup basah.</p>

        <div class="setting-row">
          <label for="soil_on">Pompa ON di bawah</label>
          <p class="val"><span id="lSoilOn"><?= e($fmt($soilOn)) ?></span><small>%</small></p>
          <input type="range" id="soil_on" name="soil_on" min="5" max="95" step="1"
                 value="<?= e($fmt($soilOn)) ?>" aria-valuetext="<?= e($fmt($soilOn)) ?>%">
        </div>
        <div class="setting-row">
          <label for="soil_off">Pompa OFF di atas</label>
          <p class="val"><span id="lSoilOff"><?= e($fmt($soilOff)) ?></span><small>%</small></p>
          <input type="range" id="soil_off" name="soil_off" min="5" max="95" step="1"
                 value="<?= e($fmt($soilOff)) ?>" aria-valuetext="<?= e($fmt($soilOff)) ?>%">
        </div>

        <div class="hband" id="bandSoil" aria-hidden="true">
          <span class="seg a" id="soilSegA" style="left:0;width:<?= max(0, $soilOn) ?>%"></span>
          <span class="seg b" id="soilSegB" style="left:<?= $soilOn ?>%;width:<?= max(0, $soilOff - $soilOn) ?>%"></span>
          <span class="seg c" id="soilSegC" style="left:<?= $soilOff ?>%;width:<?= max(0, 100 - $soilOff) ?>%"></span>
          <span class="marker" id="soilMarkOn" style="left:<?= $soilOn ?>%"></span>
          <span class="marker" id="soilMarkOff" style="left:<?= $soilOff ?>%"></span>
        </div>
        <div class="hband-scale" aria-hidden="true">
          <span>0%</span><span>25%</span><span>50%</span><span>75%</span><span>100%</span>
        </div>
        <div class="hband-legend">
          <span><i class="sw a"></i>pompa ON &le; <b id="soilLegendOn"><?= e($fmt($soilOn)) ?></b>%</span>
          <span><i class="sw b"></i>bertahan <b id="soilLegendBand"><?= e($fmt($soilOn)) ?>&ndash;<?= e($fmt($soilOff)) ?></b>%</span>
          <span><i class="sw c"></i>pompa OFF &ge; <b id="soilLegendOff"><?= e($fmt($soilOff)) ?></b>%</span>
        </div>
      </section>

      <!-- Suhu udara -->
      <section class="panel setting-group">
        <div class="panel-head">
          <h2 class="panel-title">Suhu udara</h2>
          <span class="badge-soft">Kipas exhaust</span>
        </div>
        <p class="hint">Menyalakan kipas saat suhu tinggi dan mematikannya setelah udara mendingin.</p>

        <div class="setting-row">
          <label for="temp_on">Kipas ON di atas</label>
          <p class="val"><span id="lTempOn"><?= e($fmt($tempOn)) ?></span><small>&deg;C</small></p>
          <input type="range" id="temp_on" name="temp_on" min="15" max="45" step="1"
                 value="<?= e($fmt($tempOn)) ?>" aria-valuetext="<?= e($fmt($tempOn)) ?> derajat Celsius">
        </div>
        <div class="setting-row">
          <label for="temp_off">Kipas OFF di bawah</label>
          <p class="val"><span id="lTempOff"><?= e($fmt($tempOff)) ?></span><small>&deg;C</small></p>
          <input type="range" id="temp_off" name="temp_off" min="15" max="45" step="1"
                 value="<?= e($fmt($tempOff)) ?>" aria-valuetext="<?= e($fmt($tempOff)) ?> derajat Celsius">
        </div>

        <div class="hband" id="bandTemp" aria-hidden="true">
          <span class="seg cool" id="tempSegA" style="left:0;width:<?= $tempOffPct ?>%"></span>
          <span class="seg b" id="tempSegB" style="left:<?= $tempOffPct ?>%;width:<?= max(0, $tempOnPct - $tempOffPct) ?>%"></span>
          <span class="seg hot" id="tempSegC" style="left:<?= $tempOnPct ?>%;width:<?= max(0, 100 - $tempOnPct) ?>%"></span>
          <span class="marker" id="tempMarkOff" style="left:<?= $tempOffPct ?>%"></span>
          <span class="marker" id="tempMarkOn" style="left:<?= $tempOnPct ?>%"></span>
        </div>
        <div class="hband-scale" aria-hidden="true">
          <span>15&deg;C</span><span>20&deg;C</span><span>25&deg;C</span><span>30&deg;C</span><span>35&deg;C</span><span>40&deg;C</span><span>45&deg;C</span>
        </div>
        <div class="hband-legend">
          <span><i class="sw cool"></i>kipas OFF &le; <b id="tempLegendOff"><?= e($fmt($tempOff)) ?></b>&deg;C</span>
          <span><i class="sw b"></i>bertahan <b id="tempLegendBand"><?= e($fmt($tempOff)) ?>&ndash;<?= e($fmt($tempOn)) ?></b>&deg;C</span>
          <span><i class="sw hot"></i>kipas ON &ge; <b id="tempLegendOn"><?= e($fmt($tempOn)) ?></b>&deg;C</span>
        </div>
      </section>

      <!-- Fertigasi -->
      <section class="panel setting-group">
        <div class="panel-head">
          <h2 class="panel-title">Fertigasi terjadwal</h2>
          <span class="badge-soft">Pompa pupuk</span>
        </div>
        <p class="hint">Lama pompa pupuk menyala setiap siklus dosis (1&ndash;60 detik).</p>
        <div class="setting-row">
          <label for="fert_duration_s">Durasi dosis</label>
          <p class="val"><span id="lFert"><?= e((string) $fert) ?></span><small>dtk</small></p>
          <input type="range" id="fert_duration_s" name="fert_duration_s" min="1" max="60" step="1"
                 value="<?= $fert ?>" aria-valuetext="<?= $fert ?> detik">
        </div>
      </section>

      <div class="save-bar" id="saveBar">
        <p class="state" id="saveState" role="status" aria-live="polite">Semua nilai sesuai konfigurasi tersimpan.</p>
        <div class="save-actions">
          <button class="btn ghost sm" id="resetBtn" type="button" hidden>Batalkan</button>
          <button class="btn" id="saveBtn" type="submit">Simpan konfigurasi</button>
        </div>
      </div>
    </form>
  </div>
</main>

<script>
  const hdr = document.getElementById('siteHeader');
  if (hdr) {
    const syncHdr = () => hdr.classList.toggle('scrolled', window.scrollY > 4);
    syncHdr();
    addEventListener('scroll', syncHdr, { passive: true });
  }

  const $ = id => document.getElementById(id);
  const fields = {
    soil_on: $('soil_on'), soil_off: $('soil_off'),
    temp_on: $('temp_on'), temp_off: $('temp_off'),
    fert_duration_s: $('fert_duration_s')
  };
  const initial = Object.fromEntries(Object.entries(fields).map(([k, el]) => [k, el.value]));

  function syncSliderCss(el, unitLabel) {
    el.style.setProperty('--attr-min', el.min || 0);
    el.style.setProperty('--attr-max', el.max || 100);
    el.style.setProperty('--control-value', el.value);
    el.setAttribute('aria-valuetext', el.value + unitLabel);
  }

  function setBand(prefix, a, b) {
    // a = batas kiri (persen), b = batas kanan (persen) pada skala 0..100
    const left = Math.min(a, b);
    const right = Math.max(a, b);
    $(prefix + 'SegA').style.width = Math.max(0, left) + '%';
    $(prefix + 'SegB').style.left = left + '%';
    $(prefix + 'SegB').style.width = Math.max(0, right - left) + '%';
    $(prefix + 'SegC').style.left = right + '%';
    $(prefix + 'SegC').style.width = Math.max(0, 100 - right) + '%';
  }

  function update() {
    const so = +fields.soil_on.value, sf = +fields.soil_off.value;
    const to = +fields.temp_on.value, tf = +fields.temp_off.value;

    syncSliderCss(fields.soil_on, '%');
    syncSliderCss(fields.soil_off, '%');
    syncSliderCss(fields.temp_on, ' derajat Celsius');
    syncSliderCss(fields.temp_off, ' derajat Celsius');
    syncSliderCss(fields.fert_duration_s, ' detik');

    $('lSoilOn').textContent = so;
    $('lSoilOff').textContent = sf;
    $('lTempOn').textContent = to;
    $('lTempOff').textContent = tf;
    $('lFert').textContent = fields.fert_duration_s.value;

    setBand('soil', so, sf);
    $('soilMarkOn').style.left = so + '%';
    $('soilMarkOff').style.left = sf + '%';
    $('soilLegendOn').textContent = so;
    $('soilLegendOff').textContent = sf;
    $('soilLegendBand').textContent = so <= sf ? (so + '\u2013' + sf) : 'tidak valid';

    // skala suhu sesuai min/max slider: 15-45 derajat C
    const tfPct = Math.max(0, Math.min(100, (tf - 15) / 30 * 100));
    const toPct = Math.max(0, Math.min(100, (to - 15) / 30 * 100));
    setBand('temp', tfPct, toPct);
    $('tempMarkOff').style.left = tfPct + '%';
    $('tempMarkOn').style.left = toPct + '%';
    $('tempLegendOn').textContent = to;
    $('tempLegendOff').textContent = tf;
    $('tempLegendBand').textContent = tf <= to ? (tf + '\u2013' + to) : 'tidak valid';

    const invalidSoil = so >= sf;
    const invalidTemp = tf >= to;
    $('bandSoil').classList.toggle('is-invalid', invalidSoil);
    $('bandTemp').classList.toggle('is-invalid', invalidTemp);
    fields.soil_on.setAttribute('aria-invalid', invalidSoil ? 'true' : 'false');
    fields.soil_off.setAttribute('aria-invalid', invalidSoil ? 'true' : 'false');
    fields.temp_on.setAttribute('aria-invalid', invalidTemp ? 'true' : 'false');
    fields.temp_off.setAttribute('aria-invalid', invalidTemp ? 'true' : 'false');

    const dirty = Object.entries(fields).some(([k, el]) => el.value !== initial[k]);
    const state = $('saveState');
    const bar = $('saveBar');
    const btn = $('saveBtn');
    const resetBtn = $('resetBtn');
    resetBtn.hidden = !dirty;
    if (bar) bar.classList.toggle('is-dirty', dirty || invalidSoil || invalidTemp);

    if (invalidSoil || invalidTemp) {
      state.textContent = invalidSoil && invalidTemp
        ? 'Ambang pompa air dan kipas exhaust tidak valid (batas ON & OFF tertukar).'
        : invalidSoil
          ? 'Ambang pompa ON harus lebih kecil daripada OFF.'
          : 'Ambang kipas OFF harus lebih kecil daripada ON.';
      state.classList.add('dirty');
      btn.disabled = true;
    } else {
      state.textContent = dirty
        ? 'Ada perubahan yang belum disimpan.'
        : 'Semua nilai sesuai konfigurasi tersimpan.';
      state.classList.toggle('dirty', dirty);
      btn.disabled = false;
    }
  }

  Object.values(fields).forEach(el => el.addEventListener('input', update));
  $('resetBtn').addEventListener('click', () => {
    Object.entries(initial).forEach(([k, val]) => { fields[k].value = val; });
    update();
  });
  $('cfgForm').addEventListener('submit', e => {
    const so = +fields.soil_on.value, sf = +fields.soil_off.value;
    const to = +fields.temp_on.value, tf = +fields.temp_off.value;
    if (so >= sf || tf >= to) {
      e.preventDefault();
      update();
      (so >= sf ? fields.soil_on : fields.temp_off).focus();
      return;
    }
    $('saveBtn').disabled = true;
    $('saveBtn').textContent = 'Menyimpan…';
  });
  update();
</script>
</body>
</html>
