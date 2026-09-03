@extends('layouts.app')

@section('title', 'Login')

@section('content')

    <div class="row">
        <div class="col-md-5 mx-auto">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h1 class="h4 mb-1">Masuk</h1>
                    <p class="text-secondary small mb-4">Akun demo: admin &mdash; password <code class="inline">rahasia123</code></p>

                    <form method="POST" action="{{ route('login.attempt') }}">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label" for="username">Username</label>
                            <input type="text" id="username" name="username" value="{{ old('username') }}" class="form-control {{ errors()->has('username') ? 'is-invalid' : '' }}" autofocus>
                            @error('username') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="password">Password</label>
                            <input type="password" id="password" name="password" class="form-control {{ errors()->has('password') ? 'is-invalid' : '' }}">
                            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <button class="btn btn-brand w-100" type="submit">Login</button>
                    </form>

                    @php
                        $canRegister = false;
                        try {
                            $canRegister = \App\Models\Role::where('can_register', 1)->exists();
                        } catch (\Throwable $e) {
                            $canRegister = false;
                        }
                    @endphp
                    @if ($canRegister)
                        <p class="text-secondary small text-center mt-3 mb-0">Belum punya akun? <a href="{{ route('register') }}">Daftar di sini</a>.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

@endsection

