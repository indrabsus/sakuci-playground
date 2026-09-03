@extends('layouts.app')

@section('title', 'Manage Role')

@section('content')

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <span class="badge rounded-pill badge-brand px-3 py-2 mb-2">Area Admin</span>
            <h1 class="h4 mb-0">Manage Role</h1>
        </div>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-sm btn-outline-secondary">&larr; Kembali</a>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h2 class="h6 mb-3">Tambah Role Baru</h2>

                    <form method="POST" action="{{ route('admin.roles.store') }}">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label" for="name">Nama Role</label>
                            <input type="text" id="name" name="name" value="{{ old('name') }}" class="form-control {{ errors()->has('name') ? 'is-invalid' : '' }}" placeholder="mis. editor" autofocus>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="form-check mb-3">
                            <input type="checkbox" id="can_register" name="can_register" value="1" class="form-check-input" {{ old('can_register') ? 'checked' : '' }}>
                            <label class="form-check-label" for="can_register">Bisa registrasi sendiri (muncul di halaman /register)</label>
                        </div>

                        <p class="small text-secondary mb-3">Middleware untuk role ini dibuat otomatis dan langsung bisa dipakai lewat <code class="inline">-&gt;middleware('nama-role')</code>.</p>

                        <button class="btn btn-brand w-100" type="submit">Tambah Role</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h2 class="h6 mb-3">Daftar Role</h2>

                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Nama</th>
                                    <th>Registrasi</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($roles as $role)
                                    <tr>
                                        <td>{{ $role->id }}</td>
                                        <td><code class="inline">{{ $role->name }}</code></td>
                                        <td>
                                            @if ($role->can_register)
                                                <span class="badge bg-success">Terbuka</span>
                                            @else
                                                <span class="badge bg-secondary">Tertutup</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($role->name === 'admin')
                                                <span class="text-secondary small">Role bawaan</span>
                                            @else
                                                <div class="d-flex flex-wrap align-items-center gap-2">
                                                    <form method="POST" action="{{ route('admin.roles.update', ['role' => $role->id]) }}" class="d-flex flex-wrap align-items-center gap-1">
                                                        @csrf
                                                        @method('PUT')
                                                        <input type="text" name="name" value="{{ $role->name }}" class="form-control form-control-sm" style="width: 100px" required>
                                                        <div class="form-check form-check-inline m-0" title="Bisa registrasi sendiri">
                                                            <input type="checkbox" name="can_register" value="1" class="form-check-input" id="reg{{ $role->id }}" {{ $role->can_register ? 'checked' : '' }}>
                                                            <label class="form-check-label small" for="reg{{ $role->id }}">Daftar</label>
                                                        </div>
                                                        <button type="submit" class="btn btn-sm btn-outline-brand">Simpan</button>
                                                    </form>
                                                    <form method="POST" action="{{ route('admin.roles.destroy', ['role' => $role->id]) }}" onsubmit="return confirm('Hapus role &quot;{{ $role->name }}&quot;?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                                    </form>
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-secondary">Belum ada role.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

