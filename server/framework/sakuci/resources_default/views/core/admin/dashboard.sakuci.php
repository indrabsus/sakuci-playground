@extends('layouts.app')

@section('title', 'Admin')

@section('content')

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <span class="badge rounded-pill badge-brand px-3 py-2 mb-3">Area Admin</span>
            <h1 class="h4 mb-2">Halo, {{ $user->username }}</h1>
            <p class="text-secondary mb-0">Halaman ini hanya bisa diakses role <code class="inline">admin</code> (middleware <code class="inline">admin</code>).</p>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <a href="{{ route('admin.roles.index') }}" class="card border-0 shadow-sm text-decoration-none h-100">
                <div class="card-body p-4">
                    <h2 class="h6 mb-1">Manage Role</h2>
                    <p class="text-secondary small mb-0">Tambah role baru untuk dipakai saat membuat user.</p>
                </div>
            </a>
        </div>
        <div class="col-md-6">
            <a href="{{ route('admin.users.index') }}" class="card border-0 shadow-sm text-decoration-none h-100">
                <div class="card-body p-4">
                    <h2 class="h6 mb-1">Manage User</h2>
                    <p class="text-secondary small mb-0">Tambah user baru dan tentukan role-nya.</p>
                </div>
            </a>
        </div>
        <div class="col-md-6">
            <a href="{{ route('admin.database.export') }}" class="card border-0 shadow-sm text-decoration-none h-100">
                <div class="card-body p-4">
                    <h2 class="h6 mb-1">Download Database</h2>
                    <p class="text-secondary small mb-0">Unduh seluruh isi database jadi satu file .sql, siap diimpor di server.</p>
                </div>
            </a>
        </div>
    </div>

@endsection

