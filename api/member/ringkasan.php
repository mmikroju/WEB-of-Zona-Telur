<?php
// GET: data dashboard member (profil, poin, pesanan, penukaran)
require_once __DIR__ . '/../../includes/bootstrap.php';
$u = api_mulai('GET', 'member');
$id = (int) $u['id'];

$pesanan = ambil_pesanan('user_id = ?', [$id]);

$st = db()->prepare('SELECT kode, produk_nama, poin, status, dibuat FROM penukaran WHERE user_id = ? ORDER BY dibuat DESC, id DESC');
$st->execute([$id]);
$penukaran = [];
foreach ($st->fetchAll() as $t) {
    $penukaran[] = [
        'kode'    => $t['kode'],
        'produk'  => $t['produk_nama'],
        'poin'    => (int) $t['poin'],
        'status'  => $t['status'],
        'dibuat'  => iso($t['dibuat']),
    ];
}

$aktif = 0;
$belanja = 0;
foreach ($pesanan as $p) {
    if ($p['status'] === 'Baru' || $p['status'] === 'Diproses') $aktif++;
    if ($p['status'] !== 'Dibatalkan') $belanja += $p['total'];
}

json_keluar([
    'user'      => data_publik($u),
    'poin'      => hitung_poin($id),
    'pesanan'   => $pesanan,
    'penukaran' => $penukaran,
    'stat'      => ['aktif' => $aktif, 'total' => count($pesanan), 'belanja' => $belanja],
]);
