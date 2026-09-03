<?php
/*
|--------------------------------------------------------------------------
| Sakuci Framework
|--------------------------------------------------------------------------
| Satu-satunya file yang boleh diakses langsung dari browser.
| Arahkan document root web server ke folder public/ ini.
*/

define('SAKUCI_START', microtime(true));
define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/core/bootstrap.php';

(new Sakuci\Application(BASE_PATH))->run();
