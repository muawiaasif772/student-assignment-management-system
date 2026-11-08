<?php
declare(strict_types=1);

/**
 * Database connection for XAMPP.
 * Default XAMPP MySQL account: user "root" with an empty password.
 * Change the constants below if your MySQL username or password is different.
 */
const DB_HOST = '127.0.0.1';
const DB_PORT = '3306';
const DB_NAME = 'sams_db';
const DB_USER = 'root';
const DB_PASS = '';

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    if (!extension_loaded('pdo_mysql')) {
        show_database_error('PHP is missing the pdo_mysql extension. Enable it in XAMPP, restart Apache, and refresh this page.');
    }

    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        try {
            $pdo->exec("SET time_zone = '+05:00'");
        } catch (PDOException $timezoneError) {
            error_log('Could not set MySQL time zone: ' . $timezoneError->getMessage());
        }
    } catch (PDOException $exception) {
        error_log('Database connection failed: ' . $exception->getMessage());
        $driverCode = (int) ($exception->errorInfo[1] ?? 0);
        $message = match ($driverCode) {
            1049 => 'The sams_db database was not found. Import database/sams_db.sql in phpMyAdmin, then refresh this page.',
            1045 => 'The database username or password was rejected. Update the constants in config/database.php and try again.',
            2002, 2003 => 'MySQL is not running. Open the XAMPP Control Panel, start MySQL, and refresh this page.',
            default => 'The application could not connect to the database. Start MySQL in XAMPP and confirm that sams_db has been imported.',
        };
        show_database_error($message);
    }

    return $pdo;
}

function show_database_error(string $message): void
{
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }

    http_response_code(500);
    $safe = htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>Database unavailable | SAMS</title>';
    echo '<style>body{margin:0;font-family:"Segoe UI",sans-serif;background:#f3f6f8;color:#102833}main{max-width:680px;margin:10vh auto;background:#fff;border:1px solid #e4ebf0;border-radius:16px;padding:2rem}h1{font-family:Georgia,serif;font-size:1.7rem;margin-top:0}p{line-height:1.55}</style>';
    echo '</head><body><main><h1>SAMS cannot reach the database</h1><p>' . $safe . '</p></main></body></html>';
    exit;
}
