# Contoh CRUD Lengkap: User

Panduan membuat aplikasi User management dengan Model, Controller, View, dan Route.

## 1. Buat Model + Migrasi Sekaligus

```bash
php Sakuci make:model User -m
```

Ini menghasilkan:
- `app/Models/User.php`
- `database/migrations/2026_08_19_211020_create_users_table.sql`

## 2. Update Migrasi

Edit file migrasi (sesuaikan sintaks MySQL atau SQLite):

### Untuk MySQL:
```sql
-- create_users_table

CREATE TABLE IF NOT EXISTS `users` (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama       VARCHAR(100) NOT NULL,
    email      VARCHAR(100) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Untuk SQLite:
```sql
-- create_users_table

CREATE TABLE IF NOT EXISTS "users" (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    nama       TEXT NOT NULL,
    email      TEXT NOT NULL UNIQUE,
    password   TEXT NOT NULL,
    created_at TEXT,
    updated_at TEXT
);
```

## 3. Update Model

Edit `app/Models/User.php`:

```php
<?php

namespace App\Models;

use Sakuci\Database\Model;

class User extends Model
{
    protected static ?string $table = 'users';

    // Kolom yang boleh diisi lewat create() dan update()
    protected array $fillable = ['nama', 'email', 'password'];

    // Password tidak boleh tampil saat di-convert ke array/JSON
    protected array $hidden = ['password'];
}
```

**Penjelasan:**
- `$table`: nama tabel di database
- `$fillable`: kolom yang aman untuk mass assignment (dari form)
- `$hidden`: kolom yang disembunyikan saat serialisasi (password)

## 4. Buat Controller

```bash
php Sakuci make:controller UserController
```

Edit `app/Controllers/UserController.php`:

```php
<?php

namespace App\Controllers;

use App\Models\User;
use Sakuci\Controller;
use Sakuci\Http\Request;

class UserController extends Controller
{
    // 1. Tampilkan daftar semua user
    public function index()
    {
        $users = User::latest()->get();

        return view('users.index', ['users' => $users]);
    }

    // 2. Tampilkan form tambah user
    public function create()
    {
        return view('users.create');
    }

    // 3. Simpan user baru ke database
    public function store(Request $request)
    {
        $data = $request->validate([
            'nama'     => 'required|min:3|max:100',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:6',
        ]);

        // Hash password sebelum menyimpan
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);

        User::create($data);

        return redirect(route('users.index'))->with('success', 'User berhasil ditambahkan.');
    }

    // 4. Tampilkan detail user (route model binding)
    public function show(User $user)
    {
        return view('users.show', ['user' => $user]);
    }

    // 5. Tampilkan form edit user
    public function edit(User $user)
    {
        return view('users.edit', ['user' => $user]);
    }

    // 6. Simpan perubahan user
    public function update(Request $request, User $user)
    {
        $rules = [
            'nama'     => 'required|min:3|max:100',
            'email'    => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|min:6',
        ];

        $data = $request->validate($rules);

        // Password opsional saat edit â€” hanya hash jika diisi
        if ($data['password'] ?? false) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return redirect(route('users.index'))->with('success', 'User berhasil diperbarui.');
    }

    // 7. Hapus user
    public function destroy(User $user)
    {
        $user->delete();

        return redirect(route('users.index'))->with('success', 'User berhasil dihapus.');
    }
}
```

**Penjelasan:**
- `index()`: menampilkan daftar user terurut paling baru
- `create()`: tampilkan form tambah (GET /users/create)
- `store()`: terima data form, validasi, hash password, simpan (POST /users)
- `show(User $user)`: tampilkan detail user (GET /users/{id}) â€” **route model binding** otomatis mengambil dari database
- `edit(User $user)`: tampilkan form ubah dengan data lama (GET /users/{id}/edit)
- `update()`: terima perubahan, password opsional (PUT /users/{id})
- `destroy()`: hapus user (DELETE /users/{id})

## 5. Daftarkan Route

Edit `routes/web.php`:

```php
<?php

use App\Controllers\UserController;
use Sakuci\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

// 7 route CRUD sekaligus
Route::resource('users', UserController::class);
```

Perintah `Route::resource()` otomatis membuat 7 route:

| Method | URI | Controller Method | Nama Route |
|--------|-----|-------------------|-----------|
| GET | /users | index | users.index |
| GET | /users/create | create | users.create |
| POST | /users | store | users.store |
| GET | /users/{id} | show | users.show |
| GET | /users/{id}/edit | edit | users.edit |
| PUT | /users/{id} | update | users.update |
| DELETE | /users/{id} | destroy | users.destroy |

Lihat semua route:
```bash
php Sakuci route:list
```

## 6. Buat Views

### `resources/views/users/index.Sakuci.php` - Daftar User

```blade
@extends('layouts.app')

@section('title', 'Daftar User')

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Daftar User</h1>
        <a class="btn btn-brand" href="{{ route('users.create') }}">Tambah User</a>
    </div>

    @forelse ($users as $user)
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body d-flex justify-content-between align-items-center gap-3">
                <div>
                    <h2 class="h5 mb-1">{{ $user->nama }}</h2>
                    <p class="text-secondary small mb-0">{{ $user->email }}</p>
                </div>

                <div class="d-flex gap-2 flex-shrink-0">
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('users.edit', ['id' => $user->id]) }}">Ubah</a>
                    <form method="POST" action="{{ route('users.destroy', ['id' => $user->id]) }}" onsubmit="return confirm('Yakin hapus?')">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger" type="submit">Hapus</button>
                    </form>
                </div>
            </div>
        </div>
    @empty
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <p class="text-secondary">Belum ada user.</p>
                <a class="btn btn-brand" href="{{ route('users.create') }}">Tambah sekarang</a>
            </div>
        </div>
    @endforelse

@endsection
```

### `resources/views/users/create.Sakuci.php` - Form Tambah

```blade
@extends('layouts.app')

@section('title', 'Tambah User')

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Tambah User</h1>
        <a class="btn btn-outline-secondary" href="{{ route('users.index') }}">Kembali</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('users.store') }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label" for="nama">Nama</label>
                    <input type="text" id="nama" name="nama" value="{{ old('nama') }}" class="form-control {{ errors()->has('nama') ? 'is-invalid' : '' }}">
                    @error('nama') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" class="form-control {{ errors()->has('email') ? 'is-invalid' : '' }}">
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control {{ errors()->has('password') ? 'is-invalid' : '' }}">
                    @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="d-flex gap-2">
                    <button class="btn btn-brand px-4" type="submit">Simpan</button>
                    <a class="btn btn-outline-secondary" href="{{ route('users.index') }}">Batal</a>
                </div>
            </form>
        </div>
    </div>

@endsection
```

### `resources/views/users/edit.Sakuci.php` - Form Ubah

```blade
@extends('layouts.app')

@section('title', 'Ubah User')

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Ubah User</h1>
        <a class="btn btn-outline-secondary" href="{{ route('users.index') }}">Kembali</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('users.update', ['id' => $user->id]) }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label" for="nama">Nama</label>
                    <input type="text" id="nama" name="nama" value="{{ old('nama', $user->nama) }}" class="form-control {{ errors()->has('nama') ? 'is-invalid' : '' }}">
                    @error('nama') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" class="form-control {{ errors()->has('email') ? 'is-invalid' : '' }}">
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label" for="password">Password (kosongkan jika tidak ingin ubah)</label>
                    <input type="password" id="password" name="password" class="form-control {{ errors()->has('password') ? 'is-invalid' : '' }}">
                    @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="d-flex gap-2">
                    <button class="btn btn-brand px-4" type="submit">Perbarui</button>
                    <a class="btn btn-outline-secondary" href="{{ route('users.index') }}">Batal</a>
                </div>
            </form>
        </div>
    </div>

@endsection
```

## 7. Jalankan

```bash
# Siapkan database
php Sakuci migrate

# Jalankan server
php Sakuci serve
```

Buka browser ke **http://127.0.0.1:8000/users** â€” halaman daftar user akan tampil.

## Konsep Penting

### Route Model Binding
```php
public function edit(User $user)  // Parameter User $user akan diisi otomatis dari database
```

Bukan perlu menulis:
```php
public function edit($id) {
    $user = User::findOrFail($id);  // Manual cari
}
```

### Validasi Unik dengan Exclude
```php
'email' => 'required|email|unique:users,email,' . $user->id
```

Saat edit, email baru boleh sama seperti milik user lain, tapi harus unik dari user lain. Itulah mengapa ada `,$user->id`.

### Password Hash & Hidden
```php
$data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
```

Password dienkripsi sebelum disimpan, bukan plain text.

```php
protected array $hidden = ['password'];
```

Ketika model di-convert ke array/JSON (misalnya saat return JSON API), password tidak ikut.

### Redirect dengan Flash Message
```php
return redirect(route('users.index'))->with('success', 'User berhasil ditambahkan.');
```

Pesan disimpan satu kali di session dan otomatis dihapus setelah ditampilkan (flash).

## Summary

```
Alur Model â†’ Controller â†’ View:

1. User akses http://127.0.0.1:8000/users
   â†“
2. Route::resource('users') â†’ UserController::index()
   â†“
3. Controller ambil data: User::latest()->get()
   â†“
4. Controller kirim ke view dengan view('users.index', ['users' => $users])
   â†“
5. View tampilkan dalam tabel/card dengan loop @foreach
```

Demikian. Silakan copy model, controller, views, dan routes di atas ke project Anda.

