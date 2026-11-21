<?php
// GET: data pengguna yang sedang login (atau null) + token CSRF
require_once __DIR__ . '/../includes/bootstrap.php';
api_mulai('GET');
json_keluar(['user' => data_publik(pengguna_sekarang()), 'csrf' => csrf_token()]);
