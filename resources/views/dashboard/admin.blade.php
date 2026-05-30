@extends('layouts.app')

@section('title', 'Dashboard Pasien')

@section('page-header')
    <div>
        <h4 class="fw-bold mb-1">
            👋 Halo, {{ auth()->user()->name }}!
        </h4>
        <small class="text-muted">Semoga harimu sehat dan tetap konsisten minum obat.</small>
    </div>
@endsection

@section('content')

    {{-- Stats Cards --}}
    <div class="row g-3 mb-4">
        {{-- Obat Hari Ini --}}
        <div class="col-md-6 col-lg-3">
            <x-card>
                <div class="text-muted small mb-1">💊 Obat Hari Ini</div>
                <div class="display-stat fs-2 text-success">0</div>
                <small class="text-muted">jadwal</small>
            </x-card>
        </div>

        {{-- Cek GD Hari Ini --}}
        <div class="col-md-6 col-lg-3">
            <x-card>
                <div class="text-muted small mb-1">🩸 Cek GD Hari Ini</div>
                <div class="display-stat fs-2 text-info">0</div>
                <small class="text-muted">jadwal</small>
            </x-card>
        </div>

        {{-- Kepatuhan --}}
        <div class="col-md-6 col-lg-3">
            <x-card>
                <div class="text-muted small mb-1">📊 Kepatuhan</div>
                <div class="progress my-2" style="height: 8px;">
                    <div class="progress-bar bg-warning" style="width: 0%"></div>
                </div>
                <small class="text-muted">7 hari terakhir</small>
            </x-card>
        </div>

        {{-- PMO Saya --}}
        <div class="col-md-6 col-lg-3">
            <x-card>
                <div class="text-muted small mb-1">👥 PMO Saya</div>
                <div class="fw-bold text-primary">
                    {{ auth()->user()->pasienProfile?->pmo?->name ?? 'Belum ada' }}
                </div>
                <small class="text-muted">pendamping minum obat</small>
            </x-card>
        </div>
    </div>

    {{-- Main Content Area --}}
    <div class="row g-3">
        {{-- Jadwal Hari Ini --}}
        <div class="col-lg-8">
            <x-card title="Jadwal Hari Ini" icon="ri-calendar-line">
                <div class="text-center text-muted py-5">
                    <div style="font-size: 4rem;">📭</div>
                    <p class="mb-0 mt-3">Belum ada jadwal pengingat hari ini.</p>
                    <small>Hubungi admin atau tambahkan jadwal di menu Jadwal Saya.</small>
                </div>
            </x-card>
        </div>

        {{-- Tips Diabetes --}}
        <div class="col-lg-4">
            <x-card title="Tips Diabetes" icon="ri-lightbulb-line" class="border-start border-4 border-info">
                <ul class="mb-0 ps-3">
                    <li class="mb-2">Konsumsi obat tepat waktu sesuai resep dokter.</li>
                    <li class="mb-2">Cek gula darah rutin sesuai jadwal.</li>
                    <li class="mb-2">Jaga pola makan rendah gula & karbo.</li>
                    <li class="mb-0">Olahraga ringan minimal 30 menit/hari.</li>
                </ul>
            </x-card>
        </div>
    </div>

@endsection
