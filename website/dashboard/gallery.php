<?php
// gallery.php - galeri foto kondisi tanaman tomat (unggah, filter, lihat, hapus; wajib login).
require_once __DIR__ . '/db.php';
require_login();

$user = current_user();
if (empty($_SESSION['gallery_csrf'])) {
    $_SESSION['gallery_csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['gallery_csrf'];

$devices = db()->query('SELECT id_device, device_name, location FROM devices ORDER BY id_device')->fetchAll();
$deviceIds = array_map(static fn(array $d): int => (int) $d['id_device'], $devices);
$allowedKondisi = ['sehat', 'layu', 'sakit'];
$statusPill = ['sehat' => 'sehat', 'layu' => 'layu', 'sakit' => 'sakit'];

$error = '';
$formDevice = $deviceIds[0] ?? 0;
$formKondisi = '';
$formCaption = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = (string) ($_POST['csrf'] ?? '');
    $action = (string) ($_POST['action'] ?? '');

    if (!hash_equals($csrf, $token)) {
        $error = 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.';
    } elseif ($action === 'upload') {
        $formDevice = (int) ($_POST['id_device'] ?? 0);
        $formKondisi = strtolower(trim((string) ($_POST['kondisi'] ?? '')));
        $formCaption = trim((string) ($_POST['caption'] ?? ''));

        if ($deviceIds === []) {
            $error = 'Belum ada perangkat terdaftar; tambahkan baris di tabel devices.';
        } elseif (!in_array($formDevice, $deviceIds, true)) {
            $error = 'Perangkat yang dipilih tidak valid.';
        } elseif ($formKondisi !== '' && !in_array($formKondisi, $allowedKondisi, true)) {
            $error = 'Kondisi tanaman tidak valid.';
        } elseif ($formCaption === '' || mb_strlen($formCaption) > 220) {
            $error = 'Keterangan kondisi wajib diisi (maksimal 220 karakter).';
        } elseif (!isset($_FILES['photo']) || !is_array($_FILES['photo'])) {
            $error = 'Pilih file foto tanaman terlebih dahulu.';
        } else {
            $file = $_FILES['photo'];
            $errCode = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
            if ($errCode === UPLOAD_ERR_NO_FILE) {
                $error = 'Pilih file foto tanaman terlebih dahulu.';
            } elseif ($errCode === UPLOAD_ERR_INI_SIZE || $errCode === UPLOAD_ERR_FORM_SIZE) {
                $error = 'Ukuran foto melebihi batas unggahan (maksimal 5 MB).';
            } elseif ($errCode !== UPLOAD_ERR_OK) {
                $error = 'Gagal mengunggah file foto (kode error: ' . $errCode . ').';
            } else {
                $tmp = (string) ($file['tmp_name'] ?? '');
                $size = (int) ($file['size'] ?? 0);
                $mimeMap = [
                    'image/jpeg' => '.jpg',
                    'image/png' => '.png',
                    'image/webp' => '.webp',
                ];

                if ($size <= 0 || $size > 5 * 1024 * 1024) {
                    $error = 'Ukuran foto harus antara 1 byte hingga 5 MB.';
                } elseif (!is_uploaded_file($tmp)) {
                    $error = 'Berkas unggahan tidak valid.';
                } else {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = $finfo ? (string) finfo_file($finfo, $tmp) : '';
                    if ($finfo) {
                        finfo_close($finfo);
                    }
                    if (!isset($mimeMap[$mime]) || @getimagesize($tmp) === false) {
                        $error = 'Format foto harus JPG, PNG, atau WebP yang valid.';
                    } else {
                        $uploadDir = __DIR__ . '/uploads/gallery';
                        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
                            $error = 'Direktori penyimpanan foto tidak dapat dibuat.';
                        } else {
                            $filename = date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . $mimeMap[$mime];
                            $dest = $uploadDir . '/' . $filename;
                            if (!move_uploaded_file($tmp, $dest)) {
                                $error = 'Gagal menyimpan berkas foto ke server.';
                            } else {
                                $fullCaption = ($formKondisi !== '' ? '[' . ucfirst($formKondisi) . '] ' : '') . $formCaption;
                                $fullCaption = mb_substr($fullCaption, 0, 255);
                                $relUrl = 'uploads/gallery/' . $filename;
                                try {
                                    $stmt = db()->prepare(
                                        'INSERT INTO gallery (image_url, caption, id_user, id_device) VALUES (?, ?, ?, ?)'
                                    );
                                    $stmt->execute([$relUrl, $fullCaption, (int) $user['id_user'], $formDevice]);
                                    header('Location: gallery.php?uploaded=1');
                                    exit;
                                } catch (PDOException $e) {
                                    @unlink($dest);
                                    $error = 'Gagal menyimpan catatan foto ke database.';
                                }
                            }
                        }
                    }
                }
            }
        }
    } elseif ($action === 'edit') {
        $editId      = (int) ($_POST['id_gallery'] ?? 0);
        $editDevice  = (int) ($_POST['id_device'] ?? 0);
        $editKondisi = strtolower(trim((string) ($_POST['kondisi'] ?? '')));
        $editCaption = trim((string) ($_POST['caption'] ?? ''));

        if ($editId <= 0) {
            $error = 'ID foto tidak valid.';
        } elseif (!in_array($editDevice, $deviceIds, true)) {
            $error = 'Perangkat yang dipilih tidak valid.';
        } elseif ($editKondisi !== '' && !in_array($editKondisi, $allowedKondisi, true)) {
            $error = 'Kondisi tanaman tidak valid.';
        } elseif ($editCaption === '' || mb_strlen($editCaption) > 220) {
            $error = 'Keterangan kondisi wajib diisi (maksimal 220 karakter).';
        } else {
            $chk = db()->prepare('SELECT id_gallery FROM gallery WHERE id_gallery = ?');
            $chk->execute([$editId]);
            if (!$chk->fetch()) {
                $error = 'Foto tidak ditemukan atau sudah dihapus.';
            } else {
                $fullCaption = ($editKondisi !== '' ? '[' . ucfirst($editKondisi) . '] ' : '') . $editCaption;
                $fullCaption = mb_substr($fullCaption, 0, 255);
                try {
                    $upd = db()->prepare('UPDATE gallery SET id_device = ?, caption = ? WHERE id_gallery = ?');
                    $upd->execute([$editDevice, $fullCaption, $editId]);
                    header('Location: gallery.php?updated=1');
                    exit;
                } catch (PDOException $e) {
                    $error = 'Gagal memperbarui foto di database (periksa hak akses UPDATE).';
                }
            }
        }
    } elseif ($action === 'delete') {
        $delId = (int) ($_POST['id_gallery'] ?? 0);
        if ($delId <= 0) {
            $error = 'ID foto tidak valid.';
        } else {
            $stmt = db()->prepare('SELECT id_gallery, image_url FROM gallery WHERE id_gallery = ?');
            $stmt->execute([$delId]);
            $row = $stmt->fetch();
            if (!$row) {
                $error = 'Foto tidak ditemukan atau sudah dihapus.';
            } else {
                try {
                    db()->prepare('DELETE FROM gallery WHERE id_gallery = ?')->execute([$delId]);
                    $rel = resolve_gallery_image((string) $row['image_url']);
                    $uploadRoot = realpath(__DIR__ . '/uploads/gallery');
                    if ($rel !== null && $uploadRoot !== false) {
                        $fullFile = realpath(__DIR__ . '/' . $rel);
                        $uploadPrefix = rtrim($uploadRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
                        if ($fullFile !== false && str_starts_with($fullFile, $uploadPrefix) && is_file($fullFile)) {
                            @unlink($fullFile);
                        }
                    }
                    header('Location: gallery.php?deleted=1');
                    exit;
                } catch (PDOException $e) {
                    $error = 'Gagal menghapus foto dari database (periksa hak akses DELETE).';
                }
            }
        }
    }
}

$filterDevice = (int) ($_GET['id_device'] ?? 0);
if ($filterDevice > 0 && !in_array($filterDevice, $deviceIds, true)) {
    $filterDevice = 0;
}
$filterKondisi = strtolower(trim((string) ($_GET['kondisi'] ?? '')));
if (!in_array($filterKondisi, $allowedKondisi, true)) {
    $filterKondisi = '';
}

$where = [];
$params = [];
if ($filterDevice > 0) {
    $where[] = 'g.id_device = ?';
    $params[] = $filterDevice;
}
if ($filterKondisi !== '') {
    $where[] = 'g.caption LIKE ?';
    $params[] = '[' . $filterKondisi . ']%';
}

$sql = 'SELECT g.id_gallery, g.image_url, g.caption, g.upload_date, g.id_device,
               d.device_name, d.location, u.name AS user_name
        FROM gallery g
        JOIN devices d ON d.id_device = g.id_device
        LEFT JOIN users u ON u.id_user = g.id_user';
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY g.upload_date DESC, g.id_gallery DESC LIMIT 60';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$photos = $stmt->fetchAll();

$uploaded = isset($_GET['uploaded']);
$updated  = isset($_GET['updated']);
$deleted  = isset($_GET['deleted']);
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Galeri Kondisi Tanaman - SISTOMATE</title>
<link rel="icon" type="image/svg+xml" href="../assets/favicon.svg">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Archivo:wght@400;500;600;700&family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/theme.css">
</head>
<body class="has-sidebar">
<?= navbar('gallery', $user['name']) ?>

<main class="main-content">
  <h1 class="page-title">Galeri Kondisi Tanaman</h1>
  <p class="lead-muted">Dokumentasi foto perkembangan daun, buah, dan kondisi bed tanam tomat untuk pelacakan visual per perangkat.</p>

  <?php if ($uploaded): ?>
    <div class="alert ok" role="alert">Foto kondisi tanaman berhasil diunggah.</div>
  <?php endif; ?>
  <?php if ($updated): ?>
    <div class="alert ok" role="alert">Data foto kondisi tanaman berhasil diperbarui.</div>
  <?php endif; ?>
  <?php if ($deleted): ?>
    <div class="alert ok" role="alert">Foto kondisi tanaman berhasil dihapus.</div>
  <?php endif; ?>
  <?php if ($error !== ''): ?>
    <div class="alert err" role="alert"><?= e($error) ?></div>
  <?php endif; ?>

  <section class="panel" aria-labelledby="upload-title">
    <h2 id="upload-title">Unggah Foto Kondisi Terbaru</h2>
    <form id="uploadForm" method="post" action="gallery.php" enctype="multipart/form-data">
      <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
      <input type="hidden" name="action" value="upload">

      <div class="upload-grid">
        <div class="upload-drop">
          <div class="field" style="margin-bottom:0">
            <label class="f" for="photo" style="margin-top:0">Foto tanaman</label>
            <span id="photo-hint" class="field-hint">Format JPG, PNG, atau WebP (maks. 5 MB).</span>
            <input type="file" id="photo" name="photo"
                   accept="image/jpeg,image/png,image/webp" required
                   aria-describedby="photo-hint" data-error="photo-error">
            <div id="photo-error" class="error-msg">
              <span role="img" aria-label="Error">&#10060;</span> Pilih foto JPG, PNG, atau WebP dengan ukuran maksimal 5 MB.
            </div>
            <div class="upload-preview" id="uploadPreview">
              <span>Pratinjau foto akan tampil di sini</span>
            </div>
          </div>
        </div>

        <div class="upload-meta">
          <div class="field">
            <label class="f" for="id_device" style="margin-top:0">Perangkat / lokasi</label>
            <span id="device-hint" class="field-hint">Pilih perangkat ESP32 tempat foto diambil.</span>
            <select id="id_device" name="id_device" required
                    aria-describedby="device-hint" data-error="device-error">
              <option value="">-- Pilih perangkat --</option>
              <?php foreach ($devices as $d): ?>
                <option value="<?= (int) $d['id_device'] ?>"<?= (int) $d['id_device'] === $formDevice ? ' selected' : '' ?>>
                  <?= e($d['device_name']) ?> &mdash; <?= e($d['location']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <div id="device-error" class="error-msg">
              <span role="img" aria-label="Error">&#10060;</span> Pilih perangkat lokasi tanaman terlebih dahulu.
            </div>
          </div>

          <div class="field">
            <label class="f" for="kondisi" style="margin-top:0">Kondisi tanaman (opsional)</label>
            <span id="kondisi-hint" class="field-hint">Memberi label status kondisi pada kartu foto.</span>
            <select id="kondisi" name="kondisi" aria-describedby="kondisi-hint">
              <option value=""<?= $formKondisi === '' ? ' selected' : '' ?>>Catatan umum (tanpa tag)</option>
              <option value="sehat"<?= $formKondisi === 'sehat' ? ' selected' : '' ?>>Sehat</option>
              <option value="layu"<?= $formKondisi === 'layu' ? ' selected' : '' ?>>Layu</option>
              <option value="sakit"<?= $formKondisi === 'sakit' ? ' selected' : '' ?>>Sakit</option>
            </select>
          </div>

          <div class="field">
            <label class="f" for="caption" style="margin-top:0">Keterangan kondisi</label>
            <span id="caption-hint" class="field-hint">Maksimal 220 karakter, jelaskan kondisi daun, tanah, atau buah.</span>
            <input type="text" id="caption" name="caption" required maxlength="220"
                   placeholder="Contoh: Daun segar hijau merata setelah penyiraman pagi"
                   value="<?= e($formCaption) ?>"
                   aria-describedby="caption-hint" data-error="caption-error">
            <div id="caption-error" class="error-msg">
              <span role="img" aria-label="Error">&#10060;</span> Isi keterangan kondisi tanaman (1 sampai 220 karakter).
            </div>
          </div>

          <div>
            <button type="submit" class="btn">Unggah Foto</button>
          </div>
        </div>
      </div>
    </form>
  </section>

  <section class="panel" aria-labelledby="filter-title">
    <div class="filter-head">
      <h2 id="filter-title">Daftar Dokumentasi Foto</h2>
      <span class="small muted"><?= count($photos) ?> foto ditampilkan</span>
    </div>
    <form method="get" action="gallery.php" class="row-inline">
      <div>
        <label class="f" for="filter_device">Perangkat</label>
        <select id="filter_device" name="id_device">
          <option value="0">Semua perangkat</option>
          <?php foreach ($devices as $d): ?>
            <option value="<?= (int) $d['id_device'] ?>"<?= (int) $d['id_device'] === $filterDevice ? ' selected' : '' ?>>
              <?= e($d['device_name']) ?> &mdash; <?= e($d['location']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="f" for="filter_kondisi">Kondisi</label>
        <select id="filter_kondisi" name="kondisi">
          <option value="">Semua kondisi</option>
          <option value="sehat"<?= $filterKondisi === 'sehat' ? ' selected' : '' ?>>Sehat</option>
          <option value="layu"<?= $filterKondisi === 'layu' ? ' selected' : '' ?>>Layu</option>
          <option value="sakit"<?= $filterKondisi === 'sakit' ? ' selected' : '' ?>>Sakit</option>
        </select>
      </div>
      <div>
        <button type="submit" class="btn">Terapkan Filter</button>
      </div>
    </form>
  </section>

  <?php if (!$photos): ?>
    <div class="panel center muted" style="padding:36px 20px">
      Belum ada foto kondisi tanaman pada filter ini. Unggah foto pertama melalui formulir di atas.
    </div>
  <?php else: ?>
    <section class="gallery-grid" aria-label="Daftar foto kondisi tanaman">
      <?php $imgIdx = 0; ?>
      <?php foreach ($photos as $p): ?>
        <?php
          $parsed = parse_gallery_caption($p['caption']);
          $status = $parsed['status'];
          $captionText = $parsed['text'] !== '' ? $parsed['text'] : 'Tanpa keterangan';
          $imgRel = resolve_gallery_image((string) $p['image_url']);
          $ts = strtotime((string) $p['upload_date']);
          $dateLabel = $ts ? date('d M Y, H:i', $ts) : (string) $p['upload_date'];
        ?>
        <article class="gallery-card">
          <?php if ($imgRel !== null): ?>
            <?php
              $priorityAttr = '';
              if ($imgIdx === 0) {
                  $priorityAttr = ' fetchpriority="high"';
              } elseif ($imgIdx >= 3) {
                  $priorityAttr = ' loading="lazy"';
              }
              $imgIdx++;
            ?>
            <button type="button" class="gallery-thumb-btn"
                    data-lightbox="1"
                    data-src="<?= e($imgRel) ?>"
                    data-caption="<?= e($captionText) ?>"
                    data-meta="<?= e($p['device_name'] . ' · ' . $dateLabel) ?>"
                    aria-label="Perbesar foto: <?= e($captionText) ?>">
              <img class="gallery-thumb"
                   src="<?= e($imgRel) ?>"
                   alt="<?= e($captionText) ?>"
                   width="400" height="300"<?= $priorityAttr ?>>
            </button>
          <?php else: ?>
            <div class="gallery-placeholder">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/>
                <circle cx="12" cy="13" r="3"/>
              </svg>
              <span>Berkas gambar belum tersedia</span>
            </div>
          <?php endif; ?>

          <div class="gallery-body">
            <div class="gallery-meta">
              <?php if ($status !== '' && isset($statusPill[$status])): ?>
                <span class="pill <?= e($statusPill[$status]) ?>"><?= e($status) ?></span>
              <?php else: ?>
                <span class="pill off">umum</span>
              <?php endif; ?>
              <span class="gallery-date"><?= e($dateLabel) ?></span>
            </div>

            <p class="gallery-caption"><?= e($captionText) ?></p>

            <div class="gallery-foot">
              <span class="gallery-source" title="<?= e($p['device_name'] . ($p['user_name'] ? ' · ' . $p['user_name'] : '')) ?>">
                <?= e($p['device_name']) ?><?= $p['user_name'] ? ' &middot; ' . e($p['user_name']) : '' ?>
              </span>
              <div class="card-actions">
                <button type="button" class="btn-edit"
                        data-edit-id="<?= (int) $p['id_gallery'] ?>"
                        data-edit-device="<?= (int) $p['id_device'] ?>"
                        data-edit-kondisi="<?= e($status) ?>"
                        data-edit-caption="<?= e($parsed['text']) ?>">Edit</button>
                <button type="button" class="btn-del"
                        data-delete-id="<?= (int) $p['id_gallery'] ?>"
                        data-delete-label="<?= e($captionText) ?>">Hapus</button>
              </div>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </section>
  <?php endif; ?>
</main>

<dialog id="lightboxDialog" class="gallery-dialog" closedby="any" aria-labelledby="lightboxTitle">
  <div class="dialog-head">
    <h3 id="lightboxTitle">Pratinjau Foto Kondisi</h3>
    <button type="button" class="dialog-close" data-close-dialog aria-label="Tutup">&times;</button>
  </div>
  <div class="dialog-body">
    <img id="lightboxImg" src="" alt="">
    <p id="lightboxCaption" style="font-size:16px;margin-bottom:4px"></p>
    <p id="lightboxMeta" class="small muted"></p>
    <div class="dialog-actions">
      <button type="button" class="btn ghost" data-close-dialog>Tutup</button>
    </div>
  </div>
</dialog>

<dialog id="editDialog" class="gallery-dialog confirm" closedby="any" aria-labelledby="editTitle">
  <div class="dialog-head">
    <h3 id="editTitle">Edit Keterangan Foto</h3>
    <button type="button" class="dialog-close" data-close-dialog aria-label="Tutup">&times;</button>
  </div>
  <form method="post" action="gallery.php" class="dialog-body">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <input type="hidden" name="action" value="edit">
    <input type="hidden" name="id_gallery" id="editIdInput" value="">

    <div class="field">
      <label class="f" for="editDevice" style="margin-top:0">Perangkat / lokasi</label>
      <select id="editDevice" name="id_device" required>
        <?php foreach ($devices as $d): ?>
          <option value="<?= (int) $d['id_device'] ?>">
            <?= e($d['device_name']) ?> &mdash; <?= e($d['location']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="field">
      <label class="f" for="editKondisi" style="margin-top:0">Kondisi tanaman (opsional)</label>
      <select id="editKondisi" name="kondisi">
        <option value="">Catatan umum (tanpa tag)</option>
        <option value="sehat">Sehat</option>
        <option value="layu">Layu</option>
        <option value="sakit">Sakit</option>
      </select>
    </div>

    <div class="field">
      <label class="f" for="editCaption" style="margin-top:0">Keterangan kondisi</label>
      <input type="text" id="editCaption" name="caption" required maxlength="220">
    </div>

    <div class="dialog-actions">
      <button type="button" class="btn ghost" data-close-dialog>Batal</button>
      <button type="submit" class="btn">Simpan Perubahan</button>
    </div>
  </form>
</dialog>

<dialog id="deleteDialog" class="gallery-dialog confirm" closedby="any" aria-labelledby="deleteTitle">
  <div class="dialog-head">
    <h3 id="deleteTitle">Hapus Foto Galeri</h3>
    <button type="button" class="dialog-close" data-close-dialog aria-label="Tutup">&times;</button>
  </div>
  <div class="dialog-body">
    <p>Hapus dokumentasi foto ini dari galeri?</p>
    <p id="deletePreview" class="small muted" style="margin-top:8px"></p>
    <form method="post" action="gallery.php" class="dialog-actions">
      <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="id_gallery" id="deleteIdInput" value="">
      <button type="button" class="btn ghost" data-close-dialog>Batal</button>
      <button type="submit" class="btn danger">Ya, Hapus</button>
    </form>
  </div>
</dialog>

<script>
(() => {
  const MAX_BYTES = 5 * 1024 * 1024;
  const ALLOWED_TYPES = new Set(['image/jpeg', 'image/png', 'image/webp']);

  const uploadForm = document.getElementById('uploadForm');
  const photoInput = document.getElementById('photo');
  const previewBox = document.getElementById('uploadPreview');
  let previewObjectUrl = null;

  // Sinkronisasi aria-invalid & pengumuman live region saat validasi setelah interaksi
  const linkError = (el, shown) => {
    const errorId = el.dataset.error;
    if (!errorId) return;
    const ids = (el.getAttribute('aria-describedby') || '')
      .split(/\s+/)
      .filter((id) => id && id !== errorId);
    if (shown) ids.push(errorId);
    if (ids.length) el.setAttribute('aria-describedby', ids.join(' '));
    else el.removeAttribute('aria-describedby');
  };

  const announcer = document.createElement('div');
  announcer.setAttribute('aria-live', 'polite');
  announcer.classList.add('visually-hidden');
  document.body.append(announcer);
  let announceTimer;
  let announcedField;

  const spokenText = (node) => {
    const copy = node.cloneNode(true);
    copy.querySelectorAll('[aria-hidden="true"]').forEach((n) => n.remove());
    copy.querySelectorAll('[role="img"][aria-label]').forEach((n) => {
      n.replaceWith(`${n.getAttribute('aria-label')}: `);
    });
    return copy.textContent.replace(/\s+/g, ' ').trim();
  };

  const announceError = (el) => {
    const errNode = document.getElementById(el.dataset.error);
    if (!errNode) return;
    const label = el.labels?.[0] ? `${spokenText(el.labels[0])}: ` : '';
    clearTimeout(announceTimer);
    announcer.textContent = '';
    announcedField = el;
    announceTimer = setTimeout(() => {
      announcer.textContent = label + spokenText(errNode);
    }, 500);
  };

  const flagged = new WeakSet();
  const announced = new WeakSet();
  const supportsUserInvalid = CSS.supports('selector(:user-invalid)');

  const syncAria = (el, announce = false, forceInvalid = false) => {
    if (!el.matches?.('input, select, textarea')) return;
    const invalid = supportsUserInvalid
      ? el.matches(':user-invalid')
      : (forceInvalid || el.classList.contains('user-invalid-fallback')) && !el.validity.valid;

    if (invalid) {
      if (announce && !announced.has(el)) {
        announceError(el);
        announced.add(el);
      }
      el.setAttribute('aria-invalid', 'true');
      linkError(el, true);
      flagged.add(el);
    } else if (flagged.has(el) || el.getAttribute('aria-invalid') !== 'true') {
      el.setAttribute('aria-invalid', 'false');
      linkError(el, false);
      flagged.delete(el);
      announced.delete(el);
      if (announcedField === el) {
        clearTimeout(announceTimer);
        announcer.textContent = '';
      }
    }
  };

  const handlePhotoChange = () => {
    if (!photoInput || !previewBox) return;
    if (previewObjectUrl) {
      URL.revokeObjectURL(previewObjectUrl);
      previewObjectUrl = null;
    }
    const file = photoInput.files?.[0];
    if (!file) {
      photoInput.setCustomValidity('');
      previewBox.innerHTML = '<span>Pratinjau foto akan tampil di sini</span>';
      return;
    }
    if (!ALLOWED_TYPES.has(file.type)) {
      photoInput.setCustomValidity('Format foto harus JPG, PNG, atau WebP.');
      previewBox.innerHTML = '<span>Format berkas tidak didukung</span>';
      return;
    }
    if (file.size > MAX_BYTES) {
      photoInput.setCustomValidity('Ukuran foto melebihi batas 5 MB.');
      previewBox.innerHTML = '<span>Ukuran foto terlalu besar (maks. 5 MB)</span>';
      return;
    }
    photoInput.setCustomValidity('');
    previewObjectUrl = URL.createObjectURL(file);
    const img = document.createElement('img');
    img.src = previewObjectUrl;
    img.alt = 'Pratinjau foto tanaman yang akan diunggah';
    previewBox.replaceChildren(img);
  };

  if (uploadForm) {
    const fields = uploadForm.querySelectorAll('input, select, textarea');
    const dirtyState = new WeakMap();
    fields.forEach((el) => syncAria(el));

    uploadForm.addEventListener('blur', (e) => {
      const el = e.target;
      if (!supportsUserInvalid && el.checkValidity) {
        const st = dirtyState.get(el) || { hasInteracted: false, hasBlurred: false };
        st.hasBlurred = true;
        dirtyState.set(el, st);
        if (st.hasInteracted) {
          el.classList.toggle('user-invalid-fallback', !el.validity.valid);
          el.classList.toggle('user-valid-fallback', el.validity.valid);
        }
      }
      syncAria(el, true);
    }, true);

    uploadForm.addEventListener('input', (e) => {
      const el = e.target;
      if (!supportsUserInvalid && el.checkValidity) {
        const st = dirtyState.get(el) || { hasInteracted: false, hasBlurred: false };
        st.hasInteracted = true;
        dirtyState.set(el, st);
        if (st.hasBlurred) {
          el.classList.toggle('user-invalid-fallback', !el.validity.valid);
          el.classList.toggle('user-valid-fallback', el.validity.valid);
        }
      }
      if (el.hasAttribute('aria-invalid')) syncAria(el);
    });

    uploadForm.addEventListener('change', (e) => {
      const el = e.target;
      if (el === photoInput) handlePhotoChange();
      if (!supportsUserInvalid && el.checkValidity) {
        dirtyState.set(el, { hasInteracted: true, hasBlurred: true });
        el.classList.toggle('user-invalid-fallback', !el.validity.valid);
        el.classList.toggle('user-valid-fallback', el.validity.valid);
      }
      syncAria(el, true);
    }, true);

    uploadForm.addEventListener('invalid', (e) => {
      const el = e.target;
      if (!supportsUserInvalid) {
        dirtyState.set(el, { hasInteracted: true, hasBlurred: true });
        el.classList.add('user-invalid-fallback');
        el.classList.remove('user-valid-fallback');
      }
      announced.add(el);
      syncAria(el, false, true);
    }, true);
  }

  // Lightbox & konfirmasi hapus via native <dialog>
  const lightboxDialog = document.getElementById('lightboxDialog');
  const lightboxImg = document.getElementById('lightboxImg');
  const lightboxCaption = document.getElementById('lightboxCaption');
  const lightboxMeta = document.getElementById('lightboxMeta');

  document.querySelectorAll('[data-lightbox]').forEach((btn) => {
    btn.addEventListener('click', () => {
      lightboxImg.src = btn.dataset.src || '';
      lightboxImg.alt = btn.dataset.caption || '';
      lightboxCaption.textContent = btn.dataset.caption || '';
      lightboxMeta.textContent = btn.dataset.meta || '';
      lightboxDialog.showModal();
    });
  });

  const editDialog = document.getElementById('editDialog');
  const editIdInput = document.getElementById('editIdInput');
  const editDevice = document.getElementById('editDevice');
  const editKondisi = document.getElementById('editKondisi');
  const editCaption = document.getElementById('editCaption');

  document.querySelectorAll('[data-edit-id]').forEach((btn) => {
    btn.addEventListener('click', () => {
      editIdInput.value = btn.dataset.editId || '';
      editDevice.value = btn.dataset.editDevice || '';
      editKondisi.value = btn.dataset.editKondisi || '';
      editCaption.value = btn.dataset.editCaption || '';
      editDialog.showModal();
    });
  });

  const deleteDialog = document.getElementById('deleteDialog');
  const deleteIdInput = document.getElementById('deleteIdInput');
  const deletePreview = document.getElementById('deletePreview');

  document.querySelectorAll('[data-delete-id]').forEach((btn) => {
    btn.addEventListener('click', () => {
      deleteIdInput.value = btn.dataset.deleteId || '';
      deletePreview.textContent = btn.dataset.deleteLabel || '';
      deleteDialog.showModal();
    });
  });

  document.querySelectorAll('[data-close-dialog]').forEach((btn) => {
    btn.addEventListener('click', () => {
      btn.closest('dialog')?.close();
    });
  });

  // Fallback light-dismiss klik backdrop untuk browser tanpa dukungan closedby
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
