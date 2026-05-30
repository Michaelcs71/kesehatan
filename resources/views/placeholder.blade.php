@extends('layouts.app')

@php
    $meta = request()->route()->defaults['meta'] ?? [];
    $title  = $meta['title']  ?? 'Halaman';
    $group  = $meta['group']  ?? 'Menu';
    $icon   = $meta['icon']   ?? '📄';
    $access = $meta['access'] ?? 'R';
@endphp

@section('title', $title)

@section('page-header')
    <nav aria-label="breadcrumb" class="small">
        <ol class="breadcrumb mb-2">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item">{{ $group }}</li>
            <li class="breadcrumb-item active">{{ $title }}</li>
        </ol>
    </nav>
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">{{ $icon }} {{ $title }}</h4>
            <small class="text-muted">
                Access level Anda: <span class="badge bg-info-subtle text-info">{{ $access }}</span>
            </small>
        </div>
    </div>
@endsection

@section('content')

<div class="card border-0 shadow-sm">
    <div class="card-body p-5 text-center">
        <div style="font-size: 4rem;">{{ $icon }}</div>
        <h5 class="fw-bold mt-3">{{ $title }}</h5>
        <p class="text-muted">
            Modul ini akan dibangun di Fase berikutnya.
        </p>
        <div class="mt-3">
            <span class="badge bg-info-subtle text-info px-3 py-2 me-1">Route: <code>{{ Route::currentRouteName() }}</code></span>
            <span class="badge bg-secondary-subtle text-secondary px-3 py-2">Role: <code>{{ auth()->user()->role->label() }}</code></span>
            <span class="badge bg-warning-subtle text-warning px-3 py-2">Access: <code>{{ $access }}</code></span>
        </div>
    </div>
</div>

@endsection
