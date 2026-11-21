# Zona Telur versi PHP + MySQL

Pesanan sekarang dibuat langsung di dalam web dan tersimpan di database
(tidak lagi dialihkan ke WhatsApp). Admin melihat dan memproses pesanan
dari dashboard.

## Kebutuhan
- PHP 8.1 atau lebih baru (ekstensi `pdo_mysql` aktif)
- MySQL 5.7+ atau MariaDB 10.3+
- Apache (XAMPP / Laragon / hosting cPanel) atau Nginx

## Instalasi dengan XAMPP (Windows)
1. Salin folder proyek ini ke `C:\xampp\htdocs\zona-telur`.
2. Jalankan **Apache** dan **MySQL** dari XAMPP Control Panel.
3. Buka `http://localhost/phpmyadmin`, tab **Import**, pilih
   `database/zona_telur.sql`, klik **Go**. Database `zona_telur` akan dibuat
   lengkap dengan tabel, 3 produk, dan 1 akun admin.
4. Buka `includes/config.php`, sesuaikan `DB_USER` dan `DB_PASS`
   (bawaan XAMPP: user `root`, kata sandi kosong, sudah sesuai).
5. Buka `http://localhost/zona-telur/`.

## Instalasi di hosting (cPanel)
1. Buat database dan user MySQL di cPanel, beri semua hak akses.
2. Impor `database/zona_telur.sql` lewat phpMyAdmin. Jika database sudah
   dibuat dari cPanel, hapus dua baris teratas (`CREATE DATABASE` dan `USE`)
   lalu pilih database tersebut sebelum mengimpor.
3. Ubah `includes/config.php` (`DB_NAME`, `DB_USER`, `DB_PASS`).
4. Unggah semua file ke `public_html`. Pakai HTTPS.

## Akun admin bawaan
- Username: `admin`
- Kata sandi: `admin123`

**Segera ganti** setelah masuk: Dashboard Admin, tab **Akun**, Ganti kata sandi.
Jika lupa kata sandi admin, buat hash baru lewat terminal:

    php database/buat_hash.php KataSandiBaru

lalu jalankan di phpMyAdmin:

    UPDATE pengguna SET sandi = 'HASIL_HASH' WHERE username = 'admin';

## Struktur file
    index.php            Beranda, menu (dibaca dari database), keranjang
    admin.php            Dashboard admin (hanya untuk admin)
    member.php           Dashboard member (hanya untuk member)
    api/                 Endpoint JSON (login, daftar, pesan, poin, admin)
    includes/            config.php (pengaturan) dan bootstrap.php (inti)
    database/            zona_telur.sql dan buat_hash.php
    *.js, *.css, Assets/ Tampilan

## Tabel database
| Tabel | Isi |
|---|---|
| `pengguna` | Admin dan member (kata sandi di-hash bcrypt) |
| `produk` | Menu, harga, aturan poin per produk |
| `pesanan`, `pesanan_item` | Pesanan dan isinya (harga disalin saat dipesan) |
| `penukaran` | Penukaran 100 poin menjadi bonus 1 pcs |
| `batas_akses` | Pembatas anti spam (login, daftar, pesan) |

## Aturan poin
- 10 poin per 1 mika/tray, dihitung saat pesanan berstatus **Selesai**.
- 100 poin ditukar bonus 1 pcs (produk dengan `bisa_bonus = 1`).
- Ubah angka di `includes/config.php` (`POIN_PER_MIKA`, `POIN_TUKAR`).
- Untuk menonaktifkan poin pada produk tertentu (mis. telur mentah):
  `UPDATE produk SET dapat_poin = 0 WHERE kode = 'mentah';`

## Mengubah harga / menu
Harga dan menu dibaca dari tabel `produk`. Ubah lewat phpMyAdmin, misalnya:

    UPDATE produk SET harga = 14000 WHERE kode = 'asin';

Harga dihitung ulang oleh server saat pesanan masuk, jadi tidak bisa dipalsukan
dari browser.

## Keamanan yang sudah ada
- Kata sandi di-hash (`password_hash`), sesi di cookie `HttpOnly` + `SameSite`
- Token CSRF di semua permintaan POST
- Semua query memakai prepared statement (PDO)
- Dashboard dijaga di server: admin.php hanya untuk admin, member.php untuk member
- Pembatas percobaan login, pendaftaran, dan pesanan per alamat IP
- Penukaran poin memakai transaksi, jadi tidak bisa dipakai ganda

## Sebelum dipublikasikan
- Ganti kata sandi admin bawaan
- Pasang HTTPS
- Di `includes/config.php`, biarkan `TAMPILKAN_GALAT = false`
- Folder `includes/` dan `database/` sudah memakai `.htaccess` agar tidak bisa
  dibuka dari browser (Apache). Di Nginx, blokir kedua folder itu di konfigurasi.
- Gunakan user MySQL khusus (bukan `root`) dengan kata sandi
