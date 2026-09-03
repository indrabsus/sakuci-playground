<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Konfigurasi view
    |--------------------------------------------------------------------------
    */

    'path'  => resource_path('views'),

    'cache' => storage_path('framework/views'),

    // Paksa kompilasi ulang setiap request saat debug (berguna saat ngoprek engine).
    'always_recompile' => false,

];
