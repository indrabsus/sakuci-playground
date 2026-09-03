@extends('layouts.app')

@section('title', 'Daftar')

@section('content')

    <div class="row">
        <div class="col-md-5 mx-auto">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h1 class="h4 mb-1">Daftar Akun</h1>
                    <p class="text-secondary small mb-4">Sudah punya akun? <a href="{{ route('login') }}">Masuk di sini</a>.</p>

                    <form method="POST" action="{{ route('register.attempt') }}">
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

                        <div class="mb-3">
                            <label class="form-label" for="password_confirmation">Konfirmasi Password</label>
                            <input type="password" id="password_confirmation" name="password_confirmation" class="form-control">
                        </div>

                        @if (count($roles) > 1)
                            <div class="mb-3">
                                <label class="form-label" for="role">Daftar sebagai</label>
                                <select id="role" name="role" class="form-select {{ errors()->has('role') ? 'is-invalid' : '' }}">
                                    @foreach ($roles as $role)
                                        <option value="{{ $role->name }}" {{ old('role') === $role->name ? 'selected' : '' }}>{{ $role->name }}</option>
                                    @endforeach
                                </select>
                                @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        @else
                            <input type="hidden" name="role" value="{{ $roles[0]->name }}">
                        @endif

                        <button class="btn btn-brand w-100" type="submit">Daftar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection
