<?php

namespace App\Middleware;

use Sakuci\Http\Request;
use Sakuci\Middleware;
use Sakuci\Session;

/**
 * Menolak request POST/PUT/PATCH/DELETE yang tidak menyertakan token CSRF.
 * Tambahkan @csrf di setiap form.
 */
class VerifyCsrfToken extends Middleware
{
    /** URI yang dikecualikan (mis. endpoint webhook). */
    protected array $except = [
        // '/api/*',
    ];

    public function handle(Request $request, \Closure $next): mixed
    {
        if ($this->shouldSkip($request)) {
            return $next($request);
        }

        $token = $request->input('_token') ?? $request->header('X-CSRF-TOKEN');

        if (! is_string($token) || ! hash_equals(Session::token(), $token)) {
            abort(419);
        }

        return $next($request);
    }

    protected function shouldSkip(Request $request): bool
    {
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return true;
        }

        foreach ($this->except as $pattern) {
            if (fnmatch($pattern, $request->uri())) {
                return true;
            }
        }

        return false;
    }
}

