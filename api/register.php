<?php
// POST: daftar sebagai member baru, lalu langsung masuk
require_once __DIR__ . '/../includes/bootstrap.php';
api_mulai('POST');

if (batas_terlampaui('daftar', BATAS_DAFTAR, BATAS_DAFTAR_MENIT)) {
    galat('Terlalu banyak pendaftaran dari jaringan ini. Coba lagi nanti.', 429);
}

$d = masukan();
$nama = teks($d, 'nama', 100);
$email = mb_strtolower(teks($d, 'email', 150));
$telepon = bersihkan_telepon(teks($d, 'telepon', 20));
$sandi = is_string($d['sandi'] ?? null) ? $d['sandi'] : '';

if (mb_strlen($nama) < 3) galat('Nama minimal 3 huruf.');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) galat('Format email tidak valid.');
if (!telepon_valid($telepon)) galat('Nomor WhatsApp tidak valid. Contoh: 08123456789');
if (strlen($sandi) < 6) galat('Kata sandi minimal 6 karakter.');
if (strlen($sandi) > 200) galat('Kata sandi terlalu panjang.');

try {
    db()->prepare('INSERT INTO pengguna (peran, nama, email, telepon, sandi) VALUES ("member", ?, ?, ?, ?)')
        ->execute([$nama, $email, $telepon, password_hash($sandi, PASSWORD_DEFAULT)]);
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        galat('Email sudah terdaftar. Silakan masuk.', 409);
    }
    throw $e;
}

$id = (int) db()->lastInsertId();
catat_akses('daftar');
mulai_sesi($id);

$st = db()->prepare('SELECT id, peran, nama, username, email, telepon FROM pengguna WHERE id = ?');
$st->execute([$id]);
json_keluar(['user' => data_publik($st->fetch())], 201);
