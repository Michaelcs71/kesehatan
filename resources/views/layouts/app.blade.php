<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="vapid-public-key" content="{{ config('pengingat.vapid.public_key') }}">

    <title>@yield('title', 'Kesehatan') &mdash; Sistem Pengingat Kesehatan</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>

<body>

    <script>
        window.whenKesehatanReady = function(callback) {
            if (window.kesehatanReady) {
                callback();
            } else {
                window.addEventListener('kesehatan:ready', callback, {
                    once: true
                });
            }
        };
    </script>

    {{-- Sidebar --}}
    <div class="sidebar sidebar-dark" id="sidebar">
        <div class="sidebar-brand d-flex align-items-center justify-content-between px-3"
            style="height: 56px; background: linear-gradient(135deg, #355798 0%, #1f3a6e 100%);">
            <a href="{{ route(auth()->user()->homeRoute()) }}"
                class="text-white text-decoration-none d-flex align-items-center">
                <span style="font-size: 1.4rem;">💊</span>
                <strong class="ms-2">Kesehatan</strong>
            </a>
            <button type="button" class="btn btn-link text-white p-0 d-md-none" id="sidebarClose"
                aria-label="Close sidebar">
                <i class="ri ri-close-line fs-4"></i>
            </button>
        </div>

        @include('components.sidebar')
    </div>

    {{-- Backdrop --}}
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    {{-- Main wrapper --}}
    <div class="wrapper d-flex flex-column min-vh-100 bg-light" id="mainWrapper">

        @include('partials.header')

        @if (\App\Services\ImpersonationService::sedangImpersonate())
            <div class="alert alert-warning border-0 rounded-0 mb-0 d-flex align-items-center justify-content-between px-3 py-2">
                <span class="small">
                    <i class="ri ri-eye-line me-1"></i>
                    <strong>Mode POV</strong> — Anda melihat sebagai
                    <strong>{{ auth()->user()->name }}</strong>
                    ({{ auth()->user()->role?->label() }}).
                </span>
                <form method="POST" action="{{ route('impersonate.leave') }}" class="m-0">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-dark">
                        <i class="ri ri-arrow-go-back-line me-1"></i> Kembali ke Super Admin
                    </button>
                </form>
            </div>
        @endif

        <div class="body flex-grow-1 px-3 py-4">
            <div class="container-fluid">
                @hasSection('page-header')
                    <div class="page-header mb-4">
                        @yield('page-header')
                    </div>
                @endif

                @if (session('success') || session('status'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="ri ri-checkbox-circle-line me-1"></i>
                        {{ session('success') ?? session('status') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="ri ri-error-warning-line me-1"></i>
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @yield('content')
            </div>
        </div>

        <footer class="footer px-3 py-2 bg-white border-top text-center text-muted small">
            <div>&copy; {{ date('Y') }} Kesehatan App. All rights reserved.</div>
        </footer>
    </div>

    <script>
        (function() {
            'use strict';
            const SIDEBAR_KEY = 'kesehatan-sidebar-collapsed';

            function ready(fn) {
                if (document.readyState !== 'loading') fn();
                else document.addEventListener('DOMContentLoaded', fn);
            }

            ready(function() {
                const sidebar = document.getElementById('sidebar');
                const backdrop = document.getElementById('sidebarBackdrop');
                const closeBtn = document.getElementById('sidebarClose');
                const body = document.body;

                if (!sidebar) return;

                // Restore desktop collapsed state
                if (window.matchMedia('(min-width: 768px)').matches) {
                    if (localStorage.getItem(SIDEBAR_KEY) === '1') {
                        body.classList.add('sidebar-collapsed');
                    }
                }

                // ===== TOGGLE FUNCTION =====
                window.toggleSidebar = function() {
                    const isMobile = window.matchMedia('(max-width: 767px)').matches;

                    if (isMobile) {
                        // Mobile: toggle BODY class (not sidebar class - avoids CoreUI conflict)
                        body.classList.toggle('mobile-sidebar-open');
                    } else {
                        // Desktop: collapse
                        body.classList.toggle('sidebar-collapsed');
                        const isCollapsed = body.classList.contains('sidebar-collapsed');
                        localStorage.setItem(SIDEBAR_KEY, isCollapsed ? '1' : '0');
                    }
                };

                // Close mobile sidebar
                function closeMobileSidebar() {
                    body.classList.remove('mobile-sidebar-open');
                }

                // Backdrop click -> close
                backdrop?.addEventListener('click', closeMobileSidebar);

                // Close button click
                closeBtn?.addEventListener('click', closeMobileSidebar);

                // Auto-close on nav link click (mobile)
                sidebar.querySelectorAll('.nav-link:not(.nav-group-toggle)').forEach(link => {
                    link.addEventListener('click', () => {
                        if (window.matchMedia('(max-width: 767px)').matches) {
                            closeMobileSidebar();
                        }
                    });
                });

                // Reset on resize to desktop
                let resizeTimeout;
                window.addEventListener('resize', () => {
                    clearTimeout(resizeTimeout);
                    resizeTimeout = setTimeout(() => {
                        if (window.matchMedia('(min-width: 768px)').matches) {
                            body.classList.remove('mobile-sidebar-open');
                        }
                    }, 200);
                });
            });
        })();
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    @stack('scripts')

</body>

</html>
