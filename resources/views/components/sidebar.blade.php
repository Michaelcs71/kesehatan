{{-- Sidebar Universal --}}
@php
    $user = auth()->user();
    $isPasien = $user->isPasien();
    $isPmo = $user->isPmo();
    $isAdmin = $user->isAdmin() || $user->isSuperadmin();

    // Pending count badge (cuma admin yang lihat)
    $pendingObatCount = 0;
    if ($isAdmin) {
        try {
            $pendingObatCount = \App\Models\MasterObat::where('status', 'pending')->count();
        } catch (\Exception $e) {
        }
    }
@endphp

<ul class="sidebar-nav" data-coreui="navigation">

    {{-- ============ DASHBOARD ============ --}}
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('*.dashboard') || request()->routeIs('dashboard') ? 'active' : '' }}"
            href="{{ route('dashboard') }}">
            <span class="nav-icon"><i class="ri ri-dashboard-line"></i></span> Dashboard
        </a>
    </li>

    {{-- ============ TRANSAKSI ============ --}}
    @canany(['jadwal-mo.index', 'jadwal-cgd.index', 'pengingat-mo.index', 'pengingat-cgd.index'])
        <li class="nav-title">Transaksi</li>

        @can('jadwal-mo.index')
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('jadwal-mo.*') ? 'active' : '' }}"
                    href="{{ route('jadwal-mo.index') }}">
                    <span class="nav-icon"><i class="ri ri-calendar-check-line"></i></span> Jadwal Minum Obat
                </a>
            </li>
        @endcan

        @can('jadwal-cgd.index')
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('jadwal-cgd.*') ? 'active' : '' }}"
                    href="{{ route('jadwal-cgd.index') }}">
                    <span class="nav-icon"><i class="ri ri-test-tube-line"></i></span> Jadwal Cek Gula Darah
                </a>
            </li>
        @endcan

        {{-- Pengingat - hanya muncul kalau user punya permission --}}
        @can('pengingat-mo.index')
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('pengingat-mo.*') ? 'active' : '' }}"
                    href="{{ route('pengingat-mo.index') }}">
                    <span class="nav-icon"><i class="ri ri-notification-2-line"></i></span> Pengingat Minum Obat
                </a>
            </li>
        @endcan
        @can('pengingat-cgd.index')
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('pengingat-cgd.*') ? 'active' : '' }}"
                    href="{{ route('pengingat-cgd.index') }}">
                    <span class="nav-icon"><i class="ri ri-test-tube-line"></i></span> Pengingat Cek Gula Darah
                </a>
            </li>
        @endcan
    @endcanany

    {{-- ============ LAPORAN ============ --}}
    @can('laporan-kepatuhan.index')
        <li class="nav-title">Laporan</li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.laporan.kepatuhan') ? 'active' : '' }}"
                href="{{ route('admin.laporan.kepatuhan') }}">
                <span class="nav-icon"><i class="ri ri-bar-chart-line"></i></span> Kepatuhan Pasien
            </a>
        </li>
    @endcan

    {{-- ============ KONTEN PUBLIK ============ --}}
    @canany(['konten-pengumuman.index', 'konten-edukasi.index', 'konten-galery.index'])
        <li class="nav-title">Konten Publik</li>

        @can('konten-pengumuman.index')
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('konten-pengumuman.*') ? 'active' : '' }}"
                    href="{{ route('konten-pengumuman.index') }}">
                    <span class="nav-icon"><i class="ri ri-megaphone-line"></i></span> Pengumuman
                </a>
            </li>
        @endcan
        @can('konten-edukasi.index')
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('konten-edukasi.*') ? 'active' : '' }}"
                    href="{{ route('konten-edukasi.index') }}">
                    <span class="nav-icon"><i class="ri ri-book-line"></i></span> Edukasi
                </a>
            </li>
        @endcan
        @can('konten-galery.index')
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('konten-galery.*') ? 'active' : '' }}"
                    href="{{ route('konten-galery.index') }}">
                    <span class="nav-icon"><i class="ri ri-image-line"></i></span> Galery
                </a>
            </li>
        @endcan
    @endcanany

    {{-- ============ MASTER DATA ============ --}}
    <li class="nav-title">Master Data</li>

    @can('master-user.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('master-user.*') ? 'active' : '' }}"
                href="{{ route('master-user.index') }}">
                <span class="nav-icon"><i class="ri ri-team-line"></i></span> User
            </a>
        </li>
    @endcan

    @can('pasien-pmo.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('pasien-pmo.*') ? 'active' : '' }}"
                href="{{ route('pasien-pmo.index') }}">
                <span class="nav-icon"><i class="ri ri-links-line"></i></span> Pasien PMO
            </a>
        </li>
    @endcan

    @can('master-kategori-obat.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('master-kategori-obat.*') ? 'active' : '' }}"
                href="{{ route('master-kategori-obat.index') }}">
                <span class="nav-icon"><i class="ri ri-price-tag-3-line"></i></span> Kategori Obat
            </a>
        </li>
    @endcan

    @can('master-satuan-obat.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('master-satuan-obat.*') ? 'active' : '' }}"
                href="{{ route('master-satuan-obat.index') }}">
                <span class="nav-icon"><i class="ri ri-ruler-line"></i></span> Satuan Obat
            </a>
        </li>
    @endcan

    @can('master-obat.index')
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('master-obat.*') ? 'active' : '' }}"
                href="{{ route('master-obat.index') }}">
                <span class="nav-icon"><i class="ri ri-medicine-bottle-line"></i></span> Obat
                @if ($pendingObatCount > 0)
                    <span class="badge bg-warning text-dark ms-auto">{{ $pendingObatCount }}</span>
                @endif
            </a>
        </li>
    @endcan

    {{-- ============ PENGATURAN ============ --}}
    @can('pengaturan-pengingat.index')
        <li class="nav-title">Pengaturan</li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('pengaturan-pengingat.*') ? 'active' : '' }}"
                href="{{ route('pengaturan-pengingat.index') }}">
                <span class="nav-icon"><i class="ri ri-settings-3-line"></i></span> Pengaturan Pengingat
            </a>
        </li>
    @endcan

    {{-- ============ AKUN ============ --}}
    <li class="nav-title">Akun</li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('profile.edit') ? 'active' : '' }}"
            href="{{ route('profile.edit') }}">
            <span class="nav-icon"><i class="ri ri-user-line"></i></span> Profil Saya
        </a>
    </li>
</ul>
