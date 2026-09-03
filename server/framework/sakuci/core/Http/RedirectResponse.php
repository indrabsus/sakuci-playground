<?php

namespace Sakuci\Http;

use Sakuci\MessageBag;
use Sakuci\Route;
use Sakuci\Session;

/**
 * Response redirect dengan gaya fluent:
 *
 *   return redirect('/posts')->with('success', 'Tersimpan!');
 *   return back()->withErrors($errors)->withInput();
 */
class RedirectResponse extends Response
{
    public function __construct(protected string $target = '/', int $status = 302)
    {
        $resolved = $this->resolveTarget($target);
        $cleanTarget = '/' . ltrim(parse_url($resolved, PHP_URL_PATH) ?: '/', '/');
        $html = "<!-- SAKUCI_REDIRECT: {$cleanTarget} -->\n"
              . "<!DOCTYPE html><html><head><title>Redirecting...</title></head><body style='font-family:sans-serif;background:#0f172a;color:#94a3b8;padding:24px;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;'>"
              . "<div style='text-align:center;'><p style='font-size:14px;'>Mengalihkan ke <strong>{$cleanTarget}</strong>...</p>"
              . "<script>if(window.parent && window.parent.App && window.parent !== window){window.parent.App.visitRoute('{$cleanTarget}');}</script>"
              . "</div></body></html>";
        parent::__construct($html, $status);

        $this->header('Location', $resolved);
    }

    /** Redirect ke route berdasarkan namanya. */
    public function route(string $name, array $parameters = []): static
    {
        return $this->to(Route::urlFor($name, $parameters));
    }

    public function to(string $target): static
    {
        $this->target = $target;
        $resolved = $this->resolveTarget($target);
        $cleanTarget = '/' . ltrim(parse_url($resolved, PHP_URL_PATH) ?: '/', '/');
        $this->content = "<!-- SAKUCI_REDIRECT: {$cleanTarget} -->\n"
              . "<!DOCTYPE html><html><head><title>Redirecting...</title></head><body style='font-family:sans-serif;background:#0f172a;color:#94a3b8;padding:24px;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;'>"
              . "<div style='text-align:center;'><p style='font-size:14px;'>Mengalihkan ke <strong>{$cleanTarget}</strong>...</p>"
              . "<script>if(window.parent && window.parent.App && window.parent !== window){window.parent.App.visitRoute('{$cleanTarget}');}</script>"
              . "</div></body></html>";
        $this->header('Location', $resolved);

        return $this;
    }

    /** Titipkan flash message: ->with('success', 'Berhasil') */
    public function with(string|array $key, mixed $value = null): static
    {
        foreach (is_array($key) ? $key : [$key => $value] as $k => $v) {
            Session::flash($k, $v);
        }

        return $this;
    }

    public function withErrors(MessageBag|array $errors): static
    {
        Session::flash('_errors', $errors instanceof MessageBag ? $errors : new MessageBag($errors));

        return $this;
    }

    public function withInput(?array $input = null): static
    {
        Session::flashInput($input ?? Request::current()->except(['_token', '_method']));

        return $this;
    }

    protected function resolveTarget(string $target): string
    {
        if (preg_match('#^https?://#i', $target)) {
            return $target;
        }

        return url($target);
    }

    public function send(): void
    {
        parent::send();
    }
}

