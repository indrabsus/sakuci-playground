<?php

namespace App\Controllers\Core;

use Sakuci\Controller;

class DocsController extends Controller
{
    public function index()
    {
        return view('core.docs.index', ['sections' => $this->sections()]);
    }

    protected function sections(): array
    {
        return [
            // ===== INTRO =====
            [
                'id'    => '0-intro',
                'title' => 'Dokumentasi Sakuci',
                'parts' => [
                    ['p' => 'Kerangka PHP ringan bergaya Laravel tanpa Composer. Fitur: Route, Model, View, Controller, Validation, Session, Middleware, CSRF protection, Query Builder dengan eager loading, dan Bootstrap 5.3.8 built-in.'],
                    ['p' => '<strong>Instalasi cepat:</strong> Clone dari GitHub atau salin folder ke web server Anda.'],
                    ['code' => "# Clone dari GitHub\ngit clone https://github.com/indrabsus/sakuci-framework.git\ncd sakuci-framework\n\n# Atau download dan ekstrak folder\n\n# Salin .env.example jadi .env\ncp .env.example .env\n\n# Jalankan server\nphp sakuci serve\n\n# Buka di browser\nhttp://127.0.0.1:8000", 'lang' => 'bash'],
                    ['p' => '<strong>Coba akun demo:</strong> admin / rahasia123 &mdash; satu-satunya akun bawaan. Role lain (staff, user, dst.) dibuat sendiri lewat /admin/roles setelah login.'],
                ],
            ],

            // ===== CLI =====
            [
                'id'    => '1-cli',
                'title' => 'CLI -- Perintah sakuci',
                'parts' => [
                    ['p' => 'Jalankan semua perintah dari folder project:'],
                    ['code' => "php sakuci serve                    # Jalankan server (127.0.0.1:8000)\nphp sakuci migrate                  # Jalankan migrasi\nphp sakuci migrate:fresh            # Hapus semua tabel\nphp sakuci db:check                 # Uji koneksi database\nphp sakuci route:list               # Lihat semua route\nphp sakuci make:model Nama          # Buat model\nphp sakuci make:model Nama -m       # Buat model + migrasi\nphp sakuci make:controller Nama     # Buat controller\nphp sakuci make:view nama.view      # Buat view\nphp sakuci make:migration nama      # Buat migrasi\nphp sakuci view:clear               # Bersihkan cache view", 'lang' => 'bash'],
                ],
            ],

            // ===== ROUTING =====
            [
                'id'    => '2-routing',
                'title' => 'Routing',
                'parts' => [
                    ['p' => 'Daftarkan route di routes/web.php:'],
                    ['code' => <<<'PHP'
                        use App\Controllers\PostController;
                        use Sakuci\Route;

                        // Route dasar
                        Route::get('/', function () { return view('welcome'); })->name('home');
                        Route::get('/posts', [PostController::class, 'index'])->name('posts.index');
                        Route::post('/posts', [PostController::class, 'store'])->name('posts.store');

                        // Parameter
                        Route::get('/posts/{id}', [PostController::class, 'show'])->name('posts.show');
                        Route::get('/posts/{id}/edit', [PostController::class, 'edit'])->name('posts.edit');

                        // 7 route CRUD sekaligus
                        Route::resource('posts', PostController::class);

                        // Group dengan middleware
                        Route::group(['prefix' => 'admin', 'middleware' => 'admin'], function () {
                            Route::get('/', [AdminController::class, 'index'])->name('admin.dashboard');
                        });
                        PHP, 'lang' => 'php'],
                    ['p' => 'Route dapat diakses lewat nama:'],
                    ['code' => "route('home')                  # /\nroute('posts.index')            # /posts\nroute('posts.show', ['id' => 1]) # /posts/1\nroute('admin.dashboard')        # /admin/", 'lang' => 'php'],
                ],
            ],

            // ===== MODEL =====
            [
                'id'    => '3-model',
                'title' => 'Model & Query Builder',
                'parts' => [
                    ['p' => 'Model mewakili satu tabel di database. Buat dengan:'],
                    ['code' => 'php sakuci make:model Post -m', 'lang' => 'bash'],
                    ['code' => <<<'PHP'
                        <?php
                        namespace App\Models;
                        use Sakuci\Database\Model;

                        class Post extends Model
                        {
                            protected static ?string $table = 'posts';  // opsional, ditebak dari nama
                            protected array $fillable = ['title', 'body', 'status'];
                            protected array $hidden = [];               // tidak tampil saat JSON
                        }
                        PHP, 'lang' => 'php'],
                    ['p' => '<strong>Query dasar:</strong>'],
                    ['code' => <<<'PHP'
                        Post::all();                           // Semua post
                        Post::find(1);                         // Post id=1
                        Post::findOrFail(1);                   // Atau 404
                        Post::where('status', 'published')->get();
                        Post::where('status', 'published')->first();
                        Post::latest()->limit(5)->get();
                        Post::count();

                        // Join
                        Post::leftJoin('users', 'users.id', '=', 'posts.user_id')
                            ->select('posts.title', 'users.nama')
                            ->get();

                        // Eager loading (cegah N+1)
                        Post::with('user', 'comments')->get();

                        // Paging
                        Post::paginate(15);  // 15 per halaman
                        PHP, 'lang' => 'php'],
                    ['p' => '<strong>CRUD:</strong>'],
                    ['code' => <<<'PHP'
                        // Create
                        Post::create(['title' => 'Hello', 'body' => '...', 'status' => 'published']);

                        // Update
                        $post = Post::find(1);
                        $post->update(['title' => 'New title']);

                        // Delete
                        $post->delete();
                        PHP, 'lang' => 'php'],
                ],
            ],

            // ===== CONTROLLER =====
            [
                'id'    => '4-controller',
                'title' => 'Controller',
                'parts' => [
                    ['p' => 'Buat dengan: php sakuci make:controller PostController'],
                    ['code' => <<<'PHP'
                        <?php
                        namespace App\Controllers;

                        use App\Models\Post;
                        use Sakuci\Controller;
                        use Sakuci\Http\Request;

                        class PostController extends Controller
                        {
                            // GET /posts
                            public function index()
                            {
                                $posts = Post::latest()->paginate(10);
                                return view('posts.index', ['posts' => $posts]);
                            }

                            // GET /posts/create
                            public function create()
                            {
                                return view('posts.create');
                            }

                            // POST /posts
                            public function store(Request $request)
                            {
                                $data = $request->validate([
                                    'title' => 'required|min:3|max:100',
                                    'body'  => 'required|min:10',
                                ]);

                                Post::create($data);

                                return redirect(route('posts.index'))
                                    ->with('success', 'Post berhasil dibuat.');
                            }

                            // GET /posts/{id} -- Route model binding
                            public function show(Post $post)
                            {
                                return view('posts.show', ['post' => $post]);
                            }

                            // GET /posts/{id}/edit
                            public function edit(Post $post)
                            {
                                return view('posts.edit', ['post' => $post]);
                            }

                            // PUT /posts/{id}
                            public function update(Request $request, Post $post)
                            {
                                $data = $request->validate([
                                    'title' => 'required|min:3|max:100',
                                    'body'  => 'required|min:10',
                                ]);

                                $post->update($data);

                                return redirect(route('posts.index'))
                                    ->with('success', 'Post berhasil diubah.');
                            }

                            // DELETE /posts/{id}
                            public function destroy(Post $post)
                            {
                                $post->delete();

                                return redirect(route('posts.index'))
                                    ->with('success', 'Post berhasil dihapus.');
                            }
                        }
                        PHP, 'lang' => 'php'],
                    ['p' => '<strong>Penjelasan:</strong> Parameter bertipe (Post $post) otomatis diambil dari database berdasarkan {id}. Ini namanya route model binding.'],
                ],
            ],

            // ===== VIEW =====
            [
                'id'    => '5-view',
                'title' => 'View (Template)',
                'parts' => [
                    ['p' => 'File view ada di resources/views/ dengan akhiran .sakuci.php. Pakai sintaks Blade-like:'],
                    ['code' => <<<'BLADE'
                        @extends('layouts.app')
                        @section('title', 'Daftar Post')
                        @section('content')

                            <h1>Daftar Post</h1>

                            {{-- Output aman (escape) --}}
                            <h2>{{ $post->title }}</h2>

                            {{-- Output tanpa escape --}}
                            {!! $html !!}

                            {{-- Kondisi --}}
                            @if ($posts->count() > 0)
                                Ada {{ $posts->count() }} post
                            @else
                                Belum ada post
                            @endif

                            {{-- Loop --}}
                            @foreach ($posts as $post)
                                <h3>{{ $post->title }}</h3>
                            @endforeach

                            {{-- Loop dengan else --}}
                            @forelse ($posts as $post)
                                <article>{{ $post->title }}</article>
                            @empty
                                <p>Belum ada post</p>
                            @endforelse

                            {{-- Include --}}
                            @include('components.post-card', ['post' => $post])

                            {{-- CSRF (wajib di form) --}}
                            <form method="POST" action="/posts">
                                @csrf
                                <input type="text" name="title">
                            </form>

                            {{-- Flash message --}}
                            @if (session('success'))
                                <div class="alert">{{ session('success') }}</div>
                            @endif

                            {{-- Validation error --}}
                            @error('title')
                                <span class="error">{{ $message }}</span>
                            @enderror

                            {{-- Old input --}}
                            <input type="text" value="{{ old('title') }}">

                        @endsection
                        BLADE, 'lang' => 'blade'],
                ],
            ],

            // ===== VALIDATION =====
            [
                'id'    => '6-validation',
                'title' => 'Validation',
                'parts' => [
                    ['p' => 'Di controller, gunakan $request->validate():'],
                    ['code' => <<<'PHP'
                        $data = $request->validate([
                            'nama'   => 'required|min:3|max:100',
                            'email'  => 'required|email|unique:users,email',
                            'umur'   => 'nullable|integer|min:18',
                            'status' => 'required|in:aktif,tidak-aktif',
                        ]);

                        // Kalau validasi gagal, auto-redirect dengan error & old input
                        // Kalau validasi lulus, $data berisi nilai yang sudah validated
                        PHP, 'lang' => 'php'],
                    ['p' => '<strong>Rule tersedia:</strong> required, nullable, email, url, numeric, integer, min, max, between, in, not_in, regex, alpha, alpha_num, alpha_dash, confirmed, same, different, unique, exists, date, size, array, boolean, string'],
                ],
            ],

            // ===== BOOTSTRAP UI =====
            [
                'id'    => '7-bootstrap-ui',
                'title' => 'Bootstrap 5.3.8 -- Grid, Card & Tabel',
                'parts' => [
                    ['p' => 'Bootstrap 5.3.8 sudah built-in (file lokal di public/vendor/bootstrap, tidak butuh internet). Ganti warna khas di public/css/app.css, otomatis berlaku ke seluruh tombol, badge, dan aksen brand:'],
                    ['code' => <<<'CSS'
                        :root {
                            --brand: #c2410c;       /* Warna utama */
                            --brand-dark: #9a3412; /* Saat hover */
                            --brand-subtle: #fff7ed; /* Latar lembut, mis. badge */
                        }
                        CSS, 'lang' => 'css'],

                    // ----- GRID -----
                    ['h3' => 'Grid System -- container, row, col'],
                    ['p' => 'Layout Bootstrap berbasis <strong>12 kolom</strong>. Tiga lapis yang selalu dipakai bersamaan: <code class="inline">.container</code> (bungkus lebar & center konten, sudah otomatis ada di layouts/app.sakuci.php), <code class="inline">.row</code> (bungkus satu baris kolom), lalu <code class="inline">.col-*</code> (kolom di dalamnya). Angka di belakang col menyatakan lebar dari 12, misal col-md-6 = separuh lebar mulai breakpoint md ke atas.'],
                    ['table' => [
                        ['Breakpoint' => '(tanpa akhiran) col-6', 'Lebar layar' => '< 576px (HP)', 'Kegunaan' => 'Berlaku di semua ukuran layar'],
                        ['Breakpoint' => 'col-sm-6', 'Lebar layar' => '>= 576px', 'Kegunaan' => 'HP landscape ke atas'],
                        ['Breakpoint' => 'col-md-6', 'Lebar layar' => '>= 768px', 'Kegunaan' => 'Tablet ke atas'],
                        ['Breakpoint' => 'col-lg-6', 'Lebar layar' => '>= 992px', 'Kegunaan' => 'Laptop ke atas'],
                        ['Breakpoint' => 'col-xl-6', 'Lebar layar' => '>= 1200px', 'Kegunaan' => 'Layar besar'],
                        ['Breakpoint' => 'col-xxl-6', 'Lebar layar' => '>= 1400px', 'Kegunaan' => 'Layar sangat besar'],
                    ]],
                    ['p' => '<strong>Kolom otomatis (tanpa angka):</strong> kalau semua col di satu row ditulis tanpa angka (cukup class="col"), Bootstrap membagi rata lebarnya secara otomatis -- cocok untuk jumlah kolom yang dinamis.'],
                    ['code' => <<<'BLADE'
                        <div class="container">
                            {{-- 3 kolom sama lebar, otomatis dibagi rata --}}
                            <div class="row g-3">
                                <div class="col">Kolom 1</div>
                                <div class="col">Kolom 2</div>
                                <div class="col">Kolom 3</div>
                            </div>

                            {{-- 2 kolom: penuh di HP, separuh-separuh mulai tablet (md) --}}
                            <div class="row g-3 mt-2">
                                <div class="col-md-6">Separuh kiri</div>
                                <div class="col-md-6">Separuh kanan</div>
                            </div>
                        </div>
                        BLADE, 'lang' => 'blade'],
                    ['preview' => <<<'HTML'
                        <div class="row g-3 text-center">
                            <div class="col"><div class="demo-box">col</div></div>
                            <div class="col"><div class="demo-box">col</div></div>
                            <div class="col"><div class="demo-box">col</div></div>
                        </div>
                        <div class="row g-3 mt-1 text-center">
                            <div class="col-md-6"><div class="demo-box demo-box-alt">col-md-6</div></div>
                            <div class="col-md-6"><div class="demo-box demo-box-alt">col-md-6</div></div>
                        </div>
                        HTML],
                    ['p' => '<strong>Offset & order:</strong> offset-md-4 menggeser kolom ke kanan sejauh 4/12 (dipakai untuk "menengahkan" kolom yang lebih sempit dari 12). order-1, order-2, dst mengubah urutan tampil tanpa mengubah urutan HTML -- berguna supaya urutan di HP beda dari di desktop.'],
                    ['code' => <<<'BLADE'
                        {{-- col-md-4 di tengah, karena sisa 8/12 dibagi rata kiri-kanan --}}
                        <div class="row">
                            <div class="col-md-4 offset-md-4">Kolom di tengah</div>
                        </div>

                        {{-- urutan tampil: B, C, A -- meski di HTML urutannya A, B, C --}}
                        <div class="row g-2">
                            <div class="col order-3">A (order-3)</div>
                            <div class="col order-1">B (order-1)</div>
                            <div class="col order-2">C (order-2)</div>
                        </div>
                        BLADE, 'lang' => 'blade'],
                    ['preview' => <<<'HTML'
                        <div class="row text-center">
                            <div class="col-md-4 offset-md-4"><div class="demo-box">col-md-4 offset-md-4</div></div>
                        </div>
                        <div class="row g-2 mt-1 text-center">
                            <div class="col order-3"><div class="demo-box demo-box-alt">A (order-3)</div></div>
                            <div class="col order-1"><div class="demo-box demo-box-alt">B (order-1)</div></div>
                            <div class="col order-2"><div class="demo-box demo-box-alt">C (order-2)</div></div>
                        </div>
                        HTML],
                    ['note' => 'g-2 / g-3 di atas row itu "gutter" (jarak antar kolom). Tanpa itu kolom akan mepet satu sama lain.'],

                    // ----- SPACING -----
                    ['h3' => 'Spacing Utilities -- Margin & Padding'],
                    ['p' => 'Pola nama kelasnya: <code class="inline">{property}{sisi}-{ukuran}</code>. Tidak perlu nulis CSS custom untuk jarak antar elemen -- tinggal tempel kelasnya di HTML.'],
                    ['table' => [
                        ['Bagian' => 'property', 'Kode' => 'm = margin, p = padding', 'Contoh' => 'm-3, p-2'],
                        ['Bagian' => 'sisi', 'Kode' => 't=top, b=bottom, s=start(kiri), e=end(kanan), x=kiri&kanan, y=atas&bawah, (kosong)=4 sisi', 'Contoh' => 'mt-3, px-2, mb-0'],
                        ['Bagian' => 'ukuran', 'Kode' => '0, 1(.25rem), 2(.5rem), 3(1rem), 4(1.5rem), 5(3rem), auto(khusus margin)', 'Contoh' => 'mt-4, mx-auto'],
                    ]],
                    ['code' => <<<'BLADE'
                        <div class="card p-4 mb-3">          {{-- padding 1rem semua sisi, jarak bawah 1rem --}}
                            <h5 class="mb-2">Judul</h5>       {{-- jarak bawah kecil ke teks berikutnya --}}
                            <p class="mb-0">Paragraf ini menempel ke elemen di bawahnya (mb-0).</p>
                        </div>

                        <div class="mx-auto" style="max-width: 400px;">Elemen ini di-center horizontal</div>

                        <div class="d-flex gap-2">          {{-- jarak antar item flex, tanpa margin manual --}}
                            <button class="btn btn-brand" type="button">Simpan</button>
                            <button class="btn btn-outline-secondary" type="button">Batal</button>
                        </div>
                        BLADE, 'lang' => 'blade'],
                    ['preview' => <<<'HTML'
                        <div class="bg-body-tertiary border rounded p-3">
                            <div class="demo-box mb-3">mb-3 &rarr; jarak ke bawah 1rem</div>
                            <div class="demo-box mb-3">mb-3 &rarr; jarak ke bawah 1rem</div>
                            <div class="demo-box mb-0">mb-0 &rarr; tanpa jarak</div>
                        </div>
                        <div class="d-flex gap-2 mt-3">
                            <div class="demo-box-alt demo-box p-1">p-1</div>
                            <div class="demo-box-alt demo-box p-3">p-3</div>
                            <div class="demo-box-alt demo-box p-5">p-5</div>
                        </div>
                        HTML],
                    ['note' => 'gap-1 s/d gap-5 dipakai di atas elemen ber-display flex/grid (mis. d-flex) untuk mengatur jarak antar anak secara otomatis, tanpa perlu margin manual di tiap item.'],

                    // ----- CARD -----
                    ['h3' => 'Card Component'],
                    ['p' => 'Card adalah kotak konten serba-guna. Bagian umum: <code class="inline">card-header</code>, <code class="inline">card-body</code> (berisi <code class="inline">card-title</code>, <code class="inline">card-subtitle</code>, <code class="inline">card-text</code>), dan <code class="inline">card-footer</code> -- semuanya opsional, pakai sesuai kebutuhan.'],
                    ['code' => <<<'BLADE'
                        <div class="card" style="max-width: 380px;">
                            <div class="card-header">Header Card</div>
                            <div class="card-body">
                                <h5 class="card-title">Judul Card</h5>
                                <h6 class="card-subtitle mb-2 text-secondary">Subjudul</h6>
                                <p class="card-text">Teks isi card, biasanya deskripsi singkat.</p>
                                <a href="#" class="btn btn-brand btn-sm">Aksi</a>
                            </div>
                            <div class="card-footer text-secondary small">Diperbarui 2 menit lalu</div>
                        </div>
                        BLADE, 'lang' => 'blade'],
                    ['preview' => <<<'HTML'
                        <div class="card" style="max-width: 380px;">
                            <div class="card-header">Header Card</div>
                            <div class="card-body">
                                <h5 class="card-title">Judul Card</h5>
                                <h6 class="card-subtitle mb-2 text-secondary">Subjudul</h6>
                                <p class="card-text">Teks isi card, biasanya deskripsi singkat.</p>
                                <a href="#" class="btn btn-brand btn-sm" onclick="return false;">Aksi</a>
                            </div>
                            <div class="card-footer text-secondary small">Diperbarui 2 menit lalu</div>
                        </div>
                        HTML],
                    ['p' => '<strong>Card di dalam grid</strong> (mis. daftar produk/post): bungkus tiap card dengan col-md-4, tambahkan <code class="inline">h-100</code> pada card supaya tinggi antar card sejajar meski isinya beda panjang.'],
                    ['code' => <<<'BLADE'
                        <div class="row g-3">
                            @foreach ($posts as $post)
                                <div class="col-md-4">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <h5 class="card-title">{{ $post->title }}</h5>
                                            <p class="card-text">{{ Str::limit($post->body, 80) }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        BLADE, 'lang' => 'blade'],
                    ['preview' => <<<'HTML'
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h5 class="card-title">Card A</h5>
                                        <p class="card-text">Deskripsi pendek.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h5 class="card-title">Card B</h5>
                                        <p class="card-text">Deskripsi yang jauh lebih panjang dari card lain di sebelahnya, tapi tinggi tetap sejajar berkat h-100.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h5 class="card-title">Card C</h5>
                                        <p class="card-text">Deskripsi pendek.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        HTML],

                    // ----- TABLE -----
                    ['h3' => 'Tabel (Table)'],
                    ['p' => 'Semua varian ini tinggal ditumpuk di satu tag <code class="inline">&lt;table&gt;</code>, tidak saling menggantikan.'],
                    ['table' => [
                        ['Kelas' => 'table-striped', 'Fungsi' => 'Warna selang-seling tiap baris'],
                        ['Kelas' => 'table-bordered', 'Fungsi' => 'Garis di semua sisi sel'],
                        ['Kelas' => 'table-hover', 'Fungsi' => 'Highlight baris saat kursor lewat'],
                        ['Kelas' => 'table-borderless', 'Fungsi' => 'Tanpa garis sama sekali'],
                        ['Kelas' => 'table-sm', 'Fungsi' => 'Padding sel lebih rapat'],
                        ['Kelas' => 'table-dark / table-light', 'Fungsi' => 'Tema gelap/terang untuk seluruh tabel atau thead'],
                        ['Kelas' => 'table-success, table-warning, table-danger, dst', 'Fungsi' => 'Warna kontekstual di baris/sel tertentu (tr atau td)'],
                        ['Kelas' => 'table-responsive', 'Fungsi' => 'Bungkus <table> agar bisa di-scroll horizontal di layar kecil'],
                    ]],
                    ['code' => <<<'BLADE'
                        <div class="table-responsive">
                            <table class="table table-striped table-hover table-bordered align-middle">
                                <caption>Daftar pengguna terbaru</caption>
                                <thead class="table-dark">
                                    <tr>
                                        <th>#</th>
                                        <th>Nama</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($users as $i => $user)
                                        <tr class="{{ $user->status === 'pending' ? 'table-warning' : '' }}">
                                            <td>{{ $i + 1 }}</td>
                                            <td>{{ $user->nama }}</td>
                                            <td>{{ $user->role }}</td>
                                            <td>
                                                <span class="badge text-bg-{{ $user->status === 'aktif' ? 'success' : 'secondary' }}">
                                                    {{ $user->status }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        BLADE, 'lang' => 'blade'],
                    ['preview' => <<<'HTML'
                        <div class="table-responsive">
                            <table class="table table-striped table-hover table-bordered align-middle mb-0">
                                <caption>Daftar pengguna terbaru</caption>
                                <thead class="table-dark">
                                    <tr><th>#</th><th>Nama</th><th>Role</th><th>Status</th></tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>1</td><td>Budi</td><td>Admin</td>
                                        <td><span class="badge text-bg-success">aktif</span></td>
                                    </tr>
                                    <tr class="table-warning">
                                        <td>2</td><td>Sari</td><td>Staff</td>
                                        <td><span class="badge text-bg-secondary">pending</span></td>
                                    </tr>
                                    <tr>
                                        <td>3</td><td>Andi</td><td>User</td>
                                        <td><span class="badge text-bg-secondary">nonaktif</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        HTML],

                    // ----- FLEXBOX -----
                    ['h3' => 'Flexbox -- Alternatif Mengatur Posisi'],
                    ['p' => 'Untuk posisi yang bukan grid kolom (mis. toolbar, header card, sejajarkan judul & tombol), pakai utility flex: <code class="inline">d-flex</code> mengaktifkan flexbox, <code class="inline">justify-content-*</code> mengatur sumbu horizontal (start/center/end/between/around), <code class="inline">align-items-*</code> mengatur sumbu vertikal (start/center/end), <code class="inline">flex-wrap</code> supaya item turun baris kalau sempit, dan <code class="inline">gap-*</code> untuk jarak antar item.'],
                    ['code' => <<<'BLADE'
                        <div class="d-flex justify-content-between align-items-center bg-light border rounded p-3">
                            <span class="fw-semibold">Daftar Post</span>
                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-outline-secondary" type="button">Filter</button>
                                <button class="btn btn-sm btn-brand" type="button">Tambah</button>
                            </div>
                        </div>
                        BLADE, 'lang' => 'blade'],
                    ['preview' => <<<'HTML'
                        <div class="d-flex justify-content-between align-items-center bg-body-tertiary border rounded p-3">
                            <span class="fw-semibold">Daftar Post</span>
                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-outline-secondary" type="button">Filter</button>
                                <button class="btn btn-sm btn-brand" type="button">Tambah</button>
                            </div>
                        </div>
                        HTML],

                    // ----- FORM -----
                    ['h3' => 'Form'],
                    ['p' => 'form-control untuk input, form-label untuk label, is-invalid + invalid-feedback untuk menampilkan error validasi tepat di bawah field-nya.'],
                    ['code' => <<<'BLADE'
                        <form method="POST">
                            @csrf

                            <div class="mb-3">
                                <label class="form-label" for="nama">Nama</label>
                                <input type="text" id="nama" name="nama"
                                       class="form-control {{ errors()->has('nama') ? 'is-invalid' : '' }}"
                                       value="{{ old('nama') }}">
                                @error('nama')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <button class="btn btn-brand" type="submit">Simpan</button>
                        </form>
                        BLADE, 'lang' => 'blade'],
                    ['preview' => <<<'HTML'
                        <form onsubmit="return false;">
                            <div class="mb-3">
                                <label class="form-label" for="demo-nama">Nama</label>
                                <input type="text" id="demo-nama" class="form-control" placeholder="Masukkan nama">
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="demo-invalid">Contoh field gagal validasi</label>
                                <input type="text" id="demo-invalid" class="form-control is-invalid" value="ab">
                                <div class="invalid-feedback">Nama minimal 3 karakter.</div>
                            </div>
                            <button class="btn btn-brand" type="button">Simpan</button>
                        </form>
                        HTML],

                    // ----- RINGKASAN CEPAT -----
                    ['h3' => 'Ringkasan Cepat Kelas Lain'],
                    ['table' => [
                        ['Kelas' => 'btn btn-primary / btn btn-brand', 'Fungsi' => 'Tombol biru bawaan / tombol warna khas Sakuci'],
                        ['Kelas' => 'btn-outline-*, btn-sm, btn-lg', 'Fungsi' => 'Tombol garis tepi & ukuran'],
                        ['Kelas' => 'alert alert-success/danger/warning/info', 'Fungsi' => 'Notifikasi berwarna'],
                        ['Kelas' => 'badge text-bg-*', 'Fungsi' => 'Label kecil berwarna (status, jumlah)'],
                        ['Kelas' => 'shadow-sm, border-0, rounded-3', 'Fungsi' => 'Bayangan, hilangkan garis tepi, sudut membulat'],
                        ['Kelas' => 'text-secondary, fw-bold, small', 'Fungsi' => 'Warna teks abu, tebal, ukuran kecil'],
                        ['Kelas' => 'navbar navbar-expand-lg', 'Fungsi' => 'Navigation bar responsif'],
                        ['Kelas' => 'list-group, list-group-item', 'Fungsi' => 'Daftar bertumpuk (mis. menu di dalam card)'],
                    ]],
                ],
            ],

            // ===== DATABASE =====
            [
                'id'    => '8-database',
                'title' => 'Database Setup',
                'parts' => [
                    ['p' => 'Edit .env:'],
                    ['code' => "DB_CONNECTION=mysql\nDB_HOST=127.0.0.1\nDB_PORT=3306\nDB_DATABASE=sakuci_belajar\nDB_USERNAME=root\nDB_PASSWORD=", 'lang' => 'ini'],
                    ['p' => 'Lalu buat database (lewat phpMyAdmin atau SQL):'],
                    ['code' => 'CREATE DATABASE sakuci_belajar CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;', 'lang' => 'sql'],
                    ['p' => 'Uji koneksi:'],
                    ['code' => 'php sakuci db:check', 'lang' => 'bash'],
                    ['p' => 'Untuk SQLite (tanpa setup), cukup ubah .env:'],
                    ['code' => 'DB_CONNECTION=sqlite', 'lang' => 'ini'],
                ],
            ],

            // ===== SESSION =====
            [
                'id'    => '9-session',
                'title' => 'Session & Flash Message',
                'parts' => [
                    ['p' => 'Di controller atau view:'],
                    ['code' => <<<'PHP'
                        use Sakuci\Session;

                        // Simpan
                        Session::put('user_id', 123);
                        Session::put('cart', ['item1', 'item2']);

                        // Baca
                        $id = Session::get('user_id');
                        $cart = Session::get('cart', []);  // Default []

                        // Cek ada
                        if (Session::has('user_id')) { ... }

                        // Hapus
                        Session::forget('user_id');

                        // Flash (sekali pakai, auto-hapus setelah ditampilkan)
                        return redirect('/')->with('success', 'Berhasil login!');
                        PHP, 'lang' => 'php'],
                    ['p' => 'Di view:'],
                    ['code' => <<<'BLADE'
                        {{ session('success') }}  {{-- atau null --}}
                        @if (session('error'))
                            <div class="alert alert-danger">{{ session('error') }}</div>
                        @endif
                        BLADE, 'lang' => 'blade'],
                ],
            ],

            // ===== MIDDLEWARE =====
            [
                'id'    => '10-middleware',
                'title' => 'Middleware',
                'parts' => [
                    ['p' => 'Middleware adalah filter yang menjalankan kode sebelum/sesudah request masuk ke controller. Daftarkan di config/app.php:'],
                    ['code' => <<<'PHP'
                        'middleware' => [
                            'auth'  => App\Middleware\Authenticate::class,
                            'guest' => App\Middleware\RedirectIfAuthenticated::class,
                            'admin' => App\Middleware\AdminOnly::class,
                        ],
                        PHP, 'lang' => 'php'],
                    ['p' => 'Pakai di route:'],
                    ['code' => <<<'PHP'
                        Route::get('/dashboard', [DashboardController::class, 'index'])
                            ->middleware('auth');

                        Route::group(['middleware' => 'admin'], function () {
                            Route::get('/admin', [AdminController::class, 'index']);
                        });
                        PHP, 'lang' => 'php'],
                    ['p' => 'Buat middleware:'],
                    ['code' => <<<'PHP'
                        <?php
                        namespace App\Middleware;

                        use Sakuci\Http\Request;
                        use Sakuci\Middleware;

                        class MyMiddleware extends Middleware
                        {
                            public function handle(Request $request, \Closure $next): mixed
                            {
                                // Sebelum masuk controller
                                if (/* kondisi gagal */) {
                                    abort(403, 'Tidak diizinkan');
                                }

                                $response = $next($request);

                                // Setelah controller selesai
                                return $response;
                            }
                        }
                        PHP, 'lang' => 'php'],
                ],
            ],

            // ===== MULTI-ROLE (dari awal) =====
            [
                'id'    => '11-multi-role',
                'title' => 'Multi-Role Login System',
                'parts' => [
                    ['p' => 'Instalasi baru hanya punya satu akun & satu role: admin. Role lain (staff, user, editor, dst) dibuat sendiri oleh admin lewat /admin/roles -- baru muncul di dropdown /admin/users setelah dibuat.'],
                    ['table' => [
                        ['username' => 'admin', 'password' => 'rahasia123', 'akses' => '/admin'],
                    ]],
                    ['p' => '<strong>Tambah role baru:</strong> Login sebagai admin, buka /admin/roles, isi nama role. Dua hal dibuat otomatis: berkas middleware <code class="inline">{Role}Only.php</code> di app/Middleware (langsung terdaftar sebagai alias, pakai lewat <code class="inline">Route::group([\'middleware\' => \'nama-role\'], ...)</code>), dan route group <code class="inline">/nama-role</code> di routes/web.php yang sudah dilindungi middleware tersebut.'],
                    ['p' => '<strong>Ganti nama / hapus role:</strong> Di /admin/roles, tiap role (selain admin) punya form ganti nama + toggle registrasi, dan tombol hapus. Middleware & route mengikuti otomatis; ganti nama juga memindahkan seluruh user pemilik role itu ke nama barunya. Role tidak bisa dihapus selama masih ada user yang memakainya.'],
                    ['p' => '<strong>Registrasi mandiri:</strong> Centang "Daftar" saat membuat/mengubah role supaya role itu muncul sebagai pilihan di halaman /register. Kalau tidak ada role yang dicentang, halaman /register otomatis mengarahkan kembali ke /login.'],
                ],
            ],

            // ===== TIPS =====
            [
                'id'    => '12-tips',
                'title' => 'Tips & Trik',
                'parts' => [
                    ['p' => '<strong>Helper global yang tersedia:</strong>'],
                    ['code' => <<<'PHP'
                        view('name', ['var' => 'value'])     // Render view
                        route('name', ['id' => 1])           // Generate URL
                        redirect('/path')                    // Redirect
                        redirect(route('home'))
                        back()                               // Kembali ke halaman sebelumnya
                        old('field')                         // Nilai input sebelumnya
                        errors()                             // Object error dari validasi
                        session('key')                       // Baca session
                        dd($var)                             // Dump & die
                        abort(404, 'Not found')              // Abort dengan status code
                        PHP, 'lang' => 'php'],
                    ['p' => '<strong>Eager loading (cegah N+1):</strong>'],
                    ['code' => <<<'PHP'
                        // Buruk: 11 query (1 posts + 10 comments)
                        foreach (Post::all() as $post) {
                            echo $post->comments;
                        }

                        // Bagus: 2 query (1 posts + 1 comments)
                        foreach (Post::with('comments')->get() as $post) {
                            echo $post->comments;
                        }

                        // Nested
                        Post::with('comments.user')->get();
                        PHP, 'lang' => 'php'],
                    ['p' => '<strong>Join data dari tabel lain:</strong>'],
                    ['code' => <<<'PHP'
                        User::leftJoin('profil', 'profil.user_id', '=', 'users.id')
                            ->select('users.nama', 'profil.alamat')
                            ->get();
                        PHP, 'lang' => 'php'],
                ],
            ],
        ];
    }
}
