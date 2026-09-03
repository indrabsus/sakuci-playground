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
            $pdo->beginTransaction();

            $pdo->exec("DROP TABLE IF EXISTS \"transaksi\";");
            $pdo->exec("DROP TABLE IF EXISTS \"produk\";");
            $pdo->exec("DROP TABLE IF EXISTS \"kategori\";");
            $pdo->exec("DROP TABLE IF EXISTS \"mahasiswa\";");

            // Table mahasiswa
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

            // Table kategori
            $pdo->exec("
                CREATE TABLE \"kategori\" (
                    \"id\" INTEGER PRIMARY KEY AUTOINCREMENT,
                    \"nama_kategori\" TEXT NOT NULL
                );
            ");

            // Table produk
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

            // Table transaksi
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

            // Insert Mahasiswa
            $stmtMhs = $pdo->prepare("INSERT INTO \"mahasiswa\" (nim, nama, jurusan, email, ipk) VALUES (?, ?, ?, ?, ?)");
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
            $stmtKat = $pdo->prepare("INSERT INTO \"kategori\" (nama_kategori) VALUES (?)");
            $kategoriData = ['Laptop & Komputer', 'Aksesoris IT', 'Networking', 'Buku & Tutorial'];
            foreach ($kategoriData as $kat) {
                $stmtKat->execute([$kat]);
            }

            // Insert Produk
            $stmtProd = $pdo->prepare("INSERT INTO \"produk\" (kategori_id, nama_produk, harga, stok) VALUES (?, ?, ?, ?)");
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
            $stmtTrx = $pdo->prepare("INSERT INTO \"transaksi\" (produk_id, jumlah, total_harga) VALUES (?, ?, ?)");
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

            $pdo->commit();

            return [
                'success' => true,
                'message' => 'Database "latihan" (Simulasi MySQL 8.0) berhasil diisi data contoh!',
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
