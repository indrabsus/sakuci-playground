@extends('layouts.app')

@section('title', $status . ' â€” ' . config('app.name'))

@section('content')

    <div class="text-center py-5">
        <div class="error-code">{{ $status }}</div>
        <p class="h5 fw-normal text-secondary mt-3 mb-4">{{ $exception->getMessage() }}</p>
        <a class="btn btn-brand px-4" href="{{ route('home') }}">Kembali ke Beranda</a>
    </div>

@endsection

