<?php
// POST: hapus akun member (riwayat pesanan & penukaran tetap tersimpan)
require_once __DIR__ . '/../../includes/bootstrap.php';
api_mulai('POST', 'admin');
$d = masukan();
$id = (int) ($d['id'] ?? 0);

$st = db()->prepare("DELETE FROM pengguna WHERE id = ? AND peran = 'member'");
$st->execute([$id]);
if ($st->rowCount() === 0) galat('Member tidak ditemukan.', 404);
json_keluar(['ok' => true]);
