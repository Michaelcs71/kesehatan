@extends('layouts.landing')

@section('title', $artikel->judul)

@push('styles')
<style>
    .article-hero img { width: 100%; max-height: 420px; object-fit: cover; border-radius: 16px; }
    .article-cat { font-size:.75rem; font-weight:600; text-transform:uppercase; letter-spacing:.05em; color:#06b6d4; }
    .article-body { font-size: 1.05rem; line-height: 1.8; color:#374151; white-space: pre-line; }
    .related-card { border:1px solid #f0f1f3; border-radius:12px; overflow:hidden; background:#fff; height:100%; transition:all .2s ease; }
    .related-card:hover { box-shadow:0 8px 24px rgba(0,0,0,.06); transform: translateY(-2px); }
    .related-card img { height:130px; width:100%; object-fit:cover; }
</style>
@endpush

@section('content')

<section class="py-5">
    <div class="container" style="max-width: 820px;">
        <a href="{{ route('public.edukasi') }}" class="text-decoration-none text-muted small mb-3 d-inline-block">
            <i class="ri ri-arrow-left-line me-1"></i> Kembali ke Edukasi
        </a>

        @if($artikel->kategori)
            <div class="article-cat mb-2">{{ $artikel->kategori }}</div>
        @endif
        <h1 class="fw-bold mb-2" style="letter-spacing:-0.02em;">{{ $artikel->judul }}</h1>
        <div class="text-muted small mb-4">
            <i class="ri ri-calendar-line me-1"></i>
            {{ optional($artikel->published_at ?? $artikel->created_at)->translatedFormat('d F Y') }}
            @if($artikel->creator)
                <span class="mx-2">•</span><i class="ri ri-user-line me-1"></i>{{ $artikel->creator->name }}
            @endif
        </div>

        @if($artikel->gambar_url)
            <div class="article-hero mb-4">
                <img src="{{ $artikel->gambar_url }}" alt="{{ $artikel->judul }}">
            </div>
        @endif

        @if($artikel->ringkasan)
            <p class="lead text-muted">{{ $artikel->ringkasan }}</p>
        @endif

        <div class="article-body mt-3">{{ $artikel->konten }}</div>
    </div>
</section>

@if($lainnya->isNotEmpty())
    <section class="py-5 bg-white border-top">
        <div class="container">
            <h4 class="fw-bold mb-4">Artikel Lainnya</h4>
            <div class="row g-4">
                @foreach($lainnya as $item)
                    <div class="col-md-4">
                        <a href="{{ route('public.edukasi.show', $item->slug) }}" class="text-decoration-none">
                            <div class="related-card shadow-sm">
                                @if($item->gambar_url)
                                    <img src="{{ $item->gambar_url }}" alt="{{ $item->judul }}">
                                @endif
                                <div class="p-3">
                                    <h6 class="fw-bold mb-1 text-dark">{{ $item->judul }}</h6>
                                    <p class="text-muted small mb-0">
                                        {{ \Illuminate\Support\Str::limit($item->ringkasan ?: strip_tags($item->konten), 70) }}
                                    </p>
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

@endsection
