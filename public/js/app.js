/**
 * Sakuci PHP & MySQL Ground - Core Application Controller
 * Mendukung Mode Ganda:
 * 1. Mode PHP Murni (Kanvas bersih, single/multi file, MySQL 8.0 Simulation)
 * 2. Mode Sakuci Framework (https://github.com/indrabsus/sakuci-framework.git)
 */

const DEFAULT_NATIVE_FILES = {
    'index.php': ''
};
const DEFAULT_NATIVE_FOLDERS = [];

const DEFAULT_FRAMEWORK_FILES = {
    '.env': `# ==============================================================================
# SAKUCI FRAMEWORK ENVIRONMENT CONFIGURATION (.env)
# ==============================================================================
APP_NAME="Sakuci Framework"
APP_ENV=local
APP_KEY=base64:sakuci_secret_key_playground_12345
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_TIMEZONE=Asia/Jakarta

# ==============================================================================
# KONFIGURASI BASIS DATA (DATABASE)
# Pilihan koneksi: sqlite | mysql
# ==============================================================================
DB_CONNECTION=sqlite

# Pengaturan SQLite (Bawaan Playground - Otomatis terhubung ke database latihan)
DB_SQLITE_PATH="../data/latihan.sqlite"

# Pengaturan MySQL / MariaDB (Jika ingin mencoba koneksi MySQL lokal seperti XAMPP)
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=latihan
DB_USERNAME=root
DB_PASSWORD=
`,
    'routes/web.php': `<?php

use Sakuci\\Route;
use App\\Controllers\\MahasiswaController;
use App\\Controllers\\Core\\AuthController;
use App\\Controllers\\Core\\DashboardController;
use App\\Controllers\\Core\\DocsController;
use App\\Controllers\\Core\\RoleController;
use App\\Controllers\\Core\\UserController;

/*
|--------------------------------------------------------------------------
| Route Web - Sakuci Framework
|--------------------------------------------------------------------------
| Daftarkan seluruh route aplikasi di sini.
*/

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/docs', [DocsController::class, 'index'])->name('docs');

// =========================================================================
// CRUD DATA MAHASISWA (MahasiswaController)
// =========================================================================
Route::get('/mahasiswa', [MahasiswaController::class, 'index'])->name('mahasiswa.index');
Route::get('/mahasiswa/tambah', [MahasiswaController::class, 'create'])->name('mahasiswa.create');
Route::post('/mahasiswa/simpan', [MahasiswaController::class, 'store'])->name('mahasiswa.store');
Route::get('/mahasiswa/{id}/edit', [MahasiswaController::class, 'edit'])->name('mahasiswa.edit');
Route::post('/mahasiswa/{id}/update', [MahasiswaController::class, 'update'])->name('mahasiswa.update');
Route::get('/mahasiswa/{id}/hapus', [MahasiswaController::class, 'destroy'])->name('mahasiswa.destroy');

// =========================================================================
// AUTHENTIKASI & DASHBOARD
// =========================================================================
Route::get('/login', [AuthController::class, 'showLogin'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->name('login.attempt')->middleware('guest');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::get('/register', [AuthController::class, 'showRegister'])->name('register')->middleware('guest');
Route::post('/register', [AuthController::class, 'register'])->name('register.attempt')->middleware('guest');

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard')->middleware('auth');
`,
    'app/Controllers/HomeController.php': `<?php

namespace App\\Controllers;

use Sakuci\\Controller;

class HomeController extends Controller
{
    public function index()
    {
        return view('welcome', [
            'appName' => 'Sakuci Framework Playground',
            'version' => '1.0.0'
        ]);
    }
}
`,
    'app/Controllers/MahasiswaController.php': `<?php

namespace App\\Controllers;

use Sakuci\\Controller;
use Sakuci\\Http\\Request;
use App\\Models\\Mahasiswa;

class MahasiswaController extends Controller
{
    /**
     * [READ] Menampilkan seluruh daftar data mahasiswa
     */
    public function index()
    {
        $daftarMahasiswa = Mahasiswa::all();

        return view('mahasiswa.index', [
            'mahasiswa' => $daftarMahasiswa
        ]);
    }

    /**
     * [CREATE] Menampilkan formulir tambah data mahasiswa baru
     */
    public function create()
    {
        return view('mahasiswa.create');
    }

    /**
     * [STORE] Menyimpan data mahasiswa baru ke database (SQLite / MySQL)
     */
    public function store(Request $request)
    {
        $nim = trim((string) $request->input('nim', ''));
        $nama = trim((string) $request->input('nama', ''));
        $jurusan = trim((string) $request->input('jurusan', ''));
        $email = trim((string) $request->input('email', ''));
        $ipk = (float) $request->input('ipk', 0.0);

        if ($nim === '' || $nama === '') {
            return $this->back()->with('error', 'NIM dan Nama Mahasiswa wajib diisi!');
        }

        Mahasiswa::create([
            'nim' => $nim,
            'nama' => $nama,
            'jurusan' => $jurusan,
            'email' => $email,
            'ipk' => $ipk,
        ]);

        return $this->redirect('/mahasiswa')->with('pesan', "Mahasiswa {$nama} berhasil ditambahkan!");
    }

    /**
     * [EDIT] Menampilkan formulir edit data mahasiswa berdasarkan ID
     */
    public function edit(mixed $id)
    {
        $mhs = Mahasiswa::find($id);

        if (!$mhs) {
            return $this->redirect('/mahasiswa')->with('error', 'Data mahasiswa tidak ditemukan!');
        }

        return view('mahasiswa.edit', [
            'mhs' => $mhs
        ]);
    }

    /**
     * [UPDATE] Memperbarui data mahasiswa di database
     */
    public function update(Request $request, mixed $id)
    {
        $mhs = Mahasiswa::find($id);

        if (!$mhs) {
            return $this->redirect('/mahasiswa')->with('error', 'Data mahasiswa tidak ditemukan!');
        }

        $mhs->update([
            'nim' => trim((string) $request->input('nim', $mhs->nim)),
            'nama' => trim((string) $request->input('nama', $mhs->nama)),
            'jurusan' => trim((string) $request->input('jurusan', $mhs->jurusan)),
            'email' => trim((string) $request->input('email', $mhs->email)),
            'ipk' => (float) $request->input('ipk', $mhs->ipk),
        ]);

        return $this->redirect('/mahasiswa')->with('pesan', "Data {$mhs->nama} berhasil diperbarui!");
    }

    /**
     * [DELETE] Menghapus data mahasiswa dari database
     */
    public function destroy(mixed $id)
    {
        $mhs = Mahasiswa::find($id);

        if ($mhs) {
            $nama = $mhs->nama;
            $mhs->delete();
            return $this->redirect('/mahasiswa')->with('pesan', "Mahasiswa {$nama} berhasil dihapus!");
        }

        return $this->redirect('/mahasiswa')->with('error', 'Data tidak ditemukan!');
    }
}
`,
    'app/Models/Mahasiswa.php': `<?php

namespace App\\Models;

use Sakuci\\Database\\Model;

class Mahasiswa extends Model
{
    protected static ?string $table = 'mahasiswa';
    protected string $primaryKey = 'id';
    public bool $timestamps = false;
    protected array $fillable = ['nim', 'nama', 'jurusan', 'email', 'ipk'];
}
`,
    'app/Models/User.php': `<?php

namespace App\\Models;

use Sakuci\\Database\\Model;

class User extends Model
{
    protected static ?string $table = 'users';
    protected string $primaryKey = 'id';
    public bool $timestamps = true;
    protected array $fillable = ['username', 'email', 'password', 'role'];
}
`,
    'config/app.php': `<?php

return [
    'name' => env('APP_NAME', 'Sakuci Framework'),
    'env'  => env('APP_ENV', 'local'),
    'debug' => (bool) env('APP_DEBUG', true),
    'url'  => env('APP_URL', 'http://localhost:8000'),
    'timezone' => 'Asia/Jakarta',
];
`,
    'config/database.php': `<?php

return [
    'default' => env('DB_CONNECTION', 'sqlite'),
    'connections' => [
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => env('DB_SQLITE_PATH', database_path('../data/latihan.sqlite')),
        ],
    ],
];
`,
    'resources/views/layouts/app.sakuci.php': `<!doctype html>
<html lang="id" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Sakuci Framework')</title>

    {{-- Bootstrap 5.3 & Icons CDN --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; }
        .card { border-radius: 12px; }
    </style>
</head>
<body class="d-flex flex-column min-vh-100 bg-body-tertiary">

@include('partials.navbar')

<main class="container flex-grow-1 py-4">
    @include('partials.flash')
    @yield('content')
</main>

<footer class="py-3 border-top text-center text-body-secondary small mt-auto">
    Sakuci Framework &copy; {{ date('Y') }} &bull; MVC Ringan Tanpa Composer
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@yield('scripts')

</body>
</html>
`,
    'resources/views/partials/navbar.sakuci.php': `<nav class="navbar navbar-expand-lg bg-body border-bottom sticky-top shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold text-primary d-flex items-center gap-2" href="/">
            <span>⚡</span> Sakuci Framework
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navContent">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="/">Beranda</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-semibold text-primary" href="/mahasiswa">Data Mahasiswa (CRUD)</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/halo">Test Route /halo</a>
                </li>
            </ul>
            <span class="badge text-bg-success font-monospace">SQLite Active</span>
        </div>
    </div>
</nav>
`,
    'resources/views/partials/flash.sakuci.php': `@if (session('pesan') || session('success'))
<div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
    <i class="bi bi-check-circle-fill me-2"></i>
    {{ session('pesan') ?? session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if (session('error'))
<div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
`,
    'resources/views/welcome.sakuci.php': `@extends('layouts.app')

@section('title', 'Selamat Datang di Sakuci Framework')

@section('content')
<div class="row justify-content-center my-4">
    <div class="col-lg-9 text-center">
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill mb-3">
            Sakuci Framework v1.0.0
        </span>
        <h1 class="display-5 fw-bold mb-3">
            Kerangka PHP rasa Laravel, <span class="text-primary">tanpa Composer</span>
        </h1>
        <p class="lead text-body-secondary mb-4 mx-auto" style="max-width: 650px;">
            Mendukung arsitektur MVC lengkap: Route, Controller, Model ORM, Blade Template (<code>@@extends</code> &amp; <code>@@section</code>), dan Interactive Terminal CLI (<code>php sakuci</code>).
        </p>
        <div class="d-flex justify-content-center gap-3 mb-5">
            <a href="/mahasiswa" class="btn btn-primary btn-lg px-4 shadow-sm">
                🎓 Uji CRUD Mahasiswa
            </a>
            <a href="/halo" class="btn btn-outline-secondary btn-lg px-4">
                🚀 Route Sederhana
            </a>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card h-100 border-0 shadow-sm p-3">
            <div class="card-body">
                <div class="fs-1 mb-2">🎯</div>
                <h5 class="card-title fw-bold">Blade Templating</h5>
                <p class="card-text text-body-secondary small">
                    Gunakan layout induk <code>@@extends('layouts.app')</code> dan <code>@@section('content')</code> seperti Laravel.
                </p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100 border-0 shadow-sm p-3">
            <div class="card-body">
                <div class="fs-1 mb-2">📦</div>
                <h5 class="card-title fw-bold">Model ORM</h5>
                <p class="card-text text-body-secondary small">
                    Query database mudah dengan <code>Mahasiswa::all()</code>, <code>find()</code>, <code>create()</code>, dan <code>update()</code>.
                </p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100 border-0 shadow-sm p-3">
            <div class="card-body">
                <div class="fs-1 mb-2">💻</div>
                <h5 class="card-title fw-bold">Sakuci CLI Terminal</h5>
                <p class="card-text text-body-secondary small">
                    Gunakan tab Terminal untuk menjalankan <code>php sakuci make:model</code>, <code>migrate</code>, dan <code>route:list</code>.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
`,
    'resources/views/mahasiswa/index.sakuci.php': `@extends('layouts.app')

@section('title', 'Data Mahasiswa - Sakuci Framework')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h2 class="fw-bold mb-1">🎓 Data Mahasiswa</h2>
        <p class="text-body-secondary small mb-0">
            Aplikasi CRUD menggunakan <code>MahasiswaController</code> &amp; <code>Sakuci\\Database\\Model</code>
        </p>
    </div>
    <div>
        <a href="/mahasiswa/tambah" class="btn btn-primary btn-sm px-3 shadow-sm">
            <i class="bi bi-plus-lg me-1"></i> + Tambah Mahasiswa
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 text-nowrap">
            <thead class="table-dark text-uppercase font-monospace small">
                <tr>
                    <th class="py-3 px-3 text-center" style="width: 60px;">ID</th>
                    <th class="py-3 px-3">NIM</th>
                    <th class="py-3 px-3">Nama Mahasiswa</th>
                    <th class="py-3 px-3">Jurusan</th>
                    <th class="py-3 px-3">Email</th>
                    <th class="py-3 px-3 text-center">IPK</th>
                    <th class="py-3 px-3 text-center" style="width: 150px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($mahasiswa as $mhs)
                <tr>
                    <td class="text-center font-monospace text-body-secondary">{{ $mhs->id }}</td>
                    <td class="font-monospace fw-bold text-primary">{{ $mhs->nim }}</td>
                    <td class="fw-semibold">{{ $mhs->nama }}</td>
                    <td class="text-body-secondary">{{ $mhs->jurusan }}</td>
                    <td class="small text-body-secondary">{{ $mhs->email ?? '-' }}</td>
                    <td class="text-center">
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1 font-monospace">
                            {{ number_format((float)$mhs->ipk, 2) }}
                        </span>
                    </td>
                    <td class="text-center">
                        <div class="btn-group btn-group-sm">
                            <a href="/mahasiswa/{{ $mhs->id }}/edit" class="btn btn-outline-warning">
                                Edit
                            </a>
                            <a href="/mahasiswa/{{ $mhs->id }}/hapus" onclick="return confirm('Yakin ingin menghapus data {{ $mhs->nama }}?')" class="btn btn-outline-danger">
                                Hapus
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-5 text-body-secondary">
                        <p class="mb-2">Belum ada data mahasiswa.</p>
                        <a href="/mahasiswa/tambah" class="btn btn-sm btn-primary">+ Tambah Mahasiswa Pertama</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-body-tertiary d-flex justify-content-between align-items-center py-2 px-3 small text-body-secondary">
        <span>Total: <strong>{{ count($mahasiswa) }}</strong> Mahasiswa</span>
        <span class="font-monospace">Route: /mahasiswa</span>
    </div>
</div>
@endsection
`,
    'resources/views/mahasiswa/create.sakuci.php': `@extends('layouts.app')

@section('title', 'Tambah Mahasiswa - Sakuci Framework')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6 col-md-8">
        <div class="card border-0 shadow-sm p-4">
            <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
                <div>
                    <h4 class="fw-bold mb-0">➕ Tambah Mahasiswa Baru</h4>
                    <small class="text-body-secondary">Menjalankan <code>MahasiswaController@store</code></small>
                </div>
                <a href="/mahasiswa" class="btn btn-outline-secondary btn-sm">← Batal</a>
            </div>

            <form action="/mahasiswa/simpan" method="POST">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Nomor Induk Mahasiswa (NIM) *</label>
                    <input type="text" name="nim" required placeholder="Contoh: 2024009" class="form-control font-monospace">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Nama Lengkap *</label>
                    <input type="text" name="nama" required placeholder="Contoh: Muhammad Ilham" class="form-control">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Jurusan</label>
                    <select name="jurusan" class="form-select">
                        <option value="Teknik Informatika">Teknik Informatika</option>
                        <option value="Sistem Informasi">Sistem Informasi</option>
                        <option value="Teknik Komputer">Teknik Komputer</option>
                        <option value="Manajemen Informatika">Manajemen Informatika</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Alamat Email</label>
                    <input type="email" name="email" placeholder="Contoh: ilham@kampus.ac.id" class="form-control">
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-semibold">Indeks Prestasi Kumulatif (IPK)</label>
                    <input type="number" step="0.01" min="0" max="4.00" name="ipk" value="3.50" class="form-control font-monospace">
                </div>

                <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                    <a href="/mahasiswa" class="btn btn-secondary btn-sm px-3">Batal</a>
                    <button type="submit" class="btn btn-primary btn-sm px-4">
                        💾 Simpan Mahasiswa
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
`,
    'resources/views/mahasiswa/edit.sakuci.php': `@extends('layouts.app')

@section('title', 'Edit Mahasiswa - Sakuci Framework')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6 col-md-8">
        <div class="card border-0 shadow-sm p-4">
            <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
                <div>
                    <h4 class="fw-bold mb-0">✏️ Edit Data Mahasiswa</h4>
                    <small class="text-body-secondary">Menjalankan <code>MahasiswaController@update</code> (ID: {{ $mhs->id }})</small>
                </div>
                <a href="/mahasiswa" class="btn btn-outline-secondary btn-sm">← Batal</a>
            </div>

            <form action="/mahasiswa/{{ $mhs->id }}/update" method="POST">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Nomor Induk Mahasiswa (NIM) *</label>
                    <input type="text" name="nim" value="{{ $mhs->nim }}" required class="form-control font-monospace">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Nama Lengkap *</label>
                    <input type="text" name="nama" value="{{ $mhs->nama }}" required class="form-control">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Jurusan</label>
                    <select name="jurusan" class="form-select">
                        <option value="Teknik Informatika" {{ $mhs->jurusan === 'Teknik Informatika' ? 'selected' : '' }}>Teknik Informatika</option>
                        <option value="Sistem Informasi" {{ $mhs->jurusan === 'Sistem Informasi' ? 'selected' : '' }}>Sistem Informasi</option>
                        <option value="Teknik Komputer" {{ $mhs->jurusan === 'Teknik Komputer' ? 'selected' : '' }}>Teknik Komputer</option>
                        <option value="Manajemen Informatika" {{ $mhs->jurusan === 'Manajemen Informatika' ? 'selected' : '' }}>Manajemen Informatika</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Alamat Email</label>
                    <input type="email" name="email" value="{{ $mhs->email }}" class="form-control">
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-semibold">Indeks Prestasi Kumulatif (IPK)</label>
                    <input type="number" step="0.01" min="0" max="4.00" name="ipk" value="{{ $mhs->ipk }}" class="form-control font-monospace">
                </div>

                <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                    <a href="/mahasiswa" class="btn btn-secondary btn-sm px-3">Batal</a>
                    <button type="submit" class="btn btn-warning btn-sm px-4">
                        💾 Perbarui Data
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
`
};

const DEFAULT_FRAMEWORK_FOLDERS = [
    'app',
    'app/Controllers',
    'app/Middleware',
    'app/Models',
    'config',
    'resources',
    'resources/views',
    'resources/views/layouts',
    'resources/views/partials',
    'resources/views/mahasiswa',
    'routes'
];

window.App = {
    // Mode Playground: 'native' | 'framework'
    playgroundMode: 'native',
    frameworkRoute: '/',

    // State Berkas & Folder
    files: {},
    folders: [],
    openTabs: [],
    activeFile: '',
    expandedFolders: {},
    targetFolderForCreate: '',
    isSidebarOpen: true,

    // State Tampilan & Rute (Default: HTML Preview)
    activeTab: 'html', // 'html' | 'terminal' | 'db'
    mobileView: 'editor', // 'files' | 'editor' | 'html' | 'terminal' | 'db'
    isRunning: false,
    theme: 'dark',
    terminalHistory: [],
    historyIndex: -1,
    routeHistory: ['/'],
    routeHistoryIndex: 0,
    currentUser: null,
    autoSaveTimer: null,

    init: function () {
        this.playgroundMode = localStorage.getItem('sakuci_playground_mode') || 'native';
        this.loadFilesFromStorage();
        this.setupAuth();
        this.setupSplitPane();
        this.setupTabs();
        this.setupMobileNav();
        this.setupTheme();
        this.setupDbModal();
        this.setupExplorerModals();
        this.setupSidebarToggle();
        this.setupFrameworkRouteBar();
        this.setupTerminal();

        // Inisialisasi Monaco Editor
        CodeEditor.init('monaco-editor-container', this.files[this.activeFile] ?? '', () => {
            this.runCode();
        }, this.activeFile);

        // Inisialisasi Database Manager
        DbManager.init();

        // Render UI
        this.updateModeUI();
        this.renderExplorer();
        this.renderOpenTabs();

        // Run Code Button
        const runBtn = document.getElementById('btn-run-code');
        if (runBtn) {
            runBtn.addEventListener('click', () => this.runCode());
        }

        // Reset Code Button
        const resetBtn = document.getElementById('btn-reset-code');
        if (resetBtn) {
            resetBtn.addEventListener('click', () => {
                if (confirm(`Kembalikan seluruh berkas mode ${this.playgroundMode === 'framework' ? 'Sakuci Framework' : 'PHP Murni'} ke kondisi awal?`)) {
                    this.resetCurrentMode();
                }
            });
        }

        // Format Code Button
        const formatBtn = document.getElementById('btn-format-code');
        if (formatBtn) {
            formatBtn.addEventListener('click', () => {
                CodeEditor.formatCode();
            });
        }

        // Default mobile view: langsung ke editor penuh
        if (window.innerWidth < 768) {
            this.isSidebarOpen = false;
            this.applySidebarVisibility();
            this.applyMobileView('editor');
        }

        window.addEventListener('resize', () => {
            if (window.innerWidth >= 768) {
                document.body.classList.remove('mobile-view-files', 'mobile-view-editor', 'mobile-view-other');
                const leftPane = document.getElementById('left-pane');
                const rightPane = document.getElementById('right-pane');
                if (leftPane && rightPane) {
                    leftPane.style.display = '';
                    rightPane.style.display = '';
                }
            } else {
                this.applyMobileView(this.mobileView);
            }
            CodeEditor.layout();
        });
    },

    // ==========================================
    // DUAL MODE SWITCHER (NATIVE VS FRAMEWORK)
    // ==========================================
    switchMode: function (mode) {
        if (this.playgroundMode === mode) return;

        // Simpan file mode saat ini
        this.saveFilesToStorage();

        // Beralih mode
        this.playgroundMode = mode;
        localStorage.setItem('sakuci_playground_mode', mode);

        // Muat file mode yang dipilih
        this.loadFilesFromStorage();

        this.updateModeUI();
        this.renderExplorer();
        this.renderOpenTabs();

        CodeEditor.setCode(this.files[this.activeFile] ?? '', this.activeFile);

        // Jika pindah ke framework, otomatis buka tab HTML preview & jalankan
        if (mode === 'framework') {
            if (window.innerWidth >= 768) {
                this.switchTab('html');
            }
            this.runCode();
        } else {
            if (window.innerWidth >= 768) {
                this.switchTab('html');
            }
        }
    },

    updateModeUI: function () {
        const btnNative = document.getElementById('btn-mode-native');
        const btnFramework = document.getElementById('btn-mode-framework');
        const routeBar = document.getElementById('framework-route-bar');
        const modeBadge = document.getElementById('app-mode-badge');

        if (this.playgroundMode === 'framework') {
            if (btnFramework) {
                btnFramework.className = 'px-2.5 py-1 rounded-md transition flex items-center gap-1.5 bg-sky-600 text-white shadow font-semibold';
            }
            if (btnNative) {
                btnNative.className = 'px-2.5 py-1 rounded-md transition flex items-center gap-1.5 text-gray-400 hover:text-gray-200';
            }
            if (routeBar) {
                routeBar.classList.remove('hidden');
            }
            if (modeBadge) {
                modeBadge.textContent = 'Sakuci MVC';
                modeBadge.className = 'hidden sm:inline-block text-[10px] px-1.5 py-0.2 rounded bg-sky-950 text-sky-300 border border-sky-800 font-mono font-normal';
            }
        } else {
            if (btnNative) {
                btnNative.className = 'px-2.5 py-1 rounded-md transition flex items-center gap-1.5 bg-[#21262d] text-white shadow font-semibold';
            }
            if (btnFramework) {
                btnFramework.className = 'px-2.5 py-1 rounded-md transition flex items-center gap-1.5 text-gray-400 hover:text-gray-200';
            }
            if (routeBar) {
                routeBar.classList.add('hidden');
            }
            if (modeBadge) {
                modeBadge.textContent = 'PHP 8.3 Murni';
                modeBadge.className = 'hidden sm:inline-block text-[10px] px-1.5 py-0.2 rounded bg-indigo-950 text-indigo-300 border border-indigo-800 font-mono font-normal';
            }
        }
    },

    setupFrameworkRouteBar: function () {
        const inputRoute = document.getElementById('framework-route-input');
        const btnVisit = document.getElementById('btn-visit-route');
        const btnBack = document.getElementById('btn-route-back');
        const btnForward = document.getElementById('btn-route-forward');

        if (btnBack) {
            btnBack.addEventListener('click', () => {
                if (this.routeHistoryIndex > 0) {
                    this.routeHistoryIndex--;
                    const prevRoute = this.routeHistory[this.routeHistoryIndex];
                    this.visitRoute(prevRoute, false);
                }
            });
        }

        if (btnForward) {
            btnForward.addEventListener('click', () => {
                if (this.routeHistoryIndex < this.routeHistory.length - 1) {
                    this.routeHistoryIndex++;
                    const nextRoute = this.routeHistory[this.routeHistoryIndex];
                    this.visitRoute(nextRoute, false);
                }
            });
        }

        if (btnVisit) {
            btnVisit.addEventListener('click', () => {
                if (inputRoute) {
                    this.visitRoute(inputRoute.value.trim() || '/', true);
                }
            });
        }

        if (inputRoute) {
            inputRoute.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.visitRoute(inputRoute.value.trim() || '/', true);
                }
            });
        }

        this.updateRouteNavButtons();
    },

    updateRouteNavButtons: function () {
        const btnBack = document.getElementById('btn-route-back');
        const btnForward = document.getElementById('btn-route-forward');
        if (btnBack) {
            btnBack.disabled = this.routeHistoryIndex <= 0;
        }
        if (btnForward) {
            btnForward.disabled = this.routeHistoryIndex >= this.routeHistory.length - 1;
        }
    },

    visitRoute: function (route, pushHistory = true) {
        let cleanRoute = route.trim();
        if (cleanRoute.startsWith('http://') || cleanRoute.startsWith('https://')) {
            try {
                const u = new URL(cleanRoute);
                cleanRoute = u.pathname + (u.search || '');
            } catch (e) {}
        }
        if (!cleanRoute.startsWith('/')) {
            cleanRoute = '/' + cleanRoute;
        }
        this.frameworkRoute = cleanRoute;
        this.pendingMethod = 'GET';
        this.pendingPostData = {};

        if (pushHistory) {
            this.routeHistory = this.routeHistory.slice(0, this.routeHistoryIndex + 1);
            if (this.routeHistory[this.routeHistory.length - 1] !== cleanRoute) {
                this.routeHistory.push(cleanRoute);
                this.routeHistoryIndex = this.routeHistory.length - 1;
            }
        }
        this.updateRouteNavButtons();

        const inputRoute = document.getElementById('framework-route-input');
        if (inputRoute) {
            inputRoute.value = cleanRoute;
        }

        // Pindah ke tab HTML
        if (window.innerWidth < 768) {
            this.setMobileView('html');
        } else {
            this.switchTab('html');
        }

        this.runCode();
    },

    submitFormRoute: function (route, method = 'POST', postData = {}) {
        let cleanRoute = route.trim();
        if (cleanRoute.startsWith('http://') || cleanRoute.startsWith('https://')) {
            try {
                const u = new URL(cleanRoute);
                cleanRoute = u.pathname + (u.search || '');
            } catch (e) {}
        }
        if (!cleanRoute.startsWith('/')) {
            cleanRoute = '/' + cleanRoute;
        }
        this.frameworkRoute = cleanRoute;
        this.pendingMethod = method;
        this.pendingPostData = postData;

        this.routeHistory = this.routeHistory.slice(0, this.routeHistoryIndex + 1);
        this.routeHistory.push(cleanRoute);
        this.routeHistoryIndex = this.routeHistory.length - 1;
        this.updateRouteNavButtons();

        const inputRoute = document.getElementById('framework-route-input');
        if (inputRoute) {
            inputRoute.value = cleanRoute;
        }

        if (window.innerWidth < 768) {
            this.setMobileView('html');
        } else {
            this.switchTab('html');
        }

        this.runCode();
    },

    resetCurrentMode: async function () {
        if (this.currentUser) {
            try {
                this.setCloudSyncBadge('Mereset workspace...', 'sky');
                const res = await fetch('/api/workspace/reset', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ mode: this.playgroundMode })
                });
                const data = await res.json();
                if (data.success && data.files) {
                    this.files = data.files;
                    this.folders = data.folders || [];
                    this.activeFile = data.active_file || Object.keys(data.files)[0];
                    this.openTabs = data.open_tabs || [this.activeFile];

                    this.renderExplorer();
                    this.renderOpenTabs();
                    CodeEditor.setCode(this.files[this.activeFile] ?? '', this.activeFile);
                    this.setCloudSyncBadge(`Cloud: @${this.currentUser.username}`, 'emerald');
                    return;
                }
            } catch (e) {}
        }

        // Fallback lokal jika belum login
        if (this.playgroundMode === 'framework') {
            this.files = JSON.parse(JSON.stringify(DEFAULT_FRAMEWORK_FILES));
            this.folders = [...DEFAULT_FRAMEWORK_FOLDERS];
            this.openTabs = ['.env', 'routes/web.php', 'app/Controllers/MahasiswaController.php', 'app/Models/Mahasiswa.php', 'resources/views/mahasiswa/index.sakuci.php', 'resources/views/layouts/app.sakuci.php'];
            this.activeFile = 'routes/web.php';
        } else {
            this.files = JSON.parse(JSON.stringify(DEFAULT_NATIVE_FILES));
            this.folders = [...DEFAULT_NATIVE_FOLDERS];
            this.openTabs = ['index.php'];
            this.activeFile = 'index.php';
        }
        this.saveFilesToStorage();
        this.renderExplorer();
        this.renderOpenTabs();
        CodeEditor.setCode(this.files[this.activeFile] ?? '', this.activeFile);
    },

    clearCache: async function () {
        if (!confirm('Bersihkan seluruh cache browser (LocalStorage, file tersimpan lokal, sesi offline) & server lalu muat ulang playground?')) {
            return;
        }

        try {
            this.setCloudSyncBadge('Membersihkan cache...', 'amber');
            await fetch('/api/cache/clear', { method: 'POST' }).catch(() => {});
        } catch (e) {}

        try {
            const keysToRemove = [];
            for (let i = 0; i < localStorage.length; i++) {
                const key = localStorage.key(i);
                if (key && (key.startsWith('sakuci_') || key.includes('playground') || key.includes('framework') || key.includes('native'))) {
                    keysToRemove.push(key);
                }
            }
            keysToRemove.forEach(k => localStorage.removeItem(k));
            sessionStorage.clear();
        } catch (e) {}

        window.location.href = window.location.pathname + '?refresh=' + Date.now();
    },

    // ==========================================
    // PENYIMPANAN & SINKRONISASI BERKAS
    // ==========================================
    loadFilesFromStorage: function () {
        const prefix = this.playgroundMode === 'framework' ? 'sakuci_framework_' : 'sakuci_native_';
        try {
            const savedFiles = localStorage.getItem(prefix + 'files');
            const savedFolders = localStorage.getItem(prefix + 'folders');
            const savedTabs = localStorage.getItem(prefix + 'open_tabs');
            const savedActive = localStorage.getItem(prefix + 'active_file');

            if (savedFiles) {
                const parsed = JSON.parse(savedFiles);
                if (parsed && typeof parsed === 'object' && Object.keys(parsed).length > 0) {
                    if (this.playgroundMode === 'framework') {
                        // Sinkronisasi otomatis struktur berkas bawaan
                        Object.keys(DEFAULT_FRAMEWORK_FILES).forEach(k => {
                            if (!parsed[k]) {
                                parsed[k] = DEFAULT_FRAMEWORK_FILES[k];
                            }
                        });
                        // Pastikan layouts & views menggunakan Bootstrap CDN & @extends
                        if (parsed['resources/views/layouts/app.sakuci.php'] && !parsed['resources/views/layouts/app.sakuci.php'].includes('bootstrap')) {
                            parsed['resources/views/layouts/app.sakuci.php'] = DEFAULT_FRAMEWORK_FILES['resources/views/layouts/app.sakuci.php'];
                        }
                        if (parsed['resources/views/mahasiswa/index.sakuci.php'] && !parsed['resources/views/mahasiswa/index.sakuci.php'].includes('@extends')) {
                            parsed['resources/views/mahasiswa/index.sakuci.php'] = DEFAULT_FRAMEWORK_FILES['resources/views/mahasiswa/index.sakuci.php'];
                            parsed['resources/views/mahasiswa/create.sakuci.php'] = DEFAULT_FRAMEWORK_FILES['resources/views/mahasiswa/create.sakuci.php'];
                            parsed['resources/views/mahasiswa/edit.sakuci.php'] = DEFAULT_FRAMEWORK_FILES['resources/views/mahasiswa/edit.sakuci.php'];
                            parsed['resources/views/welcome.sakuci.php'] = DEFAULT_FRAMEWORK_FILES['resources/views/welcome.sakuci.php'];
                            parsed['resources/views/partials/navbar.sakuci.php'] = DEFAULT_FRAMEWORK_FILES['resources/views/partials/navbar.sakuci.php'];
                            parsed['resources/views/partials/flash.sakuci.php'] = DEFAULT_FRAMEWORK_FILES['resources/views/partials/flash.sakuci.php'];
                        }
                        if (parsed['resources/views/welcome.sakuci.php']) {
                            parsed['resources/views/welcome.sakuci.php'] = parsed['resources/views/welcome.sakuci.php']
                                .replace(/<code>@extends<\/code>/g, '<code>@@extends</code>')
                                .replace(/<code>@section<\/code>/g, '<code>@@section</code>')
                                .replace(/<code>@extends\('layouts\.app'\)<\/code>/g, '<code>@@extends(\'layouts.app\')</code>')
                                .replace(/<code>@section\('content'\)<\/code>/g, '<code>@@section(\'content\')</code>');
                        }
                        if (parsed['config/database.php'] && !parsed['config/database.php'].includes('DB_SQLITE_PATH')) {
                            parsed['config/database.php'] = DEFAULT_FRAMEWORK_FILES['config/database.php'];
                        }
                        if (!parsed['.env']) {
                            parsed['.env'] = DEFAULT_FRAMEWORK_FILES['.env'];
                        }
                        // Pulihkan otomatis jika routes/web.php tidak sengaja tertimpa oleh MahasiswaController
                        if (parsed['routes/web.php'] && (parsed['routes/web.php'].includes('class MahasiswaController') || !parsed['routes/web.php'].includes('Route::'))) {
                            parsed['routes/web.php'] = DEFAULT_FRAMEWORK_FILES['routes/web.php'];
                        }
                    } else {
                        if (parsed['index.php'] && parsed['index.php'].includes('class MahasiswaController')) {
                            parsed['index.php'] = DEFAULT_NATIVE_FILES['index.php'];
                        }
                    }

                    this.files = parsed;
                    let loadedFolders = savedFolders ? JSON.parse(savedFolders) : [];
                    if (this.playgroundMode === 'framework') {
                        DEFAULT_FRAMEWORK_FOLDERS.forEach(df => {
                            if (!loadedFolders.includes(df)) loadedFolders.push(df);
                        });
                    }
                    this.folders = loadedFolders;
                    this.openTabs = savedTabs ? JSON.parse(savedTabs) : Object.keys(this.files);
                    if (this.playgroundMode === 'framework' && !this.openTabs.includes('app/Controllers/MahasiswaController.php')) {
                        this.openTabs.push('app/Controllers/MahasiswaController.php');
                    }
                    this.activeFile = (savedActive && this.files[savedActive] !== undefined) 
                        ? savedActive 
                        : Object.keys(this.files)[0];
                    return;
                }
            }
        } catch (e) {}

        // Inisialisasi awal jika belum ada
        if (this.playgroundMode === 'framework') {
            this.files = JSON.parse(JSON.stringify(DEFAULT_FRAMEWORK_FILES));
            this.folders = [...DEFAULT_FRAMEWORK_FOLDERS];
            this.openTabs = ['routes/web.php', 'app/Controllers/MahasiswaController.php', 'app/Models/Mahasiswa.php', 'resources/views/mahasiswa/index.sakuci.php', 'resources/views/layouts/app.sakuci.php'];
            this.activeFile = 'routes/web.php';
        } else {
            this.files = JSON.parse(JSON.stringify(DEFAULT_NATIVE_FILES));
            this.folders = [...DEFAULT_NATIVE_FOLDERS];
            this.openTabs = ['index.php'];
            this.activeFile = 'index.php';
        }
    },

    saveFilesToStorage: async function (silent = false) {
        const prefix = this.playgroundMode === 'framework' ? 'sakuci_framework_' : 'sakuci_native_';
        try {
            if (this.activeFile && this.files[this.activeFile] !== undefined) {
                const currentCode = CodeEditor.getCode();
                if (currentCode !== null) {
                    this.files[this.activeFile] = currentCode;
                }
            }
            localStorage.setItem(prefix + 'files', JSON.stringify(this.files));
            localStorage.setItem(prefix + 'folders', JSON.stringify(this.folders));
            localStorage.setItem(prefix + 'open_tabs', JSON.stringify(this.openTabs));
            localStorage.setItem(prefix + 'active_file', this.activeFile);

            // Simpan ke cloud jika user sedang login
            if (this.currentUser) {
                this.setCloudSyncBadge('Menyimpan ke Cloud...', 'sky');
                const res = await fetch('/api/workspace/save', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        mode: this.playgroundMode,
                        files: this.files,
                        active_file: this.activeFile,
                        open_tabs: this.openTabs
                    })
                });
                const data = await res.json();
                if (data.success) {
                    this.setCloudSyncBadge(`Cloud: @${this.currentUser.username}`, 'emerald');
                } else {
                    this.setCloudSyncBadge('Gagal simpan cloud', 'amber');
                }
            } else {
                this.setCloudSyncBadge('Tersimpan di Browser', 'emerald');
            }
        } catch (e) {
            this.setCloudSyncBadge('Tersimpan di Browser', 'emerald');
        }
    },

    onCodeChange: function () {
        if (!this.activeFile || this.files[this.activeFile] === undefined) return;
        const currentCode = CodeEditor.getCode();
        if (currentCode === null) return;
        this.files[this.activeFile] = currentCode;

        this.setCloudSyncBadge('Menyimpan...', 'sky');

        if (this.autoSaveTimer) {
            clearTimeout(this.autoSaveTimer);
        }
        this.autoSaveTimer = setTimeout(() => {
            this.saveFilesToStorage(true);
        }, 1200);
    },

    // ==========================================
    // AUTENTIKASI PENGGUNA & CLOUD WORKSPACE
    // ==========================================
    setupAuth: function () {
        const btnOpenModal = document.getElementById('btn-open-auth-modal');
        const btnCloseModal = document.getElementById('btn-close-auth-modal');
        const modal = document.getElementById('auth-modal');
        const tabLogin = document.getElementById('auth-tab-btn-login');
        const tabRegister = document.getElementById('auth-tab-btn-register');
        const formLogin = document.getElementById('form-auth-login');
        const formRegister = document.getElementById('form-auth-register');
        const linkSwitchRegister = document.getElementById('link-switch-to-register');
        const linkSwitchLogin = document.getElementById('link-switch-to-login');
        const btnUserProfileMenu = document.getElementById('btn-user-profile-menu');
        const userDropdown = document.getElementById('user-profile-dropdown');
        const btnLogout = document.getElementById('btn-menu-logout');
        const btnResetWorkspace = document.getElementById('btn-menu-reset-workspace');
        const btnManualSave = document.getElementById('btn-menu-save');

        if (btnOpenModal) {
            btnOpenModal.addEventListener('click', () => this.showAuthModal('login'));
        }
        if (btnCloseModal) {
            btnCloseModal.addEventListener('click', () => this.closeAuthModal());
        }
        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) this.closeAuthModal();
            });
        }

        const switchToLogin = () => {
            if (tabLogin) tabLogin.className = 'flex-1 py-2.5 text-center text-sky-400 border-b-2 border-sky-500 bg-[#161b22]/50 transition';
            if (tabRegister) tabRegister.className = 'flex-1 py-2.5 text-center text-gray-400 hover:text-gray-200 border-b-2 border-transparent transition';
            if (formLogin) formLogin.classList.remove('hidden');
            if (formRegister) formRegister.classList.add('hidden');
        };

        const switchToRegister = () => {
            if (tabRegister) tabRegister.className = 'flex-1 py-2.5 text-center text-emerald-400 border-b-2 border-emerald-500 bg-[#161b22]/50 transition';
            if (tabLogin) tabLogin.className = 'flex-1 py-2.5 text-center text-gray-400 hover:text-gray-200 border-b-2 border-transparent transition';
            if (formRegister) formRegister.classList.remove('hidden');
            if (formLogin) formLogin.classList.add('hidden');
        };

        if (tabLogin) tabLogin.addEventListener('click', switchToLogin);
        if (tabRegister) tabRegister.addEventListener('click', switchToRegister);
        if (linkSwitchRegister) linkSwitchRegister.addEventListener('click', (e) => { e.preventDefault(); switchToRegister(); });
        if (linkSwitchLogin) linkSwitchLogin.addEventListener('click', (e) => { e.preventDefault(); switchToLogin(); });

        // Dropdown toggle
        if (btnUserProfileMenu && userDropdown) {
            btnUserProfileMenu.addEventListener('click', (e) => {
                e.stopPropagation();
                userDropdown.classList.toggle('hidden');
            });
            document.addEventListener('click', () => {
                userDropdown.classList.add('hidden');
            });
        }

        // Form Login Submit
        if (formLogin) {
            formLogin.addEventListener('submit', async (e) => {
                e.preventDefault();
                const errBox = document.getElementById('auth-login-error');
                const submitBtn = document.getElementById('btn-submit-login');
                const loginVal = document.getElementById('auth-login-input')?.value?.trim();
                const passVal = document.getElementById('auth-login-password')?.value;

                if (errBox) errBox.classList.add('hidden');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = `<span>Memproses...</span>`;
                }

                try {
                    const res = await fetch('/api/auth/login', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ login: loginVal, password: passVal })
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.currentUser = data.user;
                        this.updateAuthUI();
                        this.closeAuthModal();
                        await this.syncWorkspaceFromCloud();
                        this.setCloudSyncBadge(`Cloud: @${this.currentUser.username}`, 'emerald');
                    } else {
                        if (errBox) {
                            errBox.textContent = data.error || 'Login gagal. Periksa username dan password Anda.';
                            errBox.classList.remove('hidden');
                        }
                    }
                } catch (err) {
                    if (errBox) {
                        errBox.textContent = 'Gagal menghubungi server: ' + err.message;
                        errBox.classList.remove('hidden');
                    }
                } finally {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = `<span>Masuk ke Workspace</span><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>`;
                    }
                }
            });
        }

        // Form Register Submit
        if (formRegister) {
            formRegister.addEventListener('submit', async (e) => {
                e.preventDefault();
                const errBox = document.getElementById('auth-register-error');
                const submitBtn = document.getElementById('btn-submit-register');
                const nameVal = document.getElementById('auth-reg-name')?.value?.trim();
                const usernameVal = document.getElementById('auth-reg-username')?.value?.trim();
                const emailVal = document.getElementById('auth-reg-email')?.value?.trim();
                const passVal = document.getElementById('auth-reg-password')?.value;

                if (errBox) errBox.classList.add('hidden');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = `<span>Membuat Akun...</span>`;
                }

                try {
                    const res = await fetch('/api/auth/register', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ name: nameVal, username: usernameVal, email: emailVal, password: passVal })
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.currentUser = data.user;
                        this.updateAuthUI();
                        this.closeAuthModal();
                        await this.syncWorkspaceFromCloud();
                        this.setCloudSyncBadge(`Cloud: @${this.currentUser.username}`, 'emerald');
                    } else {
                        if (errBox) {
                            errBox.textContent = data.error || 'Pendaftaran gagal.';
                            errBox.classList.remove('hidden');
                        }
                    }
                } catch (err) {
                    if (errBox) {
                        errBox.textContent = 'Gagal menghubungi server: ' + err.message;
                        errBox.classList.remove('hidden');
                    }
                } finally {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = `<span>Daftar & Mulai Belajar</span><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>`;
                    }
                }
            });
        }

        // Logout
        if (btnLogout) {
            btnLogout.addEventListener('click', async () => {
                if (confirm('Apakah Anda yakin ingin keluar dari akun Anda?')) {
                    try {
                        await fetch('/api/auth/logout', { method: 'POST' });
                    } catch (e) {}
                    this.currentUser = null;
                    this.updateAuthUI();
                    this.loadFilesFromStorage();
                    this.renderExplorer();
                    this.renderOpenTabs();
                    CodeEditor.setCode(this.files[this.activeFile] ?? '', this.activeFile);
                }
            });
        }

        // Reset Workspace
        if (btnResetWorkspace) {
            btnResetWorkspace.addEventListener('click', () => {
                if (confirm(`Kembalikan seluruh berkas mode ${this.playgroundMode === 'framework' ? 'Sakuci Framework' : 'PHP Murni'} ke kondisi awal?`)) {
                    this.resetCurrentMode();
                }
            });
        }

        // Manual Save
        if (btnManualSave) {
            btnManualSave.addEventListener('click', () => {
                this.saveFilesToStorage();
            });
        }

        // Cek status sesi saat memuat halaman
        this.checkAuth();
    },

    checkAuth: async function () {
        try {
            const res = await fetch('/api/auth/me');
            const data = await res.json();
            if (data.success && data.authenticated && data.user) {
                this.currentUser = data.user;
                this.updateAuthUI();
                await this.syncWorkspaceFromCloud();
            } else {
                this.currentUser = null;
                this.updateAuthUI();
            }
        } catch (e) {
            this.currentUser = null;
            this.updateAuthUI();
        }
    },

    updateAuthUI: function () {
        const btnOpenModal = document.getElementById('btn-open-auth-modal');
        const userWidget = document.getElementById('user-profile-widget');
        const userNameLabel = document.getElementById('user-name-label');
        const userAvatarBadge = document.getElementById('user-avatar-badge');
        const dropdownName = document.getElementById('dropdown-name');
        const dropdownUsername = document.getElementById('dropdown-username');

        if (this.currentUser) {
            if (btnOpenModal) btnOpenModal.classList.add('hidden');
            if (userWidget) userWidget.classList.remove('hidden');

            const name = this.currentUser.name || 'User';
            const initial = name.charAt(0).toUpperCase() || 'U';

            if (userNameLabel) userNameLabel.textContent = name;
            if (userAvatarBadge) userAvatarBadge.textContent = initial;
            if (dropdownName) dropdownName.textContent = name;
            if (dropdownUsername) dropdownUsername.textContent = `@${this.currentUser.username}`;

            this.setCloudSyncBadge(`Cloud: @${this.currentUser.username}`, 'emerald');
        } else {
            if (btnOpenModal) btnOpenModal.classList.remove('hidden');
            if (userWidget) userWidget.classList.add('hidden');

            this.setCloudSyncBadge('Tamu (Belum Login)', 'amber');
        }
    },

    showAuthModal: function (tab = 'login') {
        const modal = document.getElementById('auth-modal');
        if (!modal) return;
        modal.classList.remove('hidden');

        if (tab === 'register') {
            document.getElementById('auth-tab-btn-register')?.click();
        } else {
            document.getElementById('auth-tab-btn-login')?.click();
        }
    },

    closeAuthModal: function () {
        const modal = document.getElementById('auth-modal');
        if (modal) modal.classList.add('hidden');
    },

    setCloudSyncBadge: function (text, color = 'emerald') {
        const badge = document.getElementById('cloud-sync-status');
        const textEl = document.getElementById('cloud-sync-text');
        if (!badge || !textEl) return;

        textEl.textContent = text;
        const dot = badge.querySelector('span:first-child');
        if (dot) {
            dot.className = `inline-block w-1.5 h-1.5 rounded-full ${color === 'emerald' ? 'bg-emerald-400' : (color === 'amber' ? 'bg-amber-400' : 'bg-sky-400')}`;
        }
    },

    syncWorkspaceFromCloud: async function () {
        try {
            this.setCloudSyncBadge('Memuat berkas cloud...', 'sky');
            const res = await fetch(`/api/workspace/files?mode=${this.playgroundMode}`);
            const data = await res.json();
            if (data.success && data.files) {
                this.files = data.files;
                this.folders = data.folders || [];
                this.activeFile = data.active_file || Object.keys(data.files)[0] || 'index.php';
                this.openTabs = data.open_tabs || [this.activeFile];

                this.renderExplorer();
                this.renderOpenTabs();
                CodeEditor.setCode(this.files[this.activeFile] ?? '', this.activeFile);
                this.setCloudSyncBadge(this.currentUser ? `Cloud: @${this.currentUser.username}` : 'Tersimpan', 'emerald');
            }
        } catch (e) {
            this.setCloudSyncBadge('Mode Offline', 'amber');
        }
    },

    // ==========================================
    // SIDEBAR & EXPLORER TREE MANAGEMENT
    // ==========================================
    setupSidebarToggle: function () {
        const toggleBtn = document.getElementById('btn-toggle-sidebar');
        const closeSidebarBtn = document.getElementById('btn-close-sidebar');

        if (toggleBtn) {
            toggleBtn.addEventListener('click', () => {
                this.isSidebarOpen = !this.isSidebarOpen;
                this.applySidebarVisibility();
            });
        }

        if (closeSidebarBtn) {
            closeSidebarBtn.addEventListener('click', () => {
                this.isSidebarOpen = false;
                this.applySidebarVisibility();
            });
        }
    },

    applySidebarVisibility: function () {
        const sidebar = document.getElementById('file-explorer-sidebar');
        const toggleBtn = document.getElementById('btn-toggle-sidebar');
        if (!sidebar) return;

        if (this.isSidebarOpen) {
            sidebar.classList.remove('hidden');
            if (toggleBtn) toggleBtn.classList.add('bg-[#30363d]', 'text-sky-400');
        } else {
            sidebar.classList.add('hidden');
            if (toggleBtn) toggleBtn.classList.remove('bg-[#30363d]', 'text-sky-400');
        }
        CodeEditor.layout();
    },

    buildTree: function () {
        const root = { name: '', path: '', isFolder: true, children: {} };

        const allFolders = new Set([...this.folders]);
        Object.keys(this.files).forEach(f => {
            const parts = f.split('/');
            for (let i = 1; i < parts.length; i++) {
                allFolders.add(parts.slice(0, i).join('/'));
            }
        });

        allFolders.forEach(folderPath => {
            if (!folderPath) return;
            const parts = folderPath.split('/');
            let curr = root;
            let curPath = '';
            parts.forEach(part => {
                curPath = curPath ? `${curPath}/${part}` : part;
                if (!curr.children[part]) {
                    curr.children[part] = { name: part, path: curPath, isFolder: true, children: {} };
                }
                curr = curr.children[part];
            });
        });

        Object.keys(this.files).forEach(filePath => {
            const parts = filePath.split('/');
            const fileName = parts.pop();
            let curr = root;
            parts.forEach(part => {
                if (!curr.children[part]) {
                    curr.children[part] = { name: part, path: part, isFolder: true, children: {} };
                }
                curr = curr.children[part];
            });
            curr.children[fileName] = { name: fileName, path: filePath, isFolder: false };
        });

        return root;
    },

    renderExplorer: function () {
        const container = document.getElementById('explorer-tree-list');
        if (!container) return;

        const tree = this.buildTree();
        container.innerHTML = this.renderTreeChildren(tree.children, 0);
    },

    renderTreeChildren: function (children, level) {
        if (!children || Object.keys(children).length === 0) {
            return '';
        }

        const keys = Object.keys(children).sort((a, b) => {
            const itemA = children[a];
            const itemB = children[b];
            if (itemA.isFolder && !itemB.isFolder) return -1;
            if (!itemA.isFolder && itemB.isFolder) return 1;
            return a.localeCompare(b);
        });

        return keys.map(key => {
            const item = children[key];
            const indent = level * 14;

            if (item.isFolder) {
                // Folder default tertutup/collapse (sembunyikan isi file), kecuali jika diklik pengguna
                const isExpanded = Boolean(this.expandedFolders[item.path]);
                return `
                    <div>
                        <div class="tree-item flex items-center justify-between py-1 px-1.5 rounded text-xs text-gray-300 group cursor-pointer"
                            style="padding-left: ${indent + 6}px;"
                            onclick="App.toggleFolder('${item.path}')">
                            <div class="flex items-center gap-1.5 truncate">
                                <span class="text-[10px] text-gray-500 font-mono w-3 text-center">
                                    ${isExpanded ? '▼' : '▶'}
                                </span>
                                <span class="text-xs">${isExpanded ? '📂' : '📁'}</span>
                                <span class="font-mono text-gray-200 truncate">${item.name}</span>
                            </div>
                            <div class="tree-actions flex items-center gap-1 shrink-0" onclick="event.stopPropagation()">
                                <button onclick="App.openCreateFileModal('${item.path}')" title="Buat file di folder ${item.name}" 
                                    class="p-0.5 text-gray-400 hover:text-sky-400 rounded transition">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                </button>
                                <button onclick="App.openCreateFolderModal('${item.path}')" title="Buat subfolder di ${item.name}" 
                                    class="p-0.5 text-gray-400 hover:text-amber-400 rounded transition">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path></svg>
                                </button>
                                <button onclick="App.deleteFolder('${item.path}')" title="Hapus folder ${item.name}" 
                                    class="p-0.5 text-gray-400 hover:text-red-400 rounded transition">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </div>
                        </div>
                        ${isExpanded ? `<div>${this.renderTreeChildren(item.children, level + 1)}</div>` : ''}
                    </div>
                `;
            } else {
                const isActive = item.path === this.activeFile;
                const isIndex = item.path === 'index.php' || item.path === 'routes/web.php';
                return `
                    <div class="tree-item flex items-center justify-between py-1 px-1.5 rounded text-xs group cursor-pointer ${
                        isActive ? 'active font-semibold' : 'text-gray-400 hover:text-gray-200'
                    }"
                        style="padding-left: ${indent + 18}px;"
                        onclick="App.openFile('${item.path}')">
                        <div class="flex items-center gap-1.5 truncate">
                            <span class="text-xs">${isIndex ? '⭐' : '📄'}</span>
                            <span class="font-mono truncate ${isActive ? 'text-sky-300' : ''}">${item.name}</span>
                        </div>
                        <div class="tree-actions flex items-center shrink-0" onclick="event.stopPropagation()">
                            ${!isIndex ? `
                                <button onclick="App.deleteFile('${item.path}')" title="Hapus file ${item.name}" 
                                    class="p-0.5 text-gray-500 hover:text-red-400 rounded transition">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                            ` : ''}
                        </div>
                    </div>
                `;
            }
        }).join('');
    },

    toggleFolder: function (path) {
        this.expandedFolders[path] = !this.expandedFolders[path];
        this.renderExplorer();
    },

    // ==========================================
    // TOP OPEN TABS BAR
    // ==========================================
    renderOpenTabs: function () {
        const container = document.getElementById('open-tabs-list');
        if (!container) return;

        container.innerHTML = this.openTabs.map(path => {
            const isActive = path === this.activeFile;
            const isEssential = path === 'index.php' || path === 'routes/web.php';
            const displayName = path.split('/').pop();

            return `
                <div class="flex items-center rounded-t border-t border-x text-xs font-mono font-medium transition shrink-0 ${
                    isActive 
                        ? 'bg-[#0d1117] text-sky-400 border-[#30363d]' 
                        : 'bg-[#161b22] text-gray-400 border-transparent hover:text-gray-200 hover:bg-[#21262d]'
                }">
                    <button type="button" onclick="App.openFile('${path}')" 
                        class="px-2.5 py-1.5 flex items-center gap-1.5 focus:outline-none select-none">
                        <span class="text-xs">${isEssential ? '⭐' : '📄'}</span>
                        <span title="${path}">${displayName}</span>
                    </button>
                    ${!isEssential ? `
                        <button type="button" onclick="App.closeTab(event, '${path}')" title="Tutup tab" 
                            class="p-1 text-gray-500 hover:text-red-400 hover:bg-[#30363d] rounded transition mr-1 flex items-center justify-center focus:outline-none">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    ` : '<div class="w-1.5"></div>'}
                </div>
            `;
        }).join('');
    },

    openFile: function (path) {
        if (this.files[path] === undefined) return;

        // Hanya simpan file sebelumnya jika berganti file dan editor tidak sedang di-set programatik
        if (this.activeFile && this.activeFile !== path && this.files[this.activeFile] !== undefined) {
            if (!CodeEditor.isSettingCode) {
                const currentCode = CodeEditor.getCode();
                if (currentCode !== null) {
                    this.files[this.activeFile] = currentCode;
                }
            }
        }

        if (!this.openTabs.includes(path)) {
            this.openTabs.push(path);
        }

        this.activeFile = path;
        CodeEditor.setCode(this.files[path] ?? '', path);
        this.renderExplorer();
        this.renderOpenTabs();
        this.saveFilesToStorage();

        if (window.innerWidth < 768) {
            this.setMobileView('editor');
        }
    },

    closeTab: function (e, path) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }

        const isEssential = path === 'index.php' || path === 'routes/web.php';
        if (isEssential) return;

        this.openTabs = this.openTabs.filter(p => p !== path);

        if (this.activeFile === path) {
            const nextTab = this.openTabs[this.openTabs.length - 1] || Object.keys(this.files)[0];
            this.openFile(nextTab);
        } else {
            this.renderOpenTabs();
            this.saveFilesToStorage();
        }
    },

    // ==========================================
    // CREATION & DELETION MODALS
    // ==========================================
    setupExplorerModals: function () {
        const btnHeaderAddFile = document.getElementById('btn-explorer-add-file');
        const modalFile = document.getElementById('modal-create-file');
        const inputFileName = document.getElementById('input-create-file-name');
        const btnConfirmFile = document.getElementById('btn-confirm-create-file');
        const btnCancelFile = document.getElementById('btn-cancel-create-file');

        if (btnHeaderAddFile) {
            btnHeaderAddFile.addEventListener('click', () => this.openCreateFileModal(''));
        }

        if (btnConfirmFile) {
            btnConfirmFile.addEventListener('click', () => this.submitCreateFile());
        }

        if (btnCancelFile) {
            btnCancelFile.addEventListener('click', () => modalFile.classList.add('hidden'));
        }

        if (inputFileName) {
            inputFileName.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.submitCreateFile();
                } else if (e.key === 'Escape') {
                    modalFile.classList.add('hidden');
                }
            });
        }

        const btnHeaderAddFolder = document.getElementById('btn-explorer-add-folder');
        const modalFolder = document.getElementById('modal-create-folder');
        const inputFolderName = document.getElementById('input-create-folder-name');
        const btnConfirmFolder = document.getElementById('btn-confirm-create-folder');
        const btnCancelFolder = document.getElementById('btn-cancel-create-folder');

        if (btnHeaderAddFolder) {
            btnHeaderAddFolder.addEventListener('click', () => this.openCreateFolderModal(''));
        }

        if (btnConfirmFolder) {
            btnConfirmFolder.addEventListener('click', () => this.submitCreateFolder());
        }

        if (btnCancelFolder) {
            btnCancelFolder.addEventListener('click', () => modalFolder.classList.add('hidden'));
        }

        if (inputFolderName) {
            inputFolderName.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.submitCreateFolder();
                } else if (e.key === 'Escape') {
                    modalFolder.classList.add('hidden');
                }
            });
        }
    },

    openCreateFileModal: function (parentFolder = '') {
        this.targetFolderForCreate = parentFolder;
        const modal = document.getElementById('modal-create-file');
        const input = document.getElementById('input-create-file-name');
        const targetLabel = document.getElementById('label-target-folder-file');

        if (targetLabel) {
            targetLabel.textContent = parentFolder ? `Lokasi: /${parentFolder}/` : 'Lokasi: / (root)';
        }

        if (modal && input) {
            modal.classList.remove('hidden');
            input.value = '';
            input.focus();
        }
    },

    submitCreateFile: function () {
        const input = document.getElementById('input-create-file-name');
        const modal = document.getElementById('modal-create-file');
        if (!input) return;

        let name = input.value.trim();
        if (!name) return;

        name = name.replace(/[^a-zA-Z0-9_\-\.\/]/g, '');
        if (!strEndsWith(name.toLowerCase(), '.php')) {
            name += '.php';
        }

        const fullPath = this.targetFolderForCreate 
            ? `${this.targetFolderForCreate}/${name}` 
            : name;

        if (this.files[fullPath] !== undefined) {
            alert(`File "${fullPath}" sudah ada!`);
            this.openFile(fullPath);
            modal.classList.add('hidden');
            return;
        }

        this.files[fullPath] = "";
        modal.classList.add('hidden');
        this.openFile(fullPath);
    },

    openCreateFolderModal: function (parentFolder = '') {
        this.targetFolderForCreate = parentFolder;
        const modal = document.getElementById('modal-create-folder');
        const input = document.getElementById('input-create-folder-name');
        const targetLabel = document.getElementById('label-target-folder-dir');

        if (targetLabel) {
            targetLabel.textContent = parentFolder ? `Lokasi: /${parentFolder}/` : 'Lokasi: / (root)';
        }

        if (modal && input) {
            modal.classList.remove('hidden');
            input.value = '';
            input.focus();
        }
    },

    submitCreateFolder: function () {
        const input = document.getElementById('input-create-folder-name');
        const modal = document.getElementById('modal-create-folder');
        if (!input) return;

        let name = input.value.trim().replace(/[^a-zA-Z0-9_\-]/g, '');
        if (!name) return;

        const fullPath = this.targetFolderForCreate 
            ? `${this.targetFolderForCreate}/${name}` 
            : name;

        if (!this.folders.includes(fullPath)) {
            this.folders.push(fullPath);
            this.expandedFolders[fullPath] = true;
        }

        modal.classList.add('hidden');
        this.renderExplorer();
        this.saveFilesToStorage();
    },

    deleteFile: function (path) {
        const isEssential = path === 'index.php' || path === 'routes/web.php';
        if (isEssential) {
            alert(`File "${path}" adalah file utama dan tidak dapat dihapus.`);
            return;
        }

        if (!confirm(`Hapus berkas "${path}"?`)) {
            return;
        }

        delete this.files[path];
        this.openTabs = this.openTabs.filter(p => p !== path);

        if (this.activeFile === path) {
            const nextTab = this.openTabs[this.openTabs.length - 1] || Object.keys(this.files)[0];
            this.openFile(nextTab);
        } else {
            this.renderExplorer();
            this.renderOpenTabs();
            this.saveFilesToStorage();
        }
    },

    deleteFolder: function (folderPath) {
        if (!confirm(`Apakah Anda yakin ingin menghapus folder "${folderPath}" beserta seluruh file di dalamnya?`)) {
            return;
        }

        this.folders = this.folders.filter(f => f !== folderPath && !f.startsWith(folderPath + '/'));

        let wasActiveDeleted = false;
        Object.keys(this.files).forEach(f => {
            if (f.startsWith(folderPath + '/')) {
                delete this.files[f];
                this.openTabs = this.openTabs.filter(p => p !== f);
                if (this.activeFile === f) wasActiveDeleted = true;
            }
        });

        if (wasActiveDeleted) {
            const nextTab = this.openTabs[this.openTabs.length - 1] || Object.keys(this.files)[0];
            this.openFile(nextTab);
        } else {
            this.renderExplorer();
            this.renderOpenTabs();
            this.saveFilesToStorage();
        }
    },

    // ==========================================
    // RESPONSIVE & MOBILE NAVIGATION
    // ==========================================
    setupMobileNav: function () {
        const views = ['files', 'editor', 'terminal', 'html', 'db'];
        views.forEach(view => {
            const btn = document.getElementById(`mobile-nav-${view}`);
            if (btn) {
                btn.addEventListener('click', () => {
                    this.setMobileView(view);
                });
            }
        });
    },

    setMobileView: function (view) {
        this.mobileView = view;
        this.applyMobileView(view);

        if (view === 'editor') {
            CodeEditor.layout();
        } else if (view !== 'files') {
            this.switchTab(view);
        }
    },

    applyMobileView: function (view) {
        if (window.innerWidth >= 768) return;

        document.body.classList.remove('mobile-view-files', 'mobile-view-editor', 'mobile-view-other');

        if (view === 'files') {
            document.body.classList.add('mobile-view-files');
        } else if (view === 'editor') {
            document.body.classList.add('mobile-view-editor');
        } else {
            document.body.classList.add('mobile-view-other');
        }

        const views = ['files', 'editor', 'terminal', 'html', 'db'];
        views.forEach(v => {
            const btn = document.getElementById(`mobile-nav-${v}`);
            if (btn) {
                if (v === view) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            }
        });
    },

    // ==========================================
    // RIGHT PANEL DESKTOP TABS
    // ==========================================
    setupTabs: function () {
        const tabs = ['terminal', 'html', 'db'];
        tabs.forEach(tab => {
            const btn = document.getElementById(`tab-btn-${tab}`);
            if (btn) {
                btn.addEventListener('click', () => this.switchTab(tab));
            }
        });
    },

    switchTab: function (tabName) {
        this.activeTab = tabName;
        const tabs = ['terminal', 'html', 'db'];

        tabs.forEach(t => {
            const btn = document.getElementById(`tab-btn-${t}`);
            const pane = document.getElementById(`tab-pane-${t}`);
            if (btn && pane) {
                if (t === tabName) {
                    btn.classList.add('active');
                    pane.classList.remove('hidden');
                } else {
                    btn.classList.remove('active');
                    pane.classList.add('hidden');
                }
            }
        });

        if (tabName === 'db') {
            DbManager.loadTables();
        } else if (tabName === 'terminal') {
            const termInput = document.getElementById('terminal-input');
            if (termInput) termInput.focus();
        }
    },

    // ==========================================
    // INTERACTIVE SAKUCI TERMINAL CLI
    // ==========================================
    setupTerminal: function () {
        const input = document.getElementById('terminal-input');
        if (!input) return;

        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.submitTerminalInput();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (this.terminalHistory.length > 0) {
                    if (this.historyIndex === -1) {
                        this.historyIndex = this.terminalHistory.length - 1;
                    } else if (this.historyIndex > 0) {
                        this.historyIndex--;
                    }
                    input.value = this.terminalHistory[this.historyIndex] || '';
                }
            } else if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (this.terminalHistory.length > 0 && this.historyIndex !== -1) {
                    if (this.historyIndex < this.terminalHistory.length - 1) {
                        this.historyIndex++;
                        input.value = this.terminalHistory[this.historyIndex] || '';
                    } else {
                        this.historyIndex = -1;
                        input.value = '';
                    }
                }
            }
        });
    },

    submitTerminalInput: function () {
        const input = document.getElementById('terminal-input');
        if (!input) return;
        const cmd = input.value.trim();
        input.value = '';
        this.historyIndex = -1;
        if (!cmd) return;

        this.terminalHistory.push(cmd);
        this.runTerminalCommand(cmd);
    },

    runTerminalCommand: async function (command) {
        if (!command) return;

        this.appendTerminalLine(`sakuci@cli:~$ ${command}`, 'prompt');

        if (command.trim() === 'clear' || command.trim() === 'cls') {
            this.clearTerminal();
            return;
        }

        // Pindah ke tab terminal jika sedang di tab lain
        if (window.innerWidth < 768) {
            this.setMobileView('terminal');
        } else {
            this.switchTab('terminal');
        }

        try {
            const res = await fetch('/api/cli', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    command: command,
                    files: this.files,
                    mode: this.playgroundMode
                })
            });

            const result = await res.json();

            if (result.action === 'clear') {
                this.clearTerminal();
                return;
            }

            if (result.stdout) {
                this.appendTerminalLine(result.stdout, 'stdout');
            }

            if (result.stderr) {
                this.appendTerminalLine(result.stderr, 'stderr');
            }

            // Jika ada file baru yang dibuat (make:model, make:controller, make:view)
            if (result.created_files && typeof result.created_files === 'object') {
                Object.keys(result.created_files).forEach(fPath => {
                    this.files[fPath] = result.created_files[fPath];
                    // Daftarkan folder induk jika belum ada
                    const parts = fPath.split('/');
                    parts.pop();
                    let curDir = '';
                    parts.forEach(p => {
                        curDir = curDir ? `${curDir}/${p}` : p;
                        if (!this.folders.includes(curDir)) {
                            this.folders.push(curDir);
                        }
                    });
                });

                this.renderExplorer();
                this.saveFilesToStorage();

                if (result.active_file && this.files[result.active_file] !== undefined) {
                    this.openFile(result.active_file);
                }
            }
        } catch (err) {
            this.appendTerminalLine(`Terminal Error: ${err.message}`, 'stderr');
        }
    },

    appendTerminalLine: function (text, type = 'stdout') {
        const screen = document.getElementById('terminal-screen');
        if (!screen) return;

        const line = document.createElement('div');
        line.className = 'font-mono text-xs whitespace-pre-wrap select-text leading-relaxed';

        if (type === 'prompt') {
            line.className += ' text-emerald-400 font-semibold mt-2.5';
            line.textContent = text;
        } else if (type === 'stderr') {
            line.className += ' text-rose-400 bg-rose-950/20 p-2 rounded border border-rose-900/40 my-1';
            line.textContent = text;
        } else {
            line.className += ' text-slate-200';
            line.textContent = text;
        }

        screen.appendChild(line);
        screen.scrollTop = screen.scrollHeight;
    },

    clearTerminal: function () {
        const screen = document.getElementById('terminal-screen');
        if (!screen) return;
        screen.innerHTML = `
            <div class="text-sky-400/90 font-mono text-xs mb-2">⚡ Sakuci Framework Interactive Terminal CLI</div>
            <div class="text-slate-400 text-xs font-mono">Ketik perintah seperti <code class="text-sky-300">php sakuci make:model Produk</code>, <code class="text-sky-300">php sakuci route:list</code>, <code class="text-sky-300">php sakuci migrate</code>, atau <code class="text-sky-300">help</code>.</div>
        `;
    },

    // ==========================================
    // EXECUTE CODE (NATIVE OR FRAMEWORK)
    // ==========================================
    runCode: async function () {
        if (this.isRunning) return;

        if (this.activeFile && this.files[this.activeFile] !== undefined) {
            const currentCode = CodeEditor.getCode();
            if (currentCode !== null) {
                this.files[this.activeFile] = currentCode;
            }
        }
        this.saveFilesToStorage();

        const runBtn = document.getElementById('btn-run-code');
        const executionBadge = document.getElementById('metric-exec-time');
        const statusBadge = document.getElementById('metric-status');

        this.isRunning = true;
        if (runBtn) {
            runBtn.classList.add('opacity-75', 'cursor-not-allowed', 'running-pulse');
            runBtn.innerHTML = `
                <svg class="animate-spin -ml-1 mr-1.5 h-3.5 w-3.5 text-white inline-block" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Menjalankan...</span>
            `;
        }

        const methodToSend = this.pendingMethod || 'GET';
        const postToSend = this.pendingPostData || {};
        this.pendingMethod = null;
        this.pendingPostData = null;

        if (window.innerWidth < 768) {
            if (this.playgroundMode === 'framework') {
                this.setMobileView('html');
            } else {
                this.setMobileView('terminal');
            }
        } else {
            if (this.playgroundMode === 'framework' && this.activeTab !== 'terminal') {
                this.switchTab('html');
            }
        }

        try {
            const res = await fetch('/api/run', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    mode: this.playgroundMode,
                    route_uri: this.frameworkRoute,
                    http_method: methodToSend,
                    post_data: postToSend,
                    files: this.files,
                    entrypoint: this.playgroundMode === 'framework' ? 'public/index.php' : 'index.php'
                })
            });

            const result = await res.json();

            if (executionBadge) {
                executionBadge.textContent = `${result.execution_time_ms} ms`;
            }
            if (statusBadge) {
                if (result.success) {
                    statusBadge.textContent = 'Sukses (Exit 0)';
                    statusBadge.className = 'px-2 py-0.5 rounded text-[10px] md:text-[11px] font-mono bg-emerald-950/80 text-emerald-300 border border-emerald-800';
                } else {
                    statusBadge.textContent = result.timed_out ? 'Timeout (5s)' : `Error (Exit ${result.exit_code})`;
                    statusBadge.className = 'px-2 py-0.5 rounded text-[10px] md:text-[11px] font-mono bg-red-950/80 text-red-300 border border-red-800';
                }
            }

            // Tampilkan log ringkasan di terminal
            const targetUri = this.playgroundMode === 'framework' ? this.frameworkRoute : 'index.php';
            if (result.success) {
                this.appendTerminalLine(`⚡ [HTTP ${methodToSend}] ${targetUri} -> 200 OK (${result.execution_time_ms} ms)`, 'prompt');
            } else {
                this.appendTerminalLine(`⚠️ [HTTP ${methodToSend}] ${targetUri} -> Exit ${result.exit_code} (${result.execution_time_ms} ms)`, 'stderr');
            }

            if (result.route && this.playgroundMode === 'framework' && result.route !== this.frameworkRoute) {
                this.frameworkRoute = result.route;
                const inputRoute = document.getElementById('framework-route-input');
                if (inputRoute) {
                    inputRoute.value = result.route;
                }
                if (this.routeHistory[this.routeHistory.length - 1] !== result.route) {
                    this.routeHistory.push(result.route);
                    this.routeHistoryIndex = this.routeHistory.length - 1;
                    this.updateRouteNavButtons();
                }
            }

            if (result.stderr) {
                this.appendTerminalLine(result.stderr, 'stderr');
            }

            if (result.stdout && this.playgroundMode === 'native') {
                this.appendTerminalLine(result.stdout, 'stdout');
            }

            this.updateHtmlPreview(result.stdout);

        } catch (err) {
            this.appendTerminalLine(`Network Error: ${err.message}`, 'stderr');
        } finally {
            this.isRunning = false;
            if (runBtn) {
                runBtn.classList.remove('opacity-75', 'cursor-not-allowed', 'running-pulse');
                runBtn.innerHTML = `
                    <svg class="w-3.5 h-3.5 md:w-4 md:h-4 mr-1 inline-block" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"></path>
                    </svg>
                    <span>Jalankan</span>
                    <span class="hidden md:inline-block text-[10px] px-1 py-0.2 rounded bg-sky-700/80 font-mono ml-1">Ctrl+Enter</span>
                `;
            }
        }
    },

    renderConsoleOutput: function (result) {
        const consoleEl = document.getElementById('console-output-content');
        if (!consoleEl) return;

        let outputHtml = '';

        if (result.stderr) {
            outputHtml += `
                <div class="mb-3 p-3 bg-red-950/40 border border-red-800/80 rounded text-red-300 text-xs whitespace-pre-wrap font-mono">
                    <div class="flex items-center gap-1.5 font-bold mb-1 text-red-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Pesan Kesalahan / Warning:
                    </div>
                    ${this.escapeHtml(result.stderr)}
                </div>
            `;
        }

        if (result.stdout) {
            // Jika stdout adalah HTML panjang, tampilkan ringkasan di konsol dan arahkan ke tab HTML
            const isLongHtml = result.stdout.includes('<html') || result.stdout.includes('<!doctype') || result.stdout.length > 500;
            if (this.playgroundMode === 'framework' && isLongHtml) {
                outputHtml += `
                    <div class="p-3 bg-sky-950/40 border border-sky-800/80 rounded text-sky-300 text-xs font-mono mb-3">
                        ✓ Rute <code>${this.frameworkRoute}</code> berhasil diproses! Hasil tampilan visual HTML telah dirender di tab <strong>HTML</strong>.
                    </div>
                `;
            }
            outputHtml += `
                <div class="text-xs text-gray-200 font-mono whitespace-pre-wrap leading-relaxed select-text">
                    ${this.escapeHtml(result.stdout)}
                </div>
            `;
        }

        if (!result.stdout && !result.stderr) {
            outputHtml = '<div class="text-gray-500 italic p-4 text-xs font-mono">Program selesai tanpa menghasilkan output teks.</div>';
        }

        consoleEl.innerHTML = outputHtml;
    },

    updateHtmlPreview: function (rawHtml) {
        const iframe = document.getElementById('html-preview-frame');
        if (!iframe) return;

        // Jika respons adalah redirect yang belum sempat di-follow runner
        const redirMatch = rawHtml && rawHtml.match(/<!--\s*SAKUCI_REDIRECT:\s*(.*?)\s*-->/);
        if (redirMatch && redirMatch[1]) {
            this.visitRoute(redirMatch[1]);
            return;
        }

        const doc = iframe.contentDocument || iframe.contentWindow.document;
        doc.open();
        doc.write(rawHtml || '<p style="color: #94a3b8; font-style: italic; padding: 16px; font-family: sans-serif;">Tidak ada konten HTML untuk ditampilkan.</p>');
        doc.close();

        // Intercept link clicks & form submits inside simulated iframe
        try {
            doc.addEventListener('click', (e) => {
                const link = e.target.closest('a');
                if (!link) return;
                const href = link.getAttribute('href');
                if (!href || href.startsWith('#') || href.startsWith('javascript:')) return;
                e.preventDefault();
                App.visitRoute(href);
            });

            doc.addEventListener('submit', (e) => {
                const form = e.target;
                if (!form) return;
                e.preventDefault();
                const action = form.getAttribute('action') || App.frameworkRoute;
                const method = (form.getAttribute('method') || 'GET').toUpperCase();
                const formData = new FormData(form);
                const postData = {};
                formData.forEach((val, key) => { postData[key] = val; });
                App.submitFormRoute(action, method, postData);
            });
        } catch (e) {}
    },

    setupSplitPane: function () {
        const resizer = document.getElementById('split-resizer');
        const leftCol = document.getElementById('left-pane');
        const rightCol = document.getElementById('right-pane');
        const container = document.getElementById('workspace-container');

        if (!resizer || !leftCol || !rightCol || !container) return;

        let isDragging = false;
        let activePointerId = null;
        let rafId = null;

        const applySplit = (percentage) => {
            leftCol.style.width = `${percentage}%`;
            leftCol.style.flex = `0 0 ${percentage}%`;
            rightCol.style.width = `calc(${100 - percentage}% - 6px)`;
            rightCol.style.flex = '1 1 0%';
        };

        // Muat rasio split yang tersimpan sebelumnya (jika ada)
        try {
            const savedRatio = localStorage.getItem('sakuci_split_ratio');
            if (savedRatio && window.innerWidth >= 768) {
                const parsed = parseFloat(savedRatio);
                if (!isNaN(parsed) && parsed >= 20 && parsed <= 80) {
                    applySplit(parsed);
                }
            }
        } catch (e) {}

        const startDragging = (e) => {
            if (window.innerWidth < 768) return;
            // Hanya tanggapi klik kiri (button 0)
            if (e.button !== undefined && e.button !== 0) return;

            isDragging = true;
            resizer.classList.add('is-dragging');
            document.body.classList.add('is-resizing-split');

            if (e.pointerId !== undefined && resizer.setPointerCapture) {
                activePointerId = e.pointerId;
                try {
                    resizer.setPointerCapture(e.pointerId);
                } catch (err) {}
            }

            e.preventDefault();
        };

        const onMove = (e) => {
            if (!isDragging || window.innerWidth < 768) return;

            // Pengaman utama: jika tombol mouse kiri sudah tidak ditekan (mouseup hilang/di luar window), hentikan drag segera
            if (e.buttons !== undefined && (e.buttons & 1) === 0) {
                stopDragging(e);
                return;
            }

            const containerRect = container.getBoundingClientRect();
            const offset = e.clientX - containerRect.left;
            const containerWidth = containerRect.width;

            if (containerWidth <= 0) return;

            // Batas minimal & maksimal yang nyaman dan proporsional
            const minWidth = Math.min(260, containerWidth * 0.2);
            const maxWidth = Math.max(containerWidth - 260, containerWidth * 0.8);

            // Clamp offset agar mulus saat kursor keluar batas (tidak macet atau mendadak berhenti)
            const clampedOffset = Math.max(minWidth, Math.min(offset, maxWidth));
            const percentage = (clampedOffset / containerWidth) * 100;

            if (!rafId) {
                rafId = requestAnimationFrame(() => {
                    applySplit(percentage);
                    rafId = null;
                });
            }
        };

        const stopDragging = (e) => {
            if (!isDragging) return;
            isDragging = false;

            if (rafId) {
                cancelAnimationFrame(rafId);
                rafId = null;
            }

            resizer.classList.remove('is-dragging');
            document.body.classList.remove('is-resizing-split');

            if (activePointerId !== null && resizer.releasePointerCapture) {
                try {
                    resizer.releasePointerCapture(activePointerId);
                } catch (err) {}
                activePointerId = null;
            }

            // Simpan rasio split aktif ke localStorage
            try {
                const containerRect = container.getBoundingClientRect();
                const leftRect = leftCol.getBoundingClientRect();
                if (containerRect.width > 0) {
                    const finalPercentage = (leftRect.width / containerRect.width) * 100;
                    localStorage.setItem('sakuci_split_ratio', finalPercentage.toFixed(2));
                }
            } catch (err) {}

            CodeEditor.layout();
        };

        // Pointer Events (didukung semua browser modern, mengisolasi event dari iframe)
        if (window.PointerEvent) {
            resizer.addEventListener('pointerdown', startDragging);
            window.addEventListener('pointermove', onMove, { passive: false });
            window.addEventListener('pointerup', stopDragging);
            window.addEventListener('pointercancel', stopDragging);
        } else {
            // Fallback Mouse Events
            resizer.addEventListener('mousedown', startDragging);
            document.addEventListener('mousemove', onMove);
            document.addEventListener('mouseup', stopDragging);
        }

        // Pengaman ekstra jika window kehilangan fokus
        window.addEventListener('blur', () => stopDragging());

        // Double click pada resizer untuk mereset posisi kembali ke tengah 50:50
        resizer.addEventListener('dblclick', () => {
            if (window.innerWidth < 768) return;
            applySplit(50);
            try {
                localStorage.removeItem('sakuci_split_ratio');
            } catch (e) {}
            CodeEditor.layout();
        });
    },

    setupTheme: function () {
        const themeBtn = document.getElementById('btn-toggle-theme');
        if (themeBtn) {
            themeBtn.addEventListener('click', () => {
                this.theme = this.theme === 'dark' ? 'light' : 'dark';
                document.body.dataset.theme = this.theme;
                CodeEditor.setTheme(this.theme);
            });
        }
    },

    setupDbModal: function () {
        const openModalBtn = document.getElementById('btn-open-db-settings');
        const closeModalBtn = document.getElementById('btn-close-db-modal');
        const modal = document.getElementById('db-settings-modal');
        const testBtn = document.getElementById('btn-test-db-modal');

        if (!modal) return;

        const openModal = () => {
            modal.classList.remove('hidden');
        };

        if (openModalBtn) openModalBtn.addEventListener('click', openModal);
        if (closeModalBtn) closeModalBtn.addEventListener('click', () => modal.classList.add('hidden'));

        if (testBtn) {
            testBtn.addEventListener('click', async () => {
                const feedbackEl = document.getElementById('db-modal-feedback');
                feedbackEl.innerHTML = '<span class="text-gray-400">Menguji koneksi simulasi...</span>';

                try {
                    const res = await fetch('/api/db/test', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({})
                    });
                    const data = await res.json();
                    if (data.success) {
                        feedbackEl.innerHTML = `<span class="text-emerald-400 font-semibold">✓ ${data.message} (Engine: ${data.version || ''})</span>`;
                    } else {
                        feedbackEl.innerHTML = `<span class="text-red-400 font-semibold">✗ ${data.message}</span>`;
                    }
                } catch (err) {
                    feedbackEl.innerHTML = `<span class="text-red-400 font-semibold">✗ Error: ${err.message}</span>`;
                }
            });
        }
    },

    escapeHtml: function (text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, m => map[m]);
    }
};

function strEndsWith(str, suffix) {
    return str.indexOf(suffix, str.length - suffix.length) !== -1;
}

// Start application when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.App.init();
});
