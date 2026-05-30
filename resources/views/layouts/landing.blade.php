<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Kesehatan') &mdash; Sistem Pengingat Diabetes</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')

    <style>
        /* Override sidebar margin untuk layout landing */
        body { margin-left: 0 !important; }
        @media (min-width: 992px) {
            body { margin-left: 0 !important; }
        }
    </style>
</head>
<body class="bg-light">

{{-- Top Navbar (Public) --}}
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center" href="/">
            <span style="font-size: 1.4rem;">💊</span>
            <span class="text-primary ms-2">Kesehatan</span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navMain">
            <ul class="navbar-nav me-auto ms-3">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('public.pengumuman') ? 'active fw-semibold' : '' }}"
                       href="{{ route('public.pengumuman') }}">
                        Pengumuman
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('public.edukasi') ? 'active fw-semibold' : '' }}"
                       href="{{ route('public.edukasi') }}">
                        Edukasi
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('public.galery') ? 'active fw-semibold' : '' }}"
                       href="{{ route('public.galery') }}">
                        Galery
                    </a>
                </li>
            </ul>

            <div class="d-flex gap-2">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn btn-primary fw-semibold">
                        <i class="ri ri-dashboard-line me-1"></i>
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-outline-primary fw-semibold">
                        <i class="ri ri-login-circle-line me-1"></i>
                        Login
                    </a>
                    <a href="{{ route('register') }}" class="btn btn-primary fw-semibold">
                        <i class="ri ri-user-add-line me-1"></i>
                        Daftar
                    </a>
                @endauth
            </div>
        </div>
    </div>
</nav>

{{-- Content --}}
<main>
    @yield('content')
</main>

{{-- Footer --}}
<footer class="bg-white border-top py-4 text-center text-muted mt-auto">
    <div class="container">
        <small>&copy; {{ date('Y') }} Kesehatan App. All rights reserved.</small>
    </div>
</footer>

@stack('scripts')

</body>
</html>