<?php
/*
| Router untuk PHP built-in server: file statis dilayani apa adanya,
| sisanya diteruskan ke public/index.php.
*/

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');

if ($uri !== '/' && is_file(__DIR__ . '/public' . $uri)) {
    return false;
}

$_SERVER['SCRIPT_NAME'] = '/index.php';

require __DIR__ . '/public/index.php';
