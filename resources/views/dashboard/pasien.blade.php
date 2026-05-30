@extends('layouts.app')

@section('title', 'Dashboard Pasien')

@section('page-header')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">
                👋 Halo, {{ auth()->user()->name }}!
            </h4>
            <small class="text-muted">Semoga harimu sehat dan tetap konsisten minum obat.</small>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-light text-dark border px-3 py-2">
                <i class="ri ri-calendar-line me-1"></i>
                {{ \Carbon\Carbon::now()->locale('id')->isoFormat('dddd, D MMMM YYYY') }}
            </span>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        /* === DASHBOARD PASIEN — Caring & Clean === */
        .stat-card {
            transition: all 0.25s ease;
            border-radius: 14px;
            background: #fff;
            border: 1px solid #f3f4f6 !important;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06) !important;
        }

        .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
        }

        .stat-icon-success {
            background: #ecfdf5;
            color: #10b981;
        }

        .stat-icon-info {
            background: #ecfeff;
            color: #06b6d4;
        }

        .stat-icon-warning {
            background: #fffbeb;
            color: #f59e0b;
        }

        .stat-icon-danger {
            background: #fef2f2;
            color: #ef4444;
        }

        .stat-icon-primary {
            background: #eff6ff;
            color: #3b82f6;
        }

        .stat-value {
            font-size: 1.75rem;
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

        .streak-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: linear-gradient(135deg, #f59e0b, #f97316);
            color: white;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        /* Timeline Jadwal */
        .timeline-item {
            display: flex;
            gap: 14px;
            padding: 14px 0;
            border-bottom: 1px solid #f3f4f6;
        }

        .timeline-item:last-child {
            border-bottom: 0;
        }

        .timeline-time {
            min-width: 64px;
            text-align: center;
            font-weight: 700;
            color: #111827;
            font-size: 0.95rem;
        }

        .timeline-time small {
            display: block;
            color: #9ca3af;
            font-weight: 500;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .timeline-content {
            flex: 1;
            background: #f9fafb;
            padding: 12px 16px;
            border-radius: 10px;
            border-left: 3px solid #3b82f6;
        }

        .timeline-content.done {
            background: #ecfdf5;
            border-left-color: #10b981;
            opacity: 0.7;
        }

        .timeline-content.missed {
            background: #fef2f2;
            border-left-color: #ef4444;
        }

        .timeline-content.upcoming {
            background: #fffbeb;
            border-left-color: #f59e0b;
        }

        .timeline-title {
            font-weight: 600;
            color: #111827;
            font-size: 0.9rem;
            margin-bottom: 2px;
        }

        .timeline-meta {
            color: #6b7280;
            font-size: 0.8rem;
        }

        .timeline-status {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 0.7rem;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 6px;
            margin-top: 4px;
        }

        /* PMO Card */
        .pmo-card {
            background: linear-gradient(135deg, #eff6ff, #ecfeff);
            border-radius: 14px;
            padding: 1.25rem;
            border: 1px solid #e0f2fe;
        }

        .pmo-avatar {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: #3b82f6;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.25rem;
        }

        /* Compliance Ring */
        .compliance-ring {
            position: relative;
            width: 140px;
            height: 140px;
            margin: 0 auto;
        }

        .compliance-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
        }

        .compliance-text .value {
            font-size: 1.75rem;
            font-weight: 700;
            color: #111827;
            line-height: 1;
        }

        .compliance-text .label {
            font-size: 0.7rem;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Tips card */
        .tips-card {
            background: linear-gradient(135deg, #fef3c7, #fed7aa);
            border-radius: 14px;
            padding: 1.25rem;
            border: none;
        }

        .tips-card .tip-item {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
            font-size: 0.875rem;
            color: #92400e;
        }

        .tips-card .tip-item:last-child {
            margin-bottom: 0;
        }

        /* Chart card */
        .chart-card {
            background: #fff;
            border-radius: 14px;
            border: 1px solid #f3f4f6;
            padding: 1.25rem;
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
            height: 220px;
        }

        /* Week tracker — vertical timeline */
        .week-tracker-vertical {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .day-row {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            border-radius: 10px;
            background: #f9fafb;
            transition: all 0.2s ease;
            border-left: 3px solid #e5e7eb;
        }

        .day-row.done {
            background: #ecfdf5;
            border-left-color: #10b981;
        }

        .day-row.partial {
            background: #fffbeb;
            border-left-color: #f59e0b;
        }

        .day-row.missed {
            background: #fef2f2;
            border-left-color: #ef4444;
        }

        .day-row.today {
            background: #eff6ff;
            border-left-color: #3b82f6;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.15);
        }

        .day-row.future {
            opacity: 0.5;
            background: #f9fafb;
            border-left-color: #e5e7eb;
        }

        .day-info {
            flex: 1;
            min-width: 0;
        }

        .day-label {
            font-weight: 700;
            font-size: 0.875rem;
            color: #111827;
            line-height: 1;
            margin-bottom: 3px;
        }

        .day-label .day-date-small {
            font-weight: 500;
            color: #6b7280;
            font-size: 0.75rem;
            margin-left: 4px;
        }

        .day-summary {
            font-size: 0.75rem;
            color: #6b7280;
            display: flex;
            gap: 10px;
        }

        .day-summary .metric {
            display: inline-flex;
            align-items: center;
            gap: 3px;
        }

        .day-status-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.875rem;
            flex-shrink: 0;
        }

        .day-status-icon.done {
            background: #10b981;
            color: white;
        }

        .day-status-icon.partial {
            background: #f59e0b;
            color: white;
        }

        .day-status-icon.missed {
            background: #ef4444;
            color: white;
        }

        .day-status-icon.today {
            background: #3b82f6;
            color: white;
            animation: pulse 2s infinite;
        }

        .day-status-icon.future {
            background: #e5e7eb;
            color: #9ca3af;
        }

        @keyframes pulse {

            0%,
            100% {
                box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.5);
            }

            50% {
                box-shadow: 0 0 0 6px rgba(59, 130, 246, 0);
            }
        }

        .tracker-summary {
            background: #f9fafb;
            border-radius: 10px;
            padding: 10px 14px;
            margin-top: 12px;
            display: flex;
            justify-content: space-around;
            text-align: center;
        }

        .tracker-summary .summary-item {
            font-size: 0.7rem;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        .tracker-summary .summary-value {
            display: block;
            font-size: 1.1rem;
            font-weight: 700;
            color: #111827;
            margin-top: 2px;
        }

        /* Announcement */
        .announcement-item {
            display: flex;
            gap: 12px;
            padding: 12px;
            border-radius: 10px;
            background: #f9fafb;
            margin-bottom: 8px;
            transition: all 0.2s ease;
        }

        .announcement-item:hover {
            background: #f3f4f6;
            cursor: pointer;
        }

        .announcement-item:last-child {
            margin-bottom: 0;
        }

        .announcement-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: #fef3c7;
            color: #d97706;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            flex-shrink: 0;
        }

        .announcement-title {
            font-weight: 600;
            font-size: 0.875rem;
            color: #111827;
            margin-bottom: 2px;
        }

        .announcement-meta {
            font-size: 0.75rem;
            color: #6b7280;
        }
    </style>
@endpush

@section('content')

    @php
        // ===========================================
        // DUMMY DATA — Replace dengan real query
        // ===========================================
        $obatHariIni = 3;
        $obatSelesai = 2;
        $cgdHariIni = 1;
        $cgdSelesai = 0;
        $kepatuhan = 86; // %
        $streak = 12; // hari berturut-turut

        $pmoName = auth()->user()->pasienProfile?->pmo?->name ?? 'Budi Santoso';
        $pmoHubungan = 'Anak';
        $pmoWhatsapp = '081234567892';

        // Jadwal hari ini (dummy)
        $jadwalHariIni = [
            [
                'waktu' => '07:00',
                'periode' => 'Pagi',
                'jenis' => 'obat',
                'nama' => 'Metformin 500mg',
                'dosis' => '1 tablet sesudah makan',
                'status' => 'done',
            ],
            [
                'waktu' => '07:30',
                'periode' => 'Pagi',
                'jenis' => 'cgd',
                'nama' => 'Cek Gula Darah Puasa',
                'dosis' => 'Sebelum sarapan',
                'status' => 'done',
            ],
            [
                'waktu' => '12:00',
                'periode' => 'Siang',
                'jenis' => 'obat',
                'nama' => 'Glimepiride 2mg',
                'dosis' => '1 tablet sesudah makan siang',
                'status' => 'done',
            ],
            [
                'waktu' => '14:00',
                'periode' => 'Siang',
                'jenis' => 'cgd',
                'nama' => 'Cek Gula Darah 2 Jam PP',
                'dosis' => '2 jam setelah makan siang',
                'status' => 'upcoming',
            ],
            [
                'waktu' => '19:00',
                'periode' => 'Malam',
                'jenis' => 'obat',
                'nama' => 'Metformin 500mg',
                'dosis' => '1 tablet sesudah makan malam',
                'status' => 'upcoming',
            ],
        ];

        // Tracker mingguan (Senin–Minggu) — vertical detail
        // status: done (semua patuh), partial (ada yang skip), missed (skip banyak), today, future
        $weekTracker = [
            ['day' => 'Senin', 'date' => 19, 'status' => 'done', 'obat' => '3/3', 'gd' => '2/2'],
            ['day' => 'Selasa', 'date' => 20, 'status' => 'done', 'obat' => '3/3', 'gd' => '2/2'],
            ['day' => 'Rabu', 'date' => 21, 'status' => 'partial', 'obat' => '2/3', 'gd' => '2/2'],
            ['day' => 'Kamis', 'date' => 22, 'status' => 'missed', 'obat' => '1/3', 'gd' => '0/2'],
            ['day' => 'Jumat', 'date' => 23, 'status' => 'done', 'obat' => '3/3', 'gd' => '2/2'],
            ['day' => 'Sabtu', 'date' => 24, 'status' => 'done', 'obat' => '3/3', 'gd' => '2/2'],
            ['day' => 'Minggu', 'date' => 25, 'status' => 'today', 'obat' => '2/3', 'gd' => '0/1'],
        ];

        $weekSummary = [
            'patuh' => 5,
            'partial' => 1,
            'missed' => 1,
        ];

        // Tips diabetes (rotating)
        $tips = [
            ['icon' => '💧', 'text' => 'Minum air putih minimal 8 gelas per hari untuk membantu ginjal.'],
            ['icon' => '🥗', 'text' => 'Pilih karbohidrat kompleks: nasi merah, oat, atau ubi.'],
            ['icon' => '🚶', 'text' => 'Jalan kaki 30 menit setelah makan menurunkan gula darah.'],
            ['icon' => '😴', 'text' => 'Tidur 7-8 jam membantu regulasi insulin lebih baik.'],
        ];

        // Pengumuman terbaru (dummy)
        $pengumuman = [
            [
                'title' => 'Penyuluhan Diabetes Gratis',
                'meta' => 'Sabtu, 31 Mei 2026 • Puskesmas Kecamatan',
                'icon' => '📢',
            ],
            ['title' => 'Update Jadwal Cek Lab', 'meta' => 'Mulai 1 Juni 2026 • Buka pukul 06.00 WIB', 'icon' => '🔔'],
            ['title' => 'Tips Puasa Aman bagi Penderita DM', 'meta' => '3 hari lalu • Artikel Edukasi', 'icon' => '📖'],
        ];
    @endphp

    {{-- ============ STAT CARDS ============ --}}
    <div class="row g-3 mb-4">
        {{-- Obat Hari Ini --}}
        <div class="col-md-6 col-lg-3">
            <div class="stat-card shadow-sm p-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div class="stat-icon stat-icon-success">
                        <i class="ri ri-capsule-line"></i>
                    </div>
                    <span class="badge bg-light text-success border">
                        {{ $obatSelesai }}/{{ $obatHariIni }}
                    </span>
                </div>
                <div class="stat-value">{{ $obatHariIni }}</div>
                <div class="stat-label mt-1">💊 Obat Hari Ini</div>
            </div>
        </div>

        {{-- Cek GD Hari Ini --}}
        <div class="col-md-6 col-lg-3">
            <div class="stat-card shadow-sm p-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div class="stat-icon stat-icon-info">
                        <i class="ri ri-test-tube-line"></i>
                    </div>
                    <span class="badge bg-light text-info border">
                        {{ $cgdSelesai }}/{{ $cgdHariIni }}
                    </span>
                </div>
                <div class="stat-value">{{ $cgdHariIni }}</div>
                <div class="stat-label mt-1">🩸 Cek GD Hari Ini</div>
            </div>
        </div>

        {{-- Kepatuhan --}}
        <div class="col-md-6 col-lg-3">
            <div class="stat-card shadow-sm p-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div class="stat-icon stat-icon-warning">
                        <i class="ri ri-line-chart-line"></i>
                    </div>
                    <span class="streak-badge">
                        🔥 {{ $streak }} hari
                    </span>
                </div>
                <div class="stat-value">{{ $kepatuhan }}%</div>
                <div class="stat-label mt-1">📊 Kepatuhan 7 Hari</div>
                <div class="progress mt-2" style="height: 6px;">
                    <div class="progress-bar bg-warning" style="width: {{ $kepatuhan }}%"></div>
                </div>
            </div>
        </div>

        {{-- PMO Saya --}}
        <div class="col-md-6 col-lg-3">
            <div class="stat-card shadow-sm p-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div class="stat-icon stat-icon-primary">
                        <i class="ri ri-user-heart-line"></i>
                    </div>
                    <i class="ri ri-whatsapp-line text-success" style="font-size: 1.2rem;"></i>
                </div>
                <div class="fw-bold" style="font-size: 1.1rem; color: #111827;">{{ $pmoName }}</div>
                <div class="stat-label mt-1">👥 PMO ({{ $pmoHubungan }})</div>
            </div>
        </div>
    </div>

    {{-- ============ ROW 1: JADWAL HARI INI + WEEK TRACKER ============ --}}
    <div class="row g-3 mb-4">
        {{-- Jadwal Hari Ini --}}
        <div class="col-lg-8 d-flex">
            <div class="chart-card shadow-sm w-100">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <div class="chart-card-title">📅 Jadwal Hari Ini</div>
                        <div class="chart-card-subtitle">{{ count($jadwalHariIni) }} aktivitas terjadwal</div>
                    </div>
                    <a href="#" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                </div>

                @foreach ($jadwalHariIni as $jadwal)
                    <div class="timeline-item">
                        <div class="timeline-time">
                            {{ $jadwal['waktu'] }}
                            <small>{{ $jadwal['periode'] }}</small>
                        </div>
                        <div class="timeline-content {{ $jadwal['status'] }}">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="timeline-title">
                                        @if ($jadwal['jenis'] === 'obat')
                                            💊 {{ $jadwal['nama'] }}
                                        @else
                                            🩸 {{ $jadwal['nama'] }}
                                        @endif
                                    </div>
                                    <div class="timeline-meta">{{ $jadwal['dosis'] }}</div>
                                </div>
                                @if ($jadwal['status'] === 'done')
                                    <span class="timeline-status bg-success bg-opacity-10 text-success">
                                        <i class="ri ri-check-line"></i> Selesai
                                    </span>
                                @elseif ($jadwal['status'] === 'upcoming')
                                    <span class="timeline-status bg-warning bg-opacity-10 text-warning">
                                        <i class="ri ri-time-line"></i> Belum
                                    </span>
                                @elseif ($jadwal['status'] === 'missed')
                                    <span class="timeline-status bg-danger bg-opacity-10 text-danger">
                                        <i class="ri ri-close-line"></i> Terlewat
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Col kanan: Tracker + PMO, flexbox column --}}
        <div class="col-lg-4 d-flex flex-column">
            {{-- Week Tracker --}}
            <div class="chart-card shadow-sm mb-3 flex-grow-1">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <div class="chart-card-title">🗓️ Tracker Minggu Ini</div>
                        <div class="chart-card-subtitle">19 - 25 Mei 2026</div>
                    </div>
                </div>

                <div class="week-tracker-vertical">
                    @foreach ($weekTracker as $day)
                        <div class="day-row {{ $day['status'] }}">
                            <div class="day-status-icon {{ $day['status'] }}">
                                @if ($day['status'] === 'done')
                                    ✓
                                @elseif ($day['status'] === 'partial')
                                    !
                                @elseif ($day['status'] === 'missed')
                                    ✗
                                @elseif ($day['status'] === 'today')
                                    ●
                                @else
                                    —
                                @endif
                            </div>
                            <div class="day-info">
                                <div class="day-label">
                                    {{ $day['day'] }}<span class="day-date-small">{{ $day['date'] }} Mei</span>
                                </div>
                                <div class="day-summary">
                                    <span class="metric">💊 {{ $day['obat'] }}</span>
                                    <span class="metric">🩸 {{ $day['gd'] }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Summary footer --}}
                <div class="tracker-summary">
                    <div class="summary-item">
                        Patuh
                        <span class="summary-value text-success">{{ $weekSummary['patuh'] }}</span>
                    </div>
                    <div class="summary-item">
                        Sebagian
                        <span class="summary-value text-warning">{{ $weekSummary['partial'] }}</span>
                    </div>
                    <div class="summary-item">
                        Terlewat
                        <span class="summary-value text-danger">{{ $weekSummary['missed'] }}</span>
                    </div>
                </div>
            </div>


        </div>
    </div>

    {{-- ============ ROW 2: TREND GULA DARAH + COMPLIANCE RING ============ --}}
    <div class="row g-3 mb-4">
        {{-- Trend Gula Darah 7 Hari --}}
        <div class="col-lg-8">
            <div class="chart-card shadow-sm">
                <div class="d-flex justify-content-between align-items-start mb-1">
                    <div>
                        <div class="chart-card-title">📈 Trend Gula Darah Saya</div>
                        <div class="chart-card-subtitle">7 hari terakhir (mg/dL)</div>
                    </div>
                    <div class="d-flex gap-3" style="font-size: 0.75rem;">
                        <small><span class="badge bg-success">●</span> Normal (70-140)</small>
                        <small><span class="badge bg-warning">●</span> Tinggi (&gt;140)</small>
                    </div>
                </div>
                <div class="chart-container" style="height: 240px;">
                    <canvas id="chartTrendGD"></canvas>
                </div>
            </div>
        </div>

        {{-- Compliance Ring + Tips --}}
        <div class="col-lg-4">
            <div class="chart-card shadow-sm">
                <div class="chart-card-title">🎯 Kepatuhan Bulan Ini</div>
                <div class="chart-card-subtitle">Mei 2026</div>
                <div class="compliance-ring my-3">
                    <canvas id="chartCompliance"></canvas>
                    <div class="compliance-text">
                        <div class="value">{{ $kepatuhan }}%</div>
                        <div class="label">Patuh</div>
                    </div>
                </div>
                <div class="text-center">
                    <small class="text-muted d-block">Sudah konsumsi obat tepat waktu sebanyak</small>
                    <strong class="text-success">26 dari 30 hari</strong>
                </div>
            </div>
        </div>
    </div>

    {{-- ============ ROW 3: TIPS + PENGUMUMAN ============ --}}
    <div class="row g-3">
        {{-- Tips Diabetes --}}
        <div class="col-lg-6">
            <div class="tips-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0" style="color: #92400e;">💡 Tips Hari Ini</h6>
                    <small style="color: #92400e;">Dirotasi otomatis</small>
                </div>
                @foreach ($tips as $tip)
                    <div class="tip-item">
                        <span style="font-size: 1.1rem;">{{ $tip['icon'] }}</span>
                        <span>{{ $tip['text'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Pengumuman & Edukasi --}}
        <div class="col-lg-6">
            <div class="chart-card shadow-sm">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <div class="chart-card-title">📢 Pengumuman Terbaru</div>
                        <div class="chart-card-subtitle">Info & edukasi untuk Anda</div>
                    </div>
                    <a href="{{ route('public.pengumuman') }}" class="btn btn-sm btn-outline-primary">Semua</a>
                </div>
                @foreach ($pengumuman as $item)
                    <div class="announcement-item">
                        <div class="announcement-icon">{{ $item['icon'] }}</div>
                        <div class="flex-grow-1">
                            <div class="announcement-title">{{ $item['title'] }}</div>
                            <div class="announcement-meta">{{ $item['meta'] }}</div>
                        </div>
                        <i class="ri ri-arrow-right-s-line text-muted align-self-center"></i>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof window.Chart === 'undefined') {
                console.error('[Dashboard Pasien] Chart.js belum di-load!');
                return;
            }

            const Chart = window.Chart;

            // ===========================================
            // GLOBAL CHART.JS CONFIG
            // ===========================================
            Chart.defaults.font.family = "'Inter', system-ui, -apple-system, sans-serif";
            Chart.defaults.font.size = 12;
            Chart.defaults.color = '#6b7280';
            Chart.defaults.plugins.legend.display = false;
            Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(17, 24, 39, 0.95)';
            Chart.defaults.plugins.tooltip.padding = 12;
            Chart.defaults.plugins.tooltip.cornerRadius = 8;

            const PALETTE = {
                primary: '#3b82f6',
                success: '#10b981',
                warning: '#f59e0b',
                danger: '#ef4444',
                muted: '#e5e7eb',
                primaryFade: 'rgba(59, 130, 246, 0.1)',
            };

            // ===========================================
            // 1️⃣ TREND GULA DARAH 7 HARI
            // ===========================================
            const gdLabels = ['Sen 19', 'Sel 20', 'Rab 21', 'Kam 22', 'Jum 23', 'Sab 24', 'Min 25'];
            const gdData = [125, 118, 132, 145, 138, 122, 130];

            // Color per point: hijau jika normal (70-140), warning jika > 140
            const gdPointColors = gdData.map(v => v > 140 ? PALETTE.warning : PALETTE.success);

            new Chart(document.getElementById('chartTrendGD'), {
                type: 'line',
                data: {
                    labels: gdLabels,
                    datasets: [{
                        label: 'Gula Darah',
                        data: gdData,
                        borderColor: PALETTE.primary,
                        backgroundColor: PALETTE.primaryFade,
                        borderWidth: 2.5,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 6,
                        pointHoverRadius: 8,
                        pointBackgroundColor: gdPointColors,
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(ctx) {
                                    const v = ctx.parsed.y;
                                    let status = 'Normal';
                                    if (v > 200) status = 'Sangat Tinggi';
                                    else if (v > 140) status = 'Tinggi';
                                    else if (v < 70) status = 'Rendah';
                                    return `${v} mg/dL — ${status}`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: false,
                            suggestedMin: 60,
                            suggestedMax: 180,
                            grid: {
                                color: '#f3f4f6',
                                drawBorder: false
                            },
                            ticks: {
                                padding: 8,
                                callback: function(v) {
                                    return v + ' mg/dL';
                                }
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

            // ===========================================
            // 2️⃣ COMPLIANCE RING (Doughnut)
            // ===========================================
            const kepatuhan = {{ $kepatuhan }};

            new Chart(document.getElementById('chartCompliance'), {
                type: 'doughnut',
                data: {
                    labels: ['Patuh', 'Belum'],
                    datasets: [{
                        data: [kepatuhan, 100 - kepatuhan],
                        backgroundColor: [PALETTE.success, PALETTE.muted],
                        borderWidth: 0,
                        spacing: 0,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '78%',
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(ctx) {
                                    return ctx.label + ': ' + ctx.parsed + '%';
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
@endpush
