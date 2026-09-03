<?php

namespace Sakuci;

class ApiHandler
{
    public static function handle(string $uri, string $method): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');

        if ($method === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        $path = parse_url($uri, PHP_URL_PATH);
        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        try {
            switch ($path) {
                case '/api/run':
                    if ($method !== 'POST') {
                        self::error('Method not allowed', 405);
                    }
                    $files = (isset($input['files']) && is_array($input['files'])) ? $input['files'] : ['index.php' => $input['code'] ?? ''];
                    $entrypoint = $input['entrypoint'] ?? 'index.php';
                    $mode = $input['mode'] ?? 'native';
                    $routeUri = $input['route_uri'] ?? '/';
                    $httpMethod = $input['http_method'] ?? 'GET';
                    $result = Runner::run($files, $entrypoint, $mode, $routeUri, $httpMethod);
                    echo json_encode($result);
                    break;

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
                    $config = !empty($input) ? $input : Database::getConfig();
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
                    $result = Database::getTableSchema($table);
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

                default:
                    self::error('Endpoint API tidak ditemukan: ' . $path, 404);
            }
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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
