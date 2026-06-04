<header class="header header-sticky bg-white border-bottom shadow-sm" style="height: 56px;">
    <div class="container-fluid h-100">
        <div class="d-flex align-items-center h-100 w-100">

            {{-- Sidebar toggle button (visible on ALL sizes) --}}
            <button type="button" class="btn btn-link text-dark p-0 me-3" 
                    onclick="window.toggleSidebar()" 
                    title="Toggle sidebar"
                    aria-label="Toggle sidebar">
                <i class="ri ri-menu-line fs-4"></i>
            </button>

            <div class="flex-grow-1"></div>

            {{-- Tombol Aktifkan Pengingat (hanya untuk pasien) --}}
            @auth
                @if(auth()->user()->isPasien())
                    <button id="btn-aktifkan-pengingat" type="button" class="btn btn-sm btn-primary me-3">
                        <i class="ri-notification-3-line"></i> Aktifkan Pengingat
                    </button>
                @endif
            @endauth

            {{-- USER DROPDOWN --}}
            <div class="dropdown">
                <button class="btn btn-link text-decoration-none p-0 d-flex align-items-center"
                        type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="avatar bg-primary text-white" style="width: 36px; height: 36px;">
                        <span class="avatar-initials">{{ auth()->user()->getInitials() }}</span>
                    </div>
                    <div class="d-none d-md-block ms-2 text-start">
                        <div class="fw-semibold small text-dark">{{ auth()->user()->name }}</div>
                        <div class="small text-muted">{{ auth()->user()->role?->label() ?? '-' }}</div>
                    </div>
                    <i class="ri ri-arrow-down-s-line ms-2 d-none d-md-inline text-muted"></i>
                </button>

                <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2" style="min-width: 240px;">
                    <li class="dropdown-header text-center py-2">
                        <div class="fw-bold">{{ auth()->user()->name }}</div>
                        <small class="text-muted">{{ auth()->user()->email }}</small>
                    </li>
                    <li><hr class="dropdown-divider my-1"></li>
                    <li>
                        <a class="dropdown-item py-2" href="{{ route('profile.edit') }}">
                            <i class="ri ri-user-line me-2"></i> Profil Saya
                        </a>
                    </li>
                    <li><hr class="dropdown-divider my-1"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}" class="m-0">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger py-2">
                                <i class="ri ri-logout-circle-r-line me-2"></i> Logout
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</header>