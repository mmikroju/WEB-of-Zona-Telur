<?php
// GET: seluruh data dashboard admin
require_once __DIR__ . '/../../includes/bootstrap.php';
api_mulai('GET', 'admin');
$pdo = db();

$pesanan = ambil_pesanan('', []);

// ---- Statistik ----
$st = $pdo->query(
    "SELECT COUNT(*) AS total,
            COALESCE(SUM(status = 'Baru'), 0) AS baru,
            COALESCE(SUM(CASE WHEN status = 'Selesai' THEN total END), 0) AS pendapatan
     FROM pesanan"
);
$s = $st->fetch();
$jumlahMember = (int) $pdo->query("SELECT COUNT(*) FROM pengguna WHERE peran = 'member'")->fetchColumn();

// ---- Produk terlaris (tanpa pesanan yang dibatalkan) ----
$terlaris = [];
foreach ($pdo->query(
    "SELECT i.nama, SUM(i.jumlah) AS jumlah
     FROM pesanan_item i JOIN pesanan p ON p.id = i.pesanan_id
     WHERE p.status <> 'Dibatalkan'
     GROUP BY i.nama ORDER BY jumlah DESC"
)->fetchAll() as $r) {
    $terlaris[] = ['nama' => $r['nama'], 'jumlah' => (int) $r['jumlah']];
}

// ---- Member beserta jumlah pesanan dan saldo poin ----
$member = [];
foreach ($pdo->query(
    "SELECT u.id, u.nama, u.email, u.telepon, u.dibuat,
        (SELECT COUNT(*) FROM pesanan WHERE user_id = u.id) AS jumlah_pesanan,
        (SELECT COALESCE(SUM(i.poin), 0) FROM pesanan p JOIN pesanan_item i ON i.pesanan_id = p.id
          WHERE p.user_id = u.id AND p.status = 'Selesai') AS didapat,
        (SELECT COALESCE(SUM(poin), 0) FROM penukaran WHERE user_id = u.id AND status <> 'Ditolak') AS terpakai
     FROM pengguna u WHERE u.peran = 'member' ORDER BY u.dibuat DESC, u.id DESC"
)->fetchAll() as $m) {
    $member[] = [
        'id'      => (int) $m['id'],
        'nama'    => $m['nama'],
        'email'   => $m['email'],
        'telepon' => $m['telepon'],
        'dibuat'  => iso($m['dibuat']),
        'jumlah_pesanan' => (int) $m['jumlah_pesanan'],
        'saldo'   => max(0, (int) $m['didapat'] - (int) $m['terpakai']),
    ];
}

// ---- Penukaran poin ----
$penukaran = [];
foreach ($pdo->query(
    'SELECT id, kode, nama, telepon, produk_nama, poin, status, dibuat FROM penukaran ORDER BY dibuat DESC, id DESC LIMIT 500'
)->fetchAll() as $t) {
    $penukaran[] = [
        'id'      => (int) $t['id'],
        'kode'    => $t['kode'],
        'nama'    => $t['nama'],
        'telepon' => $t['telepon'],
        'produk'  => $t['produk_nama'],
        'poin'    => (int) $t['poin'],
        'status'  => $t['status'],
        'dibuat'  => iso($t['dibuat']),
    ];
}

json_keluar([
    'stat' => [
        'baru'       => (int) $s['baru'],
        'total'      => (int) $s['total'],
        'pendapatan' => (int) $s['pendapatan'],
        'member'     => $jumlahMember,
    ],
    'terlaris'  => $terlaris,
    'pesanan'   => $pesanan,
    'member'    => $member,
    'penukaran' => $penukaran,
]);
