<?php
require_once __DIR__ . '/includes/bootstrap.php';
$admin = halaman_wajib('admin'); // bukan admin -> dikembalikan ke beranda
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Admin - Zona Telur</title>
  <meta name="robots" content="noindex">
  <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
  <link rel="icon" type="image/png" href="Assets/favicon.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@600;700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="dash.css">
  <script src="api.js" defer></script>
  <script src="dash.js" defer></script>
  <script src="admin.js" defer></script>
</head>
<body>

  <header class="dash-bar">
    <a href="index.php" class="brand" aria-label="Zona Telur, ke beranda">
      <img src="Assets/logo.png" alt="Logo Zona Telur" width="52" height="52">
      <span class="brand-teks">ZONA TELUR<small>Dashboard Admin</small></span>
    </a>
    <div class="dash-aksi">
      <a class="tombol garis" href="index.php">LIHAT SITUS</a>
      <button type="button" class="tombol" id="tombolKeluar">KELUAR</button>
    </div>
  </header>

  <main class="dash">
    <div class="dash-sapa">
      <h1 id="sapa">Halo, <?= e($admin['nama']) ?></h1>
      <p>Pantau pesanan dan kelola member Zona Telur.</p>
    </div>

    <div class="stat-grid">
      <div class="stat sorot"><span>Pesanan baru</span><strong id="statBaru">0</strong></div>
      <div class="stat"><span>Total pesanan</span><strong id="statTotal">0</strong></div>
      <div class="stat"><span>Pendapatan</span><strong id="statPendapatan">Rp0</strong></div>
      <div class="stat"><span>Member</span><strong id="statMember">0</strong></div>
    </div>

    <div class="tab-bar" role="tablist">
      <button type="button" role="tab" data-tab="ringkasan">Ringkasan</button>
      <button type="button" role="tab" data-tab="pesanan">Pesanan</button>
      <button type="button" role="tab" data-tab="member">Member</button>
      <button type="button" role="tab" data-tab="penukaran" id="tabPenukaran">Penukaran Poin</button>
      <button type="button" role="tab" data-tab="akun">Akun</button>
    </div>

    <!-- Ringkasan -->
    <section class="tab-panel" id="tab-ringkasan">
      <div class="dua-kolom">
        <div class="panel">
          <h2>Produk terlaris</h2>
          <div id="terlaris"></div>
        </div>
        <div class="panel">
          <h2>Pesanan terbaru</h2>
          <div id="terbaru"></div>
        </div>
      </div>
      <p class="petunjuk">Pendapatan dihitung dari pesanan berstatus Selesai.</p>
    </section>

    <!-- Pesanan -->
    <section class="tab-panel" id="tab-pesanan" hidden>
      <div class="panel">
        <div class="panel-kepala">
          <h2>Semua pesanan</h2>
          <label>Status:
            <select id="filterStatus">
              <option value="">Semua</option>
              <option>Baru</option>
              <option>Diproses</option>
              <option>Selesai</option>
              <option>Dibatalkan</option>
            </select>
          </label>
        </div>
        <div class="tabel-bungkus">
          <table class="tabel">
            <thead>
              <tr><th>Tanggal</th><th>Pelanggan</th><th>Pesanan</th><th>Total</th><th>Pengambilan</th><th>Status</th></tr>
            </thead>
            <tbody id="isiPesanan"></tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- Member -->
    <section class="tab-panel" id="tab-member" hidden>
      <div class="panel">
        <div class="panel-kepala"><h2>Daftar member</h2></div>
        <div class="tabel-bungkus">
          <table class="tabel">
            <thead>
              <tr><th>Nama</th><th>Email</th><th>WhatsApp</th><th>Daftar</th><th>Pesanan</th><th>Poin</th><th></th></tr>
            </thead>
            <tbody id="isiMember"></tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- Penukaran poin -->
    <section class="tab-panel" id="tab-penukaran" hidden>
      <div class="panel">
        <div class="panel-kepala"><h2>Penukaran poin member</h2></div>
        <p class="petunjuk" style="text-align:left">Ubah status menjadi Selesai setelah bonus diberikan, atau Ditolak untuk mengembalikan poin member.</p>
        <div class="tabel-bungkus">
          <table class="tabel">
            <thead>
              <tr><th>Tanggal</th><th>Member</th><th>Bonus</th><th>Poin</th><th>Status</th></tr>
            </thead>
            <tbody id="isiPenukaran"></tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- Akun admin -->
    <section class="tab-panel" id="tab-akun" hidden>
      <div class="panel" style="max-width:460px">
        <h2>Ganti kata sandi</h2>
        <form class="form-dash" id="formSandi" novalidate>
          <label>Kata sandi lama
            <input type="password" id="sandiLama" autocomplete="current-password" required>
          </label>
          <label>Kata sandi baru
            <input type="password" id="sandiBaru" autocomplete="new-password" placeholder="Minimal 6 karakter" required>
          </label>
          <label>Ulangi kata sandi baru
            <input type="password" id="sandiBaru2" autocomplete="new-password" required>
          </label>
          <p class="pesan-error" id="errorSandi" role="alert"></p>
          <button type="submit" class="tombol">GANTI KATA SANDI</button>
        </form>
      </div>
    </section>
  </main>

  <div class="toast" id="toast" role="status" aria-live="polite"></div>
</body>
</html>
