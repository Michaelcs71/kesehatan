@extends('layouts.guest')

@section('title', 'Verifikasi Email')

@section('content')

<div class="text-center mb-4">
    <div style="font-size: 3rem;">✉️</div>
    <h3 class="fw-bold mb-2">Verifikasi Email Anda</h3>
    <p class="text-muted small">
        Terima kasih sudah mendaftar! Sebelum mulai, mohon verifikasi email Anda dengan klik link yang baru saja kami kirim.
        Jika belum menerima, kami bisa kirim ulang.
    </p>
</div>

@if (session('status') == 'verification-link-sent')
    <div class="alert alert-success small text-center">
        ✅ Link verifikasi baru sudah dikirim ke email Anda.
    </div>
@endif

<form method="POST" action="{{ route('verification.send') }}" class="d-grid gap-2">
    @csrf
    <button type="submit" class="btn btn-primary btn-lg fw-semibold">
        📤 Kirim Ulang Email Verifikasi
    </button>
</form>

<form method="POST" action="{{ route('logout') }}" class="d-grid mt-3">
    @csrf
    <button type="submit" class="btn btn-link text-muted small">Logout</button>
</form>

@endsection
