<?php
// app.php - dashboard operasional SISTOMATE (wajib login).
// Restorasi desain asli: font Archivo + palet sage green (dari commit 58c26fa).
require_once __DIR__ . '/db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_login();
$user = current_user();

// Ambang histeresis per-device dari tabel configs (fallback = nilai firmware).
// Device mengikuti bacaan sensor terakhir; kalau belum ada log, pakai device pertama.
$deviceId = (int) (db()->query('SELECT id_device FROM sensor_logs ORDER BY id_log DESC LIMIT 1')->fetchColumn() ?: 0);
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
<link rel="icon" type="image/svg+xml" href="assets/favicon.svg">
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

  /* sidebar vertikal (pengganti top navbar) */
  .sidebar {
    position: fixed;
    top: 0;
    left: 0;
    bottom: 0;
    width: 260px;
    height: 100vh;
    background: var(--surface);
    border-right: 1px solid var(--line);
    display: flex;
    flex-direction: column;
    padding: 24px 16px;
    box-sizing: border-box;
    z-index: 1000;
    overflow-y: auto;
  }

  .sidebar-brand {
    margin-bottom: 28px;
    padding: 0 4px;
  }
  .sidebar-brand .brand {
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
    color: var(--ink);
  }
  .sidebar-brand .brand-logo {
    width: 32px;
    height: 32px;
    flex-shrink: 0;
  }
  .sidebar-brand .brand-name {
    font-weight: 700;
    letter-spacing: .02em;
    font-size: 18px;
    line-height: 1.15;
  }
  .sidebar-brand .brand-sub {
    color: var(--muted);
    font-weight: 500;
    font-size: 12.5px;
    display: block;
  }

  .sidebar-nav {
    display: flex;
    flex-direction: column;
    gap: 6px;
  }
  .nav-item {
    display: flex;
    align-items: center;
    gap: 12px;
    color: var(--muted);
    text-decoration: none;
    font-size: 15px;
    font-weight: 500;
    padding: 10px 14px;
    border-radius: 10px;
    transition: background .15s, color .15s;
  }
  .nav-icon {
    width: 19px;
    height: 19px;
    flex-shrink: 0;
  }
  .nav-item:hover {
    background: var(--line-soft);
    color: var(--ink);
  }
  .nav-item.on,
  .nav-item[aria-current="page"] {
    background: var(--ink);
    color: var(--bg);
    font-weight: 600;
  }
  .nav-item.on .nav-icon,
  .nav-item[aria-current="page"] .nav-icon {
    stroke: var(--bg);
  }

  .sidebar-footer {
    margin-top: auto;
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding-top: 18px;
    border-top: 1px solid var(--line-soft);
  }

  .sidebar-footer .live {
    display: flex;
    align-items: center;
    gap: 8px;
    background: rgba(22, 125, 71, .08);
    border: 1px solid rgba(22, 125, 71, .18);
    border-radius: 10px;
    padding: 8px 12px;
    font-size: 13px;
    color: var(--muted);
    font-variant-numeric: tabular-nums;
  }
  .sidebar-footer .live b {
    color: var(--ink);
    font-weight: 600;
    letter-spacing: .04em;
    font-size: 12.5px;
  }
  .sidebar-footer .dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--sehat);
    flex-shrink: 0;
  }
  .sidebar-footer .dot.stale {
    background: var(--sakit);
  }

  .sidebar-user {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 10px;
    background: var(--line-soft);
    border-radius: 12px;
  }
  .user-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: var(--sehat);
    color: #FFFFFF;
    font-weight: 700;
    font-size: 14px;
    display: grid;
    place-items: center;
    flex-shrink: 0;
  }
  .user-info {
    flex: 1;
    min-width: 0;
  }
  .sidebar-user .uname {
    display: block;
    font-size: 13.5px;
    font-weight: 600;
    color: var(--ink);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    padding: 0;
  }
  .logout-btn {
    display: grid;
    place-items: center;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    color: var(--muted);
    text-decoration: none;
    flex-shrink: 0;
    transition: background .15s, color .15s;
  }
  .logout-btn:hover {
    background: rgba(214, 69, 69, .12);
    color: var(--sakit);
  }
  .logout-btn svg {
    width: 17px;
    height: 17px;
  }

  /* layout desktop dengan sidebar */
  @media (min-width: 821px) {
    body.has-sidebar,
    body:has(.sidebar) {
      padding-left: 260px;
      max-width: none;
      margin: 0;
      padding-top: 0;
      padding-right: 0;
      padding-bottom: 0;
    }
    .main-content,
    body.has-sidebar > main,
    body:has(.sidebar) > main {
      max-width: 1240px;
      margin: 0 auto;
      padding: 24px 36px 64px;
      box-sizing: border-box;
      width: 100%;
    }
  }

  /* hero: kartu status bertint + 3 kartu metrik */
  .hero { display: grid; gap: 14px; padding: 14px 0 8px; }
  .status-card {
    background: rgba(27, 158, 90, .1);
    border: 1px solid var(--line);
    border-radius: 16px;
    padding: 20px 26px;
    transition: background .5s;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
  }
  .status-why { text-align: right; max-width: 320px; }
  .status-why span { font-size: 13.5px; color: var(--muted); }
  .status-why p { font-size: 15.5px; line-height: 1.4; margin-top: 2px; }
  .status-kicker { font-size: 14.5px; color: var(--muted); margin-bottom: 4px; }
  .status-word {
    font-family: var(--sans);
    font-weight: 700;
    font-size: clamp(2.4rem, 4vw, 3.4rem);
    line-height: 1.1;
    letter-spacing: -.02em;
    color: var(--sehat);
    transition: color .5s;
  }

  .metrics { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }
  .metric {
    display: flex;
    flex-direction: column;
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: 16px;
    padding: 16px 20px 20px;
    box-shadow: 0 1px 2px rgba(22, 36, 27, .05);
  }
  .metric .top {
    display: flex; justify-content: space-between; align-items: baseline;
    margin-bottom: 12px;
    gap: 10px;
  }
  .metric .label {
    font-size: 14.5px;
    color: var(--muted);
    font-weight: 500;
    white-space: nowrap;
  }
  .metric .value {
    font-size: clamp(2.3rem, 3.2vw, 3rem);
    font-weight: 700;
    font-variant-numeric: tabular-nums;
    letter-spacing: -.02em;
    line-height: 1;
    white-space: nowrap;
  }
  .metric .value small { font-size: .48em; font-weight: 600; color: var(--muted); margin-left: 2px; }

  /* gauge ambang histeresis */
  .track { position: relative; height: 12px; background: var(--line-soft); border-radius: 4px; overflow: visible; margin-top: 4px; }
  .zone { position: absolute; top: 0; bottom: 0; border-radius: 4px; }
  .needle {
    position: absolute; top: -4px; bottom: -4px; width: 4px;
    background: var(--ink);
    border-radius: 2px;
    transition: left .8s cubic-bezier(.2, .7, .3, 1);
  }
  .tick { position: absolute; top: -3px; bottom: -3px; width: 1px; background: var(--muted); opacity: .55; }
  .amb { font-size: 14px; color: var(--muted); margin-top: auto; padding-top: 10px; font-variant-numeric: tabular-nums; }

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
    padding: 20px 22px 22px;
    box-shadow: 0 1px 2px rgba(22, 36, 27, .05);
  }
  /* panel dalam .pair ikut tinggi baris grid */
  .pair .panel { display: flex; flex-direction: column; }
  .pair .panel .tiles { flex: 1; }
  h2 { font-size: 14px; font-weight: 600; color: var(--muted); margin-bottom: 14px; text-transform: uppercase; letter-spacing: .04em; }

  /* daftar aktuator: baris horizontal yang lapang, proporsional, dan rapi */
  .tiles { display: flex; flex-direction: column; gap: 10px; }
  .tile {
    display: flex;
    flex-direction: row;
    align-items: center;
    gap: 14px;
    background: var(--bg);
    border: 1px solid var(--line);
    border-radius: 12px;
    padding: 12px 16px;
    transition: all .25s ease;
  }
  .tile.is-on {
    background: rgba(11, 114, 131, .06);
    border-color: rgba(11, 114, 131, .35);
  }
  .act-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: grid;
    place-items: center;
    background: var(--surface);
    border: 1px solid var(--line);
    flex-shrink: 0;
  }
  .act-pompa { color: #2563EB; background: rgba(37, 99, 235, .08); border-color: rgba(37, 99, 235, .2); }
  .act-pupuk { color: var(--sehat); background: rgba(22, 125, 71, .08); border-color: rgba(22, 125, 71, .2); }
  .act-kipas { color: #EA580C; background: rgba(234, 88, 12, .08); border-color: rgba(234, 88, 12, .2); }

  .act-body {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 2px;
  }
  .tile .act-name {
    font-size: 15px;
    font-weight: 600;
    color: var(--ink);
    line-height: 1.25;
    white-space: nowrap;
  }
  .tile .act-last {
    font-size: 12.5px;
    color: var(--muted);
    font-variant-numeric: tabular-nums;
    line-height: 1.25;
    white-space: nowrap;
  }
  .chip {
    display: inline-block;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: .04em;
    padding: 4px 12px;
    min-width: 48px;
    text-align: center;
    border-radius: 999px;
    background: var(--surface);
    color: var(--muted);
    border: 1px solid var(--line);
    flex-shrink: 0;
    transition: background .25s, color .25s, border-color .25s;
  }
  .chip.on {
    background: rgba(11, 114, 131, .15);
    color: var(--on);
    border-color: rgba(11, 114, 131, .35);
  }

  table { width: 100%; border-collapse: collapse; font-size: 14px; }
  td { padding: 9px 4px; border-bottom: 1px solid var(--line-soft); }
  tr:last-child td { border-bottom: 0; }
  td.t { color: var(--muted); font-variant-numeric: tabular-nums; width: 85px; }
  td.st { text-align: right; width: 68px; font-weight: 700; font-size: 12px; }
  .st-on {
    display: inline-block;
    padding: 3px 10px;
    min-width: 44px;
    text-align: center;
    border-radius: 999px;
    background: rgba(11, 114, 131, .15);
    color: var(--on);
  }
  .st-off {
    display: inline-block;
    padding: 3px 10px;
    min-width: 44px;
    text-align: center;
    border-radius: 999px;
    background: var(--line-soft);
    color: var(--muted);
  }

  /* tren: area chart */
  .trend { padding: 4px 0; }
  .sparks { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
  .spark-head {
    display: flex; justify-content: space-between; align-items: baseline;
    margin-bottom: 10px;
  }
  .spark-head .name { font-size: 15px; font-weight: 600; color: var(--muted); }
  .spark-head .now {
    font-size: clamp(1.35rem, 2.4vw, 1.75rem); font-weight: 700;
    font-variant-numeric: tabular-nums;
    color: var(--ink);
    letter-spacing: -.02em;
  }
  .spark-head .now small { font-size: .52em; color: var(--muted); font-weight: 600; margin-left: 2px; }
  svg.spark {
    display: block; width: 100%; height: 130px;
    background: #F8FAF7;
    border: 1px solid rgba(215, 224, 212, .75);
    border-radius: 12px;
  }
  .axis {
    display: flex; justify-content: space-between;
    font-size: 12.5px; font-weight: 500; color: var(--muted);
    margin-top: 8px; padding: 0 4px;
    font-variant-numeric: tabular-nums;
  }
  .axis span:last-child { font-weight: 600; color: var(--ink); }
  /* sumbu: label Y HTML di kiri presisi sejajar garis grid */
  .chart { display: flex; gap: 8px; }
  .yax {
    display: flex; flex-direction: column; justify-content: space-between;
    align-items: flex-end; flex: none;
    width: 44px; height: 130px; padding: 10px 0 12px;
    font-size: 13px; font-weight: 500; color: var(--muted);
    font-variant-numeric: tabular-nums;
  }
  .plot { position: relative; flex: 1; min-width: 0; }
  .spark-wrap { position: relative; }

  /* Pin live pulse HTML: bulat sempurna tanpa distorsi skala SVG */
  .chart-pin {
    position: absolute;
    width: 9px;
    height: 9px;
    border-radius: 50%;
    border: 2px solid #FFFFFF;
    transform: translate(-50%, -50%);
    display: none;
    pointer-events: none;
    z-index: 2;
  }
  .pin-soil {
    background: #2563EB;
    box-shadow: 0 0 0 2px rgba(37, 99, 235, .4);
    animation: pin-pulse-soil 2.2s infinite;
  }
  .pin-suhu {
    background: #EA580C;
    box-shadow: 0 0 0 2px rgba(234, 88, 12, .4);
    animation: pin-pulse-suhu 2.2s infinite;
  }
  @keyframes pin-pulse-soil {
    0%   { box-shadow: 0 0 0 2px rgba(37, 99, 235, .5), 0 0 0 0 rgba(37, 99, 235, .35); }
    70%  { box-shadow: 0 0 0 2px rgba(37, 99, 235, .5), 0 0 0 9px rgba(37, 99, 235, 0); }
    100% { box-shadow: 0 0 0 2px rgba(37, 99, 235, .5), 0 0 0 0 rgba(37, 99, 235, 0); }
  }
  @keyframes pin-pulse-suhu {
    0%   { box-shadow: 0 0 0 2px rgba(234, 88, 12, .5), 0 0 0 0 rgba(234, 88, 12, .35); }
    70%  { box-shadow: 0 0 0 2px rgba(234, 88, 12, .5), 0 0 0 9px rgba(234, 88, 12, 0); }
    100% { box-shadow: 0 0 0 2px rgba(234, 88, 12, .5), 0 0 0 0 rgba(234, 88, 12, 0); }
  }

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

  @media (max-width: 820px) {
    .sidebar {
      position: sticky;
      top: 0;
      width: 100%;
      height: auto;
      border-right: none;
      border-bottom: 1px solid var(--line);
      padding: 12px 16px;
      gap: 10px;
    }
    .sidebar-brand { margin-bottom: 0; }
    .sidebar-nav {
      flex-direction: row;
      overflow-x: auto;
      gap: 6px;
      padding-bottom: 2px;
    }
    .nav-item {
      white-space: nowrap;
      padding: 7px 12px;
      font-size: 14px;
    }
    .sidebar-footer {
      display: flex;
      flex-direction: row;
      align-items: center;
      justify-content: space-between;
      margin-top: 0;
      padding-top: 8px;
      border-top: 1px solid var(--line-soft);
    }
    .sidebar-footer .live { padding: 5px 10px; }
    .sidebar-user { padding: 4px 8px; }
    body.has-sidebar,
    body:has(.sidebar) {
      padding: 0 16px 48px;
    }
    .main-content,
    body.has-sidebar > main,
    body:has(.sidebar) > main {
      padding: 16px 0 48px;
    }
    .metrics, .sparks, .rgb-grid, .tiles { grid-template-columns: 1fr; }
    .pair { grid-template-columns: 1fr; gap: 14px; }
    .status-card { padding: 22px 22px 24px; flex-direction: column; align-items: flex-start; gap: 14px; }
    .status-why { text-align: left; margin-bottom: 0; max-width: none; }
    .status-word { font-size: clamp(2.2rem, 7vw, 2.8rem); }
  }
  @media (prefers-reduced-motion: reduce) {
    .dot { animation: none; }
    .flash { animation: none; }
    .chart-pin { animation: none; }
    .needle, .bar div, .status-word, .chip, .tile .big, .status-card { transition: none; }
  }
</style>
</head>
<body class="has-sidebar">

<?= navbar('app', $user['name'], true) ?>

<main class="main-content">

<section class="hero">
  <div class="status-card" id="statusCard">
    <div class="status-main">
      <div class="status-kicker">kondisi tanaman</div>
      <div class="status-word" id="status">-</div>
    </div>
    <div class="status-why">
      <span>keterangan</span>
      <p id="why">-</p>
    </div>
  </div>
  <div class="metrics">
    <div class="metric">
      <div class="top">
        <span class="label">suhu udara</span>
        <span class="value" id="suhu">-<small>°C</small></span>
      </div>
      <div class="amb">kipas: ON di atas <?= e((string)$cfg['temp_on']) ?>&deg;C, OFF di bawah <?= e((string)$cfg['temp_off']) ?>&deg;C</div>
    </div>
    <div class="metric">
      <div class="top">
        <span class="label">kelembapan udara</span>
        <span class="value" id="rh">-<small>%</small></span>
      </div>
      <div class="amb">rentang ideal: 60% &ndash; 80% (kondisi optimal)</div>
    </div>
    <div class="metric">
      <div class="top">
        <span class="label">kelembapan tanah</span>
        <span class="value" id="soil">-<small>%</small></span>
      </div>
      <div class="amb">pompa: ON di bawah <?= e((string)$cfg['soil_on']) ?>%, OFF di atas <?= e((string)$cfg['soil_off']) ?>%</div>
    </div>
  </div>
</section>

<div class="pair">
  <div class="panel">
    <h2>aktuator</h2>
    <div class="tiles">
      <div class="tile" id="act-row-pompa">
        <div class="act-icon act-pompa">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg>
        </div>
        <div class="act-body">
          <div class="act-name">Pompa Air</div>
          <div class="act-last" id="pompa-last">-</div>
        </div>
        <span class="chip" id="pompa-state">-</span>
        <span class="big" id="pompa-big" style="display:none">-</span>
      </div>
      <div class="tile" id="act-row-pupuk">
        <div class="act-icon act-pupuk">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"/><path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/></svg>
        </div>
        <div class="act-body">
          <div class="act-name">Pompa Pupuk</div>
          <div class="act-last" id="pupuk-last">-</div>
        </div>
        <span class="chip" id="pupuk-state">-</span>
        <span class="big" id="pupuk-big" style="display:none">-</span>
      </div>
      <div class="tile" id="act-row-kipas">
        <div class="act-icon act-kipas">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 12c0-3 2.5-5 5-5 0 2.5-2 5-5 5Z"/><path d="M12 12c3 0 5 2.5 5 5-2.5 0-5-2-5-5Z"/><path d="M12 12c0 3-2.5 5-5 5 0-2.5 2-5 5-5Z"/><path d="M12 12c-3 0-5-2.5-5-5 2.5 0 5 2 5 5Z"/></svg>
        </div>
        <div class="act-body">
          <div class="act-name">Kipas Exhaust</div>
          <div class="act-last" id="kipas-last">-</div>
        </div>
        <span class="chip" id="kipas-state">-</span>
        <span class="big" id="kipas-big" style="display:none">-</span>
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
          <div class="spark-wrap">
            <svg class="spark" id="sp-soil" viewBox="0 0 600 74" preserveAspectRatio="none" aria-hidden="true">
            <defs>
              <linearGradient id="grad-soil" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="#2563EB" stop-opacity="0.22"/>
                <stop offset="60%" stop-color="#2563EB" stop-opacity="0.06"/>
                <stop offset="100%" stop-color="#2563EB" stop-opacity="0.0"/>
              </linearGradient>
            </defs>
            <g stroke="rgba(200, 212, 198, .75)" stroke-width="1" stroke-dasharray="4,4" vector-effect="non-scaling-stroke">
              <line x1="0" y1="10" x2="600" y2="10"/><line x1="0" y1="37" x2="600" y2="37"/><line x1="0" y1="64" x2="600" y2="64"/>
            </g>
            <path id="fill-soil" fill="url(#grad-soil)" d=""/>
            <path id="line-soil" fill="none" stroke="#2563EB" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke" d=""/>
            </svg>
            <div class="chart-pin pin-soil" id="pin-soil" aria-hidden="true"></div>
          </div>
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
          <div class="spark-wrap">
            <svg class="spark" id="sp-suhu" viewBox="0 0 600 74" preserveAspectRatio="none" aria-hidden="true">
            <defs>
              <linearGradient id="grad-suhu" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="#EA580C" stop-opacity="0.22"/>
                <stop offset="60%" stop-color="#EA580C" stop-opacity="0.06"/>
                <stop offset="100%" stop-color="#EA580C" stop-opacity="0.0"/>
              </linearGradient>
            </defs>
            <g stroke="rgba(200, 212, 198, .75)" stroke-width="1" stroke-dasharray="4,4" vector-effect="non-scaling-stroke">
              <line x1="0" y1="10" x2="600" y2="10"/><line x1="0" y1="37" x2="600" y2="37"/><line x1="0" y1="64" x2="600" y2="64"/>
            </g>
            <path id="fill-suhu" fill="url(#grad-suhu)" d=""/>
            <path id="line-suhu" fill="none" stroke="#EA580C" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke" d=""/>
            </svg>
            <div class="chart-pin pin-suhu" id="pin-suhu" aria-hidden="true"></div>
          </div>
          <div class="axis"><span id="x0-suhu">-</span><span id="x1-suhu">-</span><span>sekarang</span></div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="rgb">
  <h2 style="margin-top:14px">warna daun</h2>
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

</main>

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
    if (!el) return;
    const v = Math.max(0, Math.min(100, pct));
    el.style.left = v + '%';
  }

  // area chart: monotone cubic spline + gradient fill + live circular pin
  function spark(lineId, fillId, pinId, arr) {
    const line = $(lineId), fill = $(fillId), pin = $(pinId);
    if (!line || !fill) return;
    if (!arr.length) {
      line.setAttribute('d', '');
      fill.setAttribute('d', '');
      if (pin) pin.style.display = 'none';
      return;
    }
    const min = Math.min(...arr), max = Math.max(...arr);
    const span = (max - min) || 1;
    const n = arr.length;
    // Inset horizontal 10px..590px (tidak menabrak border), vertikal 10px..64px (presisi garis grid)
    const pts = arr.map((v, i) => {
      const x = n === 1 ? 300 : 10 + (i / (n - 1)) * 580;
      const y = 64 - ((v - min) / span) * 54;
      return { x, y };
    });

    if (n === 1) {
      const d = `M 10,${pts[0].y.toFixed(1)} L 590,${pts[0].y.toFixed(1)}`;
      line.setAttribute('d', d);
      fill.setAttribute('d', d + ` L 590,74 L 10,74 Z`);
    } else {
      // Monotone Cubic Hermite Spline: mencegah overshoot di puncak/lembah
      const m = new Array(n).fill(0);
      for (let i = 0; i < n; i++) {
        if (i === 0) {
          m[i] = (pts[1].y - pts[0].y) / (pts[1].x - pts[0].x || 1);
        } else if (i === n - 1) {
          m[i] = (pts[n - 1].y - pts[n - 2].y) / (pts[n - 1].x - pts[n - 2].x || 1);
        } else {
          const d1 = pts[i].y - pts[i - 1].y;
          const d2 = pts[i + 1].y - pts[i].y;
          if (d1 * d2 <= 0) {
            m[i] = 0; // Puncak atau lembah lokal: horizontal mulus tanpa gelembung
          } else {
            m[i] = (d1 / (pts[i].x - pts[i - 1].x || 1) + d2 / (pts[i + 1].x - pts[i].x || 1)) / 2;
          }
        }
      }

      let d = `M ${pts[0].x.toFixed(1)},${pts[0].y.toFixed(1)}`;
      for (let i = 0; i < n - 1; i++) {
        const p0 = pts[i];
        const p1 = pts[i + 1];
        const dx = (p1.x - p0.x) / 3;
        const cp1x = p0.x + dx;
        const cp1y = p0.y + m[i] * dx;
        const cp2x = p1.x - dx;
        const cp2y = p1.y - m[i + 1] * dx;
        d += ` C ${cp1x.toFixed(1)},${cp1y.toFixed(1)} ${cp2x.toFixed(1)},${cp2y.toFixed(1)} ${p1.x.toFixed(1)},${p1.y.toFixed(1)}`;
      }
      line.setAttribute('d', d);
      fill.setAttribute('d', d + ` L ${pts[n - 1].x.toFixed(1)},74 L ${pts[0].x.toFixed(1)},74 Z`);
    }

    if (pin) {
      const last = pts[pts.length - 1];
      pin.style.left = (last.x / 600 * 100).toFixed(2) + '%';
      pin.style.top = (last.y / 74 * 100).toFixed(2) + '%';
      pin.style.display = 'block';
    }
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

    // metrik nilai
    setVal('suhu', L.air_temperature.toFixed(1), '°C');
    setVal('rh', String(Math.round(L.air_humidity)), '%');
    setVal('soil', String(Math.round(L.soil_moisture)), '%');

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
      '<td class="st"><span class="' + (a.status === 'ON' ? 'st-on' : 'st-off') + '">' + a.status + '</span></td></tr>'
    ).join('');
    $('history-act').innerHTML = rows || '<tr><td class="t">-</td><td>belum ada event</td><td class="st"></td></tr>';

    // sparkline (area halus dengan live circular pin)
    const hist = d.history || [];
    const soilArr = hist.map(h => +h.soil_moisture);
    const suhuArr = hist.map(h => +h.air_temperature);
    spark('line-soil', 'fill-soil', 'pin-soil', soilArr);
    spark('line-suhu', 'fill-suhu', 'pin-suhu', suhuArr);
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
