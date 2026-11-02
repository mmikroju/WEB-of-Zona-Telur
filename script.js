// =========================================================
// Zona Telur - script.js
// Materi yang dipakai: variabel (const/let), array, fungsi,
// DOM, event (addEventListener), if/else, loop, template string
// =========================================================

const NOMOR_WA = "6289529399731"; // format internasional, tanpa tanda +

// Keranjang disimpan dalam array. Setiap barang berbentuk:
// { id, nama, harga, satuan, gambar, jumlah }
let keranjang = [];

// ---------- Mengambil elemen dari HTML ----------
const tombolMenu = document.getElementById("tombolMenu");
const navigasi = document.getElementById("navigasi");
const tombolKeranjang = document.getElementById("tombolKeranjang");
const tutupKeranjang = document.getElementById("tutupKeranjang");
const panelKeranjang = document.getElementById("keranjang");
const layarGelap = document.getElementById("gelap");
const jumlahKeranjang = document.getElementById("jumlahKeranjang");
const daftarBarang = document.getElementById("daftarBarang");
const totalHarga = document.getElementById("totalHarga");
const formPesan = document.getElementById("formPesan");
const barisAlamat = document.getElementById("barisAlamat");
const pesanError = document.getElementById("pesanError");
const toast = document.getElementById("toast");

// ---------- Fungsi bantu ----------

// Mengubah angka 12000 menjadi "Rp12.000"
function formatRupiah(angka) {
  return "Rp" + angka.toLocaleString("id-ID");
}

// Menampilkan notifikasi kecil selama 2 detik
function tampilkanToast(teks) {
  toast.textContent = teks;
  toast.classList.add("tampil");
  setTimeout(function () {
    toast.classList.remove("tampil");
  }, 2000);
}

// ---------- 1. Tahun otomatis di footer ----------
document.getElementById("tahun").textContent = new Date().getFullYear();

// ---------- 2. Menu hamburger (untuk HP) ----------
tombolMenu.addEventListener("click", function () {
  navigasi.classList.toggle("buka");
});

// Tutup menu setelah salah satu link diklik
const linkNavigasi = navigasi.querySelectorAll("a");
linkNavigasi.forEach(function (link) {
  link.addEventListener("click", function () {
    navigasi.classList.remove("buka");
  });
});

// ---------- 3. Buka dan tutup keranjang ----------
function bukaKeranjang() {
  panelKeranjang.classList.add("buka");
  panelKeranjang.setAttribute("aria-hidden", "false");
  layarGelap.hidden = false;
}

function tutupPanel() {
  panelKeranjang.classList.remove("buka");
  panelKeranjang.setAttribute("aria-hidden", "true");
  layarGelap.hidden = true;
}

tombolKeranjang.addEventListener("click", bukaKeranjang);
tutupKeranjang.addEventListener("click", tutupPanel);
layarGelap.addEventListener("click", tutupPanel);

// Tombol Escape juga menutup keranjang
document.addEventListener("keydown", function (e) {
  if (e.key === "Escape") {
    tutupPanel();
  }
});

// ---------- 4. Kartu menu: tombol - + dan + KERANJANG ----------
const semuaKartu = document.querySelectorAll(".kartu-menu");

semuaKartu.forEach(function (kartu) {
  const angka = kartu.querySelector(".angka");
  const tombolKurang = kartu.querySelector(".kurang");
  const tombolTambah = kartu.querySelector(".tambah");
  const tombolMasuk = kartu.querySelector(".masuk-keranjang");

  tombolKurang.addEventListener("click", function () {
    let jumlah = Number(angka.textContent);
    if (jumlah > 1) {
      angka.textContent = jumlah - 1;
    }
  });

  tombolTambah.addEventListener("click", function () {
    let jumlah = Number(angka.textContent);
    if (jumlah < 99) {
      angka.textContent = jumlah + 1;
    }
  });

  tombolMasuk.addEventListener("click", function () {
    // Data produk dibaca dari atribut data-* di HTML
    const produk = {
      id: kartu.dataset.id,
      nama: kartu.dataset.nama,
      harga: Number(kartu.dataset.harga),
      satuan: kartu.dataset.satuan,
      gambar: kartu.dataset.gambar,
    };
    const jumlah = Number(angka.textContent);

    masukKeranjang(produk, jumlah);
    angka.textContent = 1; // kembalikan ke 1
    tampilkanToast(produk.nama + " ditambahkan ke keranjang");
  });
});

// ---------- 5. Mengelola isi keranjang ----------
function masukKeranjang(produk, jumlah) {
  // Cek apakah produk sudah ada di keranjang
  let sudahAda = null;
  for (let i = 0; i < keranjang.length; i++) {
    if (keranjang[i].id === produk.id) {
      sudahAda = keranjang[i];
    }
  }

  if (sudahAda) {
    sudahAda.jumlah = sudahAda.jumlah + jumlah; // tambah jumlahnya
  } else {
    produk.jumlah = jumlah;
    keranjang.push(produk); // barang baru
  }

  tampilkanKeranjang();
}

function ubahJumlah(id, perubahan) {
  for (let i = 0; i < keranjang.length; i++) {
    if (keranjang[i].id === id) {
      keranjang[i].jumlah = keranjang[i].jumlah + perubahan;

      // Jika jumlah jadi 0, hapus barang dari array
      if (keranjang[i].jumlah <= 0) {
        keranjang.splice(i, 1);
      }
      break;
    }
  }
  tampilkanKeranjang();
}

function hitungTotal() {
  let total = 0;
  for (let i = 0; i < keranjang.length; i++) {
    total = total + keranjang[i].harga * keranjang[i].jumlah;
  }
  return total;
}

// Menggambar ulang isi keranjang di layar
function tampilkanKeranjang() {
  if (keranjang.length === 0) {
    daftarBarang.innerHTML = '<p class="kosong">Keranjang masih kosong.<br>Pilih menu dulu, ya.</p>';
  } else {
    let html = "";
    for (let i = 0; i < keranjang.length; i++) {
      const b = keranjang[i];
      html += `
        <div class="barang">
          <img src="${b.gambar}" alt="">
          <div>
            <b>${b.nama}</b>
            <small>${formatRupiah(b.harga)} / ${b.satuan}</small>
          </div>
          <div class="qty">
            <button type="button" data-id="${b.id}" data-ubah="-1" aria-label="Kurangi">−</button>
            <span>${b.jumlah}</span>
            <button type="button" data-id="${b.id}" data-ubah="1" aria-label="Tambah">+</button>
          </div>
        </div>`;
    }
    daftarBarang.innerHTML = html;
  }

  // Perbarui total dan angka di tombol keranjang
  totalHarga.textContent = formatRupiah(hitungTotal());

  let jumlahSemua = 0;
  for (let i = 0; i < keranjang.length; i++) {
    jumlahSemua = jumlahSemua + keranjang[i].jumlah;
  }
  jumlahKeranjang.textContent = jumlahSemua;
}

// Tombol - dan + di dalam keranjang (dibuat ulang oleh JS,
// jadi klik dibaca dari elemen induknya)
daftarBarang.addEventListener("click", function (e) {
  const tombol = e.target.closest("button");
  if (tombol && tombol.dataset.id) {
    ubahJumlah(tombol.dataset.id, Number(tombol.dataset.ubah));
  }
});

// ---------- 6. Form: tampilkan alamat jika dipilih "Diantar" ----------
const pilihanAmbil = document.querySelectorAll('input[name="ambil"]');
pilihanAmbil.forEach(function (radio) {
  radio.addEventListener("change", function () {
    barisAlamat.hidden = radio.value !== "Diantar";
  });
});

// ---------- 7. Kirim pesanan ke WhatsApp ----------
formPesan.addEventListener("submit", function (e) {
  e.preventDefault(); // cegah halaman reload

  const nama = document.getElementById("nama");
  const telepon = document.getElementById("telepon");
  const alamat = document.getElementById("alamat");
  const catatan = document.getElementById("catatan");
  const ambil = document.querySelector('input[name="ambil"]:checked').value;
  const bayar = document.querySelector('input[name="bayar"]:checked').value;

  // Hapus tanda merah dari percobaan sebelumnya
  nama.classList.remove("salah");
  telepon.classList.remove("salah");
  alamat.classList.remove("salah");
  pesanError.textContent = "";

  // --- Validasi ---
  if (keranjang.length === 0) {
    pesanError.textContent = "Keranjang masih kosong. Pilih menu dulu.";
    return;
  }
  if (nama.value.trim().length < 3) {
    nama.classList.add("salah");
    pesanError.textContent = "Nama minimal 3 huruf.";
    nama.focus();
    return;
  }
  // Nomor WA Indonesia: diawali 08 atau 62, panjang 10-15 digit
  const polaTelepon = /^(08|62)[0-9]{8,13}$/;
  if (!polaTelepon.test(telepon.value.trim().replace(/[\s-]/g, ""))) {
    telepon.classList.add("salah");
    pesanError.textContent = "Nomor WhatsApp tidak valid. Contoh: 08123456789";
    telepon.focus();
    return;
  }
  if (ambil === "Diantar" && alamat.value.trim().length < 5) {
    alamat.classList.add("salah");
    pesanError.textContent = "Alamat pengantaran wajib diisi.";
    alamat.focus();
    return;
  }

  // --- Menyusun pesan WhatsApp ---
  let pesan = "Halo Zona Telur, saya ingin memesan:\n\n";
  for (let i = 0; i < keranjang.length; i++) {
    const b = keranjang[i];
    pesan += `${i + 1}. ${b.nama} x${b.jumlah} ${b.satuan} = ${formatRupiah(b.harga * b.jumlah)}\n`;
  }
  pesan += `\nTotal: ${formatRupiah(hitungTotal())}\n\n`;
  pesan += `Nama: ${nama.value.trim()}\n`;
  pesan += `No. WA: ${telepon.value.trim()}\n`;
  pesan += `Pengambilan: ${ambil}\n`;
  if (ambil === "Diantar") {
    pesan += `Alamat: ${alamat.value.trim()}\n`;
  }
  pesan += `Pembayaran: ${bayar}\n`;
  if (catatan.value.trim() !== "") {
    pesan += `Catatan: ${catatan.value.trim()}\n`;
  }
  pesan += "\nTerima kasih.";

  // encodeURIComponent mengubah teks agar aman dipakai di alamat URL
  const url = "https://wa.me/" + NOMOR_WA + "?text=" + encodeURIComponent(pesan);
  window.open(url, "_blank", "noopener");

  tampilkanToast("WhatsApp dibuka. Tekan kirim di sana.");
});

// Tampilan awal keranjang
tampilkanKeranjang();
