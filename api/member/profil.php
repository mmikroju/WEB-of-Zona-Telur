<?php
// POST: ubah nama dan nomor WhatsApp member
require_once __DIR__ . '/../../includes/bootstrap.php';
$u = api_mulai('POST', 'member');
$d = masukan();
$nama = teks($d, 'nama', 100);
$telepon = bersihkan_telepon(teks($d, 'telepon', 20));

if (mb_strlen($nama) < 3) galat('Nama minimal 3 huruf.');
if (!telepon_valid($telepon)) galat('Nomor WhatsApp tidak valid. Contoh: 08123456789');

db()->prepare('UPDATE pengguna SET nama = ?, telepon = ? WHERE id = ?')->execute([$nama, $telepon, $u['id']]);
json_keluar(['ok' => true, 'nama' => $nama, 'telepon' => $telepon]);
