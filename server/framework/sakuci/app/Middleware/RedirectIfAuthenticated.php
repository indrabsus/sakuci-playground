<?php

namespace App\Middleware;

use App\Models\User;
use Sakuci\Http\Request;
use Sakuci\Middleware;

/**
 * Kebalikan dari Authenticate: dipakai di halaman login supaya user
 * yang sudah login tidak diperlihatkan form login lagi.
 * Alias 'guest' di config/app.php.
 */
class RedirectIfAuthenticated extends Middleware
{
    public function handle(Request $request, \Closure $next): mixed
    {
        $user = User::current();

        if ($user !== null) {
            return redirect(match ($user->role) {
                'admin' => '/admin',
                'staff' => '/staff',
                default => '/dashboard',
            });
        }

        return $next($request);
    }
}

