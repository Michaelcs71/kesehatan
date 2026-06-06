@extends('layouts.landing')

@section('title', 'Pengumuman')

@push('styles')
<style>
    .page-hero { background: linear-gradient(135deg, #eff6ff 0%, #ecfeff 100%); padding: 3rem 0; }
    .page-hero h1 { font-weight: 800; color: #111827; letter-spacing: -0.02em; }
    .announce-card { border: 1px solid #f0f1f3; border-radius: 14px; transition: all .2s ease; background: #fff; overflow: hidden; }
    .announce-card:hover { box-shadow: 0 10px 30px rgba(0,0,0,.06); transform: translateY(-2px); }
    .announce-date { font-size: .8rem; color: #6b7280; }
    .announce-img { height: 200px; object-fit: cover; width: 100%; }
    .empty-state { color: #9ca3af; }
</style>
@endpush

@section('content')

<section class="page-hero">
    <div class="container">
        <div class="d-flex align-items-center gap-2 mb-2 text-primary fw-semibold">
            <i class="ri ri-megaphone-line"></i> Informasi Terbaru
        </div>
        <h1 class="mb-1">Pengumuman</h1>
        <p class="text-muted mb-0">Kabar dan informasi terbaru seputar layanan kesehatan kami.</p>
    </div>
</section>

<section class="py-5">
    <div class="container">
        @forelse($items as $item)
            <div class="announce-card shadow-sm mb-4">
                <div class="row g-0">
                    @if($item->gambar_url)
                        <div class="col-md-4">
                            <img src="{{ $item->gambar_url }}" alt="{{ $item->judul }}" class="announce-img">
                        </div>
                    @endif
                    <div class="{{ $item->gambar_url ? 'col-md-8' : 'col-12' }}">
                        <div class="p-4">
                            <div class="announce-date mb-2">
                                <i class="ri ri-calendar-line me-1"></i>
                                {{ optional($item->published_at ?? $item->created_at)->translatedFormat('d F Y') }}
                            </div>
                            <h4 class="fw-bold mb-2">{{ $item->judul }}</h4>
                            @if($item->ringkasan)
                                <p class="text-muted mb-3">{{ $item->ringkasan }}</p>
                            @endif
                            <div class="collapse" id="konten-{{ $item->id }}">
                                <div class="border-top pt-3 mb-2" style="white-space: pre-line; color:#374151;">{{ $item->konten }}</div>
                            </div>
                            <button class="btn btn-sm btn-outline-primary" type="button"
                                    data-bs-toggle="collapse" data-bs-target="#konten-{{ $item->id }}">
                                <i class="ri ri-arrow-down-s-line me-1"></i> Selengkapnya
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-5 empty-state">
                <i class="ri ri-inbox-line" style="font-size: 3rem;"></i>
                <p class="mt-3 mb-0">Belum ada pengumuman saat ini.</p>
            </div>
        @endforelse
    </div>
</section>

@endsection
