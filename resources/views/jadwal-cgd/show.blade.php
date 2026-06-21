@extends('layouts.app')

@section('title', 'Detail Jadwal CGD')

@section('page-header')
    <nav aria-label="breadcrumb" class="small">
        <ol class="breadcrumb mb-2">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('jadwal-cgd.index') }}" class="text-decoration-none">Jadwal CGD</a>
            </li>
            <li class="breadcrumb-item active" id="bcEntityName">Detail</li>
        </ol>
    </nav>
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="ri ri-test-tube-line text-warning me-2"></i>
                <span id="entityName">Detail Event CGD</span>
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

            {{-- ============ EVENT HERO CARD ============ --}}
            <div class="card border-warning border-2 mb-3" id="heroCard">
                <div class="card-body p-4 text-center"
                    style="background: linear-gradient(135deg, #fff5e6 0%, #fffaf0 100%);">
                    <i class="ri ri-test-tube-line text-warning" style="font-size: 3rem;"></i>
                    <h3 class="fw-bold mt-2 mb-3">Cek Gula Darah</h3>
                    <div class="row g-3 mt-2">
                        <div class="col-md-4">
                            <div class="text-muted small mb-1">
                                <i class="ri ri-calendar-line"></i> Tanggal
                            </div>
                            <div class="fs-5 fw-bold text-primary" id="hero-tgl">-</div>
                            <div id="hero-tgl-badge"></div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small mb-1">
                                <i class="ri ri-time-line"></i> Waktu
                            </div>
                            <div class="fs-5 fw-bold text-primary" id="hero-waktu">-</div>
                            <div class="text-muted small" id="hero-durasi">-</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small mb-1">
                                <i class="ri ri-restaurant-line"></i> Puasa
                            </div>
                            <div class="fs-5 fw-bold" id="hero-puasa">-</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ============ TEMPAT ============ --}}
            <x-card title="Lokasi Pelaksanaan" icon="ri-map-pin-line">
                <div class="d-flex align-items-start gap-3">
                    <i class="ri ri-map-pin-2-fill text-danger" style="font-size: 2rem;"></i>
                    <div class="flex-grow-1">
                        <h5 class="fw-bold mb-1" id="detail-tempat">-</h5>
                        <small class="text-muted">Tempat event pemeriksaan gula darah</small>
                    </div>
                </div>
            </x-card>

            {{-- ============ CATATAN ============ --}}
            <div class="mt-3">
                <x-card title="Catatan & Persiapan" icon="ri-sticky-note-line">
                    <p class="form-control-plaintext mb-0" id="detail-catatan" style="white-space: pre-line;">-</p>
                </x-card>
            </div>

            {{-- ============ PESERTA ============ --}}
            <div class="mt-3">
                <x-card title="Peserta & Pengingat" icon="ri-group-line">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0" id="tabelPeserta">
                            <thead class="table-light">
                                <tr>
                                    <th>Nama Pasien</th>
                                    <th>PMO</th>
                                    <th>Pengingat "Dibuat"</th>
                                    <th>Pengingat "H-1"</th>
                                </tr>
                            </thead>
                            <tbody id="tabelPesertaBody">
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">
                                        <span class="spinner-border spinner-border-sm" role="status"></span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </x-card>
            </div>
        </div>

        {{-- ============ SIDEBAR KANAN ============ --}}
        <div class="col-lg-4">
            <x-card>
                <h6 class="fw-bold mb-3 small text-muted text-uppercase">Status Event</h6>
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
                        <td class="text-muted small">Tgl Input</td>
                        <td class="fw-semibold small" id="detail-tgl_input">-</td>
                    </tr>
                    <tr>
                        <td class="text-muted small">Diinput oleh</td>
                        <td class="fw-semibold small" id="detail-creator">-</td>
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
                    INDEX: '{{ route('jadwal-cgd.index') }}',
                    EDIT: '{{ route('jadwal-cgd.edit', $id) }}',
                    DESTROY: '{{ route('jadwal-cgd.destroy', $id) }}',
                    SHOW_DATA: '{{ route('jadwal-cgd.show-data', $id) }}',
                    DEACTIVATE: '{{ route('jadwal-cgd.deactivate', $id) }}',
                    ACTIVATE: '{{ route('jadwal-cgd.activate', $id) }}',
                    MARK_SELESAI: '{{ route('jadwal-cgd.mark-selesai', $id) }}',
                },
            };

            const STATUS_BIG_BADGES = {
                'aktif': '<span class="badge bg-success-subtle text-success px-4 py-3 fs-6"><i class="ri ri-check-line"></i> Aktif</span>',
                'nonaktif': '<span class="badge bg-secondary-subtle text-secondary px-4 py-3 fs-6"><i class="ri ri-pause-circle-line"></i> Nonaktif</span>',
                'selesai': '<span class="badge bg-info-subtle text-info px-4 py-3 fs-6"><i class="ri ri-flag-line"></i> Selesai</span>',
            };

            const STATUS_INLINE = {
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

            const formatJam = (time) => {
                if (!time) return '-';
                return time.substring(0, 5);
            };

            const calcDurasi = (start, end) => {
                if (!start || !end) return '-';
                const [sh, sm] = start.split(':').map(Number);
                const [eh, em] = end.split(':').map(Number);
                const startMin = sh * 60 + (sm || 0);
                const endMin = eh * 60 + (em || 0);
                if (endMin <= startMin) return '-';
                const diffMin = endMin - startMin;
                const hours = Math.floor(diffMin / 60);
                const mins = diffMin % 60;
                if (mins === 0) return hours + ' jam';
                return hours + ' jam ' + mins + ' menit';
            };

            const isToday = (iso) => {
                if (!iso) return false;
                const d = new Date(iso);
                const today = new Date();
                return d.toDateString() === today.toDateString();
            };

            const isPast = (iso) => {
                if (!iso) return false;
                const d = new Date(iso);
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                d.setHours(0, 0, 0, 0);
                return d < today;
            };

            let cgdData = null;

            // LOAD DATA
            $.ajax({
                url: CONFIG.ROUTES.SHOW_DATA,
                method: 'GET',
            }).done(function(d) {
                cgdData = d;

                // Header
                const title = d.tempat;
                $('#entityName').text('CGD di ' + title);
                $('#bcEntityName').text(title.length > 40 ? title.substr(0, 40) + '...' : title);
                $('#entityStatus').html(
                    STATUS_INLINE[d.status] + ' &middot; ' +
                    '<span class="ms-1"><i class="ri ri-calendar-line"></i> ' + formatDate(d
                        .tgl_jadwal_cgd) + '</span>'
                );

                // Big status
                $('#bigStatusBadge').html(STATUS_BIG_BADGES[d.status] || d.status);

                // Hero
                $('#hero-tgl').text(formatDate(d.tgl_jadwal_cgd));
                if (isToday(d.tgl_jadwal_cgd)) {
                    $('#hero-tgl-badge').html(
                        '<span class="badge bg-danger-subtle text-danger mt-1">Hari Ini!</span>');
                } else if (isPast(d.tgl_jadwal_cgd)) {
                    $('#hero-tgl-badge').html(
                        '<span class="badge bg-light text-muted mt-1">Sudah Lewat</span>');
                } else {
                    $('#hero-tgl-badge').html(
                        '<span class="badge bg-success-subtle text-success mt-1">Akan Datang</span>');
                }

                $('#hero-waktu').text(formatJam(d.jam_mulai) + ' - ' + formatJam(d.jam_berakhir));
                $('#hero-durasi').text('Durasi: ' + calcDurasi(d.jam_mulai, d.jam_berakhir));

                if (d.puasa === 'Wajib') {
                    $('#hero-puasa').html('<span class="text-warning">⚠️ Wajib</span>');
                } else {
                    $('#hero-puasa').html('<span class="text-muted">Tidak</span>');
                }

                // Lokasi
                $('#detail-tempat').text(d.tempat || '-');

                // Catatan
                $('#detail-catatan').text(d.catatan || 'Tidak ada catatan tambahan.');

                // Audit
                $('#detail-id').text(d.id);
                $('#detail-tgl_input').text(formatDate(d.tgl_input));
                $('#detail-creator').text(d.creator?.name || '-');
                $('#detail-updater').text(d.updater?.name || '-');
                $('#detail-updated_at').text(d.updated_at ? formatDateTime(d.updated_at) : '-');

                // Peserta
                renderPeserta(d.peserta || []);

                renderHeaderActions(d);
            }).fail(function(xhr) {
                Swal.fire({
                    title: 'Error!',
                    text: xhr.responseJSON?.message || 'Gagal memuat data',
                    icon: 'error',
                }).then(() => window.location.href = CONFIG.ROUTES.INDEX);
            });

            function renderPeserta(peserta) {
                const $tbody = $('#tabelPesertaBody');
                if (!peserta.length) {
                    $tbody.html(
                        '<tr><td colspan="4" class="text-center text-muted py-3">Belum ada peserta terdaftar.</td></tr>'
                    );
                    return;
                }

                const badgeTerkirim =
                    '<span class="badge bg-success-subtle text-success"><i class="ri ri-check-line"></i> Terkirim</span>';
                const badgeMenunggu =
                    '<span class="badge bg-secondary-subtle text-secondary"><i class="ri ri-time-line"></i> Menunggu</span>';

                const rows = peserta.map(function(p) {
                    const badgeDibuat = p.dikirim_dibuat_pada ? badgeTerkirim : badgeMenunggu;
                    const badgeH1 = p.dikirim_h1_pada ? badgeTerkirim : badgeMenunggu;
                    return '<tr>' +
                        '<td>' + ($('<span>').text(p.nama_pasien).html() || '-') + '</td>' +
                        '<td>' + ($('<span>').text(p.nama_pmo || '-').html()) + '</td>' +
                        '<td>' + badgeDibuat + '</td>' +
                        '<td>' + badgeH1 + '</td>' +
                        '</tr>';
                });

                $tbody.html(rows.join(''));
            }

            function renderHeaderActions(d) {
                const actions = [];

                @can('jadwal-cgd.edit')
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

                @can('jadwal-cgd.delete')
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

            $(document).on('click', '#btnSelesai', () => performStatusAction(CONFIG.ROUTES.MARK_SELESAI, {
                title: 'Tandai event selesai?',
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

            $(document).on('click', '#btnDelete', function() {
                Swal.fire({
                    title: 'Hapus jadwal?',
                    html: 'Event CGD di <strong>' + cgdData.tempat +
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
