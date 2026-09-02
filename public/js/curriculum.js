/**
 * Sakuci PHP & MySQL Ground - Kurikulum & Dokumentasi Interaktif
 * Berisi modul pembelajaran terstruktur: Dasar & Procedural, OOP, Database & CRUD, serta Studi Kasus.
 */

const CURRICULUM = [
    // ==========================================
    // KATEGORI: PHP DASAR & PROSEDURAL
    // ==========================================
    {
        id: 'proc-intro',
        category: 'procedural',
        categoryName: 'PHP Dasar & Prosedural',
        title: '1. Sintaks Dasar & Output',
        difficulty: 'Pemula',
        description: 'Mengenal tag pembuka PHP, fungsi pencetakan output (echo, print), format output, dan komentar.',
        keyPoints: [
            'Kode PHP diawali dengan tag `<?php`.',
            '`echo` digunakan untuk mencetak string atau variabel ke output.',
            '`var_dump()` dan `print_r()` sangat berguna untuk debugging tipe data dan struktur array.',
            'Gunakan `//` atau `/* */` untuk menulis catatan atau komentar.'
        ],
        code: `<?php
// Contoh 1: Mencetak teks sederhana
echo "Halo, Selamat datang di Sakuci PHP Playground!\\n";
echo "Mari belajar pemrograman PHP & MySQL dengan menyenangkan.\\n\\n";

// Contoh 2: Perbedaan echo, print, dan var_dump
\$nama = "Budi Santoso";
\$umur = 20;
\$tinggi = 172.5;
\$isMahasiswa = true;

echo "Nama   : " . \$nama . "\\n";
print "Umur   : " . \$umur . " tahun\\n";

echo "\\n--- Debugging dengan var_dump() ---\\n";
var_dump(\$nama);
var_dump(\$umur);
var_dump(\$tinggi);
var_dump(\$isMahasiswa);
`
    },
    {
        id: 'proc-variables',
        category: 'procedural',
        categoryName: 'PHP Dasar & Prosedural',
        title: '2. Variabel & Tipe Data',
        difficulty: 'Pemula',
        description: 'Memahami deklarasi variabel dengan simbol $, tipe data skalar, type casting, dan konstanta (define/const).',
        keyPoints: [
            'Variabel PHP selalu diawali simbol `$` dan bersifat case-sensitive.',
            'PHP adalah bahasa loosely typed (tipe data ditentukan otomatis).',
            'Tipe data dasar: String, Integer, Float, Boolean, Array, Object, NULL.',
            'Konstanta dibuat dengan fungsi `define()` atau kata kunci `const`.'
        ],
        code: `<?php
// Konstanta
define('NAMA_KAMPUS', 'Universitas Teknologi Sakuci');
const VERSI_APLIKASI = '1.0.0';

// Variabel dengan berbagai tipe data
\$teks = "Belajar PHP Modern";       // String
\$jumlahSiswa = 35;                 // Integer
\$rataRataNilai = 88.75;             // Float
\$lulus = true;                     // Boolean
\$hobi = ["Coding", "Membaca"];     // Array
\$catatan = null;                   // NULL

echo "=== " . NAMA_KAMPUS . " (v" . VERSI_APLIKASI . ") ===\\n\\n";
echo "Topik Belajar : \$teks\\n";
echo "Jumlah Siswa  : \$jumlahSiswa orang\\n";
echo "Rata-rata     : \$rataRataNilai\\n";
echo "Status Lulus  : " . (\$lulus ? "LULUS" : "TIDAK LULUS") . "\\n";
echo "Hobi Pertama  : " . \$hobi[0] . "\\n";

// Type Casting (Konversi Tipe Data)
\$angkaString = "150";
\$total = (int)\$angkaString + 50;
echo "Hasil konversi & penjumlahan: \$total\\n";
`
    },
    {
        id: 'proc-operators',
        category: 'procedural',
        categoryName: 'PHP Dasar & Prosedural',
        title: '3. Operator & Null Coalescing',
        difficulty: 'Pemula',
        description: 'Operator aritmatika, perbandingan, logika, ternary operator, dan null coalescing operator (??).',
        keyPoints: [
            'Aritmatika: `+`, `-`, `*`, `/`, `%` (modulus), `**` (pangkat).',
            'Perbandingan: `==` (nilai sama), `===` (nilai & tipe data sama / strict), `!=`, `<`, `>`.',
            'Logika: `&&` (AND), `||` (OR), `!` (NOT).',
            'Null Coalescing `??`: Memberikan nilai default jika variabel null atau belum diset.'
        ],
        code: `<?php
\$a = 15;
\$b = 4;

echo "=== Operator Aritmatika ===\\n";
echo "\$a + \$b = " . (\$a + \$b) . "\\n";
echo "\$a - \$b = " . (\$a - \$b) . "\\n";
echo "\$a * \$b = " . (\$a * \$b) . "\\n";
echo "\$a / \$b = " . (\$a / \$b) . "\\n";
echo "\$a % \$b (Sisa Bagi) = " . (\$a % \$b) . "\\n";
echo "\$a ** \$b (Pangkat) = " . (\$a ** \$b) . "\\n\\n";

echo "=== Strict Comparison (=== vs ==) ===\\n";
\$x = 100;
\$y = "100";
echo "\$x == \$y  : " . (\$x == \$y ? "True (nilai sama)" : "False") . "\\n";
echo "\$x === \$y : " . (\$x === \$y ? "True" : "False (tipe data beda: int vs string)") . "\\n\\n";

echo "=== Null Coalescing Operator (??) ===\\n";
// Mengambil input atau menggunakan fallback default
\$inputPengguna = null;
\$namaTampil = \$inputPengguna ?? "Tamu Anonim";
echo "Selamat datang, " . \$namaTampil . "!\\n";
`
    },
    {
        id: 'proc-conditionals',
        category: 'procedural',
        categoryName: 'PHP Dasar & Prosedural',
        title: '4. Percabangan & Match Expression',
        difficulty: 'Pemula',
        description: 'Struktur kontrol alur: if, elseif, else, switch-case, dan fitur modern PHP 8 yaitu match expression.',
        keyPoints: [
            '`if-elseif-else` mengevaluasi kondisi boolean secara berurutan.',
            '`switch-case` cocok untuk memeriksa banyak kemungkinan nilai dari satu variabel.',
            '`match` (PHP 8+) lebih ringkas, mengembalikan nilai langsung, dan melakukan perbandingan ketat (===).'
        ],
        code: `<?php
\$nilai = 85;

echo "=== 1. Percabangan IF-ELSEIF-ELSE ===\\n";
if (\$nilai >= 90) {
    \$grade = "A (Istimewa)";
} elseif (\$nilai >= 80) {
    \$grade = "B (Sangat Baik)";
} elseif (\$nilai >= 70) {
    \$grade = "C (Cukup)";
} else {
    \$grade = "D (Perlu Perbaikan)";
}
echo "Nilai: \$nilai | Grade: \$grade\\n\\n";

echo "=== 2. Match Expression (PHP 8+) ===\\n";
\$statusOrder = "shipped";

\$pesanStatus = match (\$statusOrder) {
    "pending"   => "Pesanan Anda sedang menunggu pembayaran.",
    "processed" => "Pesanan sedang dikemas oleh penjual.",
    "shipped"   => "Pesanan dalam perjalanan kurir!",
    "delivered" => "Pesanan telah sampai di tujuan.",
    default     => "Status pesanan tidak dikenali."
};

echo "Status: \$statusOrder\\nInfo  : \$pesanStatus\\n";
`
    },
    {
        id: 'proc-loops',
        category: 'procedural',
        categoryName: 'PHP Dasar & Prosedural',
        title: '5. Perulangan (For, While, Foreach)',
        difficulty: 'Pemula',
        description: 'Teknik iterasi data menggunakan for, while, do-while, serta foreach untuk array asosiatif.',
        keyPoints: [
            '`for` digunakan saat jumlah iterasi sudah diketahui secara pasti.',
            '`while` mengulang selama kondisi bernilai true.',
            '`foreach` adalah cara paling bersih dan umum di PHP untuk membaca array.'
        ],
        code: `<?php
echo "=== 1. Perulangan FOR (Hitung 1 s.d 5) ===\\n";
for (\$i = 1; \$i <= 5; \$i++) {
    echo "Langkah ke-\$i\\n";
}

echo "\\n=== 2. Perulangan WHILE ===\\n";
\$hitungMundur = 3;
while (\$hitungMundur > 0) {
    echo "Peluncuran dalam \$hitungMundur...\\n";
    \$hitungMundur--;
}
echo "Roket Meluncur! 🚀\\n";

echo "\\n=== 3. Perulangan FOREACH (Array Asosiatif) ===\\n";
\$peserta = [
    "P01" => ["nama" => "Rizky", "skor" => 95],
    "P02" => ["nama" => "Anisa", "skor" => 88],
    "P03" => ["nama" => "Deni",  "skor" => 92],
];

foreach (\$peserta as \$kode => \$info) {
    echo "[\$kode] " . str_pad(\$info['nama'], 10) . " : Skor " . \$info['skor'] . "\\n";
}
`
    },
    {
        id: 'proc-functions',
        category: 'procedural',
        categoryName: 'PHP Dasar & Prosedural',
        title: '6. Fungsi & Type Hinting',
        difficulty: 'Menengah',
        description: 'Membuat fungsi kustom, default parameter, type hinting untuk parameter dan return value, serta arrow function.',
        keyPoints: [
            'Fungsi mengelompokkan kode agar reusable dan terorganisir.',
            'Type hinting (misal `int`, `string`, `float`) mencegah bug tipe data.',
            'Arrow function `fn(\$x) => \$x * 2` memberikan sintaks satu baris yang elegan.'
        ],
        code: `<?php
// Fungsi dengan Type Hinting dan Return Type
function hitungDiskon(float \$hargaAwal, float \$persenDiskon = 10.0): float {
    \$potongan = \$hargaAwal * (\$persenDiskon / 100);
    return \$hargaAwal - \$potongan;
}

// Fungsi dengan format teks rapi
function cetakStruk(string \$item, float \$harga, float \$diskon = 0): void {
    \$hargaAkhir = hitungDiskon(\$harga, \$diskon);
    echo "Item         : \$item\\n";
    echo "Harga Normal : Rp " . number_format(\$harga, 0, ',', '.') . "\\n";
    echo "Diskon       : " . \$diskon . "%\\n";
    echo "Bayar        : Rp " . number_format(\$hargaAkhir, 0, ',', '.') . "\\n";
    echo str_repeat("-", 30) . "\\n";
}

echo "=== CONTOH FUNGSI DI PHP ===\\n";
cetakStruk("Keyboard Mekanikal", 450000, 15);
cetakStruk("Mouse Wireless", 180000); // Diskon default 10%

// Arrow Function (PHP 7.4+)
\$tambahPajak = fn(float \$subtotal, float \$ppn = 0.11): float => \$subtotal + (\$subtotal * \$ppn);
echo "Total belanja 500.000 + PPN 11%: Rp " . number_format(\$tambahPajak(500000), 0, ',', '.') . "\\n";
`
    },
    {
        id: 'proc-arrays',
        category: 'procedural',
        categoryName: 'PHP Dasar & Prosedural',
        title: '7. Manipulasi Array Lengkap',
        difficulty: 'Menengah',
        description: 'Fungsi bawaan array PHP yang paling sering digunakan di dunia nyata: array_map, array_filter, array_reduce.',
        keyPoints: [
            '`array_map` mentransformasi setiap elemen array.',
            '`array_filter` menyaring elemen berdasarkan kondisi tertentu.',
            '`array_reduce` menggabungkan semua elemen menjadi satu nilai akhir (misal total).'
        ],
        code: `<?php
\$daftarNilai = [65, 82, 90, 45, 78, 95, 58, 88];

echo "Daftar Nilai Asli: " . implode(", ", \$daftarNilai) . "\\n\\n";

// 1. Filter: Ambil hanya nilai yang lulus (>= 75)
\$nilaiLulus = array_filter(\$daftarNilai, fn(\$n) => \$n >= 75);
echo "Nilai yang Lulus (>= 75):\\n";
print_r(array_values(\$nilaiLulus));

// 2. Map: Konversi nilai angka menjadi status kelulusan
\$statusSiswa = array_map(fn(\$n) => [
    'nilai' => \$n,
    'status' => \$n >= 75 ? 'LULUS' : 'REMIDI'
], \$daftarNilai);

echo "\\nStatus Kelulusan Siswa:\\n";
foreach (\$statusSiswa as \$i => \$s) {
    echo "Siswa " . (\$i + 1) . " [{\$s['nilai']}] : {\$s['status']}\\n";
}

// 3. Reduce: Menghitung total dan rata-rata
\$totalNilai = array_reduce(\$daftarNilai, fn(\$carry, \$item) => \$carry + \$item, 0);
\$rataRata = \$totalNilai / count(\$daftarNilai);

echo "\\nTotal Nilai: \$totalNilai | Rata-rata Kelas: " . round(\$rataRata, 2) . "\\n";
`
    },

    // ==========================================
    // KATEGORI: OBJECT-ORIENTED PROGRAMMING (OOP)
    // ==========================================
    {
        id: 'oop-intro',
        category: 'oop',
        categoryName: 'Pemrograman Berorientasi Objek (OOP)',
        title: '1. Class, Object, Properti & Method',
        difficulty: 'Pemula',
        description: 'Membangun cetak biru (blueprint) Class dan menginstansiasi Object nyata dalam PHP.',
        keyPoints: [
            'Class adalah template/cetak biru yang mendefinisikan atribut dan perilaku.',
            'Object adalah perwujudan konkret dari sebuah Class.',
            'Properti adalah variabel di dalam class, Method adalah fungsi di dalam class.',
            '`$this` merujuk ke instance objek yang sedang aktif.'
        ],
        code: `<?php
class Mobil {
    // Properti
    public string \$merk;
    public string \$warna;
    public int \$kecepatan = 0;

    // Method untuk menambah kecepatan
    public function gas(int \$tambah): void {
        \$this->kecepatan += \$tambah;
        echo "[\$this->merk] Menginjak gas! Kecepatan sekarang: {\$this->kecepatan} km/jam\\n";
    }

    // Method untuk mengerem
    public function rem(int \$kurang): void {
        \$this->kecepatan = max(0, \$this->kecepatan - \$kurang);
        echo "[\$this->merk] Mengerem. Kecepatan sekarang: {\$this->kecepatan} km/jam\\n";
    }

    public function status(): string {
        return "Mobil {\$this->merk} berwarna {\$this->warna} | Kecepatan: {\$this->kecepatan} km/jam";
    }
}

// Menginstansiasi Object Baru
\$avanza = new Mobil();
\$avanza->merk = "Toyota Avanza";
\$avanza->warna = "Hitam";

\$civic = new Mobil();
\$civic->merk = "Honda Civic";
\$civic->warna = "Merah";

echo \$avanza->status() . "\\n";
echo \$civic->status() . "\\n\\n";

// Mengoperasikan objek
\$civic->gas(50);
\$civic->gas(40);
\$civic->rem(30);
`
    },
    {
        id: 'oop-constructor',
        category: 'oop',
        categoryName: 'Pemrograman Berorientasi Objek (OOP)',
        title: '2. Constructor & Destructor',
        difficulty: 'Pemula',
        description: 'Inisialisasi objek otomatis saat dibuat dengan __construct() dan cleanup dengan __destruct().',
        keyPoints: [
            '`__construct()` otomatis dipanggil saat objek dibuat (`new`).',
            'PHP 8 Constructor Property Promotion memudahkan inisialisasi properti langsung di parameter constructor.',
            '`__destruct()` otomatis dieksekusi saat siklus hidup objek berakhir.'
        ],
        code: `<?php
class AkunBank {
    private string \$nomorRekening;
    private string \$pemilik;
    private float \$saldo;

    // Constructor otomatis dipanggil saat 'new AkunBank(...)'
    public function __construct(string \$norek, string \$pemilik, float \$saldoAwal = 0) {
        \$this->nomorRekening = \$norek;
        \$this->pemilik = \$pemilik;
        \$this->saldo = max(0, \$saldoAwal);
        echo "[INFO] Akun Rekening {\$this->nomorRekening} untuk {\$this->pemilik} berhasil dibuka.\\n";
    }

    public function setor(float \$nominal): void {
        if (\$nominal <= 0) {
            echo "Nominal setor harus lebih besar dari 0!\\n";
            return;
        }
        \$this->saldo += \$nominal;
        echo "[SETOR] Berhasil setor Rp " . number_format(\$nominal, 0, ',', '.') . "\\n";
    }

    public function tarik(float \$nominal): bool {
        if (\$nominal > \$this->saldo) {
            echo "[TARIK GAGAL] Saldo tidak mencukupi untuk tarik Rp " . number_format(\$nominal, 0, ',', '.') . "\\n";
            return false;
        }
        \$this->saldo -= \$nominal;
        echo "[TARIK SUKSES] Berhasil tarik Rp " . number_format(\$nominal, 0, ',', '.') . "\\n";
        return true;
    }

    public function getSaldo(): float {
        return \$this->saldo;
    }

    // Destructor
    public function __destruct() {
        echo "[CLEANUP] Sesi akun rekening {\$this->nomorRekening} selesai.\\n";
    }
}

// Simulasi penggunaan
\$nasabah1 = new AkunBank("REK-889901", "Ahmad Fauzi", 500000);
\$nasabah1->setor(250000);
\$nasabah1->tarik(900000); // Gagal karena melebihi saldo
\$nasabah1->tarik(400000); // Berhasil

echo "Saldo Akhir: Rp " . number_format(\$nasabah1->getSaldo(), 0, ',', '.') . "\\n";
`
    },
    {
        id: 'oop-encapsulation',
        category: 'oop',
        categoryName: 'Pemrograman Berorientasi Objek (OOP)',
        title: '3. Enkapsulasi & Access Modifiers',
        difficulty: 'Menengah',
        description: 'Menjaga integritas data dengan hak akses public, protected, dan private serta Getter/Setter.',
        keyPoints: [
            '`public`: Dapat diakses dari mana saja (luar class, dalam class, turunan).',
            '`protected`: Hanya dapat diakses di dalam class itu sendiri dan class turunannya.',
            '`private`: Hanya dapat diakses di dalam class pemilik properti tersebut.',
            'Getter & Setter memberikan kontrol validasi sebelum data diubah.'
        ],
        code: `<?php
class Mahasiswa {
    private string \$nim;
    private string \$nama;
    private float \$ipk;

    public function __construct(string \$nim, string \$nama, float \$ipk) {
        \$this->nim = \$nim;
        \$this->nama = \$nama;
        \$this->setIpk(\$ipk); // Menggunakan setter agar tervalidasi
    }

    // Getter untuk Nama
    public function getNama(): string {
        return \$this->nama;
    }

    // Setter untuk IPK dengan aturan validasi
    public function setIpk(float \$nilai): void {
        if (\$nilai < 0.0 || \$nilai > 4.0) {
            echo "[ERROR] Nilai IPK harus berada di rentang 0.00 - 4.00! Nilai tidak diubah.\\n";
            return;
        }
        \$this->ipk = \$nilai;
    }

    public function getIpk(): float {
        return \$this->ipk;
    }

    public function getPredikat(): string {
        if (\$this->ipk >= 3.75) return "Dengan Pujian (Cumlaude)";
        if (\$this->ipk >= 3.00) return "Sangat Memuaskan";
        return "Memuaskan";
    }
}

\$mhs = new Mahasiswa("230101", "Siti Rahmawati", 3.85);
echo "Mahasiswa : " . \$mhs->getNama() . "\\n";
echo "IPK Awal  : " . \$mhs->getIpk() . " (" . \$mhs->getPredikat() . ")\\n\\n";

// Menguji validasi setter
echo "Mencoba mengubah IPK ke 4.5 (tidak valid):\\n";
\$mhs->setIpk(4.5);

echo "Mencoba mengubah IPK ke 3.92 (valid):\\n";
\$mhs->setIpk(3.92);
echo "IPK Baru  : " . \$mhs->getIpk() . " (" . \$mhs->getPredikat() . ")\\n";
`
    },
    {
        id: 'oop-inheritance',
        category: 'oop',
        categoryName: 'Pemrograman Berorientasi Objek (OOP)',
        title: '4. Pewarisan (Inheritance & Override)',
        difficulty: 'Menengah',
        description: 'Mewarisi sifat dan method dari class induk (parent) menggunakan extends dan parent::',
        keyPoints: [
            '`extends` digunakan untuk membuat class anak yang mewarisi class induk.',
            'Method overriding memungkinkan class anak mengganti perilaku method induk.',
            '`parent::methodName()` digunakan jika ingin memanggil method asli dari class induk.'
        ],
        code: `<?php
// Class Induk (Parent)
class Karyawan {
    protected string \$nama;
    protected float \$gajiPokok;

    public function __construct(string \$nama, float \$gajiPokok) {
        \$this->nama = \$nama;
        \$this->gajiPokok = \$gajiPokok;
    }

    public function hitungGajiTotal(): float {
        return \$this->gajiPokok;
    }

    public function cetakSlip(): void {
        echo "Karyawan : {\$this->nama}\\n";
        echo "Gaji Pokok: Rp " . number_format(\$this->gajiPokok, 0, ',', '.') . "\\n";
        echo "Gaji Total: Rp " . number_format(\$this->hitungGajiTotal(), 0, ',', '.') . "\\n";
        echo str_repeat("=", 35) . "\\n";
    }
}

// Class Anak (Child) - Manager
class Manager extends Karyawan {
    private float \$tunjanganJabatan;

    public function __construct(string \$nama, float \$gajiPokok, float \$tunjangan) {
        // Panggil constructor parent
        parent::__construct(\$nama, \$gajiPokok);
        \$this->tunjanganJabatan = \$tunjangan;
    }

    // Override method hitungGajiTotal
    public function hitungGajiTotal(): float {
        return \$this->gajiPokok + \$this->tunjanganJabatan;
    }

    // Override slip gaji
    public function cetakSlip(): void {
        echo "Jabatan  : Manager\\n";
        echo "Tunjangan: Rp " . number_format(\$this->tunjanganJabatan, 0, ',', '.') . "\\n";
        parent::cetakSlip();
    }
}

// Demonstrasi Polymorphism dasar
\$staff = new Karyawan("Joko Prasetyo", 4500000);
\$mgr = new Manager("Dewi Sartika", 8000000, 3500000);

\$staff->cetakSlip();
\$mgr->cetakSlip();
`
    },
    {
        id: 'oop-interface',
        category: 'oop',
        categoryName: 'Pemrograman Berorientasi Objek (OOP)',
        title: '5. Interface & Abstract Class',
        difficulty: 'Lanjutan',
        description: 'Menerapkan kontrak kode dengan interface dan abstract class untuk arsitektur software yang rapi.',
        keyPoints: [
            '`interface` mewajibkan class yang mengimplementasikannya memiliki method-method tertentu.',
            '`implements` digunakan untuk menerapkan interface (bisa lebih dari satu interface).',
            '`abstract class` dapat memiliki method abstrak (tanpa isi) dan method biasa (dengan isi).'
        ],
        code: `<?php
// Kontrak Payment Gateway
interface PembayaranInterface {
    public function bayar(float \$nominal): bool;
    public function getMetode(): string;
}

// Implementasi 1: Transfer Bank
class TransferBank implements PembayaranInterface {
    private string \$nomorVA;

    public function __construct(string \$nomorVA) {
        \$this->nomorVA = \$nomorVA;
    }

    public function bayar(float \$nominal): bool {
        echo "[Virtual Account {\$this->nomorVA}] Pembayaran sebesar Rp " . number_format(\$nominal, 0, ',', '.') . " Terverifikasi.\\n";
        return true;
    }

    public function getMetode(): string {
        return "Transfer Bank (VA: {\$this->nomorVA})";
    }
}

// Implementasi 2: E-Wallet
class EWallet implements PembayaranInterface {
    private string \$nomorHp;

    public function __construct(string \$nomorHp) {
        \$this->nomorHp = \$nomorHp;
    }

    public function bayar(float \$nominal): bool {
        echo "[E-Wallet {\$this->nomorHp}] Pembayaran instan Rp " . number_format(\$nominal, 0, ',', '.') . " Sukses!\\n";
        return true;
    }

    public function getMetode(): string {
        return "E-Wallet (No: {\$this->nomorHp})";
    }
}

// Layanan Checkout Toko (Depend on Abstraction)
function prosesCheckout(PembayaranInterface \$metode, float \$totalTagihan) {
    echo "Memproses pesanan dengan metode: " . \$metode->getMetode() . "...\\n";
    \$sukses = \$metode->bayar(\$totalTagihan);
    if (\$sukses) {
        echo "Status Pesanan: LUNAS & Siap Dikirim! 📦\\n\\n";
    }
}

// Pengujian
\$bayar1 = new TransferBank("988012345678");
\$bayar2 = new EWallet("081298765432");

prosesCheckout(\$bayar1, 250000);
prosesCheckout(\$bayar2, 75000);
`
    },
    {
        id: 'oop-modular-files',
        category: 'oop',
        categoryName: 'Pemrograman Berorientasi Objek (OOP)',
        title: '6. Pemisahan File & Modularitas (require / include)',
        difficulty: 'Menengah',
        description: 'Memisahkan deklarasi Class ke file terpisah (misal: Mahasiswa.php) dan mengimpornya di index.php.',
        keyPoints: [
            '`require_once`: Memastikan file hanya diimpor satu kali dan melempar Fatal Error jika file tidak ditemukan.',
            '`include_once`: Mirip require_once tetapi hanya melempar Warning jika file hilang.',
            'Di playground ini, klik tombol [+] di atas editor untuk membuat file baru (misal: `Mahasiswa.php`), lalu gunakan `require_once` di `index.php`!'
        ],
        code: `<?php
// Di playground Sakuci, Anda dapat membuat file baru melalui tombol [+] di atas!
// File utama yang dieksekusi selalu 'index.php'.

// Contoh mengimpor file Mahasiswa.php yang ada di tab:
require_once __DIR__ . '/Mahasiswa.php';

echo "=== DEMO MODULARITAS MULTI-FILE ===\\n";
echo "Class Mahasiswa berhasil di-import dari file terpisah (Mahasiswa.php)!\\n\\n";

\$mhs1 = new Mahasiswa("Ahmad Fauzi", "Teknik Informatika", 3.89);
echo \$mhs1->sapa() . "\\n";

\$mhs2 = new Mahasiswa("Dewi Lestari", "Sistem Informasi", 3.95);
echo \$mhs2->sapa() . "\\n";
`
    },

    // ==========================================
    // KATEGORI: DATABASE & CRUD (MYSQL & PDO)
    // ==========================================
    {
        id: 'crud-connection',
        category: 'crud',
        categoryName: 'Database & CRUD (MySQL / PDO)',
        title: '1. Koneksi Database dengan PDO',
        difficulty: 'Pemula',
        description: 'Menghubungkan PHP ke database relasional dengan PDO dan menangani error menggunakan try-catch PDOException.',
        keyPoints: [
            'PDO (PHP Data Objects) adalah standar industri modern untuk koneksi database di PHP.',
            'Playground ini secara otomatis menyediakan variabel `$pdo` yang siap pakai!',
            'Gunakan blok `try-catch (PDOException $e)` untuk menangani kegagalan koneksi secara aman.'
        ],
        code: `<?php
// Di playground ini, koneksi '$pdo' sudah disediakan otomatis!
// Kita juga dapat membuat koneksi sendiri menggunakan konstanta DB_DRIVER, DB_HOST, dll.

echo "=== STATUS KONEKSI DATABASE PLAYGROUND ===\\n";
echo "Database Driver : " . DB_DRIVER . "\\n";

try {
    // Memeriksa versi database yang sedang aktif
    \$queryVersi = DB_DRIVER === 'mysql' ? "SELECT VERSION() as v" : "SELECT sqlite_version() as v";
    \$stmt = \$pdo->query(\$queryVersi);
    \$versi = \$stmt->fetchColumn();

    echo "Koneksi Aktif   : BERHASIL TERHUBUNG! ✅\\n";
    echo "Versi Engine    : " . \$versi . "\\n\\n";

    // Menampilkan daftar tabel yang ada
    echo "Tabel yang tersedia di database:\\n";
    if (DB_DRIVER === 'mysql') {
        \$tables = \$pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    } else {
        \$tables = \$pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchAll(PDO::FETCH_COLUMN);
    }

    foreach (\$tables as \$i => \$t) {
        echo ($i + 1) . ". \$t\\n";
    }

} catch (PDOException \$e) {
    echo "Koneksi Gagal: " . \$e->getMessage() . "\\n";
}
`
    },
    {
        id: 'crud-create',
        category: 'crud',
        categoryName: 'Database & CRUD (MySQL / PDO)',
        title: '2. Create: INSERT dengan Prepared Statement',
        difficulty: 'Menengah',
        description: 'Menambahkan data baru ke tabel database secara aman menggunakan Prepared Statement untuk mencegah SQL Injection.',
        keyPoints: [
            'JANGAN PERNAH menyambung variabel pengguna langsung ke string SQL (`"INSERT INTO ... '" . $input . "'"`).',
            'Gunakan placeholder `?` (positional) atau `:nama` (named placeholder).',
            '`$pdo->prepare()` dan `$stmt->execute()` melindungi aplikasi dari peretasan SQL Injection.',
            '`$pdo->lastInsertId()` mengembalikan ID record yang baru dibuat.'
        ],
        code: `<?php
// Pastikan tabel mahasiswa sudah ada (atau gunakan tombol 'Seed Data')
echo "=== CREATE: MENAMBAHKAN MAHASISWA BARU ===\\n";

\$nimBaru = "230199_" . rand(100, 999);
\$namaBaru = "Reza Rahadian";
\$jurusanBaru = "Teknik Informatika";
\$emailBaru = "reza." . rand(100, 999) . "@sakuci.ac.id";
\$ipkBaru = 3.88;

// SQL dengan Named Placeholder
\$sql = "INSERT INTO mahasiswa (nim, nama, jurusan, email, ipk) 
        VALUES (:nim, :nama, :jurusan, :email, :ipk)";

try {
    \$stmt = \$pdo->prepare(\$sql);
    
    // Binding parameter dan eksekusi
    \$sukses = \$stmt->execute([
        ':nim'     => \$nimBaru,
        ':nama'    => \$namaBaru,
        ':jurusan' => \$jurusanBaru,
        ':email'   => \$emailBaru,
        ':ipk'     => \$ipkBaru
    ]);

    if (\$sukses) {
        \$idBaru = \$pdo->lastInsertId();
        echo "Data berhasil ditambahkan! ✅\\n";
        echo "ID Baru : \$idBaru\\n";
        echo "NIM     : \$nimBaru\\n";
        echo "Nama    : \$namaBaru\\n";
        echo "Jurusan : \$jurusanBaru\\n";
        echo "IPK     : \$ipkBaru\\n";
    }
} catch (PDOException \$e) {
    echo "Error Insert: " . \$e->getMessage() . "\\n";
}
`
    },
    {
        id: 'crud-read',
        category: 'crud',
        categoryName: 'Database & CRUD (MySQL / PDO)',
        title: '3. Read: SELECT Data dengan WHERE & FETCH',
        difficulty: 'Menengah',
        description: 'Membaca record dari tabel menggunakan query SELECT, filter WHERE, pengurutan ORDER BY, dan LIMIT.',
        keyPoints: [
            '`$stmt->fetchAll(PDO::FETCH_ASSOC)` mengembalikan semua baris sebagai array asosiatif.',
            '`$stmt->fetch(PDO::FETCH_ASSOC)` mengambil satu baris berikutnya.',
            '`WHERE` digunakan untuk menyaring data spesifik.',
            '`ORDER BY ... DESC` mengurutkan hasil dari terbesar ke terkecil.'
        ],
        code: `<?php
echo "=== READ: DAFTAR MAHASISWA IPK TERTINGGI ===\\n";

// Query dengan filter dan pengurutan
\$sql = "SELECT id, nim, nama, jurusan, ipk 
        FROM mahasiswa 
        ORDER BY ipk DESC 
        LIMIT 5";

\$stmt = \$pdo->query(\$sql);
\$daftarMahasiswa = \$stmt->fetchAll(PDO::FETCH_ASSOC);

echo str_pad("NIM", 12) . str_pad("NAMA", 20) . str_pad("JURUSAN", 22) . "IPK\\n";
echo str_repeat("-", 60) . "\\n";

foreach (\$daftarMahasiswa as \$mhs) {
    echo str_pad(\$mhs['nim'], 12) . 
         str_pad(\$mhs['nama'], 20) . 
         str_pad(\$mhs['jurusan'], 22) . 
         number_format(\$mhs['ipk'], 2) . "\\n";
}

echo "\\n=== MENCARI MAHASISWA BERDASARKAN JURUSAN ===\\n";
\$jurusanDicari = "Teknik Informatika";
\$stmtCari = \$pdo->prepare("SELECT nama, email, ipk FROM mahasiswa WHERE jurusan = ?");
\$stmtCari->execute([\$jurusanDicari]);
\$hasilCari = \$stmtCari->fetchAll(PDO::FETCH_ASSOC);

echo "Ditemukan " . count(\$hasilCari) . " mahasiswa di jurusan \$jurusanDicari:\\n";
foreach (\$hasilCari as \$m) {
    echo "• {\$m['nama']} ({\$m['email']}) - IPK: {\$m['ipk']}\\n";
}
`
    },
    {
        id: 'crud-update',
        category: 'crud',
        categoryName: 'Database & CRUD (MySQL / PDO)',
        title: '4. Update: Memperbarui Data Record',
        difficulty: 'Menengah',
        description: 'Memperbarui data yang ada di database secara aman dengan klausa WHERE.',
        keyPoints: [
            'Selalu sertakan klausa `WHERE` pada query UPDATE agar tidak mengubah semua baris data!',
            '`$stmt->rowCount()` mengembalikan jumlah baris yang berhasil diperbarui.',
            'Gunakan prepared statement untuk nilai-nilai yang diperbarui.'
        ],
        code: `<?php
echo "=== UPDATE: MEMPERBARUI DATA MAHASISWA ===\\n";

// 1. Ambil salah satu mahasiswa untuk diperbarui
\$mhs = \$pdo->query("SELECT id, nim, nama, ipk FROM mahasiswa LIMIT 1")->fetch(PDO::FETCH_ASSOC);

if (!\$mhs) {
    echo "Tabel mahasiswa masih kosong. Silakan klik tombol 'Seed Data' di tab Database.\\n";
    exit;
}

echo "Data Sebelum Update:\\n";
echo "ID: {\$mhs['id']} | Nama: {\$mhs['nama']} | IPK: {\$mhs['ipk']}\\n\\n";

// 2. Lakukan UPDATE
\$ipkBaru = 3.99;
\$sql = "UPDATE mahasiswa SET ipk = :ipk WHERE id = :id";
\$stmt = \$pdo->prepare(\$sql);
\$stmt->execute([
    ':ipk' => \$ipkBaru,
    ':id'  => \$mhs['id']
]);

echo "Jumlah baris terpengaruh: " . \$stmt->rowCount() . "\\n";

// 3. Verifikasi hasil update
\$stmtVerif = \$pdo->prepare("SELECT id, nama, ipk FROM mahasiswa WHERE id = ?");
\$stmtVerif->execute([\$mhs['id']]);
\$updated = \$stmtVerif->fetch(PDO::FETCH_ASSOC);

echo "Data Setelah Update:\\n";
echo "ID: {\$updated['id']} | Nama: {\$updated['nama']} | IPK Baru: {\$updated['ipk']} ✅\\n";
`
    },
    {
        id: 'crud-delete',
        category: 'crud',
        categoryName: 'Database & CRUD (MySQL / PDO)',
        title: '5. Delete: Menghapus Data Record',
        difficulty: 'Menengah',
        description: 'Menghapus data record spesifik secara aman dengan klausa WHERE dan prepared statement.',
        keyPoints: [
            'HATI-HATI: Query `DELETE FROM table` tanpa `WHERE` akan menghapus seluruh data!',
            'Pastikan selalu membungkus ID sasaran dalam parameter prepared statement.',
            '`rowCount()` akan memberi tahu apakah ada baris yang benar-benar terhapus.'
        ],
        code: `<?php
echo "=== DELETE: MENGHAPUS RECORD DATA ===\\n";

// Tambahkan data sementara terlebih dahulu untuk dihapus
\$nimHapus = "DEL_" . rand(1000, 9999);
\$pdo->prepare("INSERT INTO mahasiswa (nim, nama, jurusan, email, ipk) VALUES (?, ?, ?, ?, ?)")
    ->execute([\$nimHapus, "Siswa Demo Hapus", "Sistem Informasi", "temp@example.com", 2.50]);

\$idTarget = \$pdo->lastInsertId();
echo "Data sementara dibuat dengan ID: \$idTarget (NIM: \$nimHapus)\\n";

// Eksekusi penghapusan yang aman
\$sql = "DELETE FROM mahasiswa WHERE id = :id";
\$stmt = \$pdo->prepare(\$sql);
\$stmt->execute([':id' => \$idTarget]);

\$jumlahTerhapus = \$stmt->rowCount();
if (\$jumlahTerhapus > 0) {
    echo "Record dengan ID \$idTarget berhasil dihapus! (Jumlah: \$jumlahTerhapus) 🗑️\\n";
} else {
    echo "Tidak ada record yang terhapus (ID tidak ditemukan).\\n";
}
`
    },
    {
        id: 'crud-join',
        category: 'crud',
        categoryName: 'Database & CRUD (MySQL / PDO)',
        title: '6. Relasi Tabel dengan INNER JOIN',
        difficulty: 'Lanjutan',
        description: 'Menghubungkan data dari tabel berbeda (produk, kategori, transaksi) menggunakan relasi Foreign Key dan SQL JOIN.',
        keyPoints: [
            '`INNER JOIN` menggabungkan baris yang memiliki nilai cocok di kedua tabel.',
            '`LEFT JOIN` mempertahankan semua data dari tabel kiri meskipun tidak ada pasangan di tabel kanan.',
            'Alias tabel (misal `p` untuk `produk`, `k` untuk `kategori`) membuat query lebih bersih.'
        ],
        code: `<?php
echo "=== RELASI TABEL: PRODUK & KATEGORI (INNER JOIN) ===\\n";

\$sql = "SELECT 
            p.id, 
            p.nama_produk, 
            p.harga, 
            p.stok, 
            k.nama_kategori 
        FROM produk p
        INNER JOIN kategori k ON p.kategori_id = k.id
        ORDER BY k.nama_kategori, p.nama_produk";

\$stmt = \$pdo->query(\$sql);
\$daftarProduk = \$stmt->fetchAll(PDO::FETCH_ASSOC);

echo str_pad("KATEGORI", 20) . str_pad("NAMA PRODUK", 32) . str_pad("HARGA", 16) . "STOK\\n";
echo str_repeat("-", 75) . "\\n";

foreach (\$daftarProduk as \$item) {
    echo str_pad(\$item['nama_kategori'], 20) . 
         str_pad(\$item['nama_produk'], 32) . 
         str_pad("Rp " . number_format(\$item['harga'], 0, ',', '.'), 16) . 
         \$item['stok'] . " unit\\n";
}

echo "\\n=== LAPORAN TRANSAKSI PENJUALAN ===\\n";
\$sqlTx = "SELECT 
            t.id, 
            p.nama_produk, 
            t.jumlah, 
            t.total_harga, 
            t.tanggal
          FROM transaksi t
          INNER JOIN produk p ON t.produk_id = p.id
          ORDER BY t.id DESC";

\$stmtTx = \$pdo->query(\$sqlTx);
\$daftarTx = \$stmtTx->fetchAll(PDO::FETCH_ASSOC);

foreach (\$daftarTx as \$tx) {
    echo "#{\$tx['id']} | {\$tx['nama_produk']} x {\$tx['jumlah']} = Rp " . 
         number_format(\$tx['total_harga'], 0, ',', '.') . " (" . \$tx['tanggal'] . ")\\n";
}
`
    },

    // ==========================================
    // KATEGORI: STUDI KASUS & MINI APPS
    // ==========================================
    {
        id: 'case-student-crud',
        category: 'cases',
        categoryName: 'Studi Kasus & Proyek Mini',
        title: '1. Class MahasiswaRepository (Design Pattern CRUD)',
        difficulty: 'Lanjutan',
        description: 'Menerapkan arsitektur Repository Pattern berbasis OOP untuk mengelola seluruh operasi CRUD database secara terstruktur.',
        keyPoints: [
            'Pemisahan logika bisnis dan query database menggunakan Repository Pattern.',
            'Semua operasi CRUD (GetAll, FindById, Create, Update, Delete) dibungkus rapi dalam satu class.',
            'Mudah di-unit test dan sangat mudah dipelihara pada aplikasi skala besar.'
        ],
        code: `<?php
/**
 * MahasiswaRepository: Menangani seluruh interaksi database untuk entitas Mahasiswa
 */
class MahasiswaRepository {
    private PDO \$db;

    public function __construct(PDO \$db) {
        \$this->db = \$db;
    }

    public function getAll(): array {
        \$stmt = \$this->db->query("SELECT * FROM mahasiswa ORDER BY id DESC");
        return \$stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int \$id): ?array {
        \$stmt = \$this->db->prepare("SELECT * FROM mahasiswa WHERE id = :id");
        \$stmt->execute([':id' => \$id]);
        \$result = \$stmt->fetch(PDO::FETCH_ASSOC);
        return \$result ?: null;
    }

    public function create(array \$data): int {
        \$sql = "INSERT INTO mahasiswa (nim, nama, jurusan, email, ipk) 
                VALUES (:nim, :nama, :jurusan, :email, :ipk)";
        \$stmt = \$this->db->prepare(\$sql);
        \$stmt->execute([
            ':nim'     => \$data['nim'],
            ':nama'    => \$data['nama'],
            ':jurusan' => \$data['jurusan'],
            ':email'   => \$data['email'],
            ':ipk'     => \$data['ipk']
        ]);
        return (int)\$this->db->lastInsertId();
    }

    public function delete(int \$id): bool {
        \$stmt = \$this->db->prepare("DELETE FROM mahasiswa WHERE id = :id");
        return \$stmt->execute([':id' => \$id]);
    }
}

// === PENGGUNAAN REPOSITORY ===
\$repo = new MahasiswaRepository(\$pdo);

// 1. Tambah Mahasiswa Baru
\$idBaru = \$repo->create([
    'nim' => '230999_' . rand(10, 99),
    'nama' => 'Gita Gutawa',
    'jurusan' => 'Sistem Informasi',
    'email' => 'gita@example.com',
    'ipk' => 3.95
]);
echo "Mahasiswa baru berhasil disimpan dengan ID: \$idBaru\\n\\n";

// 2. Temukan berdasarkan ID
\$mhs = \$repo->findById(\$idBaru);
echo "Detail Mahasiswa Ditemukan:\\n";
echo "Nama   : " . \$mhs['nama'] . "\\n";
echo "Jurusan: " . \$mhs['jurusan'] . "\\n";
echo "IPK    : " . \$mhs['ipk'] . "\\n\\n";

// 3. Tampilkan 3 data teratas
echo "Daftar 3 Mahasiswa Terbaru:\\n";
\$semua = \$repo->getAll();
foreach (array_slice(\$semua, 0, 3) as \$item) {
    echo "- [{\$item['nim']}] {\$item['nama']} (IPK: {\$item['ipk']})\\n";
}
`
    },
    {
        id: 'case-html-view',
        category: 'cases',
        categoryName: 'Studi Kasus & Proyek Mini',
        title: '2. Render Tampilan Tabel HTML & Badge',
        difficulty: 'Menengah',
        description: 'Menghasilkan output HTML tabel modern dari database. Klik tab "HTML Preview" di panel sebelah kanan untuk melihat hasil tampilannya!',
        keyPoints: [
            'PHP dapat menyisipkan output HTML secara dinamis.',
            'Playground ini memiliki tab "HTML Preview" khusus untuk merender hasil `echo` berupa HTML, CSS, dan tabel.',
            'Sangat cocok untuk belajar membuat web dashboard dinamis.'
        ],
        code: `<?php
// Mengambil data produk dari database
\$stmt = \$pdo->query("
    SELECT p.nama_produk, p.harga, p.stok, k.nama_kategori 
    FROM produk p 
    LEFT JOIN kategori k ON p.kategori_id = k.id
    ORDER BY p.id ASC
");
\$produkList = \$stmt->fetchAll(PDO::FETCH_ASSOC);

// Menghasilkan output HTML yang stylish!
echo <<<HTML
<div style="font-family: system-ui, -apple-system, sans-serif; padding: 20px; background: #0f172a; color: #f8fafc; border-radius: 8px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="margin: 0; font-size: 20px; color: #38bdf8;">📦 Katalog Inventaris Produk</h2>
        <span style="background: #0369a1; padding: 4px 12px; border-radius: 20px; font-size: 13px;">Total: count(\$produkList) item</span>
    </div>

    <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 14px;">
        <thead>
            <tr style="border-bottom: 2px solid #334155; color: #94a3b8;">
                <th style="padding: 10px;">Nama Produk</th>
                <th style="padding: 10px;">Kategori</th>
                <th style="padding: 10px;">Harga</th>
                <th style="padding: 10px;">Stok</th>
                <th style="padding: 10px;">Status</th>
            </tr>
        </thead>
        <tbody>
HTML;

foreach (\$produkList as \$p) {
    \$badgeColor = \$p['stok'] > 20 ? '#10b981' : '#f59e0b';
    \$badgeText = \$p['stok'] > 20 ? 'Tersedia' : 'Stok Menipis';
    \$hargaFormat = "Rp " . number_format(\$p['harga'], 0, ',', '.');

    echo <<<ROW
        <tr style="border-bottom: 1px solid #1e293b;">
            <td style="padding: 12px 10px; font-weight: 500;">{\$p['nama_produk']}</td>
            <td style="padding: 12px 10px; color: #cbd5e1;">{\$p['nama_kategori']}</td>
            <td style="padding: 12px 10px; color: #38bdf8; font-weight: 600;">{\$hargaFormat}</td>
            <td style="padding: 12px 10px;">{\$p['stok']} unit</td>
            <td style="padding: 12px 10px;">
                <span style="background: {\$badgeColor}22; color: {\$badgeColor}; border: 1px solid {\$badgeColor}55; padding: 3px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">
                    {\$badgeText}
                </span>
            </td>
        </tr>
ROW;
}

echo <<<HTML
        </tbody>
    </table>
    <p style="margin-top: 20px; font-size: 12px; color: #64748b; text-align: center;">
        Dirender secara dinamis dengan PHP 8.3 & Database Playground
    </p>
</div>
HTML;
`
    }
];

if (typeof module !== 'undefined' && module.exports) {
    module.exports = { CURRICULUM };
}
