# Panduan Lengkap Deploy Sakuci Playground ke aaPanel 🚀

Panduan ini menuntun Anda mengunggah dan menjalankan **Sakuci PHP & MySQL Ground** di server VPS / Hosting menggunakan **aaPanel**.

---

## Persyaratan Server (aaPanel)
1. **Web Server**: **Nginx** ATAU **Apache**
2. **PHP**: **8.1**, **8.2**, atau **8.3**
3. **MySQL / MariaDB**: (5.7 / 8.0)
4. Ekstensi PHP wajib aktif di aaPanel:
   - `pdo_mysql`
   - `pdo_sqlite` (opsional untuk fallback)
   - `fileinfo`
   - `json`
   - `mbstring`

---

## Langkah-Langkah Deploy

### 1. Buat Website Baru di aaPanel
1. Masuk ke dashboard aaPanel -> menu **Website** -> klik **Add site**.
2. Isi nama domain Anda (misal `playground.domainanda.com`).
3. Pilih versi PHP: **PHP-8.1**, **PHP-8.2**, atau **PHP-8.3**.
4. Database: Anda bisa langsung pilih **MySQL** untuk otomatis dibuatkan database & user, atau buat manual nanti.
5. Klik **Submit**.

---

### 2. Upload File Proyek & Atur Permission
1. Buka menu **Files** di aaPanel, navigasikan ke folder website:
   `/www/wwwroot/domainanda.com/`
2. Hapus file bawaan aaPanel jika ada (`index.html`, `404.html`).
3. Upload seluruh file proyek ini (bisa zip lalu ekstrak di aaPanel).
4. **Penting (Permission):** 
   - Pilih semua file di `/www/wwwroot/domainanda.com/` -> klik **Permission**.
   - Set Owner: `www`, Group: `www`, Permission: `755` (dan centang *Apply to subdirectories*).

---

### 3. Konfigurasi Database MySQL
1. Buka menu **Databases** di aaPanel:
   - Buat database baru jika belum ada (misal `sakuci_ground`).
   - Catat: **Database Name**, **Username**, dan **Password**.
2. Buka file `.env` di root direktori `/www/wwwroot/domainanda.com/.env`:
   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=http://domainanda.com

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=sakuci_ground
   DB_USERNAME=user_database_anda
   DB_PASSWORD=password_database_anda
   ```
3. Simpan file `.env`.
4. *Catatan:* Skema tabel (`users`, `user_sessions`, `user_files`, `mahasiswa`, dll) **otomatis dibuat oleh sistem** saat website pertama kali dibuka. Jika ingin import manual lewat phpMyAdmin, file SQL tersedia di `data/schema.sql`.

---

### 4. Konfigurasi URL Rewrite (Sesuai Web Server Anda)

> [!WARNING]
> Jika Anda mendapatkan error `500 Internal Server Error (Please contact the server administrator at webmaster@example.com)`, hal ini terjadi karena server Anda menggunakan **Apache** dan Anda menempelkan kode konfigurasi Nginx ke dalamnya! Gunakan pilihan sesuai webserver yang terinstall di aaPanel Anda:

#### Pilihan A: Jika Menggunakan APACHE (LAMP)
Jika web server Anda Apache, **JANGAN** menempelkan script `location { ... }` Nginx.
Cukup gunakan file [`.htaccess`](.htaccess) yang sudah disertakan:
1. Buka menu **Website** di aaPanel -> klik domain Anda -> menu **URL rewrite**.
2. Pilih template rewrite atau tempel aturan Apache berikut:
   ```apache
   <IfModule mod_rewrite.c>
       RewriteEngine On
       RewriteBase /

       # Keamanan
       RewriteRule ^(\.env|\.git|.*\.sqlite|.*\.sql) - [F,L]

       # File statis (CSS, JS, Fonts)
       RewriteCond %{DOCUMENT_ROOT}/public%{REQUEST_URI} -f
       RewriteRule ^(.*)$ public/$1 [L]

       # Request ke router / index
       RewriteCond %{REQUEST_FILENAME} !-f
       RewriteCond %{REQUEST_FILENAME} !-d
       RewriteRule ^ index.php [QSA,L]
   </IfModule>
   DirectoryIndex index.php index.html
   ```
3. Klik **Save**.

#### Pilihan B: Jika Menggunakan NGINX (LNMP)
1. Di menu **Website** aaPanel -> klik domain Anda -> buka menu **URL rewrite**.
2. Tempel aturan Nginx dari [nginx.aapanel.conf](nginx.aapanel.conf):
   ```nginx
   location / {
       try_files $uri $uri/ /index.php?$query_string;
   }

   location ~ /\.(env|git|ht) {
       deny all;
       return 404;
   }

   location ~* \.(sqlite|sql|log)$ {
       deny all;
       return 404;
   }
   ```
3. Klik **Save**.

---

### 5. Pengaturan Eksekusi PHP CLI (Untuk Runner)
Sakuci Playground mengeksekusi kode user via `proc_open` di PHP CLI.
Pastikan di aaPanel menu **App Store** -> **PHP-8.x** -> **Disabled functions**:
- Hapus `proc_open`, `proc_close`, `proc_get_status` dari daftar *Disabled functions* jika ada, agar runner eksekusi kode PHP playground dapat berjalan lancar.

---

### 6. Selesai! 🎉
Buka domain Anda di browser:
1. Klik tombol **Masuk / Daftar** di kanan atas.
2. Daftarkan akun baru (nama, username, email, password).
3. Kode, file, dan sesi database latihan Anda kini aman tersimpan di MySQL aaPanel dan terisolasi per pengguna!
