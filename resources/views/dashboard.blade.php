@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')

{{-- Welcome Card --}}
<div class="card border-0 shadow-sm mb-4 brand-gradient text-white">
    <div class="card-body p-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h3 class="fw-bold mb-2">Halo, {{ auth()->user()->name }} 👋</h3>
                <p class="mb-0 opacity-90">
                    Selamat datang di dashboard Kesehatan. Mulai dengan menambahkan obat di Master Data, lalu atur jadwal pengingatnya.
                </p>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <a href="{{ route('master.obat') }}" class="btn btn-light fw-semibold">
                    💊 Kelola Obat
                </a>
            </div>
        </div>
    </div>
</div>

{{-- Stats --}}
<div class="row g-4 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm card-hover h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">Total Obat</div>
                <div class="display-stat fs-2 fw-bold text-primary">0</div>
                <div class="small text-muted">obat aktif</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm card-hover h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">Pengingat Aktif</div>
                <div class="display-stat fs-2 fw-bold text-success">0</div>
                <div class="small text-muted">jadwal terjadwal</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm card-hover h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">Hari Ini</div>
                <div class="display-stat fs-2 fw-bold text-warning">0</div>
                <div class="small text-muted">pengingat</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm card-hover h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">Kepatuhan</div>
                <div class="display-stat fs-2 fw-bold text-info">—</div>
                <div class="small text-muted">7 hari terakhir</div>
            </div>
        </div>
    </div>
</div>

{{-- Quick Access --}}
<div class="row g-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom-0 pt-3 pb-2">
                <h5 class="fw-bold mb-0">💼 Akses Cepat — Transaksi</h5>
            </div>
            <div class="card-body pt-2">
                <div class="list-group list-group-flush">
                    <a href="{{ route('transaksi.pengingat') }}" class="list-group-item list-group-item-action border-0 px-0 d-flex align-items-center gap-3">
                        <span style="font-size: 1.4rem;">⏰</span>
                        <div class="flex-grow-1">
                            <div class="fw-semibold">Pengingat Obat</div>
                            <small class="text-muted">Daftar pengingat aktif</small>
                        </div>
                        <span class="text-muted">›</span>
                    </a>
                    <a href="{{ route('transaksi.riwayat') }}" class="list-group-item list-group-item-action border-0 px-0 d-flex align-items-center gap-3">
                        <span style="font-size: 1.4rem;">📋</span>
                        <div class="flex-grow-1">
                            <div class="fw-semibold">Riwayat Minum Obat</div>
                            <small class="text-muted">Log konsumsi harian</small>
                        </div>
                        <span class="text-muted">›</span>
                    </a>
                    <a href="{{ route('transaksi.laporan') }}" class="list-group-item list-group-item-action border-0 px-0 d-flex align-items-center gap-3">
                        <span style="font-size: 1.4rem;">📊</span>
                        <div class="flex-grow-1">
                            <div class="fw-semibold">Laporan Kepatuhan</div>
                            <small class="text-muted">Analisis & statistik</small>
                        </div>
                        <span class="text-muted">›</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom-0 pt-3 pb-2">
                <h5 class="fw-bold mb-0">🗂️ Akses Cepat — Master</h5>
            </div>
            <div class="card-body pt-2">
                <div class="list-group list-group-flush">
                    <a href="{{ route('master.obat') }}" class="list-group-item list-group-item-action border-0 px-0 d-flex align-items-center gap-3">
                        <span style="font-size: 1.4rem;">💊</span>
                        <div class="flex-grow-1">
                            <div class="fw-semibold">Daftar Obat</div>
                            <small class="text-muted">Kelola data obat</small>
                        </div>
                        <span class="text-muted">›</span>
                    </a>
                    <a href="{{ route('master.jadwal_template') }}" class="list-group-item list-group-item-action border-0 px-0 d-flex align-items-center gap-3">
                        <span style="font-size: 1.4rem;">📋</span>
                        <div class="flex-grow-1">
                            <div class="fw-semibold">Template Jadwal</div>
                            <small class="text-muted">Template reusable</small>
                        </div>
                        <span class="text-muted">›</span>
                    </a>
                    <a href="{{ route('master.kontak') }}" class="list-group-item list-group-item-action border-0 px-0 d-flex align-items-center gap-3">
                        <span style="font-size: 1.4rem;">📞</span>
                        <div class="flex-grow-1">
                            <div class="fw-semibold">Kontak Darurat</div>
                            <small class="text-muted">Keluarga & dokter</small>
                        </div>
                        <span class="text-muted">›</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Tips --}}
<div class="card border-0 shadow-sm mt-4 border-start border-4 border-info">
    <div class="card-body">
        <h6 class="fw-bold mb-2">💡 Tips Memulai</h6>
        <ol class="mb-0 ps-3 small text-muted">
            <li>Tambahkan obat di <strong>Master → Daftar Obat</strong>.</li>
            <li>Buat template jadwal di <strong>Master → Template Jadwal</strong> untuk pengingat reusable.</li>
            <li>Aktifkan pengingat di <strong>Transaksi → Pengingat Obat</strong>.</li>
            <li>Pantau kepatuhan di <strong>Transaksi → Laporan Kepatuhan</strong>.</li>
        </ol>
    </div>
</div>

@endsection
