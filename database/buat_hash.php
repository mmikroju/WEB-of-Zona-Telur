<?php
// Alat bantu (hanya lewat terminal) untuk membuat hash kata sandi.
// Contoh:  php database/buat_hash.php KataSandiBaruAnda
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
if ($argc < 2 || strlen($argv[1]) < 6) {
    fwrite(STDERR, "Pemakaian: php database/buat_hash.php KataSandiBaru   (minimal 6 karakter)\n");
    exit(1);
}
echo password_hash($argv[1], PASSWORD_DEFAULT), "\n";
