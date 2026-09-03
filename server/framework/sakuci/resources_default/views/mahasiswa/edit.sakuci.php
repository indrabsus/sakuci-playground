@extends('layouts.app')

@section('title', 'Edit Mahasiswa - Sakuci Framework')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6 col-md-8">
        <div class="card border-0 shadow-sm p-4">
            <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
                <div>
                    <h4 class="fw-bold mb-0">✏️ Edit Data Mahasiswa</h4>
                    <small class="text-body-secondary">Menjalankan <code>MahasiswaController@update</code> (ID: {{ $mhs->id }})</small>
                </div>
                <a href="/mahasiswa" class="btn btn-outline-secondary btn-sm">← Batal</a>
            </div>

            <form action="/mahasiswa/{{ $mhs->id }}/update" method="POST">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Nomor Induk Mahasiswa (NIM) *</label>
                    <input type="text" name="nim" value="{{ $mhs->nim }}" required class="form-control font-monospace">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Nama Lengkap *</label>
                    <input type="text" name="nama" value="{{ $mhs->nama }}" required class="form-control">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Jurusan</label>
                    <select name="jurusan" class="form-select">
                        <option value="Teknik Informatika" {{ $mhs->jurusan === 'Teknik Informatika' ? 'selected' : '' }}>Teknik Informatika</option>
                        <option value="Sistem Informasi" {{ $mhs->jurusan === 'Sistem Informasi' ? 'selected' : '' }}>Sistem Informasi</option>
                        <option value="Teknik Komputer" {{ $mhs->jurusan === 'Teknik Komputer' ? 'selected' : '' }}>Teknik Komputer</option>
                        <option value="Manajemen Informatika" {{ $mhs->jurusan === 'Manajemen Informatika' ? 'selected' : '' }}>Manajemen Informatika</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Alamat Email</label>
                    <input type="email" name="email" value="{{ $mhs->email }}" class="form-control">
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-semibold">Indeks Prestasi Kumulatif (IPK)</label>
                    <input type="number" step="0.01" min="0" max="4.00" name="ipk" value="{{ $mhs->ipk }}" class="form-control font-monospace">
                </div>

                <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                    <a href="/mahasiswa" class="btn btn-secondary btn-sm px-3">Batal</a>
                    <button type="submit" class="btn btn-warning btn-sm px-4">
                        💾 Perbarui Data
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
