@extends('layouts.app')

@section('title', 'Detail Foto')

@section('page-header')
    <nav aria-label="breadcrumb" class="small">
        <ol class="breadcrumb mb-2">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('konten-galery.index') }}" class="text-decoration-none">Galeri</a></li>
            <li class="breadcrumb-item active" id="bcEntityName">Detail</li>
        </ol>
    </nav>
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="ri ri-image-line text-primary me-2"></i>
                <span id="entityName">Detail Foto</span>
            </h4>
            <div class="text-muted small" id="entityStatus">
                <span class="spinner-border spinner-border-sm" role="status"></span>
            </div>
        </div>
        <div class="d-flex gap-2" id="headerActions" style="display:none !important;"></div>
    </div>
@endsection

@section('content')

<div class="row g-4">
    <div class="col-lg-7">
        <x-card title="Foto" icon="ri-image-line">
            <div class="text-center">
                <img id="detail-gambar" src="" alt="Foto" class="img-fluid rounded border" style="max-height: 460px;">
            </div>
        </x-card>
    </div>

    <div class="col-lg-5">
        <x-card title="Keterangan" icon="ri-text">
            <x-detail-field label="Judul" icon="ri-text">
                <p class="form-control-plaintext mb-0 fw-semibold" id="detail-judul">-</p>
            </x-detail-field>
            <x-detail-field label="Deskripsi" icon="ri-file-text-line" :class="'mb-0'">
                <p class="form-control-plaintext mb-0" id="detail-deskripsi" style="white-space: pre-line;">-</p>
            </x-detail-field>
        </x-card>

        <div class="mt-3">
            <x-card title="Status & Audit" icon="ri-information-line">
                <table class="table table-borderless table-sm mb-0">
                    <tr>
                        <td class="text-muted small">Status</td>
                        <td id="detail-status">-</td>
                    </tr>
                    <tr>
                        <td class="text-muted small">Dibuat oleh</td>
                        <td class="fw-semibold small" id="detail-creator">-</td>
                    </tr>
                    <tr>
                        <td class="text-muted small">Dibuat pada</td>
                        <td class="fw-semibold small" id="detail-created_at">-</td>
                    </tr>
                </table>
            </x-card>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
window.whenKesehatanReady(function() {
    'use strict';

    const CONFIG = {
        ROUTES: {
            INDEX:     '{{ route('konten-galery.index') }}',
            EDIT:      '{{ route('konten-galery.edit', $id) }}',
            DESTROY:   '{{ route('konten-galery.destroy', $id) }}',
            SHOW_DATA: '{{ route('konten-galery.show-data', $id) }}',
        },
    };

    const STATUS_BADGES = {
        true:  '<span class="badge bg-success-subtle text-success px-3 py-2"><i class="ri ri-global-line"></i> Publik</span>',
        false: '<span class="badge bg-secondary-subtle text-secondary px-3 py-2"><i class="ri ri-draft-line"></i> Draft</span>',
    };

    const formatDate = (iso) => {
        if (!iso) return '-';
        const d = new Date(iso);
        return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
    };

    let data = null;

    $.ajax({ url: CONFIG.ROUTES.SHOW_DATA, method: 'GET' })
    .done(function(d) {
        data = d;
        $('#entityName').text(d.judul);
        $('#bcEntityName').text(d.judul);
        $('#entityStatus').html(STATUS_BADGES[d.is_published ? 'true' : 'false']);

        $('#detail-judul').text(d.judul);
        $('#detail-deskripsi').text(d.deskripsi || '-');
        $('#detail-status').html(STATUS_BADGES[d.is_published ? 'true' : 'false']);
        $('#detail-creator').text(d.creator?.name || '-');
        $('#detail-created_at').text(formatDate(d.created_at));

        if (d.gambar_path) {
            $('#detail-gambar').attr('src', '/storage/' + d.gambar_path);
        }

        const actions = [];
        @can('konten-galery.edit')
            actions.push('<a href="' + CONFIG.ROUTES.EDIT + '" class="btn btn-outline-warning btn-sm"><i class="ri ri-pencil-line me-1"></i> Edit</a>');
        @endcan
        @can('konten-galery.delete')
            actions.push('<button class="btn btn-outline-danger btn-sm" id="btnDelete"><i class="ri ri-delete-bin-line me-1"></i> Hapus</button>');
        @endcan
        if (actions.length) {
            $('#headerActions').html(actions.join('')).attr('style', 'display:flex !important;');
        }
    })
    .fail(function(xhr) {
        Swal.fire({ title: 'Error!', text: xhr.responseJSON?.message || 'Gagal memuat data', icon: 'error' })
            .then(() => window.location.href = CONFIG.ROUTES.INDEX);
    });

    $(document).on('click', '#btnDelete', function() {
        Swal.fire({
            title: 'Hapus foto?',
            html: '<strong>' + (data?.judul || '') + '</strong> akan dihapus.<br><small class="text-muted">Tindakan ini tidak bisa dibatalkan.</small>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal',
            customClass: { confirmButton: 'btn btn-danger me-2', cancelButton: 'btn btn-secondary' },
            buttonsStyling: false,
        }).then(function(result) {
            if (!result.isConfirmed) return;
            $.ajax({ url: CONFIG.ROUTES.DESTROY, method: 'DELETE' })
            .done(function(res) {
                Swal.fire({ title: 'Berhasil!', text: res.message, icon: 'success', timer: 2000, showConfirmButton: false })
                    .then(() => window.location.href = CONFIG.ROUTES.INDEX);
            })
            .fail(function(xhr) {
                Swal.fire('Gagal!', xhr.responseJSON?.message || 'Terjadi kesalahan', 'error');
            });
        });
    });
});
</script>
@endpush
