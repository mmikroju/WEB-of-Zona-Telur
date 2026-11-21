// =========================================================
// Zona Telur - api.js
// Pembantu untuk memanggil API PHP (dipakai semua halaman).
// Token CSRF dibaca dari <meta name="csrf-token"> yang ditulis server.
// =========================================================

const ZT_CSRF = (document.querySelector('meta[name="csrf-token"]') || {}).content || "";

// api("api/me.php")               -> GET
// api("api/login.php", {id, ...}) -> POST (JSON)
// Melempar Error dengan pesan dari server jika gagal.
async function api(url, data) {
  const opsi = { credentials: "same-origin", headers: { Accept: "application/json" } };

  if (data !== undefined) {
    opsi.method = "POST";
    opsi.headers["Content-Type"] = "application/json";
    opsi.headers["X-CSRF-Token"] = ZT_CSRF;
    opsi.body = JSON.stringify(data);
  }

  let respons;
  try {
    respons = await fetch(url, opsi);
  } catch (e) {
    throw new Error("Tidak dapat terhubung ke server. Periksa koneksi internet.");
  }

  let isi = {};
  try {
    isi = await respons.json();
  } catch (e) {
    // balasan bukan JSON (mis. galat PHP)
  }

  if (!respons.ok) {
    const err = new Error(isi.galat || "Terjadi kesalahan pada server.");
    err.status = respons.status;
    throw err;
  }
  return isi;
}
