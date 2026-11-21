// =========================================================
// Zona Telur - auth.js
// Login (admin & member otomatis dikenali), daftar member,
// dan menu akun di header. Semua data dikelola server (PHP + MySQL);
// di sini hanya tampilan dan pemanggilan API.
// =========================================================

// Pengguna saat ini ditulis oleh server ke window.ZT_USER (null = belum login)
let penggunaSekarang = window.ZT_USER || null;

// ---------- Elemen ----------
const tombolMasuk = document.getElementById("tombolMasuk");
const akunMenu = document.getElementById("akunMenu");
const akunChip = document.getElementById("akunChip");
const akunAvatar = document.getElementById("akunAvatar");
const akunNama = document.getElementById("akunNama");
const akunPeran = document.getElementById("akunPeran");
const akunDropdown = document.getElementById("akunDropdown");
const linkDashboard = document.getElementById("linkDashboard");
const tombolKeluar = document.getElementById("tombolKeluar");

const modalAuth = document.getElementById("modalAuth");
const judulAuth = document.getElementById("judulAuth");
const tabMasuk = document.getElementById("tabMasuk");
const tabDaftar = document.getElementById("tabDaftar");
const formMasuk = document.getElementById("formMasuk");
const formDaftar = document.getElementById("formDaftar");
const errorMasuk = document.getElementById("errorMasuk");
const errorDaftar = document.getElementById("errorDaftar");

// ---------- Tampilan akun di header ----------
function perbaruiTampilanAkun() {
  const user = penggunaSekarang;
  window.ZT_USER = user;

  if (!user) {
    tombolMasuk.hidden = false;
    akunMenu.hidden = true;
    return;
  }

  tombolMasuk.hidden = true;
  akunMenu.hidden = false;
  akunAvatar.textContent = user.nama.trim().charAt(0);
  akunNama.textContent = user.nama;
  akunPeran.textContent = user.peran === "admin" ? "Admin" : "Member";
  akunPeran.classList.toggle("admin", user.peran === "admin");
  linkDashboard.href = user.peran === "admin" ? "admin.php" : "member.php";
  tutupDropdown();

  // Member: isi otomatis data di form pesanan (fungsi ada di script.js)
  if (typeof isiDataMember === "function") isiDataMember();
}

function tutupDropdown() {
  akunDropdown.hidden = true;
  akunChip.setAttribute("aria-expanded", "false");
}

akunChip.addEventListener("click", function () {
  const buka = akunDropdown.hidden;
  akunDropdown.hidden = !buka;
  akunChip.setAttribute("aria-expanded", String(buka));
});

document.addEventListener("click", function (e) {
  if (!akunMenu.contains(e.target)) tutupDropdown();
});

// ---------- Modal ----------
function bukaModal(modal) {
  modal.hidden = false;
  document.body.style.overflow = "hidden";
}

function tutupSemuaModal() {
  modalAuth.hidden = true;
  document.body.style.overflow = "";
}

document.querySelectorAll("[data-tutup-modal]").forEach(function (tombol) {
  tombol.addEventListener("click", tutupSemuaModal);
});

modalAuth.addEventListener("click", function (e) {
  if (e.target === modalAuth) tutupSemuaModal(); // klik area gelap
});

document.addEventListener("keydown", function (e) {
  if (e.key === "Escape") tutupSemuaModal();
});

// Pindah tab Masuk / Daftar
function pilihTab(nama) {
  const masuk = nama === "masuk";
  formMasuk.hidden = !masuk;
  formDaftar.hidden = masuk;
  tabMasuk.classList.toggle("aktif", masuk);
  tabDaftar.classList.toggle("aktif", !masuk);
  tabMasuk.setAttribute("aria-selected", String(masuk));
  tabDaftar.setAttribute("aria-selected", String(!masuk));
  judulAuth.textContent = masuk ? "Masuk ke Zona Telur" : "Daftar Member Baru";
  errorMasuk.textContent = "";
  errorDaftar.textContent = "";
}

tabMasuk.addEventListener("click", function () { pilihTab("masuk"); });
tabDaftar.addEventListener("click", function () { pilihTab("daftar"); });
document.getElementById("keDaftar").addEventListener("click", function (e) {
  e.preventDefault();
  pilihTab("daftar");
});
document.getElementById("keMasuk").addEventListener("click", function (e) {
  e.preventDefault();
  pilihTab("masuk");
});

tombolMasuk.addEventListener("click", function () {
  pilihTab("masuk");
  bukaModal(modalAuth);
  document.getElementById("masukId").focus();
});

// Mengunci tombol submit selama menunggu server
function kunciTombol(form, kunci, teksAsli) {
  const tombol = form.querySelector('button[type="submit"]');
  if (kunci) {
    tombol.dataset.teks = tombol.textContent;
    tombol.textContent = "MOHON TUNGGU...";
  } else {
    tombol.textContent = tombol.dataset.teks || teksAsli;
  }
  tombol.disabled = kunci;
}

// ---------- Daftar member ----------
formDaftar.addEventListener("submit", async function (e) {
  e.preventDefault();

  const nama = document.getElementById("daftarNama");
  const email = document.getElementById("daftarEmail");
  const telepon = document.getElementById("daftarTelepon");
  const sandi = document.getElementById("daftarSandi");
  const sandi2 = document.getElementById("daftarSandi2");

  [nama, email, telepon, sandi, sandi2].forEach(function (el) {
    el.classList.remove("salah");
  });
  errorDaftar.textContent = "";

  function gagal(el, pesan) {
    el.classList.add("salah");
    errorDaftar.textContent = pesan;
    el.focus();
  }

  // Pemeriksaan cepat di browser. Server tetap memeriksa ulang semuanya.
  const teleponBersih = telepon.value.trim().replace(/[\s-]/g, "");
  if (nama.value.trim().length < 3) return gagal(nama, "Nama minimal 3 huruf.");
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())) {
    return gagal(email, "Format email tidak valid.");
  }
  if (!/^(08|62)[0-9]{8,13}$/.test(teleponBersih)) {
    return gagal(telepon, "Nomor WhatsApp tidak valid. Contoh: 08123456789");
  }
  if (sandi.value.length < 6) return gagal(sandi, "Kata sandi minimal 6 karakter.");
  if (sandi.value !== sandi2.value) return gagal(sandi2, "Kata sandi tidak sama.");

  kunciTombol(formDaftar, true);
  try {
    const hasil = await api("api/register.php", {
      nama: nama.value.trim(),
      email: email.value.trim(),
      telepon: teleponBersih,
      sandi: sandi.value,
    });
    formDaftar.reset();
    penggunaSekarang = hasil.user;
    perbaruiTampilanAkun();
    tutupSemuaModal();
    tampilkanToast("Selamat datang, " + hasil.user.nama + "!");
  } catch (err) {
    errorDaftar.textContent = err.message;
  } finally {
    kunciTombol(formDaftar, false, "DAFTAR SEKARANG");
  }
});

// ---------- Masuk ----------
formMasuk.addEventListener("submit", async function (e) {
  e.preventDefault();

  const idInput = document.getElementById("masukId");
  const sandiInput = document.getElementById("masukSandi");
  idInput.classList.remove("salah");
  sandiInput.classList.remove("salah");
  errorMasuk.textContent = "";

  if (idInput.value.trim() === "" || sandiInput.value === "") {
    errorMasuk.textContent = "Isi email/username dan kata sandi.";
    return;
  }

  kunciTombol(formMasuk, true);
  try {
    const hasil = await api("api/login.php", {
      id: idInput.value.trim(),
      sandi: sandiInput.value,
    });
    formMasuk.reset();
    penggunaSekarang = hasil.user;

    if (hasil.user.peran === "admin") {
      window.location.href = "admin.php"; // admin langsung ke dashboard
      return;
    }
    perbaruiTampilanAkun();
    tutupSemuaModal();
    tampilkanToast("Berhasil masuk sebagai Member.");
  } catch (err) {
    sandiInput.classList.add("salah");
    errorMasuk.textContent = err.message;
  } finally {
    kunciTombol(formMasuk, false, "MASUK");
  }
});

// ---------- Keluar ----------
tombolKeluar.addEventListener("click", async function () {
  try {
    await api("api/logout.php", {});
  } catch (e) {
    // tetap lanjut: halaman dimuat ulang di bawah
  }
  // Muat ulang agar token CSRF dan sesi baru dipakai
  window.location.reload();
});

// ---------- Mulai ----------
perbaruiTampilanAkun();
