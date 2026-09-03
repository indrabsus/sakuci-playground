<?php

namespace Sakuci\Http;

use Sakuci\View;

/**
 * Representasi response yang akan dikirim ke browser.
 */
class Response
{
    protected array $headers = [];

    public function __construct(
        protected string $content = '',
        protected int $status = 200
    ) {
    }

    public static function make(string $content = '', int $status = 200): static
    {
        return new static($content, $status);
    }

    public static function view(string $view, array $data = [], int $status = 200): static
    {
        return (new static(View::make($view, $data), $status))
            ->header('Content-Type', 'text/html; charset=UTF-8');
    }

    public static function json(mixed $data, int $status = 200): static
    {
        $payload = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return (new static($payload ?: 'null', $status))
            ->header('Content-Type', 'application/json; charset=UTF-8');
    }

    public function header(string $key, string $value): static
    {
        $this->headers[$key] = $value;

        return $this;
    }

    public function withHeaders(array $headers): static
    {
        foreach ($headers as $key => $value) {
            $this->header($key, $value);
        }

        return $this;
    }

    public function status(int $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function send(): void
    {
        if (! headers_sent()) {
            http_response_code($this->status);

            if (! isset($this->headers['Content-Type'])) {
                $this->headers['Content-Type'] = 'text/html; charset=UTF-8';
            }

            foreach ($this->headers as $key => $value) {
                header($key . ': ' . $value, true);
            }
        }

        echo $this->content;
    }

    public function __toString(): string
    {
        return $this->content;
    }
}

