@extends('layouts.app')

@section('title', 'Profil Saya')

@section('page-header')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">Profil Saya</h4>
            <small class="text-muted">Kelola informasi akun, password, dan pengaturan keamanan.</small>
        </div>
    </div>
@endsection

@section('content')

<div class="row g-4">
    {{-- Update Profile Info --}}
    <div class="col-lg-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3">📋 Informasi Profil</h5>
                <p class="text-muted small mb-4">Perbarui nama dan alamat email akun Anda.</p>

                @include('profile.partials.update-profile-information-form')
            </div>
        </div>
    </div>

    {{-- Update Password --}}
    <div class="col-lg-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3">🔑 Ubah Password</h5>
                <p class="text-muted small mb-4">Pastikan password Anda panjang dan acak agar akun tetap aman.</p>

                @include('profile.partials.update-password-form')
            </div>
        </div>
    </div>

    {{-- Delete Account --}}
    <div class="col-lg-12">
        <div class="card border-0 shadow-sm border-start border-4 border-danger">
            <div class="card-body p-4">
                <h5 class="fw-bold text-danger mb-3">⚠️ Hapus Akun</h5>
                <p class="text-muted small mb-4">
                    Setelah akun dihapus, semua data akan terhapus permanen. Sebelum melanjutkan, pastikan Anda sudah backup data yang ingin disimpan.
                </p>

                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</div>

@endsection
