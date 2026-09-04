# Contoh Model â€” Berbagai Kasus

## 1. Model Sederhana: Post

```php
<?php
namespace App\Models;

use Sakuci\Database\Model;

class Post extends Model
{
    protected static ?string $table = 'posts';

    // Kolom yang boleh diisi create()/update()
    protected array $fillable = ['title', 'body', 'status'];

    // Kolom yang disembunyikan saat JSON/array
    protected array $hidden = [];
}
```

**Cara pakai:**
```php
Post::all();
Post::find(1);
Post::create(['title' => 'Hello', 'body' => 'World', 'status' => 'published']);
$post->update(['status' => 'draft']);
$post->delete();
```

---

## 2. Model dengan Hidden: User

Menyembunyikan password saat toArray() atau JSON:

```php
<?php
namespace App\Models;

use Sakuci\Database\Model;

class User extends Model
{
    protected static ?string $table = 'users';

    protected array $fillable = ['nama', 'email', 'password'];

    // Password tidak boleh tampil di JSON/array
    protected array $hidden = ['password'];

    // Helper method: cek password
    public function verifyPassword(string $plain): bool
    {
        return password_verify($plain, $this->password);
    }

    // Static method: cari user yang sedang login
    public static function current(): ?static
    {
        static $user = null;
        static $resolved = false;

        if (!$resolved) {
            $resolved = true;
            if (\Sakuci\Session::has('user_id')) {
                $user = static::find(\Sakuci\Session::get('user_id'));
            }
        }

        return $user;
    }
}
```

**Cara pakai:**
```php
$user = User::create([
    'nama' => 'Budi',
    'email' => 'budi@example.com',
    'password' => password_hash('rahasia', PASSWORD_DEFAULT),
]);

// Password tidak tampil
echo json_encode($user);  // {"id": 1, "nama": "Budi", "email": "budi@example.com"}

// Verifikasi password
if ($user->verifyPassword('rahasia')) {
    echo 'Benar!';
}
```

---

## 3. Model dengan Custom Methods: Produk

Tambah logika bisnis di model:

```php
<?php
namespace App\Models;

use Sakuci\Database\Model;

class Produk extends Model
{
    protected static ?string $table = 'produk';

    protected array $fillable = ['nama', 'harga', 'stok', 'kategori'];

    // Cek stok tersedia
    public function tersedia(): bool
    {
        return $this->stok > 0;
    }

    // Hitung harga diskon
    public function hargaDiskon(int $persen): float
    {
        return $this->harga * (100 - $persen) / 100;
    }

    // Kurangi stok (saat ada order)
    public function kurangiStok(int $jumlah): bool
    {
        if ($this->stok < $jumlah) {
            return false;
        }

        $this->update(['stok' => $this->stok - $jumlah]);
        return true;
    }

    // Static method: produk paling laku
    public static function paliing Laku(int $limit = 5)
    {
        return static::orderBy('terjual', 'desc')->limit($limit)->get();
    }
}
```

**Cara pakai:**
```php
$produk = Produk::find(1);

if ($produk->tersedia()) {
    echo 'Tersedia';
    echo 'Harga: ' . $produk->hargaDiskon(20);  // Diskon 20%
    $produk->kurangiStok(2);
}

$top5 = Produk::paliingLaku(5);
```

---

## 4. Model dengan Relasi: Kategori â†’ Produk

Tabel kategori punya banyak produk:

```php
<?php
namespace App\Models;

use Sakuci\Database\Model;

class Kategori extends Model
{
    protected static ?string $table = 'kategori';

    protected array $fillable = ['nama', 'deskripsi'];

    // Relasi: kategori punya banyak produk
    public function produk()
    {
        return new \Sakuci\Database\Relation(
            type: 'hasMany',
            relatedClass: Produk::class,
            foreignKey: 'kategori_id',
            localKey: 'id'
        );
    }
}
```

**Cara pakai:**
```php
$kategori = Kategori::find(1);

// Eager loading (cegah N+1)
$kategoris = Kategori::with('produk')->get();

// Lalu akses relasi
foreach ($kategoris as $kat) {
    echo $kat->nama . ': ' . count($kat->produk);
}
```

---

## 5. Model dengan Timestamps: Blog

Otomatis catat waktu create & update:

```php
<?php
namespace App\Models;

use Sakuci\Database\Model;

class Blog extends Model
{
    protected static ?string $table = 'blog';

    protected array $fillable = ['judul', 'isi', 'penulis_id'];

    // created_at & updated_at otomatis diisi
    public bool $timestamps = true;

    // Query scope: postingan terbaru bulan ini
    public function scopeIniBulan($query)
    {
        $awal = date('Y-m-01');
        $akhir = date('Y-m-t');

        return $query->whereBetween('created_at', [$awal, $akhir]);
    }
}
```

**Cara pakai:**
```php
// created_at & updated_at otomatis
$blog = Blog::create(['judul' => 'Belajar Sakuci', 'isi' => '...', 'penulis_id' => 1]);
// created_at: 2026-08-20 12:30:45
// updated_at: 2026-08-20 12:30:45

// Update created_at otomatis berubah
$blog->update(['isi' => 'Konten baru']);
// updated_at: 2026-08-20 12:45:00

// Query scope
$bulanan = Blog::iniBulan()->get();  // Postingan bulan Agustus
```

---

## 6. Model dengan Casting: Konfigurasi

Cast tipe data otomatis:

```php
<?php
namespace App\Models;

use Sakuci\Database\Model;

class Konfigurasi extends Model
{
    protected static ?string $table = 'konfigurasi';

    protected array $fillable = ['kunci', 'nilai'];

    // Cast nilai JSON jadi array
    protected array $casts = [
        'nilai' => 'array',  // Jika nilai berupa JSON
    ];
}
```

**Cara pakai:**
```php
// Simpan
Konfigurasi::create([
    'kunci' => 'tema',
    'nilai' => json_encode(['warna' => 'biru', 'font' => 'sans-serif']),
]);

// Baca (otomatis jadi array)
$config = Konfigurasi::where('kunci', 'tema')->first();
echo $config->nilai['warna'];  // biru (bukan string, tapi array)
```

---

## 7. Model Lengkap: Pesanan (Order)

Kombinasi dari semua di atas:

```php
<?php
namespace App\Models;

use Sakuci\Database\Model;

class Pesanan extends Model
{
    protected static ?string $table = 'pesanan';

    protected array $fillable = ['user_id', 'total_harga', 'status', 'catatan'];

    public bool $timestamps = true;

    // Scope: pesanan belum dikirim
    public function scopeBelumDikirim($query)
    {
        return $query->whereIn('status', ['pending', 'dikemas', 'siap_kirim']);
    }

    // Scope: pesanan bulan ini
    public function scopeIniBulan($query)
    {
        return $query->whereBetween('created_at', [
            date('Y-m-01 00:00:00'),
            date('Y-m-t 23:59:59'),
        ]);
    }

    // Helper: ubah status
    public function kirim()
    {
        return $this->update(['status' => 'dikirim']);
    }

    public function selesai()
    {
        return $this->update(['status' => 'selesai']);
    }

    // Total omzet bulan ini
    public static function omzetIniBulan(): float
    {
        return static::iniBulan()
            ->where('status', 'selesai')
            ->sum('total_harga');
    }
}
```

**Cara pakai:**
```php
// Buat pesanan baru
$pesanan = Pesanan::create([
    'user_id' => 1,
    'total_harga' => 150000,
    'status' => 'pending',
]);

// Pakai scope
$pending = Pesanan::belumDikirim()->get();

// Pakai helper
$pesanan->kirim();
$pesanan->selesai();

// Statistik
$omzet = Pesanan::omzetIniBulan();  // Total yang sudah selesai
```

---

## Cheat Sheet Query

```php
// Basic
Post::all()                          // Semua
Post::find(1)                        // ID = 1
Post::findOrFail(1)                  // ID = 1 atau 404

// Kondisi
Post::where('status', 'published')->get()
Post::where('views', '>', 100)->get()
Post::whereIn('id', [1, 2, 3])->get()
Post::whereBetween('created_at', [$awal, $akhir])->get()

// Urut
Post::latest()->get()                // Terbaru dulu
Post::oldest()->get()                // Terlama dulu
Post::orderBy('title')->get()        // Abjad A-Z

// Limit
Post::limit(5)->get()                // 5 baris
Post::offset(10)->limit(5)->get()    // Skip 10, ambil 5 (page 3)

// Join
Post::leftJoin('users', 'users.id', '=', 'posts.user_id')
    ->select('posts.title', 'users.nama')
    ->get()

// Eager loading (cegah N+1)
Post::with('user', 'comments')->get()
Post::with('comments.user')->get()   // Nested

// Aggregate
Post::count()                        // Hitung jumlah
Post::sum('views')                   // Total views
Post::avg('rating')                  // Rating rata-rata
Post::max('created_at')              // Terbaru

// CRUD
Post::create([...])                  // Buat baru
$post->update([...])                 // Ubah
$post->delete()                      // Hapus

// Cek
if ($post->exists()) { ... }
if ($post->was Changed()) { ... }
```

---

## Migrasi untuk Contoh

```sql
-- Tabel users
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at DATETIME, updated_at DATETIME
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel kategori
CREATE TABLE kategori (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    deskripsi TEXT,
    created_at DATETIME, updated_at DATETIME
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel produk
CREATE TABLE produk (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kategori_id INT UNSIGNED NOT NULL,
    nama VARCHAR(100) NOT NULL,
    harga DECIMAL(10,2) NOT NULL,
    stok INT NOT NULL DEFAULT 0,
    terjual INT NOT NULL DEFAULT 0,
    created_at DATETIME, updated_at DATETIME,
    FOREIGN KEY (kategori_id) REFERENCES kategori(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel pesanan
CREATE TABLE pesanan (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    total_harga DECIMAL(10,2) NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'pending',
    catatan TEXT,
    created_at DATETIME, updated_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

