<?php

namespace Sakuci;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $connection = null;
    private static string $configFile = __DIR__ . '/../data/db_config.json';
    private static string $sqliteFile = __DIR__ . '/../data/latihan.sqlite';

    public static function getConfig(): array
    {
        return [
            'driver' => 'mysql',
            'is_simulated' => true,
            'driver_label' => 'MySQL 8.0 (Simulasi)',
            'mysql' => [
                'host' => 'localhost',
                'port' => 3306,
                'user' => 'root',
                'password' => '',
                'database' => 'latihan'
            ],
            'sqlite' => [
                'path' => self::$sqliteFile
            ]
        ];
    }

    public static function saveConfig(array $newConfig): bool
    {
        // Tetap menjaga konfigurasi simulasi MySQL
        return true;
    }

    public static function getConnection(?array $customConfig = null): PDO
    {
        if (self::$connection !== null && $customConfig === null) {
            return self::$connection;
        }

        $sqlitePath = self::$sqliteFile;
        $dir = dirname($sqlitePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $dsn = "sqlite:" . $sqlitePath;
        $pdo = new PDO($dsn, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        // SQLite performance & foreign key pragma
        $pdo->exec("PRAGMA foreign_keys = ON;");

        if ($customConfig === null) {
            self::$connection = $pdo;
        }
        return $pdo;
    }

    public static function testConnection(array $config): array
    {
        return [
            'success' => true,
            'message' => "Simulasi MySQL 8.0 terhubung! (Host: localhost, User: root, Database: latihan)",
            'version' => "8.0.36-simulated",
            'driver' => 'mysql'
        ];
    }

    public static function getTables(): array
    {
        try {
            $pdo = self::getConnection();
            $tables = [];

            $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name");
            $results = $stmt->fetchAll();
            foreach ($results as $r) {
                $tableName = $r['name'];
                $countStmt = $pdo->query("SELECT COUNT(*) FROM \"{$tableName}\"");
                $realCount = $countStmt ? (int)$countStmt->fetchColumn() : 0;
                $tables[] = [
                    'name' => $tableName,
                    'rows' => $realCount,
                    'size_kb' => 0
                ];
            }

            return ['success' => true, 'tables' => $tables];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public static function getTableColumns(string $tableName): array
    {
        try {
            $pdo = self::getConnection();
            $safeName = str_replace('"', '""', $tableName);
            $stmt = $pdo->query("PRAGMA table_info(\"{$safeName}\")");
            $columns = [];

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

            return ['success' => true, 'columns' => $columns];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public static function getTableData(string $tableName, int $page = 1, int $perPage = 25): array
    {
        try {
            $pdo = self::getConnection();
            $safeName = str_replace('"', '""', $tableName);

            $countStmt = $pdo->query("SELECT COUNT(*) FROM \"{$safeName}\"");
            $totalRows = (int)$countStmt->fetchColumn();

            $offset = ($page - 1) * $perPage;
            $dataStmt = $pdo->query("SELECT * FROM \"{$safeName}\" LIMIT {$perPage} OFFSET {$offset}");
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
            $trimmed = trim($query);

            if (empty($trimmed)) {
                return ['success' => false, 'message' => 'Query tidak boleh kosong.'];
            }

            // Normalisasi sintaks MySQL ke SQLite
            $normalized = self::normalizeMySqlToSqlite($trimmed);

            $upper = strtoupper($normalized);
            $isSelect = str_starts_with($upper, 'SELECT') || 
                        str_starts_with($upper, 'PRAGMA') || 
                        str_starts_with($upper, 'SHOW') || 
                        str_starts_with($upper, 'DESC');

            if ($isSelect) {
                // Tangani SHOW TABLES
                if (preg_match('/^SHOW\s+TABLES/i', $trimmed)) {
                    $normalized = "SELECT name as Tables_in_latihan FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'";
                }
                // Tangani DESC / DESCRIBE nama_tabel
                elseif (preg_match('/^(DESC|DESCRIBE)\s+`?([a-zA-Z0-9_]+)`?/i', $trimmed, $m)) {
                    return self::getTableColumns($m[2]);
                }

                $stmt = $pdo->query($normalized);
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
                $affected = $pdo->exec($normalized);
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

    public static function normalizeMySqlToSqlite(string $sql): string
    {
        // INT AUTO_INCREMENT PRIMARY KEY -> INTEGER PRIMARY KEY AUTOINCREMENT
        $sql = preg_replace('/\bINT(EGER)?\s+(AUTO_INCREMENT\s+PRIMARY\s+KEY|PRIMARY\s+KEY\s+AUTO_INCREMENT)\b/i', 'INTEGER PRIMARY KEY AUTOINCREMENT', $sql);
        $sql = preg_replace('/\bAUTO_INCREMENT\b/i', 'AUTOINCREMENT', $sql);
        $sql = preg_replace('/\bINT\s+PRIMARY\s+KEY\b/i', 'INTEGER PRIMARY KEY', $sql);
        $sql = preg_replace('/\bENGINE\s*=\s*[A-Za-z0-9_]+/i', '', $sql);
        $sql = preg_replace('/\bDEFAULT\s+CHARSET\s*=\s*[A-Za-z0-9_]+/i', '', $sql);
        $sql = preg_replace('/\bCOLLATE\s*=\s*[A-Za-z0-9_]+/i', '', $sql);
        $sql = preg_replace('/\bNOW\s*\(\s*\)/i', "DATETIME('now', 'localtime')", $sql);
        return $sql;
    }
}
