<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/validators.php';
require_once __DIR__ . '/auth.php';

set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    error_log('PHP error: ' . $message . ' in ' . $file . ':' . $line);
    return true;
});

set_exception_handler(static function (Throwable $exception): void {
    static $handling = false;
    error_log('Uncaught ' . $exception::class . ': ' . $exception->getMessage() . ' in ' . $exception->getFile() . ':' . $exception->getLine());

    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, "The application could not complete the request.\n");
        exit(1);
    }

    if ($handling || !function_exists('render_error')) {
        if (!headers_sent()) {
            http_response_code(500);
        }
        echo 'The system could not complete your request. Please try again later.';
        exit;
    }

    $handling = true;
    render_error('Something went wrong', 'The system could not complete your request. Please try again later.', 500);
});
