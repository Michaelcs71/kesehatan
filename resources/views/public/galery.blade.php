@extends('layouts.landing')

@section('title', 'Galeri')

@push('styles')
<style>
    .page-hero { background: linear-gradient(135deg, #fef3c7 0%, #eff6ff 100%); padding: 3rem 0; }
    .page-hero h1 { font-weight: 800; color: #111827; letter-spacing: -0.02em; }
    .gallery-item { position: relative; border-radius: 14px; overflow: hidden; cursor: pointer; background:#000; }
    .gallery-item img { width: 100%; height: 230px; object-fit: cover; transition: transform .35s ease, opacity .35s ease; display:block; }
    .gallery-item:hover img { transform: scale(1.06); opacity: .85; }
    .gallery-overlay { position:absolute; left:0; right:0; bottom:0; padding: 2rem 1rem .9rem; color:#fff;
        background: linear-gradient(transparent, rgba(0,0,0,.75)); opacity:0; transition: opacity .25s ease; }
    .gallery-item:hover .gallery-overlay { opacity:1; }
    .gallery-overlay .t { font-weight:700; font-size:.95rem; }
    .empty-state { color:#9ca3af; }
    #lightboxImg { max-height: 80vh; width:auto; max-width:100%; border-radius: 8px; }
</style>
@endpush

@section('content')

<section class="page-hero">
    <div class="container">
        <div class="d-flex align-items-center gap-2 mb-2 text-warning fw-semibold">
            <i class="ri ri-image-2-line"></i> Dokumentasi Kegiatan
        </div>
        <h1 class="mb-1">Galeri</h1>
        <p class="text-muted mb-0">Kumpulan foto kegiatan dan dokumentasi layanan kami.</p>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="row g-3">
            @forelse($items as $item)
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="gallery-item shadow-sm"
                         data-bs-toggle="modal" data-bs-target="#lightbox"
                         data-img="{{ $item->gambar_url }}"
                         data-judul="{{ $item->judul }}"
                         data-desc="{{ $item->deskripsi }}">
                        <img src="{{ $item->gambar_url }}" alt="{{ $item->judul }}" loading="lazy">
                        <div class="gallery-overlay">
                            <div class="t">{{ $item->judul }}</div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12 text-center py-5 empty-state">
                    <i class="ri ri-image-line" style="font-size: 3rem;"></i>
                    <p class="mt-3 mb-0">Belum ada foto di galeri saat ini.</p>
                </div>
            @endforelse
        </div>
    </div>
</section>

{{-- Lightbox Modal --}}
<div class="modal fade" id="lightbox" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 bg-transparent">
            <div class="modal-body text-center p-0">
                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-2" data-bs-dismiss="modal" style="z-index:5;"></button>
                <img id="lightboxImg" src="" alt="">
                <div class="text-white mt-3">
                    <h5 class="fw-bold mb-1" id="lightboxTitle"></h5>
                    <p class="mb-0 text-white-50 small" id="lightboxDesc"></p>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    const lightbox = document.getElementById('lightbox');
    if (lightbox) {
        lightbox.addEventListener('show.bs.modal', function (event) {
            const trigger = event.relatedTarget;
            document.getElementById('lightboxImg').src = trigger.getAttribute('data-img');
            document.getElementById('lightboxTitle').textContent = trigger.getAttribute('data-judul') || '';
            document.getElementById('lightboxDesc').textContent = trigger.getAttribute('data-desc') || '';
        });
    }
</script>
@endpush
