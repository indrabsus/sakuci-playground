<?php

namespace Sakuci;

/**
 * Penyimpan konfigurasi aplikasi.
 * Semua file di folder config/ dimuat memakai nama file sebagai key utama.
 * Contoh: config/app.php -> config('app.name')
 */
class Config
{
    protected static array $items = [];

    public static function load(string $path): void
    {
        foreach (glob(rtrim($path, '/\\') . '/*.php') as $file) {
            static::$items[basename($file, '.php')] = require $file;
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = static::$items;

        foreach (explode('.', $key) as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    public static function set(string $key, mixed $value): void
    {
        $segments = explode('.', $key);
        $items    = &static::$items;

        while (count($segments) > 1) {
            $segment = array_shift($segments);
            if (! isset($items[$segment]) || ! is_array($items[$segment])) {
                $items[$segment] = [];
            }
            $items = &$items[$segment];
        }

        $items[array_shift($segments)] = $value;
    }

    public static function all(): array
    {
        return static::$items;
    }
}

