<?php

namespace App\Middleware;

use App\Models\User;
use Sakuci\Http\Request;
use Sakuci\Middleware;

/**
 * Dasar untuk middleware berbasis role. Router Sakuci membuat middleware
 * tanpa argumen (new $class()), jadi role tidak dikirim lewat parameter
 * seperti Laravel ('role:admin') â€” cukup buat turunan tipis per role:
 *
 *   class AdminOnly extends EnsureRole
 *   {
 *       protected function roles(): array { return ['admin']; }
 *   }
 */
abstract class EnsureRole extends Middleware
{
    /** @return string[] role yang diizinkan mengakses route ini */
    abstract protected function roles(): array;

    public function handle(Request $request, \Closure $next): mixed
    {
        $user = User::current();

        if ($user === null) {
            return redirect('/login')->with('error', 'Silakan login terlebih dahulu.');
        }

        if (! $user->hasRole(...$this->roles())) {
            abort(403, 'Anda tidak punya akses ke halaman ini.');
        }

        return $next($request);
    }
}

