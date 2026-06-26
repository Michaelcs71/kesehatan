@extends('layouts.app')

@section('title', 'Dashboard PMO')

@section('page-header')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">
                👋 Halo, {{ auth()->user()->name }}!
            </h4>
            <small class="text-muted">Pantau kepatuhan pasien binaan Anda hari ini.</small>
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
        /* === DASHBOARD PMO — Operational + Caring === */
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

        .stat-icon-primary {
            background: #eff6ff;
            color: #3b82f6;
        }

        .stat-icon-success {
            background: #ecfdf5;
            color: #10b981;
        }

        .stat-icon-warning {
            background: #fffbeb;
            color: #f59e0b;
        }

        .stat-icon-danger {
            background: #fef2f2;
            color: #ef4444;
        }

        .stat-icon-info {
            background: #ecfeff;
            color: #06b6d4;
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

        /* === ALERT CARD (Pasien Perlu Perhatian) === */
        .alert-card {
            background: linear-gradient(135deg, #fef2f2, #fef3c7);
            border-radius: 14px;
            border: 1px solid #fde68a;
            padding: 1.25rem;
        }

        .alert-card-title {
            font-size: 1rem;
            font-weight: 700;
            color: #92400e;
            margin-bottom: 0.25rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .alert-card-subtitle {
            font-size: 0.8rem;
            color: #b45309;
            margin-bottom: 1rem;
        }

        .alert-item {
            background: #fff;
            border-radius: 10px;
            padding: 12px 14px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-left: 3px solid #ef4444;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
        }

        .alert-item:last-child {
            margin-bottom: 0;
        }

        .alert-item.warning {
            border-left-color: #f59e0b;
        }

        .alert-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #fee2e2;
            color: #dc2626;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.9rem;
            flex-shrink: 0;
        }

        .alert-item.warning .alert-avatar {
            background: #fef3c7;
            color: #d97706;
        }

        .alert-info {
            flex: 1;
            min-width: 0;
        }

        .alert-name {
            font-weight: 600;
            color: #111827;
            font-size: 0.9rem;
            margin-bottom: 2px;
        }

        .alert-detail {
            font-size: 0.75rem;
            color: #6b7280;
        }

        .alert-actions {
            display: flex;
            gap: 4px;
            flex-shrink: 0;
        }

        .alert-btn {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.95rem;
        }

        .alert-btn-success {
            background: #ecfdf5;
            color: #047857;
        }

        .alert-btn-success:hover {
            background: #10b981;
            color: white;
        }

        .alert-btn-info {
            background: #ecfeff;
            color: #0891b2;
        }

        .alert-btn-info:hover {
            background: #06b6d4;
            color: white;
        }

        /* === PASIEN CARD === */
        .pasien-card {
            background: #fff;
            border: 1px solid #f3f4f6;
            border-radius: 14px;
            padding: 1.25rem;
            transition: all 0.25s ease;
            height: 100%;
        }

        .pasien-card:hover {
            border-color: #3b82f6;
            box-shadow: 0 8px 24px rgba(59, 130, 246, 0.08);
        }

        .pasien-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #f3f4f6;
        }

        .pasien-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1rem;
            color: white;
            flex-shrink: 0;
        }

        .pasien-avatar.blue {
            background: linear-gradient(135deg, #3b82f6, #06b6d4);
        }

        .pasien-avatar.purple {
            background: linear-gradient(135deg, #8b5cf6, #ec4899);
        }

        .pasien-avatar.green {
            background: linear-gradient(135deg, #10b981, #06b6d4);
        }

        .pasien-name {
            font-weight: 700;
            color: #111827;
            font-size: 0.95rem;
            line-height: 1.2;
            margin-bottom: 2px;
        }

        .pasien-meta {
            font-size: 0.75rem;
            color: #6b7280;
        }

        .pasien-status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 4px;
        }

        .pasien-status-dot.online {
            background: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
        }

        .pasien-status-dot.warning {
            background: #f59e0b;
        }

        .pasien-status-dot.danger {
            background: #ef4444;
        }

        .pasien-progress-section {
            margin-bottom: 12px;
        }

        .pasien-progress-label {
            display: flex;
            justify-content: space-between;
            font-size: 0.75rem;
            color: #6b7280;
            margin-bottom: 4px;
        }

        .pasien-progress-label strong {
            color: #111827;
            font-weight: 700;
        }

        .pasien-quick-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-bottom: 1rem;
        }

        .quick-info-item {
            background: #f9fafb;
            border-radius: 8px;
            padding: 8px 10px;
            text-align: center;
        }

        .quick-info-value {
            font-size: 1rem;
            font-weight: 700;
            color: #111827;
            line-height: 1;
        }

        .quick-info-label {
            font-size: 0.65rem;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 2px;
        }

        .pasien-actions {
            display: flex;
            gap: 6px;
        }

        .pasien-actions .btn {
            flex: 1;
            font-size: 0.75rem;
            padding: 6px 8px;
        }

        /* === CHART CARD === */
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
            height: 240px;
        }

        /* === ACTIVITY TIMELINE === */
        .activity-item {
            display: flex;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid #f3f4f6;
        }

        .activity-item:last-child {
            border-bottom: 0;
        }

        .activity-time {
            min-width: 56px;
            font-weight: 700;
            color: #111827;
            font-size: 0.85rem;
        }

        .activity-time small {
            display: block;
            font-weight: 500;
            color: #9ca3af;
            font-size: 0.65rem;
            text-transform: uppercase;
        }

        .activity-content {
            flex: 1;
            background: #f9fafb;
            padding: 10px 12px;
            border-radius: 10px;
            border-left: 3px solid #3b82f6;
        }

        .activity-content.done {
            background: #ecfdf5;
            border-left-color: #10b981;
        }

        .activity-content.upcoming {
            background: #fffbeb;
            border-left-color: #f59e0b;
        }

        .activity-content.missed {
            background: #fef2f2;
            border-left-color: #ef4444;
        }

        .activity-pasien {
            font-size: 0.7rem;
            color: #6b7280;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }

        .activity-title {
            font-weight: 600;
            color: #111827;
            font-size: 0.875rem;
            margin-bottom: 2px;
        }

        .activity-meta {
            font-size: 0.75rem;
            color: #6b7280;
        }

        .activity-action-btn {
            background: #fff;
            border: 1px solid #d1d5db;
            color: #374151;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .activity-action-btn:hover {
            background: #10b981;
            color: white;
            border-color: #10b981;
        }

        /* === TIPS CARD === */
        .tips-card {
            background: linear-gradient(135deg, #dbeafe, #ecfeff);
            border-radius: 14px;
            padding: 1.25rem;
            border: none;
        }

        .tips-card .tip-item {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
            font-size: 0.875rem;
            color: #1e3a8a;
        }

        .tips-card .tip-item:last-child {
            margin-bottom: 0;
        }
    </style>
@endpush

@section('content')

    @php
        // ===========================================
        // Data dari DashboardService::untukPmo()
        // ===========================================
        $totalPasien        = $total_pasien;
        $patuhHariIni       = $patuh_hari_ini;
        $perluPerhatian     = $perlu_perhatian;
        $totalJadwalHariIni = $total_jadwal_hari_ini;
        $streakPendampingan = 0; // belum dihitung putaran ini

        // Alert pasien perlu perhatian — belum ada sumber data putaran ini
        $alertPasien = [];

        // Warna avatar berputar untuk variasi visual
        $avatarColors = ['blue', 'purple', 'green', 'warning', 'danger'];

        // Daftar pasien binaan dari service
        $pasienBinaan = collect($daftar_pasien)->map(function ($p, $idx) use ($avatarColors) {
            $namaWords = explode(' ', trim($p['nama']));
            $inisial   = mb_strtoupper(mb_substr($namaWords[0], 0, 1))
                       . (isset($namaWords[1]) ? mb_strtoupper(mb_substr($namaWords[1], 0, 1)) : '');
            $kepatuhan = $p['kepatuhan'] ?? 0;
            $status    = $kepatuhan >= 80 ? 'online' : ($kepatuhan >= 60 ? 'warning' : 'danger');

            return [
                'nama'          => $p['nama'],
                'inisial'       => $inisial,
                'usia'          => '-',
                'tipe_dm'       => $p['status_diabetes'] ?? 'Tipe 2',
                'avatar_color'  => $avatarColors[$idx % count($avatarColors)],
                'status'        => $status,
                'kepatuhan'     => $kepatuhan,
                'streak'        => 0,
                'next_jadwal'   => '-',
                'next_aktivitas'=> '-',
                'gd_terakhir'   => $p['gd_terakhir'] ?? '-',
                'whatsapp'      => null,
            ];
        })->all();

        // Aktivitas timeline dari service (PengingatMoLog)
        $aktivitasHariIni = collect($timeline)->map(fn ($t) => [
            'waktu'     => $t['waktu'] ?? '-',
            'periode'   => '-',
            'pasien'    => $t['nama'],
            'aktivitas' => $t['aksi'],
            'status'    => 'done',
        ])->all();

        // Tips pendampingan PMO
        $tipsPmo = $tips;
    @endphp

    {{-- ============ STAT CARDS ============ --}}
    <div class="row g-3 mb-4">
        {{-- Total Pasien Binaan --}}
        <div class="col-md-6 col-lg-3">
            <div class="stat-card shadow-sm p-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div class="stat-icon stat-icon-primary">
                        <i class="ri ri-user-heart-line"></i>
                    </div>
                    <span class="streak-badge">
                        🔥 {{ $streakPendampingan }} hari
                    </span>
                </div>
                <div class="stat-value">{{ $totalPasien }}</div>
                <div class="stat-label mt-1">👥 Pasien Binaan</div>
            </div>
        </div>

        {{-- Patuh Hari Ini --}}
        <div class="col-md-6 col-lg-3">
            <div class="stat-card shadow-sm p-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div class="stat-icon stat-icon-success">
                        <i class="ri ri-checkbox-circle-line"></i>
                    </div>
                    <span class="badge bg-light text-success border">
                        {{ $patuhHariIni }}/{{ $totalJadwalHariIni }}
                    </span>
                </div>
                <div class="stat-value">{{ $patuhHariIni }}</div>
                <div class="stat-label mt-1">✅ Sudah Patuh Hari Ini</div>
                <div class="progress mt-2" style="height: 6px;">
                    <div class="progress-bar bg-success"
                        style="width: {{ $totalJadwalHariIni > 0 ? round(($patuhHariIni / $totalJadwalHariIni) * 100) : 0 }}%"></div>
                </div>
            </div>
        </div>

        {{-- Perlu Perhatian --}}
        <div class="col-md-6 col-lg-3">
            <div class="stat-card shadow-sm p-4" style="cursor: pointer;" onclick="scrollToAlert()">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div class="stat-icon stat-icon-danger">
                        <i class="ri ri-error-warning-line"></i>
                    </div>
                    @if ($perluPerhatian > 0)
                        <span class="badge bg-danger">URGENT</span>
                    @endif
                </div>
                <div class="stat-value">{{ $perluPerhatian }}</div>
                <div class="stat-label mt-1">⚠️ Perlu Perhatian</div>
            </div>
        </div>

        {{-- Jadwal Hari Ini --}}
        <div class="col-md-6 col-lg-3">
            <div class="stat-card shadow-sm p-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div class="stat-icon stat-icon-warning">
                        <i class="ri ri-calendar-check-line"></i>
                    </div>
                    <span class="badge bg-light text-warning border">
                        {{ $totalJadwalHariIni - $patuhHariIni }} pending
                    </span>
                </div>
                <div class="stat-value">{{ $totalJadwalHariIni }}</div>
                <div class="stat-label mt-1">⏰ Total Jadwal Hari Ini</div>
            </div>
        </div>
    </div>

    {{-- ============ ALERT: PASIEN PERLU PERHATIAN ============ --}}
    @if (count($alertPasien) > 0)
        <div class="row g-3 mb-4">
            <div class="col-12">
                <div class="alert-card" id="alert-section">
                    <div class="alert-card-title">
                        🚨 Pasien Perlu Perhatian Anda
                    </div>
                    <div class="alert-card-subtitle">
                        {{ count($alertPasien) }} pasien skip jadwal — hubungi sekarang
                    </div>

                    @foreach ($alertPasien as $alert)
                        <div class="alert-item {{ $alert['level'] === 'warning' ? 'warning' : '' }}">
                            <div class="alert-avatar">{{ $alert['inisial'] }}</div>
                            <div class="alert-info">
                                <div class="alert-name">{{ $alert['nama'] }}</div>
                                <div class="alert-detail">
                                    <i class="ri ri-time-line"></i> {{ $alert['waktu'] }} —
                                    {{ $alert['masalah'] }}
                                </div>
                            </div>
                            <div class="alert-actions">
                                <a href="https://wa.me/{{ ltrim($alert['whatsapp'], '0') }}?text=Halo%20{{ urlencode($alert['nama']) }}%2C%20jangan%20lupa%20{{ urlencode($alert['masalah']) }}"
                                    target="_blank" class="alert-btn alert-btn-success" title="Chat WhatsApp">
                                    <i class="ri ri-whatsapp-line"></i>
                                </a>
                                <button class="alert-btn alert-btn-info" title="Catat sudah dilakukan"
                                    onclick="alert('Konfirmasi: {{ $alert['nama'] }} sudah {{ $alert['masalah'] }}? (Implementasi nanti)')">
                                    <i class="ri ri-check-line"></i>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- ============ PASIEN BINAAN CARDS ============ --}}
    <div class="row g-3 mb-4">
        <div class="col-12 mb-1">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0" style="color: #111827;">
                    <i class="ri ri-user-heart-line"></i> Pasien Binaan Saya
                </h6>
                <a href="#" class="btn btn-sm btn-outline-primary">
                    Lihat Semua
                </a>
            </div>
        </div>

        @forelse ($pasienBinaan as $pasien)
            <div class="col-md-6 col-lg-4">
                <div class="pasien-card shadow-sm">
                    {{-- Header --}}
                    <div class="pasien-header">
                        <div class="pasien-avatar {{ $pasien['avatar_color'] }}">
                            {{ $pasien['inisial'] }}
                        </div>
                        <div class="flex-grow-1 min-w-0">
                            <div class="pasien-name">{{ $pasien['nama'] }}</div>
                            <div class="pasien-meta">
                                <span class="pasien-status-dot {{ $pasien['status'] }}"></span>
                                {{ $pasien['usia'] }} thn • DM {{ $pasien['tipe_dm'] }}
                            </div>
                        </div>
                    </div>

                    {{-- Kepatuhan Progress --}}
                    <div class="pasien-progress-section">
                        <div class="pasien-progress-label">
                            <span>Kepatuhan 7 hari</span>
                            <strong>{{ $pasien['kepatuhan'] }}%</strong>
                        </div>
                        <div class="progress" style="height: 6px;">
                            @php
                                $progressColor =
                                    $pasien['kepatuhan'] >= 80
                                        ? 'bg-success'
                                        : ($pasien['kepatuhan'] >= 60
                                            ? 'bg-warning'
                                            : 'bg-danger');
                            @endphp
                            <div class="progress-bar {{ $progressColor }}" style="width: {{ $pasien['kepatuhan'] }}%">
                            </div>
                        </div>
                    </div>

                    {{-- Quick Info --}}
                    <div class="pasien-quick-info">
                        <div class="quick-info-item">
                            <div class="quick-info-value">🔥 {{ $pasien['streak'] }}</div>
                            <div class="quick-info-label">Streak</div>
                        </div>
                        <div class="quick-info-item">
                            @php
                                $gdNilai  = $pasien['gd_terakhir'];
                                $gdColor  = is_numeric($gdNilai) && $gdNilai > 140
                                    ? 'text-warning'
                                    : 'text-success';
                            @endphp
                            <div class="quick-info-value {{ $gdColor }}">{{ $gdNilai ?? '-' }}</div>
                            <div class="quick-info-label">GD mg/dL</div>
                        </div>
                    </div>

                    {{-- Next Schedule --}}
                    <div class="mb-3 p-2 rounded" style="background: #f9fafb; font-size: 0.8rem;">
                        <div class="text-muted" style="font-size: 0.7rem; text-transform: uppercase; font-weight: 600;">
                            Jadwal Berikutnya
                        </div>
                        <div class="fw-semibold" style="color: #111827;">
                            <i class="ri ri-time-line"></i> {{ $pasien['next_jadwal'] }} —
                            {{ $pasien['next_aktivitas'] }}
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="pasien-actions">
                        <a href="#" class="btn btn-outline-primary btn-sm">
                            <i class="ri ri-eye-line"></i> Detail
                        </a>
                        @if ($pasien['whatsapp'])
                            <a href="https://wa.me/{{ ltrim($pasien['whatsapp'], '0') }}?text=Halo%20{{ urlencode($pasien['nama']) }}"
                                target="_blank" class="btn btn-success btn-sm">
                                <i class="ri ri-whatsapp-line"></i> Chat
                            </a>
                        @else
                            <button class="btn btn-success btn-sm" disabled title="Nomor WhatsApp belum tersedia">
                                <i class="ri ri-whatsapp-line"></i> Chat
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="text-center text-muted py-4">
                    Belum ada pasien binaan.
                </div>
            </div>
        @endforelse
    </div>

    {{-- ============ ROW: AKTIVITAS HARI INI + CHART KEPATUHAN ============ --}}
    <div class="row g-3 mb-4">
        {{-- Aktivitas Hari Ini --}}
        <div class="col-lg-7">
            <div class="chart-card shadow-sm">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <div class="chart-card-title">📅 Aktivitas Semua Pasien Hari Ini</div>
                        <div class="chart-card-subtitle">{{ count($aktivitasHariIni) }} aktivitas terjadwal</div>
                    </div>
                    <a href="#" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                </div>

                <div style="max-height: 480px; overflow-y: auto;">
                    @forelse ($aktivitasHariIni as $aktivitas)
                        <div class="activity-item">
                            <div class="activity-time">
                                {{ $aktivitas['waktu'] }}
                                <small>{{ $aktivitas['periode'] }}</small>
                            </div>
                            <div class="activity-content {{ $aktivitas['status'] }}">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="activity-pasien">👤 {{ $aktivitas['pasien'] }}</div>
                                        <div class="activity-title">{{ $aktivitas['aktivitas'] }}</div>
                                        <div class="activity-meta">
                                            @if ($aktivitas['status'] === 'done')
                                                <span class="text-success">
                                                    <i class="ri ri-check-line"></i> Sudah dikonfirmasi
                                                </span>
                                            @elseif ($aktivitas['status'] === 'upcoming')
                                                <span class="text-warning">
                                                    <i class="ri ri-time-line"></i> Belum waktunya
                                                </span>
                                            @elseif ($aktivitas['status'] === 'missed')
                                                <span class="text-danger">
                                                    <i class="ri ri-error-warning-line"></i> Terlewat
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    @if ($aktivitas['status'] !== 'done')
                                        <button class="activity-action-btn" title="Konfirmasi sudah dilakukan"
                                            onclick="alert('Konfirmasi {{ $aktivitas['aktivitas'] }} untuk {{ $aktivitas['pasien'] }}? (Implementasi nanti)')">
                                            ✓ Catat
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-muted py-4">
                            Belum ada aktivitas.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Chart Kepatuhan --}}
        <div class="col-lg-5">
            <div class="chart-card shadow-sm">
                <div class="chart-card-title">📈 Trend Kepatuhan Pasien Binaan</div>
                <div class="chart-card-subtitle">7 hari terakhir (%)</div>
                <div class="chart-container" style="height: 280px;">
                    <canvas id="chartKepatuhanBinaan"></canvas>
                </div>

                {{-- Mini legend with averages --}}
                <div class="mt-3 pt-3 border-top">
                    <div class="row g-2 text-center">
                        @forelse ($pasienBinaan as $pasien)
                            <div class="col-4">
                                <div class="text-muted"
                                    style="font-size: 0.7rem; text-transform: uppercase; font-weight: 600;">
                                    {{ explode(' ', $pasien['nama'])[0] }}
                                </div>
                                <div class="fw-bold" style="font-size: 1.1rem; color: #111827;">
                                    {{ $pasien['kepatuhan'] }}%
                                </div>
                            </div>
                        @empty
                            <div class="col-12 text-muted text-center py-2" style="font-size: 0.8rem;">
                                Belum ada pasien binaan.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============ TIPS PENDAMPINGAN ============ --}}
    <div class="row g-3">
        <div class="col-12">
            <div class="tips-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0" style="color: #1e3a8a;">
                        💡 Tips Pendampingan PMO
                    </h6>
                    <small style="color: #1e3a8a;">Untuk pendamping yang lebih efektif</small>
                </div>
                <div class="row g-3">
                    @foreach ($tipsPmo as $tip)
                        <div class="col-md-6">
                            <div class="tip-item">
                                <span style="font-size: 1.2rem;">{{ $tip['icon'] }}</span>
                                <span>{{ $tip['text'] }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        // Scroll to alert section
        function scrollToAlert() {
            const el = document.getElementById('alert-section');
            if (el) {
                el.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
                el.style.animation = 'none';
                setTimeout(() => {
                    el.style.boxShadow = '0 0 0 4px rgba(239, 68, 68, 0.3)';
                    setTimeout(() => {
                        el.style.boxShadow = '';
                    }, 1500);
                }, 100);
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            if (typeof window.Chart === 'undefined') {
                console.error('[Dashboard PMO] Chart.js belum di-load!');
                return;
            }

            const Chart = window.Chart;

            Chart.defaults.font.family = "'Inter', system-ui, -apple-system, sans-serif";
            Chart.defaults.font.size = 12;
            Chart.defaults.color = '#6b7280';
            Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(17, 24, 39, 0.95)';
            Chart.defaults.plugins.tooltip.padding = 12;
            Chart.defaults.plugins.tooltip.cornerRadius = 8;

            const PALETTE = {
                primary: '#3b82f6',
                info: '#06b6d4',
                success: '#10b981',
                warning: '#f59e0b',
                danger: '#ef4444',
                purple: '#8b5cf6',
            };

            // ===========================================
            // CHART: TREND KEPATUHAN 7 HARI per PASIEN
            // ===========================================
            const labels = ['Sen 19', 'Sel 20', 'Rab 21', 'Kam 22', 'Jum 23', 'Sab 24', 'Min 25'];

            new Chart(document.getElementById('chartKepatuhanBinaan'), {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                            label: 'Siti Pasien',
                            data: [80, 90, 70, 60, 80, 75, 75],
                            borderColor: PALETTE.primary,
                            backgroundColor: 'rgba(59, 130, 246, 0.05)',
                            borderWidth: 2.5,
                            fill: false,
                            tension: 0.4,
                            pointRadius: 3,
                            pointHoverRadius: 6,
                            pointBackgroundColor: PALETTE.primary,
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                        },
                        {
                            label: 'Bapak Hasan',
                            data: [70, 65, 75, 50, 60, 55, 62],
                            borderColor: PALETTE.purple,
                            backgroundColor: 'rgba(139, 92, 246, 0.05)',
                            borderWidth: 2.5,
                            fill: false,
                            tension: 0.4,
                            pointRadius: 3,
                            pointHoverRadius: 6,
                            pointBackgroundColor: PALETTE.purple,
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                        },
                        {
                            label: 'Ibu Aminah',
                            data: [90, 95, 100, 90, 95, 100, 94],
                            borderColor: PALETTE.success,
                            backgroundColor: 'rgba(16, 185, 129, 0.05)',
                            borderWidth: 2.5,
                            fill: false,
                            tension: 0.4,
                            pointRadius: 3,
                            pointHoverRadius: 6,
                            pointBackgroundColor: PALETTE.success,
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                        },
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom',
                            labels: {
                                padding: 10,
                                boxWidth: 8,
                                boxHeight: 8,
                                usePointStyle: true,
                                pointStyle: 'circle',
                                font: {
                                    size: 11
                                },
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(ctx) {
                                    return ctx.dataset.label + ': ' + ctx.parsed.y + '%';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: false,
                            suggestedMin: 40,
                            suggestedMax: 100,
                            grid: {
                                color: '#f3f4f6',
                                drawBorder: false
                            },
                            ticks: {
                                padding: 8,
                                callback: function(v) {
                                    return v + '%';
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
        });
    </script>
@endpush
