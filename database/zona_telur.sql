-- =========================================================
-- Zona Telur - skema database (MySQL / MariaDB)
-- Cara pakai: impor file ini lewat phpMyAdmin (tab Import),
-- atau jalankan:  mysql -u root -p < database/zona_telur.sql
-- =========================================================

CREATE DATABASE IF NOT EXISTS zona_telur
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE zona_telur;

-- ---------------------------------------------------------
-- Pengguna: admin dan member
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS pengguna (
  id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  peran     ENUM('admin','member') NOT NULL DEFAULT 'member',
  nama      VARCHAR(100) NOT NULL,
  username  VARCHAR(50)  NULL,            -- dipakai admin untuk login
  email     VARCHAR(150) NULL,            -- dipakai member untuk login
  telepon   VARCHAR(20)  NOT NULL DEFAULT '',
  sandi     VARCHAR(255) NOT NULL,        -- hash bcrypt (password_hash)
  dibuat    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_username (username),
  UNIQUE KEY uk_email (email)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Produk (menu). Harga diambil dari sini, bukan dari browser.
--   dapat_poin : 1 = pembelian produk ini menghasilkan poin
--   bisa_bonus : 1 = produk ini boleh dipilih sebagai bonus penukaran poin
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS produk (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  kode          VARCHAR(30)  NOT NULL,
  nama          VARCHAR(100) NOT NULL,
  deskripsi     VARCHAR(255) NOT NULL DEFAULT '',
  harga         INT UNSIGNED NOT NULL,
  satuan        VARCHAR(20)  NOT NULL DEFAULT 'mika',
  gambar        VARCHAR(150) NOT NULL DEFAULT '',
  lencana       VARCHAR(30)  NULL,
  gambar_penuh  TINYINT(1)   NOT NULL DEFAULT 0,
  dapat_poin    TINYINT(1)   NOT NULL DEFAULT 1,
  bisa_bonus    TINYINT(1)   NOT NULL DEFAULT 0,
  urutan        INT          NOT NULL DEFAULT 0,
  aktif         TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  UNIQUE KEY uk_kode (kode)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Pesanan dan isinya
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS pesanan (
  id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  kode      VARCHAR(20)  NOT NULL,
  user_id   INT UNSIGNED NULL,            -- NULL = pembeli tamu / member sudah dihapus
  nama      VARCHAR(100) NOT NULL,
  telepon   VARCHAR(20)  NOT NULL,
  ambil     ENUM('Ambil sendiri','Diantar') NOT NULL,
  alamat    VARCHAR(255) NULL,
  bayar     ENUM('QRIS','Tunai') NOT NULL,
  catatan   VARCHAR(255) NULL,
  total     INT UNSIGNED NOT NULL,
  status    ENUM('Baru','Diproses','Selesai','Dibatalkan') NOT NULL DEFAULT 'Baru',
  ip        VARCHAR(45)  NULL,
  dibuat    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_kode (kode),
  KEY idx_user (user_id),
  KEY idx_status (status),
  KEY idx_dibuat (dibuat),
  CONSTRAINT fk_pesanan_user FOREIGN KEY (user_id)
    REFERENCES pengguna (id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pesanan_item (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  pesanan_id  INT UNSIGNED NOT NULL,
  produk_id   INT UNSIGNED NULL,
  nama        VARCHAR(100) NOT NULL,      -- salinan saat dipesan
  satuan      VARCHAR(20)  NOT NULL,
  harga       INT UNSIGNED NOT NULL,      -- harga satuan saat dipesan
  jumlah      INT UNSIGNED NOT NULL,
  poin        INT UNSIGNED NOT NULL DEFAULT 0,  -- poin yang dihasilkan item ini
  PRIMARY KEY (id),
  KEY idx_pesanan (pesanan_id),
  CONSTRAINT fk_item_pesanan FOREIGN KEY (pesanan_id)
    REFERENCES pesanan (id) ON DELETE CASCADE,
  CONSTRAINT fk_item_produk FOREIGN KEY (produk_id)
    REFERENCES produk (id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Penukaran poin (bonus 1 pcs per 100 poin)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS penukaran (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  kode         VARCHAR(20)  NOT NULL,
  user_id      INT UNSIGNED NULL,
  nama         VARCHAR(100) NOT NULL,     -- salinan nama member
  telepon      VARCHAR(20)  NOT NULL,
  produk_id    INT UNSIGNED NULL,
  produk_nama  VARCHAR(100) NOT NULL,
  poin         INT UNSIGNED NOT NULL,
  status       ENUM('Diajukan','Selesai','Ditolak') NOT NULL DEFAULT 'Diajukan',
  dibuat       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_kode (kode),
  KEY idx_user (user_id),
  CONSTRAINT fk_tukar_user FOREIGN KEY (user_id)
    REFERENCES pengguna (id) ON DELETE SET NULL,
  CONSTRAINT fk_tukar_produk FOREIGN KEY (produk_id)
    REFERENCES produk (id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Pembatas akses (anti spam: login, daftar, pesan)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS batas_akses (
  id     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  jenis  VARCHAR(20)  NOT NULL,
  ip     VARCHAR(45)  NOT NULL,
  waktu  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_cari (jenis, ip, waktu)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Data awal
-- ---------------------------------------------------------
INSERT INTO produk (kode, nama, deskripsi, harga, satuan, gambar, lencana, gambar_penuh, dapat_poin, bisa_bonus, urutan) VALUES
('pindang', 'Telur Pindang', 'Bumbu meresap hingga ke dalam, gurih dan wangi rempah. Mika isi 3 butir.', 12000, 'mika',  'Assets/telur-pindang.jpg', 'Favorit', 0, 1, 1, 1),
('asin',    'Telur Asin',    'Asin gurih merata dengan kuning telur berminyak. Mika isi 3 butir.',      13000, 'mika',  'Assets/telur-asin.jpg',    NULL,      0, 1, 1, 2),
('mentah',  'Telur Mentah',  'Telur ayam segar untuk kebutuhan dapur, kue, atau usaha kuliner.',        50000, 'butir', 'Assets/telur-mentah.jpg',  NULL,      1, 1, 0, 3);

-- Akun admin bawaan: username "admin", kata sandi "admin123"
-- WAJIB diganti setelah instalasi (lihat PETUNJUK-INSTALASI.md)
INSERT INTO pengguna (peran, nama, username, email, telepon, sandi) VALUES
('admin', 'Admin Zona Telur', 'admin', NULL, '', '$2y$10$VPZPDr8QMSXLgta2U8WlQuVHHLJCZIG0cDQpcwlaCJIT5NP9HczRK');
