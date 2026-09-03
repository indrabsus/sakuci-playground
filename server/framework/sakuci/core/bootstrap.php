<?php
/*
|--------------------------------------------------------------------------
| Bootstrap Sakuci Framework
|--------------------------------------------------------------------------
| File ini menggantikan peran Composer autoload. Semua class dimuat
| otomatis mengikuti aturan PSR-4 sederhana:
|
|   Sakuci\Router          -> core/Router.php
|   App\Controllers\Home   -> app/Controllers/Home.php
*/

if (! defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

spl_autoload_register(function (string $class): void {
    $prefixes = [
        'Sakuci\\' => BASE_PATH . '/core/',
        'App\\'    => BASE_PATH . '/app/',
    ];

    foreach ($prefixes as $prefix => $directory) {
        if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
            continue;
        }

        $relative = substr($class, strlen($prefix));
        $file     = $directory . str_replace('\\', '/', $relative) . '.php';

        if (is_file($file)) {
            require_once $file;
            return;
        }
    }
});

require_once BASE_PATH . '/core/helpers.php';
