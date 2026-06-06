@extends('layouts.landing')

@section('title', 'Edukasi')

@push('styles')
<style>
    .page-hero { background: linear-gradient(135deg, #ecfdf5 0%, #eff6ff 100%); padding: 3rem 0; }
    .page-hero h1 { font-weight: 800; color: #111827; letter-spacing: -0.02em; }
    .edu-card { border: 1px solid #f0f1f3; border-radius: 14px; overflow: hidden; background: #fff; height: 100%; transition: all .2s ease; }
    .edu-card:hover { box-shadow: 0 10px 30px rgba(0,0,0,.07); transform: translateY(-3px); }
    .edu-card img { height: 180px; width: 100%; object-fit: cover; }
    .edu-thumb-fallback { height: 180px; display:flex; align-items:center; justify-content:center; background:linear-gradient(135deg,#3b82f6,#06b6d4); color:#fff; font-size:2.5rem; }
    .edu-cat { font-size:.72rem; font-weight:600; text-transform:uppercase; letter-spacing:.04em; color:#06b6d4; }
    .edu-title { font-weight:700; color:#111827; }
    .filter-pill { cursor:pointer; }
    .empty-state { color:#9ca3af; }
</style>
@endpush

@section('content')

<section class="page-hero">
    <div class="container">
        <div class="d-flex align-items-center gap-2 mb-2 text-success fw-semibold">
            <i class="ri ri-book-open-line"></i> Pusat Edukasi
        </div>
        <h1 class="mb-1">Edukasi Kesehatan</h1>
        <p class="text-muted mb-0">Artikel dan informasi untuk membantu Anda mengelola diabetes dengan lebih baik.</p>
    </div>
</section>

<section class="py-5">
    <div class="container">
        @if($kategoris->isNotEmpty())
            <div class="d-flex flex-wrap gap-2 mb-4" id="kategoriFilter">
                <span class="badge rounded-pill bg-primary filter-pill px-3 py-2" data-cat="">Semua</span>
                @foreach($kategoris as $kat)
                    <span class="badge rounded-pill bg-light text-dark border filter-pill px-3 py-2" data-cat="{{ $kat }}">{{ $kat }}</span>
                @endforeach
            </div>
        @endif

        <div class="row g-4" id="eduGrid">
            @forelse($items as $item)
                <div class="col-md-6 col-lg-4 edu-item" data-cat="{{ $item->kategori }}">
                    <a href="{{ route('public.edukasi.show', $item->slug) }}" class="text-decoration-none">
                        <div class="edu-card shadow-sm">
                            @if($item->gambar_url)
                                <img src="{{ $item->gambar_url }}" alt="{{ $item->judul }}">
                            @else
                                <div class="edu-thumb-fallback"><i class="ri ri-book-2-line"></i></div>
                            @endif
                            <div class="p-3">
                                @if($item->kategori)
                                    <div class="edu-cat mb-1">{{ $item->kategori }}</div>
                                @endif
                                <h5 class="edu-title mb-2">{{ $item->judul }}</h5>
                                <p class="text-muted small mb-0">
                                    {{ \Illuminate\Support\Str::limit($item->ringkasan ?: strip_tags($item->konten), 110) }}
                                </p>
                            </div>
                        </div>
                    </a>
                </div>
            @empty
                <div class="col-12 text-center py-5 empty-state">
                    <i class="ri ri-book-line" style="font-size: 3rem;"></i>
                    <p class="mt-3 mb-0">Belum ada artikel edukasi saat ini.</p>
                </div>
            @endforelse
        </div>
    </div>
</section>

@endsection

@push('scripts')
<script>
    document.querySelectorAll('#kategoriFilter .filter-pill').forEach(function (pill) {
        pill.addEventListener('click', function () {
            const cat = this.getAttribute('data-cat');
            document.querySelectorAll('#kategoriFilter .filter-pill').forEach(function (p) {
                p.classList.remove('bg-primary', 'text-white');
                p.classList.add('bg-light', 'text-dark', 'border');
            });
            this.classList.add('bg-primary');
            this.classList.remove('bg-light', 'text-dark', 'border');

            document.querySelectorAll('#eduGrid .edu-item').forEach(function (item) {
                item.style.display = (!cat || item.getAttribute('data-cat') === cat) ? '' : 'none';
            });
        });
    });
</script>
@endpush
