<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Kesehatan') }} — @yield('title', 'Selamat Datang')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-body">

<div class="auth-wrapper d-flex align-items-center min-vh-100 py-4 px-3">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-9 col-lg-7 col-xl-5">

                {{-- Logo / Brand --}}
                <div class="text-center mb-4">
                    <a href="{{ url('/') }}" class="text-decoration-none">
                        <h2 class="fw-bold text-white mb-1">💊 Kesehatan</h2>
                        <p class="text-white-50 mb-0">Pengingat Obat Pintar</p>
                    </a>
                </div>

                {{-- Card --}}
                <div class="card border-0 shadow-lg rounded-4">
                    <div class="card-body p-4 p-md-5">
                        @yield('content')
                    </div>
                </div>

                {{-- Footer link --}}
                <div class="text-center mt-3">
                    <a href="{{ url('/') }}" class="text-white-50 small text-decoration-none">
                        ← Kembali ke beranda
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
