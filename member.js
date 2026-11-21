// =========================================================
// Zona Telur - member.js
// Dashboard member: riwayat pesanan, poin & bonus, profil.
// Akses halaman dijaga server (member.php); data dari api/member/*.
// =========================================================

const POIN_TUKAR = window.ZT_CONFIG.poinTukar;
const NOMOR_WA = window.ZT_CONFIG.wa;

let data = null; // data dashboard dari server

document.getElementById("tombolKeluar").addEventListener("click", keluar);
aktifkanTab();
pasangFormSandi();
muatData();

async function muatData() {
  try {
    data = await api("api/member/ringkasan.php");
    isiProfil();
    tampilkanRiwayat();
    tampilkanPoin();
  } catch (err) {
    tanganiGalat(err);
  }
}

function isiProfil() {
  document.getElementById("profilNama").value = data.user.nama;
  document.getElementById("profilEmail").value = data.user.email || "";
  document.getElementById("profilTelepon").value = data.user.telepon;
}

// ---------- Riwayat pesanan ----------
function tampilkanRiwayat() {
  const wadah = document.getElementById("daftarPesanan");
  wadah.innerHTML = "";

  document.getElementById("statAktif").textContent = data.stat.aktif;
  document.getElementById("statTotal").textContent = data.stat.total;
  document.getElementById("statBelanja").textContent = formatRupiah(data.stat.belanja);

  if (data.pesanan.length === 0) {
    const kosong = el("div", "kosong-blok");
    kosong.appendChild(el("p", "", "Kamu belum punya pesanan."));
    const link = el("a", "tombol", "MULAI PESAN");
    link.href = "index.php#menu";
    kosong.appendChild(link);
    wadah.appendChild(kosong);
    return;
  }

  data.pesanan.forEach(function (p) {
    const kartu = el("div", "pesanan");

    const atas = el("div", "pesanan-atas");
    atas.appendChild(el("small", "", formatTanggal(p.dibuat) + " · " + p.kode));
    atas.appendChild(lencanaStatus(p.status));
    kartu.appendChild(atas);

    const ul = el("ul");
    p.items.forEach(function (i) {
      ul.appendChild(el("li", "", i.nama + " ×" + i.jumlah + " " + i.satuan + " (" + formatRupiah(i.harga * i.jumlah) + ")"));
    });
    kartu.appendChild(ul);

    let info = p.ambil + " · " + p.bayar;
    if (p.alamat) info += " · " + p.alamat;
    if (p.status === "Selesai") info += " · +" + p.poin + " poin";
    else if (p.status !== "Dibatalkan" && p.poin > 0) info += " · " + p.poin + " poin (setelah selesai)";

    const bawah = el("div", "pesanan-bawah");
    bawah.appendChild(el("span", "", info));
    bawah.appendChild(el("strong", "", formatRupiah(p.total)));
    kartu.appendChild(bawah);

    wadah.appendChild(kartu);
  });
}

// ---------- Poin & bonus ----------
function tampilkanPoin() {
  const poin = data.poin;

  document.getElementById("statPoin").textContent = poin.saldo;
  document.getElementById("poinSaldo").textContent = poin.saldo;
  document.getElementById("poinBar").style.width = Math.min(poin.saldo, POIN_TUKAR) / POIN_TUKAR * 100 + "%";

  document.getElementById("poinKet").textContent = poin.saldo >= POIN_TUKAR
    ? "Kamu bisa menukar " + Math.floor(poin.saldo / POIN_TUKAR) + " bonus. Pilih di sebelah."
    : "Kurang " + (POIN_TUKAR - poin.saldo) + " poin lagi untuk bonus 1 pcs.";

  document.getElementById("poinMenunggu").textContent = poin.menunggu > 0
    ? poin.menunggu + " poin menunggu pesananmu selesai."
    : "";

  document.querySelectorAll(".bonus").forEach(function (t) {
    t.disabled = poin.saldo < POIN_TUKAR;
  });

  tampilkanRiwayatTukar();
}

function tampilkanRiwayatTukar() {
  const wadah = document.getElementById("daftarTukar");
  wadah.innerHTML = "";

  if (data.penukaran.length === 0) {
    wadah.appendChild(el("p", "kosong-blok", "Belum ada penukaran poin."));
    return;
  }

  data.penukaran.forEach(function (t) {
    const kartu = el("div", "pesanan");

    const atas = el("div", "pesanan-atas");
    atas.appendChild(el("small", "", formatTanggal(t.dibuat) + " · " + t.kode));
    atas.appendChild(lencanaStatus(t.status));
    kartu.appendChild(atas);

    kartu.appendChild(el("p", "", "Bonus 1 pcs " + t.produk));

    const bawah = el("div", "pesanan-bawah");
    let ket = "−" + t.poin + " poin";
    if (t.status === "Ditolak") ket = "Ditolak · poin dikembalikan";
    if (t.status === "Selesai") ket += " · bonus sudah diberikan";
    bawah.appendChild(el("span", "", ket));

    if (t.status === "Diajukan") {
      const pesan = "Halo Zona Telur, saya " + data.user.nama + " ingin menukar " + t.poin +
        " poin dengan bonus 1 pcs " + t.produk + " (kode " + t.kode + ").";
      const link = el("a", "tautan-wa", "Konfirmasi via WhatsApp");
      link.href = "https://wa.me/" + NOMOR_WA + "?text=" + encodeURIComponent(pesan);
      link.target = "_blank";
      link.rel = "noopener";
      bawah.appendChild(link);
    }

    kartu.appendChild(bawah);
    wadah.appendChild(kartu);
  });
}

document.querySelectorAll(".bonus").forEach(function (tombol) {
  tombol.addEventListener("click", async function () {
    const error = document.getElementById("errorTukar");
    error.textContent = "";

    if (!confirm("Tukar " + POIN_TUKAR + " poin dengan bonus 1 pcs " + tombol.dataset.nama + "?")) return;

    document.querySelectorAll(".bonus").forEach(function (t) { t.disabled = true; });
    try {
      await api("api/member/tukar.php", { produk: tombol.dataset.produk });
      tampilkanToast("Penukaran diajukan. Admin akan memprosesnya.");
    } catch (err) {
      if (err.status === 401 || err.status === 403) return tanganiGalat(err);
      error.textContent = err.message;
    }
    muatData(); // saldo dan riwayat selalu mengikuti server
  });
});

// ---------- Simpan profil ----------
document.getElementById("formProfil").addEventListener("submit", async function (e) {
  e.preventDefault();
  const error = document.getElementById("errorProfil");
  const nama = document.getElementById("profilNama").value.trim();
  const telepon = document.getElementById("profilTelepon").value.trim().replace(/[\s-]/g, "");
  error.textContent = "";

  if (nama.length < 3) { error.textContent = "Nama minimal 3 huruf."; return; }
  if (!/^(08|62)[0-9]{8,13}$/.test(telepon)) {
    error.textContent = "Nomor WhatsApp tidak valid. Contoh: 08123456789";
    return;
  }

  try {
    await api("api/member/profil.php", { nama: nama, telepon: telepon });
    await muatData();
    document.getElementById("sapa").textContent = "Halo, " + nama.split(" ")[0] + "!";
    tampilkanToast("Profil disimpan.");
  } catch (err) {
    error.textContent = err.message;
  }
});
