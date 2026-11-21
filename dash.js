// =========================================================
// Zona Telur - dash.js
// Fungsi bersama untuk halaman dashboard (admin.php & member.php).
// Data diambil dari server lewat api() (lihat api.js).
// =========================================================

function formatRupiah(angka) {
  return "Rp" + Number(angka).toLocaleString("id-ID");
}

function formatTanggal(iso) {
  return new Date(iso).toLocaleDateString("id-ID", {
    day: "numeric", month: "short", year: "numeric",
    hour: "2-digit", minute: "2-digit",
  });
}

// Notifikasi kecil
function tampilkanToast(teks) {
  const toast = document.getElementById("toast");
  toast.textContent = teks;
  toast.classList.add("tampil");
  setTimeout(function () { toast.classList.remove("tampil"); }, 2200);
}

// Keluar: hapus sesi di server, lalu kembali ke beranda
async function keluar() {
  try { await api("api/logout.php", {}); } catch (e) {}
  window.location.href = "index.php";
}

// Jika sesi habis / tidak berhak, server membalas 401/403 -> kembali ke beranda
function tanganiGalat(err) {
  if (err.status === 401 || err.status === 403) {
    window.location.href = "index.php";
    return;
  }
  tampilkanToast(err.message);
}

// Pindah tab: tombol [data-tab="x"] menampilkan panel #tab-x
function aktifkanTab() {
  const tombol = document.querySelectorAll("[data-tab]");
  const panel = document.querySelectorAll(".tab-panel");

  function pilih(nama) {
    tombol.forEach(function (t) {
      const aktif = t.dataset.tab === nama;
      t.classList.toggle("aktif", aktif);
      t.setAttribute("aria-selected", String(aktif));
    });
    panel.forEach(function (p) { p.hidden = p.id !== "tab-" + nama; });
  }

  tombol.forEach(function (t) {
    t.addEventListener("click", function () { pilih(t.dataset.tab); });
  });
  pilih(tombol[0].dataset.tab);
}

// Membuat elemen dengan teks aman (tanpa innerHTML)
function el(tag, kelas, teks) {
  const e = document.createElement(tag);
  if (kelas) e.className = kelas;
  if (teks !== undefined) e.textContent = teks;
  return e;
}

// Lencana status pesanan / penukaran
function lencanaStatus(status) {
  return el("span", "status status-" + status.toLowerCase(), status);
}

// "Telur Pindang ×2, Telur Asin ×1"
function ringkasItem(items) {
  return items.map(function (i) { return i.nama + " ×" + i.jumlah; }).join(", ");
}

// Form ganti kata sandi (dipakai admin dan member)
function pasangFormSandi() {
  const form = document.getElementById("formSandi");
  if (!form) return;

  form.addEventListener("submit", async function (e) {
    e.preventDefault();
    const error = document.getElementById("errorSandi");
    const lama = document.getElementById("sandiLama");
    const baru = document.getElementById("sandiBaru");
    const baru2 = document.getElementById("sandiBaru2");
    error.textContent = "";

    if (baru.value.length < 6) { error.textContent = "Kata sandi baru minimal 6 karakter."; return; }
    if (baru.value !== baru2.value) { error.textContent = "Kata sandi baru tidak sama."; return; }

    try {
      await api("api/ganti_sandi.php", { lama: lama.value, baru: baru.value });
      form.reset();
      tampilkanToast("Kata sandi berhasil diganti.");
    } catch (err) {
      error.textContent = err.message;
    }
  });
}
