<?php
// POST: ubah status pesanan
require_once __DIR__ . '/../../includes/bootstrap.php';
api_mulai('POST', 'admin');
$d = masukan();
$id = (int) ($d['id'] ?? 0);
$status = teks($d, 'status', 20);

if (!in_array($status, ['Baru', 'Diproses', 'Selesai', 'Dibatalkan'], true)) galat('Status tidak valid.');

$st = db()->prepare('UPDATE pesanan SET status = ? WHERE id = ?');
$st->execute([$status, $id]);
json_keluar(['ok' => true]);
