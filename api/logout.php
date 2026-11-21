<?php
// POST: keluar
require_once __DIR__ . '/../includes/bootstrap.php';
api_mulai('POST');

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $c = session_get_cookie_params();
    setcookie(session_name(), '', time() - 3600, $c['path'], $c['domain'], $c['secure'], $c['httponly']);
}
session_destroy();
json_keluar(['ok' => true]);
