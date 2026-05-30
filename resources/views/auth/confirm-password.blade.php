@extends('layouts.guest')

@section('title', 'Konfirmasi Password')

@section('content')

<div class="text-center mb-4">
    <h3 class="fw-bold mb-2">🔒 Area Aman</h3>
    <p class="text-muted small">
        Mohon konfirmasi password Anda sebelum melanjutkan.
    </p>
</div>

<form method="POST" action="{{ route('password.confirm') }}">
    @csrf

    <div class="mb-4">
        <label for="password" class="form-label fw-semibold small">Password</label>
        <input id="password" type="password" name="password"
               class="form-control form-control-lg @error('password') is-invalid @enderror"
               required autocomplete="current-password" autofocus>
        @error('password')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <button type="submit" class="btn btn-primary btn-lg w-100 fw-semibold">
        Konfirmasi
    </button>
</form>

@endsection
