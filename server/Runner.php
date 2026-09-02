<?php

namespace Sakuci;

use Exception;
use PDO;

class Runner
{
    /**
     * Jalankan script PHP multi-file dalam proses terisolasi (sandbox)
     *
     * @param array<string, string> $files Array asosiatif ['nama_file.php' => 'isi_kode']
     * @param string $entrypoint File utama yang dieksekusi (default: index.php)
     * @return array Hasil eksekusi
     */
    public static function run(array $files, string $entrypoint = 'index.php'): array
    {
        $startTime = microtime(true);
        $tempBaseDir = __DIR__ . '/../data/temp';
        if (!is_dir($tempBaseDir)) {
            mkdir($tempBaseDir, 0777, true);
        }

        // Buat folder eksekusi unik per run
        $runId = uniqid('run_', true);
        $runDir = $tempBaseDir . '/' . $runId;
        mkdir($runDir, 0777, true);

        // Generate database connection helper bootstrap file
        $dbConfig = Database::getConfig();
        $driver = $dbConfig['driver'] ?? 'mysql';
        $dbBootstrapCode = "<?php\n" . self::generateDbBootstrap($dbConfig);
        $bootstrapFile = $runDir . '/_bootstrap.php';
        file_put_contents($bootstrapFile, $dbBootstrapCode);

        // Tulis semua file dan direktori pengguna ke folder eksekusi
        foreach ($files as $filename => $content) {
            // Normalisasi slashes dan hapus directory traversal '..'
            $normalized = str_replace('\\', '/', $filename);
            $parts = array_filter(explode('/', $normalized), fn($p) => $p !== '' && $p !== '.' && $p !== '..');
            $safeRelativePath = implode('/', $parts);

            if (empty($safeRelativePath)) {
                continue;
            }

            if (!str_ends_with(strtolower($safeRelativePath), '.php')) {
                $safeRelativePath .= '.php';
            }

            $fullPath = $runDir . '/' . $safeRelativePath;
            $parentDir = dirname($fullPath);
            if (!is_dir($parentDir)) {
                mkdir($parentDir, 0777, true);
            }

            // Transformasi kode MySQL pengguna ke simulasi SQLite
            $transformedContent = self::transformUserCodeForSimulation($content);

            // Pastikan tag pembuka PHP ada jika pengguna menulis kode murni
            $trimmed = trim($transformedContent);
            if (!empty($trimmed) && !str_starts_with($trimmed, '<?php') && !str_starts_with($trimmed, '<?=') && !str_starts_with($trimmed, '<html') && !str_starts_with($trimmed, '<!')) {
                $transformedContent = "<?php\n" . $transformedContent;
            }

            file_put_contents($fullPath, $transformedContent);
        }

        $safeEntrypoint = basename($entrypoint);
        if (!file_exists($runDir . '/' . $safeEntrypoint)) {
            $safeEntrypoint = 'index.php';
        }

        $phpBinary = PHP_BINARY;
        if (!file_exists($phpBinary)) {
            $phpBinary = 'php';
        }

        // Auto prepend _bootstrap.php agar simulasi MySQL dan variabel $koneksi, $conn, $pdo, $db selalu siap
        $absBootstrap = realpath($bootstrapFile);
        $extArgs = "-d extension=pdo_sqlite -d extension=sqlite3 -d memory_limit=128M -d max_execution_time=5 -d display_errors=1 -d error_reporting=E_ALL -d auto_prepend_file=\"{$absBootstrap}\"";

        $command = "\"{$phpBinary}\" {$extArgs} \"{$safeEntrypoint}\"";

        $descriptors = [
            0 => ['pipe', 'r'], // STDIN
            1 => ['pipe', 'w'], // STDOUT
            2 => ['pipe', 'w']  // STDERR
        ];

        // Jalankan proses dengan working directory di $runDir
        $process = proc_open($command, $descriptors, $pipes, $runDir);

        $stdout = '';
        $stderr = '';
        $exitCode = 0;
        $timedOut = false;

        if (is_resource($process)) {
            fclose($pipes[0]); // Tutup stdin

            // Non-blocking stream reads dengan timeout 5 detik
            stream_set_blocking($pipes[1], false);
            stream_set_blocking($pipes[2], false);

            $procTimeout = 5.0; // detik
            $procStart = microtime(true);

            while (true) {
                $status = proc_get_status($process);
                if (!$status['running']) {
                    $exitCode = $status['exitcode'];
                    break;
                }

                if ((microtime(true) - $procStart) > $procTimeout) {
                    $timedOut = true;
                    proc_terminate($process, 9);
                    $stderr .= "\n[Batas Waktu Terlampaui]: Eksekusi program melebihi batas waktu maksimal (5 detik).";
                    break;
                }

                $read1 = stream_get_contents($pipes[1]);
                if ($read1 !== false && $read1 !== '') {
                    $stdout .= $read1;
                }

                $read2 = stream_get_contents($pipes[2]);
                if ($read2 !== false && $read2 !== '') {
                    $stderr .= $read2;
                }

                usleep(15000); // 15ms
            }

            // Ambil sisa output
            $stdout .= stream_get_contents($pipes[1]);
            $stderr .= stream_get_contents($pipes[2]);

            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);
        } else {
            $stderr = "Gagal memulai proses eksekusi PHP.";
            $exitCode = 1;
        }

        $duration = round((microtime(true) - $startTime) * 1000, 2);

        if (str_contains($stderr, 'Maximum execution time of') || str_contains($stdout, 'Maximum execution time of')) {
            $timedOut = true;
            $stderr .= "\n[Batas Waktu Terlampaui]: Eksekusi program melebihi batas waktu maksimal (5 detik).";
        }

        // Hapus folder eksekusi sementara
        self::deleteDirectory($runDir);

        return [
            'success' => ($exitCode === 0 && !$timedOut),
            'stdout' => $stdout,
            'stderr' => $stderr,
            'exit_code' => $exitCode,
            'execution_time_ms' => $duration,
            'timed_out' => $timedOut,
            'driver' => 'mysql',
            'entrypoint' => $safeEntrypoint
        ];
    }

    private static function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) return;
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                self::deleteDirectory($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }

    /**
     * Transformasi pemanggilan koneksi MySQL menjadi pemanggilan simulasi SQLite
     */
    private static function transformUserCodeForSimulation(string $code): string
    {
        // 1. new PDO("mysql:host=...;dbname=...", ...) -> new PDO("sqlite:" . DB_PATH, ...)
        $code = preg_replace(
            '/new\s+PDO\s*\(\s*([\'"])mysql:[^\'"]*\\1(\s*,\s*[^,\)]+)?(\s*,\s*[^,\)]+)?(\s*,\s*[^,\)]+)?\s*\)/i',
            'new PDO("sqlite:" . DB_PATH, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC])',
            $code
        );

        // 2. mysqli_connect(...) -> sakuci_mysqli_connect(...)
        $code = preg_replace('/\bmysqli_connect\s*\(/i', 'sakuci_mysqli_connect(', $code);

        // 3. new mysqli(...) -> new SakuciMySQLi(...)
        $code = preg_replace('/\bnew\s+mysqli\s*\(/i', 'new SakuciMySQLi(', $code);

        // 4. Procedural mysqli functions
        $functions = [
            'mysqli_query',
            'mysqli_fetch_assoc',
            'mysqli_fetch_array',
            'mysqli_fetch_row',
            'mysqli_num_rows',
            'mysqli_affected_rows',
            'mysqli_insert_id',
            'mysqli_error',
            'mysqli_errno',
            'mysqli_close',
            'mysqli_connect_error',
            'mysqli_connect_errno',
            'mysqli_real_escape_string',
            'mysqli_escape_string'
        ];

        foreach ($functions as $fn) {
            $code = preg_replace('/\b' . $fn . '\s*\(/i', 'sakuci_' . $fn . '(', $code);
        }

        return $code;
    }

    private static function generateDbBootstrap(array $config): string
    {
        $mysql = $config['mysql'] ?? [];
        $host = addslashes($mysql['host'] ?? 'localhost');
        $port = (int)($mysql['port'] ?? 3306);
        $user = addslashes($mysql['user'] ?? 'root');
        $pass = addslashes($mysql['password'] ?? '');
        $database = addslashes($mysql['database'] ?? 'latihan');

        $sqlitePath = addslashes(str_replace('\\', '/', $config['sqlite']['path'] ?? (__DIR__ . '/../data/latihan.sqlite')));

        return <<<PHP
// --- Sakuci MySQL 8.0 Simulation Engine ---
if (!defined('DB_DRIVER')) define('DB_DRIVER', 'mysql');
if (!defined('DB_HOST')) define('DB_HOST', '{$host}');
if (!defined('DB_PORT')) define('DB_PORT', {$port});
if (!defined('DB_USER')) define('DB_USER', '{$user}');
if (!defined('DB_PASS')) define('DB_PASS', '{$pass}');
if (!defined('DB_NAME')) define('DB_NAME', '{$database}');
if (!defined('DB_PATH')) define('DB_PATH', '{$sqlitePath}');

if (!defined('MYSQLI_ASSOC')) define('MYSQLI_ASSOC', 1);
if (!defined('MYSQLI_NUM')) define('MYSQLI_NUM', 2);
if (!defined('MYSQLI_BOTH')) define('MYSQLI_BOTH', 3);

function get_pdo(): PDO {
    \$dsn = "sqlite:" . DB_PATH;
    \$pdo = new PDO(\$dsn, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    \$pdo->exec("PRAGMA foreign_keys = ON;");
    return \$pdo;
}

class SakuciMySQLiResult {
    private array \$rows;
    private int \$cursor = 0;
    public int \$num_rows = 0;

    public function __construct(array \$rows) {
        \$this->rows = \$rows;
        \$this->num_rows = count(\$rows);
    }

    public function fetch_assoc(): ?array {
        if (\$this->cursor >= \$this->num_rows) return null;
        return \$this->rows[\$this->cursor++];
    }

    public function fetch_array(int \$mode = MYSQLI_BOTH): array|null|false {
        if (\$this->cursor >= \$this->num_rows) return null;
        \$row = \$this->rows[\$this->cursor++];
        if (\$mode === MYSQLI_ASSOC) return \$row;
        if (\$mode === MYSQLI_NUM) return array_values(\$row);
        \$both = \$row;
        \$vals = array_values(\$row);
        foreach (\$vals as \$k => \$v) {
            \$both[\$k] = \$v;
        }
        return \$both;
    }

    public function fetch_row(): ?array {
        if (\$this->cursor >= \$this->num_rows) return null;
        return array_values(\$this->rows[\$this->cursor++]);
    }
}

class SakuciMySQLi {
    public ?PDO \$pdo = null;
    public string \$error = '';
    public int \$errno = 0;
    public ?string \$connect_error = null;
    public int \$connect_errno = 0;
    public int \$insert_id = 0;
    public int \$affected_rows = 0;

    public function __construct(string \$host = 'localhost', string \$user = 'root', string \$pass = '', string \$db = 'latihan', int \$port = 3306) {
        try {
            \$this->pdo = get_pdo();
        } catch (Throwable \$e) {
            \$this->connect_error = \$e->getMessage();
            \$this->connect_errno = 2002;
        }
    }

    public function query(string \$sql) {
        if (!\$this->pdo) return false;
        try {
            \$this->error = '';
            \$this->errno = 0;

            // Normalisasi sintaks MySQL ke SQLite
            \$normSql = preg_replace('/\\bINT(EGER)?\\s+(AUTO_INCREMENT\\s+PRIMARY\\s+KEY|PRIMARY\\s+KEY\\s+AUTO_INCREMENT)\\b/i', 'INTEGER PRIMARY KEY AUTOINCREMENT', \$sql);
            \$normSql = preg_replace('/\\bAUTO_INCREMENT\\b/i', 'AUTOINCREMENT', \$normSql);
            \$normSql = preg_replace('/\\bINT\\s+PRIMARY\\s+KEY\\b/i', 'INTEGER PRIMARY KEY', \$normSql);
            \$normSql = preg_replace('/\\bENGINE\\s*=\\s*[A-Za-z0-9_]+/i', '', \$normSql);
            \$normSql = preg_replace('/\\bDEFAULT\\s+CHARSET\\s*=\\s*[A-Za-z0-9_]+/i', '', \$normSql);
            \$normSql = preg_replace('/\\bNOW\\s*\\(\\s*\\)/i', "DATETIME('now', 'localtime')", \$normSql);

            \$stmt = \$this->pdo->query(\$normSql);
            if (\$stmt === false) return false;

            \$trim = strtoupper(trim(\$normSql));
            if (str_starts_with(\$trim, 'INSERT')) {
                \$this->insert_id = (int)\$this->pdo->lastInsertId();
                \$this->affected_rows = \$stmt->rowCount();
                return true;
            } elseif (str_starts_with(\$trim, 'UPDATE') || str_starts_with(\$trim, 'DELETE')) {
                \$this->affected_rows = \$stmt->rowCount();
                return true;
            } elseif (str_starts_with(\$trim, 'CREATE') || str_starts_with(\$trim, 'DROP') || str_starts_with(\$trim, 'ALTER')) {
                return true;
            }

            \$rows = \$stmt->fetchAll(PDO::FETCH_ASSOC);
            return new SakuciMySQLiResult(\$rows);
        } catch (Throwable \$e) {
            \$this->error = \$e->getMessage();
            \$this->errno = (int)(\$e->getCode() ?: 1064);
            return false;
        }
    }

    public function real_escape_string(string \$str): string {
        return addslashes(\$str);
    }

    public function escape_string(string \$str): string {
        return addslashes(\$str);
    }

    public function close(): bool {
        return true;
    }
}

function sakuci_mysqli_connect(string \$host = 'localhost', string \$user = 'root', string \$pass = '', string \$db = 'latihan', int \$port = 3306) {
    \$c = new SakuciMySQLi(\$host, \$user, \$pass, \$db, \$port);
    return \$c->connect_error ? false : \$c;
}

function sakuci_mysqli_query(\$conn, string \$query) {
    if (\$conn instanceof SakuciMySQLi) return \$conn->query(\$query);
    return false;
}

function sakuci_mysqli_fetch_assoc(\$result) {
    if (\$result instanceof SakuciMySQLiResult) return \$result->fetch_assoc();
    return null;
}

function sakuci_mysqli_fetch_array(\$result, int \$mode = MYSQLI_BOTH) {
    if (\$result instanceof SakuciMySQLiResult) return \$result->fetch_array(\$mode);
    return null;
}

function sakuci_mysqli_fetch_row(\$result) {
    if (\$result instanceof SakuciMySQLiResult) return \$result->fetch_row();
    return null;
}

function sakuci_mysqli_num_rows(\$result) {
    if (\$result instanceof SakuciMySQLiResult) return \$result->num_rows;
    return 0;
}

function sakuci_mysqli_affected_rows(\$conn) {
    if (\$conn instanceof SakuciMySQLi) return \$conn->affected_rows;
    return 0;
}

function sakuci_mysqli_insert_id(\$conn) {
    if (\$conn instanceof SakuciMySQLi) return \$conn->insert_id;
    return 0;
}

function sakuci_mysqli_error(\$conn) {
    if (\$conn instanceof SakuciMySQLi) return \$conn->error;
    return '';
}

function sakuci_mysqli_close(\$conn) {
    if (\$conn instanceof SakuciMySQLi) return \$conn->close();
    return true;
}

function sakuci_mysqli_connect_error(): ?string {
    return null;
}

function sakuci_mysqli_real_escape_string(\$conn, string \$str): string {
    return addslashes(\$str);
}

// Inisialisasi variabel \$pdo, \$db, \$koneksi, \$conn global agar langsung siap pakai
try {
    \$pdo = get_pdo();
    \$db = \$pdo;
    \$koneksi = new SakuciMySQLi('localhost', 'root', '', 'latihan');
    \$conn = \$koneksi;
} catch (Throwable \$e) {
    \$pdo = null;
    \$db = null;
    \$koneksi = null;
    \$conn = null;
}
// --- Sakuci MySQL 8.0 Simulation Engine End ---
PHP;
    }
}
