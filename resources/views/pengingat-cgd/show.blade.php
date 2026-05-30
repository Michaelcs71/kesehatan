@extends('layouts.app')

@section('title', 'Detail Konfirmasi Minum Obat')

@section('page-header')
    <nav aria-label="breadcrumb" class="small">
        <ol class="breadcrumb mb-2">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('pengingat-mo.index') }}" class="text-decoration-none">Pengingat
                    Minum Obat</a></li>
            <li class="breadcrumb-item active" id="bcEntityName">Detail</li>
        </ol>
    </nav>
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="ri ri-notification-2-line text-success me-2"></i>
                <span id="entityName">Detail Konfirmasi</span>
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

            {{-- ============ KEPATUHAN HERO CARD ============ --}}
            <div class="card border-2 mb-3" id="patuhHeroCard">
                <div class="card-body p-4 text-center" id="patuhHeroBody">
                    <div class="spinner-border" role="status"></div>
                </div>
            </div>

            {{-- ============ INFO PASIEN & OBAT ============ --}}
            <x-card title="Informasi Minum Obat" icon="ri-medicine-bottle-line">
                <div class="row g-3">
                    <div class="col-md-6">
                        <x-detail-field label="Nama Pasien" icon="ri-user-line">
                            <p class="form-control-plaintext mb-0" id="detail-nama_pasien">-</p>
                        </x-detail-field>
                    </div>
                    <div class="col-md-6">
                        <x-detail-field label="Diinput Oleh" icon="ri-user-settings-line">
                            <p class="form-control-plaintext mb-0" id="detail-user">-</p>
                        </x-detail-field>
                    </div>
                    <div class="col-md-7">
                        <x-detail-field label="Nama Obat" icon="ri-capsule-line">
                            <p class="form-control-plaintext mb-0" id="detail-nama_obat">-</p>
                        </x-detail-field>
                    </div>
                    <div class="col-md-5">
                        <x-detail-field label="Dosis" icon="ri-scales-line" :class="'mb-0'">
                            <p class="form-control-plaintext mb-0" id="detail-dosis">-</p>
                        </x-detail-field>
                    </div>
                    <div class="col-md-12">
                        <x-detail-field label="Aturan Minum Standar (dari Master Obat)" icon="ri-information-line"
                            :class="'mb-0'">
                            <p class="form-control-plaintext mb-0 text-muted small" id="detail-aturan_minum">-</p>
                        </x-detail-field>
                    </div>
                </div>
            </x-card>

            {{-- ============ WAKTU MINUM ============ --}}
            <div class="mt-3">
                <x-card title="Waktu Minum" icon="ri-time-line">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <x-detail-field label="Tanggal" icon="ri-calendar-line">
                                <p class="form-control-plaintext mb-0" id="detail-tgl_minum_obat">-</p>
                            </x-detail-field>
                        </div>
                    </div>

                    {{-- Timeline visualization --}}
                    <div class="d-flex align-items-center justify-content-around gap-2 my-3 flex-wrap" id="timelineBox">
                        <div class="text-center">
                            <small class="text-muted d-block mb-1">Slot Seharusnya</small>
                            <div class="badge bg-light text-dark fs-5 px-3 py-2" id="detail-jam_slot_target">--:--</div>
                        </div>
                        <i class="ri ri-arrow-right-line text-muted fs-3" id="timelineArrow"></i>
                        <div class="text-center">
                            <small class="text-muted d-block mb-1">Jam Minum Aktual</small>
                            <div class="badge bg-primary-subtle text-primary fs-5 px-3 py-2" id="detail-jam_minum_obat">
                                --:--</div>
                        </div>
                        <i class="ri ri-arrow-right-line text-muted fs-3"></i>
                        <div class="text-center">
                            <small class="text-muted d-block mb-1">Selisih</small>
                            <div id="detail-patuh_badge">-</div>
                        </div>
                    </div>
                </x-card>
            </div>

            {{-- ============ FOTO BUKTI ============ --}}
            <div class="mt-3">
                <x-card title="Foto Bukti" icon="ri-camera-line">
                    <div class="text-center" id="fotoBox">
                        <div class="spinner-border" role="status"></div>
                    </div>
                </x-card>
            </div>
        </div>

        {{-- ============ SIDEBAR KANAN ============ --}}
        <div class="col-lg-4">
            <x-card>
                <h6 class="fw-bold mb-3 small text-muted text-uppercase">Status & Audit</h6>
                <div class="text-center mb-3" id="bigStatusBadge">
                    <span class="spinner-border spinner-border-sm" role="status"></span>
                </div>
                <hr>
                <table class="table table-borderless table-sm mb-0">
                    <tr>
                        <td class="text-muted small">ID Log</td>
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

            <div class="mt-3">
                <x-card title="Link Cepat" icon="ri-link">
                    <div class="d-grid gap-2">
                        <a href="#" id="linkToJadwal" class="btn btn-outline-primary btn-sm">
                            <i class="ri ri-calendar-check-line me-1"></i> Lihat Jadwal Minum Obat
                        </a>
                    </div>
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
                ID: '{{ $id }}',
                ROUTES: {
                    INDEX: '{{ route('pengingat-mo.index') }}',
                    EDIT: '{{ route('pengingat-mo.edit', $id) }}',
                    DESTROY: '{{ route('pengingat-mo.destroy', $id) }}',
                    SHOW_DATA: '{{ route('pengingat-mo.show-data', $id) }}',
                    DEACTIVATE: '{{ route('pengingat-mo.deactivate', $id) }}',
                    ACTIVATE: '{{ route('pengingat-mo.activate', $id) }}',
                    JADWAL_SHOW: '{{ route('jadwal-mo.show', ':id') }}',
                },
            };

            const STATUS_BIG = {
                'aktif': '<span class="badge bg-success-subtle text-success px-4 py-3 fs-6"><i class="ri ri-check-line"></i> Aktif</span>',
                'nonaktif': '<span class="badge bg-secondary-subtle text-secondary px-4 py-3 fs-6"><i class="ri ri-pause-circle-line"></i> Nonaktif</span>',
            };

            const STATUS_INLINE = {
                'aktif': '<span class="badge bg-success-subtle text-success"><i class="ri ri-check-line"></i> Aktif</span>',
                'nonaktif': '<span class="badge bg-secondary-subtle text-secondary"><i class="ri ri-pause-circle-line"></i> Nonaktif</span>',
            };

            const formatDate = (iso) => {
                if (!iso) return '-';
                const d = new Date(iso);
                return d.toLocaleDateString('id-ID', {
                    weekday: 'long',
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
                    month: 'short',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            };

            const formatJam = (time) => {
                if (!time) return '-';
                return time.substring(0, 5);
            };

            const getPatuhInfo = (menit) => {
                const abs = Math.abs(menit);
                if (abs <= 15) {
                    return {
                        kategori: 'Tepat Waktu',
                        color: 'success',
                        icon: 'ri-check-double-line',
                        bgClass: 'success-subtle',
                        emoji: '✓',
                        message: menit === 0 ? 'Minum tepat sesuai jadwal!' :
                            `${menit > 0 ? '+' : ''}${menit} menit dari slot — masih dalam toleransi`,
                    };
                } else if (abs <= 60) {
                    return {
                        kategori: 'Terlambat',
                        color: 'warning',
                        icon: 'ri-time-line',
                        bgClass: 'warning-subtle',
                        emoji: '⏰',
                        message: `${menit > 0 ? '+' : ''}${menit} menit dari slot ${menit > 0 ? '(terlambat)' : '(lebih awal)'}`,
                    };
                } else {
                    return {
                        kategori: 'Sangat Terlambat',
                        color: 'danger',
                        icon: 'ri-alarm-warning-line',
                        bgClass: 'danger-subtle',
                        emoji: '⚠️',
                        message: `${menit > 0 ? '+' : ''}${menit} menit dari slot — sangat terlambat`,
                    };
                }
            };

            let logData = null;

            // LOAD DATA
            $.ajax({
                url: CONFIG.ROUTES.SHOW_DATA,
                method: 'GET',
            }).done(function(d) {
                logData = d;

                const obatName = d.nama_obat || 'Obat';
                const pasienName = d.nama_pasien || 'Pasien';
                const title = obatName + ' - ' + pasienName;
                $('#entityName').text(title);
                $('#bcEntityName').text(title.length > 40 ? title.substring(0, 40) + '...' : title);

                $('#entityStatus').html(
                    STATUS_INLINE[d.status] + ' &middot; ' +
                    '<span class="ms-1"><i class="ri ri-calendar-line"></i> ' + formatDate(d
                        .tgl_minum_obat) + '</span>'
                );

                // Big status
                $('#bigStatusBadge').html(STATUS_BIG[d.status] || d.status);

                // Patuh Hero
                renderPatuhHero(d);

                // Pasien & Obat info
                $('#detail-nama_pasien').text(d.nama_pasien || '-');
                $('#detail-user').text(d.user?.name || '-');
                $('#detail-nama_obat').html('<strong>' + (d.nama_obat || '-') + '</strong>');

                const obat = d.jadwal_mo?.obat;
                const dosis = obat?.dosis_default || '-';
                const satuan = obat?.satuan?.nama || '';
                $('#detail-dosis').text(dosis + (satuan && dosis !== '-' ? ' ' + satuan : ''));
                $('#detail-aturan_minum').text(obat?.aturan_minum || 'Tidak ada keterangan khusus.');

                // Waktu
                $('#detail-tgl_minum_obat').text(formatDate(d.tgl_minum_obat));
                $('#detail-jam_minum_obat').text(formatJam(d.jam_minum_obat));
                if (d.jam_slot_target) {
                    $('#detail-jam_slot_target').text(formatJam(d.jam_slot_target));
                } else {
                    $('#detail-jam_slot_target').text('Tidak diset').addClass('text-muted');
                    $('#timelineArrow').addClass('text-muted');
                }

                // Patuh badge
                const patuhInfo = getPatuhInfo(d.patuh_menit);
                $('#detail-patuh_badge').html(
                    `<span class="badge bg-${patuhInfo.bgClass} text-${patuhInfo.color} fs-6 px-3 py-2"><i class="${patuhInfo.icon} me-1"></i>${patuhInfo.kategori}</span>`
                    );

                // Foto
                if (d.foto_obat) {
                    const fotoUrl = '/storage/' + d.foto_obat;
                    $('#fotoBox').html(`
                <div class="position-relative d-inline-block">
                    <img src="${fotoUrl}"
                         alt="Foto bukti minum obat"
                         class="img-fluid rounded shadow-sm foto-zoomable"
                         style="max-height: 500px; cursor: zoom-in;"
                         data-img-zoom="${fotoUrl}">
                </div>
                <div class="mt-2">
                    <small class="text-muted">
                        <i class="ri ri-zoom-in-line"></i> Klik foto untuk perbesar
                    </small>
                </div>
            `);
                } else {
                    $('#fotoBox').html(
                        '<div class="text-muted py-4"><i class="ri ri-image-off-line fs-1 d-block mb-2"></i>Tidak ada foto bukti</div>'
                        );
                }

                // Audit
                $('#detail-id').text(d.id);
                $('#detail-creator').text(d.creator?.name || '-');
                $('#detail-created_at').text(formatDateTime(d.created_at));
                $('#detail-updater').text(d.updater?.name || '-');
                $('#detail-updated_at').text(d.updated_at ? formatDateTime(d.updated_at) : '-');

                // Link to jadwal
                if (d.id_jo) {
                    $('#linkToJadwal').attr('href', CONFIG.ROUTES.JADWAL_SHOW.replace(':id', d.id_jo));
                }

                renderHeaderActions(d);
            }).fail(function(xhr) {
                Swal.fire({
                    title: 'Error!',
                    text: xhr.responseJSON?.message || 'Gagal memuat data',
                    icon: 'error',
                }).then(() => window.location.href = CONFIG.ROUTES.INDEX);
            });

            function renderPatuhHero(d) {
                const info = getPatuhInfo(d.patuh_menit);
                $('#patuhHeroCard').removeClass().addClass('card border-2 mb-3 border-' + info.color);

                const html = `
            <div style="background: linear-gradient(135deg, var(--bs-${info.color}-bg-subtle, #fff) 0%, #fff 100%);">
                <div class="display-1 mb-2">${info.emoji}</div>
                <h3 class="fw-bold text-${info.color} mb-1">${info.kategori}</h3>
                <p class="text-muted mb-0">${info.message}</p>
            </div>
        `;
                $('#patuhHeroBody').html(html);
            }

            function renderHeaderActions(d) {
                const actions = [];

                @can('pengingat-mo.edit')
                    actions.push(
                        `<a href="${CONFIG.ROUTES.EDIT}" class="btn btn-outline-warning btn-sm"><i class="ri ri-pencil-line me-1"></i> Edit</a>`
                        );

                    if (d.status === 'aktif') {
                        actions.push(
                            `<button class="btn btn-outline-secondary btn-sm" id="btnDeactivate"><i class="ri ri-pause-circle-line me-1"></i> Nonaktifkan</button>`
                            );
                    } else if (d.status === 'nonaktif') {
                        actions.push(
                            `<button class="btn btn-outline-success btn-sm" id="btnActivate"><i class="ri ri-play-circle-line me-1"></i> Aktifkan</button>`
                            );
                    }
                @endcan

                @can('pengingat-mo.delete')
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
                title: 'Nonaktifkan log?',
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

            $(document).on('click', '#btnDelete', function() {
                Swal.fire({
                    title: 'Hapus log konfirmasi?',
                    html: 'Log konfirmasi untuk <strong>' + (logData.nama_obat || '-') +
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

            // ============ FOTO ZOOM ============
            $(document).on('click', '[data-img-zoom]', function(e) {
                e.preventDefault();
                const url = $(this).attr('data-img-zoom');
                Swal.fire({
                    imageUrl: url,
                    imageAlt: 'Foto bukti minum obat',
                    showConfirmButton: false,
                    showCloseButton: true,
                    width: '90%',
                    background: '#000',
                    customClass: {
                        image: 'img-zoom-modal'
                    },
                });
            });
        });
    </script>

    <style>
        .foto-zoomable {
            transition: transform 0.2s;
        }

        .foto-zoomable:hover {
            transform: scale(1.02);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
        }

        .img-zoom-modal {
            max-height: 85vh !important;
            max-width: 100% !important;
            object-fit: contain;
        }

        #patuhHeroCard {
            transition: all 0.3s;
        }
    </style>
@endpush
