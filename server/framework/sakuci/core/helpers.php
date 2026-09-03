<?php
/*
|--------------------------------------------------------------------------
| Helper Global
|--------------------------------------------------------------------------
| Fungsi pendek yang dipakai di controller maupun view.
*/

use Sakuci\Config;
use Sakuci\Http\RedirectResponse;
use Sakuci\Http\Request;
use Sakuci\Http\Response;
use Sakuci\MessageBag;
use Sakuci\Route;
use Sakuci\Session;
use Sakuci\View;

/*
| Polyfill mbstring: sebagian instalasi PHP di Windows belum mengaktifkan
| ekstensi mbstring. Fungsi pengganti di bawah tetap sadar UTF-8.
*/

if (! function_exists('mb_strlen')) {
    function mb_strlen(string $value, ?string $encoding = null): int
    {
        return preg_match_all('/./us', $value) ?: 0;
    }
}

if (! function_exists('mb_substr')) {
    function mb_substr(string $value, int $start, ?int $length = null, ?string $encoding = null): string
    {
        $characters = preg_split('//u', $value, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return implode('', array_slice($characters, $start, $length));
    }
}

if (! function_exists('mb_strtolower')) {
    function mb_strtolower(string $value, ?string $encoding = null): string
    {
        return strtolower($value);
    }
}

if (! function_exists('mb_strtoupper')) {
    function mb_strtoupper(string $value, ?string $encoding = null): string
    {
        return strtoupper($value);
    }
}

if (! function_exists('e')) {
    /** Escape output supaya aman dari XSS. */
    function e(mixed $value): string
    {
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (! function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        return BASE_PATH . ($path ? DIRECTORY_SEPARATOR . ltrim($path, '/\\') : '');
    }
}

if (! function_exists('app_path')) {
    function app_path(string $path = ''): string
    {
        return base_path('app' . ($path ? '/' . ltrim($path, '/') : ''));
    }
}

if (! function_exists('storage_path')) {
    function storage_path(string $path = ''): string
    {
        return base_path('storage' . ($path ? '/' . ltrim($path, '/') : ''));
    }
}

if (! function_exists('resource_path')) {
    function resource_path(string $path = ''): string
    {
        return base_path('resources' . ($path ? '/' . ltrim($path, '/') : ''));
    }
}

if (! function_exists('database_path')) {
    function database_path(string $path = ''): string
    {
        return base_path('database' . ($path ? '/' . ltrim($path, '/') : ''));
    }
}

if (! function_exists('config')) {
    function config(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return Config::all();
        }

        return Config::get($key, $default);
    }
}

if (! function_exists('env')) {
    /** Baca variabel dari file .env (atau environment server). */
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? getenv($key);

        if ($value === false || $value === null) {
            return $default;
        }

        return match (strtolower((string) $value)) {
            'true', '(true)'   => true,
            'false', '(false)' => false,
            'null', '(null)'   => null,
            'empty', '(empty)' => '',
            default            => $value,
        };
    }
}

if (! function_exists('request')) {
    function request(?string $key = null, mixed $default = null): mixed
    {
        $request = Request::current();

        if ($key === null) {
            return $request;
        }

        return $request->input($key, $default);
    }
}

if (! function_exists('view')) {
    function view(string $view, array $data = []): Response
    {
        return Response::view($view, $data);
    }
}

if (! function_exists('render')) {
    /** Render view menjadi string mentah (tanpa objek Response). */
    function render(string $view, array $data = []): string
    {
        return View::make($view, $data);
    }
}

if (! function_exists('json')) {
    function json(mixed $data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }
}

if (! function_exists('redirect')) {
    function redirect(?string $to = null, int $status = 302): RedirectResponse
    {
        return new RedirectResponse($to ?? '/', $status);
    }
}

if (! function_exists('back')) {
    function back(int $status = 302): RedirectResponse
    {
        return redirect(Session::get('_previous_url', '/'), $status);
    }
}

if (! function_exists('url')) {
    function url(string $path = ''): string
    {
        return rtrim(Request::baseUrl() . '/' . ltrim($path, '/'), '/') ?: '/';
    }
}

if (! function_exists('asset')) {
    function asset(string $path): string
    {
        return url($path);
    }
}

if (! function_exists('route')) {
    /** Bangun URL dari nama route: route('posts.show', ['id' => 3]) */
    function route(string $name, array $parameters = []): string
    {
        return Route::urlFor($name, $parameters);
    }
}

if (! function_exists('current_route_name')) {
    /** Nama route yang cocok dengan request saat ini, mis. 'docs'. Null bila tidak bernama. */
    function current_route_name(): ?string
    {
        return Route::router()->current()?->name;
    }
}

if (! function_exists('is_route')) {
    /** Cek apakah route saat ini punya nama tertentu: is_route('docs') */
    function is_route(string ...$names): bool
    {
        return in_array(current_route_name(), $names, true);
    }
}

if (! function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return Session::token();
    }
}

if (! function_exists('session')) {
    function session(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return Session::all();
        }

        return Session::has($key) ? Session::get($key) : Session::getFlash($key, $default);
    }
}

if (! function_exists('old')) {
    function old(string $key, mixed $default = ''): mixed
    {
        return Session::old($key, $default);
    }
}

if (! function_exists('errors')) {
    function errors(): MessageBag
    {
        return Session::errors();
    }
}

if (! function_exists('abort')) {
    function abort(int $code, string $message = ''): never
    {
        throw new \Sakuci\Exceptions\HttpException($code, $message);
    }
}

if (! function_exists('abort_if')) {
    function abort_if(bool $condition, int $code, string $message = ''): void
    {
        if ($condition) {
            abort($code, $message);
        }
    }
}

if (! function_exists('str_slug')) {
    function str_slug(string $value, string $separator = '-'): string
    {
        $value = preg_replace('/[^a-zA-Z0-9]+/u', $separator, trim($value));

        return strtolower(trim((string) $value, $separator));
    }
}

if (! function_exists('str_snake')) {
    function str_snake(string $value): string
    {
        $value = preg_replace('/(?<!^)[A-Z]/', '_$0', $value);

        return strtolower((string) $value);
    }
}

if (! function_exists('str_studly')) {
    function str_studly(string $value): string
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $value)));
    }
}

if (! function_exists('str_plural')) {
    /** Pluralisasi sederhana untuk menebak nama tabel. */
    function str_plural(string $value): string
    {
        if (preg_match('/(s|x|z|ch|sh)$/i', $value)) {
            return $value . 'es';
        }

        if (preg_match('/[^aeiou]y$/i', $value)) {
            return substr($value, 0, -1) . 'ies';
        }

        return $value . 's';
    }
}

if (! function_exists('class_basename')) {
    /** Ambil nama class tanpa namespace: App\Models\Post -> Post */
    function class_basename(string|object $class): string
    {
        $class = is_object($class) ? get_class($class) : $class;

        return basename(str_replace('\\', '/', $class));
    }
}

if (! function_exists('str_limit')) {
    function str_limit(string $value, int $limit = 100, string $end = '...'): string
    {
        if (mb_strlen($value) <= $limit) {
            return $value;
        }

        return rtrim(mb_substr($value, 0, $limit)) . $end;
    }
}

if (! function_exists('dd')) {
    /** Dump lalu hentikan eksekusi. */
    function dd(mixed ...$values): never
    {
        if (PHP_SAPI !== 'cli') {
            echo '<pre style="background:#111827;color:#e5e7eb;padding:16px;border-radius:8px;'
               . 'font:13px/1.6 ui-monospace,Consolas,monospace;overflow:auto">';
        }

        foreach ($values as $value) {
            var_dump($value);
        }

        if (PHP_SAPI !== 'cli') {
            echo '</pre>';
        }

        exit(1);
    }
}

if (! function_exists('dump')) {
    function dump(mixed ...$values): void
    {
        foreach ($values as $value) {
            echo PHP_SAPI === 'cli' ? '' : '<pre>';
            var_dump($value);
            echo PHP_SAPI === 'cli' ? '' : '</pre>';
        }
    }
}

