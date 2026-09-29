<?php

define('BASE_PATH', dirname(__DIR__, 2));

// Load .env early so env() is available for the config constants below.
require_once BASE_PATH . '/app/config/bootstrap.php';

define('APP_NAME', 'Eventrify');
define('APP_URL', rtrim((string) env('APP_URL', 'http://localhost:8000'), '/'));
define('APP_ENV', (string) env('APP_ENV', 'development'));

// Detect the app base URL from the current request so links and assets work on
// any host/port — localhost:8000, a LAN IP, an Apache subfolder, or a deployed
// host. APP_URL (from .env) is only a fallback for CLI / non-HTTP contexts.
if (PHP_SAPI !== 'cli') {
    $scheme = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        $scheme = strtolower(trim(explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'])[0]));
    }
    $host = $_SERVER['HTTP_HOST'] ?? parse_url(APP_URL, PHP_URL_HOST);
    $basePath = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/')));
    $basePath = ($basePath === '/' || $basePath === '.') ? '' : rtrim($basePath, '/');
    define('BASE_URL', rtrim($scheme . '://' . $host . $basePath, '/'));
} else {
    define('BASE_URL', APP_URL);
}

// ── Session bootstrap ────────────────────────────────────────────────
// Cookies are hardened before the session starts: HttpOnly keeps the id out
// of reach of scripts, SameSite=Lax blocks cross-site form posts, and
// Secure is set automatically when the request arrived over HTTPS.
$requestIsHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https'
    || (string) ($_SERVER['SERVER_PORT'] ?? '') === '443';

if (PHP_SAPI !== 'cli') {
    ini_set('session.use_strict_mode', '1');   // reject client-supplied session ids
    ini_set('session.use_only_cookies', '1'); // never read the id from the URL
    ini_set('session.cookie_httponly', '1');

    session_name('EVENTRIFY_SESSID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => ($basePath !== '' ? $basePath : '') . '/',
        'domain'   => '',
        'secure'   => $requestIsHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
