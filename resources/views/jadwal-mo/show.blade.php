@extends('layouts.app')

@section('title', 'Detail Jadwal')

@section('page-header')
    <nav aria-label="breadcrumb" class="small">
        <ol class="breadcrumb mb-2">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('jadwal-mo.index') }}" class="text-decoration-none">Jadwal Minum
                    Obat</a></li>
            <li class="breadcrumb-item active" id="bcEntityName">Detail</li>
        </ol>
    </nav>
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="ri ri-calendar-check-line text-primary me-2"></i>
                <span id="entityName">Detail Jadwal</span>
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

            {{-- ============ INFO PASIEN & PMO ============ --}}
            <x-card title="Pasien & PMO" icon="ri-user-heart-line">
                <div class="row g-3">
                    <div class="col-md-6">
                        <x-detail-field label="Nama Pasien" icon="ri-user-line">
                            <p class="form-control-plaintext mb-0" id="detail-nama_pasien">-</p>
                        </x-detail-field>
                    </div>
                    <div class="col-md-6">
                        <x-detail-field label="Nama PMO" icon="ri-shield-user-line" :class="'mb-0'">
                            <p class="form-control-plaintext mb-0" id="detail-nama_pmo">-</p>
                        </x-detail-field>
                    </div>
                </div>
            </x-card>

            {{-- ============ INFO OBAT ============ --}}
            <div class="mt-3">
                <x-card title="Obat" icon="ri-medicine-bottle-line">
                    <div class="row g-3">
                        <div class="col-md-7">
                            <x-detail-field label="Nama Obat" icon="ri-capsule-line">
                                <p class="form-control-plaintext mb-0" id="detail-obat_nama">-</p>
                            </x-detail-field>
                        </div>
                        <div class="col-md-5">
                            <x-detail-field label="Dosis Default" icon="ri-scales-line">
                                <p class="form-control-plaintext mb-0" id="detail-obat_dosis">-</p>
                            </x-detail-field>
                        </div>
                        <div class="col-md-6">
                            <x-detail-field label="Satuan" icon="ri-ruler-line">
                                <p class="form-control-plaintext mb-0" id="detail-obat_satuan">-</p>
                            </x-detail-field>
                        </div>
                        <div class="col-md-6">
                            <x-detail-field label="Kategori" icon="ri-price-tag-3-line" :class="'mb-0'">
                                <p class="form-control-plaintext mb-0" id="detail-obat_kategori">-</p>
                            </x-detail-field>
                        </div>
                        <div class="col-md-12">
                            <x-detail-field label="Aturan Minum (Standar)" icon="ri-information-line" :class="'mb-0'">
                                <p class="form-control-plaintext mb-0 text-muted small" id="detail-obat_aturan_minum">-</p>
                            </x-detail-field>
                        </div>
                    </div>
                </x-card>
            </div>

            {{-- ============ JADWAL ============ --}}
            <div class="mt-3">
                <x-card title="Jadwal Minum Obat" icon="ri-calendar-check-line">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <x-detail-field label="Tanggal Mulai" icon="ri-calendar-line">
                                <p class="form-control-plaintext mb-0" id="detail-tgl_mulai">-</p>
                            </x-detail-field>
                        </div>
                        <div class="col-md-6">
                            <x-detail-field label="Jam Mulai" icon="ri-time-line">
                                <p class="form-control-plaintext mb-0" id="detail-jam_mulai">-</p>
                            </x-detail-field>
                        </div>
                        <div class="col-md-12">
                            <x-detail-field label="Frekuensi" icon="ri-repeat-line">
                                <p class="form-control-plaintext mb-0">
                                    <span class="badge bg-primary-subtle text-primary fs-6 px-3 py-2"
                                        id="detail-frekuensi_per_hari">-</span>
                                    <span class="ms-2 text-muted small">kali sehari</span>
                                </p>
                            </x-detail-field>
                        </div>
                        <div class="col-md-12">
                            <x-detail-field label="Catatan Dosis" icon="ri-sticky-note-line" :class="'mb-0'">
                                <p class="form-control-plaintext mb-0" id="detail-catatan_dosis"
                                    style="white-space: pre-line;">-</p>
                            </x-detail-field>
                        </div>
                    </div>
                </x-card>
            </div>

            {{-- ============ SLOT JAM HARIAN ============ --}}
            <div class="mt-3">
                <x-card title="Slot Jam Minum Obat (Sehari)" icon="ri-time-line">
                    <p class="small text-muted mb-3">
                        <i class="ri ri-information-line me-1"></i>
                        Otomatis dihitung dari jam mulai dan frekuensi per hari. Interval setiap dosis: <strong
                            id="detail-interval_jam">-</strong> jam.
                    </p>
                    <div id="slotJamContainer" class="d-flex flex-wrap gap-2 align-items-center">
                        {{-- Filled by JS --}}
                    </div>
                </x-card>
            </div>
        </div>

        {{-- ============ SIDEBAR KANAN ============ --}}
        <div class="col-lg-4">
            <x-card>
                <h6 class="fw-bold mb-3 small text-muted text-uppercase">Status Jadwal</h6>
                <div class="text-center mb-3" id="bigStatusBadge">
                    <span class="spinner-border spinner-border-sm" role="status"></span>
                </div>
                <hr>
                <table class="table table-borderless table-sm mb-0">
                    <tr>
                        <td class="text-muted small">ID Jadwal</td>
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
                    INDEX: '{{ route('jadwal-mo.index') }}',
                    EDIT: '{{ route('jadwal-mo.edit', $id) }}',
                    DESTROY: '{{ route('jadwal-mo.destroy', $id) }}',
                    SHOW_DATA: '{{ route('jadwal-mo.show-data', $id) }}',
                    DEACTIVATE: '{{ route('jadwal-mo.deactivate', $id) }}',
                    ACTIVATE: '{{ route('jadwal-mo.activate', $id) }}',
                    MARK_SELESAI: '{{ route('jadwal-mo.mark-selesai', $id) }}',
                },
            };

            const STATUS_BIG_BADGES = {
                'aktif': '<span class="badge bg-success-subtle text-success px-4 py-3 fs-6"><i class="ri ri-check-line"></i> Aktif</span>',
                'nonaktif': '<span class="badge bg-secondary-subtle text-secondary px-4 py-3 fs-6"><i class="ri ri-pause-circle-line"></i> Nonaktif</span>',
                'selesai': '<span class="badge bg-info-subtle text-info px-4 py-3 fs-6"><i class="ri ri-flag-line"></i> Selesai</span>',
            };

            const STATUS_INLINE_BADGES = {
                'aktif': '<span class="badge bg-success-subtle text-success"><i class="ri ri-check-line"></i> Aktif</span>',
                'nonaktif': '<span class="badge bg-secondary-subtle text-secondary"><i class="ri ri-pause-circle-line"></i> Nonaktif</span>',
                'selesai': '<span class="badge bg-info-subtle text-info"><i class="ri ri-flag-line"></i> Selesai</span>',
            };

            const formatDate = (iso) => {
                if (!iso) return '-';
                const d = new Date(iso);
                return d.toLocaleDateString('id-ID', {
                    day: 'numeric',
                    month: 'long',
                    year: 'numeric'
                });
            };

            const formatDateTime = (iso) => {
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

            const formatTime = (time) => {
                if (!time) return '-';
                return time.substring(0, 5);
            };

            function generateSlotJam(jamMulai, frekuensi) {
                if (!jamMulai || !frekuensi || frekuensi <= 0) return [];
                const slots = [];
                const interval = 24 / frekuensi;
                const [h, m] = jamMulai.split(':').map(Number);
                const startMinutes = h * 60 + (m || 0);

                for (let i = 0; i < frekuensi; i++) {
                    const minutes = Math.floor((startMinutes + (i * interval * 60)) % (24 * 60));
                    const hh = Math.floor(minutes / 60);
                    const mm = minutes % 60;
                    slots.push(String(hh).padStart(2, '0') + ':' + String(mm).padStart(2, '0'));
                }
                return slots;
            }

            let jadwalData = null;

            // LOAD DATA
            $.ajax({
                url: CONFIG.ROUTES.SHOW_DATA,
                method: 'GET',
            }).done(function(d) {
                jadwalData = d;

                // Header
                const obatName = d.obat?.nama ?? 'Obat tidak ditemukan';
                const title = obatName + ' → ' + d.nama_pasien;
                $('#entityName').text(title);
                $('#bcEntityName').text(title.length > 40 ? title.substr(0, 40) + '...' : title);
                $('#entityStatus').html(
                    STATUS_INLINE_BADGES[d.status] + ' &middot; ' +
                    '<span class="ms-1"><i class="ri ri-time-line"></i> ' + formatTime(d.jam_mulai) +
                    ' &middot; ' + d.frekuensi_per_hari + 'x sehari</span>'
                );

                // Big status
                $('#bigStatusBadge').html(STATUS_BIG_BADGES[d.status] || d.status);

                // Pasien & PMO
                $('#detail-nama_pasien').text(d.nama_pasien || '-');
                $('#detail-nama_pmo').text(d.nama_pmo || '-');

                // Obat
                $('#detail-obat_nama').html('<strong>' + (d.obat?.nama || '-') + '</strong>');
                $('#detail-obat_dosis').text(d.obat?.dosis_default || '-');
                $('#detail-obat_satuan').text(d.obat?.satuan?.nama || '-');
                $('#detail-obat_kategori').text(d.obat?.kategori?.nama || '-');
                $('#detail-obat_aturan_minum').text(d.obat?.aturan_minum || 'Tidak ada keterangan khusus.');

                // Jadwal
                $('#detail-tgl_mulai').text(formatDate(d.tgl_mulai));
                $('#detail-jam_mulai').html('<span class="badge bg-light text-dark fs-6">' + formatTime(d
                    .jam_mulai) + '</span>');
                $('#detail-frekuensi_per_hari').text(d.frekuensi_per_hari + 'x');
                $('#detail-catatan_dosis').text(d.catatan_dosis || '-');

                // Slot jam harian
                const slots = generateSlotJam(d.jam_mulai, d.frekuensi_per_hari);
                const interval = d.frekuensi_per_hari > 0 ? (24 / d.frekuensi_per_hari).toFixed(1) : '-';
                $('#detail-interval_jam').text(interval);

                const slotHtml = slots.map((time, idx) => {
                    return `
                <div class="slot-jam-card border rounded p-3 text-center shadow-sm" style="min-width: 100px; background: #f8f9ff;">
                    <small class="text-muted d-block mb-1">Dosis ${idx + 1}</small>
                    <strong class="d-block fs-4 text-primary">${time}</strong>
                </div>
            `;
                }).join('<i class="ri ri-arrow-right-line text-muted fs-4 mx-1"></i>');
                $('#slotJamContainer').html(slotHtml);

                // Audit
                $('#detail-id').text(d.id);
                $('#detail-creator').text(d.creator?.name || '-');
                $('#detail-created_at').text(formatDateTime(d.created_at));
                $('#detail-updater').text(d.updater?.name || '-');
                $('#detail-updated_at').text(d.updated_at ? formatDateTime(d.updated_at) : '-');

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

                @can('jadwal-mo.edit')
                    actions.push(
                        `<a href="${CONFIG.ROUTES.EDIT}" class="btn btn-outline-warning btn-sm"><i class="ri ri-pencil-line me-1"></i> Edit</a>`
                        );

                    if (d.status === 'aktif') {
                        actions.push(
                            `<button class="btn btn-outline-secondary btn-sm" id="btnDeactivate"><i class="ri ri-pause-circle-line me-1"></i> Nonaktifkan</button>`
                            );
                        actions.push(
                            `<button class="btn btn-outline-info btn-sm" id="btnSelesai"><i class="ri ri-flag-line me-1"></i> Tandai Selesai</button>`
                            );
                    } else if (d.status === 'nonaktif') {
                        actions.push(
                            `<button class="btn btn-outline-success btn-sm" id="btnActivate"><i class="ri ri-play-circle-line me-1"></i> Aktifkan</button>`
                            );
                    }
                @endcan

                @can('jadwal-mo.delete')
                    actions.push(
                        `<button class="btn btn-outline-danger btn-sm" id="btnDelete"><i class="ri ri-delete-bin-line me-1"></i> Hapus</button>`
                        );
                @endcan

                if (actions.length) {
                    $('#headerActions').html(actions.join('')).attr('style', 'display:flex !important;');
                }
            }

            // ============ STATUS ACTIONS ============
            const performStatusAction = (url, swalConfig) => {
                Swal.fire(swalConfig).then(function(result) {
                    if (!result.isConfirmed) return;

                    $.ajax({
                        url: url,
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
                        Swal.fire('Gagal!', xhr.responseJSON?.message || 'Terjadi kesalahan',
                            'error');
                    });
                });
            };

            $(document).on('click', '#btnDeactivate', () => performStatusAction(CONFIG.ROUTES.DEACTIVATE, {
                title: 'Nonaktifkan jadwal?',
                html: 'Jadwal akan dinonaktifkan.<br><small class="text-muted">Bisa diaktifkan kembali nanti.</small>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Nonaktifkan',
                cancelButtonText: 'Batal',
                customClass: {
                    confirmButton: 'btn btn-warning me-2',
                    cancelButton: 'btn btn-secondary'
                },
                buttonsStyling: false,
            }));

            $(document).on('click', '#btnActivate', () => performStatusAction(CONFIG.ROUTES.ACTIVATE, {
                title: 'Aktifkan kembali?',
                html: 'Jadwal akan diaktifkan kembali.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Aktifkan',
                cancelButtonText: 'Batal',
                customClass: {
                    confirmButton: 'btn btn-success me-2',
                    cancelButton: 'btn btn-secondary'
                },
                buttonsStyling: false,
            }));

            $(document).on('click', '#btnSelesai', () => performStatusAction(CONFIG.ROUTES.MARK_SELESAI, {
                title: 'Tandai jadwal selesai?',
                html: 'Jadwal akan ditandai selesai (pengobatan tuntas).',
                icon: 'info',
                showCancelButton: true,
                confirmButtonText: 'Ya, Selesai',
                cancelButtonText: 'Batal',
                customClass: {
                    confirmButton: 'btn btn-info me-2',
                    cancelButton: 'btn btn-secondary'
                },
                buttonsStyling: false,
            }));

            // ============ DELETE ============
            $(document).on('click', '#btnDelete', function() {
                Swal.fire({
                    title: 'Hapus jadwal?',
                    html: 'Jadwal untuk obat <strong>' + (jadwalData.obat?.nama || '-') +
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

    <style>
        .slot-jam-card {
            transition: transform 0.2s;
        }

        .slot-jam-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1) !important;
        }
    </style>
@endpush
