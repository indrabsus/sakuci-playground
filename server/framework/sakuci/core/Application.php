<?php

namespace Sakuci;

use Sakuci\Exceptions\HttpException;
use Sakuci\Exceptions\ValidationException;
use Sakuci\Http\RedirectResponse;
use Sakuci\Http\Request;
use Sakuci\Http\Response;

/**
 * Kernel aplikasi: memuat konfigurasi, menjalankan request melalui
 * router, lalu mengirim response ke browser.
 */
class Application
{
    public const VERSION = '1.0.0';

    protected static ?Application $instance = null;

    public function __construct(protected string $basePath)
    {
        static::$instance = $this;
    }

    public static function instance(): ?static
    {
        return static::$instance;
    }

    public function basePath(string $path = ''): string
    {
        return $this->basePath . ($path ? DIRECTORY_SEPARATOR . ltrim($path, '/\\') : '');
    }

    /*
    |----------------------------------------------------------------------
    | Siklus hidup request
    |----------------------------------------------------------------------
    */

    public function boot(): void
    {
        $this->loadEnvironment();

        Config::load($this->basePath('config'));

        date_default_timezone_set(Config::get('app.timezone', 'Asia/Jakarta'));

        $this->registerErrorHandlers();

        Session::start();

        $this->loadRoutes();
    }

    public function run(): void
    {
        $this->boot();

        $request = Request::capture();

        try {
            $response = Route::router()->dispatch($request);
        } catch (\Throwable $e) {
            $response = $this->renderException($e, $request);
        }

        $response->send();

        $this->rememberUrl($request, $response);
    }

    /** Muat file .env sederhana (KEY=value per baris). */
    protected function loadEnvironment(): void
    {
        $file = $this->basePath('.env');

        if (! is_file($file)) {
            return;
        }

        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key   = trim($key);
            $value = trim(trim($value), "\"'");

            $_ENV[$key] = $value;

            if (function_exists('putenv')) {
                putenv($key . '=' . $value);
            }
        }
    }

    protected function loadRoutes(): void
    {
        foreach (['web', 'api'] as $file) {
            $path = $this->basePath("routes/{$file}.php");

            if (is_file($path)) {
                require $path;
            }
        }
    }

    /** Simpan URL saat ini supaya back() bisa bekerja. */
    protected function rememberUrl(Request $request, Response $response): void
    {
        if ($request->isMethod('GET') && ! $request->isAjax() && ! $response instanceof RedirectResponse) {
            Session::put('_previous_url', $request->uri());
        }
    }

    /*
    |----------------------------------------------------------------------
    | Penanganan error
    |----------------------------------------------------------------------
    */

    protected function registerErrorHandlers(): void
    {
        error_reporting(E_ALL);
        ini_set('display_errors', '0');

        set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
            if (! (error_reporting() & $severity)) {
                return false;
            }

            throw new \ErrorException($message, 0, $severity, $file, $line);
        });

        set_exception_handler(function (\Throwable $e): void {
            $this->renderException($e, Request::current())->send();
        });

        register_shutdown_function(function (): void {
            $error = error_get_last();

            if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
                $this->renderException(
                    new \ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']),
                    Request::current()
                )->send();
            }
        });
    }

    protected function renderException(\Throwable $e, Request $request): Response
    {
        // Validasi gagal -> kembalikan user ke form beserta pesan error.
        if ($e instanceof ValidationException) {
            if ($request->wantsJson()) {
                return Response::json(['message' => $e->getMessage(), 'errors' => $e->errors()], 422);
            }

            return back();
        }

        $status = $e instanceof HttpException ? $e->getStatusCode() : 500;

        $this->logException($e);

        if ($request->wantsJson()) {
            return Response::json([
                'message' => $status === 500 && ! config('app.debug') ? 'Server Error' : $e->getMessage(),
            ], $status);
        }

        if (config('app.debug') && ! $e instanceof HttpException) {
            return Response::make($this->renderDebugPage($e), 500);
        }

        // Gunakan view errors/{kode} bila tersedia.
        foreach (["errors.{$status}", 'errors.error'] as $view) {
            if (View::exists($view)) {
                return Response::view($view, ['exception' => $e, 'status' => $status], $status);
            }
        }

        return Response::make('<h1>' . $status . '</h1><p>' . e($e->getMessage()) . '</p>', $status);
    }

    protected function logException(\Throwable $e): void
    {
        $directory = $this->basePath('storage/logs');

        if (! is_dir($directory)) {
            @mkdir($directory, 0777, true);
        }

        @file_put_contents(
            $directory . '/batara.log',
            sprintf(
                "[%s] %s: %s di %s:%d%s%s%s",
                date('Y-m-d H:i:s'),
                get_class($e),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
                PHP_EOL,
                $e->getTraceAsString(),
                PHP_EOL . PHP_EOL
            ),
            FILE_APPEND
        );
    }

    /** Halaman error detail saat APP_DEBUG aktif. */
    protected function renderDebugPage(\Throwable $e): string
    {
        $snippet = '';
        $lines   = is_file($e->getFile()) ? file($e->getFile()) : [];
        $start   = max(0, $e->getLine() - 8);

        foreach (array_slice($lines, $start, 15, true) as $number => $line) {
            $current  = $number + 1 === $e->getLine();
            $snippet .= sprintf(
                '<div style="%s"><span style="color:#6b7280">%4d</span>  %s</div>',
                $current ? 'background:#7f1d1d;color:#fee2e2' : '',
                $number + 1,
                e(rtrim($line))
            );
        }

        return '<!doctype html><html lang="id"><meta charset="utf-8">'
            . '<title>Error &mdash; Sakuci</title>'
            . '<body style="margin:0;background:#0f172a;color:#e2e8f0;font:15px/1.6 system-ui,Segoe UI,sans-serif">'
            . '<div style="max-width:980px;margin:0 auto;padding:40px 24px">'
            . '<div style="color:#f87171;font-weight:600;letter-spacing:.08em;text-transform:uppercase;font-size:12px">'
            . e(get_class($e)) . '</div>'
            . '<h1 style="margin:8px 0 4px;font-size:26px;line-height:1.3">' . e($e->getMessage()) . '</h1>'
            . '<p style="color:#94a3b8;margin:0 0 24px">' . e($e->getFile()) . ':' . $e->getLine() . '</p>'
            . '<pre style="background:#020617;border:1px solid #1e293b;border-radius:10px;padding:16px;overflow:auto;'
            . 'font:13px/1.7 ui-monospace,Consolas,monospace">' . $snippet . '</pre>'
            . '<h2 style="font-size:15px;color:#94a3b8;margin:28px 0 8px">Stack trace</h2>'
            . '<pre style="background:#020617;border:1px solid #1e293b;border-radius:10px;padding:16px;overflow:auto;'
            . 'font:12px/1.7 ui-monospace,Consolas,monospace;color:#cbd5e1">' . e($e->getTraceAsString()) . '</pre>'
            . '</div></body></html>';
    }
}

