@extends('layouts.guest')

@section('title', 'Reset Password')

@section('content')

<div class="text-center mb-4">
    <h3 class="fw-bold mb-1">Reset Password</h3>
    <p class="text-muted small mb-0">Masukkan password baru untuk akun Anda.</p>
</div>

<form method="POST" action="{{ route('password.store') }}">
    @csrf

    <input type="hidden" name="token" value="{{ $request->route('token') }}">

    <div class="mb-3">
        <label for="email" class="form-label fw-semibold small">Email</label>
        <input id="email" type="email" name="email"
               class="form-control form-control-lg @error('email') is-invalid @enderror"
               value="{{ old('email', $request->email) }}" required autofocus>
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label for="password" class="form-label fw-semibold small">Password Baru</label>
        <input id="password" type="password" name="password"
               class="form-control form-control-lg @error('password') is-invalid @enderror"
               required autocomplete="new-password" placeholder="Minimal 8 karakter">
        @error('password')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-4">
        <label for="password_confirmation" class="form-label fw-semibold small">Konfirmasi Password</label>
        <input id="password_confirmation" type="password" name="password_confirmation"
               class="form-control form-control-lg" required autocomplete="new-password">
    </div>

    <button type="submit" class="btn btn-primary btn-lg w-100 fw-semibold">
        🔑 Reset Password
    </button>
</form>

@endsection
