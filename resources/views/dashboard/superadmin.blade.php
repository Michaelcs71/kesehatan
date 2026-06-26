@extends('layouts.app')

@section('title', 'Dashboard Admin')

@section('page-header')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">
                👋 Halo, {{ auth()->user()->name }}!
            </h4>
            <small class="text-muted">
                @if (auth()->user()->isSuperadmin())
                    Anda login sebagai <strong class="text-danger">Superadmin</strong> — akses penuh ke seluruh sistem.
                @else
                    Anda login sebagai <strong class="text-primary">Admin</strong>.
                @endif
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

        .stat-icon-primary {
            background: #eff6ff;
            color: #3b82f6;
        }

        .stat-icon-info {
            background: #ecfeff;
            color: #06b6d4;
        }

        .stat-icon-success {
            background: #ecfdf5;
            color: #10b981;
        }

        .stat-icon-warning {
            background: #fffbeb;
            color: #f59e0b;
        }

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

        .stat-delta-up {
            background: #ecfdf5;
            color: #047857;
        }

        .stat-delta-down {
            background: #fef2f2;
            color: #b91c1c;
        }

        .stat-delta-flat {
            background: #f3f4f6;
            color: #6b7280;
        }

        .chart-card {
            background: #fff;
            border-radius: 12px;
            border: 1px solid #f3f4f6;
            padding: 1.5rem;
            height: 100%;
        }

        .chart-card-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: #111827;
            margin-bottom: 0.25rem;
        }

        .chart-card-subtitle {
            font-size: 0.8rem;
            color: #6b7280;
            margin-bottom: 1rem;
        }

        .chart-container {
            position: relative;
            width: 100%;
        }

        .chart-h-sm {
            height: 260px;
        }

        .chart-h-md {
            height: 320px;
        }

        .chart-h-lg {
            height: 360px;
        }

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
                    <span class="stat-delta {{ $stats['pasien']['trend'] === 'up' ? 'stat-delta-up' : 'stat-delta-down' }}">
                        <i class="ri ri-arrow-{{ $stats['pasien']['trend'] }}-line"></i>
                        {{ abs($stats['pasien']['delta']) }}%
                    </span>
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
                    <span class="stat-delta {{ $stats['pmo']['trend'] === 'up' ? 'stat-delta-up' : 'stat-delta-down' }}">
                        <i class="ri ri-arrow-{{ $stats['pmo']['trend'] }}-line"></i>
                        {{ abs($stats['pmo']['delta']) }}%
                    </span>
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
                    <span class="stat-delta {{ $stats['obat']['trend'] === 'up' ? 'stat-delta-up' : 'stat-delta-down' }}">
                        <i class="ri ri-arrow-{{ $stats['obat']['trend'] }}-line"></i>
                        {{ abs($stats['obat']['delta']) }}%
                    </span>
                </div>
                <div class="stat-value">{{ number_format($stats['obat']['value']) }}</div>
                <div class="stat-label mt-1">Master Obat</div>
            </div>
        </div>

        {{-- Pending --}}
        <div class="col-md-6 col-lg-3">
            <div class="stat-card shadow-sm p-4" style="cursor: pointer;"
                onclick="window.location.href='{{ url('master-obat?status=pending') }}'">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div class="stat-icon stat-icon-warning">
                        <i class="ri ri-time-line"></i>
                    </div>
                    <span
                        class="stat-delta {{ $stats['pending']['trend'] === 'down' ? 'stat-delta-down' : 'stat-delta-up' }}">
                        <i class="ri ri-arrow-{{ $stats['pending']['trend'] }}-line"></i>
                        {{ abs($stats['pending']['delta']) }}%
                    </span>
                </div>
                <div class="stat-value">{{ number_format($stats['pending']['value']) }}</div>
                <div class="stat-label mt-1">Pending Verifikasi</div>
            </div>
        </div>
    </div>

    {{-- ============ ROW 1: TREND CGD + DISTRIBUSI KATEGORI ============ --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="chart-card shadow-sm">
                <div class="d-flex justify-content-between align-items-start mb-1">
                    <div>
                        <div class="chart-card-title">📈 Trend Log Gula Darah</div>
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

    {{-- ============ ROW 2: COMPLIANCE + TOP 5 PASIEN ============ --}}
    {{-- TODO: Compliance chart menunggu sumber data nyata --}}
    @if(false)
    <div class="row g-3 mb-4">
        <div class="col-lg-7">
            <div class="chart-card shadow-sm">
                <div class="chart-card-title">💊 Compliance Minum Obat</div>
                <div class="chart-card-subtitle">Ketepatan waktu minum obat (per minggu)</div>
                <div class="chart-container chart-h-md">
                    <canvas id="chartCompliance"></canvas>
                </div>
            </div>
        </div>

        {{-- TODO: Top 5 Pasien chart menunggu sumber data nyata --}}
        <div class="col-lg-5">
            <div class="chart-card shadow-sm">
                <div class="chart-card-title">🏆 Top 5 Pasien Paling Aktif</div>
                <div class="chart-card-subtitle">Jumlah log dalam 30 hari</div>
                <div class="chart-container chart-h-md">
                    <canvas id="chartTopPasien"></canvas>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- TODO: User Growth chart menunggu sumber data nyata --}}
    @if(false)
    {{-- ============ ROW 3: USER GROWTH (FULL WIDTH) ============ --}}
    <div class="row g-3">
        <div class="col-12">
            <div class="chart-card shadow-sm">
                <div class="d-flex justify-content-between align-items-start mb-1">
                    <div>
                        <div class="chart-card-title">📊 Pertumbuhan Pengguna</div>
                        <div class="chart-card-subtitle">6 bulan terakhir</div>
                    </div>
                    <div class="d-flex gap-3">
                        <small><span class="legend-dot" style="background:#3b82f6"></span>Pasien</small>
                        <small><span class="legend-dot" style="background:#06b6d4"></span>PMO</small>
                    </div>
                </div>
                <div class="chart-container chart-h-lg">
                    <canvas id="chartUserGrowth"></canvas>
                </div>
            </div>
        </div>
    </div>
    @endif

@endsection

@push('scripts')
    <script>
        window.whenKesehatanReady(function() {
            const Chart = window.Chart;

            // ===========================================
            // GLOBAL CHART.JS CONFIG (Modern Minimalist)
            // ===========================================
            Chart.defaults.font.family = "'Inter', system-ui, -apple-system, sans-serif";
            Chart.defaults.font.size = 12;
            Chart.defaults.color = '#6b7280';
            Chart.defaults.plugins.legend.display = false;
            Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(17, 24, 39, 0.95)';
            Chart.defaults.plugins.tooltip.padding = 12;
            Chart.defaults.plugins.tooltip.cornerRadius = 8;
            Chart.defaults.plugins.tooltip.titleFont = {
                size: 13,
                weight: '600'
            };
            Chart.defaults.plugins.tooltip.bodyFont = {
                size: 12
            };

            const PALETTE = {
                primary: '#3b82f6',
                info: '#06b6d4',
                success: '#10b981',
                warning: '#f59e0b',
                danger: '#ef4444',
                dark: '#111827',
                muted: '#9ca3af',
                primaryFade: 'rgba(59, 130, 246, 0.1)',
                infoFade: 'rgba(6, 182, 212, 0.1)',
            };

            // 1️⃣ TREND CGD (30 hari) - Line chart — data dari server
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
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: '#f3f4f6',
                                drawBorder: false
                            },
                            ticks: {
                                padding: 8
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                maxRotation: 0,
                                autoSkip: true,
                                maxTicksLimit: 10
                            }
                        }
                    }
                }
            });

            // 2️⃣ DISTRIBUSI KATEGORI CGD - Doughnut
            // Plugin: tampilkan total di tengah donut, tween angka saat
            // segmen di-hide/show lewat legend (easeOutCubic ~600ms).
            const doughnutCenterText = {
                id: 'doughnutCenterText',
                afterDraw(chart, _args, opts) {
                    const {
                        ctx,
                        chartArea
                    } = chart;
                    if (!chartArea) return;

                    // Total dari segmen yang sedang TERLIHAT saja
                    const values = chart.data.datasets[0].data;
                    let target = 0;
                    values.forEach((v, i) => {
                        if (chart.getDataVisibility(i)) target += Number(v) || 0;
                    });

                    // State tween per-chart
                    const st = chart.$centerTween ||
                        (chart.$centerTween = {
                            cur: target,
                            from: target,
                            to: target,
                            start: 0,
                            dur: 0
                        });

                    if (st.to !== target) {
                        st.from = st.cur;
                        st.to = target;
                        st.start = performance.now();
                        st.dur = 600;
                    }

                    if (st.dur > 0) {
                        const t = Math.min(1, (performance.now() - st.start) / st.dur);
                        const eased = 1 - Math.pow(1 - t, 3); // easeOutCubic
                        st.cur = st.from + (st.to - st.from) * eased;
                        if (t < 1) {
                            requestAnimationFrame(() => chart.draw());
                        } else {
                            st.cur = st.to;
                            st.dur = 0;
                        }
                    }

                    const cx = (chartArea.left + chartArea.right) / 2;
                    const cy = (chartArea.top + chartArea.bottom) / 2;
                    const value = Math.round(st.cur).toLocaleString('id-ID');

                    ctx.save();
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';

                    ctx.fillStyle = PALETTE.dark;
                    ctx.font = "700 30px 'Inter', system-ui, sans-serif";
                    ctx.fillText(value, cx, cy - 8);

                    ctx.fillStyle = PALETTE.muted;
                    ctx.font = "500 12px 'Inter', system-ui, sans-serif";
                    ctx.fillText('Total Log', cx, cy + 16);

                    ctx.restore();
                }
            };

            // 2️⃣ DISTRIBUSI KATEGORI CGD — data dari server
            const distribusi = @json($distribusi_kategori);
            const distData = [distribusi.normal ?? 0, distribusi.tidak_terkontrol ?? 0, distribusi.tinggi ?? 0, distribusi.berbahaya ?? 0];

            if (document.getElementById('chartKategoriCgd')) {
            new Chart(document.getElementById('chartKategoriCgd'), {
                type: 'doughnut',
                data: {
                    labels: ['Normal', 'Tidak Terkontrol', 'Tinggi', 'Berbahaya'],
                    datasets: [{
                        data: distData,
                        backgroundColor: [PALETTE.success, PALETTE.warning, PALETTE.danger, PALETTE
                            .dark
                        ],
                        borderWidth: 0,
                        spacing: 2,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    animation: {
                        animateRotate: true,
                        animateScale: false
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom',
                            labels: {
                                padding: 12,
                                boxWidth: 8,
                                boxHeight: 8,
                                usePointStyle: true,
                                pointStyle: 'circle',
                            }
                        }
                    }
                },
                plugins: [doughnutCenterText]
            });
            } // end if chartKategoriCgd

            // 3️⃣ COMPLIANCE MINUM OBAT - Stacked Bar (disabled — menunggu sumber data nyata)
            if (document.getElementById('chartCompliance')) {
            new Chart(document.getElementById('chartCompliance'), {
                type: 'bar',
                data: {
                    labels: ['Minggu 1', 'Minggu 2', 'Minggu 3', 'Minggu 4'],
                    datasets: [{
                            label: 'Tepat Waktu',
                            data: [180, 195, 210, 225],
                            backgroundColor: PALETTE.success,
                            borderRadius: {
                                topLeft: 8,
                                topRight: 8
                            },
                            borderSkipped: false,
                        },
                        {
                            label: 'Telat',
                            data: [45, 38, 32, 28],
                            backgroundColor: PALETTE.warning,
                        },
                        {
                            label: 'Sangat Telat',
                            data: [25, 18, 15, 12],
                            backgroundColor: PALETTE.danger,
                            borderRadius: {
                                bottomLeft: 8,
                                bottomRight: 8
                            },
                            borderSkipped: false,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom',
                            labels: {
                                padding: 12,
                                boxWidth: 8,
                                boxHeight: 8,
                                usePointStyle: true,
                            }
                        }
                    },
                    scales: {
                        x: {
                            stacked: true,
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            stacked: true,
                            beginAtZero: true,
                            grid: {
                                color: '#f3f4f6',
                                drawBorder: false
                            }
                        }
                    }
                }
            });
            } // end if chartCompliance

            // 4️⃣ TOP 5 PASIEN PALING AKTIF - Horizontal Bar (disabled — menunggu sumber data nyata)
            if (document.getElementById('chartTopPasien')) {
            new Chart(document.getElementById('chartTopPasien'), {
                type: 'bar',
                data: {
                    labels: ['Siti Aminah', 'Budi Santoso', 'Rini Wijaya', 'Agus Setiawan', 'Dewi Lestari'],
                    datasets: [{
                        label: 'Jumlah Log',
                        data: [58, 52, 47, 41, 38],
                        backgroundColor: [
                            PALETTE.primary, PALETTE.info, PALETTE.success, PALETTE.warning,
                            PALETTE.muted,
                        ],
                        borderRadius: 6,
                        borderSkipped: false,
                        barThickness: 22,
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            beginAtZero: true,
                            grid: {
                                color: '#f3f4f6',
                                drawBorder: false
                            }
                        },
                        y: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
            } // end if chartTopPasien

            // 5️⃣ USER GROWTH 6 BULAN - Multi-line Area (disabled — menunggu sumber data nyata)
            if (document.getElementById('chartUserGrowth')) {
            new Chart(document.getElementById('chartUserGrowth'), {
                type: 'line',
                data: {
                    labels: ['Des 2025', 'Jan 2026', 'Feb 2026', 'Mar 2026', 'Apr 2026', 'Mei 2026'],
                    datasets: [{
                            label: 'Pasien',
                            data: [120, 145, 168, 195, 220, 248],
                            borderColor: PALETTE.primary,
                            backgroundColor: PALETTE.primaryFade,
                            borderWidth: 2.5,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 4,
                            pointBackgroundColor: PALETTE.primary,
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                        },
                        {
                            label: 'PMO',
                            data: [42, 51, 58, 67, 78, 89],
                            borderColor: PALETTE.info,
                            backgroundColor: PALETTE.infoFade,
                            borderWidth: 2.5,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 4,
                            pointBackgroundColor: PALETTE.info,
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: '#f3f4f6',
                                drawBorder: false
                            },
                            ticks: {
                                padding: 8
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
            } // end if chartUserGrowth
        });
    </script>
@endpush
