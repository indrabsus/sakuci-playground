<?php

namespace Sakuci;

class ApiHandler
{
    public static function handle(string $uri, string $method): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');

        if ($method === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        $path = parse_url($uri, PHP_URL_PATH);
        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        try {
            switch ($path) {
                // ==========================================
                // AUTENTIKASI PENGGUNA (MULTI-USER)
                // ==========================================
                case '/api/auth/register':
                    if ($method !== 'POST') self::error('Method not allowed', 405);
                    $name = $input['name'] ?? '';
                    $username = $input['username'] ?? '';
                    $email = $input['email'] ?? '';
                    $password = $input['password'] ?? '';
                    $result = Auth::register($name, $username, $email, $password);
                    echo json_encode($result);
                    break;

                case '/api/auth/login':
                    if ($method !== 'POST') self::error('Method not allowed', 405);
                    $login = $input['login'] ?? ($input['username'] ?? '');
                    $password = $input['password'] ?? '';
                    $result = Auth::login($login, $password);
                    echo json_encode($result);
                    break;

                case '/api/auth/logout':
                    Auth::logout();
                    echo json_encode(['success' => true, 'message' => 'Anda telah berhasil keluar.']);
                    break;

                case '/api/auth/me':
                    $currentUser = Auth::user();
                    echo json_encode([
                        'success' => true,
                        'authenticated' => ($currentUser !== null),
                        'user' => $currentUser
                    ]);
                    break;

                // ==========================================
                // MANAJEMEN WORKSPACE & ISOLASI BERKAS
                // ==========================================
                case '/api/workspace/files':
                    $currentUser = Auth::user();
                    $mode = $_GET['mode'] ?? 'framework';
                    if ($currentUser) {
                        $workspace = WorkspaceManager::getUserWorkspace($currentUser['id'], $mode);
                        echo json_encode($workspace);
                    } else {
                        // Tamu / belum login: sediakan template awal bawaan
                        $defaultFiles = ($mode === 'framework') ? WorkspaceManager::getDefaultFrameworkFiles() : WorkspaceManager::getDefaultNativeFiles();
                        echo json_encode([
                            'success' => true,
                            'mode' => $mode,
                            'files' => $defaultFiles,
                            'folders' => ($mode === 'framework') ? ['app', 'app/Controllers', 'app/Models', 'resources', 'resources/views', 'resources/views/layouts', 'resources/views/partials', 'resources/views/mahasiswa', 'routes'] : [],
                            'active_file' => ($mode === 'framework') ? 'routes/web.php' : 'index.php',
                            'open_tabs' => ($mode === 'framework') ? ['.env', 'routes/web.php', 'app/Controllers/MahasiswaController.php'] : ['index.php'],
                            'is_guest' => true
                        ]);
                    }
                    break;

                case '/api/workspace/save':
                    if ($method !== 'POST') self::error('Method not allowed', 405);
                    $currentUser = Auth::user();
                    if (!$currentUser) {
                        self::error('Silakan masuk terlebih dahulu untuk menyimpan perubahan berkas ke akun Anda.', 401);
                    }
                    $mode = $input['mode'] ?? 'framework';
                    $files = $input['files'] ?? [];
                    $activeFile = $input['active_file'] ?? null;
                    $openTabs = $input['open_tabs'] ?? null;
                    $result = WorkspaceManager::saveUserWorkspace($currentUser['id'], $mode, $files, $activeFile, $openTabs);
                    echo json_encode($result);
                    break;

                case '/api/workspace/reset':
                    if ($method !== 'POST') self::error('Method not allowed', 405);
                    $currentUser = Auth::user();
                    if (!$currentUser) {
                        self::error('Silakan masuk terlebih dahulu untuk mereset workspace.', 401);
                    }
                    $mode = $input['mode'] ?? 'framework';
                    $result = WorkspaceManager::resetUserWorkspace($currentUser['id'], $mode);
                    echo json_encode($result);
                    break;

                // ==========================================
                // RUNNER & CLI EXECUTION
                // ==========================================
                case '/api/run':
                    if ($method !== 'POST') {
                        self::error('Method not allowed', 405);
                    }
                    $files = (isset($input['files']) && is_array($input['files'])) ? $input['files'] : ['index.php' => $input['code'] ?? ''];
                    $entrypoint = $input['entrypoint'] ?? 'index.php';
                    $mode = $input['mode'] ?? 'native';
                    $routeUri = $input['route_uri'] ?? '/';
                    $httpMethod = $input['http_method'] ?? 'GET';
                    $postData = (isset($input['post_data']) && is_array($input['post_data'])) ? $input['post_data'] : [];

                    $currentUser = Auth::user();
                    $userId = $currentUser['id'] ?? 0;

                    // Simpan berkas aktif ke cloud database user jika sedang login
                    if ($userId > 0 && !empty($files)) {
                        try {
                            WorkspaceManager::saveUserWorkspace($userId, $mode, $files, $entrypoint);
                        } catch (\Throwable $e) {}
                    }

                    $result = Runner::run($files, $entrypoint, $mode, $routeUri, $httpMethod, $postData, $userId);
                    echo json_encode($result);
                    break;

                case '/api/cli':
                    if ($method !== 'POST') {
                        self::error('Method not allowed', 405);
                    }
                    $command = $input['command'] ?? '';
                    $files = (isset($input['files']) && is_array($input['files'])) ? $input['files'] : [];
                    $mode = $input['mode'] ?? 'framework';

                    $currentUser = Auth::user();
                    $userId = $currentUser['id'] ?? 0;

                    $result = CliHandler::run($command, $files, $mode, $userId);
                    echo json_encode($result);
                    break;

                // ==========================================
                // DATABASE TOOLS & VIEWER
                // ==========================================
                case '/api/db/config':
                    if ($method === 'GET') {
                        $config = Database::getConfig();
                        echo json_encode(['success' => true, 'config' => $config]);
                    } elseif ($method === 'POST') {
                        $success = Database::saveConfig($input);
                        echo json_encode(['success' => $success, 'message' => 'Konfigurasi database berhasil disimpan']);
                    }
                    break;

                case '/api/db/test':
                    if ($method !== 'POST') {
                        self::error('Method not allowed', 405);
                    }
                    $config = !empty($input) ? $input : null;
                    $result = Database::testConnection($config);
                    echo json_encode($result);
                    break;

                case '/api/db/tables':
                    $result = Database::getTables();
                    echo json_encode($result);
                    break;

                case '/api/db/schema':
                    $table = $_GET['table'] ?? '';
                    if (empty($table)) {
                        self::error('Parameter table diperlukan', 400);
                    }
                    $result = Database::getTableColumns($table);
                    echo json_encode($result);
                    break;

                case '/api/db/table-data':
                    $table = $_GET['table'] ?? '';
                    $page = max(1, (int)($_GET['page'] ?? 1));
                    $limit = max(1, min(100, (int)($_GET['limit'] ?? 25)));
                    if (empty($table)) {
                        self::error('Parameter table diperlukan', 400);
                    }
                    $result = Database::getTableData($table, $page, $limit);
                    echo json_encode($result);
                    break;

                case '/api/db/query':
                    if ($method !== 'POST') {
                        self::error('Method not allowed', 405);
                    }
                    $sql = $input['sql'] ?? '';
                    $result = Database::executeQuery($sql);
                    echo json_encode($result);
                    break;

                case '/api/db/seed':
                    if ($method !== 'POST') {
                        self::error('Method not allowed', 405);
                    }
                    $result = SeedData::seed();
                    echo json_encode($result);
                    break;

                case '/api/cache/clear':
                    if ($method !== 'POST') {
                        self::error('Method not allowed', 405);
                    }
                    $tempDir = __DIR__ . '/../data/temp';
                    if (is_dir($tempDir)) {
                        $items = glob($tempDir . '/*');
                        if (is_array($items)) {
                            foreach ($items as $item) {
                                if (is_dir($item)) {
                                    $files = new \RecursiveIteratorIterator(
                                        new \RecursiveDirectoryIterator($item, \FilesystemIterator::SKIP_DOTS),
                                        \RecursiveIteratorIterator::CHILD_FIRST
                                    );
                                    foreach ($files as $f) {
                                        $f->isDir() ? @rmdir($f->getRealPath()) : @unlink($f->getRealPath());
                                    }
                                    @rmdir($item);
                                } else {
                                    @unlink($item);
                                }
                            }
                        }
                    }
                    echo json_encode(['success' => true, 'message' => 'Cache server dan berkas sementara berhasil dibersihkan!']);
                    break;

                default:
                    self::error('Endpoint API tidak ditemukan: ' . $path, 404);
            }
        } catch (\Throwable $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        exit;
    }

    private static function error(string $message, int $statusCode = 400): void
    {
        http_response_code($statusCode);
        echo json_encode(['success' => false, 'error' => $message]);
        exit;
    }
}
