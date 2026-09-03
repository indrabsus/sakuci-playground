<?php

namespace Sakuci;

use Sakuci\Http\RedirectResponse;
use Sakuci\Http\Response;

/**
 * Base controller. Semua controller aplikasi mewarisi class ini.
 */
abstract class Controller
{
    /** Middleware tambahan yang dijalankan lewat properti $middleware route. */
    protected array $middleware = [];

    protected function view(string $view, array $data = []): Response
    {
        return Response::view($view, $data);
    }

    protected function json(mixed $data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }

    protected function redirect(string $to): RedirectResponse
    {
        return new RedirectResponse($to);
    }

    protected function route(string $name, array $parameters = []): RedirectResponse
    {
        return (new RedirectResponse('/'))->route($name, $parameters);
    }

    protected function back(): RedirectResponse
    {
        return back();
    }

    protected function abort(int $code, string $message = ''): never
    {
        abort($code, $message);
    }

    /** Method yang dipanggil bila controller dipakai sebagai single action. */
    public function __invoke(): mixed
    {
        throw new \BadMethodCallException(static::class . ' tidak punya method __invoke().');
    }
}

