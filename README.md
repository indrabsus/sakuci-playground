# 🐘 Sakuci PHP & MySQL Ground

**Sakuci PHP & MySQL Ground** adalah aplikasi playground berbasis web interaktif yang dirancang khusus untuk pembelajaran pemrograman PHP dan basis data MySQL secara praktis, cepat, dan responsif langsung dari peramban (browser).

Dilengkapi dengan code editor profesional (Monaco Editor - mesin yang sama dengan VS Code), penjelajah berkas & direktori bertingkat (*File Explorer Sidebar*), mesin simulasi MySQL 8.0 zero-config, penjelajah database visual, serta konsol SQL interaktif.

---

## ✨ Fitur Utama

1. **📁 Penjelajah File & Folder (Gaya VS Code)**:
   - Sidebar penjelajah berkas di sisi kiri editor yang mendukung pembuatan file PHP dan folder bertingkat (`models`, `config`, `classes`, dll).
   - Eksekusi modular di backend: `require_once __DIR__ . '/models/User.php';` berjalan mulus.
   - Bilah tab editor atas dengan tombol tutup `✕` dan indikator file aktif.
   - Default bersih: Hanya `index.php` (dalam keadaan kosong), memberikan kebebasan penuh bagi pengguna untuk menulis kode dari nol.

2. **🗄️ Mesin Simulasi MySQL 8.0 (Zero-Config)**:
   - Pembelajaran MySQL tanpa perlu instalasi atau setup server MySQL di PC.
   - Menggunakan kredensial standar pembelajaran:
     - **Host**: `localhost` (127.0.0.1)
     - **Port**: `3306`
     - **User**: `root`
     - **Password**: `""` (kosong)
     - **Database**: `latihan`
   - Mendukung penuh sintaks standar MySQL di PHP:
     - **MySQLi Prosedural**: `mysqli_connect()`, `mysqli_query()`, `mysqli_fetch_assoc()`, `mysqli_fetch_array()`, dll.
     - **MySQLi OOP**: `new mysqli()`, `$conn->query()`, `$result->fetch_assoc()`, dll.
     - **PDO MySQL**: `new PDO("mysql:host=localhost;dbname=latihan", "root", "")`.
     - Variabel global `$koneksi`, `$conn`, `$pdo`, dan `$db` langsung siap pakai.
   - Penjelajah tabel visual dan konsol SQL langsung untuk `SELECT`, `INSERT`, `UPDATE`, `CREATE TABLE`, dll.
   - Tombol **"Seed Data"**: Sekali klik untuk membuat tabel sampel `mahasiswa`, `kategori`, `produk`, dan `transaksi`.

3. **📱 Tampilan Responsif & Mobile-Friendly**:
   - Khusus layar ponsel/smartphone, navigasi dioptimalkan menjadi bilah tab bawah (*Bottom Navigation Bar*):
     - **[📁 Berkas]**: Tampilan penjelajah file & folder layar penuh.
     - **[📝 Editor]**: Monaco Editor layar penuh (100% lebar layar).
     - **[💻 Konsol]**: Output terminal standar (`stdout` & `stderr`).
     - **[🌐 HTML]**: Render live preview HTML hasil `echo`.
     - **[🗄️ Database]**: Manajemen tabel dan query SQL interaktif.

4. **⚡ Eksekutor PHP Terisolasi (Sandbox)**:
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
│   │   ├── app.js           # Kontroller utama aplikasi & state management
│   │   ├── db-manager.js    # Manajemen tabel dan konsol SQL
│   │   └── editor.js        # Integrasi Monaco Editor
│   └── index.html           # Single Page Application UI
├── server/
│   ├── ApiHandler.php       # REST API endpoints (/api/*)
│   ├── Database.php         # Database helper & normalizer query MySQL
│   ├── Runner.php           # Sandbox process executor & MySQL simulation
│   └── SeedData.php         # Seeder tabel sampel (mahasiswa, produk, dll)
├── router.php               # PHP built-in web server router
├── start.bat                # Windows 1-click launcher
└── README.md
```
