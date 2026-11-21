<?php
// POST: buat pesanan baru dari keranjang (tamu maupun member)
require_once __DIR__ . '/../includes/bootstrap.php';
api_mulai('POST');

if (batas_terlampaui('pesan', BATAS_PESAN, BATAS_PESAN_MENIT)) {
    galat('Terlalu banyak pesanan dari jaringan ini. Coba lagi nanti atau hubungi kami lewat WhatsApp.', 429);
}

$d = masukan();
$nama = teks($d, 'nama', 100);
$telepon = bersihkan_telepon(teks($d, 'telepon', 20));
$ambil = teks($d, 'ambil', 20);
$alamat = teks($d, 'alamat', 255);
$bayar = teks($d, 'bayar', 10);
$catatan = teks($d, 'catatan', 255);

if (mb_strlen($nama) < 3) galat('Nama minimal 3 huruf.');
if (!telepon_valid($telepon)) galat('Nomor WhatsApp tidak valid. Contoh: 08123456789');
if (!in_array($ambil, ['Ambil sendiri', 'Diantar'], true)) galat('Pilihan pengambilan tidak valid.');
if ($ambil === 'Diantar' && mb_strlen($alamat) < 5) galat('Alamat pengantaran wajib diisi.');
if ($ambil !== 'Diantar') $alamat = '';
if (!in_array($bayar, ['QRIS', 'Tunai'], true)) galat('Metode pembayaran tidak valid.');

// ---- Isi keranjang: hanya kode produk dan jumlah yang dipercaya dari browser ----
$kiriman = $d['items'] ?? null;
if (!is_array($kiriman) || count($kiriman) === 0 || count($kiriman) > 20) {
    galat('Keranjang masih kosong.');
}
$jumlahPerKode = [];
foreach ($kiriman as $it) {
    $kode = is_array($it) && is_string($it['id'] ?? null) ? $it['id'] : '';
    $jml  = is_array($it) ? ($it['jumlah'] ?? 0) : 0;
    if ($kode === '' || !is_int($jml) || $jml < 1 || $jml > 99) {
        galat('Isi keranjang tidak valid.');
    }
    $jumlahPerKode[$kode] = min(99, ($jumlahPerKode[$kode] ?? 0) + $jml);
}

// ---- Harga dan poin dihitung dari database ----
$in = implode(',', array_fill(0, count($jumlahPerKode), '?'));
$st = db()->prepare("SELECT id, kode, nama, satuan, harga, dapat_poin FROM produk WHERE aktif = 1 AND kode IN ($in)");
$st->execute(array_keys($jumlahPerKode));
$produk = [];
foreach ($st->fetchAll() as $p) {
    $produk[$p['kode']] = $p;
}
if (count($produk) !== count($jumlahPerKode)) {
    galat('Ada produk yang tidak tersedia. Muat ulang halaman.');
}

$total = 0;
$baris = [];
foreach ($jumlahPerKode as $kode => $jml) {
    $p = $produk[$kode];
    $total += (int) $p['harga'] * $jml;
    $baris[] = [
        'produk_id' => (int) $p['id'],
        'nama'      => $p['nama'],
        'satuan'    => $p['satuan'],
        'harga'     => (int) $p['harga'],
        'jumlah'    => $jml,
        'poin'      => $p['dapat_poin'] ? $jml * POIN_PER_MIKA : 0,
    ];
}

$u = pengguna_sekarang();
$idUser = ($u && $u['peran'] === 'member') ? (int) $u['id'] : null;

$pdo = db();
$pdo->beginTransaction();
try {
    // Kode pesanan acak; ulangi jika kebetulan bentrok
    $idPesanan = 0;
    $kodePesanan = '';
    for ($coba = 0; $coba < 5 && !$idPesanan; $coba++) {
        $kodePesanan = 'ZT-' . strtoupper(bin2hex(random_bytes(3)));
        try {
            $pdo->prepare(
                'INSERT INTO pesanan (kode, user_id, nama, telepon, ambil, alamat, bayar, catatan, total, ip)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([
                $kodePesanan, $idUser, $nama, $telepon, $ambil,
                $alamat !== '' ? $alamat : null, $bayar,
                $catatan !== '' ? $catatan : null, $total, ip_klien(),
            ]);
            $idPesanan = (int) $pdo->lastInsertId();
        } catch (PDOException $e) {
            if ($e->getCode() !== '23000') throw $e;
        }
    }
    if (!$idPesanan) {
        throw new RuntimeException('Gagal membuat kode pesanan.');
    }

    $ins = $pdo->prepare(
        'INSERT INTO pesanan_item (pesanan_id, produk_id, nama, satuan, harga, jumlah, poin) VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    foreach ($baris as $b) {
        $ins->execute([$idPesanan, $b['produk_id'], $b['nama'], $b['satuan'], $b['harga'], $b['jumlah'], $b['poin']]);
    }
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    error_log('Gagal menyimpan pesanan: ' . $e->getMessage());
    galat('Pesanan gagal disimpan. Silakan coba lagi.', 500);
}

catat_akses('pesan');
json_keluar([
    'ok'     => true,
    'kode'   => $kodePesanan,
    'total'  => $total,
    'member' => $idUser !== null,
], 201);
