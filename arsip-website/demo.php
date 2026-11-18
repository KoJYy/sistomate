<?php
// demo.php - pintu masuk cepat dashboard untuk demonstrasi lokal.
// Login otomatis memakai akun demo di MySQL (atau fallback demo-data jika DB tidak aktif).
require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$u = null;
if (!db_is_demo()) {
    $u = db()->query(
        "SELECT id_user, name FROM users
         WHERE email IN ('demo@sistomate.local', 'admin@sistomate.id')
         ORDER BY FIELD(email, 'demo@sistomate.local', 'admin@sistomate.id') LIMIT 1"
    )->fetch();
    if (!$u) {
        $u = db()->query('SELECT id_user, name FROM users ORDER BY id_user LIMIT 1')->fetch();
    }
}
if (!$u) {
    require_once __DIR__ . '/demo-data.php';
    $u = demo_user();
}

$_SESSION['user_id'] = (int) $u['id_user'];
$_SESSION['user_name'] = $u['name'];
session_regenerate_id(true);

header('Location: app.php');
exit;
