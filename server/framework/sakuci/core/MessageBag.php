<?php

namespace Sakuci;

/**
 * Wadah pesan error validasi, mirip MessageBag milik Laravel.
 */
class MessageBag implements \Countable, \JsonSerializable
{
    public function __construct(protected array $messages = [])
    {
        // Terima juga bentuk singkat ['field' => 'pesan'], bukan hanya
        // ['field' => ['pesan']] -- keduanya dipakai lewat withErrors().
        foreach ($this->messages as $key => $value) {
            if (! is_array($value)) {
                $this->messages[$key] = [$value];
            }
        }
    }

    public function add(string $key, string $message): static
    {
        $this->messages[$key][] = $message;

        return $this;
    }

    public function has(?string $key = null): bool
    {
        if ($key === null) {
            return $this->messages !== [];
        }

        return ! empty($this->messages[$key]);
    }

    public function any(): bool
    {
        return $this->messages !== [];
    }

    public function first(?string $key = null, string $default = ''): string
    {
        if ($key !== null) {
            return $this->messages[$key][0] ?? $default;
        }

        foreach ($this->messages as $messages) {
            return $messages[0] ?? $default;
        }

        return $default;
    }

    public function get(string $key): array
    {
        return $this->messages[$key] ?? [];
    }

    /** Semua pesan dalam satu array datar. */
    public function all(): array
    {
        $all = [];
        foreach ($this->messages as $messages) {
            foreach ($messages as $message) {
                $all[] = $message;
            }
        }

        return $all;
    }

    public function keys(): array
    {
        return array_keys($this->messages);
    }

    public function toArray(): array
    {
        return $this->messages;
    }

    public function count(): int
    {
        return count($this->all());
    }

    public function jsonSerialize(): mixed
    {
        return $this->messages;
    }
}

