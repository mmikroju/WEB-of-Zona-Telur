// =========================================================
// Zona Telur - admin.js
// Dashboard admin: ringkasan, kelola pesanan, member, penukaran poin.
// Akses halaman dijaga server (admin.php); data dari api/admin/data.php.
// =========================================================

const STATUS_PESANAN = ["Baru", "Diproses", "Selesai", "Dibatalkan"];
const STATUS_TUKAR = ["Diajukan", "Selesai", "Ditolak"];

let data = null; // seluruh data dashboard dari server

document.getElementById("tombolKeluar").addEventListener("click", keluar);
document.getElementById("filterStatus").addEventListener("change", tampilkanPesanan);
aktifkanTab();
pasangFormSandi();
muatData();

async function muatData() {
  try {
    data = await api("api/admin/data.php");
    tampilkanSemua();
  } catch (err) {
    tanganiGalat(err);
  }
}

function tampilkanSemua() {
  tampilkanStatistik();
  tampilkanTerlaris();
  tampilkanTerbaru();
  tampilkanPesanan();
  tampilkanMember();
  tampilkanPenukaran();
}

// Mengirim perubahan ke server, lalu memuat ulang data
async function kirimUbah(url, isi, pesanBerhasil) {
  try {
    await api(url, isi);
    await muatData();
    tampilkanToast(pesanBerhasil);
  } catch (err) {
    tanganiGalat(err);
    muatData(); // kembalikan tampilan ke kondisi di server
  }
}

// ---------- Statistik ----------
function tampilkanStatistik() {
  document.getElementById("statBaru").textContent = data.stat.baru;
  document.getElementById("statTotal").textContent = data.stat.total;
  document.getElementById("statPendapatan").textContent = formatRupiah(data.stat.pendapatan);
  document.getElementById("statMember").textContent = data.stat.member;
}

// ---------- Produk terlaris ----------
function tampilkanTerlaris() {
  const wadah = document.getElementById("terlaris");
  wadah.innerHTML = "";

  if (data.terlaris.length === 0) {
    wadah.appendChild(el("p", "kosong-blok", "Belum ada data penjualan."));
    return;
  }

  const maks = data.terlaris[0].jumlah;
  data.terlaris.forEach(function (t) {
    const baris = el("div", "bar-baris");
    const atas = el("div");
    atas.appendChild(el("span", "", t.nama));
    atas.appendChild(el("span", "", String(t.jumlah)));
    const jalur = el("div", "bar-jalur");
    const isi = el("div", "bar-isi");
    isi.style.width = (t.jumlah / maks) * 100 + "%";
    jalur.appendChild(isi);
    baris.appendChild(atas);
    baris.appendChild(jalur);
    wadah.appendChild(baris);
  });
}

// ---------- 5 pesanan terbaru ----------
function tampilkanTerbaru() {
  const wadah = document.getElementById("terbaru");
  wadah.innerHTML = "";

  const daftar = data.pesanan.slice(0, 5); // server sudah mengurutkan terbaru dulu
  if (daftar.length === 0) {
    wadah.appendChild(el("p", "kosong-blok", "Belum ada pesanan masuk."));
    return;
  }

  daftar.forEach(function (p) {
    const baris = el("div", "bar-baris");
    const atas = el("div");
    atas.appendChild(el("span", "", p.nama + " · " + formatRupiah(p.total)));
    atas.appendChild(lencanaStatus(p.status));
    baris.appendChild(atas);
    baris.appendChild(el("small", "", ringkasItem(p.items)));
    wadah.appendChild(baris);
  });
}

// ---------- Tabel pesanan ----------
function tampilkanPesanan() {
  if (!data) return;
  const isi = document.getElementById("isiPesanan");
  const filter = document.getElementById("filterStatus").value;
  isi.innerHTML = "";

  let daftar = data.pesanan;
  if (filter !== "") {
    daftar = daftar.filter(function (p) { return p.status === filter; });
  }

  if (daftar.length === 0) {
    const tr = el("tr");
    const td = el("td", "kosong-tabel", "Tidak ada pesanan.");
    td.colSpan = 6;
    tr.appendChild(td);
    isi.appendChild(tr);
    return;
  }

  daftar.forEach(function (p) {
    const tr = el("tr");

    const tdTgl = el("td", "nowrap", formatTanggal(p.dibuat));
    tdTgl.appendChild(el("small", "", p.kode));
    tr.appendChild(tdTgl);

    const tdPel = el("td");
    tdPel.appendChild(el("b", "", p.nama));
    tdPel.appendChild(el("small", "", p.telepon));
    tdPel.appendChild(el("small", "", p.member ? "Member" : "Tamu"));
    tr.appendChild(tdPel);

    const tdItem = el("td", "", ringkasItem(p.items));
    if (p.catatan) tdItem.appendChild(el("small", "", "Catatan: " + p.catatan));
    tr.appendChild(tdItem);

    tr.appendChild(el("td", "nowrap", formatRupiah(p.total)));

    const tdAmbil = el("td", "", p.ambil);
    if (p.alamat) tdAmbil.appendChild(el("small", "", p.alamat));
    tdAmbil.appendChild(el("small", "", "Bayar: " + p.bayar));
    tr.appendChild(tdAmbil);

    tr.appendChild(selectStatus(STATUS_PESANAN, p.status, function (baru) {
      kirimUbah("api/admin/status_pesanan.php", { id: p.id, status: baru }, "Status diubah menjadi " + baru + ".");
    }));

    isi.appendChild(tr);
  });
}

// <td> berisi dropdown status
function selectStatus(daftarStatus, statusSekarang, saatUbah) {
  const td = el("td");
  const pilih = el("select");
  daftarStatus.forEach(function (s) {
    const opsi = el("option", "", s);
    opsi.value = s;
    if (s === statusSekarang) opsi.selected = true;
    pilih.appendChild(opsi);
  });
  pilih.addEventListener("change", function () { saatUbah(pilih.value); });
  td.appendChild(pilih);
  return td;
}

// ---------- Tabel member ----------
function tampilkanMember() {
  const isi = document.getElementById("isiMember");
  isi.innerHTML = "";

  if (data.member.length === 0) {
    const tr = el("tr");
    const td = el("td", "kosong-tabel", "Belum ada member yang mendaftar.");
    td.colSpan = 7;
    tr.appendChild(td);
    isi.appendChild(tr);
    return;
  }

  data.member.forEach(function (m) {
    const tr = el("tr");
    tr.appendChild(el("td", "", m.nama));
    tr.appendChild(el("td", "", m.email || ""));
    tr.appendChild(el("td", "", m.telepon));
    tr.appendChild(el("td", "nowrap", new Date(m.dibuat).toLocaleDateString("id-ID", {
      day: "numeric", month: "short", year: "numeric",
    })));
    tr.appendChild(el("td", "", String(m.jumlah_pesanan)));
    tr.appendChild(el("td", "nowrap", m.saldo + " poin"));

    const tdAksi = el("td");
    const hapus = el("button", "tombol-hapus", "Hapus");
    hapus.type = "button";
    hapus.addEventListener("click", function () {
      if (confirm("Hapus member " + m.nama + "? Riwayat pesanannya tetap tersimpan.")) {
        kirimUbah("api/admin/hapus_member.php", { id: m.id }, "Member dihapus.");
      }
    });
    tdAksi.appendChild(hapus);
    tr.appendChild(tdAksi);

    isi.appendChild(tr);
  });
}

// ---------- Penukaran poin ----------
function tampilkanPenukaran() {
  const isi = document.getElementById("isiPenukaran");
  isi.innerHTML = "";

  const menunggu = data.penukaran.filter(function (t) { return t.status === "Diajukan"; }).length;
  document.getElementById("tabPenukaran").textContent =
    menunggu > 0 ? "Penukaran Poin (" + menunggu + ")" : "Penukaran Poin";

  if (data.penukaran.length === 0) {
    const tr = el("tr");
    const td = el("td", "kosong-tabel", "Belum ada penukaran poin.");
    td.colSpan = 5;
    tr.appendChild(td);
    isi.appendChild(tr);
    return;
  }

  data.penukaran.forEach(function (t) {
    const tr = el("tr");

    const tdTgl = el("td", "nowrap", formatTanggal(t.dibuat));
    tdTgl.appendChild(el("small", "", t.kode));
    tr.appendChild(tdTgl);

    const tdMember = el("td");
    tdMember.appendChild(el("b", "", t.nama));
    tdMember.appendChild(el("small", "", t.telepon));
    tr.appendChild(tdMember);

    tr.appendChild(el("td", "", "1 pcs " + t.produk));
    tr.appendChild(el("td", "nowrap", t.poin + " poin"));

    tr.appendChild(selectStatus(STATUS_TUKAR, t.status, function (baru) {
      kirimUbah("api/admin/status_tukar.php", { id: t.id, status: baru }, "Status penukaran diubah menjadi " + baru + ".");
    }));

    isi.appendChild(tr);
  });
}
