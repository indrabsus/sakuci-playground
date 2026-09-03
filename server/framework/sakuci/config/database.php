<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Koneksi default
    |--------------------------------------------------------------------------
    | Diatur lewat DB_CONNECTION di file .env â€” pilihannya "mysql" atau "sqlite".
    */

    'default' => env('DB_CONNECTION', 'mysql'),

    'connections' => [

        /*
        | MySQL / MariaDB (XAMPP, Laragon, dsb).
        | Databasenya harus dibuat lebih dulu, misalnya lewat phpMyAdmin:
        |     CREATE DATABASE batara;
        */
        'mysql' => [
            'driver'   => 'mysql',
            'host'     => env('DB_HOST', '127.0.0.1'),
            'port'     => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'batara'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset'  => 'utf8mb4',
        ],

        /*
        | SQLite â€” tanpa server, file dibuat otomatis.
        | Butuh ekstensi pdo_sqlite aktif di php.ini.
        */
        'sqlite' => [
            'driver'   => 'sqlite',
            'database' => env('DB_SQLITE_PATH', database_path('database.sqlite')),
        ],

    ],

];

