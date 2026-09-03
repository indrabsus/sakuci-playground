<?php

namespace Sakuci;

use Sakuci\Http\Request;
use Sakuci\Http\Response;

/**
 * Kontrak middleware.
 *
 *   class CekLogin extends Middleware
 *   {
 *       public function handle(Request $request, \Closure $next): mixed
 *       {
 *           if (! session('user')) {
 *               return redirect('/login');
 *           }
 *
 *           return $next($request);
 *       }
 *   }
 */
abstract class Middleware
{
    /**
     * @param  \Closure(Request): Response  $next
     */
    abstract public function handle(Request $request, \Closure $next): mixed;
}

