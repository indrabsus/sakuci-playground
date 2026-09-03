<?php

namespace App\Middleware;

use App\Models\User;
use Sakuci\Http\Request;
use Sakuci\Middleware;

/**
 * Menolak akses bila belum login. Daftarkan sebagai alias 'auth'
 * di config/app.php lalu pakai: Route::group(['middleware' => 'auth'], ...)
 */
class Authenticate extends Middleware
{
    public function handle(Request $request, \Closure $next): mixed
    {
        if (User::current() === null) {
            return redirect('/login')->with('error', 'Silakan login terlebih dahulu.');
        }

        return $next($request);
    }
}

