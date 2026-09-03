<?php

namespace Sakuci;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $connection = null;
    private static ?string $activeDriver = null;
    private static string $sqliteFile = __DIR__ . '/../data/latihan.sqlite';
    private static array $envConfig = [];

    /**
     * Baca konfigurasi .env root atau environment variables
     */
    public static function loadEnv(): array
    {
        if (!empty(self::$envConfig)) {
            return self::$envConfig;
        }

        $envFile = __DIR__ . '/../.env';
        $vars = [];
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || str_starts_with($line, '#')) continue;
                if (str_contains($line, '=')) {
                    [$key, $val] = explode('=', $line, 2);
                    $key = trim($key);
                    $val = trim($val, " \t\n\r\0\x0B\"'");
                    $vars[$key] = $val;
                }
            }
        }

        self::$envConfig = [
            'connection' => getenv('DB_CONNECTION') ?: ($vars['DB_CONNECTION'] ?? 'mysql'),
            'host'       => getenv('DB_HOST') ?: ($vars['DB_HOST'] ?? '127.0.0.1'),
            'port'       => getenv('DB_PORT') ?: ($vars['DB_PORT'] ?? '3306'),
            'database'   => getenv('DB_DATABASE') ?: ($vars['DB_DATABASE'] ?? 'sakuci_ground'),
            'username'   => getenv('DB_USERNAME') ?: ($vars['DB_USERNAME'] ?? 'root'),
            'password'   => getenv('DB_PASSWORD') ?: ($vars['DB_PASSWORD'] ?? ''),
            'sqlite_path'=> getenv('DB_SQLITE_PATH') ?: ($vars['DB_SQLITE_PATH'] ?? self::$sqliteFile)
        ];

        return self::$envConfig;
    }

    public static function getConfig(): array
    {
        $env = self::loadEnv();
        $isMySql = ($env['connection'] === 'mysql');

        return [
            'driver' => $isMySql ? 'mysql' : 'sqlite',
            'is_simulated' => false,
            'driver_label' => $isMySql ? 'MySQL 8.0 (aaPanel / Production)' : 'SQLite 3 (Fallback)',
            'mysql' => [
                'host' => $env['host'],
                'port' => (int)$env['port'],
                'user' => $env['username'],
                'password' => $env['password'],
                'database' => $env['database']
            ],
            'sqlite' => [
                'path' => $env['sqlite_path']
            ]
        ];
    }

    public static function saveConfig(array $newConfig): bool
    {
        return true;
    }

    public static function getActiveDriver(): string
    {
        if (self::$activeDriver === null) {
            self::getConnection();
        }
        return self::$activeDriver ?: 'mysql';
    }

    public static function getConnection(?array $customConfig = null): PDO
    {
        if (self::$connection !== null && $customConfig === null) {
            return self::$connection;
        }

        $env = self::loadEnv();
        $targetDriver = $customConfig['driver'] ?? $env['connection'];

        // Coba koneksi MySQL jika target adalah MySQL
        if ($targetDriver === 'mysql') {
            try {
                $host = $customConfig['host'] ?? $env['host'];
                $port = $customConfig['port'] ?? $env['port'];
                $dbName = $customConfig['database'] ?? $env['database'];
                $user = $customConfig['username'] ?? $env['username'];
                $pass = $customConfig['password'] ?? $env['password'];

                $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4";
                $pdo = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ]);

                if ($customConfig === null) {
                    self::$connection = $pdo;
                    self::$activeDriver = 'mysql';
                    // Jalankan auto migrate skema jika tabel belum ada
                    self::ensureSchema($pdo, 'mysql');
                }
                return $pdo;
            } catch (PDOException $e) {
                // Jika koneksi MySQL gagal dan ini panggilan default, fallback ke SQLite
                if ($customConfig === null) {
                    error_log("MySQL connection failed ({$e->getMessage()}), falling back to SQLite.");
                } else {
                    throw $e;
                }
            }
        }

        // Fallback SQLite
        $sqlitePath = $env['sqlite_path'];
        if (!str_starts_with($sqlitePath, '/') && !preg_match('/^[A-Za-z]:[\\\\\/]/', $sqlitePath)) {
            $sqlitePath = __DIR__ . '/../' . ltrim($sqlitePath, './\\');
        }
        $dir = dirname($sqlitePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $dsn = "sqlite:" . $sqlitePath;
        $pdo = new PDO($dsn, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        $pdo->exec("PRAGMA foreign_keys = ON;");

        if ($customConfig === null) {
            self::$connection = $pdo;
            self::$activeDriver = 'sqlite';
            self::ensureSchema($pdo, 'sqlite');
        }
        return $pdo;
    }

    /**
     * Buat tabel sistem (users, user_sessions, user_files, dll) jika belum ada
     */
    public static function ensureSchema(PDO $pdo, string $driver): void
    {
        try {
            if ($driver === 'mysql') {
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS `users` (
                        `id` INT AUTO_INCREMENT PRIMARY KEY,
                        `name` VARCHAR(100) NOT NULL,
                        `username` VARCHAR(50) NOT NULL UNIQUE,
                        `email` VARCHAR(100) NOT NULL UNIQUE,
                        `password_hash` VARCHAR(255) NOT NULL,
                        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                ");

                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS `user_sessions` (
                        `id` INT AUTO_INCREMENT PRIMARY KEY,
                        `user_id` INT NOT NULL,
                        `session_token` VARCHAR(64) NOT NULL UNIQUE,
                        `ip_address` VARCHAR(45) NULL,
                        `user_agent` TEXT NULL,
                        `expires_at` DATETIME NOT NULL,
                        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                        INDEX `idx_user_token` (`user_id`, `session_token`),
                        CONSTRAINT `fk_session_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                ");

                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS `user_files` (
                        `id` INT AUTO_INCREMENT PRIMARY KEY,
                        `user_id` INT NOT NULL,
                        `mode` VARCHAR(20) NOT NULL DEFAULT 'native',
                        `file_path` VARCHAR(255) NOT NULL,
                        `content` MEDIUMTEXT NOT NULL,
                        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        UNIQUE KEY `unique_user_mode_file` (`user_id`, `mode`, `file_path`),
                        CONSTRAINT `fk_file_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                ");

                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS `user_preferences` (
                        `user_id` INT PRIMARY KEY,
                        `playground_mode` VARCHAR(20) DEFAULT 'native',
                        `active_file` VARCHAR(255) DEFAULT 'index.php',
                        `open_tabs_json` TEXT NULL,
                        `split_ratio` DECIMAL(5,2) DEFAULT 50.00,
                        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        CONSTRAINT `fk_pref_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                ");

                // Inisialisasi tabel latihan template (mahasiswa, kategori, produk, transaksi)
                $checkMhs = $pdo->query("SHOW TABLES LIKE 'mahasiswa'")->fetch();
                if (!$checkMhs) {
                    SeedData::seed();
                }
            } else {
                // SQLite Schema
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS users (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        name TEXT NOT NULL,
                        username TEXT NOT NULL UNIQUE,
                        email TEXT NOT NULL UNIQUE,
                        password_hash TEXT NOT NULL,
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                    );
                ");

                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS user_sessions (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        user_id INTEGER NOT NULL,
                        session_token TEXT NOT NULL UNIQUE,
                        ip_address TEXT NULL,
                        user_agent TEXT NULL,
                        expires_at DATETIME NOT NULL,
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                    );
                ");

                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS user_files (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        user_id INTEGER NOT NULL,
                        mode TEXT NOT NULL DEFAULT 'native',
                        file_path TEXT NOT NULL,
                        content TEXT NOT NULL,
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                        UNIQUE(user_id, mode, file_path),
                        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                    );
                ");

                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS user_preferences (
                        user_id INTEGER PRIMARY KEY,
                        playground_mode TEXT DEFAULT 'native',
                        active_file TEXT DEFAULT 'index.php',
                        open_tabs_json TEXT NULL,
                        split_ratio REAL DEFAULT 50.0,
                        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                    );
                ");

                $checkMhs = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='mahasiswa'")->fetch();
                if (!$checkMhs) {
                    SeedData::seed();
                }
            }
        } catch (\Throwable $e) {
            error_log("EnsureSchema notice: " . $e->getMessage());
        }
    }

    public static function testConnection(?array $config = null): array
    {
        try {
            $pdo = self::getConnection($config);
            $driver = self::$activeDriver ?: 'mysql';
            $version = $pdo->query('SELECT VERSION()')->fetchColumn();
            return [
                'success' => true,
                'message' => "Terhubung ke MySQL Server! (Versi: {$version})",
                'version' => $version,
                'driver' => $driver
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => "Koneksi Database Gagal: " . $e->getMessage(),
                'driver' => 'mysql'
            ];
        }
    }

    public static function getTables(): array
    {
        try {
            $pdo = self::getConnection();
            $driver = self::$activeDriver;
            $tables = [];

            if ($driver === 'mysql') {
                $stmt = $pdo->query("SHOW TABLES");
                $results = $stmt->fetchAll(PDO::FETCH_NUM);
                foreach ($results as $r) {
                    $tableName = $r[0];
                    // Sembunyikan tabel sistem dari viewer latihan agar siswa fokus pada tabel latihan
                    if (in_array($tableName, ['users', 'user_sessions', 'user_files', 'user_preferences'])) {
                        continue;
                    }
                    $countStmt = $pdo->query("SELECT COUNT(*) FROM `{$tableName}`");
                    $realCount = $countStmt ? (int)$countStmt->fetchColumn() : 0;
                    $tables[] = [
                        'name' => $tableName,
                        'rows' => $realCount,
                        'size_kb' => 0
                    ];
                }
            } else {
                $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name");
                $results = $stmt->fetchAll();
                foreach ($results as $r) {
                    $tableName = $r['name'];
                    if (in_array($tableName, ['users', 'user_sessions', 'user_files', 'user_preferences'])) {
                        continue;
                    }
                    $countStmt = $pdo->query("SELECT COUNT(*) FROM \"{$tableName}\"");
                    $realCount = $countStmt ? (int)$countStmt->fetchColumn() : 0;
                    $tables[] = [
                        'name' => $tableName,
                        'rows' => $realCount,
                        'size_kb' => 0
                    ];
                }
            }

            return ['success' => true, 'tables' => $tables, 'driver' => $driver];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public static function getTableColumns(string $tableName): array
    {
        try {
            $pdo = self::getConnection();
            $driver = self::$activeDriver;
            $columns = [];

            if ($driver === 'mysql') {
                $safeName = str_replace('`', '``', $tableName);
                $stmt = $pdo->query("DESCRIBE `{$safeName}`");
                foreach ($stmt->fetchAll() as $col) {
                    $columns[] = [
                        'Field' => $col['Field'],
                        'Type' => $col['Type'],
                        'Null' => $col['Null'],
                        'Key' => $col['Key'],
                        'Default' => $col['Default'],
                        'Extra' => $col['Extra']
                    ];
                }
            } else {
                $safeName = str_replace('"', '""', $tableName);
                $stmt = $pdo->query("PRAGMA table_info(\"{$safeName}\")");
                foreach ($stmt->fetchAll() as $col) {
                    $columns[] = [
                        'Field' => $col['name'],
                        'Type' => $col['type'] ?: 'TEXT',
                        'Null' => $col['notnull'] ? 'NO' : 'YES',
                        'Key' => $col['pk'] ? 'PRI' : '',
                        'Default' => $col['dflt_value'],
                        'Extra' => $col['pk'] ? 'auto_increment' : ''
                    ];
                }
            }

            return ['success' => true, 'columns' => $columns];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public static function getTableData(string $tableName, int $page = 1, int $perPage = 25): array
    {
        try {
            $pdo = self::getConnection();
            $driver = self::$activeDriver;
            $quote = ($driver === 'mysql') ? '`' : '"';
            $safeName = str_replace($quote, $quote . $quote, $tableName);

            $countStmt = $pdo->query("SELECT COUNT(*) FROM {$quote}{$safeName}{$quote}");
            $totalRows = (int)$countStmt->fetchColumn();

            $offset = ($page - 1) * $perPage;
            $dataStmt = $pdo->query("SELECT * FROM {$quote}{$safeName}{$quote} LIMIT {$perPage} OFFSET {$offset}");
            $rows = $dataStmt->fetchAll();

            $colRes = self::getTableColumns($tableName);
            $columns = $colRes['columns'] ?? [];

            return [
                'success' => true,
                'table' => $tableName,
                'columns' => $columns,
                'rows' => $rows,
                'total_rows' => $totalRows,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => ceil($totalRows / $perPage) ?: 1
            ];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public static function executeQuery(string $query): array
    {
        $startTime = microtime(true);
        try {
            $pdo = self::getConnection();
            $driver = self::$activeDriver;
            $trimmed = trim($query);

            if (empty($trimmed)) {
                return ['success' => false, 'message' => 'Query tidak boleh kosong.'];
            }

            $upper = strtoupper($trimmed);
            $isSelect = str_starts_with($upper, 'SELECT') || 
                        str_starts_with($upper, 'SHOW') || 
                        str_starts_with($upper, 'DESC') ||
                        str_starts_with($upper, 'PRAGMA') ||
                        str_starts_with($upper, 'EXPLAIN');

            if ($isSelect) {
                $stmt = $pdo->query($trimmed);
                $rows = $stmt->fetchAll();
                $columns = [];
                if (count($rows) > 0) {
                    $columns = array_keys($rows[0]);
                }

                $duration = round((microtime(true) - $startTime) * 1000, 2);
                return [
                    'success' => true,
                    'type' => 'select',
                    'columns' => $columns,
                    'rows' => $rows,
                    'row_count' => count($rows),
                    'duration_ms' => $duration
                ];
            } else {
                $affected = $pdo->exec($trimmed);
                $duration = round((microtime(true) - $startTime) * 1000, 2);
                return [
                    'success' => true,
                    'type' => 'dml',
                    'affected_rows' => $affected,
                    'duration_ms' => $duration,
                    'message' => "Query berhasil dieksekusi ({$duration} ms). Baris terpengaruh: {$affected}"
                ];
            }
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'SQL Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Memastikan tabel latihan user (u{userId}_mahasiswa, etc.) ada di MySQL
     */
    public static function ensureUserPracticeTables(int $userId): void
    {
        if ($userId <= 0) return;
        try {
            $pdo = self::getConnection();
            $driver = self::$activeDriver;
            $prefix = "u{$userId}_";

            $baseTables = ['mahasiswa', 'kategori', 'produk', 'transaksi'];
            foreach ($baseTables as $tbl) {
                $userTable = $prefix . $tbl;
                if ($driver === 'mysql') {
                    $exists = $pdo->query("SHOW TABLES LIKE '{$userTable}'")->fetch();
                    if (!$exists) {
                        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$userTable}` LIKE `{$tbl}`;");
                        $pdo->exec("INSERT INTO `{$userTable}` SELECT * FROM `{$tbl}`;");
                    }
                } else {
                    $exists = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='{$userTable}'")->fetch();
                    if (!$exists) {
                        $pdo->exec("CREATE TABLE IF NOT EXISTS \"{$userTable}\" AS SELECT * FROM \"{$tbl}\";");
                    }
                }
            }
        } catch (\Throwable $e) {
            error_log("ensureUserPracticeTables: " . $e->getMessage());
        }
    }
}
