<?php
// app.php - dashboard operasional SISTOMATE (wajib login).
// Restorasi desain asli: font Archivo + palet sage green (dari commit 58c26fa).
require_once __DIR__ . '/db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_login();
$user = current_user();

// Ambang histeresis dari tabel configs (fallback = nilai firmware).
$cfg = db()->query('SELECT * FROM configs WHERE id = 1')->fetch()
    ?: ['soil_on' => 40, 'soil_off' => 55, 'temp_on' => 32, 'temp_off' => 31, 'fert_duration_s' => 7];

// Posisi zona ambang di gauge (skala suhu 20-40 derajat C, tanah 0-100%).
$soilOnPct  = max(0.0, min(100.0, (float) $cfg['soil_on']));
$soilOffPct = max(0.0, min(100.0, (float) $cfg['soil_off']));
$pctSuhu    = fn($t) => max(0.0, min(100.0, ((float) $t - 20) / 20 * 100));
$tempOffPct = $pctSuhu($cfg['temp_off']);
$tempOnPct  = $pctSuhu($cfg['temp_on']);
$cfgJs = [
    'soil_on' => (float) $cfg['soil_on'],
    'soil_off' => (float) $cfg['soil_off'],
    'temp_on' => (float) $cfg['temp_on'],
    'temp_off' => (float) $cfg['temp_off'],
];
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SISTOMATE - Monitor Tanaman Tomat</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Archivo:wght@400;500;600;700&family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,700;1,9..144,600;1,9..144,700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" defer></script>
<style>
  :root {
    --bg: #EFF3EC;
    --surface: #FFFFFF;
    --line: #D7E0D4;
    --line-soft: #E6ECE3;
    --ink: #16241B;
    --muted: #465B4E;
    --sehat: #167D47;
    --layu: #D08700;
    --sakit: #D64545;
    --kering: #9A6A3C;
    --on: #0B7285;
    --soilc: #2563EB;
    --suhuc: #EA580C;
    --sans: "Archivo", system-ui, -apple-system, sans-serif;
    --serif: "Fraunces", Georgia, serif;
  }
  * { box-sizing: border-box; margin: 0; }
  html { background: var(--bg); scrollbar-gutter: stable; overflow-y: scroll; }
  body {
    background: var(--bg);
    color: var(--ink);
    font-family: var(--sans);
    font-size: 18px;
    line-height: 1.5;
    -webkit-font-smoothing: antialiased;
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 32px 64px;
  }
  :focus-visible { outline: 2px solid var(--sehat); outline-offset: 2px; }

  /* header */
  header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 18px 0 14px;
    font-size: 15px;
    gap: 14px;
    flex-wrap: wrap;
  }
  /* sisi kiri: brand + nav pil terkunci berdampingan (posisi nav tak melompat) */
  .brand-group { display: flex; align-items: center; gap: 24px; }
  /* sisi kanan: [live] + nama user + logout */
  .header-right { display: flex; align-items: center; gap: 16px; }
  .brand { font-weight: 700; letter-spacing: .02em; font-size: 20px; }
  .brand span { color: var(--muted); font-weight: 500; margin-left: 10px; font-size: 15px; }
  .live { display: flex; align-items: center; gap: 10px; color: var(--muted); font-variant-numeric: tabular-nums; }
  .live b { color: var(--ink); font-weight: 600; letter-spacing: .04em; font-size: 14.5px; }
  .dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: var(--sehat);
  }
  .dot.stale { background: var(--sakit); }

  /* navigasi utama */
  .navbar { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
  .navbar a {
    color: var(--muted);
    text-decoration: none;
    font-size: 16px;
    font-weight: 500;
    padding: 6px 13px;
    border-radius: 999px;
    transition: background .15s, color .15s;
  }
  .navbar a:hover { background: var(--line-soft); color: var(--ink); }
  .navbar a.on { background: var(--ink); color: var(--bg); font-weight: 600; }
  .uname { color: var(--muted); font-size: 15.5px; padding: 0 8px; }
  .logout {
    color: var(--muted);
    text-decoration: none;
    font-size: 15px;
    font-weight: 500;
    padding: 6px 13px;
    border-radius: 999px;
    transition: background .15s, color .15s;
  }
  .logout:hover { background: var(--line-soft); color: var(--ink); }

  /* hero: kartu status bertint + 3 kartu metrik */
  .hero { display: grid; gap: 14px; padding: 14px 0 8px; }
  .status-card {
    background: rgba(27, 158, 90, .1);
    border: 1px solid var(--line);
    border-radius: 20px;
    padding: 26px 30px 28px;
    transition: background .5s;
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 24px;
  }
  .status-why { text-align: right; max-width: 270px; margin-bottom: 10px; }
  .status-why span { font-size: 15px; color: var(--muted); }
  .status-why p { font-size: 17px; line-height: 1.45; margin-top: 4px; }
  .status-kicker { font-size: 16px; color: var(--muted); margin-bottom: 6px; }
  .status-word {
    font-family: var(--sans);
    font-weight: 700;
    font-size: clamp(5rem, 12vw, 8rem);
    line-height: 1;
    letter-spacing: -.03em;
    color: var(--sehat);
    transition: color .5s;
  }

  .metrics { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }
  .metric {
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: 16px;
    padding: 16px 20px 20px;
    box-shadow: 0 1px 2px rgba(22, 36, 27, .05);
  }
  .metric .top {
    display: flex; justify-content: space-between; align-items: baseline;
    margin-bottom: 10px;
  }
  .metric .label { font-size: 15px; color: var(--muted); }
  .metric .value {
    font-size: clamp(3rem, 4vw, 3.5rem);
    font-weight: 700;
    font-variant-numeric: tabular-nums;
    letter-spacing: -.02em;
    line-height: 1;
  }
  .metric .value small { font-size: .48em; font-weight: 600; color: var(--muted); margin-left: 2px; }

  /* gauge ambang histeresis */
  .track { position: relative; height: 12px; background: var(--line-soft); border-radius: 4px; overflow: visible; }
  .zone { position: absolute; top: 0; bottom: 0; border-radius: 4px; }
  .needle {
    position: absolute; top: -4px; bottom: -4px; width: 4px;
    background: var(--ink);
    border-radius: 2px;
    transition: left .8s cubic-bezier(.2, .7, .3, 1);
  }
  .tick { position: absolute; top: -3px; bottom: -3px; width: 1px; background: var(--muted); opacity: .55; }
  .amb { font-size: 15px; color: var(--muted); margin-top: 8px; font-variant-numeric: tabular-nums; }

  /* dua kolom: aktuator + riwayat */
  .pair {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
    gap: 14px;
    padding: 14px 0;
  }
  .panel {
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: 16px;
    padding: 18px 22px 20px;
    box-shadow: 0 1px 2px rgba(22, 36, 27, .05);
  }
  /* panel dalam .pair ikut tinggi baris grid */
  .pair .panel { display: flex; flex-direction: column; }
  .pair .panel .tiles { flex: 1; }
  h2 { font-size: 15px; font-weight: 600; color: var(--muted); margin-bottom: 14px; }

  /* tile aktuator: FLAT - bukan kartu bersarang (panel sudah kartu); pemisah = garis 1px */
  .tiles { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
  .tile {
    display: flex;
    flex-direction: column;
    padding: 4px 12px 4px 0;
  }
  .tile + .tile { border-left: 1px solid var(--line-soft); padding-left: 16px; }
  .tile .act-name { font-size: 16px; font-weight: 600; }
  .tile .big {
    font-size: 2.4rem;
    font-weight: 700;
    letter-spacing: -.02em;
    line-height: 1.15;
    color: var(--muted);
    transition: color .25s;
  }
  .tile .big.on { color: var(--on); }
  .tile .act-last { font-size: 14px; color: var(--muted); font-variant-numeric: tabular-nums; margin-top: auto; padding-top: 8px; }
  .chip {
    display: inline-block;
    align-self: flex-start;
    font-size: 14px; font-weight: 700; letter-spacing: .05em;
    padding: 3px 11px;
    border-radius: 999px;
    background: var(--line-soft);
    color: var(--muted);
    margin-top: 6px;
    transition: background .25s, color .25s;
  }
  .chip.on { background: var(--on); color: #fff; }

  table { width: 100%; border-collapse: collapse; font-size: 15px; }
  td { padding: 9px 0; border-bottom: 1px solid var(--line-soft); }
  tr:last-child td { border-bottom: 0; }
  td.t { color: var(--muted); font-variant-numeric: tabular-nums; width: 92px; }
  td.st { text-align: right; width: 64px; font-weight: 700; font-size: 14.5px; }
  .st-on { color: var(--on); }
  .st-off { color: var(--muted); }

  /* tren: area chart */
  .trend { padding: 4px 0; }
  .sparks { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
  .spark-head {
    display: flex; justify-content: space-between; align-items: baseline;
    margin-bottom: 8px;
  }
  .spark-head .name { font-size: 15px; color: var(--muted); }
  .spark-head .now {
    font-size: clamp(1.25rem, 2.2vw, 1.6rem); font-weight: 700;
    font-variant-numeric: tabular-nums;
  }
  .spark-head .now small { font-size: .55em; color: var(--muted); font-weight: 600; }
  svg.spark { display: block; width: 100%; height: 130px; background: var(--bg); border: 1px solid var(--line-soft); border-radius: 10px; }
  .axis { display: flex; justify-content: space-between; font-size: 14px; color: var(--muted); margin-top: 6px; font-variant-numeric: tabular-nums; }
  /* sumbu: label Y HTML di kiri (SVG stretched, teks di dlm viewBox bakal melar) */
  .chart { display: flex; gap: 8px; }
  .yax {
    display: flex; flex-direction: column; justify-content: space-between;
    align-items: flex-end; flex: none;
    width: 46px; height: 130px; padding: 3px 0 9px;
    font-size: 14px; color: var(--muted); font-variant-numeric: tabular-nums;
  }
  .plot { flex: 1; min-width: 0; }

  /* rgb */
  .rgb { padding: 4px 0; }
  .rgb-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-bottom: 16px; }
  .rgb-card {
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: 14px;
    padding: 14px 16px 16px;
    box-shadow: 0 1px 2px rgba(22, 36, 27, .05);
  }
  .rgb .label { display: flex; justify-content: space-between; align-items: baseline; font-size: 15px; color: var(--muted); margin-bottom: 9px; }
  .rgb .label b { color: var(--ink); font-weight: 700; font-variant-numeric: tabular-nums; font-size: 1.5rem; }
  .bar { height: 16px; background: var(--line-soft); border-radius: 6px; overflow: hidden; }
  .bar div { height: 100%; border-radius: 6px; transition: width .8s cubic-bezier(.2, .7, .3, 1); }
  .verdict {
    display: inline-block;
    font-size: 16px; font-weight: 600;
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: 999px;
    padding: 10px 24px;
    box-shadow: 0 1px 2px rgba(22, 36, 27, .05);
  }

  footer {
    margin-top: 18px;
    padding-top: 16px;
    border-top: 1px solid var(--line);
    font-size: 14.5px; color: var(--muted);
    display: flex; justify-content: space-between; flex-wrap: wrap; gap: 8px;
  }

  /* angka yang barusan berubah */
  .flash { animation: flashbg .7s ease-out; }
  @keyframes flashbg {
    0% { background: rgba(27, 158, 90, .18); }
    100% { background: transparent; }
  }

  @media (max-width: 760px) {
    .brand span { display: none; }
    .brand-group { flex-wrap: wrap; width: 100%; }
    .navbar { width: 100%; overflow-x: auto; }
    .metrics, .sparks, .rgb-grid, .tiles { grid-template-columns: 1fr; }
    .pair { grid-template-columns: 1fr; gap: 14px; }
    .status-card { padding: 22px 22px 24px; flex-direction: column; align-items: flex-start; gap: 14px; }
    .status-why { text-align: left; margin-bottom: 0; max-width: none; }
    .status-word { font-size: clamp(4rem, 14vw, 6rem); }
  }
  @media (prefers-reduced-motion: reduce) {
    .dot { animation: none; }
    .flash { animation: none; }
    .needle, .bar div, .status-word, .chip, .tile .big, .status-card { transition: none; }
  }
</style>
</head>
<body>

<?= navbar('app', $user['name'], true) ?>

<section class="hero">
  <div class="status-card" id="statusCard">
    <div class="status-main">
      <div class="status-kicker">status tanaman</div>
      <div class="status-word" id="status">-</div>
    </div>
    <div class="status-why">
      <span>alasan</span>
      <p id="why">-</p>
    </div>
  </div>
  <div class="metrics">
    <div class="metric">
      <div class="top">
        <span class="label">suhu udara</span>
        <span class="value" id="suhu">-<small>°C</small></span>
      </div>
      <div class="track">
        <div class="zone" style="left:0;width:<?= $tempOffPct ?>%;background:rgba(27,158,90,.28)"></div>
        <div class="zone" style="left:<?= $tempOnPct ?>%;right:0;background:rgba(208,135,0,.32)"></div>
        <div class="tick" style="left:<?= $tempOffPct ?>%"></div>
        <div class="tick" style="left:<?= $tempOnPct ?>%"></div>
        <div class="needle" id="ndl-suhu" style="left:50%"></div>
      </div>
      <div class="amb">kipas: ON di atas <?= e((string)$cfg['temp_on']) ?>&deg;C, OFF di bawah <?= e((string)$cfg['temp_off']) ?>&deg;C</div>
    </div>
    <div class="metric">
      <div class="top">
        <span class="label">kelembapan udara</span>
        <span class="value" id="rh">-<small>%</small></span>
      </div>
    </div>
    <div class="metric">
      <div class="top">
        <span class="label">kelembapan tanah</span>
        <span class="value" id="soil">-<small>%</small></span>
      </div>
      <div class="track">
        <div class="zone" style="left:0;width:<?= $soilOnPct ?>%;background:rgba(154,106,60,.35)"></div>
        <div class="tick" style="left:<?= $soilOnPct ?>%"></div>
        <div class="tick" style="left:<?= $soilOffPct ?>%"></div>
        <div class="needle" id="ndl-soil" style="left:50%"></div>
      </div>
      <div class="amb">pompa: ON di bawah <?= e((string)$cfg['soil_on']) ?>%, OFF di atas <?= e((string)$cfg['soil_off']) ?>%</div>
    </div>
  </div>
</section>

<div class="pair">
  <div class="panel">
    <h2>aktuator</h2>
    <div class="tiles">
      <div class="tile">
        <div class="act-name">Pompa Air</div>
        <div class="big" id="pompa-big">-</div>
        <span class="chip" id="pompa-state">-</span>
        <div class="act-last" id="pompa-last">-</div>
      </div>
      <div class="tile">
        <div class="act-name">Pompa Pupuk</div>
        <div class="big" id="pupuk-big">-</div>
        <span class="chip" id="pupuk-state">-</span>
        <div class="act-last" id="pupuk-last">-</div>
      </div>
      <div class="tile">
        <div class="act-name">Kipas Exhaust</div>
        <div class="big" id="kipas-big">-</div>
        <span class="chip" id="kipas-state">-</span>
        <div class="act-last" id="kipas-last">-</div>
      </div>
    </div>
  </div>
  <div class="panel">
    <h2>riwayat aktuator</h2>
    <table><tbody id="history-act"></tbody></table>
  </div>
</div>

<section class="trend">
  <div class="sparks">
    <div class="panel">
      <div class="spark-head">
        <span class="name">kelembapan tanah</span>
        <span class="now" id="now-soil">-<small>%</small></span>
      </div>
      <div class="chart">
        <div class="yax" aria-hidden="true">
          <span id="y-top-soil">-</span><span id="y-mid-soil">-</span><span id="y-bot-soil">-</span>
        </div>
        <div class="plot">
          <svg class="spark" id="sp-soil" viewBox="0 0 600 74" preserveAspectRatio="none" aria-hidden="true">
            <g stroke="#D7E0D4" stroke-width="1" vector-effect="non-scaling-stroke">
              <line x1="0" y1="8" x2="600" y2="8"/><line x1="0" y1="38" x2="600" y2="38"/><line x1="0" y1="68" x2="600" y2="68"/>
            </g>
            <polygon id="fill-soil" fill="#2563EB" fill-opacity=".14" points=""/>
            <polyline id="line-soil" fill="none" stroke="#2563EB" stroke-width="2.5" vector-effect="non-scaling-stroke" points=""/>
          </svg>
          <div class="axis"><span id="x0-soil">-</span><span id="x1-soil">-</span><span>sekarang</span></div>
        </div>
      </div>
    </div>
    <div class="panel">
      <div class="spark-head">
        <span class="name">suhu udara</span>
        <span class="now" id="now-suhu">-<small>°C</small></span>
      </div>
      <div class="chart">
        <div class="yax" aria-hidden="true">
          <span id="y-top-suhu">-</span><span id="y-mid-suhu">-</span><span id="y-bot-suhu">-</span>
        </div>
        <div class="plot">
          <svg class="spark" id="sp-suhu" viewBox="0 0 600 74" preserveAspectRatio="none" aria-hidden="true">
            <g stroke="#D7E0D4" stroke-width="1" vector-effect="non-scaling-stroke">
              <line x1="0" y1="8" x2="600" y2="8"/><line x1="0" y1="38" x2="600" y2="38"/><line x1="0" y1="68" x2="600" y2="68"/>
            </g>
            <polygon id="fill-suhu" fill="#EA580C" fill-opacity=".14" points=""/>
            <polyline id="line-suhu" fill="none" stroke="#EA580C" stroke-width="2.5" vector-effect="non-scaling-stroke" points=""/>
          </svg>
          <div class="axis"><span id="x0-suhu">-</span><span id="x1-suhu">-</span><span>sekarang</span></div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="rgb">
  <h2 style="margin-top:14px">warna daun (TCS34725)</h2>
  <div class="rgb-grid">
    <div class="rgb-card">
      <div class="label">merah <b id="v-r">0</b></div>
      <div class="bar"><div id="b-r" style="background:#EF4444;width:0%"></div></div>
    </div>
    <div class="rgb-card">
      <div class="label">hijau <b id="v-g">0</b></div>
      <div class="bar"><div id="b-g" style="background:#1B9E5A;width:0%"></div></div>
    </div>
    <div class="rgb-card">
      <div class="label">biru <b id="v-b">0</b></div>
      <div class="bar"><div id="b-b" style="background:#2563EB;width:0%"></div></div>
    </div>
  </div>
  <div class="verdict" id="verdict">-</div>
</section>

<footer>
  <span id="dev">-</span>
  <span id="today">-</span>
  <span>update tiap 5 detik</span>
</footer>

<script>
  const $ = id => document.getElementById(id);
  // ambang histeresis dari tabel configs (di-encode PHP, fallback firmware 40/55/32/31/7)
  const CFG = <?= json_encode($cfgJs, JSON_UNESCAPED_UNICODE) ?>;
  const STATUS_COLOR = {
    sehat: '#1B9E5A', layu: '#D08700',
    sakit: '#D64545', kering: '#9A6A3C'
  };
  let lastPollOk = Date.now();

  function flash(el) {
    el.classList.remove('flash');
    void el.offsetWidth;          // restart animasi
    el.classList.add('flash');
  }

  function setText(id, txt) {
    const el = $(id);
    if (el.textContent !== txt) { el.textContent = txt; flash(el); }
  }

  // angka + satuan: flash HANYA kalau nilai berubah (bandingin dataset, bukan innerHTML)
  function setVal(id, txt, unit) {
    const el = $(id);
    if (el.dataset.v !== txt) {
      el.dataset.v = txt;
      el.innerHTML = txt + '<small>' + unit + '</small>';
      flash(el);
    }
  }

  function gauge(id, pct) {
    const el = $(id);
    const v = Math.max(0, Math.min(100, pct));
    el.style.left = v + '%';
  }

  // area chart: polyline + polygon ditutup ke baseline (fill di bawah garis)
  function spark(lineId, fillId, arr) {
    const line = $(lineId), fill = $(fillId);
    if (!arr.length) { line.setAttribute('points', ''); fill.setAttribute('points', ''); return; }
    const min = Math.min(...arr), max = Math.max(...arr);
    const span = (max - min) || 1;
    const pts = arr.map((v, i) => {
      const x = (i / Math.max(1, arr.length - 1)) * 600;
      const y = 68 - ((v - min) / span) * 60;   // padding 4px atas-bawah
      return x.toFixed(1) + ',' + y.toFixed(1);
    });
    line.setAttribute('points', pts.join(' '));
    fill.setAttribute('points', pts.join(' ') + ' 600,74 0,74');
  }

  function timeOnly(s) {
    if (!s) return '-';
    return s.slice(11, 19);       // "HH:MM:SS" dari datetime MySQL
  }

  // label sumbu: Y dari min/mid/max data, X dari jam terlama + jam tengah history
  function axes(prefix, arr, times, fmt) {
    const set = (id, txt) => { $(id).textContent = txt; };
    if (!arr.length) {
      ['y-top-', 'y-mid-', 'y-bot-'].forEach(p => set(p + prefix, '-'));
      set('x0-' + prefix, '-'); set('x1-' + prefix, '-');
      return;
    }
    const min = Math.min(...arr), max = Math.max(...arr);
    const span = (max - min) || 1;
    set('y-top-' + prefix, fmt(max));
    set('y-mid-' + prefix, fmt(min + span / 2));
    set('y-bot-' + prefix, fmt(min));
    const n = arr.length;
    set('x0-' + prefix, timeOnly(times[0]).slice(0, 5));      // HH:MM
    set('x1-' + prefix, timeOnly(times[n >> 1]).slice(0, 5));
  }

  function render(d) {
    const L = d.latest;
    if (!L) return;

    // status hero: warna kata + tint latar kartu (hex 8-digit, alpha 10%)
    const sw = $('status');
    if (sw.textContent !== L.plant_status) {
      const c = STATUS_COLOR[L.plant_status] || '#16241B';
      sw.textContent = L.plant_status;
      sw.style.color = c;
      $('statusCard').style.background = c + '1A';
    }
    // alasan status: diturunkan dari data yang sama dengan heuristik firmware
    const soilR = Math.round(L.soil_moisture), tempR = L.air_temperature.toFixed(1);
    const why = L.plant_status === 'kering' ? 'kelembapan tanah ' + soilR + '% - di bawah ' + CFG.soil_on + '%'
      : L.plant_status === 'layu' ? 'suhu ' + tempR + '°C - di atas ' + CFG.temp_on + '°C'
      : L.plant_status === 'sakit' ? 'spektrum daun tidak lagi hijau dominan'
      : 'tanah ' + soilR + '% dan suhu ' + tempR + '°C - semuanya dalam batas';
    if ($('why').textContent !== why) $('why').textContent = why;

    // metrik + gauge (skala suhu 20-40°C)
    setVal('suhu', L.air_temperature.toFixed(1), '°C');
    setVal('rh', String(Math.round(L.air_humidity)), '%');
    setVal('soil', String(Math.round(L.soil_moisture)), '%');
    gauge('ndl-soil', L.soil_moisture);
    gauge('ndl-suhu', (L.air_temperature - 20) / 20 * 100);

    // aktuator: state terakhir per nama (normalisasi: 'pompa_air' == 'Pompa Air')
    const norm = s => String(s).toLowerCase().replace(/[^a-z0-9]+/g, '');
    const byName = {};
    (d.actuators || []).forEach(a => {
      const k = norm(a.actuator_name);
      if (!byName[k]) byName[k] = a;
    });
    [['Pompa Air', 'pompa'], ['Pompa Pupuk', 'pupuk'], ['Kipas Exhaust', 'kipas']].forEach(([name, key]) => {
      const a = byName[norm(name)];
      const st = a ? a.status : '-';
      const big = $(key + '-big');
      const chip = $(key + '-state');
      if (big.textContent !== st) flash(big);
      big.textContent = st;
      big.className = 'big' + (st === 'ON' ? ' on' : '');
      if (chip.textContent !== st) flash(chip);
      chip.textContent = st;
      chip.className = 'chip' + (st === 'ON' ? ' on' : '');
      setText(key + '-last', a ? 'terakhir ' + timeOnly(a.executed_at) : 'belum ada event');
    });

    // riwayat tabel
    const rows = (d.actuators || []).map(a =>
      '<tr><td class="t">' + timeOnly(a.executed_at) + '</td>' +
      '<td>' + a.actuator_name + '</td>' +
      '<td class="st ' + (a.status === 'ON' ? 'st-on' : 'st-off') + '">' + a.status + '</td></tr>'
    ).join('');
    $('history-act').innerHTML = rows || '<tr><td class="t">-</td><td>belum ada event</td><td class="st"></td></tr>';

    // sparkline (area)
    const hist = d.history || [];
    const soilArr = hist.map(h => +h.soil_moisture);
    const suhuArr = hist.map(h => +h.air_temperature);
    spark('line-soil', 'fill-soil', soilArr);
    spark('line-suhu', 'fill-suhu', suhuArr);
    axes('soil', soilArr, hist.map(h => h.recorded_at), v => Math.round(v) + '%');
    axes('suhu', suhuArr, hist.map(h => h.recorded_at), v => v.toFixed(1) + '°C');
    $('now-soil').innerHTML = Math.round(L.soil_moisture) + '<small>%</small>';
    $('now-suhu').innerHTML = L.air_temperature.toFixed(1) + '<small>°C</small>';

    // rgb
    const r = +L.rgb_red, g = +L.rgb_green, b = +L.rgb_blue;
    const mx = Math.max(r, g, b, 1);
    setText('v-r', String(r)); setText('v-g', String(g)); setText('v-b', String(b));
    $('b-r').style.width = (r / mx * 100) + '%';
    $('b-g').style.width = (g / mx * 100) + '%';
    $('b-b').style.width = (b / mx * 100) + '%';
    const verdict = $('verdict');
    // ambang sama dengan firmware: sakit = G/R < 0.75 (heuristik 21 Okt)
    const redDom = g * 4 < r * 3 && r > 100;
    const redThin = !redDom && r > g && r > 100;
    const vtxt = r <= 100
      ? 'sinyal terlalu lemah - dekatkan daun ke sensor'
      : redDom
        ? 'merah dominan - indikasi daun kuning/stres'
        : redThin
          ? 'merah tipis unggul - masih dalam ambang daun sehat'
          : 'hijau dominan - spektrum daun sehat';
    if (verdict.dataset.t !== vtxt) {
      verdict.dataset.t = vtxt;
      verdict.innerHTML = '<b style="color:' +
        (r <= 100 ? 'var(--muted)' : redDom ? 'var(--sakit)' : 'var(--sehat)') +
        '">' + vtxt + '</b>';
    }

    // footer
    if (d.device) $('dev').textContent = d.device.device_name + ', ' + d.device.location;
    if (d.today) $('today').textContent =
      Number(d.today.sensor_rows).toLocaleString('id-ID') + ' bacaan, ' +
      Number(d.today.actuator_events).toLocaleString('id-ID') + ' event hari ini';

    lastPollOk = Date.now();
  }

  async function poll() {
    try {
      const res = await fetch('live.php', { cache: 'no-store' });
      if (!res.ok) throw new Error(res.status);
      const d = await res.json();
      if (d.ok) render(d);
    } catch (e) { /* biarkan; dot stale di bawah */ }
    finally { setTimeout(poll, 5000); }
  }

  // jam + indikator koneksi
  setInterval(() => {
    $('clock').textContent = new Date().toLocaleTimeString('id-ID', { hour12: false });
    const stale = Date.now() - lastPollOk > 12000;
    $('dot').classList.toggle('stale', stale);
    $('livetxt').textContent = stale ? 'TERPUTUS' : 'LIVE';
  }, 1000);

  poll();
</script>
</body>
</html>
