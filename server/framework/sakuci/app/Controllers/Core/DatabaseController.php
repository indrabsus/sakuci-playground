<?php

namespace App\Controllers\Core;

use Sakuci\Controller;
use Sakuci\Database\Connection;
use Sakuci\Http\Response;

/** Ekspor seluruh isi database jadi satu file .sql, siap diimpor lagi di server lain. */
class DatabaseController extends Controller
{
    public function export(): Response
    {
        $sql = Connection::driver() === 'mysql' ? $this->dumpMysql() : $this->dumpSqlite();

        $filename = 'sakuci-' . date('Y-m-d_His') . '.sql';

        return Response::make($sql)
            ->header('Content-Type', 'application/sql; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    protected function dumpMysql(): string
    {
        $pdo    = Connection::pdo();
        $tables = array_map(fn (array $row) => array_values($row)[0], Connection::select('SHOW TABLES'));

        $output  = "-- Sakuci Framework -- database export (MySQL)\n";
        $output .= "-- Dibuat: " . date('Y-m-d H:i:s') . "\n\n";
        $output .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

        foreach ($tables as $table) {
            $create    = Connection::selectOne('SHOW CREATE TABLE `' . $table . '`');
            $createSql = $create['Create Table'] ?? '';

            $output .= "-- --------------------------------------------------------\n";
            $output .= "-- Tabel: {$table}\n";
            $output .= "-- --------------------------------------------------------\n\n";
            $output .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $output .= $createSql . ";\n\n";
            $output .= $this->dumpRows($pdo, $table, '`');
        }

        $output .= "SET FOREIGN_KEY_CHECKS = 1;\n";

        return $output;
    }

    protected function dumpSqlite(): string
    {
        $pdo    = Connection::pdo();
        $tables = array_map(
            fn (array $row) => $row['name'],
            Connection::select("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'")
        );

        $output  = "-- Sakuci Framework -- database export (SQLite)\n";
        $output .= "-- Dibuat: " . date('Y-m-d H:i:s') . "\n\n";

        foreach ($tables as $table) {
            $schema = Connection::selectOne(
                "SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ?",
                [$table]
            );

            $output .= "-- --------------------------------------------------------\n";
            $output .= "-- Tabel: {$table}\n";
            $output .= "-- --------------------------------------------------------\n\n";
            $output .= 'DROP TABLE IF EXISTS "' . $table . '";' . "\n";
            $output .= ($schema['sql'] ?? '') . ";\n\n";
            $output .= $this->dumpRows($pdo, $table, '"');
        }

        return $output;
    }

    /** Ubah seluruh baris satu tabel jadi statement INSERT, dipecah per 200 baris. */
    protected function dumpRows(\PDO $pdo, string $table, string $quote): string
    {
        $rows = Connection::select('SELECT * FROM ' . $quote . $table . $quote);

        if ($rows === []) {
            return "\n";
        }

        $columns = array_map(fn (string $col) => $quote . $col . $quote, array_keys($rows[0]));
        $output  = '';

        foreach (array_chunk($rows, 200) as $chunk) {
            $values = [];

            foreach ($chunk as $row) {
                $escaped = array_map(
                    fn ($value) => $value === null ? 'NULL' : $pdo->quote((string) $value),
                    array_values($row)
                );

                $values[] = '(' . implode(', ', $escaped) . ')';
            }

            $output .= 'INSERT INTO ' . $quote . $table . $quote . ' (' . implode(', ', $columns) . ") VALUES\n"
                . implode(",\n", $values) . ";\n";
        }

        return $output . "\n";
    }
}
