@extends('layouts.app')

@section('title', 'Laporan Kepatuhan Pasien')

@section('page-header')
    <nav aria-label="breadcrumb" class="small">
        <ol class="breadcrumb mb-2">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item active">Laporan Kepatuhan</li>
        </ol>
    </nav>
    <div class="d-flex justify-content-between align-items-end flex-wrap gap-3">
        <div>
            <h4 class="fw-bold mb-1"><i class="ri ri-bar-chart-line text-primary me-1"></i> Laporan Kepatuhan Pasien</h4>
            <small class="text-muted">
                Ringkasan kepatuhan minum obat & kontrol gula darah.
                Periode <strong>{{ \Illuminate\Support\Carbon::parse($report['periode']['start'])->translatedFormat('d M Y') }}</strong>
                s/d <strong>{{ \Illuminate\Support\Carbon::parse($report['periode']['end'])->translatedFormat('d M Y') }}</strong>.
            </small>
        </div>
        <form method="GET" class="d-flex align-items-end gap-2">
            <div>
                <label class="form-label small mb-1 text-muted">Dari</label>
                <input type="date" name="start" value="{{ $report['periode']['start'] }}" class="form-control form-control-sm">
            </div>
            <div>
                <label class="form-label small mb-1 text-muted">Sampai</label>
                <input type="date" name="end" value="{{ $report['periode']['end'] }}" class="form-control form-control-sm">
            </div>
            <button type="submit" class="btn btn-primary btn-sm"><i class="ri ri-filter-3-line me-1"></i> Terapkan</button>
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="ri ri-printer-line"></i></button>
        </form>
    </div>
@endsection

@push('styles')
<style>
    .stat-mini { border-radius: 12px; border: 1px solid #f0f1f3; background:#fff; padding: 1rem 1.25rem; }
    .stat-mini .v { font-size: 1.75rem; font-weight: 700; line-height: 1; color:#111827; }
    .stat-mini .l { font-size: .78rem; color:#6b7280; }
    .lvl-baik   { background:#ecfdf5; color:#047857; }
    .lvl-cukup  { background:#fffbeb; color:#b45309; }
    .lvl-kurang { background:#fef2f2; color:#b91c1c; }
    .prog { height: 8px; border-radius: 6px; background:#f3f4f6; overflow:hidden; }
    .prog > span { display:block; height:100%; border-radius:6px; }
    .empty-state { color:#9ca3af; }
    @media print { .sidebar, .btn, form, .breadcrumb { display:none !important; } body { margin:0 !important; } }
</style>
@endpush

@section('content')

@php
    $mo = $report['mo'];
    $cgd = $report['cgd'];
    $levelBadge = fn($lvl) => match($lvl) {
        'baik'   => '<span class="badge lvl-baik px-2 py-1">Baik</span>',
        'cukup'  => '<span class="badge lvl-cukup px-2 py-1">Cukup</span>',
        default  => '<span class="badge lvl-kurang px-2 py-1">Kurang</span>',
    };
    $progColor = fn($p) => $p >= 80 ? '#10b981' : ($p >= 50 ? '#f59e0b' : '#ef4444');
@endphp

{{-- ============ MINUM OBAT ============ --}}
<h6 class="fw-bold text-uppercase text-muted small mb-2"><i class="ri ri-capsule-line me-1"></i> Kepatuhan Minum Obat</h6>
<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3"><div class="stat-mini"><div class="l">Pasien Terpantau</div><div class="v text-primary">{{ $mo['jumlah_pasien'] }}</div></div></div>
    <div class="col-6 col-lg-3"><div class="stat-mini"><div class="l">Total Konfirmasi</div><div class="v">{{ number_format($mo['total']) }}</div></div></div>
    <div class="col-6 col-lg-3"><div class="stat-mini"><div class="l">Tepat Waktu</div><div class="v text-success">{{ number_format($mo['tepat']) }}</div></div></div>
    <div class="col-6 col-lg-3"><div class="stat-mini"><div class="l">Rata-rata Kepatuhan</div><div class="v" style="color:{{ $progColor($mo['persen']) }}">{{ $mo['persen'] }}%</div></div></div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-4">
        <x-card title="Distribusi Ketepatan" icon="ri-pie-chart-line">
            @if($mo['total'] > 0)
                <div style="position:relative;height:240px;"><canvas id="chartMo"></canvas></div>
            @else
                <div class="text-center py-5 empty-state"><i class="ri ri-inbox-line fs-1"></i><p class="mt-2 mb-0 small">Belum ada data</p></div>
            @endif
        </x-card>
    </div>
    <div class="col-lg-8">
        <x-card title="Kepatuhan per Pasien" icon="ri-user-heart-line">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Pasien</th>
                            <th class="text-center">Total</th>
                            <th class="text-center text-success">Tepat</th>
                            <th class="text-center text-warning">Telat</th>
                            <th class="text-center text-danger">Sangat Telat</th>
                            <th style="width:22%">Kepatuhan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($mo['rows'] as $r)
                            <tr>
                                <td class="fw-semibold">{{ $r['nama'] }}</td>
                                <td class="text-center">{{ $r['total'] }}</td>
                                <td class="text-center">{{ $r['tepat'] }}</td>
                                <td class="text-center">{{ $r['telat'] }}</td>
                                <td class="text-center">{{ $r['sangat'] }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="prog flex-grow-1"><span style="width:{{ $r['persen'] }}%;background:{{ $progColor($r['persen']) }}"></span></div>
                                        <span class="small fw-semibold" style="min-width:42px">{{ $r['persen'] }}%</span>
                                        {!! $levelBadge($r['level']) !!}
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center py-4 empty-state">Belum ada data konfirmasi minum obat pada periode ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>
</div>

{{-- ============ CEK GULA DARAH ============ --}}
<h6 class="fw-bold text-uppercase text-muted small mb-2"><i class="ri ri-test-tube-line me-1"></i> Kontrol Gula Darah</h6>
<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3"><div class="stat-mini"><div class="l">Pasien Tercatat</div><div class="v text-primary">{{ $cgd['jumlah_pasien'] }}</div></div></div>
    <div class="col-6 col-lg-3"><div class="stat-mini"><div class="l">Total Pemeriksaan</div><div class="v">{{ number_format($cgd['total']) }}</div></div></div>
    <div class="col-6 col-lg-3"><div class="stat-mini"><div class="l">Hasil Normal</div><div class="v text-success">{{ number_format($cgd['distribusi']['normal']) }}</div></div></div>
    <div class="col-6 col-lg-3"><div class="stat-mini"><div class="l">% Terkontrol</div><div class="v" style="color:{{ $progColor($cgd['persen']) }}">{{ $cgd['persen'] }}%</div></div></div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <x-card title="Distribusi Hasil CGD" icon="ri-pie-chart-line">
            @if($cgd['total'] > 0)
                <div style="position:relative;height:240px;"><canvas id="chartCgd"></canvas></div>
            @else
                <div class="text-center py-5 empty-state"><i class="ri ri-inbox-line fs-1"></i><p class="mt-2 mb-0 small">Belum ada data</p></div>
            @endif
        </x-card>
    </div>
    <div class="col-lg-8">
        <x-card title="Kontrol Gula Darah per Pasien" icon="ri-user-heart-line">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Pasien</th>
                            <th class="text-center">Cek</th>
                            <th class="text-center text-success">Normal</th>
                            <th class="text-center text-warning">Tidak Terkontrol</th>
                            <th class="text-center text-danger">Tinggi</th>
                            <th class="text-center">Berbahaya</th>
                            <th class="text-center">Rata-rata</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cgd['rows'] as $r)
                            <tr>
                                <td class="fw-semibold">{{ $r['nama'] }}</td>
                                <td class="text-center">{{ $r['total'] }}</td>
                                <td class="text-center">{{ $r['normal'] }}</td>
                                <td class="text-center">{{ $r['tidak_terkontrol'] }}</td>
                                <td class="text-center">{{ $r['tinggi'] }}</td>
                                <td class="text-center">{{ $r['berbahaya'] }}</td>
                                <td class="text-center"><span class="badge bg-light text-dark border">{{ $r['rata_mgdl'] }} mg/dL</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center py-4 empty-state">Belum ada data pemeriksaan gula darah pada periode ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>
</div>

@endsection

@push('scripts')
<script>
window.whenKesehatanReady(function() {
    const Chart = window.Chart;
    if (!Chart) return;

    const MO = @json($mo);
    const CGD = @json($cgd);

    const doughnutOpts = {
        responsive: true, maintainAspectRatio: false, cutout: '62%',
        plugins: { legend: { display: true, position: 'bottom',
            labels: { padding: 12, boxWidth: 8, boxHeight: 8, usePointStyle: true, pointStyle: 'circle' } } }
    };

    const moEl = document.getElementById('chartMo');
    if (moEl && MO.total > 0) {
        new Chart(moEl, {
            type: 'doughnut',
            data: {
                labels: ['Tepat Waktu', 'Telat', 'Sangat Telat'],
                datasets: [{ data: [MO.tepat, MO.telat, MO.sangat],
                    backgroundColor: ['#10b981', '#f59e0b', '#ef4444'], borderWidth: 0, spacing: 2 }]
            },
            options: doughnutOpts,
        });
    }

    const cgdEl = document.getElementById('chartCgd');
    if (cgdEl && CGD.total > 0) {
        const d = CGD.distribusi;
        new Chart(cgdEl, {
            type: 'doughnut',
            data: {
                labels: ['Normal', 'Tidak Terkontrol', 'Tinggi', 'Berbahaya'],
                datasets: [{ data: [d.normal, d.tidak_terkontrol, d.tinggi, d.berbahaya],
                    backgroundColor: ['#10b981', '#f59e0b', '#ef4444', '#111827'], borderWidth: 0, spacing: 2 }]
            },
            options: doughnutOpts,
        });
    }
});
</script>
@endpush
