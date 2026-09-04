<?php

namespace Sakuci;

use PDO;
use PDOException;

class SeedData
{
    public static function seed(): array
    {
        try {
            $pdo = Database::getConnection();
            $driver = Database::getActiveDriver();
            if ($driver !== 'mysql') {
                $pdo->beginTransaction();
            }

            if ($driver === 'mysql') {
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
                $pdo->exec("DROP TABLE IF EXISTS `transaksi`;");
                $pdo->exec("DROP TABLE IF EXISTS `produk`;");
                $pdo->exec("DROP TABLE IF EXISTS `kategori`;");
                $pdo->exec("DROP TABLE IF EXISTS `mahasiswa`;");

                $pdo->exec("
                    CREATE TABLE `kategori` (
                        `id` INT AUTO_INCREMENT PRIMARY KEY,
                        `nama_kategori` VARCHAR(100) NOT NULL
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                ");

                $pdo->exec("
                    CREATE TABLE `produk` (
                        `id` INT AUTO_INCREMENT PRIMARY KEY,
                        `kategori_id` INT NULL,
                        `nama_produk` VARCHAR(150) NOT NULL,
                        `harga` DECIMAL(12,2) NOT NULL,
                        `stok` INT DEFAULT 0,
                        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        CONSTRAINT `fk_produk_kategori` FOREIGN KEY (`kategori_id`) REFERENCES `kategori` (`id`) ON DELETE SET NULL
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                ");

                $pdo->exec("
                    CREATE TABLE `mahasiswa` (
                        `id` INT AUTO_INCREMENT PRIMARY KEY,
                        `nim` VARCHAR(20) NOT NULL UNIQUE,
                        `nama` VARCHAR(100) NOT NULL,
                        `jurusan` VARCHAR(100) NOT NULL,
                        `email` VARCHAR(100) NOT NULL,
                        `ipk` DECIMAL(3,2) DEFAULT 0.00,
                        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                ");

                $pdo->exec("
                    CREATE TABLE `transaksi` (
                        `id` INT AUTO_INCREMENT PRIMARY KEY,
                        `produk_id` INT NULL,
                        `jumlah` INT NOT NULL,
                        `total_harga` DECIMAL(12,2) NOT NULL,
                        `tanggal` DATETIME DEFAULT CURRENT_TIMESTAMP,
                        CONSTRAINT `fk_transaksi_produk` FOREIGN KEY (`produk_id`) REFERENCES `produk` (`id`) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                ");

                $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
            } else {
                $pdo->exec("PRAGMA foreign_keys = OFF;");
                $pdo->exec("DROP TABLE IF EXISTS \"transaksi\";");
                $pdo->exec("DROP TABLE IF EXISTS \"produk\";");
                $pdo->exec("DROP TABLE IF EXISTS \"kategori\";");
                $pdo->exec("DROP TABLE IF EXISTS \"mahasiswa\";");

                $pdo->exec("
                    CREATE TABLE \"mahasiswa\" (
                        \"id\" INTEGER PRIMARY KEY AUTOINCREMENT,
                        \"nim\" TEXT NOT NULL UNIQUE,
                        \"nama\" TEXT NOT NULL,
                        \"jurusan\" TEXT NOT NULL,
                        \"email\" TEXT NOT NULL,
                        \"ipk\" REAL DEFAULT 0.00,
                        \"created_at\" DATETIME DEFAULT CURRENT_TIMESTAMP,
                        \"updated_at\" DATETIME DEFAULT CURRENT_TIMESTAMP
                    );
                ");

                $pdo->exec("
                    CREATE TABLE \"kategori\" (
                        \"id\" INTEGER PRIMARY KEY AUTOINCREMENT,
                        \"nama_kategori\" TEXT NOT NULL
                    );
                ");

                $pdo->exec("
                    CREATE TABLE \"produk\" (
                        \"id\" INTEGER PRIMARY KEY AUTOINCREMENT,
                        \"kategori_id\" INTEGER,
                        \"nama_produk\" TEXT NOT NULL,
                        \"harga\" REAL NOT NULL,
                        \"stok\" INTEGER DEFAULT 0,
                        \"created_at\" DATETIME DEFAULT CURRENT_TIMESTAMP,
                        \"updated_at\" DATETIME DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (\"kategori_id\") REFERENCES \"kategori\"(\"id\") ON DELETE SET NULL
                    );
                ");

                $pdo->exec("
                    CREATE TABLE \"transaksi\" (
                        \"id\" INTEGER PRIMARY KEY AUTOINCREMENT,
                        \"produk_id\" INTEGER,
                        \"jumlah\" INTEGER NOT NULL,
                        \"total_harga\" REAL NOT NULL,
                        \"tanggal\" DATETIME DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (\"produk_id\") REFERENCES \"produk\"(\"id\") ON DELETE CASCADE
                    );
                ");

                $pdo->exec("PRAGMA foreign_keys = ON;");
            }

            // Insert Mahasiswa
            $stmtMhs = $pdo->prepare("INSERT INTO mahasiswa (nim, nama, jurusan, email, ipk) VALUES (?, ?, ?, ?, ?)");
            $mahasiswaData = [
                ['2024001', 'Budi Santoso', 'Teknik Informatika', 'budi.santoso@kampus.ac.id', 3.85],
                ['2024002', 'Siti Rahmawati', 'Sistem Informasi', 'siti.rahma@kampus.ac.id', 3.92],
                ['2024003', 'Ahmad Fadillah', 'Teknik Komputer', 'ahmad.f@kampus.ac.id', 3.45],
                ['2024004', 'Dewi Lestari', 'Teknik Informatika', 'dewi.lestari@kampus.ac.id', 3.78],
                ['2024005', 'Rizky Pratama', 'Manajemen Informatika', 'rizky.p@kampus.ac.id', 3.20],
                ['2024006', 'Anisa Putri', 'Sistem Informasi', 'anisa.p@kampus.ac.id', 3.65],
                ['2024007', 'Doni Kurniawan', 'Teknik Informatika', 'doni.k@kampus.ac.id', 3.50],
                ['2024008', 'Mega Utami', 'Teknik Komputer', 'mega.u@kampus.ac.id', 3.88],
            ];
            foreach ($mahasiswaData as $row) {
                $stmtMhs->execute($row);
            }

            // Insert Kategori
            $stmtKat = $pdo->prepare("INSERT INTO kategori (nama_kategori) VALUES (?)");
            $kategoriData = ['Laptop & Komputer', 'Aksesoris IT', 'Networking', 'Buku & Tutorial'];
            foreach ($kategoriData as $kat) {
                $stmtKat->execute([$kat]);
            }

            // Insert Produk
            $stmtProd = $pdo->prepare("INSERT INTO produk (kategori_id, nama_produk, harga, stok) VALUES (?, ?, ?, ?)");
            $produkData = [
                [1, 'Laptop ThinkPad T14 Gen 3', 14500000, 5],
                [1, 'Monitor Dell 24 Inch IPS 75Hz', 2200000, 12],
                [2, 'Mouse Logitech MX Master 3S', 1450000, 8],
                [2, 'Mechanical Keyboard Keychron K2', 1250000, 15],
                [3, 'Router Mikrotik RB750Gr3', 850000, 20],
                [3, 'Kabel LAN Cat6 50m Belden', 350000, 10],
                [4, 'Buku Mahir Pemrograman PHP & MySQL', 125000, 25],
                [4, 'Buku Arsitektur Modern Berbasis REST API', 140000, 18],
            ];
            foreach ($produkData as $prod) {
                $stmtProd->execute($prod);
            }

            // Insert Transaksi
            $stmtTrx = $pdo->prepare("INSERT INTO transaksi (produk_id, jumlah, total_harga) VALUES (?, ?, ?)");
            $transaksiData = [
                [1, 1, 14500000],
                [3, 2, 2900000],
                [4, 1, 1250000],
                [7, 3, 375000],
                [5, 2, 1700000],
            ];
            foreach ($transaksiData as $trx) {
                $stmtTrx->execute($trx);
            }

            if ($pdo->inTransaction()) {
                $pdo->commit();
            }

            // Sync ke tabel latihan user yang sedang aktif jika ada
            if ($driver === 'mysql') {
                try {
                    $uTables = $pdo->query("SHOW TABLES LIKE 'u%\\_mahasiswa'")->fetchAll(PDO::FETCH_COLUMN);
                    foreach ($uTables as $uMhs) {
                        if (preg_match('/^(u\d+)_mahasiswa$/', $uMhs, $m)) {
                            $prefix = $m[1] . '_';
                            foreach (['mahasiswa', 'kategori', 'produk', 'transaksi'] as $tbl) {
                                $uTbl = $prefix . $tbl;
                                $pdo->exec("CREATE TABLE IF NOT EXISTS `{$uTbl}` LIKE `{$tbl}`;");
                                $pdo->exec("TRUNCATE TABLE `{$uTbl}`;");
                                $pdo->exec("INSERT INTO `{$uTbl}` SELECT * FROM `{$tbl}`;");
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    // non-fatal
                }
            }

            return [
                'success' => true,
                'message' => 'Database "latihan" (' . strtoupper($driver) . ') berhasil diisi data contoh!',
                'tables' => ['mahasiswa', 'kategori', 'produk', 'transaksi']
            ];

        } catch (PDOException $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return [
                'success' => false,
                'message' => 'Gagal seeding database: ' . $e->getMessage()
            ];
        }
    }
}
