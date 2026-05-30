@extends('layouts.app')

@section('title', 'Detail Kategori')

@section('page-header')
    <nav aria-label="breadcrumb" class="small">
        <ol class="breadcrumb mb-2">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('master-kategori-obat.index') }}" class="text-decoration-none">Master Kategori Obat</a></li>
            <li class="breadcrumb-item active" id="bcEntityName">Detail</li>
        </ol>
    </nav>
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="ri ri-price-tag-3-line text-primary me-2"></i>
                <span id="entityName">Detail Kategori</span>
            </h4>
            <div class="text-muted small" id="entityStatus">
                <span class="spinner-border spinner-border-sm" role="status"></span>
            </div>
        </div>

        <div class="d-flex gap-2" id="headerActions" style="display:none !important;">
            {{-- Filled via JS --}}
        </div>
    </div>
@endsection

@section('content')

<div class="row g-4">
    <div class="col-lg-8">
        <x-card title="Informasi Kategori" icon="ri-price-tag-3-line">
            <x-detail-field label="Nama Kategori" icon="ri-text">
                <p class="form-control-plaintext mb-0" id="detail-nama">-</p>
            </x-detail-field>

            <x-detail-field label="Deskripsi" icon="ri-file-text-line">
                <p class="form-control-plaintext mb-0" id="detail-deskripsi" style="white-space: pre-line;">-</p>
            </x-detail-field>

            <x-detail-field label="Status" icon="ri-toggle-line" :class="'mb-0'">
                <p class="form-control-plaintext mb-0" id="detail-status">-</p>
            </x-detail-field>
        </x-card>

        <div class="mt-3">
            <x-card title="Penggunaan" icon="ri-medicine-bottle-line">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-primary-subtle text-primary rounded p-3" style="width:60px;height:60px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;">
                        <i class="ri ri-medicine-bottle-line"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Jumlah obat dalam kategori ini</div>
                        <div class="fw-bold fs-3" id="detail-obats_count">0</div>
                    </div>
                </div>
                <div class="mt-3 small text-muted" id="usageNote">
                    Loading...
                </div>
            </x-card>
        </div>
    </div>

    <div class="col-lg-4">
        <x-card>
            <h6 class="fw-bold mb-3 small text-muted text-uppercase">ID & Audit</h6>
            <table class="table table-borderless table-sm mb-0">
                <tr>
                    <td class="text-muted small">ID</td>
                    <td><code class="small" id="detail-id">-</code></td>
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
                    <td class="text-muted small">Diupdate oleh</td>
                    <td class="fw-semibold small" id="detail-updater">-</td>
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
        ID: '{{ $id }}',
        ROUTES: {
            INDEX:     '{{ route('master-kategori-obat.index') }}',
            EDIT:      '{{ route('master-kategori-obat.edit', $id) }}',
            DESTROY:   '{{ route('master-kategori-obat.destroy', $id) }}',
            SHOW_DATA: '{{ route('master-kategori-obat.show-data', $id) }}',
        },
    };

    const STATUS_BADGES = {
        true:  '<span class="badge bg-success-subtle text-success px-3 py-2"><i class="ri ri-check-line"></i> Aktif</span>',
        false: '<span class="badge bg-secondary-subtle text-secondary px-3 py-2"><i class="ri ri-close-line"></i> Nonaktif</span>',
    };

    const formatDate = (iso) => {
        if (!iso) return '-';
        const d = new Date(iso);
        return d.toLocaleDateString('id-ID', {
            day: 'numeric', month: 'short', year: 'numeric',
            hour: '2-digit', minute: '2-digit',
        });
    };

    let dataKategori = null;

    // LOAD DATA
    $.ajax({
        url: CONFIG.ROUTES.SHOW_DATA,
        method: 'GET',
    }).done(function(d) {
        dataKategori = d;

        // Header
        $('#entityName').text(d.nama);
        $('#bcEntityName').text(d.nama);
        $('#entityStatus').html(STATUS_BADGES[d.is_active ? 'true' : 'false']);

        // Detail
        $('#detail-nama').text(d.nama);
        $('#detail-deskripsi').text(d.deskripsi || '-');
        $('#detail-status').html(STATUS_BADGES[d.is_active ? 'true' : 'false']);
        $('#detail-id').text(d.id);
        $('#detail-obats_count').text(d.obats_count || 0);

        // Usage note
        const count = d.obats_count || 0;
        if (count === 0) {
            $('#usageNote').html('<i class="ri ri-information-line"></i> Kategori ini belum digunakan oleh obat manapun. Aman untuk dihapus.');
        } else {
            $('#usageNote').html(`<i class="ri ri-error-warning-line text-warning"></i> Kategori ini sedang digunakan oleh <strong>${count}</strong> obat. Tidak dapat dihapus sebelum semua obat dipindahkan ke kategori lain.`);
        }

        // Audit
        $('#detail-creator').text(d.creator?.name || '-');
        $('#detail-created_at').text(formatDate(d.created_at));
        $('#detail-updater').text(d.updater?.name || '-');
        $('#detail-updated_at').text(d.updated_at ? formatDate(d.updated_at) : '-');

        // Header actions
        const actions = [];
        @can('master-kategori-obat.edit')
            actions.push('<a href="' + CONFIG.ROUTES.EDIT + '" class="btn btn-outline-warning btn-sm"><i class="ri ri-pencil-line me-1"></i> Edit</a>');
        @endcan
        @can('master-kategori-obat.delete')
            // Hanya tampil tombol delete kalau obats_count == 0
            if (count === 0) {
                actions.push('<button class="btn btn-outline-danger btn-sm" id="btnDelete"><i class="ri ri-delete-bin-line me-1"></i> Hapus</button>');
            }
        @endcan
        if (actions.length) {
            $('#headerActions').html(actions.join('')).attr('style', 'display:flex !important;');
        }
    }).fail(function(xhr) {
        Swal.fire({
            title: 'Error!',
            text: xhr.responseJSON?.message || 'Gagal memuat data',
            icon: 'error',
        }).then(() => window.location.href = CONFIG.ROUTES.INDEX);
    });

    // DELETE
    $(document).on('click', '#btnDelete', function() {
        Swal.fire({
            title: 'Hapus kategori?',
            html: 'Kategori <strong>' + (dataKategori?.nama || '') + '</strong> akan dihapus.<br><small class="text-muted">Tindakan ini tidak bisa dibatalkan.</small>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal',
            customClass: {
                confirmButton: 'btn btn-danger me-2',
                cancelButton: 'btn btn-secondary',
            },
            buttonsStyling: false,
        }).then(function(result) {
            if (!result.isConfirmed) return;

            $.ajax({
                url: CONFIG.ROUTES.DESTROY,
                method: 'DELETE',
            }).done(function(res) {
                Swal.fire({
                    title: 'Berhasil!',
                    text: res.message,
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false,
                }).then(() => window.location.href = CONFIG.ROUTES.INDEX);
            }).fail(function(xhr) {
                Swal.fire('Gagal!', xhr.responseJSON?.message || 'Terjadi kesalahan', 'error');
            });
        });
    });
});
</script>
@endpush