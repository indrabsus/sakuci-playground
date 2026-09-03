<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Identitas aplikasi
    |--------------------------------------------------------------------------
    */

    'name'     => env('APP_NAME', 'Sakuci'),

    // Saat true, halaman error menampilkan detail lengkap. Matikan di produksi.
    'debug'    => env('APP_DEBUG', true),

    'timezone' => env('APP_TIMEZONE', 'Asia/Jakarta'),

    'session_name' => 'sakuci_session',

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    | global_middleware  : dijalankan pada setiap request.
    | middleware         : alias yang bisa dipakai di route/group.
    |                      Contoh: Route::post(...)->middleware('auth');
    */

    'global_middleware' => [
        App\Middleware\VerifyCsrfToken::class,
    ],

    'middleware' => [
        'auth'  => App\Middleware\Authenticate::class,
        'guest' => App\Middleware\RedirectIfAuthenticated::class,
        'admin' => App\Middleware\AdminOnly::class,
        // Alias role lain ditambahkan otomatis di sini oleh RoleController
        // saat admin membuat role baru lewat /admin/roles.
    ],

];
