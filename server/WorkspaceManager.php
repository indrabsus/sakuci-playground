<?php

namespace Sakuci;

use PDO;

class WorkspaceManager
{
    /**
     * Dapatkan file default untuk mode Native (PHP Murni)
     */
    public static function getDefaultNativeFiles(): array
    {
        return [
            'index.php' => "<?php\n\n// Sakuci PHP & MySQL Ground - Mode PHP Murni\n\necho \"<h1>Selamat Datang di Sakuci Ground! 🐘</h1>\";\necho \"<p>Server PHP aktif: \" . phpversion() . \"</p>\";\necho \"<p>Waktu server: \" . date('Y-m-d H:i:s') . \"</p>\";\n\nrequire_once 'koneksi.php';\n\ntry {\n    \$stmt = \$pdo->query(\"SELECT * FROM mahasiswa LIMIT 5\");\n    echo \"<h3>Daftar Mahasiswa (MySQL / SQLite):</h3><ul>\";\n    while (\$m = \$stmt->fetch()) {\n        echo \"<li><strong>\" . htmlspecialchars(\$m['nim']) . \"</strong> - \" . htmlspecialchars(\$m['nama']) . \" (\" . htmlspecialchars(\$m['jurusan']) . \")</li>\";\n    }\n    echo \"</ul>\";\n} catch (Exception \$e) {\n    echo \"<p style='color:red;'>Query error: \" . htmlspecialchars(\$e->getMessage()) . \"</p>\";\n}\n",
            'koneksi.php' => "<?php\n\n// Konfigurasi Koneksi Database\n\$driver = 'mysql'; // atau 'sqlite'\n\ntry {\n    if (\$driver === 'mysql') {\n        // Otomatis terhubung ke database sesi pengguna\n        \$pdo = new PDO(\"mysql:host=localhost;dbname=sakuci_ground;charset=utf8mb4\", \"root\", \"\", [\n            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,\n            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC\n        ]);\n    } else {\n        \$pdo = new PDO(\"sqlite:\" . __DIR__ . \"/../data/latihan.sqlite\", null, null, [\n            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,\n            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC\n        ]);\n    }\n} catch (PDOException \$e) {\n    die(\"Koneksi database gagal: \" . \$e->getMessage());\n}\n"
        ];
    }

    /**
     * Dapatkan folder default untuk mode Sakuci Framework MVC
     */
    public static function getDefaultFrameworkFolders(): array
    {
        return [
            'app',
            'app/Controllers',
            'app/Controllers/Core',
            'app/Middleware',
            'app/Models',
            'config',
            'core',
            'core/Database',
            'core/Exceptions',
            'core/Http',
            'core/Routing',
            'core/Validation',
            'database',
            'database/migrations',
            'public',
            'public/css',
            'public/js',
            'resources',
            'resources/views',
            'resources/views/core',
            'resources/views/core/admin',
            'resources/views/core/admin/roles',
            'resources/views/core/admin/users',
            'resources/views/core/auth',
            'resources/views/core/docs',
            'resources/views/core/errors',
            'resources/views/layouts',
            'resources/views/partials',
            'resources/views/mahasiswa',
            'routes',
            'storage',
            'storage/framework',
            'storage/framework/views',
            'storage/logs'
        ];
    }

    /**
     * Dapatkan file default untuk mode Sakuci Framework MVC
     */
    public static function getDefaultFrameworkFiles(): array
    {
        $base = __DIR__ . '/framework/sakuci';
        $files = [];

        $addFile = function($relPath, $diskPath) use (&$files) {
            if (file_exists($diskPath)) {
                $files[$relPath] = file_get_contents($diskPath);
            }
        };

        $scanDir = function($diskDir, $prefix) use (&$files, &$scanDir) {
            if (!is_dir($diskDir)) return;
            $items = scandir($diskDir);
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') continue;
                $diskPath = $diskDir . '/' . $item;
                $relPath = ($prefix !== '') ? ($prefix . '/' . $item) : $item;
                if (is_dir($diskPath)) {
                    $scanDir($diskPath, $relPath);
                } else {
                    $files[$relPath] = file_get_contents($diskPath);
                }
            }
        };

        $addFile('.env', $base . '/.env');
        $addFile('routes/web.php', $base . '/routes_default/web.php');
        $addFile('sakuci', $base . '/sakuci');
        $addFile('server.php', $base . '/server.php');
        $addFile('README.md', $base . '/README.md');
        $addFile('TUTORIAL.md', $base . '/TUTORIAL.md');

        $scanDir($base . '/app_default', 'app');
        $scanDir($base . '/config', 'config');
        $scanDir($base . '/core', 'core');
        $scanDir($base . '/database', 'database');
        $scanDir($base . '/public', 'public');
        $scanDir($base . '/resources_default/views', 'resources/views');

        return $files;
    }

    /**
     * Inisialisasi berkas awal saat user pertama kali mendaftar / masuk
     */
    public static function initializeDefaultFiles(int $userId): void
    {
        if ($userId <= 0) return;
        $pdo = Database::getConnection();
        $isMySql = (Database::getActiveDriver() === 'mysql');
        $quote = $isMySql ? '`' : '"';

        // Cek apakah user sudah punya file di mode native
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM {$quote}user_files{$quote} WHERE user_id = ? AND mode = 'native'");
        $checkStmt->execute([$userId]);
        $hasNative = (int)$checkStmt->fetchColumn() > 0;

        if (!$hasNative) {
            $nativeFiles = self::getDefaultNativeFiles();
            $insertStmt = $pdo->prepare("INSERT INTO {$quote}user_files{$quote} (user_id, mode, file_path, content) VALUES (?, 'native', ?, ?)");
            foreach ($nativeFiles as $path => $content) {
                $insertStmt->execute([$userId, $path, $content]);
            }
        }

        // Cek apakah user sudah punya file di mode framework
        $checkFwStmt = $pdo->prepare("SELECT COUNT(*) FROM {$quote}user_files{$quote} WHERE user_id = ? AND mode = 'framework'");
        $checkFwStmt->execute([$userId]);
        $hasFw = (int)$checkFwStmt->fetchColumn() > 0;

        if (!$hasFw) {
            $fwFiles = self::getDefaultFrameworkFiles();
            $insertStmt = $pdo->prepare("INSERT INTO {$quote}user_files{$quote} (user_id, mode, file_path, content) VALUES (?, 'framework', ?, ?)");
            foreach ($fwFiles as $path => $content) {
                $insertStmt->execute([$userId, $path, $content]);
            }
        }

        // Inisialisasi preferensi jika belum ada
        $prefStmt = $pdo->prepare("SELECT user_id FROM {$quote}user_preferences{$quote} WHERE user_id = ?");
        $prefStmt->execute([$userId]);
        if (!$prefStmt->fetch()) {
            $pdo->prepare("INSERT INTO {$quote}user_preferences{$quote} (user_id, playground_mode, active_file, split_ratio) VALUES (?, 'framework', 'routes/web.php', 50.00)")
                ->execute([$userId]);
        }
    }

    /**
     * Ambil seluruh berkas workspace milik user untuk mode yang dipilih
     */
    public static function getUserWorkspace(int $userId, string $mode = 'framework'): array
    {
        $mode = ($mode === 'native') ? 'native' : 'framework';
        self::initializeDefaultFiles($userId);

        $pdo = Database::getConnection();
        $isMySql = (Database::getActiveDriver() === 'mysql');
        $quote = $isMySql ? '`' : '"';

        $stmt = $pdo->prepare("SELECT file_path, content, updated_at FROM {$quote}user_files{$quote} WHERE user_id = ? AND mode = ? ORDER BY file_path ASC");
        $stmt->execute([$userId, $mode]);
        $rows = $stmt->fetchAll();

        $files = [];
        $foldersSet = [];

        foreach ($rows as $row) {
            $path = $row['file_path'];
            $files[$path] = $row['content'];

            $parts = explode('/', $path);
            array_pop($parts);
            $accum = '';
            foreach ($parts as $p) {
                $accum = $accum ? ($accum . '/' . $p) : $p;
                $foldersSet[$accum] = true;
            }
        }

        // Ambil preferensi user
        $prefStmt = $pdo->prepare("SELECT * FROM {$quote}user_preferences{$quote} WHERE user_id = ?");
        $prefStmt->execute([$userId]);
        $pref = $prefStmt->fetch() ?: [];

        $activeFile = $pref['active_file'] ?? ($mode === 'framework' ? 'routes/web.php' : 'index.php');
        if (!isset($files[$activeFile])) {
            $keys = array_keys($files);
            $activeFile = $keys[0] ?? 'index.php';
        }

        $openTabs = [];
        if (!empty($pref['open_tabs_json'])) {
            $decoded = json_decode($pref['open_tabs_json'], true);
            if (is_array($decoded)) {
                $openTabs = array_values(array_filter($decoded, fn($t) => isset($files[$t])));
            }
        }
        if (empty($openTabs)) {
            $openTabs = [$activeFile];
        }

        return [
            'success' => true,
            'mode' => $mode,
            'files' => $files,
            'folders' => array_values(array_keys($foldersSet)),
            'active_file' => $activeFile,
            'open_tabs' => $openTabs,
            'split_ratio' => (float)($pref['split_ratio'] ?? 50.0)
        ];
    }

    /**
     * Simpan/sinkronkan perubahan berkas dari client ke database user
     */
    public static function saveUserWorkspace(int $userId, string $mode, array $files, ?string $activeFile = null, ?array $openTabs = null): array
    {
        $mode = ($mode === 'native') ? 'native' : 'framework';
        $pdo = Database::getConnection();
        $isMySql = (Database::getActiveDriver() === 'mysql');
        $quote = $isMySql ? '`' : '"';

        $pdo->beginTransaction();
        try {
            // Ambil daftar file lama milik user di mode ini
            $oldFilesStmt = $pdo->prepare("SELECT file_path FROM {$quote}user_files{$quote} WHERE user_id = ? AND mode = ?");
            $oldFilesStmt->execute([$userId, $mode]);
            $existingPaths = $oldFilesStmt->fetchAll(PDO::FETCH_COLUMN);
            $existingMap = array_flip($existingPaths);

            // Upsert files
            if ($isMySql) {
                $upsertStmt = $pdo->prepare("
                    INSERT INTO `user_files` (`user_id`, `mode`, `file_path`, `content`, `updated_at`)
                    VALUES (?, ?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE `content` = VALUES(`content`), `updated_at` = NOW()
                ");
            } else {
                $upsertStmt = $pdo->prepare("
                    INSERT INTO user_files (user_id, mode, file_path, content, updated_at)
                    VALUES (?, ?, ?, ?, datetime('now'))
                    ON CONFLICT(user_id, mode, file_path) DO UPDATE SET content = excluded.content, updated_at = datetime('now')
                ");
            }

            foreach ($files as $filePath => $content) {
                $upsertStmt->execute([$userId, $mode, $filePath, $content]);
                unset($existingMap[$filePath]);
            }

            // Hapus file yang sudah tidak ada di payload (dihapus user)
            if (!empty($existingMap)) {
                $delStmt = $pdo->prepare("DELETE FROM {$quote}user_files{$quote} WHERE user_id = ? AND mode = ? AND file_path = ?");
                foreach (array_keys($existingMap) as $deletedPath) {
                    $delStmt->execute([$userId, $mode, $deletedPath]);
                }
            }

            // Update preferensi
            $updatePrefSql = $isMySql
                ? "INSERT INTO `user_preferences` (`user_id`, `playground_mode`, `active_file`, `open_tabs_json`) 
                   VALUES (?, ?, ?, ?) 
                   ON DUPLICATE KEY UPDATE `playground_mode` = VALUES(`playground_mode`), `active_file` = VALUES(`active_file`), `open_tabs_json` = VALUES(`open_tabs_json`)"
                : "INSERT INTO user_preferences (user_id, playground_mode, active_file, open_tabs_json)
                   VALUES (?, ?, ?, ?)
                   ON CONFLICT(user_id) DO UPDATE SET playground_mode = excluded.playground_mode, active_file = excluded.active_file, open_tabs_json = excluded.open_tabs_json";

            $prefStmt = $pdo->prepare($updatePrefSql);
            $prefStmt->execute([
                $userId,
                $mode,
                $activeFile ?: 'index.php',
                $openTabs ? json_encode(array_values($openTabs)) : null
            ]);

            $pdo->commit();
            return ['success' => true, 'message' => 'Berkas berhasil disimpan ke cloud database.'];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Reset workspace user kembali ke template bawaan
     */
    public static function resetUserWorkspace(int $userId, string $mode): array
    {
        $mode = ($mode === 'native') ? 'native' : 'framework';
        $pdo = Database::getConnection();
        $isMySql = (Database::getActiveDriver() === 'mysql');
        $quote = $isMySql ? '`' : '"';

        $pdo->beginTransaction();
        try {
            $delStmt = $pdo->prepare("DELETE FROM {$quote}user_files{$quote} WHERE user_id = ? AND mode = ?");
            $delStmt->execute([$userId, $mode]);

            $defaultFiles = ($mode === 'framework') ? self::getDefaultFrameworkFiles() : self::getDefaultNativeFiles();
            $insertStmt = $pdo->prepare("INSERT INTO {$quote}user_files{$quote} (user_id, mode, file_path, content) VALUES (?, ?, ?, ?)");
            foreach ($defaultFiles as $path => $content) {
                $insertStmt->execute([$userId, $mode, $path, $content]);
            }

            $pdo->commit();
            return self::getUserWorkspace($userId, $mode);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
