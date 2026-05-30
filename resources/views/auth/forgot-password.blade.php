@extends('layouts.guest')

@section('title', 'Lupa Password')

@section('content')

<div class="text-center mb-4">
    <h3 class="fw-bold mb-1">Lupa Password?</h3>
    <p class="text-muted small mb-0">
        Masukkan email Anda. Kami akan kirim link reset password ke email tersebut.
    </p>
</div>

@if (session('status'))
    <div class="alert alert-success small">
        {{ session('status') }}
    </div>
@endif

<form method="POST" action="{{ route('password.email') }}">
    @csrf

    <div class="mb-4">
        <label for="email" class="form-label fw-semibold small">Email</label>
        <input id="email" type="email" name="email"
               class="form-control form-control-lg @error('email') is-invalid @enderror"
               value="{{ old('email') }}" required autofocus
               placeholder="nama@email.com">
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <button type="submit" class="btn btn-primary btn-lg w-100 fw-semibold mb-3">
        ✉️ Kirim Link Reset
    </button>

    <div class="text-center">
        <a href="{{ route('login') }}" class="small text-decoration-none">
            ← Kembali ke login
        </a>
    </div>
</form>

@endsection
