@extends('layouts.app')

@section('title', 'Detail Mapping')

@section('page-header')
    <nav aria-label="breadcrumb" class="small">
        <ol class="breadcrumb mb-2">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('pasien-pmo.index') }}" class="text-decoration-none">Pasien PMO</a>
            </li>
            <li class="breadcrumb-item active" id="bcEntityName">Detail</li>
        </ol>
    </nav>
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="ri ri-links-line text-primary me-2"></i>
                <span id="entityName">Detail Mapping</span>
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

            {{-- ============ PASIEN INFO ============ --}}
            <x-card title="Pasien" icon="ri-user-heart-line">
                <div class="row g-3">
                    <div class="col-md-7">
                        <x-detail-field label="Nama Pasien" icon="ri-user-line">
                            <p class="form-control-plaintext mb-0" id="detail-nama_pasien">-</p>
                        </x-detail-field>
                    </div>
                    <div class="col-md-5">
                        <x-detail-field label="NIK" icon="ri-fingerprint-line">
                            <p class="form-control-plaintext mb-0"><code id="detail-nik">-</code></p>
                        </x-detail-field>
                    </div>
                    <div class="col-md-12">
                        <x-detail-field label="WhatsApp" icon="ri-whatsapp-line" :class="'mb-0'">
                            <p class="form-control-plaintext mb-0" id="detail-pasien_wa">-</p>
                        </x-detail-field>
                    </div>
                </div>
            </x-card>

            {{-- ============ PMO INFO ============ --}}
            <div class="mt-3">
                <x-card title="PMO (Pendamping Minum Obat)" icon="ri-shield-user-line">
                    <div class="row g-3">
                        <div class="col-md-7">
                            <x-detail-field label="Nama PMO" icon="ri-user-line">
                                <p class="form-control-plaintext mb-0" id="detail-nama_pmo">-</p>
                            </x-detail-field>
                        </div>
                        <div class="col-md-5">
                            <x-detail-field label="Jenis PMO" icon="ri-vip-line">
                                <p class="form-control-plaintext mb-0" id="detail-jenis_pmo">-</p>
                            </x-detail-field>
                        </div>
                        <div class="col-md-12">
                            <x-detail-field label="WhatsApp" icon="ri-whatsapp-line" :class="'mb-0'">
                                <p class="form-control-plaintext mb-0" id="detail-pmo_wa">-</p>
                            </x-detail-field>
                        </div>
                    </div>
                </x-card>
            </div>

            {{-- ============ DETAIL MAPPING ============ --}}
            <div class="mt-3">
                <x-card title="Detail Mapping" icon="ri-settings-3-line">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <x-detail-field label="Status Diabetes" icon="ri-heart-pulse-line">
                                <p class="form-control-plaintext mb-0" id="detail-status_diabetes">-</p>
                            </x-detail-field>
                        </div>
                        <div class="col-md-6">
                            <x-detail-field label="Tanggal Registrasi" icon="ri-calendar-line">
                                <p class="form-control-plaintext mb-0" id="detail-tanggal_regis">-</p>
                            </x-detail-field>
                        </div>
                        <div class="col-md-12">
                            <x-detail-field label="Catatan" icon="ri-sticky-note-line" :class="'mb-0'">
                                <p class="form-control-plaintext mb-0" id="detail-catatan" style="white-space: pre-line;">-
                                </p>
                            </x-detail-field>
                        </div>
                    </div>
                </x-card>
            </div>
        </div>

        <div class="col-lg-4">
            <x-card>
                <h6 class="fw-bold mb-3 small text-muted text-uppercase">Status Mapping</h6>
                <div class="text-center mb-3" id="bigStatusBadge">
                    <span class="spinner-border spinner-border-sm" role="status"></span>
                </div>
                <hr>
                <table class="table table-borderless table-sm mb-0">
                    <tr>
                        <td class="text-muted small">ID Mapping</td>
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
                    INDEX: '{{ route('pasien-pmo.index') }}',
                    EDIT: '{{ route('pasien-pmo.edit', $id) }}',
                    DESTROY: '{{ route('pasien-pmo.destroy', $id) }}',
                    SHOW_DATA: '{{ route('pasien-pmo.show-data', $id) }}',
                    DEACTIVATE: '{{ route('pasien-pmo.deactivate', $id) }}',
                    ACTIVATE: '{{ route('pasien-pmo.activate', $id) }}',
                },
            };

            const JENIS_BADGES = {
                'Keluarga': '<span class="badge bg-info-subtle text-info px-3 py-2"><i class="ri ri-home-heart-line"></i> Keluarga</span>',
                'Kader': '<span class="badge bg-warning-subtle text-warning px-3 py-2"><i class="ri ri-shield-user-line"></i> Kader</span>',
            };

            const DIABETES_BADGES = {
                'Rendah': '<span class="badge bg-success-subtle text-success px-3 py-2">Rendah</span>',
                'Sedang': '<span class="badge bg-warning-subtle text-warning px-3 py-2">Sedang</span>',
                'Tinggi': '<span class="badge bg-danger-subtle text-danger px-3 py-2">Tinggi</span>',
            };

            const STATUS_BADGES = {
                active: '<span class="badge bg-success-subtle text-success px-4 py-3 fs-6"><i class="ri ri-check-line"></i> Aktif</span>',
                inactive: '<span class="badge bg-secondary-subtle text-secondary px-4 py-3 fs-6"><i class="ri ri-close-line"></i> Nonaktif</span>',
            };

            const formatDate = (iso) => {
                if (!iso) return '-';
                const d = new Date(iso);
                return d.toLocaleDateString('id-ID', {
                    day: 'numeric',
                    month: 'long',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            };

            const formatBirthDate = (iso) => {
                if (!iso) return '-';
                const d = new Date(iso);
                return d.toLocaleDateString('id-ID', {
                    day: 'numeric',
                    month: 'long',
                    year: 'numeric'
                });
            };

            let mappingData = null;

            // LOAD DATA
            $.ajax({
                url: CONFIG.ROUTES.SHOW_DATA,
                method: 'GET',
            }).done(function(d) {
                mappingData = d;

                // Header
                const title = d.nama_pasien + ' ↔ ' + d.nama_pmo;
                $('#entityName').text(title);
                $('#bcEntityName').text(title.length > 40 ? title.substr(0, 40) + '...' : title);
                $('#entityStatus').html(
                    (d.is_active ?
                        '<span class="badge bg-success-subtle text-success"><i class="ri ri-check-line"></i> Aktif</span>' :
                        '<span class="badge bg-secondary-subtle text-secondary"><i class="ri ri-close-line"></i> Nonaktif</span>'
                        ) +
                    ' &middot; <span class="ms-1">' + (JENIS_BADGES[d.jenis_pmo] || d.jenis_pmo) +
                    '</span>'
                );

                // Big status badge (kanan)
                $('#bigStatusBadge').html(d.is_active ? STATUS_BADGES.active : STATUS_BADGES.inactive);

                // Pasien info
                $('#detail-nama_pasien').text(d.nama_pasien);
                $('#detail-nik').text(d.nik || '-');
                $('#detail-pasien_wa').text(d.pasien?.whatsapp_number || '-');

                // PMO info
                $('#detail-nama_pmo').text(d.nama_pmo);
                $('#detail-jenis_pmo').html(JENIS_BADGES[d.jenis_pmo] || d.jenis_pmo);
                $('#detail-pmo_wa').text(d.pmo?.whatsapp_number || '-');

                // Detail mapping
                $('#detail-status_diabetes').html(DIABETES_BADGES[d.status_diabetes] || d.status_diabetes);
                $('#detail-tanggal_regis').text(formatBirthDate(d.tanggal_regis));
                $('#detail-catatan').text(d.catatan || '-');

                // Audit
                $('#detail-id').text(d.id);
                $('#detail-creator').text(d.creator?.name || '-');
                $('#detail-created_at').text(formatDate(d.created_at));
                $('#detail-updater').text(d.updater?.name || '-');
                $('#detail-updated_at').text(d.updated_at ? formatDate(d.updated_at) : '-');

                renderHeaderActions(d);
            }).fail(function(xhr) {
                Swal.fire({
                    title: 'Error!',
                    text: xhr.responseJSON?.message || 'Gagal memuat data',
                    icon: 'error',
                }).then(() => window.location.href = CONFIG.ROUTES.INDEX);
            });

            function renderHeaderActions(d) {
                const actions = [];

                @can('pasien-pmo.edit')
                    actions.push(
                        `<a href="${CONFIG.ROUTES.EDIT}" class="btn btn-outline-warning btn-sm"><i class="ri ri-pencil-line me-1"></i> Edit</a>`
                        );

                    // Toggle aktif/nonaktif
                    if (d.is_active) {
                        actions.push(
                            `<button class="btn btn-outline-secondary btn-sm" id="btnDeactivate"><i class="ri ri-pause-circle-line me-1"></i> Nonaktifkan</button>`
                            );
                    } else {
                        actions.push(
                            `<button class="btn btn-outline-success btn-sm" id="btnActivate"><i class="ri ri-play-circle-line me-1"></i> Aktifkan</button>`
                            );
                    }
                @endcan

                @can('pasien-pmo.delete')
                    actions.push(
                        `<button class="btn btn-outline-danger btn-sm" id="btnDelete"><i class="ri ri-delete-bin-line me-1"></i> Hapus</button>`
                        );
                @endcan

                if (actions.length) {
                    $('#headerActions').html(actions.join('')).attr('style', 'display:flex !important;');
                }
            }

            // ============ DEACTIVATE ============
            $(document).on('click', '#btnDeactivate', function() {
                Swal.fire({
                    title: 'Nonaktifkan mapping?',
                    html: 'Mapping ini akan dinonaktifkan.<br><small class="text-muted">Data tetap tersimpan, bisa diaktifkan kembali nanti.</small>',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Nonaktifkan',
                    cancelButtonText: 'Batal',
                    customClass: {
                        confirmButton: 'btn btn-warning me-2',
                        cancelButton: 'btn btn-secondary'
                    },
                    buttonsStyling: false,
                }).then(function(result) {
                    if (!result.isConfirmed) return;

                    $.ajax({
                        url: CONFIG.ROUTES.DEACTIVATE,
                        method: 'POST',
                        data: {
                            _token: $('meta[name=csrf-token]').attr('content')
                        },
                    }).done(function(res) {
                        Swal.fire({
                                title: 'Berhasil!',
                                text: res.message,
                                icon: 'success',
                                timer: 1500,
                                showConfirmButton: false
                            })
                            .then(() => window.location.reload());
                    }).fail(function(xhr) {
                        Swal.fire('Gagal!', xhr.responseJSON?.message ||
                            'Terjadi kesalahan', 'error');
                    });
                });
            });

            // ============ ACTIVATE ============
            $(document).on('click', '#btnActivate', function() {
                Swal.fire({
                    title: 'Aktifkan kembali mapping?',
                    html: 'Mapping ini akan diaktifkan kembali.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Aktifkan',
                    cancelButtonText: 'Batal',
                    customClass: {
                        confirmButton: 'btn btn-success me-2',
                        cancelButton: 'btn btn-secondary'
                    },
                    buttonsStyling: false,
                }).then(function(result) {
                    if (!result.isConfirmed) return;

                    $.ajax({
                        url: CONFIG.ROUTES.ACTIVATE,
                        method: 'POST',
                        data: {
                            _token: $('meta[name=csrf-token]').attr('content')
                        },
                    }).done(function(res) {
                        Swal.fire({
                                title: 'Berhasil!',
                                text: res.message,
                                icon: 'success',
                                timer: 1500,
                                showConfirmButton: false
                            })
                            .then(() => window.location.reload());
                    }).fail(function(xhr) {
                        Swal.fire('Gagal!', xhr.responseJSON?.message ||
                            'Terjadi kesalahan', 'error');
                    });
                });
            });

            // ============ DELETE ============
            $(document).on('click', '#btnDelete', function() {
                Swal.fire({
                    title: 'Hapus mapping?',
                    html: 'Mapping antara <strong>' + mappingData.nama_pasien +
                        '</strong> dan <strong>' + mappingData.nama_pmo +
                        '</strong> akan dihapus.<br><small class="text-danger">Tindakan ini tidak bisa dibatalkan.</small>',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, hapus!',
                    cancelButtonText: 'Batal',
                    customClass: {
                        confirmButton: 'btn btn-danger me-2',
                        cancelButton: 'btn btn-secondary'
                    },
                    buttonsStyling: false,
                }).then(function(result) {
                    if (!result.isConfirmed) return;

                    $.ajax({
                            url: CONFIG.ROUTES.DESTROY,
                            method: 'DELETE'
                        })
                        .done(function(res) {
                            Swal.fire({
                                    title: 'Berhasil!',
                                    text: res.message,
                                    icon: 'success',
                                    timer: 2000,
                                    showConfirmButton: false
                                })
                                .then(() => window.location.href = CONFIG.ROUTES.INDEX);
                        })
                        .fail(function(xhr) {
                            Swal.fire('Gagal!', xhr.responseJSON?.message ||
                                'Terjadi kesalahan', 'error');
                        });
                });
            });
        });
    </script>
@endpush
