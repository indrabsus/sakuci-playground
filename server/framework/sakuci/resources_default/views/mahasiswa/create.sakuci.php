@extends('layouts.app')

@section('title', 'Tambah Mahasiswa - Sakuci Framework')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6 col-md-8">
        <div class="card border-0 shadow-sm p-4">
            <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
                <div>
                    <h4 class="fw-bold mb-0">➕ Tambah Mahasiswa Baru</h4>
                    <small class="text-body-secondary">Menjalankan <code>MahasiswaController@store</code></small>
                </div>
                <a href="/mahasiswa" class="btn btn-outline-secondary btn-sm">← Batal</a>
            </div>

            <form action="/mahasiswa/simpan" method="POST">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Nomor Induk Mahasiswa (NIM) *</label>
                    <input type="text" name="nim" required placeholder="Contoh: 2024009" class="form-control font-monospace">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Nama Lengkap *</label>
                    <input type="text" name="nama" required placeholder="Contoh: Muhammad Ilham" class="form-control">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Jurusan</label>
                    <select name="jurusan" class="form-select">
                        <option value="Teknik Informatika">Teknik Informatika</option>
                        <option value="Sistem Informasi">Sistem Informasi</option>
                        <option value="Teknik Komputer">Teknik Komputer</option>
                        <option value="Manajemen Informatika">Manajemen Informatika</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Alamat Email</label>
                    <input type="email" name="email" placeholder="Contoh: ilham@kampus.ac.id" class="form-control">
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-semibold">Indeks Prestasi Kumulatif (IPK)</label>
                    <input type="number" step="0.01" min="0" max="4.00" name="ipk" value="3.50" class="form-control font-monospace">
                </div>

                <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                    <a href="/mahasiswa" class="btn btn-secondary btn-sm px-3">Batal</a>
                    <button type="submit" class="btn btn-primary btn-sm px-4">
                        💾 Simpan Mahasiswa
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
