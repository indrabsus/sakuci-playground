# Sakuci Framework

Kerangka PHP ringan bergaya Laravel -- **Route, Model, View, Controller** -- tanpa Composer,
tanpa dependensi eksternal. Cukup PHP OOP murni.

```
Request -> public/index.php -> Application -> Router -> Middleware -> Controller -> Model
                                                                            |
                                                        Response <- View (.sakuci.php)
```

> **Baru pertama kali memakai Sakuci?** Ikuti **[TUTORIAL.md](TUTORIAL.md)** -- panduan
> langkah demi langkah dari menyalakan server sampai membuat CRUD lengkap.
> File README ini adalah rujukan sintaks, bukan panduan awal.

---

## Persyaratan

| Kebutuhan | Keterangan |
|---|---|
| PHP 8.1+ | Wajib. |
| `pdo_mysql` | Untuk MySQL/MariaDB -- aktif secara bawaan di XAMPP dan Laragon. |
| `pdo_sqlite` | Hanya bila memakai SQLite. Aktifkan `extension=pdo_sqlite` di `php.ini`. |
| `mbstring` | Opsional -- sudah ada polyfill bawaan bila belum aktif. |

---

## Menjalankan

```bash
php sakuci serve
```

Buka http://127.0.0.1:8000.

Untuk XAMPP/Apache: salin folder ini ke `htdocs`, lalu akses
`http://localhost/sakuci-framework/` (sudah ada `.htaccess` yang mengarahkan ke
`public/`). Idealnya arahkan DocumentRoot langsung ke folder `public/`.

---

## Pengaturan Database

Salin `.env.example` menjadi `.env`, lalu sesuaikan:

```ini
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sakuci
DB_USERNAME=root
DB_PASSWORD=
```

Databasenya harus dibuat lebih dulu (lewat phpMyAdmin atau perintah SQL):

```sql
CREATE DATABASE sakuci CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
```

Uji koneksinya:

```bash
php sakuci db:check
```

Bila gagal, pesan errornya sudah menyertakan saran perbaikan.

**Tanpa server database** -- ganti satu baris saja di `.env`:

```ini
DB_CONNECTION=sqlite
```

File otomatis dibuat di `database/database.sqlite`.

---

## Struktur Folder

```
sakuci-framework/
|-- app/
|   |-- Controllers/          controller aplikasi (masih kosong)
|   |-- Models/               model, satu file per tabel (masih kosong)
|   +-- Middleware/           filter request (berisi VerifyCsrfToken)
|-- config/
|   |-- app.php               nama app, debug, timezone, middleware
|   |-- database.php          koneksi sqlite / mysql
|   +-- view.php              lokasi & cache view
|-- core/                     ISI FRAMEWORK -- tidak perlu diubah
|   |-- Application.php       kernel: boot, dispatch, error handler
|   |-- Route.php             facade statis Route::
|   |-- Routing/              Router + RouteDefinition
|   |-- Http/                 Request, Response, RedirectResponse
|   |-- Database/             Connection, QueryBuilder, Model, Relation, Paginator
|   |-- Validation/           Validator
|   |-- View.php              template engine (.Sakuci.php)
|   |-- Controller.php        base controller
|   |-- Session.php           session + flash + old input
|   |-- helpers.php           fungsi global: view(), route(), old(), dd()
|   +-- bootstrap.php         autoloader PSR-4 (pengganti Composer)
|-- database/migrations/      file .sql
|-- public/                   document root
|   |-- css/app.css           tema (warna khas, blok kode)
|   +-- vendor/bootstrap/     Bootstrap 5.3.8 -- lokal, tanpa internet
|-- resources/views/          file *.sakuci.php (berisi layout + welcome)
|-- routes/web.php            daftar route
|-- storage/                  cache view + log
|-- .env                      pengaturan database & aplikasi
|-- server.php                router untuk `php -S`
|-- sakuci                    CLI (padanan artisan)
|-- TUTORIAL.md               panduan langkah demi langkah
+-- README.md                 rujukan sintaks (file ini)
```

---

## Route

`routes/web.php`

```php
use App\Controllers\PostController;
use Sakuci\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::post('/posts', [PostController::class, 'store'])->name('posts.store');
Route::put('/posts/{id}', [PostController::class, 'update']);
Route::delete('/posts/{id}', [PostController::class, 'destroy']);

// Parameter opsional
Route::get('/arsip/{tahun?}', [ArsipController::class, 'index']);

// Closure
Route::get('/halo/{nama}', fn (string $nama) => "Halo, {$nama}!");

// Mengembalikan array -> otomatis JSON
Route::get('/api/posts', fn () => ['data' => Post::all()]);

// 7 route CRUD sekaligus
Route::resource('posts', PostController::class);

// Group: prefix + middleware
Route::group(['prefix' => 'admin', 'middleware' => 'auth'], function () {
    Route::get('/dashboard', [AdminController::class, 'index'])->name('admin.dashboard');
});

// Tanpa controller
Route::view('/kontak', 'kontak');
Route::redirect('/lama', '/baru');
```

Route yang dihasilkan `Route::resource('posts', ...)`:

| Method | URI                | Controller | Nama route      |
|--------|--------------------|------------|-----------------|
| GET    | /posts             | index      | posts.index     |
| GET    | /posts/create      | create     | posts.create    |
| POST   | /posts             | store      | posts.store     |
| GET    | /posts/{id}        | show       | posts.show      |
| GET    | /posts/{id}/edit   | edit       | posts.edit      |
| PUT    | /posts/{id}        | update     | posts.update    |
| DELETE | /posts/{id}        | destroy    | posts.destroy   |

Membuat URL dari nama route:

```php
route('posts.show', ['id' => 7]);   // /posts/7
```

---

## Controller

```php
namespace App\Controllers;

use App\Models\Post;
use Sakuci\Controller;
use Sakuci\Http\Request;

class PostController extends Controller
{
    public function index(Request $request)
    {
        return view('posts.index', ['posts' => Post::latest()->paginate(10)]);
    }

    // Route model binding: {id} otomatis jadi objek Post
    public function show(Post $post)
    {
        return view('posts.show', ['post' => $post]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'  => 'required|min:3|max:120',
            'body'   => 'required|min:10',
            'status' => 'required|in:draft,published',
        ]);

        Post::create($data);

        return redirect(route('posts.index'))->with('success', 'Tersimpan!');
    }
}
```

Jika validasi gagal, user otomatis dikembalikan ke halaman sebelumnya lengkap dengan
pesan error (`errors()`) dan isian lama (`old()`).

Aturan validasi: `required`, `nullable`, `email`, `url`, `numeric`, `integer`, `string`,
`boolean`, `array`, `min`, `max`, `between`, `size`, `in`, `not_in`, `regex`, `alpha`,
`alpha_num`, `alpha_dash`, `date`, `confirmed`, `same`, `different`, `unique`, `exists`.

---

## Model

```php
namespace App\Models;

use Sakuci\Database\Model;

class Post extends Model
{
    protected static ?string $table = 'posts';   // opsional, ditebak dari nama class
    protected array $fillable = ['title', 'slug', 'body', 'status'];
    protected array $hidden   = [];              // disembunyikan saat toArray()/JSON
    protected array $casts    = ['meta' => 'array'];
    public bool $timestamps   = true;            // created_at & updated_at otomatis
}
```

```php
Post::all();
Post::find(1);
Post::findOrFail(1);                 // 404 bila tidak ada
Post::create(['title' => 'Halo']);
Post::destroy(3);

Post::where('status', 'published')
    ->where('title', 'like', '%php%')
    ->orderBy('id', 'desc')
    ->limit(10)
    ->get();

Post::query()->latest()->paginate(15);
Post::query()->whereIn('id', [1, 2, 3])->count();

$post = Post::find(1);
$post->title = 'Judul baru';
$post->save();
$post->update(['status' => 'draft']);
$post->delete();
```

### Relasi

Contoh tiga tabel yang saling terhubung (User -> Post -> Comment):

```php
use Sakuci\Database\Relation;

class User extends Model
{
    protected array $hidden = ['password'];                                          // tak ikut ke JSON

    public function posts(): Relation    { return $this->hasMany(Post::class); }     // $user->posts
    public function comments(): Relation { return $this->hasMany(Comment::class); }  // $user->comments
}

class Post extends Model
{
    public function user(): Relation     { return $this->belongsTo(User::class); }   // $post->user
    public function comments(): Relation { return $this->hasMany(Comment::class); }  // $post->comments
}

class Comment extends Model
{
    public function post(): Relation { return $this->belongsTo(Post::class); }       // $comment->post
    public function user(): Relation { return $this->belongsTo(User::class); }       // $comment->user
}
```

Kunci ditebak otomatis (Post -> post_id, induk -> id), atau tulis sendiri:

```php
$this->hasMany(Comment::class, 'artikel_id', 'id');
$this->belongsTo(User::class, 'penulis_id', 'id');
```

Relasi tetap bisa dirantai seperti query biasa:

```php
$post->comments()->where('approved', 1)->latest()->limit(5)->get();
```

### Eager Loading -- `with()`

Memanggil relasi di dalam perulangan menghasilkan **N+1 query**:

```php
$posts = Post::all();                 // 1 query
foreach ($posts as $post) {
    count($post->comments);           // +1 query per postingan
}
// 10 postingan = 11 query
```

`with()` memuatnya sekaligus:

```php
$posts = Post::with('comments')->get();   // 2 query, berapa pun jumlah postingannya
foreach ($posts as $post) {
    count($post->comments);               // tidak ada query tambahan
}
```

Bentuk pemakaian:

```php
Post::with('comments')->get();                       // satu relasi
Post::with(['user', 'comments'])->get();             // beberapa relasi
Post::with('comments.user')->get();                  // bersarang
Post::with('comments', fn ($q) => $q->latest())->get();          // dengan syarat
Post::with(['comments' => fn ($q) => $q->where('ok', 1)])->get(); // bentuk array

Post::with('comments')->where('status', 'published')->paginate(10);  // ikut jalan di paginate
```

Relasi bersarang memakai titik, dan kedalamannya bebas:

```php
Post::with(['user', 'comments.user'])->get();   // penulis post + penulis tiap komentar
User::with('posts.comments.user')->get();       // tiga tingkat
```

Perbandingan terukur pada 10 postingan x 3 komentar:

| Cara | Query |
|---|---|
| Tanpa `with()` | **48** |
| `with(['user', 'comments'])` -- penulis komentar masih N+1 | **30** |
| `with(['user', 'comments.user'])` | **4** |
| `User::with('posts.comments.user')` | **4** |

Jumlah query tetap segitu berapa pun banyaknya baris -- satu query per tingkat relasi.

Untuk model yang sudah terlanjur diambil:

```php
$post = Post::find(1);
$post->load('comments.user');       // muat sekarang
$post->loadMissing('comments');     // lewati bila sudah dimuat

$post->relationLoaded('comments');  // true / false
```

Hasil relasi: `hasMany` -> array model, `hasOne`/`belongsTo` -> satu model atau `null`.
Relasi yang sudah dimuat ikut terbawa saat `toArray()` / respons JSON.

> Catatan: sama seperti Laravel, `limit()` di dalam closure `with()` berlaku untuk
> keseluruhan query relasi, bukan per induk.

Menghitung query yang benar-benar dijalankan (untuk memastikan tidak ada N+1):

```php
count(Sakuci\Database\Connection::log());
```

---

## Tampilan (Bootstrap)

**Bootstrap 5.3.8** sudah disertakan secara lokal di `public/vendor/bootstrap`
(CSS + JS bundle) dan dipanggil dari `resources/views/layouts/app.Sakuci.php`.
Tidak butuh internet, tidak butuh npm.

Setiap view yang memakai `@extends('layouts.app')` otomatis ikut tertata.

Menyesuaikan warna khas -- ubah di `public/css/app.css`:

```css
:root {
    --brand: #c2410c;
    --brand-dark: #9a3412;
}
```

Kelas tambahan di luar Bootstrap: `btn-brand`, `btn-outline-brand`, `text-brand`,
`badge-brand`, `brand-mark`, `step-number`, `pre.code`, `code.inline`.

Ingin memakai Tailwind atau CSS sendiri? Ganti saja tag `<link>` di `resources/views/layouts/app.sakuci.php` -- framework tidak terikat pada Bootstrap.

---

## View

File view berada di `resources/views` dengan akhiran `.sakuci.php`.

```php
view('posts.index', ['posts' => $posts]);   // resources/views/posts/index.Sakuci.php
```

Sintaks yang didukung:

```blade
{{ $variabel }}              {{-- otomatis di-escape --}}
{!! $html !!}                {{-- tanpa escape --}}
{{-- komentar --}}

@extends('layouts.app')
@section('title', 'Judul')
@section('content') ... @endsection
@yield('content')
@show    @parent
@include('partials.flash')
@includeIf('partials.opsional', ['x' => 1])

@if (...) @elseif (...) @else @endif
@unless (...) @endunless
@isset ($x) @endisset
@empty ($x) @endempty
@foreach ($items as $item) @endforeach
@forelse ($items as $item) ... @empty ... @endforelse
@for (...) @endfor      @while (...) @endwhile
@continue   @break

@error('title') {{ $message }} @enderror
@csrf                        {{-- token CSRF --}}
@method('PUT')               {{-- method spoofing --}}
@php ... @endphp
@json($data)   @dd($x)   @dump($x)
```

Form update/hapus:

```blade
<form method="POST" action="{{ route('posts.update', ['id' => $post->id]) }}">
    @csrf
    @method('PUT')
    ...
</form>
```

View dikompilasi menjadi PHP biasa di `storage/framework/views` dan otomatis dikompilasi ulang saat file sumber berubah.

---

## Helper Global

| Helper | Kegunaan |
|---|---|
| `view($nama, $data)` | Render view menjadi Response |
| `redirect($url)` / `back()` | Redirect (`->with()`, `->withErrors()`, `->withInput()`) |
| `route($nama, $params)` | URL dari nama route |
| `url($path)` / `asset($path)` | URL absolut |
| `request($key)` | Ambil input |
| `old($key, $default)` | Isian form sebelumnya |
| `errors()` | MessageBag: `->any()`, `->first('field')`, `->all()` |
| `session($key)` | Session / flash |
| `config('app.name')` | Konfigurasi |
| `env('DB_HOST')` | Isi file `.env` |
| `csrf_token()` | Token CSRF |
| `abort(404)` | Hentikan dengan error HTTP |
| `dd($x)` / `dump($x)` | Debug |
| `e($x)`, `str_slug()`, `str_limit()`, `str_snake()`, `str_studly()`, `str_plural()` | Utilitas string |

---

## Middleware

```php
namespace App\Middleware;

use Sakuci\Http\Request;
use Sakuci\Middleware;

class Authenticate extends Middleware
{
    public function handle(Request $request, \Closure $next): mixed
    {
        if (! session('user')) {
            return redirect('/login');
        }

        return $next($request);
    }
}
```

Daftarkan di `config/app.php`:

```php
'global_middleware' => [App\Middleware\VerifyCsrfToken::class],   // semua request
'middleware'        => ['auth' => App\Middleware\Authenticate::class],
```

Pakai di route:

```php
Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('auth');
```

---

## Perintah CLI

```bash
php sakuci serve                 # server pengembangan (127.0.0.1:8000)
php sakuci migrate               # jalankan .sql di database/migrations
php sakuci migrate:fresh         # hapus SEMUA tabel lalu mulai dari nol
php sakuci db:check              # uji koneksi database sesuai .env
php sakuci route:list            # daftar seluruh route
php sakuci make:controller Nama  # buat controller
php sakuci make:model Nama       # buat model
php sakuci make:model Nama -m    # buat model + berkas migrasinya sekaligus
php sakuci make:view nama.view   # buat view
php sakuci make:migration nama   # buat file migrasi
php sakuci view:clear            # bersihkan cache view
```

---

## Sebelum Produksi

1. Set `APP_DEBUG=false` di `.env`.
2. Arahkan DocumentRoot web server ke folder `public/`.
3. Pastikan folder `storage/` bisa ditulis.
