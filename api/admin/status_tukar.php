<?php
// POST: ubah status penukaran poin (Diajukan / Selesai / Ditolak)
require_once __DIR__ . '/../../includes/bootstrap.php';
api_mulai('POST', 'admin');
$d = masukan();
$id = (int) ($d['id'] ?? 0);
$status = teks($d, 'status', 20);

if (!in_array($status, ['Diajukan', 'Selesai', 'Ditolak'], true)) galat('Status tidak valid.');

$pdo = db();
$pdo->beginTransaction();
try {
    $st = $pdo->prepare('SELECT user_id, poin, status FROM penukaran WHERE id = ? FOR UPDATE');
    $st->execute([$id]);
    $t = $st->fetch();
    if (!$t) {
        $pdo->rollBack();
        galat('Data penukaran tidak ditemukan.', 404);
    }

    // Membuka kembali penukaran yang ditolak = memakai poin lagi. Pastikan saldo cukup.
    if ($t['status'] === 'Ditolak' && $status !== 'Ditolak' && $t['user_id'] !== null) {
        if (hitung_poin((int) $t['user_id'])['saldo'] < (int) $t['poin']) {
            $pdo->rollBack();
            galat('Saldo poin member tidak cukup untuk membuka kembali penukaran ini.', 409);
        }
    }

    $pdo->prepare('UPDATE penukaran SET status = ? WHERE id = ?')->execute([$status, $id]);
    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Gagal mengubah status penukaran: ' . $e->getMessage());
    galat('Gagal mengubah status.', 500);
}
json_keluar(['ok' => true]);
