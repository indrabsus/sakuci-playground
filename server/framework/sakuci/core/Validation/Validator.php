<?php

namespace Sakuci\Validation;

use Sakuci\Database\Connection;
use Sakuci\MessageBag;

/**
 * Validator sederhana dengan sintaks aturan seperti Laravel.
 *
 *   $data = $request->validate([
 *       'title' => 'required|min:3|max:120',
 *       'email' => 'required|email|unique:users,email',
 *       'umur'  => 'nullable|integer|min:17',
 *   ]);
 *
 * Aturan tersedia: required, nullable, email, url, numeric, integer, string,
 * boolean, array, min, max, between, size, in, not_in, regex, alpha,
 * alpha_num, alpha_dash, date, confirmed, same, different, unique, exists.
 */
class Validator
{
    protected MessageBag $errors;

    protected array $validated = [];

    public function __construct(
        protected array $data,
        protected array $rules,
        protected array $messages = [],
        protected array $labels = []
    ) {
        $this->errors = new MessageBag();
    }

    public static function make(array $data, array $rules, array $messages = [], array $labels = []): static
    {
        $validator = new static($data, $rules, $messages, $labels);
        $validator->run();

        return $validator;
    }

    public function run(): void
    {
        foreach ($this->rules as $field => $ruleSet) {
            $rules = is_array($ruleSet) ? $ruleSet : explode('|', $ruleSet);
            $value = $this->data[$field] ?? null;

            $nullable = in_array('nullable', $rules, true);
            $isEmpty  = $value === null || $value === '' || $value === [];

            // Kolom nullable yang kosong tidak perlu diperiksa lebih lanjut.
            if ($nullable && $isEmpty) {
                $this->validated[$field] = $value;
                continue;
            }

            foreach ($rules as $rule) {
                if ($rule === 'nullable' || $rule === '') {
                    continue;
                }

                [$name, $parameters] = $this->parseRule($rule);

                if (! $this->passes($name, $field, $value, $parameters)) {
                    $this->errors->add($field, $this->message($field, $name, $parameters));
                    continue 2; // satu pesan per field sudah cukup
                }
            }

            $this->validated[$field] = $value;
        }
    }

    /** @return array{0: string, 1: array} */
    protected function parseRule(string $rule): array
    {
        if (! str_contains($rule, ':')) {
            return [$rule, []];
        }

        [$name, $parameter] = explode(':', $rule, 2);

        return [$name, explode(',', $parameter)];
    }

    protected function passes(string $rule, string $field, mixed $value, array $parameters): bool
    {
        return match ($rule) {
            'required'  => ! ($value === null || $value === '' || $value === []),
            'email'     => (bool) filter_var((string) $value, FILTER_VALIDATE_EMAIL),
            'url'       => (bool) filter_var((string) $value, FILTER_VALIDATE_URL),
            'numeric'   => is_numeric($value),
            'integer'   => filter_var($value, FILTER_VALIDATE_INT) !== false,
            'string'    => is_string($value),
            'boolean'   => in_array($value, [true, false, 0, 1, '0', '1', 'on', 'off'], true),
            'array'     => is_array($value),
            'alpha'     => (bool) preg_match('/^[\pL\pM]+$/u', (string) $value),
            'alpha_num' => (bool) preg_match('/^[\pL\pM\pN]+$/u', (string) $value),
            'alpha_dash'=> (bool) preg_match('/^[\pL\pM\pN_-]+$/u', (string) $value),
            'date'      => strtotime((string) $value) !== false,
            'regex'     => (bool) preg_match($parameters[0] ?? '//', (string) $value),
            'min'       => $this->size($value) >= (float) ($parameters[0] ?? 0),
            'max'       => $this->size($value) <= (float) ($parameters[0] ?? 0),
            'size'      => $this->size($value) == (float) ($parameters[0] ?? 0),
            'between'   => $this->size($value) >= (float) ($parameters[0] ?? 0)
                           && $this->size($value) <= (float) ($parameters[1] ?? 0),
            'in'        => in_array((string) $value, $parameters, true),
            'not_in'    => ! in_array((string) $value, $parameters, true),
            'confirmed' => ($this->data[$field . '_confirmation'] ?? null) === $value,
            'same'      => ($this->data[$parameters[0] ?? ''] ?? null) === $value,
            'different' => ($this->data[$parameters[0] ?? ''] ?? null) !== $value,
            'unique'    => $this->uniqueCheck($field, $value, $parameters),
            'exists'    => $this->existsCheck($field, $value, $parameters),
            default     => true,
        };
    }

    /** Angka dibandingkan nilainya, teks dibandingkan panjangnya. */
    protected function size(mixed $value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        if (is_array($value)) {
            return (float) count($value);
        }

        return (float) mb_strlen((string) $value);
    }

    /** unique:tabel,kolom,idYangDikecualikan */
    protected function uniqueCheck(string $field, mixed $value, array $parameters): bool
    {
        $table  = $parameters[0] ?? '';
        $column = $parameters[1] ?? $field;
        $ignore = $parameters[2] ?? null;

        if ($table === '') {
            return true;
        }

        $sql      = "SELECT COUNT(*) AS total FROM \"{$table}\" WHERE \"{$column}\" = ?";
        $bindings = [$value];

        if ($ignore !== null) {
            $sql .= ' AND "' . ($parameters[3] ?? 'id') . '" <> ?';
            $bindings[] = $ignore;
        }

        if (Connection::driver() === 'mysql') {
            $sql = str_replace('"', '`', $sql);
        }

        return (int) (Connection::selectOne($sql, $bindings)['total'] ?? 0) === 0;
    }

    /** exists:tabel,kolom */
    protected function existsCheck(string $field, mixed $value, array $parameters): bool
    {
        $table  = $parameters[0] ?? '';
        $column = $parameters[1] ?? $field;

        if ($table === '') {
            return true;
        }

        $sql = "SELECT COUNT(*) AS total FROM \"{$table}\" WHERE \"{$column}\" = ?";

        if (Connection::driver() === 'mysql') {
            $sql = str_replace('"', '`', $sql);
        }

        return (int) (Connection::selectOne($sql, [$value])['total'] ?? 0) > 0;
    }

    protected function message(string $field, string $rule, array $parameters): string
    {
        // Pesan khusus: ['title.required' => '...'] atau ['title' => '...']
        foreach ([$field . '.' . $rule, $field] as $key) {
            if (isset($this->messages[$key])) {
                return $this->messages[$key];
            }
        }

        $label = $this->labels[$field] ?? str_replace('_', ' ', $field);
        $first = $parameters[0] ?? '';

        return match ($rule) {
            'required'   => "Kolom {$label} wajib diisi.",
            'email'      => "Kolom {$label} harus berupa alamat email yang valid.",
            'url'        => "Kolom {$label} harus berupa URL yang valid.",
            'numeric'    => "Kolom {$label} harus berupa angka.",
            'integer'    => "Kolom {$label} harus berupa bilangan bulat.",
            'string'     => "Kolom {$label} harus berupa teks.",
            'boolean'    => "Kolom {$label} harus bernilai benar atau salah.",
            'array'      => "Kolom {$label} harus berupa array.",
            'alpha'      => "Kolom {$label} hanya boleh berisi huruf.",
            'alpha_num'  => "Kolom {$label} hanya boleh berisi huruf dan angka.",
            'alpha_dash' => "Kolom {$label} hanya boleh berisi huruf, angka, tanda hubung, dan garis bawah.",
            'date'       => "Kolom {$label} harus berupa tanggal yang valid.",
            'regex'      => "Format kolom {$label} tidak sesuai.",
            'min'        => "Kolom {$label} minimal {$first}.",
            'max'        => "Kolom {$label} maksimal {$first}.",
            'size'       => "Kolom {$label} harus berukuran {$first}.",
            'between'    => "Kolom {$label} harus di antara {$first} dan " . ($parameters[1] ?? '') . '.',
            'in'         => "Kolom {$label} berisi pilihan yang tidak valid.",
            'not_in'     => "Kolom {$label} berisi pilihan yang tidak diizinkan.",
            'confirmed'  => "Konfirmasi kolom {$label} tidak cocok.",
            'same'       => "Kolom {$label} harus sama dengan {$first}.",
            'different'  => "Kolom {$label} harus berbeda dengan {$first}.",
            'unique'     => "Nilai {$label} sudah digunakan.",
            'exists'     => "Nilai {$label} tidak ditemukan.",
            default      => "Kolom {$label} tidak valid.",
        };
    }

    public function fails(): bool
    {
        return $this->errors->any();
    }

    public function passesAll(): bool
    {
        return ! $this->fails();
    }

    public function errors(): MessageBag
    {
        return $this->errors;
    }

    public function validated(): array
    {
        return $this->validated;
    }
}

