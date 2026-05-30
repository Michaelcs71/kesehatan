@extends('layouts.landing')

@section('title', 'Kesehatan - Sistem Pengingat Diabetes')

@push('styles')
    <style>
        /* === LANDING — Clean & Trustworthy === */
        :root {
            --primary: #3b82f6;
            --primary-dark: #1e40af;
            --primary-light: #eff6ff;
            --secondary: #06b6d4;
            --dark: #111827;
            --gray-text: #6b7280;
            --light-bg: #fafbfc;
        }

        .landing-section {
            padding: 5rem 0;
        }

        .section-title {
            font-weight: 800;
            color: var(--dark);
            letter-spacing: -0.02em;
            margin-bottom: 0.5rem;
        }

        .section-subtitle {
            color: var(--gray-text);
            font-size: 1.05rem;
        }

        /* === HERO === */
        .hero-section {
            background: linear-gradient(135deg, #f0f4ff 0%, #e6f7ff 100%);
            padding: 6rem 0 5rem;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.06), transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: white;
            border: 1px solid #dbeafe;
            color: var(--primary);
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 1.25rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        }

        .hero-title {
            font-weight: 800;
            font-size: 3.25rem;
            line-height: 1.15;
            color: #1f3a6e;
            letter-spacing: -0.03em;
            margin-bottom: 1.25rem;
        }

        .hero-title .gradient-text {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-subtitle {
            font-size: 1.15rem;
            color: var(--gray-text);
            line-height: 1.65;
            margin-bottom: 2rem;
        }

        .hero-btn {
            font-weight: 600;
            padding: 0.85rem 1.75rem;
            border-radius: 12px;
            transition: all 0.25s ease;
        }

        .hero-btn-primary {
            background: var(--primary);
            border: 2px solid var(--primary);
            color: white;
            box-shadow: 0 4px 14px rgba(59, 130, 246, 0.25);
        }

        .hero-btn-primary:hover {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.35);
        }

        .hero-btn-outline {
            background: white;
            border: 2px solid #e5e7eb;
            color: var(--dark);
        }

        .hero-btn-outline:hover {
            border-color: var(--primary);
            color: var(--primary);
            transform: translateY(-2px);
        }

        .hero-trust {
            margin-top: 2.5rem;
            display: flex;
            gap: 1.5rem;
            flex-wrap: wrap;
            color: var(--gray-text);
            font-size: 0.85rem;
        }

        .hero-trust-item {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .hero-trust-item i {
            color: #10b981;
            font-size: 1.1rem;
        }

        /* Hero visual: phone mockup */
        .hero-visual {
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .phone-mockup {
            width: 280px;
            height: 560px;
            background: white;
            border-radius: 36px;
            border: 8px solid var(--dark);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            position: relative;
            overflow: hidden;
            transform: rotate(-3deg);
        }

        .phone-screen {
            padding: 1.5rem 1rem;
            height: 100%;
            background: linear-gradient(180deg, #f0f4ff 0%, white 100%);
        }

        .phone-header {
            text-align: center;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .phone-greeting {
            font-size: 0.7rem;
            color: var(--gray-text);
            margin-bottom: 2px;
        }

        .phone-name {
            font-weight: 700;
            font-size: 0.95rem;
            color: var(--dark);
        }

        .phone-card {
            background: white;
            border-radius: 12px;
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
            border-left: 3px solid #10b981;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.75rem;
        }

        .phone-card.upcoming {
            border-left-color: #f59e0b;
        }

        .phone-card-icon {
            width: 30px;
            height: 30px;
            background: #ecfdf5;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .phone-card.upcoming .phone-card-icon {
            background: #fffbeb;
        }

        .phone-card-info {
            flex: 1;
            min-width: 0;
        }

        .phone-card-title {
            font-weight: 600;
            color: var(--dark);
            font-size: 0.75rem;
            line-height: 1.2;
        }

        .phone-card-time {
            color: var(--gray-text);
            font-size: 0.65rem;
            margin-top: 1px;
        }

        .phone-card-status {
            font-size: 0.6rem;
            font-weight: 700;
            text-transform: uppercase;
            color: #10b981;
        }

        .phone-card.upcoming .phone-card-status {
            color: #d97706;
        }

        .phone-stats {
            background: linear-gradient(135deg, #3b82f6, #06b6d4);
            border-radius: 12px;
            padding: 0.75rem;
            color: white;
            margin-bottom: 0.75rem;
            text-align: center;
        }

        .phone-stats-value {
            font-size: 1.5rem;
            font-weight: 800;
            line-height: 1;
        }

        .phone-stats-label {
            font-size: 0.65rem;
            opacity: 0.9;
            margin-top: 2px;
        }

        /* === STATS SECTION === */
        .stats-section {
            background: var(--dark);
            color: white;
            padding: 3.5rem 0;
        }

        .stat-item {
            text-align: center;
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #60a5fa, #06b6d4);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            line-height: 1;
            letter-spacing: -0.02em;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #d1d5db;
            margin-top: 0.5rem;
        }

        /* === FEATURE CARDS === */
        .feature-card {
            background: white;
            border-radius: 16px;
            padding: 2rem 1.5rem;
            border: 1px solid #f3f4f6;
            height: 100%;
            transition: all 0.25s ease;
        }

        .feature-card:hover {
            transform: translateY(-4px);
            border-color: #dbeafe;
            box-shadow: 0 12px 32px rgba(59, 130, 246, 0.08);
        }

        .feature-icon-wrap {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            margin-bottom: 1.25rem;
        }

        .feature-icon-blue {
            background: #eff6ff;
            color: #3b82f6;
        }

        .feature-icon-cyan {
            background: #ecfeff;
            color: #06b6d4;
        }

        .feature-icon-green {
            background: #ecfdf5;
            color: #10b981;
        }

        .feature-icon-amber {
            background: #fffbeb;
            color: #f59e0b;
        }

        .feature-icon-purple {
            background: #f5f3ff;
            color: #8b5cf6;
        }

        .feature-icon-rose {
            background: #fff1f2;
            color: #f43f5e;
        }

        .feature-title {
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .feature-desc {
            color: var(--gray-text);
            font-size: 0.9rem;
            line-height: 1.6;
            margin-bottom: 0;
        }

        /* === HOW IT WORKS === */
        .how-section {
            background: var(--light-bg);
        }

        .step-card {
            text-align: center;
            padding: 1.5rem;
            position: relative;
        }

        .step-number {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            font-size: 1.75rem;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.25rem;
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.25);
        }

        .step-title {
            font-weight: 700;
            color: var(--dark);
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }

        .step-desc {
            color: var(--gray-text);
            font-size: 0.9rem;
            line-height: 1.6;
        }

        /* === FOR WHO === */
        .audience-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            border: 1px solid #f3f4f6;
            height: 100%;
            transition: all 0.25s ease;
            position: relative;
            overflow: hidden;
        }

        .audience-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
        }

        .audience-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.06);
        }

        .audience-avatar {
            width: 64px;
            height: 64px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            margin-bottom: 1.25rem;
        }

        .audience-avatar.pasien {
            background: #eff6ff;
            color: #3b82f6;
        }

        .audience-avatar.pmo {
            background: #f5f3ff;
            color: #8b5cf6;
        }

        .audience-avatar.admin {
            background: #ecfdf5;
            color: #10b981;
        }

        .audience-title {
            font-weight: 700;
            font-size: 1.2rem;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .audience-desc {
            color: var(--gray-text);
            font-size: 0.9rem;
            margin-bottom: 1.25rem;
            line-height: 1.6;
        }

        .audience-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .audience-list li {
            font-size: 0.875rem;
            color: var(--dark);
            padding: 4px 0;
            display: flex;
            align-items: start;
            gap: 8px;
        }

        .audience-list li i {
            color: #10b981;
            font-size: 1rem;
            flex-shrink: 0;
            margin-top: 2px;
        }

        /* === FAQ === */
        .faq-item {
            background: white;
            border: 1px solid #f3f4f6;
            border-radius: 12px;
            margin-bottom: 12px;
            overflow: hidden;
            transition: all 0.2s ease;
        }

        .faq-item:hover {
            border-color: #dbeafe;
        }

        .faq-question {
            padding: 1.25rem 1.5rem;
            font-weight: 600;
            color: var(--dark);
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            user-select: none;
            transition: all 0.2s ease;
        }

        .faq-question:hover {
            background: var(--primary-light);
        }

        .faq-question i {
            color: var(--primary);
            transition: transform 0.25s ease;
            font-size: 1.25rem;
        }

        .faq-item.active .faq-question i {
            transform: rotate(180deg);
        }

        .faq-answer {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
            padding: 0 1.5rem;
        }

        .faq-item.active .faq-answer {
            max-height: 500px;
            padding: 0 1.5rem 1.25rem 1.5rem;
        }

        .faq-answer p {
            color: var(--gray-text);
            line-height: 1.7;
            margin: 0;
        }

        /* === CTA CLOSING === */
        .cta-section {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 4rem 2rem;
            border-radius: 24px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .cta-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1), transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .cta-title {
            font-size: 2.25rem;
            font-weight: 800;
            margin-bottom: 0.75rem;
            letter-spacing: -0.02em;
        }

        .cta-subtitle {
            opacity: 0.95;
            font-size: 1.1rem;
            margin-bottom: 2rem;
        }

        .cta-btn {
            background: white;
            color: var(--primary);
            font-weight: 700;
            padding: 0.95rem 2rem;
            border-radius: 12px;
            border: none;
            font-size: 1.05rem;
            transition: all 0.25s ease;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.1);
        }

        .cta-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
            color: var(--primary-dark);
        }

        /* === RESPONSIVE === */
        @media (max-width: 991.98px) {
            .hero-title {
                font-size: 2.5rem;
            }

            .phone-mockup {
                width: 240px;
                height: 480px;
                transform: rotate(0);
                margin: 2rem auto 0;
            }

            .landing-section {
                padding: 3.5rem 0;
            }
        }

        @media (max-width: 575.98px) {
            .hero-title {
                font-size: 2rem;
            }

            .cta-title {
                font-size: 1.5rem;
            }

            .cta-section {
                padding: 2.5rem 1.5rem;
            }
        }
    </style>
@endpush

@section('content')

    {{-- ============ HERO ============ --}}
    <section class="hero-section">
        <div class="container position-relative">
            <div class="row align-items-center">
                <div class="col-lg-7">
                    <span class="hero-badge">
                        <i class="ri ri-shield-check-line"></i>
                        Aplikasi Kesehatan Diabetes #1 Berbasis WhatsApp
                    </span>
                    <h1 class="hero-title">
                        Kelola Diabetes
                        <span class="gradient-text">dengan Dampingan</span>
                    </h1>
                    <p class="hero-subtitle">
                        Sistem pengingat minum obat dan cek gula darah yang didampingi oleh keluarga.
                        Konsisten, mudah, dan terukur — semua dalam satu platform.
                    </p>
                    <div class="d-flex gap-2 flex-wrap">
                        @auth
                            <a href="{{ route('dashboard') }}" class="btn hero-btn hero-btn-primary">
                                <i class="ri ri-dashboard-line me-2"></i>
                                Buka Dashboard
                            </a>
                        @else
                            <a href="{{ route('register') }}" class="btn hero-btn hero-btn-primary">
                                <i class="ri ri-user-add-line me-2"></i>
                                Daftar Gratis
                            </a>
                            <a href="{{ route('login') }}" class="btn hero-btn hero-btn-outline">
                                <i class="ri ri-login-circle-line me-2"></i>
                                Login
                            </a>
                        @endauth
                    </div>

                    <div class="hero-trust">
                        <span class="hero-trust-item">
                            <i class="ri ri-checkbox-circle-fill"></i> Gratis untuk Pasien
                        </span>
                        <span class="hero-trust-item">
                            <i class="ri ri-checkbox-circle-fill"></i> Tanpa Install App
                        </span>
                        <span class="hero-trust-item">
                            <i class="ri ri-checkbox-circle-fill"></i> Data Terenkripsi
                        </span>
                    </div>
                </div>

                <div class="col-lg-5 hero-visual">
                    <div class="phone-mockup">
                        <div class="phone-screen">
                            <div class="phone-header">
                                <div class="phone-greeting">Selamat pagi 👋</div>
                                <div class="phone-name">Ibu Siti</div>
                            </div>

                            <div class="phone-stats">
                                <div class="phone-stats-value">86%</div>
                                <div class="phone-stats-label">KEPATUHAN 7 HARI</div>
                            </div>

                            <div class="phone-card">
                                <div class="phone-card-icon">💊</div>
                                <div class="phone-card-info">
                                    <div class="phone-card-title">Metformin 500mg</div>
                                    <div class="phone-card-time">07:00 — Pagi</div>
                                </div>
                                <div class="phone-card-status">✓ Selesai</div>
                            </div>

                            <div class="phone-card">
                                <div class="phone-card-icon">🩸</div>
                                <div class="phone-card-info">
                                    <div class="phone-card-title">Cek Gula Darah</div>
                                    <div class="phone-card-time">07:30 — Pagi</div>
                                </div>
                                <div class="phone-card-status">✓ Selesai</div>
                            </div>

                            <div class="phone-card upcoming">
                                <div class="phone-card-icon">💊</div>
                                <div class="phone-card-info">
                                    <div class="phone-card-title">Glimepiride 2mg</div>
                                    <div class="phone-card-time">12:00 — Siang</div>
                                </div>
                                <div class="phone-card-status">Nanti</div>
                            </div>

                            <div class="phone-card upcoming">
                                <div class="phone-card-icon">🩸</div>
                                <div class="phone-card-info">
                                    <div class="phone-card-title">Cek GD 2 Jam PP</div>
                                    <div class="phone-card-time">14:00 — Siang</div>
                                </div>
                                <div class="phone-card-status">Nanti</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============ STATS ============ --}}
    <section class="stats-section">
        <div class="container">
            <div class="row g-4">
                <div class="col-6 col-md-3 stat-item">
                    <div class="stat-value">24/7</div>
                    <div class="stat-label">Pengingat Aktif</div>
                </div>
                <div class="col-6 col-md-3 stat-item">
                    <div class="stat-value">100%</div>
                    <div class="stat-label">Berbasis Web & WhatsApp</div>
                </div>
                <div class="col-6 col-md-3 stat-item">
                    <div class="stat-value">3</div>
                    <div class="stat-label">Role Terintegrasi</div>
                </div>
                <div class="col-6 col-md-3 stat-item">
                    <div class="stat-value">∞</div>
                    <div class="stat-label">Pasien per PMO</div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============ FEATURES ============ --}}
    <section class="landing-section">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title">Fitur Lengkap untuk Pengelolaan Diabetes</h2>
                <p class="section-subtitle">Semua yang Anda butuhkan dalam satu platform</p>
            </div>

            <div class="row g-4">
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon-wrap feature-icon-blue">
                            <i class="ri ri-alarm-line"></i>
                        </div>
                        <h5 class="feature-title">Pengingat Minum Obat</h5>
                        <p class="feature-desc">
                            Notifikasi otomatis via WhatsApp & web sesuai jadwal yang Anda atur.
                            Tidak ada lagi obat yang terlewat.
                        </p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon-wrap feature-icon-rose">
                            <i class="ri ri-test-tube-line"></i>
                        </div>
                        <h5 class="feature-title">Catatan Gula Darah</h5>
                        <p class="feature-desc">
                            Pantau gula darah dengan grafik tren yang jelas. Lihat kondisi
                            Anda dalam hitungan detik.
                        </p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon-wrap feature-icon-purple">
                            <i class="ri ri-team-line"></i>
                        </div>
                        <h5 class="feature-title">Pendampingan PMO</h5>
                        <p class="feature-desc">
                            Keluarga atau kerabat bisa bantu pantau kepatuhan Anda
                            sebagai Pendamping Minum Obat resmi.
                        </p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon-wrap feature-icon-green">
                            <i class="ri ri-line-chart-line"></i>
                        </div>
                        <h5 class="feature-title">Laporan Kepatuhan</h5>
                        <p class="feature-desc">
                            Dapatkan laporan visual harian dan mingguan. Cocok ditunjukkan
                            ke dokter saat kontrol rutin.
                        </p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon-wrap feature-icon-cyan">
                            <i class="ri ri-book-open-line"></i>
                        </div>
                        <h5 class="feature-title">Edukasi Diabetes</h5>
                        <p class="feature-desc">
                            Artikel, tips, dan informasi terkurasi tentang pengelolaan
                            diabetes dari sumber terpercaya.
                        </p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon-wrap feature-icon-amber">
                            <i class="ri ri-medicine-bottle-line"></i>
                        </div>
                        <h5 class="feature-title">Database Obat</h5>
                        <p class="feature-desc">
                            Katalog obat diabetes dengan informasi dosis, kategori,
                            dan satuan yang lengkap dan terverifikasi.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============ HOW IT WORKS ============ --}}
    <section class="landing-section how-section">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title">Cara Kerja</h2>
                <p class="section-subtitle">Mulai gunakan dalam 3 langkah sederhana</p>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="step-card">
                        <div class="step-number">1</div>
                        <h5 class="step-title">Daftar & Lengkapi Profil</h5>
                        <p class="step-desc">
                            Buat akun gratis sebagai pasien. Lengkapi data diabetes Anda
                            dan tunjuk PMO dari keluarga atau kerabat.
                        </p>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="step-card">
                        <div class="step-number">2</div>
                        <h5 class="step-title">Atur Jadwal Obat & Cek GD</h5>
                        <p class="step-desc">
                            Masukkan jadwal minum obat dan cek gula darah sesuai
                            anjuran dokter. Sistem akan otomatis mengingatkan.
                        </p>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="step-card">
                        <div class="step-number">3</div>
                        <h5 class="step-title">Pantau & Konsisten</h5>
                        <p class="step-desc">
                            Terima pengingat tiap jadwal, konfirmasi setelah selesai,
                            dan lihat progress kepatuhan Anda terus meningkat.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============ FOR WHO ============ --}}
    <section class="landing-section">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title">Untuk Siapa Aplikasi Ini?</h2>
                <p class="section-subtitle">Solusi terintegrasi untuk semua pihak dalam pengelolaan diabetes</p>
            </div>

            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="audience-card">
                        <div class="audience-avatar pasien">
                            <i class="ri ri-user-heart-line"></i>
                        </div>
                        <h5 class="audience-title">Pasien Diabetes</h5>
                        <p class="audience-desc">
                            Untuk Anda yang ingin lebih konsisten dalam pengelolaan
                            penyakit diabetes sehari-hari.
                        </p>
                        <ul class="audience-list">
                            <li><i class="ri ri-check-line"></i> Tidak lupa minum obat</li>
                            <li><i class="ri ri-check-line"></i> Catat gula darah dengan mudah</li>
                            <li><i class="ri ri-check-line"></i> Lihat progress kepatuhan</li>
                            <li><i class="ri ri-check-line"></i> Akses edukasi diabetes</li>
                        </ul>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="audience-card">
                        <div class="audience-avatar pmo">
                            <i class="ri ri-shield-user-line"></i>
                        </div>
                        <h5 class="audience-title">Pendamping (PMO)</h5>
                        <p class="audience-desc">
                            Untuk keluarga atau kerabat yang ingin mendampingi orang
                            terdekat dalam pengelolaan diabetesnya.
                        </p>
                        <ul class="audience-list">
                            <li><i class="ri ri-check-line"></i> Pantau pasien dampingan</li>
                            <li><i class="ri ri-check-line"></i> Bantu konfirmasi obat</li>
                            <li><i class="ri ri-check-line"></i> Notifikasi saat skip jadwal</li>
                            <li><i class="ri ri-check-line"></i> Lihat laporan kepatuhan</li>
                        </ul>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="audience-card">
                        <div class="audience-avatar admin">
                            <i class="ri ri-hospital-line"></i>
                        </div>
                        <h5 class="audience-title">Tenaga Kesehatan</h5>
                        <p class="audience-desc">
                            Untuk puskesmas, klinik, dan posyandu yang ingin memantau
                            program kesehatan pasien diabetes secara terstruktur.
                        </p>
                        <ul class="audience-list">
                            <li><i class="ri ri-check-line"></i> Monitor banyak pasien</li>
                            <li><i class="ri ri-check-line"></i> Laporan kepatuhan agregat</li>
                            <li><i class="ri ri-check-line"></i> Kelola database obat</li>
                            <li><i class="ri ri-check-line"></i> Konten edukasi & pengumuman</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============ FAQ ============ --}}
    <section class="landing-section how-section">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title">Pertanyaan Sering Diajukan</h2>
                <p class="section-subtitle">Hal-hal yang mungkin ingin Anda tahu sebelum mulai</p>
            </div>

            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFaq(this)">
                            <span>Apakah aplikasi ini gratis?</span>
                            <i class="ri ri-arrow-down-s-line"></i>
                        </div>
                        <div class="faq-answer">
                            <p>
                                Ya, aplikasi ini gratis digunakan untuk pasien dan PMO. Semua fitur
                                pengingat obat, cek gula darah, dan laporan kepatuhan dapat diakses
                                tanpa biaya.
                            </p>
                        </div>
                    </div>

                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFaq(this)">
                            <span>Apakah saya perlu install aplikasi?</span>
                            <i class="ri ri-arrow-down-s-line"></i>
                        </div>
                        <div class="faq-answer">
                            <p>
                                Tidak. Aplikasi berbasis web dan dapat diakses langsung dari
                                browser di HP, tablet, atau laptop. Pengingat dikirim melalui
                                WhatsApp sehingga Anda tidak perlu menginstal apapun.
                            </p>
                        </div>
                    </div>

                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFaq(this)">
                            <span>Apakah data kesehatan saya aman?</span>
                            <i class="ri ri-arrow-down-s-line"></i>
                        </div>
                        <div class="faq-answer">
                            <p>
                                Sangat aman. Data Anda terenkripsi dan hanya dapat diakses oleh
                                Anda sendiri dan PMO yang Anda tunjuk. Kami berkomitmen menjaga
                                privasi data kesehatan sesuai standar keamanan medis.
                            </p>
                        </div>
                    </div>

                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFaq(this)">
                            <span>Apa itu PMO (Pendamping Minum Obat)?</span>
                            <i class="ri ri-arrow-down-s-line"></i>
                        </div>
                        <div class="faq-answer">
                            <p>
                                PMO adalah orang yang Anda tunjuk untuk mendampingi pengelolaan
                                diabetes Anda — biasanya anggota keluarga, pasangan, atau kerabat
                                dekat. PMO akan membantu mengingatkan, memantau kepatuhan, dan
                                membantu konfirmasi jadwal obat saat Anda butuh.
                            </p>
                        </div>
                    </div>

                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFaq(this)">
                            <span>Apakah dokter saya akan diberi akses?</span>
                            <i class="ri ri-arrow-down-s-line"></i>
                        </div>
                        <div class="faq-answer">
                            <p>
                                Saat ini akses dokter belum tersedia secara langsung. Namun, Anda
                                dapat mengunduh laporan kepatuhan dalam format yang bisa Anda
                                tunjukkan ke dokter saat kontrol rutin.
                            </p>
                        </div>
                    </div>

                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFaq(this)">
                            <span>Bagaimana cara mendapatkan pengingat WhatsApp?</span>
                            <i class="ri ri-arrow-down-s-line"></i>
                        </div>
                        <div class="faq-answer">
                            <p>
                                Cukup daftarkan nomor WhatsApp aktif Anda saat registrasi. Sistem
                                akan otomatis mengirim pesan pengingat sesuai jadwal yang Anda atur,
                                lengkap dengan dosis obat dan instruksi.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============ CTA CLOSING ============ --}}
    <section class="landing-section">
        <div class="container">
            <div class="cta-section">
                <h2 class="cta-title">Siap Memulai Pengelolaan Diabetes yang Lebih Baik?</h2>
                <p class="cta-subtitle">
                    Bergabung sekarang dan rasakan kemudahan dalam menjaga kepatuhan minum obat dan cek gula darah.
                </p>
                @auth
                    <a href="{{ route('dashboard') }}" class="cta-btn d-inline-flex align-items-center gap-2">
                        <i class="ri ri-dashboard-line"></i>
                        Buka Dashboard
                    </a>
                @else
                    <a href="{{ route('register') }}" class="cta-btn d-inline-flex align-items-center gap-2">
                        <i class="ri ri-user-add-line"></i>
                        Daftar Gratis Sekarang
                    </a>
                @endauth
            </div>
        </div>
    </section>

@endsection

@push('scripts')
    <script>
        function toggleFaq(element) {
            const item = element.parentElement;
            const isActive = item.classList.contains('active');

            // Close all other FAQ items (accordion behavior)
            document.querySelectorAll('.faq-item').forEach(faq => {
                faq.classList.remove('active');
            });

            // Toggle current
            if (!isActive) {
                item.classList.add('active');
            }
        }
    </script>
@endpush
