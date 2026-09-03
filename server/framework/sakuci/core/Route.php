<?php

namespace Sakuci;

use Sakuci\Routing\RouteDefinition;
use Sakuci\Routing\Router;

/**
 * Facade statis untuk Router, supaya file routes/web.php terasa seperti Laravel:
 *
 *   Route::get('/', [HomeController::class, 'index'])->name('home');
 *   Route::resource('posts', PostController::class);
 *
 * @method static RouteDefinition get(string $uri, mixed $action)
 * @method static RouteDefinition post(string $uri, mixed $action)
 * @method static RouteDefinition put(string $uri, mixed $action)
 * @method static RouteDefinition patch(string $uri, mixed $action)
 * @method static RouteDefinition delete(string $uri, mixed $action)
 * @method static RouteDefinition any(string $uri, mixed $action)
 * @method static RouteDefinition view(string $uri, string $view, array $data = [])
 * @method static RouteDefinition redirect(string $uri, string $target, int $status = 302)
 * @method static void           resource(string $name, string $controller)
 * @method static void           group(array $attributes, \Closure $callback)
 */
class Route
{
    public static function router(): Router
    {
        return Router::instance();
    }

    public static function urlFor(string $name, array $parameters = []): string
    {
        return static::router()->urlFor($name, $parameters);
    }

    public static function __callStatic(string $method, array $arguments): mixed
    {
        return static::router()->{$method}(...$arguments);
    }
}

