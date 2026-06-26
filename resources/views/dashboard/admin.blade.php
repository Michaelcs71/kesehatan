@extends('layouts.app')

@section('title', 'Dashboard Admin')

@section('page-header')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">
                👋 Halo, {{ auth()->user()->name }}!
            </h4>
            <small class="text-muted">
                Anda login sebagai <strong class="text-primary">Admin</strong>.
            </small>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-light text-dark border px-3 py-2">
                <i class="ri ri-calendar-line me-1"></i>
                30 Hari Terakhir
            </span>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        /* === MODERN MINIMALIST DASHBOARD === */
        .stat-card {
            transition: all 0.25s ease;
            border-radius: 12px;
            background: #fff;
            border: 1px solid #f3f4f6 !important;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06) !important;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
        }

        .stat-icon-primary { background: #eff6ff; color: #3b82f6; }
        .stat-icon-info    { background: #ecfeff; color: #06b6d4; }
        .stat-icon-success { background: #ecfdf5; color: #10b981; }
        .stat-icon-warning { background: #fffbeb; color: #f59e0b; }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            line-height: 1;
            color: #111827;
            letter-spacing: -0.02em;
        }

        .stat-label {
            color: #6b7280;
            font-size: 0.825rem;
            font-weight: 500;
        }

        .stat-delta {
            display: inline-flex;
            align-items: center;
            gap: 2px;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 6px;
        }

        .stat-delta-up   { background: #ecfdf5; color: #047857; }
        .stat-delta-down { background: #fef2f2; color: #b91c1c; }
        .stat-delta-flat { background: #f3f4f6; color: #6b7280; }

        .chart-card {
            background: #fff;
            border-radius: 12px;
            border: 1px solid #f3f4f6;
            padding: 1.5rem;
            height: 100%;
        }

        .chart-card-title    { font-size: 0.95rem; font-weight: 700; color: #111827; margin-bottom: 0.25rem; }
        .chart-card-subtitle { font-size: 0.8rem; color: #6b7280; margin-bottom: 1rem; }

        .chart-container { position: relative; width: 100%; }
        .chart-h-sm { height: 260px; }
        .chart-h-md { height: 320px; }
        .chart-h-lg { height: 360px; }

        .legend-dot {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 6px;
            vertical-align: middle;
        }
    </style>
@endpush

@section('content')

    @php
        $stats = [
            'pasien'  => ['value' => $total_pasien, 'delta' => 0, 'trend' => 'up'],
            'pmo'     => ['value' => $total_pmo, 'delta' => 0, 'trend' => 'up'],
            'obat'    => ['value' => $total_obat, 'delta' => 0, 'trend' => 'up'],
            'pending' => ['value' => $perlu_tindak_lanjut, 'delta' => 0, 'trend' => 'down'],
        ];
    @endphp

    {{-- ============ STAT CARDS ============ --}}
    <div class="row g-3 mb-4">
        {{-- Pasien --}}
        <div class="col-md-6 col-lg-3">
            <div class="stat-card shadow-sm p-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div class="stat-icon stat-icon-primary">
                        <i class="ri ri-user-heart-line"></i>
                    </div>
                    <span class="stat-delta stat-delta-flat">—</span>
                </div>
                <div class="stat-value">{{ number_format($stats['pasien']['value']) }}</div>
                <div class="stat-label mt-1">Total Pasien</div>
            </div>
        </div>

        {{-- PMO --}}
        <div class="col-md-6 col-lg-3">
            <div class="stat-card shadow-sm p-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div class="stat-icon stat-icon-info">
                        <i class="ri ri-team-line"></i>
                    </div>
                    <span class="stat-delta stat-delta-flat">—</span>
                </div>
                <div class="stat-value">{{ number_format($stats['pmo']['value']) }}</div>
                <div class="stat-label mt-1">Total PMO</div>
            </div>
        </div>

        {{-- Master Obat --}}
        <div class="col-md-6 col-lg-3">
            <div class="stat-card shadow-sm p-4" style="cursor: pointer;"
                onclick="window.location.href='{{ url('master-obat') }}'">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div class="stat-icon stat-icon-success">
                        <i class="ri ri-medicine-bottle-line"></i>
                    </div>
                    <span class="stat-delta stat-delta-flat">—</span>
                </div>
                <div class="stat-value">{{ number_format($stats['obat']['value']) }}</div>
                <div class="stat-label mt-1">Master Obat</div>
            </div>
        </div>

        {{-- Perlu Tindak Lanjut --}}
        <div class="col-md-6 col-lg-3">
            <div class="stat-card shadow-sm p-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div class="stat-icon stat-icon-warning">
                        <i class="ri ri-time-line"></i>
                    </div>
                    <span class="stat-delta stat-delta-flat">—</span>
                </div>
                <div class="stat-value">{{ number_format($stats['pending']['value']) }}</div>
                <div class="stat-label mt-1">Perlu Tindak Lanjut</div>
            </div>
        </div>
    </div>

    {{-- ============ ROW 1: TREND CGD + DISTRIBUSI KATEGORI ============ --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="chart-card shadow-sm">
                <div class="d-flex justify-content-between align-items-start mb-1">
                    <div>
                        <div class="chart-card-title">📈 Tren Log Gula Darah</div>
                        <div class="chart-card-subtitle">30 hari terakhir</div>
                    </div>
                    <div class="d-flex gap-3">
                        <small><span class="legend-dot" style="background:#3b82f6"></span>Total Log</small>
                    </div>
                </div>
                <div class="chart-container chart-h-md">
                    <canvas id="chartTrendCgd"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="chart-card shadow-sm">
                <div class="chart-card-title">🍩 Distribusi Hasil CGD</div>
                <div class="chart-card-subtitle">Berdasarkan kategori</div>
                @if(array_sum($distribusi_kategori) === 0)
                    <div class="chart-container chart-h-md d-flex align-items-center justify-content-center">
                        <div class="text-center text-muted">
                            <div style="font-size:2.5rem;">📊</div>
                            <p class="mb-0 mt-2 small">Belum ada data CGD untuk ditampilkan.</p>
                        </div>
                    </div>
                @else
                    <div class="chart-container chart-h-md">
                        <canvas id="chartKategoriCgd"></canvas>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ============ ROW 2: AKTIVITAS TERBARU ============ --}}
    <div class="row g-3">
        <div class="col-12">
            <div class="chart-card shadow-sm">
                <div class="chart-card-title">🕐 Aktivitas Terbaru Pasien</div>
                <div class="chart-card-subtitle">Log minum obat 10 terbaru</div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nama Pasien</th>
                                <th>Aktivitas</th>
                                <th>Waktu</th>
                                <th>Tanggal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($aktivitas_terbaru as $item)
                                <tr>
                                    <td>{{ $item['nama'] }}</td>
                                    <td>{{ $item['aksi'] }}</td>
                                    <td>{{ $item['waktu'] }}</td>
                                    <td>{{ $item['tgl'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        Belum ada aktivitas yang tercatat.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        window.whenKesehatanReady(function() {
            const Chart = window.Chart;

            Chart.defaults.font.family = "'Inter', system-ui, -apple-system, sans-serif";
            Chart.defaults.font.size = 12;
            Chart.defaults.color = '#6b7280';
            Chart.defaults.plugins.legend.display = false;
            Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(17, 24, 39, 0.95)';
            Chart.defaults.plugins.tooltip.padding = 12;
            Chart.defaults.plugins.tooltip.cornerRadius = 8;
            Chart.defaults.plugins.tooltip.titleFont = { size: 13, weight: '600' };
            Chart.defaults.plugins.tooltip.bodyFont = { size: 12 };

            const PALETTE = {
                primary: '#3b82f6',
                info: '#06b6d4',
                success: '#10b981',
                warning: '#f59e0b',
                danger: '#ef4444',
                dark: '#111827',
                muted: '#9ca3af',
                primaryFade: 'rgba(59, 130, 246, 0.1)',
            };

            // 1️⃣ TREND CGD (30 hari) — data dari server
            const trendRows = @json($tren_30hari);
            const trendData = trendRows.map(r => r.jml);
            const trendLabels = trendRows.map(r => r.tgl);

            new Chart(document.getElementById('chartTrendCgd'), {
                type: 'line',
                data: {
                    labels: trendLabels,
                    datasets: [{
                        label: 'Log CGD',
                        data: trendData,
                        borderColor: PALETTE.primary,
                        backgroundColor: PALETTE.primaryFade,
                        borderWidth: 2.5,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 0,
                        pointHoverRadius: 5,
                        pointBackgroundColor: PALETTE.primary,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: '#f3f4f6', drawBorder: false },
                            ticks: { padding: 8 }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 10 }
                        }
                    }
                }
            });

            // 2️⃣ DISTRIBUSI KATEGORI CGD — data dari server
            if (document.getElementById('chartKategoriCgd')) {
                const distribusi = @json($distribusi_kategori);
                const distData = [distribusi.normal ?? 0, distribusi.tidak_terkontrol ?? 0, distribusi.tinggi ?? 0, distribusi.berbahaya ?? 0];

                new Chart(document.getElementById('chartKategoriCgd'), {
                    type: 'doughnut',
                    data: {
                        labels: ['Normal', 'Tidak Terkontrol', 'Tinggi', 'Berbahaya'],
                        datasets: [{
                            data: distData,
                            backgroundColor: [PALETTE.success, PALETTE.warning, PALETTE.danger, PALETTE.dark],
                            borderWidth: 0,
                            spacing: 2,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '70%',
                        animation: { animateRotate: true, animateScale: false },
                        plugins: {
                            legend: {
                                display: true,
                                position: 'bottom',
                                labels: { padding: 12, boxWidth: 8, boxHeight: 8, usePointStyle: true, pointStyle: 'circle' }
                            }
                        }
                    }
                });
            }
        });
    </script>
@endpush
