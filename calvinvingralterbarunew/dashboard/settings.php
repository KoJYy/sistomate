<?php
// settings.php - pengaturan ambang histeresis (configs) & kelola perangkat (CRUD devices; wajib login).
// Satu baris configs untuk setiap devices; divalidasi server-side agar histeresis valid.
require_once __DIR__ . '/db.php';
require_login();

$user = current_user();
if (empty($_SESSION['settings_csrf'])) {
    $_SESSION['settings_csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['settings_csrf'];

$devices = db()->query(
    'SELECT d.id_device, d.device_name, d.location, d.id_user, u.name AS owner_name
     FROM devices d
     LEFT JOIN users u ON u.id_user = d.id_user
     ORDER BY d.id_device'
)->fetchAll();
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

$defaults = ['soil_on' => 40, 'soil_off' => 60, 'temp_on' => 27, 'temp_off' => 25, 'fert_duration_s' => 7];
$cfg = $defaults;
if ($deviceId > 0) {
    $stmt = db()->prepare('SELECT * FROM configs WHERE id_device = ?');
    $stmt->execute([$deviceId]);
    $row = $stmt->fetch();
    if ($row) {
        $cfg = $row + $cfg;
    }
}

function is_duplicate_pdo(PDOException $e): bool
{
    return $e->getCode() === '23000' && isset($e->errorInfo[1]) && (int) $e->errorInfo[1] === 1062;
}

$error = '';
$newName = '';
$newLocation = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token  = (string) ($_POST['csrf'] ?? '');
    $action = (string) ($_POST['action'] ?? 'save_config');

    if (!hash_equals($csrf, $token)) {
        $error = 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.';
    } elseif ($action === 'save_config') {
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
            // Pakai VALUES(kolom) agar portabel MySQL 8 + MariaDB 10.11.
            $stmt = db()->prepare(
                'INSERT INTO configs (id_device, soil_on, soil_off, temp_on, temp_off, fert_duration_s)
                 VALUES (?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                   soil_on = VALUES(soil_on),
                   soil_off = VALUES(soil_off),
                   temp_on = VALUES(temp_on),
                   temp_off = VALUES(temp_off),
                   fert_duration_s = VALUES(fert_duration_s)'
            );
            $stmt->execute([$deviceId, $soilOn, $soilOff, $tempOn, $tempOff, $fert]);
            header('Location: settings.php?saved=1&id_device=' . $deviceId);
            exit;
        }
        // simpan nilai form terkirim untuk ditampilkan lagi
        $cfg = ['soil_on' => $soilOn, 'soil_off' => $soilOff, 'temp_on' => $tempOn,
                'temp_off' => $tempOff, 'fert_duration_s' => $fert];
    } elseif ($action === 'device_create') {
        $newName = trim((string) ($_POST['device_name'] ?? ''));
        $newLocation = trim((string) ($_POST['location'] ?? ''));

        if ($newName === '' || mb_strlen($newName) > 100) {
            $error = 'Nama perangkat wajib diisi (maksimal 100 karakter).';
        } elseif ($newLocation === '' || mb_strlen($newLocation) > 150) {
            $error = 'Lokasi perangkat wajib diisi (maksimal 150 karakter).';
        } else {
            try {
                $ins = db()->prepare('INSERT INTO devices (device_name, location, id_user) VALUES (?, ?, ?)');
                $ins->execute([$newName, $newLocation, (int) $user['id_user']]);
                $newId = (int) db()->lastInsertId();
                db()->prepare('INSERT IGNORE INTO configs (id_device) VALUES (?)')->execute([$newId]);
                header('Location: settings.php?msg=dev_created&id_device=' . $newId . '#kelola-perangkat');
                exit;
            } catch (PDOException $e) {
                $error = is_duplicate_pdo($e)
                    ? 'Nama perangkat sudah digunakan. Pilih nama lain.'
                    : 'Gagal menambahkan perangkat ke database.';
            }
        }
    } elseif ($action === 'device_update') {
        $editId   = (int) ($_POST['id_device'] ?? 0);
        $editName = trim((string) ($_POST['device_name'] ?? ''));
        $editLoc  = trim((string) ($_POST['location'] ?? ''));

        if (!in_array($editId, $deviceIds, true)) {
            $error = 'Perangkat yang dipilih tidak valid.';
        } elseif ($editName === '' || mb_strlen($editName) > 100) {
            $error = 'Nama perangkat wajib diisi (maksimal 100 karakter).';
        } elseif ($editLoc === '' || mb_strlen($editLoc) > 150) {
            $error = 'Lokasi perangkat wajib diisi (maksimal 150 karakter).';
        } else {
            try {
                $upd = db()->prepare('UPDATE devices SET device_name = ?, location = ? WHERE id_device = ?');
                $upd->execute([$editName, $editLoc, $editId]);
                header('Location: settings.php?msg=dev_updated&id_device=' . $editId . '#kelola-perangkat');
                exit;
            } catch (PDOException $e) {
                $error = is_duplicate_pdo($e)
                    ? 'Nama perangkat sudah digunakan. Pilih nama lain.'
                    : 'Gagal memperbarui data perangkat (periksa hak akses UPDATE).';
            }
        }
    } elseif ($action === 'device_delete') {
        $delId = (int) ($_POST['id_device'] ?? 0);
        if (!in_array($delId, $deviceIds, true)) {
            $error = 'Perangkat yang dipilih tidak valid.';
        } elseif (count($devices) <= 1) {
            $error = 'Minimal harus tersisa satu perangkat terdaftar.';
        } else {
            try {
                db()->prepare('DELETE FROM devices WHERE id_device = ?')->execute([$delId]);
                header('Location: settings.php?msg=dev_deleted#kelola-perangkat');
                exit;
            } catch (PDOException $e) {
                $error = 'Gagal menghapus perangkat dari database (periksa hak akses DELETE).';
            }
        }
    }
}

$msgMap = [
    'dev_created' => 'Perangkat baru berhasil ditambahkan beserta konfigurasi bawaannya.',
    'dev_updated' => 'Data perangkat berhasil diperbarui.',
    'dev_deleted' => 'Perangkat dan seluruh log terkait berhasil dihapus.',
];
$saved = isset($_GET['saved']);
$msgKey = (string) ($_GET['msg'] ?? '');
$okMessage = $saved ? 'Konfigurasi tersimpan.' : ($msgMap[$msgKey] ?? '');
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
<body class="has-sidebar">
<?= navbar('settings', $user['name']) ?>

<main class="main-content">
  <div class="settings-wrap">
  <h1 class="page-title">Pengaturan Ambang Histeresis</h1>
  <p class="lead-muted">Nilai disimpan ke tabel <code>configs</code> per perangkat dan menjadi
    acuan logika kendali firmware (pompa air &amp; kipas exhaust).</p>

  <?php if ($okMessage !== ''): ?>
    <div class="alert ok" role="alert"><?= e($okMessage) ?></div>
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
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <input type="hidden" name="action" value="save_config">
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

  <section class="panel" id="kelola-perangkat" aria-labelledby="devices-title">
    <div class="table-head">
      <div class="table-title-group">
        <h2 id="devices-title">Kelola Perangkat</h2>
        <span class="small muted"><?= count($devices) ?> perangkat terdaftar</span>
      </div>
    </div>
    <p class="small muted" style="margin-bottom:12px">CRUD tabel <code>devices</code>: menambah perangkat otomatis membuat baris <code>configs</code> bawaan; menghapus perangkat ikut menghapus <code>configs</code> dan log terkait (<code>ON DELETE CASCADE</code>).</p>

    <form method="post" action="settings.php#kelola-perangkat" class="row-inline">
      <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
      <input type="hidden" name="action" value="device_create">
      <div>
        <label class="f" for="new_device_name">Nama perangkat</label>
        <input type="text" id="new_device_name" name="device_name" required maxlength="100"
               placeholder="Contoh: SISTOMATE-02" value="<?= e($newName) ?>">
      </div>
      <div>
        <label class="f" for="new_location">Lokasi</label>
        <input type="text" id="new_location" name="location" required maxlength="150"
               placeholder="Contoh: Bed-3-Baris-1" value="<?= e($newLocation) ?>">
      </div>
      <div>
        <button type="submit" class="btn">Tambah</button>
      </div>
    </form>

    <div class="table-wrapper" tabindex="0" role="region" aria-labelledby="devices-title" style="margin-top:16px">
      <table class="tbl">
        <thead>
          <tr>
            <th scope="col">ID</th>
            <th scope="col">Nama Perangkat</th>
            <th scope="col">Lokasi</th>
            <th scope="col">Pemilik</th>
            <th scope="col">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$devices): ?>
            <tr><td colspan="5" class="center muted" style="padding:18px 10px">Belum ada perangkat terdaftar.</td></tr>
          <?php endif; ?>
          <?php foreach ($devices as $d): ?>
            <tr>
              <td><?= (int) $d['id_device'] ?></td>
              <td><b><?= e($d['device_name']) ?></b></td>
              <td><?= e($d['location']) ?></td>
              <td><?= e($d['owner_name'] ?: '-') ?></td>
              <td>
                <div class="card-actions">
                  <button type="button" class="btn-edit"
                          data-dev-edit="<?= (int) $d['id_device'] ?>"
                          data-dev-name="<?= e($d['device_name']) ?>"
                          data-dev-loc="<?= e($d['location']) ?>">Edit</button>
                  <button type="button" class="btn-del"
                          data-dev-del="<?= (int) $d['id_device'] ?>"
                          data-dev-name="<?= e($d['device_name']) ?>"
                          <?= count($devices) <= 1 ? 'disabled title="Minimal harus tersisa satu perangkat"' : '' ?>>Hapus</button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

  <p class="small muted center mt-block">Perubahan berlaku untuk tampilan dashboard;
    firmware membaca nilai yang sama saat integrasi configs selesai.</p>
  </div>
</main>

<dialog id="editDeviceDialog" class="gallery-dialog confirm" closedby="any" aria-labelledby="editDevTitle">
  <div class="dialog-head">
    <h3 id="editDevTitle">Edit Perangkat</h3>
    <button type="button" class="dialog-close" data-close-dialog aria-label="Tutup">&times;</button>
  </div>
  <form method="post" action="settings.php#kelola-perangkat" class="dialog-body">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <input type="hidden" name="action" value="device_update">
    <input type="hidden" name="id_device" id="editDevId" value="">

    <div class="field">
      <label class="f" for="editDevName" style="margin-top:0">Nama perangkat</label>
      <input type="text" id="editDevName" name="device_name" required maxlength="100">
    </div>

    <div class="field">
      <label class="f" for="editDevLoc" style="margin-top:0">Lokasi</label>
      <input type="text" id="editDevLoc" name="location" required maxlength="150">
    </div>

    <div class="dialog-actions">
      <button type="button" class="btn ghost" data-close-dialog>Batal</button>
      <button type="submit" class="btn">Simpan Perubahan</button>
    </div>
  </form>
</dialog>

<dialog id="deleteDeviceDialog" class="gallery-dialog confirm" closedby="any" aria-labelledby="delDevTitle">
  <div class="dialog-head">
    <h3 id="delDevTitle">Hapus Perangkat</h3>
    <button type="button" class="dialog-close" data-close-dialog aria-label="Tutup">&times;</button>
  </div>
  <div class="dialog-body">
    <p>Hapus perangkat <b id="delDevName"></b>?</p>
    <p class="small muted" style="margin-top:8px">Menghapus perangkat juga menghapus konfigurasi histeresis (<code>configs</code>), <code>sensor_logs</code>, <code>actuator_logs</code>, dan foto <code>gallery</code> yang terhubung lewat <code>ON DELETE CASCADE</code>.</p>
    <form method="post" action="settings.php#kelola-perangkat" class="dialog-actions">
      <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
      <input type="hidden" name="action" value="device_delete">
      <input type="hidden" name="id_device" id="delDevId" value="">
      <button type="button" class="btn ghost" data-close-dialog>Batal</button>
      <button type="submit" class="btn danger">Ya, Hapus</button>
    </form>
  </div>
</dialog>

<script>
(() => {
  const editDevDialog = document.getElementById('editDeviceDialog');
  const editDevId = document.getElementById('editDevId');
  const editDevName = document.getElementById('editDevName');
  const editDevLoc = document.getElementById('editDevLoc');

  document.querySelectorAll('[data-dev-edit]').forEach((btn) => {
    btn.addEventListener('click', () => {
      editDevId.value = btn.dataset.devEdit || '';
      editDevName.value = btn.dataset.devName || '';
      editDevLoc.value = btn.dataset.devLoc || '';
      editDevDialog.showModal();
    });
  });

  const delDevDialog = document.getElementById('deleteDeviceDialog');
  const delDevId = document.getElementById('delDevId');
  const delDevName = document.getElementById('delDevName');

  document.querySelectorAll('[data-dev-del]').forEach((btn) => {
    btn.addEventListener('click', () => {
      delDevId.value = btn.dataset.devDel || '';
      delDevName.textContent = btn.dataset.devName || '';
      delDevDialog.showModal();
    });
  });

  document.querySelectorAll('[data-close-dialog]').forEach((btn) => {
    btn.addEventListener('click', () => {
      btn.closest('dialog')?.close();
    });
  });

  if (!('closedBy' in HTMLDialogElement.prototype)) {
    document.querySelectorAll('dialog.gallery-dialog').forEach((dialog) => {
      dialog.addEventListener('click', (event) => {
        if (event.target !== dialog) return;
        const rect = dialog.getBoundingClientRect();
        const isDialogContent = (
          rect.top <= event.clientY &&
          event.clientY <= rect.top + rect.height &&
          rect.left <= event.clientX &&
          event.clientX <= rect.left + rect.width
        );
        if (isDialogContent) return;
        dialog.close();
      });
    });
  }
})();
</script>
</body>
</html>
