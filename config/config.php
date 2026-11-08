<?php
declare(strict_types=1);

if (PHP_VERSION_ID < 80000) {
    exit('SAMS requires PHP 8 or newer.');
}

define('APP_NAME', 'Student Assignment Management System');
define('APP_SHORT', 'SAMS');
define('APP_ROOT', dirname(__DIR__));
define('UPLOAD_DIR', APP_ROOT . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'uploads');
define('MAX_UPLOAD_BYTES', 10 * 1024 * 1024);
define('SESSION_TIMEOUT', 1800);
define('LOGIN_MAX_ATTEMPTS', 8);

date_default_timezone_set('Asia/Karachi');

$logDirectory = APP_ROOT . DIRECTORY_SEPARATOR . 'logs';
if (!is_dir($logDirectory)) {
    mkdir($logDirectory, 0755, true);
}

ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', $logDirectory . DIRECTORY_SEPARATOR . 'error.log');

require_once __DIR__ . '/database.php';

if (PHP_SAPI !== 'cli' && !headers_sent()) {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'self'; font-src 'self'; form-action 'self'; base-uri 'self'; frame-ancestors 'self'; object-src 'none'");
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('SAMSSESSID');
    $secureCookie = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secureCookie,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

if (!isset($_SESSION['_created'])) {
    $_SESSION['_created'] = time();
} elseif (time() - (int) $_SESSION['_created'] > 900) {
    session_regenerate_id(true);
    $_SESSION['_created'] = time();
}
