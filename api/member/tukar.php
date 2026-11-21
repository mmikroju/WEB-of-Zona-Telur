<?php
// POST: member menukar 100 poin dengan bonus 1 pcs
require_once __DIR__ . '/../../includes/bootstrap.php';
$u = api_mulai('POST', 'member');
$d = masukan();
$kodeProduk = teks($d, 'produk', 30);

$pdo = db();
$pdo->beginTransaction();
try {
    // Kunci baris member agar dua permintaan bersamaan tidak bisa memakai poin yang sama
    $st = $pdo->prepare('SELECT nama, telepon FROM pengguna WHERE id = ? FOR UPDATE');
    $st->execute([$u['id']]);
    $m = $st->fetch();

    $st = $pdo->prepare('SELECT id, nama FROM produk WHERE kode = ? AND aktif = 1 AND bisa_bonus = 1');
    $st->execute([$kodeProduk]);
    $produk = $st->fetch();
    if (!$produk) {
        $pdo->rollBack();
        galat('Pilihan bonus tidak tersedia.');
    }

    if (hitung_poin((int) $u['id'])['saldo'] < POIN_TUKAR) {
        $pdo->rollBack();
        galat('Poin belum cukup. Dibutuhkan ' . POIN_TUKAR . ' poin.', 422);
    }

    $kode = '';
    for ($coba = 0; $coba < 5; $coba++) {
        $kode = 'TK-' . strtoupper(bin2hex(random_bytes(3)));
        try {
            $pdo->prepare(
                'INSERT INTO penukaran (kode, user_id, nama, telepon, produk_id, produk_nama, poin) VALUES (?, ?, ?, ?, ?, ?, ?)'
            )->execute([$kode, $u['id'], $m['nama'], $m['telepon'], $produk['id'], $produk['nama'], POIN_TUKAR]);
            break;
        } catch (PDOException $e) {
            if ($e->getCode() !== '23000' || $coba === 4) throw $e;
        }
    }
    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Gagal menukar poin: ' . $e->getMessage());
    galat('Penukaran gagal. Silakan coba lagi.', 500);
}

json_keluar(['ok' => true, 'kode' => $kode, 'produk' => $produk['nama'], 'nama' => $m['nama']], 201);
