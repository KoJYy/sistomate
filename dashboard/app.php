<?php
// app.php - dashboard operasional SISTOMATE (wajib login).
// Visualisasi: gauge meter suhu/kelembapan, indikator warna daun RGB + badge
// status tanaman, badge aktuator, grafik tren Chart.js (data dari live.php).
require_once __DIR__ . '/db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_login();

$user = current_user();

// Ambang histeresis dari tabel configs (fallback = nilai firmware).
$cfg = db()->query('SELECT * FROM configs WHERE id = 1')->fetch()
    ?: ['soil_on' => 40, 'soil_off' => 55, 'temp_on' => 32, 'temp_off' => 31, 'fert_duration_s' => 7];

$statusColors = ['sehat' => 'success', 'kering' => 'warning', 'layu' => 'info', 'sakit' => 'danger'];
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dashboard - SISTOMATE</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<style>
  body { background: #f4f7f4; }
  .card-m { border: none; border-radius: 1rem; box-shadow: 0 6px 16px rgba(0,0,0,.06); }
  .gauge-wrap { position: relative; }
  .gauge-wrap canvas { width: 100%; height: auto; }
  .gauge-val { position: absolute; left: 0; right: 0; bottom: 6%;
               text-align: center; font-weight: 700; font-size: 1.35rem; }
  .rgb-bar { height: 10px; border-radius: 5px; }
  #statBadge { font-size: 1rem; }
</style>
</head>
<body>
<?= navbar('app', $user['name']) ?>

<main class="container pb-5">
  <!-- Baris status + koneksi -->
  <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
      <h1 class="h4 fw-bold mb-0">Dashboard Real-time</h1>
      <small class="text-muted" id="deviceInfo">Greenhouse Mini - memuat…</small>
    </div>
    <div class="text-end">
      <span class="badge bg-secondary" id="statBadge">…</span>
      <span class="badge bg-success" id="connBadge">MENGHUBUNGKAN</span>
    </div>
  </div>

  <!-- GAUGE METER -->
  <div class="row g-3 mb-3">
    <div class="col-md-4">
      <div class="card card-m p-3 text-center">
        <div class="small text-muted mb-1">🌡️ Suhu Udara</div>
        <div class="gauge-wrap"><canvas id="gTemp" width="300" height="190"></canvas>
          <div class="gauge-val" id="vTemp">–</div></div>
        <div class="small text-muted">Ambang kipas: &gt; <?= e((string)$cfg['temp_on']) ?> °C (ON) /
          <?= e((string)$cfg['temp_off']) ?> °C (OFF)</div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card card-m p-3 text-center">
        <div class="small text-muted mb-1">💦 Kelembapan Udara</div>
        <div class="gauge-wrap"><canvas id="gHum" width="300" height="190"></canvas>
          <div class="gauge-val" id="vHum">–</div></div>
        <div class="small text-muted">Relatif terhadap udara greenhouse</div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card card-m p-3 text-center">
        <div class="small text-muted mb-1">🌱 Kelembapan Tanah</div>
        <div class="gauge-wrap"><canvas id="gSoil" width="300" height="190"></canvas>
          <div class="gauge-val" id="vSoil">–</div></div>
        <div class="small text-muted">Ambang pompa: &lt; <?= e((string)$cfg['soil_on']) ?> % (ON) /
          &ge; <?= e((string)$cfg['soil_off']) ?> % (OFF)</div>
      </div>
    </div>
  </div>

  <!-- RGB + AKTUATOR -->
  <div class="row g-3 mb-3">
    <div class="col-md-6">
      <div class="card card-m p-3 h-100">
        <h2 class="h6 fw-bold">🌈 Warna Daun (TCS34725)</h2>
        <div class="d-flex align-items-center gap-3 mb-2">
          <div id="rgbSwatch" style="width:42px;height:42px;border-radius:10px;background:#888"></div>
          <div><span class="badge bg-success" id="leafBadge">–</span>
            <div class="small text-muted" id="leafNote">rasio hijau/merah: –</div></div>
        </div>
        <div class="small text-muted">R</div><div class="rgb-bar bg-danger mb-2" id="barR" style="width:0%"></div>
        <div class="small text-muted">G</div><div class="rgb-bar bg-success mb-2" id="barG" style="width:0%"></div>
        <div class="small text-muted">B</div><div class="rgb-bar bg-primary mb-2" id="barB" style="width:0%"></div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card card-m p-3 h-100">
        <h2 class="h6 fw-bold">⚡ Status Aktuator</h2>
        <div class="d-flex flex-wrap gap-2 mb-3" id="actBadges">
          <span class="badge bg-secondary">Pompa Air: …</span>
          <span class="badge bg-secondary">Pompa Pupuk: …</span>
          <span class="badge bg-secondary">Kipas Exhaust: …</span>
        </div>
        <h3 class="h6 fw-bold">🗒️ Transisi Terakhir</h3>
        <ul class="list-unstyled small mb-0" id="actList"><li class="text-muted">memuat…</li></ul>
      </div>
    </div>
  </div>

  <!-- GRAFIK TREN -->
  <div class="card card-m p-3">
    <h2 class="h6 fw-bold mb-2">📈 Tren 60 Bacaan Terakhir</h2>
    <canvas id="trend" height="90"></canvas>
  </div>

  <div class="small text-muted mt-3 text-center" id="todayInfo"></div>
</main>

<script>
const CFG = {
  soilOn:  <?= json_encode((float)$cfg['soil_on']) ?>,
  soilOff: <?= json_encode((float)$cfg['soil_off']) ?>,
  tempOn:  <?= json_encode((float)$cfg['temp_on']) ?>,
  tempOff: <?= json_encode((float)$cfg['temp_off']) ?>
};
const STATUS_COLORS = <?= json_encode($statusColors) ?>;

/* ---- Gauge meter spidometer bulat (canvas, tanpa pustaka) ---- */
function drawGauge(id, val, min, max, unit, marks) {
  const cv = document.getElementById(id), ctx = cv.getContext('2d');
  const W = cv.width, H = cv.height, cx = W / 2, cy = H * 0.92, R = W * 0.42;
  const A0 = Math.PI * 0.85, A1 = Math.PI * 2.15;           // busur 234 derajat
  ctx.clearRect(0, 0, W, H);
  // lintasan abu-abu
  ctx.lineWidth = 20; ctx.lineCap = 'round';
  ctx.strokeStyle = '#e5e7eb';
  ctx.beginPath(); ctx.arc(cx, cy, R, A0, A1); ctx.stroke();
  // zona ambang (histeresis)
  (marks || []).forEach(m => {
    ctx.strokeStyle = m.color; ctx.lineWidth = 20;
    const t0 = A0 + (Math.min(Math.max(m.from, min), max) - min) / (max - min) * (A1 - A0);
    const t1 = A0 + (Math.min(Math.max(m.to,   min), max) - min) / (max - min) * (A1 - A0);
    ctx.beginPath(); ctx.arc(cx, cy, R, t0, t1); ctx.stroke();
  });
  if (val == null || isNaN(val)) return;
  // jarum
  const t = A0 + (Math.min(Math.max(val, min), max) - min) / (max - min) * (A1 - A0);
  ctx.strokeStyle = '#16241B'; ctx.lineWidth = 4;
  ctx.beginPath(); ctx.moveTo(cx, cy);
  ctx.lineTo(cx + Math.cos(t) * (R - 26), cy + Math.sin(t) * (R - 26)); ctx.stroke();
  ctx.fillStyle = '#16241B';
  ctx.beginPath(); ctx.arc(cx, cy, 7, 0, Math.PI * 2); ctx.fill();
}

/* ---- Grafik tren Chart.js ---- */
const trendCtx = document.getElementById('trend');
const trend = new Chart(trendCtx, {
  type: 'line',
  data: { labels: [], datasets: [
    { label: 'Kelembapan Tanah (%)', data: [], borderColor: '#2563EB',
      backgroundColor: 'rgba(37,99,235,.12)', fill: true, tension: .3, pointRadius: 0 },
    { label: 'Suhu Udara (°C)', data: [], borderColor: '#EA580C',
      backgroundColor: 'rgba(234,88,12,.10)', fill: true, tension: .3, pointRadius: 0 }
  ]},
  options: {
    animation: false,
    scales: { x: { ticks: { maxTicksLimit: 8 } }, y: { beginAtZero: false } },
    plugins: { legend: { position: 'bottom' } }
  }
});

/* ---- Polling live.php tiap 5 detik ---- */
function fmtTime(s) { return s ? s.substring(11, 19) : ''; }

async function refresh() {
  try {
    const r = await fetch('live.php', { cache: 'no-store' });
    const d = await r.json();
    if (!d.ok) throw new Error('payload tidak ok');
    document.getElementById('connBadge').className = 'badge bg-success';
    document.getElementById('connBadge').textContent = 'LIVE';

    const L = d.latest;
    if (L) {
      drawGauge('gTemp', +L.air_temperature, 20, 45, '°C',
        [{ from: CFG.tempOn, to: 45, color: '#fca5a5' }]);
      drawGauge('gHum',  +L.air_humidity, 0, 100, '%', []);
      drawGauge('gSoil', +L.soil_moisture, 0, 100, '%',
        [{ from: 0, to: CFG.soilOn, color: '#fca5a5' },
         { from: CFG.soilOff, to: 100, color: '#86efac' }]);
      document.getElementById('vTemp').textContent = (+L.air_temperature).toFixed(1) + ' °C';
      document.getElementById('vHum').textContent  = (+L.air_humidity).toFixed(0) + ' %';
      document.getElementById('vSoil').textContent = (+L.soil_moisture).toFixed(0) + ' %';

      // status tanaman
      const badge = document.getElementById('statBadge');
      badge.className = 'badge bg-' + (STATUS_COLORS[L.plant_status] || 'secondary');
      badge.textContent = 'Status: ' + L.plant_status.toUpperCase();

      // warna daun RGB
      const mx = Math.max(+L.rgb_red, +L.rgb_green, +L.rgb_blue, 1);
      document.getElementById('barR').style.width = (+L.rgb_red  / mx * 100) + '%';
      document.getElementById('barG').style.width = (+L.rgb_green / mx * 100) + '%';
      document.getElementById('barB').style.width = (+L.rgb_blue / mx * 100) + '%';
      document.getElementById('rgbSwatch').style.background =
        `rgb(${+L.rgb_red},${+L.rgb_green},${+L.rgb_blue})`;
      const ratio = (+L.rgb_green / Math.max(+L.rgb_red, 1)).toFixed(2);
      const leaf = document.getElementById('leafBadge');
      const dominan = ratio >= 0.75 ? ['hijau dominan', 'success'] : ['merah dominan', 'danger'];
      leaf.className = 'badge bg-' + dominan[1];
      leaf.textContent = 'Daun: ' + dominan[0];
      document.getElementById('leafNote').textContent = 'rasio hijau/merah: ' + ratio;
    }
    if (d.device) {
      document.getElementById('deviceInfo').textContent =
        d.device.device_name + ' - ' + d.device.location;
    }
    if (d.today) {
      document.getElementById('todayInfo').textContent =
        'Hari ini: ' + d.today.sensor_rows + ' bacaan sensor · ' +
        d.today.actuator_events + ' event aktuator · terakhir diperbarui ' +
        new Date().toLocaleTimeString('id-ID');
    }

    // badge aktuator (status terakhir tiap nama)
    const latest = {};
    (d.actuators || []).forEach(a => { if (!(a.actuator_name in latest)) latest[a.actuator_name] = a; });
    const nama = { pompa_air: 'Pompa Air', pompa_pupuk: 'Pompa Pupuk', kipas_exhaust: 'Kipas Exhaust' };
    document.getElementById('actBadges').innerHTML =
      Object.entries(nama).map(([k, label]) => {
        const a = latest[k];
        const on = a && a.status === 'ON';
        return `<span class="badge bg-${on ? 'success' : 'secondary'}">${label}: ${a ? a.status : '–'}</span>`;
      }).join(' ');
    document.getElementById('actList').innerHTML =
      (d.actuators || []).slice(0, 5).map(a =>
        `<li>${nama[a.actuator_name] || a.actuator_name} <b>${a.status}</b> · ${fmtTime(a.executed_at)}</li>`
      ).join('') || '<li class="text-muted">belum ada event</li>';

    // grafik tren
    const hist = d.history || [];
    trend.data.labels = hist.map(h => fmtTime(h.recorded_at));
    trend.data.datasets[0].data = hist.map(h => +h.soil_moisture);
    trend.data.datasets[1].data = hist.map(h => +h.air_temperature);
    trend.update('none');
  } catch (e) {
    document.getElementById('connBadge').className = 'badge bg-danger';
    document.getElementById('connBadge').textContent = 'TERPUTUS';
  }
}
refresh();
setInterval(refresh, 5000);
</script>
</body>
</html>
