<?php

namespace Sakuci;

use PDO;

class CliHandler
{
    public static function run(string $commandLine, array $files, string $mode = 'framework', int $userId = 0): array
    {
        $commandLine = trim($commandLine);

        if ($commandLine === '') {
            return [
                'stdout' => '',
                'stderr' => '',
                'exit_code' => 0
            ];
        }

        // 1. Perintah built-in terminal
        if ($commandLine === 'clear' || $commandLine === 'cls') {
            return [
                'action' => 'clear',
                'stdout' => '',
                'stderr' => '',
                'exit_code' => 0
            ];
        }

        if ($commandLine === 'help') {
            return [
                'stdout' => "Perintah Terminal yang didukung:\n"
                    . "  php sakuci --help           Tampilkan panduan perintah Sakuci CLI\n"
                    . "  php sakuci make:model <Nama> Buat model baru\n"
                    . "  php sakuci make:controller <Nama> Buat controller baru\n"
                    . "  php sakuci make:view <nama>  Buat view baru\n"
                    . "  php sakuci route:list        Tampilkan seluruh rute\n"
                    . "  php sakuci migrate           Jalankan migrasi tabel database\n"
                    . "  php sakuci db:seed           Isi ulang database dengan sampel data\n"
                    . "  php sakuci db:check          Uji koneksi database\n"
                    . "  php <file.php>               Eksekusi berkas PHP langsung\n"
                    . "  ls                           Lihat daftar berkas dalam proyek\n"
                    . "  clear                        Bersihkan layar terminal\n",
                'stderr' => '',
                'exit_code' => 0
            ];
        }

        if ($commandLine === 'ls' || $commandLine === 'dir') {
            $output = "Berkas Proyek Saat Ini:\n";
            foreach (array_keys($files) as $f) {
                $output .= "  📄 " . $f . "\n";
            }
            return [
                'stdout' => $output,
                'stderr' => '',
                'exit_code' => 0
            ];
        }

        // 2. Perintah `php sakuci ...`
        if (preg_match('/^php\s+sakuci(\s+(.*))?$/i', $commandLine, $matches)) {
            $subArgs = trim($matches[2] ?? '');
            return self::handleSakuciCli($subArgs, $files, $mode);
        }

        // 3. Perintah `php <filename>`
        if (preg_match('/^php\s+([a-zA-Z0-9_\-\/\.]+\.php)(.*)$/i', $commandLine, $matches)) {
            $fileToRun = trim($matches[1]);
            if (!isset($files[$fileToRun])) {
                // Coba cari tanpa leading slash
                $fileToRun = ltrim($fileToRun, '/');
            }

            if (!isset($files[$fileToRun])) {
                return [
                    'stdout' => '',
                    'stderr' => "Error: Berkas '{$fileToRun}' tidak ditemukan di proyek.",
                    'exit_code' => 1
                ];
            }

            $res = Runner::run($files, $fileToRun, 'native');
            return [
                'stdout' => $res['stdout'] ?? '',
                'stderr' => $res['stderr'] ?? '',
                'exit_code' => $res['exit_code'] ?? 0
            ];
        }

        return [
            'stdout' => '',
            'stderr' => "Perintah tidak dikenali: '{$commandLine}'. Ketik 'help' atau 'php sakuci --help' untuk daftar perintah.",
            'exit_code' => 127
        ];
    }

    private static function handleSakuciCli(string $argsString, array $files, string $mode): array
    {
        $parts = preg_split('/\s+/', trim($argsString));
        $action = $parts[0] ?? '';
        $param1 = $parts[1] ?? '';
        $param2 = $parts[2] ?? '';

        if ($action === '' || $action === '--help' || $action === '-h' || $action === 'help') {
            return [
                'stdout' => self::getCliHelpBanner(),
                'stderr' => '',
                'exit_code' => 0
            ];
        }

        switch (strtolower($action)) {
            case 'route:list':
                return self::handleRouteList($files);

            case 'make:model':
                return self::handleMakeModel($param1, $param2, $files);

            case 'make:controller':
                return self::handleMakeController($param1, $files);

            case 'make:view':
                return self::handleMakeView($param1, $files);

            case 'migrate':
                return self::handleMigrate();

            case 'migrate:fresh':
                return self::handleMigrateFresh();

            case 'db:seed':
                return self::handleDbSeed();

            case 'db:check':
                return self::handleDbCheck();

            case 'view:clear':
                return [
                    'stdout' => "INFO  Cache kompilasi view berhasil dibersihkan.\n",
                    'stderr' => '',
                    'exit_code' => 0
                ];

            default:
                return [
                    'stdout' => '',
                    'stderr' => "Perintah 'sakuci {$action}' tidak ditemukan. Ketik 'php sakuci --help' untuk melihat daftar perintah.",
                    'exit_code' => 1
                ];
        }
    }

    private static function getCliHelpBanner(): string
    {
        return <<<TEXT
  ____             __                   _ 
 / ___|   __ _    | | __  _   _    ___ (_)
 \___ \  / _` |   | |/ / | | | |  / __|| |
  ___) || (_| | _ |   <  | |_| | | (__ | |
 |____/  \__,_|(_)|_|\_\  \__,_|  \___||_|
 Sakuci Framework CLI v1.0.0 (Artisan-like)

Penggunaan:
  php sakuci <perintah> [argumen] [opsi]

Perintah Tersedia:
  make:model <Nama> [-m]     Buat berkas Model ORM baru (contoh: php sakuci make:model Produk)
  make:controller <Nama>     Buat berkas Controller MVC baru (contoh: php sakuci make:controller ProdukController)
  make:view <nama>           Buat berkas View Blade/Sakuci baru (contoh: php sakuci make:view produk.index)
  route:list                 Tampilkan seluruh daftar rute aplikasi yang terdaftar
  migrate                    Jalankan migrasi tabel database SQLite / MySQL
  migrate:fresh              Hapus semua tabel dan jalankan ulang migrasi
  db:seed                    Isi database dengan data sampel pembelajaran
  db:check                   Periksa status koneksi basis data
  view:clear                 Bersihkan berkas cache view (.sakuci.php)

TEXT;
    }

    private static function handleRouteList(array $files): array
    {
        $webRoutesContent = $files['routes/web.php'] ?? '';
        $routes = [];

        // Parsing manual Route::(get|post|put|patch|delete)
        if (preg_match_all('/Route::(get|post|put|patch|delete)\s*\(\s*[\'"]([^\'"]+)[\'"]\s*,\s*([^)]+)\)/i', $webRoutesContent, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $method = strtoupper($m[1]);
                $uri = $m[2];
                $actionRaw = trim($m[3]);

                // Bersihkan action
                if (preg_match('/\[\s*([a-zA-Z0-9_]+)::class\s*,\s*[\'"]([a-zA-Z0-9_]+)[\'"]\s*\]/', $actionRaw, $actMatch)) {
                    $action = $actMatch[1] . '@' . $actMatch[2];
                } elseif (str_contains($actionRaw, 'function')) {
                    $action = 'Closure';
                } else {
                    $action = trim($actionRaw, '\'"');
                }

                $routes[] = [
                    'method' => $method,
                    'uri' => $uri,
                    'action' => $action
                ];
            }
        }

        if (empty($routes)) {
            $routes = [
                ['method' => 'GET', 'uri' => '/', 'action' => 'HomeController@index'],
                ['method' => 'GET', 'uri' => '/halo', 'action' => 'Closure'],
                ['method' => 'GET', 'uri' => '/mahasiswa', 'action' => 'MahasiswaController@index'],
                ['method' => 'GET', 'uri' => '/mahasiswa/tambah', 'action' => 'MahasiswaController@create'],
                ['method' => 'POST', 'uri' => '/mahasiswa/simpan', 'action' => 'MahasiswaController@store'],
                ['method' => 'GET', 'uri' => '/mahasiswa/{id}/edit', 'action' => 'MahasiswaController@edit'],
                ['method' => 'POST', 'uri' => '/mahasiswa/{id}/update', 'action' => 'MahasiswaController@update'],
                ['method' => 'GET', 'uri' => '/mahasiswa/{id}/hapus', 'action' => 'MahasiswaController@destroy']
            ];
        }

        $out = "+--------+------------------------------------+---------------------------------------+\n";
        $out .= "| METHOD | URI                                | ACTION                                |\n";
        $out .= "+--------+------------------------------------+---------------------------------------+\n";
        foreach ($routes as $r) {
            $mPad = str_pad($r['method'], 6);
            $uPad = str_pad($r['uri'], 34);
            $aPad = str_pad($r['action'], 37);
            $out .= "| {$mPad} | {$uPad} | {$aPad} |\n";
        }
        $out .= "+--------+------------------------------------+---------------------------------------+\n";
        $out .= "Total: " . count($routes) . " rute terdaftar.\n";

        return [
            'stdout' => $out,
            'stderr' => '',
            'exit_code' => 0
        ];
    }

    private static function handleMakeModel(string $name, string $opt, array $files): array
    {
        $name = ucfirst(trim($name));
        if ($name === '') {
            return [
                'stdout' => '',
                'stderr' => "Error: Nama Model diperlukan.\nContoh: php sakuci make:model Produk",
                'exit_code' => 1
            ];
        }

        $table = strtolower($name) . 's';
        if (str_ends_with($table, 'ys')) {
            $table = substr($table, 0, -2) . 'ies';
        }

        $filePath = "app/Models/{$name}.php";
        $content = <<<PHP
<?php

namespace App\Models;

use Sakuci\Database\Model;

class {$name} extends Model
{
    protected static ?string \$table = '{$table}';

    protected string \$primaryKey = 'id';

    public bool \$timestamps = true;

    protected array \$fillable = [
        'nama',
        'keterangan'
    ];
}
PHP;

        $msg = "INFO  Model [app/Models/{$name}.php] berhasil dibuat!\n";
        $newFiles = [
            $filePath => $content
        ];

        if ($opt === '-m' || $opt === '--migration') {
            $msg .= "INFO  Migrasi tabel [{$table}] disiapkan.\n";
        }

        return [
            'stdout' => $msg,
            'stderr' => '',
            'exit_code' => 0,
            'created_files' => $newFiles,
            'active_file' => $filePath
        ];
    }

    private static function handleMakeController(string $name, array $files): array
    {
        $name = trim($name);
        if ($name === '') {
            return [
                'stdout' => '',
                'stderr' => "Error: Nama Controller diperlukan.\nContoh: php sakuci make:controller ProdukController",
                'exit_code' => 1
            ];
        }

        if (!str_ends_with($name, 'Controller')) {
            $name .= 'Controller';
        }

        $filePath = "app/Controllers/{$name}.php";
        $content = <<<PHP
<?php

namespace App\Controllers;

use Sakuci\Controller;
use Sakuci\Http\Request;

class {$name} extends Controller
{
    /**
     * [READ] Menampilkan daftar data
     */
    public function index()
    {
        return view('welcome', [
            'appName' => 'Sakuci Framework'
        ]);
    }

    /**
     * [CREATE] Menampilkan formulir input data baru
     */
    public function create()
    {
        //
    }

    /**
     * [STORE] Menyimpan data baru ke basis data
     */
    public function store(Request \$request)
    {
        //
    }

    /**
     * [EDIT] Menampilkan formulir ubah data
     */
    public function edit(mixed \$id)
    {
        //
    }

    /**
     * [UPDATE] Memperbarui data yang ada di basis data
     */
    public function update(Request \$request, mixed \$id)
    {
        //
    }

    /**
     * [DELETE] Menghapus data dari basis data
     */
    public function destroy(mixed \$id)
    {
        //
    }
}
PHP;

        return [
            'stdout' => "INFO  Controller [app/Controllers/{$name}.php] berhasil dibuat!\n",
            'stderr' => '',
            'exit_code' => 0,
            'created_files' => [
                $filePath => $content
            ],
            'active_file' => $filePath
        ];
    }

    private static function handleMakeView(string $name, array $files): array
    {
        $name = trim($name);
        if ($name === '') {
            return [
                'stdout' => '',
                'stderr' => "Error: Nama View diperlukan.\nContoh: php sakuci make:view produk.index",
                'exit_code' => 1
            ];
        }

        $cleanName = str_replace(['.', '\\'], '/', $name);
        if (str_ends_with($cleanName, '.sakuci.php')) {
            $cleanName = substr($cleanName, 0, -11);
        }

        $filePath = "resources/views/{$cleanName}.sakuci.php";
        $title = ucwords(str_replace(['/', '_', '-'], ' ', $cleanName));

        $content = <<<HTML
@extends('layouts.app')

@section('title', '{$title}')

@section('content')
<div class="card shadow-sm border-0 rounded-4 p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="fw-bold mb-0">{$title}</h3>
        <a href="/" class="btn btn-outline-secondary btn-sm">← Kembali</a>
    </div>
    <p class="text-body-secondary">
        Berkas view ini berhasil dibuat menggunakan perintah <code>php sakuci make:view</code>.
    </p>
</div>
@endsection
HTML;

        return [
            'stdout' => "INFO  View [resources/views/{$cleanName}.sakuci.php] berhasil dibuat!\n",
            'stderr' => '',
            'exit_code' => 0,
            'created_files' => [
                $filePath => $content
            ],
            'active_file' => $filePath
        ];
    }

    private static function handleMigrate(): array
    {
        try {
            $pdo = Database::getConnection();
            SeedData::seed();
            return [
                'stdout' => "INFO  Menjalankan migrasi database...\n"
                    . "  [OK] Migrasi tabel 'mahasiswa' .................... SUKSES\n"
                    . "  [OK] Migrasi tabel 'kategori' ..................... SUKSES\n"
                    . "  [OK] Migrasi tabel 'produk' ....................... SUKSES\n"
                    . "  [OK] Migrasi tabel 'transaksi' .................... SUKSES\n"
                    . "Semua migrasi berhasil diselesaikan!\n",
                'stderr' => '',
                'exit_code' => 0
            ];
        } catch (\Throwable $e) {
            return [
                'stdout' => '',
                'stderr' => "Gagal menjalankan migrasi: " . $e->getMessage(),
                'exit_code' => 1
            ];
        }
    }

    private static function handleMigrateFresh(): array
    {
        try {
            $pdo = Database::getConnection();
            $tables = ['mahasiswa', 'produk', 'kategori', 'transaksi'];
            foreach ($tables as $t) {
                $pdo->exec("DROP TABLE IF EXISTS {$t}");
            }
            SeedData::seed();
            return [
                'stdout' => "INFO  Menghapus seluruh tabel lama (DROP TABLE)...\n"
                    . "INFO  Menjalankan ulang seluruh migrasi & data sampel...\n"
                    . "  [OK] Refresh database ............................ SUKSES\n"
                    . "Database berhasil diperbarui dalam kondisi bersih (fresh)!\n",
                'stderr' => '',
                'exit_code' => 0
            ];
        } catch (\Throwable $e) {
            return [
                'stdout' => '',
                'stderr' => "Gagal migrate:fresh: " . $e->getMessage(),
                'exit_code' => 1
            ];
        }
    }

    private static function handleDbSeed(): array
    {
        try {
            $pdo = Database::getConnection();
            SeedData::seed();
            return [
                'stdout' => "INFO  Mengisi ulang basis data dengan sampel data pembelajaran...\n"
                    . "  [OK] Data mahasiswa (8 record) ................... SUKSES\n"
                    . "  [OK] Data kategori (5 record) .................... SUKSES\n"
                    . "  [OK] Data produk (10 record) ..................... SUKSES\n"
                    . "  [OK] Data transaksi (5 record) ................... SUKSES\n"
                    . "Database Seeding selesai dengan sukses!\n",
                'stderr' => '',
                'exit_code' => 0
            ];
        } catch (\Throwable $e) {
            return [
                'stdout' => '',
                'stderr' => "Gagal db:seed: " . $e->getMessage(),
                'exit_code' => 1
            ];
        }
    }

    private static function handleDbCheck(): array
    {
        try {
            $pdo = Database::getConnection();
            $res = Database::getTables();
            $tables = $res['tables'] ?? [];
            $tableNames = array_column($tables, 'name');
            $count = count($tableNames);

            return [
                'stdout' => "STATUS KONEKSI BASIS DATA (SIMULASI MYSQL 8.0):\n"
                    . "  Driver        : SQLite 3 (MySQL Compatibility Layer)\n"
                    . "  Status        : Terhubung (Connected ✅)\n"
                    . "  Jumlah Tabel  : {$count} tabel\n"
                    . "  Daftar Tabel  : " . implode(', ', $tableNames) . "\n",
                'stderr' => '',
                'exit_code' => 0
            ];
        } catch (\Throwable $e) {
            return [
                'stdout' => '',
                'stderr' => "Koneksi database gagal: " . $e->getMessage(),
                'exit_code' => 1
            ];
        }
    }
}
