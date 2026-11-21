<?php
// =========================================================
// Zona Telur - includes/bootstrap.php
// Dimuat oleh semua halaman dan API: sesi, database, fungsi bantu,
// CSRF, pembatas akses, dan perhitungan poin.
// =========================================================

declare(strict_types=1);

require_once __DIR__ . '/config.php';

date_default_timezone_set(ZONA_WAKTU);
error_reporting(E_ALL);
ini_set('display_errors', TAMPILKAN_GALAT ? '1' : '0');
ini_set('log_errors', '1');

// ---------- Sesi ----------
if (session_status() === PHP_SESSION_NONE) {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $https,
        'httponly' => true,      // tidak bisa dibaca JavaScript
        'samesite' => 'Lax',     // melindungi dari kiriman lintas situs
    ]);
    session_name('zt_sesi');
    session_start();
}

// ---------- Database ----------
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '" . ZONA_WAKTU_SQL . "'",
                ]
            );
        } catch (PDOException $e) {
            error_log('Koneksi database gagal: ' . $e->getMessage());
            if (str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/api/')) {
                json_keluar(['galat' => 'Database tidak dapat dihubungi.'], 500);
            }
            http_response_code(500);
            exit('Database tidak dapat dihubungi. Periksa includes/config.php dan pastikan MySQL berjalan.');
        }
    }
    return $pdo;
}

// ---------- Fungsi bantu ----------
function e(?string $teks): string
{
    return htmlspecialchars((string) $teks, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function rupiah(int $angka): string
{
    return 'Rp' . number_format($angka, 0, ',', '.');
}

// Ubah "2026-11-17 09:28:00" menjadi format ISO dengan zona waktu
function iso(?string $waktu): ?string
{
    return $waktu ? (new DateTimeImmutable($waktu))->format('c') : null;
}

function ip_klien(): string
{
    // Sengaja memakai REMOTE_ADDR saja: header X-Forwarded-For bisa dipalsukan.
    return substr($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', 0, 45);
}

function json_keluar(array $data, int $kode = 200): never
{
    http_response_code($kode);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function galat(string $pesan, int $kode = 400): never
{
    json_keluar(['galat' => $pesan], $kode);
}

// ---------- CSRF ----------
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

// ---------- Pengguna yang sedang login ----------
function pengguna_sekarang(): ?array
{
    static $cache = false;
    if ($cache !== false) {
        return $cache;
    }
    $cache = null;
    if (!empty($_SESSION['user_id'])) {
        $st = db()->prepare('SELECT id, peran, nama, username, email, telepon FROM pengguna WHERE id = ?');
        $st->execute([$_SESSION['user_id']]);
        $baris = $st->fetch();
        if ($baris) {
            $cache = $baris;
        } else {
            unset($_SESSION['user_id']); // akun sudah dihapus
        }
    }
    return $cache;
}

function mulai_sesi(int $idPengguna): void
{
    session_regenerate_id(true); // cegah session fixation
    $_SESSION['user_id'] = $idPengguna;
}

// Data pengguna yang aman dikirim ke browser
function data_publik(?array $u): ?array
{
    if (!$u) {
        return null;
    }
    return [
        'id'      => (int) $u['id'],
        'peran'   => $u['peran'],
        'nama'    => $u['nama'],
        'email'   => $u['email'],
        'telepon' => $u['telepon'],
    ];
}

// ---------- Penjaga halaman (untuk file .php yang menampilkan HTML) ----------
function halaman_wajib(string $peran): array
{
    $u = pengguna_sekarang();
    if (!$u || $u['peran'] !== $peran) {
        header('Location: index.php');
        exit;
    }
    header('Cache-Control: no-store');
    return $u;
}

// ---------- Pembuka API ----------
// $metode : 'GET' atau 'POST'. POST wajib membawa token CSRF.
// $peran  : null = siapa saja, 'member' / 'admin' = wajib login dengan peran itu.
function api_mulai(string $metode, ?string $peran = null): ?array
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== $metode) {
        header('Allow: ' . $metode);
        galat('Metode tidak diizinkan.', 405);
    }

    if ($metode === 'POST') {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!hash_equals(csrf_token(), (string) $token)) {
            galat('Sesi tidak valid. Muat ulang halaman lalu coba lagi.', 403);
        }
    }

    $u = null;
    if ($peran !== null) {
        $u = pengguna_sekarang();
        if (!$u) {
            galat('Silakan masuk terlebih dahulu.', 401);
        }
        if ($u['peran'] !== $peran) {
            galat('Anda tidak memiliki akses.', 403);
        }
    }
    return $u;
}

// Membaca isi permintaan JSON
function masukan(): array
{
    $mentah = file_get_contents('php://input');
    if ($mentah === '' || $mentah === false) {
        return [];
    }
    $data = json_decode($mentah, true);
    if (!is_array($data)) {
        galat('Format data tidak valid.');
    }
    return $data;
}

// Ambil nilai teks dari masukan, dipangkas dan dibatasi panjangnya
function teks(array $data, string $kunci, int $maks = 255): string
{
    $nilai = $data[$kunci] ?? '';
    if (!is_string($nilai)) {
        return '';
    }
    return mb_substr(trim($nilai), 0, $maks);
}

function telepon_valid(string $telepon): bool
{
    return (bool) preg_match('/^(08|62)[0-9]{8,13}$/', $telepon);
}

function bersihkan_telepon(string $telepon): string
{
    return preg_replace('/[\s-]/', '', $telepon) ?? '';
}

// ---------- Pembatas akses (anti spam) ----------
function batas_terlampaui(string $jenis, int $maks, int $menit): bool
{
    $st = db()->prepare(
        'SELECT COUNT(*) FROM batas_akses WHERE jenis = ? AND ip = ? AND waktu > (NOW() - INTERVAL ' . (int) $menit . ' MINUTE)'
    );
    $st->execute([$jenis, ip_klien()]);
    return (int) $st->fetchColumn() >= $maks;
}

function catat_akses(string $jenis): void
{
    db()->prepare('INSERT INTO batas_akses (jenis, ip) VALUES (?, ?)')->execute([$jenis, ip_klien()]);
    if (random_int(1, 50) === 1) { // bersihkan catatan lama sesekali
        db()->exec('DELETE FROM batas_akses WHERE waktu < (NOW() - INTERVAL 1 DAY)');
    }
}

function hapus_catatan_akses(string $jenis): void
{
    db()->prepare('DELETE FROM batas_akses WHERE jenis = ? AND ip = ?')->execute([$jenis, ip_klien()]);
}

// ---------- Poin member ----------
//  didapat  : poin dari pesanan berstatus Selesai
//  menunggu : poin dari pesanan Baru / Diproses
//  terpakai : poin untuk penukaran yang belum ditolak
function hitung_poin(int $idPengguna): array
{
    $st = db()->prepare(
        "SELECT
            COALESCE(SUM(CASE WHEN p.status = 'Selesai' THEN i.poin END), 0) AS didapat,
            COALESCE(SUM(CASE WHEN p.status IN ('Baru','Diproses') THEN i.poin END), 0) AS menunggu
         FROM pesanan p
         JOIN pesanan_item i ON i.pesanan_id = p.id
         WHERE p.user_id = ?"
    );
    $st->execute([$idPengguna]);
    $r = $st->fetch();

    $st = db()->prepare("SELECT COALESCE(SUM(poin), 0) FROM penukaran WHERE user_id = ? AND status <> 'Ditolak'");
    $st->execute([$idPengguna]);
    $terpakai = (int) $st->fetchColumn();

    $didapat = (int) $r['didapat'];
    return [
        'didapat'  => $didapat,
        'menunggu' => (int) $r['menunggu'],
        'terpakai' => $terpakai,
        'saldo'    => max(0, $didapat - $terpakai),
    ];
}

// Ambil daftar pesanan lengkap dengan item-itemnya.
// $where berisi kondisi SQL (tanpa kata WHERE), $params nilainya.
function ambil_pesanan(string $where, array $params, int $batas = 500): array
{
    $sql = 'SELECT id, kode, user_id, nama, telepon, ambil, alamat, bayar, catatan, total, status, dibuat
            FROM pesanan' . ($where !== '' ? ' WHERE ' . $where : '') . '
            ORDER BY dibuat DESC, id DESC LIMIT ' . (int) $batas;
    $st = db()->prepare($sql);
    $st->execute($params);
    $daftar = $st->fetchAll();
    if (!$daftar) {
        return [];
    }

    $ids = array_column($daftar, 'id');
    $in  = implode(',', array_fill(0, count($ids), '?'));
    $st  = db()->prepare("SELECT pesanan_id, nama, satuan, harga, jumlah, poin FROM pesanan_item WHERE pesanan_id IN ($in)");
    $st->execute($ids);
    $item = [];
    $poinPesanan = [];
    foreach ($st->fetchAll() as $i) {
        $poinPesanan[$i['pesanan_id']] = ($poinPesanan[$i['pesanan_id']] ?? 0) + (int) $i['poin'];
        $item[$i['pesanan_id']][] = [
            'nama'   => $i['nama'],
            'satuan' => $i['satuan'],
            'harga'  => (int) $i['harga'],
            'jumlah' => (int) $i['jumlah'],
        ];
    }

    $hasil = [];
    foreach ($daftar as $p) {
        $items = $item[$p['id']] ?? [];
        $hasil[] = [
            'id'      => (int) $p['id'],
            'kode'    => $p['kode'],
            'nama'    => $p['nama'],
            'telepon' => $p['telepon'],
            'ambil'   => $p['ambil'],
            'alamat'  => $p['alamat'],
            'bayar'   => $p['bayar'],
            'catatan' => $p['catatan'],
            'total'   => (int) $p['total'],
            'status'  => $p['status'],
            'dibuat'  => iso($p['dibuat']),
            'member'  => $p['user_id'] !== null,
            'poin'    => $poinPesanan[$p['id']] ?? 0,
            'items'   => $items,
        ];
    }
    return $hasil;
}
