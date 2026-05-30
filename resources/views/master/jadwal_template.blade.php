@extends('layouts.app')

@section('title', 'Template Jadwal')

@section('page-header')
    <nav aria-label="breadcrumb" class="small">
        <ol class="breadcrumb mb-2">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item">Master</li>
            <li class="breadcrumb-item active" aria-current="page">Template Jadwal</li>
        </ol>
    </nav>
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">📋 Template Jadwal</h4>
            <small class="text-muted">Template jadwal pengingat yang reusable.</small>
        </div>
        <div>
            <button class="btn btn-primary fw-semibold" disabled>
                ➕ Tambah Baru <small class="opacity-75">(Soon)</small>
            </button>
        </div>
    </div>
@endsection

@section('content')

<div class="card border-0 shadow-sm">
    <div class="card-body p-5 text-center">
        <div style="font-size: 4rem;">📋</div>
        <h5 class="fw-bold mt-3">Template Jadwal</h5>
        <p class="text-muted mb-4">
            Halaman ini masih placeholder.<br>
            Silakan custom sesuai kebutuhan Anda.
        </p>
        <div>
            <span class="badge bg-info-subtle text-info px-3 py-2">
                Route: <code>{{ Route::currentRouteName() }}</code>
            </span>
        </div>
    </div>
</div>

@endsection
