# 🐘 Sakuci PHP & MySQL Ground

**Sakuci PHP & MySQL Ground** adalah aplikasi playground berbasis web interaktif yang dirancang khusus untuk pembelajaran pemrograman PHP dan basis data MySQL secara praktis, cepat, dan responsif langsung dari peramban (browser).

Kini dilengkapi dengan **Mode Ganda**:
1. 🐘 **Mode PHP Murni (Native)**: Kanvas bersih, default `index.php` kosong, cocok untuk belajar dasar PHP prosedural, OOP, dan koneksi langsung MySQL.
2. ⚡ **Mode Sakuci Framework ([indrabsus/sakuci-framework](https://github.com/indrabsus/sakuci-framework.git))**: Kerangka kerja PHP ringan bergaya Laravel tanpa Composer. Mendukung Route (`routes/web.php`), Controller, Model ORM, dan View Blade-like (`.sakuci.php`), lengkap dengan bilah URL address bar simulator.

---

## ✨ Fitur Utama

1. **🔀 Mode Ganda Playground (PHP Murni vs Sakuci Framework)**:
   - Pengalih mode instan di header atas: `[ 🐘 PHP Murni ]` dan `[ ⚡ Sakuci Framework ]`.
   - **Isolasi Workspace**: Berkas dan perubahan pada masing-masing mode tersimpan rapi secara independen di `localStorage` peramban. Berpindah mode tidak akan menimpa file Anda.
   - **Bilah Alamat URL Simulator**: Pada mode Sakuci Framework, Anda dapat menguji berbagai endpoint rute (misal: `/`, `/halo`, `/mahasiswa`) secara interaktif.

2. **📁 Penjelajah File & Folder (Gaya VS Code)**:
   - Sidebar penjelajah berkas di sisi kiri editor yang mendukung pembuatan file PHP dan folder bertingkat (`models`, `config`, `classes`, dll).
   - Eksekusi modular di backend: `require_once __DIR__ . '/models/User.php';` berjalan mulus.
   - Bilah tab editor atas dengan tombol tutup `✕` dan indikator file aktif.

3. **🗄️ Mesin Simulasi MySQL 8.0 (Zero-Config)**:
   - Pembelajaran MySQL tanpa perlu instalasi atau menyalakan service MySQL di PC.
   - Kredensial standar pembelajaran:
     - **Host**: `localhost` (127.0.0.1)
     - **Port**: `3306`
     - **User**: `root`
     - **Password**: `""` (kosong)
     - **Database**: `latihan`
   - Mendukung penuh sintaks standar MySQL di PHP:
     - **MySQLi Prosedural**: `mysqli_connect()`, `mysqli_query()`, `mysqli_fetch_assoc()`, `mysqli_fetch_array()`, dll.
     - **MySQLi OOP**: `new mysqli()`, `$conn->query()`, `$result->fetch_assoc()`, dll.
     - **PDO MySQL**: `new PDO("mysql:host=localhost;dbname=latihan", "root", "")`.
     - **Sakuci Model (ORM)**: `Mahasiswa::all()`, `Model::where()`, dll.
   - Penjelajah tabel visual dan konsol SQL langsung untuk `SELECT`, `INSERT`, `UPDATE`, `CREATE TABLE`, dll.
   - Tombol **"Seed Data"**: Sekali klik untuk mengisi ulang tabel `mahasiswa`, `kategori`, `produk`, dan `transaksi`.

4. **📱 Tampilan Responsif & Mobile-Friendly**:
   - Khusus layar ponsel/smartphone, navigasi dioptimalkan menjadi bilah tab bawah (*Bottom Navigation Bar*):
     - **[📁 Berkas]**: Penjelajah file & folder layar penuh.
     - **[📝 Editor]**: Monaco Editor layar penuh (100% lebar layar).
     - **[💻 Konsol]**: Output terminal standar (`stdout` & `stderr`).
     - **[🌐 HTML]**: Render live preview HTML hasil rute/view MVC.
     - **[🗄️ Database]**: Manajemen tabel dan query SQL interaktif.

5. **⚡ Eksekutor PHP Terisolasi (Sandbox)**:
   - Menjalankan kode PHP 8.3 dengan proteksi timeout (5 detik) untuk mencegah infinite loop.
   - Pengukuran performa: Waktu eksekusi (milidetik) dan exit code.

---

## 🚀 Cara Menjalankan Aplikasi

### Metode 1: Sekali Klik (Windows)
Cukup klik ganda (double-click) file:
```
start.bat
```
Server web PHP akan otomatis berjalan di `http://localhost:8000` dan browser akan langsung terbuka.

### Metode 2: Menggunakan Terminal / Command Line
Jalankan perintah berikut di direktori proyek:
```bash
php -d extension=pdo_sqlite -d extension=sqlite3 -S 127.0.0.1:8000 router.php
```
Kemudian buka browser Anda dan kunjungi:
[http://localhost:8000](http://localhost:8000)

---

## 📁 Struktur Direktori Proyek

```
sakuci-php-ground/
├── data/
│   ├── latihan.sqlite       # Database penyimpanan lokal (Simulasi MySQL)
│   └── temp/                # Direktori isolasi sementara eksekusi kode
├── public/
│   ├── css/
│   │   └── style.css        # Custom styles & tata letak responsif
│   ├── js/
│   │   ├── app.js           # Kontroller utama aplikasi, mode switcher, & state
│   │   ├── db-manager.js    # Manajemen tabel dan konsol SQL
│   │   └── editor.js        # Integrasi Monaco Editor
│   └── index.html           # Single Page Application UI
├── server/
│   ├── framework/
│   │   └── sakuci/          # Core engine Sakuci Framework MVC
│   ├── ApiHandler.php       # REST API endpoints (/api/*)
│   ├── Database.php         # Database helper & normalizer query MySQL
│   ├── Runner.php           # Sandbox process executor (Native & Framework)
│   └── SeedData.php         # Seeder tabel sampel (mahasiswa, produk, dll)
├── router.php               # PHP built-in web server router
├── start.bat                # Windows 1-click launcher
└── README.md
```
