<?php

namespace Sakuci\Database;

use Sakuci\Config;

/**
 * Koneksi database berbasis PDO (SQLite atau MySQL).
 * Semua query memakai prepared statement supaya aman dari SQL injection.
 */
class Connection
{
    protected static ?\PDO $pdo = null;

    /** Riwayat query untuk kebutuhan debug. */
    protected static array $log = [];

    public static function pdo(): \PDO
    {
        if (static::$pdo instanceof \PDO) {
            return static::$pdo;
        }

        $name   = Config::get('database.default', 'sqlite');
        $config = Config::get("database.connections.{$name}");

        if (! $config) {
            throw new \RuntimeException("Koneksi database [{$name}] belum dikonfigurasi.");
        }

        $options = [
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            if (($config['driver'] ?? 'mysql') === 'sqlite') {
                $path = $config['database'];

                if ($path !== ':memory:' && ! is_file($path)) {
                    if (! is_dir(dirname($path))) {
                        mkdir(dirname($path), 0777, true);
                    }
                    touch($path);
                }

                static::$pdo = new \PDO('sqlite:' . $path, null, null, $options);
                static::$pdo->exec('PRAGMA foreign_keys = ON');
            } else {
                $dsn = sprintf(
                    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                    $config['host'] ?? '127.0.0.1',
                    $config['port'] ?? 3306,
                    $config['database'] ?? '',
                    $config['charset'] ?? 'utf8mb4'
                );

                static::$pdo = new \PDO($dsn, $config['username'] ?? 'root', $config['password'] ?? '', $options);
            }
        } catch (\PDOException $e) {
            throw new \RuntimeException(static::hint($name, $config, $e), 0, $e);
        }

        return static::$pdo;
    }

    /** Ubah error PDO menjadi pesan yang menuntun ke solusinya. */
    protected static function hint(string $name, array $config, \PDOException $e): string
    {
        $pesan = $e->getMessage();
        $saran = match (true) {
            str_contains($pesan, 'could not find driver') && ($config['driver'] ?? '') === 'sqlite'
                => 'Ekstensi pdo_sqlite belum aktif. Buka php.ini, hilangkan titik-koma pada baris "extension=pdo_sqlite", lalu restart server.',
            str_contains($pesan, 'could not find driver')
                => 'Ekstensi pdo_mysql belum aktif. Buka php.ini, hilangkan titik-koma pada baris "extension=pdo_mysql", lalu restart server.',
            str_contains($pesan, 'Access denied')
                => 'Username atau password salah. Periksa DB_USERNAME dan DB_PASSWORD di file .env.',
            str_contains($pesan, 'Unknown database')
                => 'Database "' . ($config['database'] ?? '') . '" belum ada. Buat dulu lewat phpMyAdmin '
                   . 'atau jalankan: CREATE DATABASE ' . ($config['database'] ?? '') . ';',
            str_contains($pesan, 'Connection refused') || str_contains($pesan, 'No connection')
                => 'Server MySQL tidak berjalan. Nyalakan MySQL di XAMPP/Laragon, atau periksa DB_HOST dan DB_PORT di .env.',
            default => 'Periksa kembali pengaturan database di file .env.',
        };

        return 'Gagal terhubung ke database [' . $name . ']. ' . $saran . ' (Pesan asli: ' . $pesan . ')';
    }

    public static function driver(): string
    {
        return Config::get('database.connections.' . Config::get('database.default', 'sqlite') . '.driver', 'sqlite');
    }

    public static function run(string $sql, array $bindings = []): \PDOStatement
    {
        $start = microtime(true);

        $statement = static::pdo()->prepare($sql);
        $statement->execute(static::normalize($bindings));

        static::$log[] = [
            'sql'      => $sql,
            'bindings' => $bindings,
            'time'     => round((microtime(true) - $start) * 1000, 2),
        ];

        return $statement;
    }

    public static function select(string $sql, array $bindings = []): array
    {
        return static::run($sql, $bindings)->fetchAll();
    }

    public static function selectOne(string $sql, array $bindings = []): ?array
    {
        $row = static::run($sql, $bindings)->fetch();

        return $row === false ? null : $row;
    }

    public static function statement(string $sql, array $bindings = []): int
    {
        return static::run($sql, $bindings)->rowCount();
    }

    public static function insertGetId(string $sql, array $bindings = []): string
    {
        static::run($sql, $bindings);

        return static::pdo()->lastInsertId();
    }

    public static function transaction(\Closure $callback): mixed
    {
        $pdo = static::pdo();
        $pdo->beginTransaction();

        try {
            $result = $callback();
            $pdo->commit();

            return $result;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function log(): array
    {
        return static::$log;
    }

    /** PDO tidak menerima bool/objek mentah sebagai binding. */
    protected static function normalize(array $bindings): array
    {
        foreach ($bindings as $key => $value) {
            if (is_bool($value)) {
                $bindings[$key] = $value ? 1 : 0;
            } elseif ($value instanceof \DateTimeInterface) {
                $bindings[$key] = $value->format('Y-m-d H:i:s');
            } elseif (is_array($value)) {
                $bindings[$key] = json_encode($value, JSON_UNESCAPED_UNICODE);
            }
        }

        return array_values($bindings);
    }
}

