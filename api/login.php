<?php
// POST: masuk sebagai admin atau member (peran dikenali otomatis dari akun)
require_once __DIR__ . '/../includes/bootstrap.php';
api_mulai('POST');

if (batas_terlampaui('login', BATAS_LOGIN_GAGAL, BATAS_LOGIN_MENIT)) {
    galat('Terlalu banyak percobaan masuk. Coba lagi beberapa menit lagi.', 429);
}

$d = masukan();
$id = mb_strtolower(teks($d, 'id', 150));
$sandi = is_string($d['sandi'] ?? null) ? $d['sandi'] : '';
if ($id === '' || $sandi === '') {
    galat('Isi email/username dan kata sandi.');
}

$st = db()->prepare('SELECT id, peran, sandi FROM pengguna WHERE email = ? OR username = ? LIMIT 1');
$st->execute([$id, $id]);
$u = $st->fetch();

// Selalu menjalankan password_verify agar waktu respons tidak membocorkan
// apakah akun ada atau tidak.
$hashPalsu = '$2y$10$usesomesillystringforeusesomesillystringforeusesomesillystr';
$cocok = password_verify($sandi, $u['sandi'] ?? $hashPalsu) && $u;

if (!$cocok) {
    catat_akses('login');
    galat('Email/username atau kata sandi salah.', 401);
}

hapus_catatan_akses('login');
if (password_needs_rehash($u['sandi'], PASSWORD_DEFAULT)) {
    db()->prepare('UPDATE pengguna SET sandi = ? WHERE id = ?')
        ->execute([password_hash($sandi, PASSWORD_DEFAULT), $u['id']]);
}
mulai_sesi((int) $u['id']);

$st = db()->prepare('SELECT id, peran, nama, username, email, telepon FROM pengguna WHERE id = ?');
$st->execute([$u['id']]);
json_keluar(['user' => data_publik($st->fetch())]);
