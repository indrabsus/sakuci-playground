<?php

namespace App\Middleware;

/** Hanya role admin yang boleh lewat. Alias 'admin' di config/app.php. */
class AdminOnly extends EnsureRole
{
    protected function roles(): array
    {
        return ['admin'];
    }
}

