<?php
require_once __DIR__ . '/includes/bootstrap.php';

$userSekarang = data_publik(pengguna_sekarang());
$daftarProduk = db()->query('SELECT kode, nama, deskripsi, harga, satuan, gambar, lencana, gambar_penuh FROM produk WHERE aktif = 1 ORDER BY urutan, id')->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Zona Telur - Telur Asin dan Telur Pindang Otentik</title>
  <meta name="description" content="Zona Telur menjual telur asin, telur pindang, dan telur mentah di Narmada, Lombok. Pesan online langsung dari web.">
  <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
  <link rel="icon" type="image/png" href="Assets/favicon.png">

  <!-- Font Google: Baloo 2 (judul) dan DM Sans (isi) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@600;700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="style.css">
  <script>window.ZT_USER = <?= json_encode($userSekarang, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
  <script src="api.js" defer></script>
  <script src="script.js" defer></script>
  <script src="auth.js" defer></script>
</head>
<body>

  <!-- ========== HEADER ========== -->
  <header>
    <a href="#beranda" class="brand" aria-label="Zona Telur, ke beranda">
      <img src="Assets/logo.png" alt="Logo Zona Telur" width="56" height="56">
      <span class="brand-teks">ZONA TELUR<small>Asin &amp; Pindang Otentik</small></span>
    </a>

    <button class="tombol-menu" id="tombolMenu" aria-label="Buka menu navigasi">☰</button>

    <nav id="navigasi">
      <a href="#beranda">BERANDA</a>
      <a href="#menu">MENU</a>
      <a href="#cara-pesan">CARA PESAN</a>
      <a href="#tentang">TENTANG</a>
      <a href="#lokasi">LOKASI</a>
    </nav>

    <!-- Akun: tombol Masuk (tamu) atau menu pengguna (sudah login) -->
    <div class="akun">
      <button type="button" class="tombol-masuk" id="tombolMasuk">👤 Masuk</button>

      <div class="akun-menu" id="akunMenu" hidden>
        <button type="button" class="akun-chip" id="akunChip" aria-haspopup="true" aria-expanded="false">
          <span class="akun-avatar" id="akunAvatar">?</span>
          <span class="akun-nama" id="akunNama">Nama</span>
          <span class="lencana-peran" id="akunPeran">Member</span>
        </button>
        <div class="akun-dropdown" id="akunDropdown" hidden>
          <a href="member.php" id="linkDashboard">📊 Dashboard</a>
          <button type="button" id="tombolKeluar">Keluar</button>
        </div>
      </div>
    </div>

    <button class="tombol-keranjang" id="tombolKeranjang" aria-label="Buka keranjang">
      🧺 Keranjang <span class="jumlah-keranjang" id="jumlahKeranjang">0</span>
    </button>
  </header>

  <main>

    <!-- ========== HERO ========== -->
    <section class="hero" id="beranda">
      <div class="hero-teks">
        <div class="label">Dari Narmada, Lombok</div>
        <h1>Telur asin &amp; pindang <em>otentik</em>, siap santap.</h1>
        <p>Gurih, bumbunya meresap, dikemas praktis dalam mika. Cocok untuk lauk sehari-hari,
           bekal, maupun acara keluarga. Pesan langsung lewat web, kami konfirmasi lewat WhatsApp.</p>

        <div class="hero-tombol">
          <a class="tombol" href="#menu">PESAN SEKARANG</a>
          <a class="tombol garis" href="#lokasi">HUBUNGI KAMI</a>
        </div>

        <ul class="hero-fakta">
          <li><strong>3 butir</strong><span>per mika</span></li>
          <li><strong>QRIS &amp; Tunai</strong><span>pembayaran</span></li>
          <li><strong>Pre-order</strong><span>jumlah besar</span></li>
        </ul>
      </div>

      <div class="hero-gambar" aria-hidden="true">
        <img class="gambar-logo" src="Assets/logo.png" alt="">
        <img class="foto foto-pindang" src="Assets/telur-pindang.jpg" alt="">
        <img class="foto foto-asin" src="Assets/telur-asin.jpg" alt="">
      </div>
    </section>

    <!-- ========== MENU ========== -->
    <section class="seksi-menu" id="menu">
      <div class="judul-seksi">
        <div class="label">Pilihan kami</div>
        <h2>Menu Zona Telur</h2>
        <p>Pilih jumlahnya, masukkan ke keranjang, lalu kirim pesanan langsung dari web ini.</p>
      </div>

      <div class="grid-menu">
<?php foreach ($daftarProduk as $p): ?>
        <article class="kartu-menu" data-id="<?= e($p['kode']) ?>" data-nama="<?= e($p['nama']) ?>" data-harga="<?= (int) $p['harga'] ?>" data-satuan="<?= e($p['satuan']) ?>" data-gambar="<?= e($p['gambar']) ?>">
          <div class="menu-foto">
            <img<?= $p['gambar_penuh'] ? ' class="isi-penuh"' : '' ?> src="<?= e($p['gambar']) ?>" alt="<?= e($p['nama']) ?>">
<?php if ($p['lencana']): ?>
            <span class="lencana"><?= e($p['lencana']) ?></span>
<?php endif; ?>
          </div>
          <div class="menu-isi">
            <h3><?= e($p['nama']) ?></h3>
            <p><?= e($p['deskripsi']) ?></p>
            <div class="harga"><?= e(rupiah((int) $p['harga'])) ?> <small>/ <?= e($p['satuan']) ?></small></div>
            <div class="menu-aksi">
              <div class="qty">
                <button type="button" class="kurang" aria-label="Kurangi">−</button>
                <span class="angka">1</span>
                <button type="button" class="tambah" aria-label="Tambah">+</button>
              </div>
              <button type="button" class="tombol masuk-keranjang">+ KERANJANG</button>
            </div>
          </div>
        </article>
<?php endforeach; ?>
      </div>
    </section>

    <!-- ========== CARA PESAN ========== -->
    <section class="seksi-langkah" id="cara-pesan">
      <div class="judul-seksi">
        <div class="label">Mudah dan cepat</div>
        <h2>Cara Memesan</h2>
      </div>

      <ol class="langkah">
        <li><span>1</span><h3>Pilih produk</h3><p>Tentukan telur pindang, telur asin, atau telur mentah dan jumlahnya.</p></li>
        <li><span>2</span><h3>Isi data pesanan</h3><p>Nama, nomor WhatsApp, ambil atau antar, dan metode pembayaran.</p></li>
        <li><span>3</span><h3>Kirim pesanan</h3><p>Pesanan langsung tercatat di sistem kami. Tinggal tekan kirim.</p></li>
        <li><span>4</span><h3>Konfirmasi &amp; bayar</h3><p>Admin menghubungi lewat WhatsApp untuk konfirmasi. Bayar via QRIS atau tunai.</p></li>
        <li><span>5</span><h3>Terima pesanan</h3><p>Ambil di tempat atau diantar sesuai pilihanmu.</p></li>
      </ol>
    </section>

    <!-- ========== TENTANG ========== -->
    <section class="seksi-tentang" id="tentang">
      <div class="tentang-foto">
        <img src="Assets/telur-asin.jpg" alt="Telur asin Zona Telur dalam bakul bambu">
        <img src="Assets/telur-pindang.jpg" alt="Telur pindang Zona Telur dalam bakul bambu">
      </div>

      <div class="tentang-isi">
        <div class="label">Cerita kami</div>
        <h2>Telur olahan yang dibuat dengan rasa otentik</h2>
        <p>Zona Telur adalah usaha telur olahan dari Narmada, Lombok. Kami mengolah telur menjadi
           telur asin yang gurih dan telur pindang yang bumbunya meresap, lalu mengemasnya dalam
           mika agar praktis dibawa dan dibagikan.</p>
        <p>Cukup pesan lewat web atau WhatsApp, pesanan langsung kami siapkan.</p>

        <ul class="keunggulan">
          <li><b>Bumbu meresap</b><span>Pindang gurih hingga ke dalam.</span></li>
          <li><b>Kemasan praktis</b><span>Mika isi 3 butir, siap dibawa.</span></li>
          <li><b>Harga terjangkau</b><span>Mulai Rp12.000 per mika.</span></li>
          <li><b>Produk lokal</b><span>Dibuat dan dilayani dari Lombok.</span></li>
        </ul>
      </div>
    </section>

    <!-- ========== LOKASI / KONTAK ========== -->
    <section class="seksi-lokasi" id="lokasi">
      <div class="judul-seksi">
        <div class="label">Hubungi kami</div>
        <h2>Pesan &amp; Tanya Langsung</h2>
        <p>Untuk pre-order jumlah besar, pertanyaan produk, atau pengantaran, chat kami kapan saja.</p>
      </div>

      <div class="kotak-lokasi">
        <img src="Assets/logo.png" alt="" class="logo-lokasi">
        <div>
          <h3>Zona Telur</h3>
          <p>📍 Narmada, Lombok<br>
             🕒 Chat kami kapan saja<br>
             ☎ WhatsApp 0895-2939-9731<br>
             📷 Instagram @zona.telur</p>
          <div class="lokasi-tombol">
            <a class="tombol" href="https://wa.me/6289529399731" target="_blank" rel="noopener">CHAT WHATSAPP</a>
            <a class="tombol garis" href="https://instagram.com/zona.telur" target="_blank" rel="noopener">INSTAGRAM</a>
          </div>
        </div>
      </div>
    </section>

  </main>

  <!-- ========== FOOTER ========== -->
  <footer>
    <div class="footer-brand">
      <img src="Assets/logo.png" alt="" width="44" height="44">
      <div><strong>ZONA TELUR</strong><span>Telur Asin dan Telur Pindang Otentik</span></div>
    </div>
    <p>&copy; <span id="tahun">2026</span> Zona Telur, Narmada, Lombok.</p>
  </footer>

  <!-- ========== KERANJANG (panel geser) ========== -->
  <div class="gelap" id="gelap" hidden></div>

  <aside class="keranjang" id="keranjang" aria-hidden="true" aria-label="Keranjang belanja">
    <div class="keranjang-kepala">
      <h2>Keranjang</h2>
      <button type="button" class="tombol-tutup" id="tutupKeranjang" aria-label="Tutup keranjang">✕</button>
    </div>

    <div class="keranjang-isi">
      <!-- Daftar barang diisi oleh script.js -->
      <div id="daftarBarang"></div>

      <!-- Tampilan setelah pesanan berhasil dikirim -->
      <div id="pesananSukses" class="pesanan-sukses" hidden>
        <div class="sukses-ikon" aria-hidden="true">✓</div>
        <h3>Pesanan terkirim!</h3>
        <p>Kode pesanan: <strong id="suksesKode"></strong></p>
        <p>Total: <strong id="suksesTotal"></strong></p>
        <p class="petunjuk" id="suksesInfo"></p>
        <button type="button" class="tombol lebar" id="pesanLagi">PESAN LAGI</button>
      </div>

      <form id="formPesan" class="form-pesan" novalidate>
        <div class="total"><span>Total</span><strong id="totalHarga">Rp0</strong></div>

        <label>Nama
          <input type="text" id="nama" autocomplete="name" required>
        </label>

        <label>Nomor WhatsApp
          <input type="tel" id="telepon" autocomplete="tel" inputmode="tel" placeholder="08xxxxxxxxxx" required>
        </label>

        <fieldset>
          <legend>Pengambilan</legend>
          <label class="pilihan"><input type="radio" name="ambil" value="Ambil sendiri" checked> Ambil sendiri</label>
          <label class="pilihan"><input type="radio" name="ambil" value="Diantar"> Diantar</label>
        </fieldset>

        <label id="barisAlamat" hidden>Alamat pengantaran
          <textarea id="alamat" rows="2" placeholder="Alamat lengkap. Ongkir dikonfirmasi admin."></textarea>
        </label>

        <fieldset>
          <legend>Pembayaran</legend>
          <label class="pilihan"><input type="radio" name="bayar" value="QRIS" checked> QRIS</label>
          <label class="pilihan"><input type="radio" name="bayar" value="Tunai"> Tunai</label>
        </fieldset>

        <label>Catatan
          <textarea id="catatan" rows="2" placeholder="Contoh: pre-order untuk hari Sabtu"></textarea>
        </label>

        <p class="pesan-error" id="pesanError" role="alert"></p>
        <button type="submit" class="tombol lebar">KIRIM PESANAN</button>
        <p class="petunjuk">Pesanan dianggap pasti setelah dikonfirmasi admin lewat WhatsApp.</p>
      </form>
    </div>
  </aside>

  <!-- ========== MODAL LOGIN / DAFTAR ========== -->
  <div class="modal" id="modalAuth" hidden>
    <div class="modal-kotak" role="dialog" aria-modal="true" aria-labelledby="judulAuth">
      <button type="button" class="tombol-tutup modal-tutup" data-tutup-modal aria-label="Tutup">✕</button>

      <img src="Assets/logo.png" alt="" class="modal-logo" width="64" height="64">
      <h2 id="judulAuth">Masuk ke Zona Telur</h2>

      <div class="tab-auth" role="tablist">
        <button type="button" role="tab" class="aktif" id="tabMasuk" aria-selected="true">Masuk</button>
        <button type="button" role="tab" id="tabDaftar" aria-selected="false">Daftar Member</button>
      </div>

      <!-- Form Masuk -->
      <form id="formMasuk" class="form-auth" novalidate>
        <label>Email atau username
          <input type="text" id="masukId" autocomplete="username" required>
        </label>
        <label>Kata sandi
          <input type="password" id="masukSandi" autocomplete="current-password" required>
        </label>

        <p class="pesan-error" id="errorMasuk" role="alert"></p>
        <button type="submit" class="tombol lebar">MASUK</button>
        <p class="petunjuk">Belum punya akun? <a href="#" id="keDaftar">Daftar sebagai member</a></p>
      </form>

      <!-- Form Daftar -->
      <form id="formDaftar" class="form-auth" novalidate hidden>
        <label>Nama lengkap
          <input type="text" id="daftarNama" autocomplete="name" required>
        </label>
        <label>Email
          <input type="email" id="daftarEmail" autocomplete="email" placeholder="nama@email.com" required>
        </label>
        <label>Nomor WhatsApp
          <input type="tel" id="daftarTelepon" autocomplete="tel" inputmode="tel" placeholder="08xxxxxxxxxx" required>
        </label>
        <label>Kata sandi
          <input type="password" id="daftarSandi" autocomplete="new-password" placeholder="Minimal 6 karakter" required>
        </label>
        <label>Ulangi kata sandi
          <input type="password" id="daftarSandi2" autocomplete="new-password" required>
        </label>

        <p class="pesan-error" id="errorDaftar" role="alert"></p>
        <button type="submit" class="tombol lebar">DAFTAR SEKARANG</button>
        <p class="petunjuk">Sudah punya akun? <a href="#" id="keMasuk">Masuk di sini</a></p>
      </form>
    </div>
  </div>

  <!-- Notifikasi kecil di bawah layar -->
  <div class="toast" id="toast" role="status" aria-live="polite"></div>

</body>
</html>
