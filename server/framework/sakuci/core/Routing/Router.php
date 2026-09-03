<?php

namespace Sakuci\Routing;

use Sakuci\Config;
use Sakuci\Database\Model;
use Sakuci\Exceptions\HttpException;
use Sakuci\Http\RedirectResponse;
use Sakuci\Http\Request;
use Sakuci\Http\Response;

/**
 * Mesin routing: menyimpan daftar route, mencocokkan request,
 * menjalankan middleware, lalu memanggil controller.
 */
class Router
{
    protected static ?Router $instance = null;

    /** @var array<string, RouteDefinition[]> dikelompokkan per HTTP method */
    protected array $routes = [];

    /** @var array<string, RouteDefinition> */
    protected array $named = [];

    /** Tumpukan atribut group yang sedang aktif. */
    protected array $groupStack = [];

    /** Route yang cocok dengan request saat ini (diisi saat dispatch). */
    protected ?RouteDefinition $current = null;

    public static function instance(): static
    {
        return static::$instance ??= new static();
    }

    /** Route yang sedang aktif -- null bila belum ada request yang di-dispatch. */
    public function current(): ?RouteDefinition
    {
        return $this->current;
    }

    /*
    |----------------------------------------------------------------------
    | Pendaftaran route
    |----------------------------------------------------------------------
    */

    public function add(string|array $methods, string $uri, mixed $action): RouteDefinition
    {
        $uri     = $this->applyPrefix($uri);
        $created = [];

        foreach ((array) $methods as $method) {
            $method = strtoupper($method);
            $route  = new RouteDefinition($method, $uri, $this->applyNamespace($action));
            $route->middleware($this->groupMiddleware());

            $this->routes[$method][] = $route;
            $created[]               = $route;
        }

        $primary = array_shift($created);

        // Route GET otomatis membuat kembarannya untuk HEAD. Pemanggilan
        // ->name() / ->middleware() harus berlaku untuk keduanya.
        $primary->siblings = $created;

        return $primary;
    }

    public function get(string $uri, mixed $action): RouteDefinition
    {
        // Route GET otomatis juga melayani HEAD.
        return $this->add(['GET', 'HEAD'], $uri, $action);
    }

    public function post(string $uri, mixed $action): RouteDefinition
    {
        return $this->add('POST', $uri, $action);
    }

    public function put(string $uri, mixed $action): RouteDefinition
    {
        return $this->add('PUT', $uri, $action);
    }

    public function patch(string $uri, mixed $action): RouteDefinition
    {
        return $this->add('PATCH', $uri, $action);
    }

    public function delete(string $uri, mixed $action): RouteDefinition
    {
        return $this->add('DELETE', $uri, $action);
    }

    public function any(string $uri, mixed $action): RouteDefinition
    {
        return $this->add(['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE'], $uri, $action);
    }

    /** Route yang langsung menampilkan view tanpa controller. */
    public function view(string $uri, string $view, array $data = []): RouteDefinition
    {
        return $this->get($uri, fn () => Response::view($view, $data));
    }

    /** Route yang langsung melakukan redirect. */
    public function redirect(string $uri, string $target, int $status = 302): RouteDefinition
    {
        return $this->get($uri, fn () => new RedirectResponse($target, $status));
    }

    /**
     * Tujuh route CRUD sekaligus, sama seperti Route::resource() di Laravel.
     */
    public function resource(string $name, string $controller): void
    {
        $name = trim($name, '/');
        $base = str_replace('/', '.', $name);

        $this->get("/{$name}", [$controller, 'index'])->name("{$base}.index");
        $this->get("/{$name}/create", [$controller, 'create'])->name("{$base}.create");
        $this->post("/{$name}", [$controller, 'store'])->name("{$base}.store");
        $this->get("/{$name}/{id}", [$controller, 'show'])->name("{$base}.show");
        $this->get("/{$name}/{id}/edit", [$controller, 'edit'])->name("{$base}.edit");
        $this->put("/{$name}/{id}", [$controller, 'update'])->name("{$base}.update");
        $this->patch("/{$name}/{id}", [$controller, 'update']);
        $this->delete("/{$name}/{id}", [$controller, 'destroy'])->name("{$base}.destroy");
    }

    /**
     * Kelompokkan route dengan prefix / middleware bersama.
     *
     *   Route::group(['prefix' => 'admin', 'middleware' => 'auth'], function () { ... });
     */
    public function group(array $attributes, \Closure $callback): void
    {
        $this->groupStack[] = $attributes;

        $callback($this);

        array_pop($this->groupStack);
    }

    public function registerName(string $name, RouteDefinition $route): void
    {
        $this->named[$name] = $route;
    }

    public function urlFor(string $name, array $parameters = []): string
    {
        if (! isset($this->named[$name])) {
            throw new \InvalidArgumentException("Route dengan nama [{$name}] tidak terdaftar.");
        }

        return url($this->named[$name]->toUrl($parameters));
    }

    /** @return array<string, RouteDefinition[]> */
    public function routes(): array
    {
        return $this->routes;
    }

    protected function applyPrefix(string $uri): string
    {
        $prefix = '';

        foreach ($this->groupStack as $group) {
            if (! empty($group['prefix'])) {
                $prefix .= '/' . trim($group['prefix'], '/');
            }
        }

        $uri = $prefix . '/' . trim($uri, '/');

        return '/' . trim($uri, '/');
    }

    protected function groupMiddleware(): array
    {
        $middleware = [];

        foreach ($this->groupStack as $group) {
            $middleware = array_merge($middleware, (array) ($group['middleware'] ?? []));
        }

        return $middleware;
    }

    /** Lengkapi nama controller pendek dengan namespace default. */
    protected function applyNamespace(mixed $action): mixed
    {
        $namespace = 'App\\Controllers\\';

        if (is_string($action) && str_contains($action, '@') && ! str_contains($action, '\\')) {
            return $namespace . $action;
        }

        if (is_array($action) && isset($action[0]) && is_string($action[0]) && ! str_contains($action[0], '\\')) {
            $action[0] = $namespace . $action[0];
        }

        return $action;
    }

    /*
    |----------------------------------------------------------------------
    | Dispatch
    |----------------------------------------------------------------------
    */

    public function dispatch(Request $request): Response
    {
        $route = $this->findRoute($request);

        $this->current = $route;
        $request->setRouteParams($route->parameters);

        return $this->runThroughMiddleware($request, $route, function (Request $request) use ($route) {
            return $this->toResponse($this->callAction($route, $request));
        });
    }

    protected function findRoute(Request $request): RouteDefinition
    {
        $uri    = $request->uri();
        $method = $request->method();

        foreach ($this->routes[$method] ?? [] as $route) {
            if ($route->matches($uri)) {
                return $route;
            }
        }

        // URI cocok tapi method-nya salah -> 405, bukan 404.
        foreach ($this->routes as $routes) {
            foreach ($routes as $route) {
                if ($route->matches($uri)) {
                    throw new HttpException(405, "Method {$method} tidak diizinkan untuk {$uri}");
                }
            }
        }

        throw new HttpException(404, "Route untuk [{$method} {$uri}] tidak ditemukan.");
    }

    /** Jalankan rantai middleware lalu handler intinya. */
    protected function runThroughMiddleware(Request $request, RouteDefinition $route, \Closure $destination): Response
    {
        $aliases = Config::get('app.middleware', []);
        $stack   = array_reverse(array_merge(Config::get('app.global_middleware', []), $route->middleware));

        $next = $destination;

        foreach ($stack as $name) {
            $class = $aliases[$name] ?? $name;
            $previous = $next;

            $next = function (Request $request) use ($class, $previous): Response {
                if (! class_exists($class)) {
                    throw new \RuntimeException("Middleware [{$class}] tidak ditemukan.");
                }

                return $this->toResponse((new $class())->handle($request, $previous));
            };
        }

        return $this->toResponse($next($request));
    }

    /** Panggil closure / controller dengan injeksi parameter. */
    protected function callAction(RouteDefinition $route, Request $request): mixed
    {
        $action = $route->action;

        if ($action instanceof \Closure) {
            return $action(...$this->resolveArguments(new \ReflectionFunction($action), $route->parameters, $request));
        }

        if (is_string($action) && str_contains($action, '@')) {
            [$class, $method] = explode('@', $action, 2);
        } elseif (is_array($action)) {
            [$class, $method] = [$action[0], $action[1] ?? '__invoke'];
        } elseif (is_string($action)) {
            [$class, $method] = [$action, '__invoke'];
        } else {
            throw new \RuntimeException('Action route tidak dikenali.');
        }

        if (! class_exists($class)) {
            throw new \RuntimeException("Controller [{$class}] tidak ditemukan.");
        }

        $controller = new $class();

        if (! method_exists($controller, $method)) {
            throw new \RuntimeException("Method [{$method}] tidak ada di controller [{$class}].");
        }

        $arguments = $this->resolveArguments(
            new \ReflectionMethod($controller, $method),
            $route->parameters,
            $request
        );

        return $controller->{$method}(...$arguments);
    }

    /**
     * Cocokkan parameter method controller dengan parameter route.
     * Mendukung injeksi Request dan route model binding.
     */
    protected function resolveArguments(\ReflectionFunctionAbstract $reflection, array $parameters, Request $request): array
    {
        $arguments = [];
        $values    = array_values($parameters);
        $index     = 0;

        foreach ($reflection->getParameters() as $parameter) {
            $type = $parameter->getType();
            $name = $parameter->getName();

            if ($type instanceof \ReflectionNamedType && ! $type->isBuiltin()) {
                $class = $type->getName();

                // Injeksi Request.
                if (is_a($class, Request::class, true)) {
                    $arguments[] = $request;
                    continue;
                }

                // Route model binding: findOrFail berdasarkan parameter route.
                if (is_subclass_of($class, Model::class)) {
                    $key = $parameters[$name] ?? ($values[$index] ?? null);
                    $index++;
                    $arguments[] = $class::findOrFail($key);
                    continue;
                }

                $arguments[] = new $class();
                continue;
            }

            if (array_key_exists($name, $parameters)) {
                $arguments[] = $parameters[$name];
                $index++;
                continue;
            }

            if (isset($values[$index])) {
                $arguments[] = $values[$index++];
                continue;
            }

            $arguments[] = $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null;
        }

        return $arguments;
    }

    /** Ubah apa pun yang dikembalikan controller menjadi Response. */
    public function toResponse(mixed $result): Response
    {
        if ($result instanceof Response) {
            return $result;
        }

        if (is_array($result) || $result instanceof \JsonSerializable) {
            return Response::json($result);
        }

        return Response::make((string) $result);
    }
}

