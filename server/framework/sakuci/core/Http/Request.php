<?php

namespace Sakuci\Http;

use Sakuci\Session;
use Sakuci\Validation\Validator;

/**
 * Representasi request HTTP yang masuk.
 */
class Request
{
    protected static ?Request $current = null;

    /** Parameter yang diambil dari pola route, mis. /posts/{id} */
    protected array $routeParams = [];

    public function __construct(
        public array $query = [],
        public array $request = [],
        public array $server = [],
        public array $cookies = [],
        public array $files = []
    ) {
    }

    public static function capture(): static
    {
        $body = $_POST;

        // Dukungan body JSON supaya framework ini juga nyaman dipakai untuk API.
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (str_contains($contentType, 'application/json')) {
            $decoded = json_decode(file_get_contents('php://input') ?: '', true);
            if (is_array($decoded)) {
                $body = array_merge($body, $decoded);
            }
        }

        // PUT/PATCH/DELETE via form-urlencoded.
        if (! $body && in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['PUT', 'PATCH', 'DELETE'], true)) {
            parse_str(file_get_contents('php://input') ?: '', $body);
        }

        return static::$current = new static($_GET, $body, $_SERVER, $_COOKIE, $_FILES);
    }

    public static function current(): static
    {
        return static::$current ??= static::capture();
    }

    /*
    |----------------------------------------------------------------------
    | Informasi dasar
    |----------------------------------------------------------------------
    */

    /** Method sebenarnya, sudah memperhitungkan spoofing lewat field _method. */
    public function method(): string
    {
        $method = strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');

        if ($method === 'POST') {
            $spoofed = strtoupper((string) ($this->request['_method'] ?? $this->header('X-HTTP-Method-Override') ?? ''));
            if (in_array($spoofed, ['PUT', 'PATCH', 'DELETE'], true)) {
                return $spoofed;
            }
        }

        return $method;
    }

    public function realMethod(): string
    {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    }

    public function isMethod(string $method): bool
    {
        return $this->method() === strtoupper($method);
    }

    /** Path request relatif terhadap folder public, selalu diawali "/". */
    public function uri(): string
    {
        $uri  = parse_url($this->server['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $uri  = rawurldecode($uri);
        $base = static::basePath();

        if ($base !== '' && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base));
        }

        return '/' . trim($uri, '/');
    }

    public function fullUrl(): string
    {
        return static::baseUrl() . ($this->server['REQUEST_URI'] ?? '/');
    }

    /** Sub-folder tempat aplikasi dipasang (kosong jika di root domain). */
    public static function basePath(): string
    {
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        $base   = rtrim(str_replace('\\', '/', dirname($script)), '/');

        return $base === '/' ? '' : $base;
    }

    public static function baseUrl(): string
    {
        $https  = ($_SERVER['HTTPS'] ?? 'off') !== 'off' || ($_SERVER['SERVER_PORT'] ?? 80) == 443;
        $scheme = $https ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';

        return $scheme . '://' . $host . static::basePath();
    }

    public function ip(): string
    {
        return $this->server['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    public function header(string $key, mixed $default = null): mixed
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $key));

        return $this->server[$key] ?? $default;
    }

    public function isAjax(): bool
    {
        return strtolower((string) $this->header('X-Requested-With')) === 'xmlhttprequest';
    }

    public function wantsJson(): bool
    {
        return $this->isAjax() || str_contains((string) $this->header('Accept'), 'application/json');
    }

    /*
    |----------------------------------------------------------------------
    | Input
    |----------------------------------------------------------------------
    */

    public function all(): array
    {
        return array_merge($this->query, $this->request);
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    public function __get(string $key): mixed
    {
        return $this->input($key);
    }

    public function has(string ...$keys): bool
    {
        $all = $this->all();

        foreach ($keys as $key) {
            if (! array_key_exists($key, $all)) {
                return false;
            }
        }

        return true;
    }

    public function filled(string $key): bool
    {
        $value = $this->input($key);

        return $value !== null && $value !== '' && $value !== [];
    }

    public function only(array|string $keys): array
    {
        $keys = is_array($keys) ? $keys : func_get_args();

        return array_intersect_key($this->all(), array_flip($keys));
    }

    public function except(array|string $keys): array
    {
        $keys = is_array($keys) ? $keys : func_get_args();

        return array_diff_key($this->all(), array_flip($keys));
    }

    public function boolean(string $key): bool
    {
        return filter_var($this->input($key), FILTER_VALIDATE_BOOLEAN);
    }

    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function cookie(string $key, mixed $default = null): mixed
    {
        return $this->cookies[$key] ?? $default;
    }

    /*
    |----------------------------------------------------------------------
    | Parameter route
    |----------------------------------------------------------------------
    */

    public function setRouteParams(array $params): void
    {
        $this->routeParams = $params;
    }

    public function route(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->routeParams;
        }

        return $this->routeParams[$key] ?? $default;
    }

    /*
    |----------------------------------------------------------------------
    | Validasi
    |----------------------------------------------------------------------
    */

    /**
     * Validasi input. Jika gagal, otomatis redirect balik dengan error
     * dan old input â€” persis kebiasaan Laravel.
     *
     * @throws \Sakuci\Exceptions\ValidationException
     */
    public function validate(array $rules, array $messages = []): array
    {
        $validator = Validator::make($this->all(), $rules, $messages);

        if ($validator->fails()) {
            Session::flash('_errors', $validator->errors());
            Session::flashInput($this->except(['_token', '_method', 'password', 'password_confirmation']));

            throw new \Sakuci\Exceptions\ValidationException($validator->errors());
        }

        return $validator->validated();
    }
}

