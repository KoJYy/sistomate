<?php
// demo-data.php — data simulasi untuk server demo lokal.
// Berkas ini TIDAK ikut dikirim ke server produksi; hanya untuk menjalankan
// tampilan tanpa MySQL. Semua angka dihitung dari waktu, jadi terasa hidup.

date_default_timezone_set('Asia/Jakarta');

function demo_state_path(): string
{
    return __DIR__ . '/demo-state.json';
}

function demo_cfg_default(): array
{
    return ['id' => 1, 'soil_on' => 40, 'soil_off' => 55, 'temp_on' => 32, 'temp_off' => 31, 'fert_duration_s' => 7];
}

function demo_cfg(): array
{
    $p = demo_state_path();
    if (!is_file($p)) {
        $c = demo_cfg_default();
        file_put_contents($p, json_encode($c));
        return $c;
    }
    $j = json_decode((string) file_get_contents($p), true);
    return is_array($j) ? array_merge(demo_cfg_default(), $j) : demo_cfg_default();
}

function demo_cfg_save(array $patch): array
{
    $new = array_merge(demo_cfg(), $patch);
    file_put_contents(demo_state_path(), json_encode($new));
    return $new;
}

function demo_device(): array
{
    return ['id_device' => 1, 'device_name' => 'SISTOMATE-01', 'location' => 'Greenhouse Polije'];
}

function demo_user(): array
{
    return [
        'id_user' => 1,
        'name' => 'Calvin Aprilian',
        'email' => 'demo@sistomate.local',
        'password_hash' => '$2y$12$gLyWwwiO40mKqMhNxet6rO0pGT4v8hfsB6IoxqLWog0t1fp6vbdFS',
    ];
}

// Satu bacaan sensor pada detik $ts (gelombang halus menyeberangi pita histeresis + riak mikro).
function demo_reading(int $ts, array $cfg): array
{
    $temp = 31.2 + sin($ts / 450) * 2.1 + sin($ts / 71) * 0.22 + sin($ts / 19) * 0.14;
    $hum  = 68.0 + cos($ts / 900) * 7.5 + sin($ts / 47) * 0.8 + cos($ts / 29) * 0.3;
    $soil = 47.5 + sin($ts / 600) * 14.5 + sin($ts / 83) * 0.8 + sin($ts / 23) * 0.35;
    $temp = max(10.0, min(50.0, $temp));
    $hum  = max(0.0, min(100.0, $hum));
    $soil = max(0.0, min(100.0, $soil));

    $r = (int) round(168 + sin($ts / 311) * 12);
    $g = (int) round(232 + cos($ts / 277) * 9);
    $b = (int) round(121 + sin($ts / 199) * 7);

    // heuristik sama dengan firmware: kering -> layu -> sakit -> sehat
    $status = 'sehat';
    if ($soil <= $cfg['soil_on']) {
        $status = 'kering';
    } elseif ($temp >= $cfg['temp_on']) {
        $status = 'layu';
    } elseif ($r > 100 && $g * 4 < $r * 3) {
        $status = 'sakit';
    }

    return [
        'recorded_at' => date('Y-m-d H:i:s', $ts),
        'air_temperature' => round($temp, 1),
        'air_humidity' => round($hum, 1),
        'soil_moisture' => round($soil, 1),
        'rgb_red' => max(0, $r),
        'rgb_green' => max(0, $g),
        'rgb_blue' => max(0, $b),
        'plant_status' => $status,
        'device_name' => 'SISTOMATE-01',
        'id_device' => 1,
    ];
}

// Evaluasi transisi histeresis pompa air & kipas exhaust pada satu titik waktu.
function demo_step_hysteresis(array &$state, array $r, array $cfg): void
{
    $soil = (float) $r['soil_moisture'];
    $temp = (float) $r['air_temperature'];

    if ($soil <= (float) $cfg['soil_on']) {
        $state['Pompa Air'] = 'ON';
    } elseif ($soil >= (float) $cfg['soil_off']) {
        $state['Pompa Air'] = 'OFF';
    }

    if ($temp >= (float) $cfg['temp_on']) {
        $state['Kipas Exhaust'] = 'ON';
    } elseif ($temp <= (float) $cfg['temp_off']) {
        $state['Kipas Exhaust'] = 'OFF';
    }
}

// Rentetan event aktuator berbasis histeresis penuh + jadwal fertigasi sesuai fert_duration_s.
function demo_actuator_events(array $cfg, int $fromTs, int $toTs, int $step = 60): array
{
    if ($fromTs > $toTs) {
        return [];
    }
    $step = max(15, $step);
    $span = $toTs - $fromTs;
    // Jaga jumlah iterasi tetap ringan untuk rentang multi-hari.
    if ($span > 86400) {
        $step = max($step, (int) floor($span / 1440));
    }

    // Pemanasan histeresis 2 jam sebelum $fromTs agar kondisi awal akurat.
    $warmupStart = $fromTs - 7200;
    $hyst = ['Pompa Air' => 'OFF', 'Kipas Exhaust' => 'OFF'];
    for ($ts = $warmupStart; $ts < $fromTs; $ts += $step) {
        demo_step_hysteresis($hyst, demo_reading($ts, $cfg), $cfg);
    }

    $events = [];
    $prev = $hyst;
    $prevTs = $fromTs;
    $lastEvalTs = $fromTs - 1;

    for ($ts = $fromTs; $ts <= $toTs; $ts += $step) {
        $lastEvalTs = $ts;
        $cur = $prev;
        demo_step_hysteresis($cur, demo_reading($ts, $cfg), $cfg);
        foreach ($cur as $name => $st) {
            if ($st !== $prev[$name]) {
                $jit = (($ts + crc32($name)) % 29) - 14;
                $evTs = max($fromTs, min($toTs, max($prevTs, $ts + $jit)));
                $events[] = [
                    'executed_at' => date('Y-m-d H:i:s', $evTs),
                    'actuator_name' => $name,
                    'status' => $st,
                    'device_name' => 'SISTOMATE-01',
                ];
            }
        }
        $prev = $cur;
        $prevTs = $ts;
    }

    // Pastikan tepat di $toTs juga dievaluasi agar status terkini sinkron dengan pembacaan terbaru.
    if ($lastEvalTs < $toTs) {
        $cur = $prev;
        demo_step_hysteresis($cur, demo_reading($toTs, $cfg), $cfg);
        foreach ($cur as $name => $st) {
            if ($st !== $prev[$name]) {
                $events[] = [
                    'executed_at' => date('Y-m-d H:i:s', $toTs),
                    'actuator_name' => $name,
                    'status' => $st,
                    'device_name' => 'SISTOMATE-01',
                ];
            }
        }
    }

    // Jadwal fertigasi deterministik setiap 90 menit (5400 dtk), menyala selama fert_duration_s.
    $fertPeriod = 5400;
    $fertDur = max(1, (int) ($cfg['fert_duration_s'] ?? 7));
    $firstSlot = (int) (floor($fromTs / $fertPeriod) * $fertPeriod);
    for ($slot = $firstSlot; $slot <= $toTs; $slot += $fertPeriod) {
        if ($slot >= $fromTs && $slot <= $toTs) {
            $events[] = [
                'executed_at' => date('Y-m-d H:i:s', $slot),
                'actuator_name' => 'Pompa Pupuk',
                'status' => 'ON',
                'device_name' => 'SISTOMATE-01',
            ];
        }
        $offTs = $slot + $fertDur;
        if ($offTs >= $fromTs && $offTs <= $toTs) {
            $events[] = [
                'executed_at' => date('Y-m-d H:i:s', $offTs),
                'actuator_name' => 'Pompa Pupuk',
                'status' => 'OFF',
                'device_name' => 'SISTOMATE-01',
            ];
        }
    }

    usort($events, static function ($a, $b) {
        $cmp = strcmp($b['executed_at'], $a['executed_at']);
        if ($cmp !== 0) {
            return $cmp;
        }
        // Pada detik sama (mis. transisi), OFF lebih baru jika durasi 0, ON lebih dulu sebelum OFF.
        return strcmp($a['status'], $b['status']);
    });
    return $events;
}

// Status terbaru untuk ketiga aktuator tepat pada detik $now (menjamin ketiganya selalu hadir).
function demo_actuator_latest(array $cfg, int $now): array
{
    $events = demo_actuator_events($cfg, $now - 18 * 3600, $now, 30);
    $latestByName = [];
    foreach ($events as $ev) {
        $n = $ev['actuator_name'];
        if (!isset($latestByName[$n])) {
            $latestByName[$n] = $ev;
        }
        if (count($latestByName) === 3) {
            break;
        }
    }

    // Jika salah satu aktuator belum berubah dalam 18 jam terakhir, hitung statusnya saat ini.
    $hyst = ['Pompa Air' => 'OFF', 'Kipas Exhaust' => 'OFF'];
    for ($ts = $now - 7200; $ts <= $now; $ts += 60) {
        demo_step_hysteresis($hyst, demo_reading($ts, $cfg), $cfg);
    }
    foreach (['Pompa Air', 'Kipas Exhaust'] as $name) {
        if (!isset($latestByName[$name])) {
            $latestByName[$name] = [
                'executed_at' => date('Y-m-d H:i:s', $now - 3600),
                'actuator_name' => $name,
                'status' => $hyst[$name],
                'device_name' => 'SISTOMATE-01',
            ];
        }
    }

    if (!isset($latestByName['Pompa Pupuk'])) {
        $fertPeriod = 5400;
        $fertDur = max(1, (int) ($cfg['fert_duration_s'] ?? 7));
        $lastSlot = (int) (floor($now / $fertPeriod) * $fertPeriod);
        $isOn = ($now - $lastSlot) < $fertDur;
        $latestByName['Pompa Pupuk'] = [
            'executed_at' => date('Y-m-d H:i:s', $isOn ? $lastSlot : ($lastSlot + $fertDur)),
            'actuator_name' => 'Pompa Pupuk',
            'status' => $isOn ? 'ON' : 'OFF',
            'device_name' => 'SISTOMATE-01',
        ];
    }

    $out = array_values($latestByName);
    usort($out, fn($a, $b) => strcmp($b['executed_at'], $a['executed_at']));
    return $out;
}

// Baris sensor historis (mundur dari $toTs hingga $fromTs, skala interval otomatis untuk rentang panjang).
function demo_sensor_rows(array $cfg, int $fromTs, int $toTs, int $step = 300, int $limit = 200): array
{
    if ($fromTs > $toTs) {
        return [];
    }
    $step = max(1, $step);
    $span = $toTs - $fromTs;
    $effectiveStep = max($step, (int) ceil($span / max(1, $limit - 1)));

    $rows = [];
    for ($ts = $toTs; $ts >= $fromTs && count($rows) < $limit; $ts -= $effectiveStep) {
        $rows[] = demo_reading($ts, $cfg);
    }
    return $rows;
}
