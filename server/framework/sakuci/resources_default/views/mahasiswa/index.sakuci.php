@extends('layouts.app')

@section('title', 'Data Mahasiswa - Sakuci Framework')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h2 class="fw-bold mb-1">🎓 Data Mahasiswa</h2>
        <p class="text-body-secondary small mb-0">
            Aplikasi CRUD menggunakan <code>MahasiswaController</code> &amp; <code>Sakuci\Database\Model</code>
        </p>
    </div>
    <div>
        <a href="/mahasiswa/tambah" class="btn btn-primary btn-sm px-3 shadow-sm">
            <i class="bi bi-plus-lg me-1"></i> + Tambah Mahasiswa
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 text-nowrap">
            <thead class="table-dark text-uppercase font-monospace small">
                <tr>
                    <th class="py-3 px-3 text-center" style="width: 60px;">ID</th>
                    <th class="py-3 px-3">NIM</th>
                    <th class="py-3 px-3">Nama Mahasiswa</th>
                    <th class="py-3 px-3">Jurusan</th>
                    <th class="py-3 px-3">Email</th>
                    <th class="py-3 px-3 text-center">IPK</th>
                    <th class="py-3 px-3 text-center" style="width: 150px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($mahasiswa as $mhs)
                <tr>
                    <td class="text-center font-monospace text-body-secondary">{{ $mhs->id }}</td>
                    <td class="font-monospace fw-bold text-primary">{{ $mhs->nim }}</td>
                    <td class="fw-semibold">{{ $mhs->nama }}</td>
                    <td class="text-body-secondary">{{ $mhs->jurusan }}</td>
                    <td class="small text-body-secondary">{{ $mhs->email ?? '-' }}</td>
                    <td class="text-center">
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1 font-monospace">
                            {{ number_format((float)$mhs->ipk, 2) }}
                        </span>
                    </td>
                    <td class="text-center">
                        <div class="btn-group btn-group-sm">
                            <a href="/mahasiswa/{{ $mhs->id }}/edit" class="btn btn-outline-warning">
                                Edit
                            </a>
                            <a href="/mahasiswa/{{ $mhs->id }}/hapus" onclick="return confirm('Yakin ingin menghapus data {{ $mhs->nama }}?')" class="btn btn-outline-danger">
                                Hapus
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-5 text-body-secondary">
                        <p class="mb-2">Belum ada data mahasiswa.</p>
                        <a href="/mahasiswa/tambah" class="btn btn-sm btn-primary">+ Tambah Mahasiswa Pertama</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-body-tertiary d-flex justify-content-between align-items-center py-2 px-3 small text-body-secondary">
        <span>Total: <strong>{{ count($mahasiswa) }}</strong> Mahasiswa</span>
        <span class="font-monospace">Route: /mahasiswa</span>
    </div>
</div>
@endsection
