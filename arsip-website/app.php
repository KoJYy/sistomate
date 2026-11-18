<?php
// app.php - dashboard operasional SISTOMATE (wajib login / demo.php).
require_once __DIR__ . '/db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_login();
$user = current_user();

// Ambang histeresis per-device dari tabel configs (fallback = nilai firmware).
$deviceId = (int) (db()->query('SELECT id_device FROM sensor_logs ORDER BY recorded_at DESC, id_log DESC LIMIT 1')->fetchColumn() ?: 0);
if ($deviceId <= 0) {
    $deviceId = (int) (db()->query('SELECT MIN(id_device) FROM devices')->fetchColumn() ?: 0);
}
$cfg = ['soil_on' => 40, 'soil_off' => 55, 'temp_on' => 32, 'temp_off' => 31, 'fert_duration_s' => 7];
if ($deviceId > 0) {
    $stmt = db()->prepare('SELECT * FROM configs WHERE id_device = ?');
    $stmt->execute([$deviceId]);
    $row = $stmt->fetch();
    if ($row) {
        $cfg = $row + $cfg;
    }
}

$soilOnPct  = max(0.0, min(100.0, (float) $cfg['soil_on']));
$soilOffPct = max(0.0, min(100.0, (float) $cfg['soil_off']));
$pctSuhu    = fn($t) => max(0.0, min(100.0, ((float) $t - 20) / 20 * 100));
$tempOffPct = $pctSuhu($cfg['temp_off']);
$tempOnPct  = $pctSuhu($cfg['temp_on']);
$cfgJs = [
    'soil_on'  => (float) $cfg['soil_on'],
    'soil_off' => (float) $cfg['soil_off'],
    'temp_on'  => (float) $cfg['temp_on'],
    'temp_off' => (float) $cfg['temp_off'],
];
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dashboard - SISTOMATE</title>
<link rel="icon" type="image/svg+xml" href="favicon.svg">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Instrument+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="theme.css?v=37">
</head>
<body>

<?= navbar('app', $user['name'], true) ?>

<main class="wrap dash" id="main" tabindex="-1">

  <noscript><div class="alert err">Dashboard ini memerlukan JavaScript untuk pembaruan data otomatis.</div></noscript>

  <!-- Ringkasan status: kompak satu baris -->
  <section class="status-card card" id="statusCard">
    <div class="status-main" aria-live="polite">
      <div class="status-mark"><span class="dot" id="statusDot" aria-hidden="true"></span></div>
      <div>
        <p class="cap status-kicker">status tanaman</p>
        <p class="status-word" id="status">-</p>
      </div>
    </div>
    <div class="status-why">
      <p class="cap">alasan</p>
      <p id="why">-</p>
    </div>
    <div class="status-meta">
      <p class="cap">pembacaan terakhir</p>
      <p class="mono" id="upd">-</p>
    </div>
  </section>

  <!-- Tiga metrik utama seragam -->
  <section class="metrics">
    <article class="metric card metric--suhu" id="metric-suhu">
      <div class="metric-head">
        <span class="cap">suhu udara <span class="cap-sep">&middot; DHT11</span></span>
        <span class="state-chip" id="suhu-state">normal</span>
      </div>
      <p class="metric-value" id="suhu">-<small>&deg;C</small></p>
      <div class="gauge">
        <div class="track" role="img" aria-label="posisi suhu pada skala 20 sampai 40 derajat celsius">
          <span class="fill" id="fill-suhu"></span>
          <span class="mark" style="left:<?= $tempOffPct ?>%"></span>
          <span class="mark" style="left:<?= $tempOnPct ?>%"></span>
        </div>
        <div class="thr-row" aria-hidden="true">
          <span class="thr" style="left:<?= $tempOnPct ?>%"><?= e((string)$cfg['temp_on']) ?>&deg; ON</span>
        </div>
        <div class="scale"><span>20&deg;</span><span>30&deg;</span><span>40&deg;</span></div>
        <div class="legend">
          <span>kipas OFF &le; <?= e((string)$cfg['temp_off']) ?>&deg;</span>
          <span class="accent">kipas ON &ge; <?= e((string)$cfg['temp_on']) ?>&deg;</span>
        </div>
      </div>
    </article>

    <article class="metric card metric--rh" id="metric-rh">
      <div class="metric-head">
        <span class="cap">kelembapan udara <span class="cap-sep">&middot; DHT11</span></span>
        <span class="state-chip" id="rh-state">normal</span>
      </div>
      <p class="metric-value" id="rh">-<small>%</small></p>
      <div class="gauge">
        <div class="track" role="img" aria-label="posisi kelembapan udara pada skala 0 sampai 100 persen">
          <span class="fill" id="fill-rh"></span>
        </div>
        <div class="scale"><span>0%</span><span>50%</span><span>100%</span></div>
        <div class="legend">
          <span>target kelembapan tajuk tomat 55%&ndash;85%</span>
        </div>
      </div>
    </article>

    <article class="metric card metric--tanah" id="metric-soil">
      <div class="metric-head">
        <span class="cap">kelembapan tanah <span class="cap-sep">&middot; kapasitif</span></span>
        <span class="state-chip" id="soil-state">normal</span>
      </div>
      <p class="metric-value" id="soil">-<small>%</small></p>
      <div class="gauge">
        <div class="track" role="img" aria-label="posisi kelembapan tanah pada skala 0 sampai 100 persen">
          <span class="fill" id="fill-soil"></span>
          <span class="mark" style="left:<?= $soilOnPct ?>%"></span>
          <span class="mark" style="left:<?= $soilOffPct ?>%"></span>
        </div>
        <div class="thr-row" aria-hidden="true">
          <span class="thr" style="left:<?= $soilOnPct ?>%"><?= e((string)$cfg['soil_on']) ?>%</span>
          <span class="thr" style="left:<?= $soilOffPct ?>%"><?= e((string)$cfg['soil_off']) ?>%</span>
        </div>
        <div class="scale"><span>0%</span><span>50%</span><span>100%</span></div>
        <div class="legend">
          <span class="accent">pompa ON &le; <?= e((string)$cfg['soil_on']) ?>%</span>
          <span>pompa OFF &ge; <?= e((string)$cfg['soil_off']) ?>%</span>
        </div>
      </div>
    </article>
  </section>

  <!-- Aktuator + riwayat terakhir -->
  <div class="split">
    <section class="panel">
      <div class="panel-head">
        <h2 class="panel-title">Aktuator</h2>
        <span class="badge-soft">relay 4 kanal &middot; 3 terpakai</span>
      </div>
      <div class="actuators">
        <div class="act" id="act-pompa">
          <div class="act-top">
            <svg class="act-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3s6 6.5 6 11a6 6 0 1 1-12 0c0-4.5 6-11 6-11z"/><path d="M9 14.5a3 3 0 0 0 3 3"/></svg>
            <span class="act-name">Pompa Air</span>
            <span class="chip" id="pompa-state">-</span>
          </div>
          <p class="act-big" id="pompa-big">-</p>
          <p class="act-last" id="pompa-last">-</p>
        </div>
        <div class="act" id="act-pupuk">
          <div class="act-top">
            <svg class="act-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 3h6"/><path d="M10 3v5.2L5.4 17.4A2.2 2.2 0 0 0 7.4 21h9.2a2.2 2.2 0 0 0 2-3.6L14 8.2V3"/><path d="M7.4 15h9.2"/></svg>
            <span class="act-name">Pompa Pupuk</span>
            <span class="chip" id="pupuk-state">-</span>
          </div>
          <p class="act-big" id="pupuk-big">-</p>
          <p class="act-last" id="pupuk-last">-</p>
        </div>
        <div class="act" id="act-kipas">
          <div class="act-top">
            <svg class="act-icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="2"/><path d="M12 10c0-4 1-7 4-7s3 4-4 7z"/><path d="M14 12c4 0 7 1 7 4s-4 3-7-4z"/><path d="M12 14c0 4-1 7-4 7s-3-4 4-7z"/><path d="M10 12c-4 0-7-1-7-4s4-3 7 4z"/></svg>
            <span class="act-name">Kipas Exhaust</span>
            <span class="chip" id="kipas-state">-</span>
          </div>
          <p class="act-big" id="kipas-big">-</p>
          <p class="act-last" id="kipas-last">-</p>
        </div>
      </div>
      <p class="act-summary" id="act-summary">-</p>
    </section>

    <section class="panel">
      <div class="panel-head">
        <h2 class="panel-title">Riwayat aktuator</h2>
        <span class="badge-soft">5 event terakhir</span>
      </div>
      <div class="tbl-wrap">
        <table class="tbl">
          <thead><tr><th>Waktu</th><th>Aktuator</th><th class="right">Status</th></tr></thead>
          <tbody id="history-act"></tbody>
        </table>
      </div>
      <p class="panel-foot"><a href="history.php">Buka riwayat lengkap &rarr;</a></p>
    </section>
  </div>

  <!-- Tren 60 bacaan terakhir (bersebelahan + tooltip hover) -->
  <section class="trends">
    <article class="panel">
      <div class="spark-head">
        <div>
          <p class="name">Kelembapan tanah</p>
          <p class="spark-range" id="rng-soil">60 bacaan terakhir</p>
        </div>
        <p class="now" id="now-soil">-<small>%</small></p>
      </div>
      <div class="chart">
        <div class="yax" aria-hidden="true">
          <span id="y-top-soil">-</span><span id="y-mid-soil">-</span><span id="y-bot-soil">-</span>
        </div>
        <div class="plot" id="plot-soil">
          <div class="chart-tip" id="tip-soil" hidden></div>
          <svg class="spark" id="sp-soil" viewBox="0 0 600 74" preserveAspectRatio="none" role="img" aria-label="Grafik tren kelembapan tanah">
            <defs>
              <linearGradient id="grad-soil" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0" stop-color="#15683E" stop-opacity=".22"/>
                <stop offset="1" stop-color="#15683E" stop-opacity="0"/>
              </linearGradient>
            </defs>
            <g stroke="#DCE3DD" stroke-width="1" vector-effect="non-scaling-stroke">
              <line x1="0" y1="8" x2="600" y2="8"/><line x1="0" y1="38" x2="600" y2="38"/><line x1="0" y1="68" x2="600" y2="68"/>
            </g>
            <line id="thr-soil" x1="0" y1="-10" x2="600" y2="-10" stroke="#8A5A2E" stroke-width="1.2" stroke-dasharray="4 4" vector-effect="non-scaling-stroke" opacity=".7"/>
            <polygon id="fill-soil" fill="url(#grad-soil)" points=""/>
            <polyline id="line-soil" fill="none" stroke="#15683E" stroke-width="2.4" vector-effect="non-scaling-stroke" points=""/>
            <line id="cross-soil" x1="0" y1="0" x2="0" y2="74" stroke="#111613" stroke-width="1" vector-effect="non-scaling-stroke" opacity=".35" hidden/>
            <circle id="dot-soil" cx="0" cy="0" r="3.5" fill="#15683E" stroke="#FFFFFF" stroke-width="1.5" hidden/>
          </svg>
          <div class="axis"><span id="x0-soil">-</span><span id="x1-soil">-</span><span>sekarang</span></div>
        </div>
      </div>
    </article>

    <article class="panel">
      <div class="spark-head">
        <div>
          <p class="name">Suhu udara</p>
          <p class="spark-range" id="rng-suhu">60 bacaan terakhir</p>
        </div>
        <p class="now" id="now-suhu">-<small>&deg;C</small></p>
      </div>
      <div class="chart">
        <div class="yax" aria-hidden="true">
          <span id="y-top-suhu">-</span><span id="y-mid-suhu">-</span><span id="y-bot-suhu">-</span>
        </div>
        <div class="plot" id="plot-suhu">
          <div class="chart-tip" id="tip-suhu" hidden></div>
          <svg class="spark" id="sp-suhu" viewBox="0 0 600 74" preserveAspectRatio="none" role="img" aria-label="Grafik tren suhu udara">
            <defs>
              <linearGradient id="grad-suhu" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0" stop-color="#C94A16" stop-opacity=".22"/>
                <stop offset="1" stop-color="#C94A16" stop-opacity="0"/>
              </linearGradient>
            </defs>
            <g stroke="#DCE3DD" stroke-width="1" vector-effect="non-scaling-stroke">
              <line x1="0" y1="8" x2="600" y2="8"/><line x1="0" y1="38" x2="600" y2="38"/><line x1="0" y1="68" x2="600" y2="68"/>
            </g>
            <line id="thr-suhu" x1="0" y1="-10" x2="600" y2="-10" stroke="#C33B3B" stroke-width="1.2" stroke-dasharray="4 4" vector-effect="non-scaling-stroke" opacity=".7"/>
            <polygon id="fill-suhu" fill="url(#grad-suhu)" points=""/>
            <polyline id="line-suhu" fill="none" stroke="#C94A16" stroke-width="2.4" vector-effect="non-scaling-stroke" points=""/>
            <line id="cross-suhu" x1="0" y1="0" x2="0" y2="74" stroke="#111613" stroke-width="1" vector-effect="non-scaling-stroke" opacity=".35" hidden/>
            <circle id="dot-suhu" cx="0" cy="0" r="3.5" fill="#C94A16" stroke="#FFFFFF" stroke-width="1.5" hidden/>
          </svg>
          <div class="axis"><span id="x0-suhu">-</span><span id="x1-suhu">-</span><span>sekarang</span></div>
        </div>
      </div>
    </article>
  </section>

  <!-- Spektrum daun -->
  <section class="panel">
    <div class="panel-head">
      <h2 class="panel-title">Warna daun</h2>
      <span class="badge-soft">TCS34725</span>
    </div>
    <div class="rgb-bars">
      <div class="rgb-item">
        <div class="label"><span>merah</span><b id="v-r">0</b></div>
        <div class="bar"><div id="b-r" style="background:var(--r);width:0%"></div></div>
      </div>
      <div class="rgb-item">
        <div class="label"><span>hijau</span><b id="v-g">0</b></div>
        <div class="bar"><div id="b-g" style="background:var(--g);width:0%"></div></div>
      </div>
      <div class="rgb-item">
        <div class="label"><span>biru</span><b id="v-b">0</b></div>
        <div class="bar"><div id="b-b" style="background:var(--b);width:0%"></div></div>
      </div>
    </div>
    <div class="rgb-verdict">
      <span class="leaf-swatch" id="leafSwatch" aria-hidden="true"></span>
      <span id="verdict">-</span>
      <span class="rgb-foot">rasio G/R <b id="ratio">-</b> &middot; batang relatif terhadap kanal tertinggi</span>
    </div>
  </section>

  <footer class="site-footer">
    <span><span id="dev">-</span> &middot; JTI TRK Polije</span>
    <span><span id="today">-</span><span class="sep">&middot;</span>pembaruan otomatis tiap 5 detik<span class="sep">&middot;</span><span class="clock" id="clock" aria-label="Waktu lokal"><?= date('H:i:s') ?></span></span>
  </footer>
</main>

<script>
  const $ = id => document.getElementById(id);
  const CFG = <?= json_encode($cfgJs, JSON_UNESCAPED_UNICODE) ?>;
  const CFG_DEVICE_ID = <?= (int) $deviceId ?>;
  const STATUS_COLOR = {
    sehat: '#15683E',
    layu: '#B27300',
    sakit: '#C33B3B',
    kering: '#8A5A2E'
  };

  let lastPollOk = Date.now();
  const chartSeries = { soil: [], suhu: [] };

  function p2(n) { return String(n).padStart(2, '0'); }
  function timeOnly(s) { return s ? String(s).slice(11, 19) : '-'; }

  function flash(el) {
    if (!el) return;
    el.classList.remove('flash');
    void el.offsetWidth;
    el.classList.add('flash');
  }

  function setText(id, txt) {
    const el = $(id);
    if (el && el.textContent !== txt) { el.textContent = txt; flash(el); }
  }

  function setVal(id, txt, unit) {
    const el = $(id);
    if (!el) return;
    if (el.dataset.v !== txt) {
      el.dataset.v = txt;
      el.innerHTML = txt + '<small>' + unit + '</small>';
      flash(el);
    }
  }

  function syncTableHints() {
    document.querySelectorAll('.tbl-wrap').forEach(w => {
      w.classList.toggle('is-scrollable', w.scrollHeight > w.clientHeight + 2);
      w.classList.toggle('at-end', w.scrollTop + w.clientHeight >= w.scrollHeight - 4);
    });
  }

  function setState(chipId, cardId, label, tone) {
    const chip = $(chipId), card = $(cardId);
    if (!chip || !card) return;
    if (chip.textContent !== label) chip.textContent = label;
    chip.className = 'state-chip ' + tone;
    card.classList.toggle('is-warn', tone === 'warn');
    card.classList.toggle('is-alert', tone === 'alert');
  }

  function gauge(id, pct) {
    const el = $(id);
    if (el) el.style.width = Math.max(0, Math.min(100, pct)) + '%';
  }

  function spark(prefix, arr, times, fmt, thrVal) {
    const line = $('line-' + prefix), fill = $('fill-' + prefix), thr = $('thr-' + prefix);
    if (!arr.length) {
      line.setAttribute('points', '');
      fill.setAttribute('points', '');
      chartSeries[prefix] = [];
      return;
    }
    const all = thrVal != null ? arr.concat([thrVal]) : arr;
    const min = Math.min(...all), max = Math.max(...all);
    const span = (max - min) || 1;
    const toY = v => 66 - ((v - min) / span) * 58;

    const coords = arr.map((v, i) => {
      const x = (i / Math.max(1, arr.length - 1)) * 600;
      const y = toY(v);
      return { x, y, v, t: timeOnly(times[i]) };
    });
    chartSeries[prefix] = coords;

    const pts = coords.map(p => p.x.toFixed(1) + ',' + p.y.toFixed(1));
    line.setAttribute('points', pts.join(' '));
    fill.setAttribute('points', pts.join(' ') + ' 600,74 0,74');

    if (thr && thrVal != null) {
      const ty = toY(thrVal).toFixed(1);
      thr.setAttribute('y1', ty);
      thr.setAttribute('y2', ty);
    }

    const set = (id, txt) => { const el = $(id); if (el) el.textContent = txt; };
    const rawMin = Math.min(...arr), rawMax = Math.max(...arr);
    set('y-top-' + prefix, fmt(max));
    set('y-mid-' + prefix, fmt(min + span / 2));
    set('y-bot-' + prefix, fmt(min));
    const rng = $('rng-' + prefix);
    if (rng) rng.textContent = arr.length + ' bacaan · min ' + fmt(rawMin) + ' · maks ' + fmt(rawMax);
    set('x0-' + prefix, timeOnly(times[0]).slice(0, 5));
    set('x1-' + prefix, timeOnly(times[arr.length >> 1]).slice(0, 5));
  }

  function bindChartHover(prefix, fmt) {
    const svg = $('sp-' + prefix), tip = $('tip-' + prefix);
    const cross = $('cross-' + prefix), dot = $('dot-' + prefix);
    if (!svg || !tip) return;

    function move(clientX) {
      const pts = chartSeries[prefix];
      if (!pts.length) return;
      const rect = svg.getBoundingClientRect();
      const rel = Math.max(0, Math.min(1, (clientX - rect.left) / Math.max(1, rect.width)));
      const idx = Math.round(rel * (pts.length - 1));
      const p = pts[idx];
      cross.setAttribute('x1', p.x.toFixed(1));
      cross.setAttribute('x2', p.x.toFixed(1));
      cross.hidden = false;
      dot.setAttribute('cx', p.x.toFixed(1));
      dot.setAttribute('cy', p.y.toFixed(1));
      dot.hidden = false;
      tip.textContent = p.t.slice(0, 5) + ' · ' + fmt(p.v);
      tip.style.left = Math.max(14, Math.min(86, (p.x / 600) * 100)) + '%';
      tip.hidden = false;
    }
    svg.addEventListener('pointermove', e => move(e.clientX));
    svg.addEventListener('pointerleave', () => {
      cross.hidden = true; dot.hidden = true; tip.hidden = true;
    });
  }

  function render(d) {
    const L = d && d.latest;
    if (!L) return;

    // Status hero
    const sw = $('status');
    const c = STATUS_COLOR[L.plant_status] || '#111613';
    if (sw.textContent !== L.plant_status) {
      sw.textContent = L.plant_status;
      flash(sw);
    }
    sw.style.color = c;
    $('statusDot').style.background = c;
    const card = $('statusCard');
    card.style.setProperty('--tint', c + '14');
    card.style.setProperty('--status', c);
    card.style.setProperty('--status-soft', c + '24');
    card.style.setProperty('--status-tint', c + '38');

    const soilR = Math.round(L.soil_moisture), tempR = (+L.air_temperature).toFixed(1);
    const why = L.plant_status === 'kering' ? 'kelembapan tanah ' + soilR + '% — di bawah ambang ' + CFG.soil_on + '%'
      : L.plant_status === 'layu' ? 'suhu ' + tempR + '°C — di atas ambang ' + CFG.temp_on + '°C'
      : L.plant_status === 'sakit' ? 'spektrum daun tidak lagi hijau dominan'
      : 'tanah ' + soilR + '% dan suhu ' + tempR + '°C — semuanya dalam batas';
    if ($('why').textContent !== why) $('why').textContent = why;
    setText('upd', timeOnly(L.recorded_at));

    // Metrik + gauge
    setVal('suhu', (+L.air_temperature).toFixed(1), '°C');
    setVal('rh', String(Math.round(L.air_humidity)), '%');
    setVal('soil', String(Math.round(L.soil_moisture)), '%');
    gauge('fill-soil', L.soil_moisture);
    gauge('fill-rh', L.air_humidity);
    gauge('fill-suhu', ((+L.air_temperature) - 20) / 20 * 100);

    const t = +L.air_temperature, s = +L.soil_moisture, h = +L.air_humidity;
    setState('suhu-state', 'metric-suhu', t >= CFG.temp_on ? 'panas' : 'normal', t >= CFG.temp_on ? 'alert' : 'ok');
    setState('soil-state', 'metric-soil', s <= CFG.soil_on ? 'kering' : (s >= CFG.soil_off ? 'basah' : 'normal'), s <= CFG.soil_on ? 'warn' : 'ok');
    setState('rh-state', 'metric-rh', h < 45 ? 'kering' : (h > 85 ? 'lembap' : 'normal'), (h < 45 || h > 85) ? 'warn' : 'ok');

    // Aktuator
    const norm = str => String(str).toLowerCase().replace(/[^a-z0-9]+/g, '');
    const byName = {};
    (d.actuators || []).forEach(a => {
      const k = norm(a.actuator_name);
      if (!byName[k]) byName[k] = a;
    });

    [['Pompa Air', 'pompa'], ['Pompa Pupuk', 'pupuk'], ['Kipas Exhaust', 'kipas']].forEach(([name, key]) => {
      const a = byName[norm(name)];
      const st = a ? a.status : '-';
      const big = $(key + '-big'), chip = $(key + '-state');
      if (big.textContent !== st) flash(big);
      big.textContent = st;
      big.className = 'act-big' + (st === 'ON' ? ' on' : '');
      chip.textContent = st;
      chip.className = 'chip' + (st === 'ON' ? ' on' : '');
      const tile = $('act-' + key);
      if (tile) tile.classList.toggle('is-on', st === 'ON');
      setText(key + '-last', a ? 'terakhir ' + timeOnly(a.executed_at) : 'belum ada event');
    });

    const aktif = ['pompa', 'pupuk', 'kipas'].filter(k => $(k + '-state').textContent === 'ON').length;
    setText('act-summary', aktif + ' dari 3 kanal aktif · perintah dari logika histeresis firmware');

    // Tabel riwayat (maks 5 event)
    const rows = (d.actuators || []).slice(0, 5).map(a =>
      '<tr><td class="time">' + timeOnly(a.executed_at) + '</td>' +
      '<td>' + a.actuator_name + '</td>' +
      '<td class="right"><span class="pill ' + (a.status === 'ON' ? 'on' : 'off') + '">' + a.status + '</span></td></tr>'
    ).join('');
    $('history-act').innerHTML = rows || '<tr class="empty-row"><td colspan="3">Belum ada event aktuator.</td></tr>';

    // Grafik tren
    const hist = d.history || [];
    const soilArr = hist.map(x => +x.soil_moisture);
    const suhuArr = hist.map(x => +x.air_temperature);
    const times = hist.map(x => x.recorded_at);
    spark('soil', soilArr, times, v => Math.round(v) + '%', CFG.soil_on);
    spark('suhu', suhuArr, times, v => v.toFixed(1) + '°', CFG.temp_on);
    $('now-soil').innerHTML = Math.round(L.soil_moisture) + '<small>%</small>';
    $('now-suhu').innerHTML = (+L.air_temperature).toFixed(1) + '<small>°C</small>';

    // RGB TCS34725
    const r = +L.rgb_red, g = +L.rgb_green, b = +L.rgb_blue;
    const mx = Math.max(r, g, b, 1);
    setText('v-r', String(r)); setText('v-g', String(g)); setText('v-b', String(b));
    $('b-r').style.width = (r / mx * 100) + '%';
    $('b-g').style.width = (g / mx * 100) + '%';
    $('b-b').style.width = (b / mx * 100) + '%';
    setText('ratio', r > 0 ? (g / r).toFixed(2) : '-');

    const redDom = g * 4 < r * 3 && r > 100;
    const redThin = !redDom && r > g && r > 100;
    const vtxt = r <= 100
      ? 'sinyal terlalu lemah — dekatkan daun ke sensor'
      : redDom
        ? 'merah dominan — indikasi daun kuning/stres'
        : redThin
          ? 'merah tipis unggul — masih dalam ambang daun sehat'
          : 'hijau dominan — spektrum daun sehat';
    const vc = r <= 100 ? '#626C66' : redDom ? '#C33B3B' : '#15683E';
    const vEl = $('verdict');
    vEl.textContent = vtxt;
    vEl.style.color = vc;
    vEl.style.fontWeight = '600';
    const lw = $('leafSwatch');
    if (lw) lw.style.background = 'rgb(' + r + ',' + g + ',' + b + ')';

    // Footer
    if (d.device) $('dev').textContent = d.device.device_name + ' · ' + d.device.location;
    if (d.today) $('today').textContent =
      Number(d.today.sensor_rows).toLocaleString('id-ID') + ' bacaan, ' +
      Number(d.today.actuator_events).toLocaleString('id-ID') + ' event hari ini';

    syncTableHints();
    lastPollOk = Date.now();
  }

  let pollTimer = null;
  async function poll() {
    try {
      const res = await fetch('live.php' + (CFG_DEVICE_ID > 0 ? ('?id_device=' + CFG_DEVICE_ID) : ''), { cache: 'no-store' });
      if (!res.ok) throw new Error(res.status);
      const d = await res.json();
      if (d.ok) render(d);
    } catch (e) { /* biarkan indikator stale menangani */ }
    finally {
      clearTimeout(pollTimer);
      pollTimer = setTimeout(poll, 5000);
    }
  }

  setInterval(() => {
    const now = new Date();
    const clock = $('clock');
    if (clock) clock.textContent = p2(now.getHours()) + ':' + p2(now.getMinutes()) + ':' + p2(now.getSeconds());
    const stale = Date.now() - lastPollOk > 15000;
    const upd = $('upd'), statusDot = $('statusDot');
    if (upd) upd.classList.toggle('stale', stale);
    if (statusDot) statusDot.classList.toggle('stale', stale);
  }, 1000);

  document.querySelectorAll('.tbl-wrap').forEach(w => w.addEventListener('scroll', syncTableHints, { passive: true }));
  addEventListener('resize', syncTableHints);

  const hdr = document.getElementById('siteHeader');
  const syncHdr = () => hdr && hdr.classList.toggle('scrolled', window.scrollY > 4);
  addEventListener('scroll', syncHdr, { passive: true });
  syncHdr();

  bindChartHover('soil', v => Math.round(v) + '%');
  bindChartHover('suhu', v => v.toFixed(1) + '°C');
  poll();
</script>
</body>
</html>
