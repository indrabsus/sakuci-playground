@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <span class="badge rounded-pill badge-brand px-3 py-2 mb-3">Role: {{ $user->role }}</span>
            <h1 class="h4 mb-2">Halo, {{ $user->username }}</h1>
            <p class="text-secondary mb-0">Ini halaman dashboard umum, bisa diakses semua role yang sudah login.</p>
        </div>
    </div>

@endsection

