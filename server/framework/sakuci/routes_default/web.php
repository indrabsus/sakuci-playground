<?php

use App\Controllers\Core\AuthController;
use App\Controllers\Core\DashboardController;
use App\Controllers\Core\DatabaseController;
use App\Controllers\Core\DocsController;
use App\Controllers\Core\RoleController;
use App\Controllers\Core\UserController;
use App\Controllers\MahasiswaController;
use Sakuci\Route;

/*
|--------------------------------------------------------------------------
| Route Web
|--------------------------------------------------------------------------
| Daftarkan seluruh route aplikasi di sini.
|
| Cara menulis action:
|   [HomeController::class, 'index']   -> disarankan
|   'HomeController@index'             -> namespace App\Controllers otomatis
|   function () { ... }                -> closure
*/

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/halo', function () {
    return response("<!doctype html><html lang='id' data-bs-theme='dark'><head><meta charset='utf-8'><title>Test Route /halo</title><link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'></head><body class='p-5 text-center bg-dark text-light'><div class='card card-body bg-dark border-secondary mx-auto shadow mt-5' style='max-width: 500px;'><h2 class='text-info mb-3'>⚡ Halo dari Sakuci Framework!</h2><p class='text-secondary'>Rute sederhana <code>/halo</code> berjalan lancar langsung dari framework.</p><div class='mt-4'><a href='/' class='btn btn-outline-light btn-sm me-2'>&larr; Kembali ke Beranda</a><a href='/mahasiswa' class='btn btn-primary btn-sm'>Buka CRUD Mahasiswa</a></div></div></body></html>");
});

Route::get('/docs', [DocsController::class, 'index'])->name('docs');

// =========================================================================
// CRUD DATA MAHASISWA (MahasiswaController)
// =========================================================================
Route::get('/mahasiswa', [MahasiswaController::class, 'index'])->name('mahasiswa.index');
Route::get('/mahasiswa/tambah', [MahasiswaController::class, 'create'])->name('mahasiswa.create');
Route::post('/mahasiswa/simpan', [MahasiswaController::class, 'store'])->name('mahasiswa.store');
Route::get('/mahasiswa/{id}/edit', [MahasiswaController::class, 'edit'])->name('mahasiswa.edit');
Route::post('/mahasiswa/{id}/update', [MahasiswaController::class, 'update'])->name('mahasiswa.update');
Route::get('/mahasiswa/{id}/hapus', [MahasiswaController::class, 'destroy'])->name('mahasiswa.destroy');

/*
|--------------------------------------------------------------------------
| Login multi-role
|--------------------------------------------------------------------------
| Lihat /docs untuk penjelasan lengkap langkah demi langkah.
*/

Route::get('/login', [AuthController::class, 'showLogin'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->name('login.attempt')->middleware('guest');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::get('/register', [AuthController::class, 'showRegister'])->name('register')->middleware('guest');
Route::post('/register', [AuthController::class, 'register'])->name('register.attempt')->middleware('guest');

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard')->middleware('auth');

Route::group(['prefix' => 'admin', 'middleware' => 'admin'], function () {
    Route::get('/', [DashboardController::class, 'admin'])->name('admin.dashboard');

    Route::get('/roles', [RoleController::class, 'index'])->name('admin.roles.index');
    Route::post('/roles', [RoleController::class, 'store'])->name('admin.roles.store');
    Route::put('/roles/{role}', [RoleController::class, 'update'])->name('admin.roles.update');
    Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('admin.roles.destroy');

    Route::get('/users', [UserController::class, 'index'])->name('admin.users.index');
    Route::post('/users', [UserController::class, 'store'])->name('admin.users.store');

    Route::get('/database/export', [DatabaseController::class, 'export'])->name('admin.database.export');
});

/*
|--------------------------------------------------------------------------
| Route role dinamis
|--------------------------------------------------------------------------
| Blok di bawah ini dikelola otomatis oleh RoleController saat admin
| menambah, mengganti nama, atau menghapus role lewat /admin/roles.
| Jangan diedit manual -- perubahan bisa tertimpa.
*/
// @generated-roles:start

// @generated-roles:end

/*
|--------------------------------------------------------------------------
| Contoh (hapus/ubah sesuai kebutuhan)
|--------------------------------------------------------------------------
|
| use App\Controllers\BukuController;
|
| Route::get('/buku', [BukuController::class, 'index'])->name('buku.index');
|
| // Tujuh route CRUD sekaligus: index, create, store, show, edit, update, destroy
| // Route::resource
|
| // Group dengan prefix dan middleware bersama
| Route::group(['prefix' => 'admin', 'middleware' => 'auth'], function () {
|     Route::get('/dashboard', [DashboardController::class, 'index']);
| });
*/

