<?php
// =========================================================
// Zona Telur - includes/config.php
// Pengaturan database dan aturan poin.
// Nilai bisa diubah langsung di sini (default cocok untuk XAMPP).
// =========================================================

// ----- Database -----
define('DB_HOST', getenv('ZT_DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('ZT_DB_NAME') ?: 'zona_telur');
define('DB_USER', getenv('ZT_DB_USER') ?: 'root');
define('DB_PASS', getenv('ZT_DB_PASS') !== false ? getenv('ZT_DB_PASS') : '');

// ----- Zona waktu (Lombok = WITA) -----
define('ZONA_WAKTU', 'Asia/Makassar');
define('ZONA_WAKTU_SQL', '+08:00');

// ----- Aturan poin -----
define('POIN_PER_MIKA', 10);   // setiap 1 mika/tray yang dibeli
define('POIN_TUKAR', 100);     // poin untuk 1 bonus (1 pcs)

// ----- Batas anti spam (maksimal kejadian per IP dalam N menit) -----
define('BATAS_LOGIN_GAGAL', 8);    // percobaan login gagal
define('BATAS_LOGIN_MENIT', 10);
define('BATAS_DAFTAR', 5);         // pendaftaran akun
define('BATAS_DAFTAR_MENIT', 60);
define('BATAS_PESAN', 10);         // pesanan
define('BATAS_PESAN_MENIT', 60);

// ----- Lainnya -----
define('NOMOR_WA', '6289529399731');  // dipakai untuk tombol konfirmasi penukaran
define('TAMPILKAN_GALAT', false);     // true hanya saat pengembangan
