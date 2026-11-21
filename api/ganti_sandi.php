<?php
// POST: ganti kata sandi sendiri (admin maupun member)
require_once __DIR__ . '/../includes/bootstrap.php';
api_mulai('POST');
$u = pengguna_sekarang();
if (!$u) galat('Silakan masuk terlebih dahulu.', 401);

$d = masukan();
$lama = is_string($d['lama'] ?? null) ? $d['lama'] : '';
$baru = is_string($d['baru'] ?? null) ? $d['baru'] : '';

$st = db()->prepare('SELECT sandi FROM pengguna WHERE id = ?');
$st->execute([$u['id']]);
if (!password_verify($lama, (string) $st->fetchColumn())) {
    galat('Kata sandi lama salah.', 422);
}
if (strlen($baru) < 6) galat('Kata sandi baru minimal 6 karakter.');
if (strlen($baru) > 200) galat('Kata sandi terlalu panjang.');

db()->prepare('UPDATE pengguna SET sandi = ? WHERE id = ?')
    ->execute([password_hash($baru, PASSWORD_DEFAULT), $u['id']]);
session_regenerate_id(true);
json_keluar(['ok' => true]);
