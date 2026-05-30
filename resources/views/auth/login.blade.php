@extends('layouts.guest')

@section('title', 'Masuk ke Akun')

@section('content')

    <div class="text-center mb-4">
        <h3 class="fw-bold mb-1">Masuk ke Akun</h3>
        <p class="text-muted small mb-0">Selamat datang kembali! Silakan login untuk lanjut.</p>
    </div>

    {{-- Session Status --}}
    @if (session('status'))
        <div class="alert alert-success small">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        {{-- Login (nama atau WhatsApp) --}}
        <div class="mb-3">
            <label for="login" class="form-label fw-semibold small">
                Nama Lengkap atau No. WhatsApp
            </label>
            <input id="login" type="text" name="login"
                class="form-control form-control-lg @error('login') is-invalid @enderror" value="{{ old('login') }}"
                required autofocus autocomplete="username" placeholder="Contoh: Budi Santoso atau 081234567890">
            @error('login')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <small class="text-muted d-block mt-1">
                <i class="ri ri-information-line"></i>
                Anda bisa login menggunakan nama lengkap atau nomor WhatsApp yang terdaftar.
            </small>
        </div>

        {{-- Password --}}
        <div class="mb-3">
            <label for="password" class="form-label fw-semibold small">Password</label>
            <input id="password" type="password" name="password"
                class="form-control form-control-lg @error('password') is-invalid @enderror" required
                autocomplete="current-password" placeholder="••••••••">
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        {{-- Remember Me --}}
        <div class="mb-4 form-check">
            <input id="remember_me" type="checkbox" name="remember" class="form-check-input">
            <label class="form-check-label small" for="remember_me">Ingat saya</label>
        </div>

        {{-- Submit --}}
        <button type="submit" class="btn btn-primary btn-lg w-100 fw-semibold mb-3">
            <i class="ri ri-login-box-line me-1"></i> Masuk
        </button>

        {{-- Link Register --}}
        <div class="text-center">
            <span class="text-muted small">Belum punya akun?</span>
            <a href="{{ route('register') }}" class="small fw-semibold text-decoration-none">
                Daftar sekarang
            </a>
        </div>
    </form>

@endsection
