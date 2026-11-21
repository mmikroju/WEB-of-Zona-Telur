<?php
require_once __DIR__ . '/includes/bootstrap.php';
$member = halaman_wajib('member'); // bukan member -> dikembalikan ke beranda
$daftarBonus = db()->query('SELECT kode, nama, gambar FROM produk WHERE aktif = 1 AND bisa_bonus = 1 ORDER BY urutan, id')->fetchAll();
$waKonfirmasi = NOMOR_WA;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Member - Zona Telur</title>
  <meta name="robots" content="noindex">
  <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
  <link rel="icon" type="image/png" href="Assets/favicon.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@600;700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="dash.css">
  <script>window.ZT_CONFIG = <?= json_encode(['poinTukar' => POIN_TUKAR, 'wa' => NOMOR_WA]) ?>;</script>
  <script src="api.js" defer></script>
  <script src="dash.js" defer></script>
  <script src="member.js" defer></script>
</head>
<body>

  <header class="dash-bar">
    <a href="index.php" class="brand" aria-label="Zona Telur, ke beranda">
      <img src="Assets/logo.png" alt="Logo Zona Telur" width="52" height="52">
      <span class="brand-teks">ZONA TELUR<small>Akun Member</small></span>
    </a>
    <div class="dash-aksi">
      <a class="tombol garis" href="index.php#menu">PESAN LAGI</a>
      <button type="button" class="tombol" id="tombolKeluar">KELUAR</button>
    </div>
  </header>

  <main class="dash">
    <div class="dash-sapa">
      <h1 id="sapa">Halo, <?= e(explode(' ', $member['nama'])[0]) ?>!</h1>
      <p>Lihat riwayat pesanan dan atur akunmu di sini.</p>
    </div>

    <div class="stat-grid">
      <div class="stat sorot"><span>Poin saya</span><strong id="statPoin">0</strong></div>
      <div class="stat"><span>Pesanan aktif</span><strong id="statAktif">0</strong></div>
      <div class="stat"><span>Total pesanan</span><strong id="statTotal">0</strong></div>
      <div class="stat"><span>Total belanja</span><strong id="statBelanja">Rp0</strong></div>
    </div>

    <div class="tab-bar" role="tablist">
      <button type="button" role="tab" data-tab="pesanan">Pesanan Saya</button>
      <button type="button" role="tab" data-tab="poin">Poin &amp; Bonus</button>
      <button type="button" role="tab" data-tab="profil">Profil</button>
    </div>

    <!-- Pesanan saya -->
    <section class="tab-panel" id="tab-pesanan">
      <div class="panel">
        <h2>Riwayat pesanan</h2>
        <div class="daftar-pesanan" id="daftarPesanan"></div>
      </div>
    </section>

    <!-- Poin & bonus -->
    <section class="tab-panel" id="tab-poin" hidden>
      <div class="dua-kolom">
        <div class="panel">
          <h2>Poin kamu</h2>
          <div class="poin-besar"><strong id="poinSaldo">0</strong><span>poin</span></div>
          <div class="bar-jalur"><div class="bar-isi" id="poinBar" style="width:0"></div></div>
          <p class="catatan-poin" id="poinKet"></p>
          <ul class="aturan">
            <li>Setiap pembelian 1 mika/tray = <?= POIN_PER_MIKA ?> poin.</li>
            <li>Poin masuk setelah pesananmu berstatus Selesai.</li>
            <li><?= POIN_TUKAR ?> poin = bonus 1 pcs: pilih salah satu produk bonus.</li>
          </ul>
          <p class="petunjuk" id="poinMenunggu" style="text-align:left"></p>
        </div>

        <div class="panel">
          <h2>Tukar poin</h2>
          <p class="petunjuk" style="text-align:left">Pilih bonus 1 pcs seharga <?= POIN_TUKAR ?> poin:</p>
          <div class="pilih-bonus">
<?php foreach ($daftarBonus as $b): ?>
            <button type="button" class="bonus" data-produk="<?= e($b['kode']) ?>" data-nama="<?= e($b['nama']) ?>">
              <img src="<?= e($b['gambar']) ?>" alt="">
              <b><?= e($b['nama']) ?></b><span>1 pcs · <?= POIN_TUKAR ?> poin</span>
            </button>
<?php endforeach; ?>
          </div>
          <p class="pesan-error" id="errorTukar" role="alert"></p>
        </div>
      </div>

      <div class="panel">
        <h2>Riwayat penukaran</h2>
        <div class="daftar-pesanan" id="daftarTukar"></div>
      </div>
    </section>

    <!-- Profil -->
    <section class="tab-panel" id="tab-profil" hidden>
      <div class="dua-kolom">
        <div class="panel">
          <h2>Data diri</h2>
          <form class="form-dash" id="formProfil" novalidate>
            <label>Nama lengkap
              <input type="text" id="profilNama" autocomplete="name" required>
            </label>
            <label>Email
              <input type="email" id="profilEmail" readonly>
            </label>
            <label>Nomor WhatsApp
              <input type="tel" id="profilTelepon" autocomplete="tel" inputmode="tel" required>
            </label>
            <p class="pesan-error" id="errorProfil" role="alert"></p>
            <button type="submit" class="tombol">SIMPAN</button>
          </form>
        </div>

        <div class="panel">
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
      </div>
    </section>
  </main>

  <div class="toast" id="toast" role="status" aria-live="polite"></div>
</body>
</html>
