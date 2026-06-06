@extends('layouts.app')

@section('title', 'Detail Pengumuman')

@section('page-header')
    <nav aria-label="breadcrumb" class="small">
        <ol class="breadcrumb mb-2">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('konten-pengumuman.index') }}" class="text-decoration-none">Pengumuman</a></li>
            <li class="breadcrumb-item active" id="bcEntityName">Detail</li>
        </ol>
    </nav>
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="ri ri-megaphone-line text-primary me-2"></i>
                <span id="entityName">Detail Pengumuman</span>
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
    <div class="col-lg-8">
        <x-card title="Isi Pengumuman" icon="ri-megaphone-line">
            <div class="text-center mb-3 d-none" id="gambarWrap">
                <img id="detail-gambar" src="" alt="Gambar" class="img-fluid rounded border" style="max-height: 320px;">
            </div>

            <x-detail-field label="Judul" icon="ri-text">
                <p class="form-control-plaintext mb-0 fw-semibold" id="detail-judul">-</p>
            </x-detail-field>

            <x-detail-field label="Ringkasan" icon="ri-file-text-line">
                <p class="form-control-plaintext mb-0" id="detail-ringkasan" style="white-space: pre-line;">-</p>
            </x-detail-field>

            <x-detail-field label="Isi" icon="ri-article-line" :class="'mb-0'">
                <div class="form-control-plaintext mb-0" id="detail-konten" style="white-space: pre-line;">-</div>
            </x-detail-field>
        </x-card>
    </div>

    <div class="col-lg-4">
        <x-card title="Status & Audit" icon="ri-information-line">
            <table class="table table-borderless table-sm mb-0">
                <tr>
                    <td class="text-muted small">Status</td>
                    <td id="detail-status">-</td>
                </tr>
                <tr>
                    <td class="text-muted small">Tanggal Publikasi</td>
                    <td class="fw-semibold small" id="detail-published_at">-</td>
                </tr>
                <tr>
                    <td class="text-muted small">Dibuat oleh</td>
                    <td class="fw-semibold small" id="detail-creator">-</td>
                </tr>
                <tr>
                    <td class="text-muted small">Dibuat pada</td>
                    <td class="fw-semibold small" id="detail-created_at">-</td>
                </tr>
                <tr>
                    <td class="text-muted small">Diupdate pada</td>
                    <td class="fw-semibold small" id="detail-updated_at">-</td>
                </tr>
            </table>
        </x-card>
    </div>
</div>

@endsection

@push('scripts')
<script>
window.whenKesehatanReady(function() {
    'use strict';

    const CONFIG = {
        ROUTES: {
            INDEX:     '{{ route('konten-pengumuman.index') }}',
            EDIT:      '{{ route('konten-pengumuman.edit', $id) }}',
            DESTROY:   '{{ route('konten-pengumuman.destroy', $id) }}',
            SHOW_DATA: '{{ route('konten-pengumuman.show-data', $id) }}',
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
        $('#detail-ringkasan').text(d.ringkasan || '-');
        $('#detail-konten').text(d.konten || '-');
        $('#detail-status').html(STATUS_BADGES[d.is_published ? 'true' : 'false']);
        $('#detail-published_at').text(d.published_at ? formatDate(d.published_at) : '-');
        $('#detail-creator').text(d.creator?.name || '-');
        $('#detail-created_at').text(formatDate(d.created_at));
        $('#detail-updated_at').text(d.updated_at ? formatDate(d.updated_at) : '-');

        if (d.gambar_path) {
            $('#detail-gambar').attr('src', '/storage/' + d.gambar_path);
            $('#gambarWrap').removeClass('d-none');
        }

        const actions = [];
        @can('konten-pengumuman.edit')
            actions.push('<a href="' + CONFIG.ROUTES.EDIT + '" class="btn btn-outline-warning btn-sm"><i class="ri ri-pencil-line me-1"></i> Edit</a>');
        @endcan
        @can('konten-pengumuman.delete')
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
            title: 'Hapus pengumuman?',
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
