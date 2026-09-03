<?php

namespace Sakuci\Routing;

/**
 * Satu baris route: method + uri + action.
 */
class RouteDefinition
{
    public ?string $name = null;

    public array $middleware = [];

    /** Route kembar (mis. HEAD untuk setiap GET). */
    public array $siblings = [];

    /** Parameter hasil pencocokan URI. */
    public array $parameters = [];

    protected string $regex;

    public function __construct(
        public string $method,
        public string $uri,
        public mixed $action
    ) {
        $this->regex = $this->compile($uri);
    }

    /** Ubah "/posts/{id}" menjadi regex bernama. */
    protected function compile(string $uri): string
    {
        // Parameter opsional: /posts/{page?} -> segmen beserta slash-nya boleh hilang.
        $pattern = preg_replace(
            '#/\{([a-zA-Z_][a-zA-Z0-9_]*)\?\}#',
            '(?:/(?P<$1>[^/]+))?',
            $uri
        );

        // Parameter wajib: {id}
        $pattern = preg_replace(
            '#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#',
            '(?P<$1>[^/]+)',
            (string) $pattern
        );

        return '#^' . $pattern . '$#u';
    }

    public function matches(string $uri): bool
    {
        if (! preg_match($this->regex, $uri, $matches)) {
            return false;
        }

        $this->parameters = [];

        foreach ($matches as $key => $value) {
            if (is_string($key) && $value !== '') {
                $this->parameters[$key] = $value;
            }
        }

        return true;
    }

    /** Beri nama route: Route::get(...)->name('posts.index') */
    public function name(string $name): static
    {
        $this->name = $name;

        foreach ($this->siblings as $sibling) {
            $sibling->name = $name;
        }

        Router::instance()->registerName($name, $this);

        return $this;
    }

    /** Tambahkan middleware: ->middleware('auth') atau ->middleware(['a','b']) */
    public function middleware(string|array $middleware): static
    {
        $this->middleware = array_merge($this->middleware, (array) $middleware);

        foreach ($this->siblings as $sibling) {
            $sibling->middleware = $this->middleware;
        }

        return $this;
    }

    /** Bangun URL dari pola route dan parameternya. */
    public function toUrl(array $parameters = []): string
    {
        $uri = $this->uri;

        foreach ($parameters as $key => $value) {
            $replaced = preg_replace('#\{' . preg_quote((string) $key, '#') . '\??\}#', rawurlencode((string) $value), $uri, 1);

            if ($replaced !== $uri) {
                $uri = (string) $replaced;
                unset($parameters[$key]);
            }
        }

        // Parameter posisional untuk placeholder yang tersisa.
        foreach (array_values($parameters) as $value) {
            $uri = (string) preg_replace('#\{[a-zA-Z_][a-zA-Z0-9_]*\??\}#', rawurlencode((string) $value), $uri, 1);
        }

        // Bersihkan placeholder opsional yang tidak diisi.
        $uri = (string) preg_replace('#/\{[a-zA-Z_][a-zA-Z0-9_]*\?\}#', '', $uri);

        return $uri === '' ? '/' : $uri;
    }
}

