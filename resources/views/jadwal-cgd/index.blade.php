@extends('layouts.app')

@section('title', 'Jadwal Cek Gula Darah')

@section('page-header')
    <nav aria-label="breadcrumb" class="small">
        <ol class="breadcrumb mb-2">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item active">Jadwal Cek Gula Darah</li>
        </ol>
    </nav>
    <div>
        <h4 class="fw-bold mb-1"><i class="ri ri-test-tube-line text-warning me-1"></i> Jadwal Cek Gula Darah</h4>
        <small class="text-muted">
            @can('jadwal-cgd.create')
                Kelola event pemeriksaan gula darah massal di lapangan.
            @else
                Lihat jadwal event cek gula darah yang tersedia.
            @endcan
        </small>
    </div>
@endsection

@section('content')

    @php
        $stats = \App\Services\JadwalCgdService::getStats();
    @endphp

    {{-- ============ STATS CARDS ============ --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <a href="javascript:void(0)" onclick="filterReset()" class="text-decoration-none">
                <x-card class="card-hover h-100">
                    <div class="text-muted small"><i class="ri ri-list-check"></i> Total</div>
                    <div class="display-stat fs-2 text-primary">{{ $stats['total'] }}</div>
                </x-card>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="javascript:void(0)" onclick="filterByTime('upcoming')" class="text-decoration-none">
                <x-card class="card-hover h-100">
                    <div class="text-muted small"><i class="ri ri-calendar-event-line text-success"></i> Akan Datang</div>
                    <div class="display-stat fs-2 text-success">{{ $stats['upcoming'] }}</div>
                </x-card>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="javascript:void(0)" onclick="filterByTime('past')" class="text-decoration-none">
                <x-card class="card-hover h-100">
                    <div class="text-muted small"><i class="ri ri-history-line text-info"></i> Sudah Lewat</div>
                    <div class="display-stat fs-2 text-info">{{ $stats['past'] }}</div>
                </x-card>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="javascript:void(0)" onclick="filterByStatus('selesai')" class="text-decoration-none">
                <x-card class="card-hover h-100">
                    <div class="text-muted small"><i class="ri ri-flag-line text-secondary"></i> Selesai</div>
                    <div class="display-stat fs-2 text-secondary">{{ $stats['selesai'] }}</div>
                </x-card>
            </a>
        </div>
    </div>

    {{-- ============ DATA TABLE ============ --}}
    <x-card title="Daftar Jadwal CGD" icon="ri-test-tube-line">
        <x-slot:headerActions>
            @can('jadwal-cgd.create')
                <a href="{{ route('jadwal-cgd.create') }}" class="btn btn-primary btn-sm">
                    <i class="ri ri-add-line me-1"></i> Tambah Jadwal
                </a>
            @endcan
        </x-slot:headerActions>

        {{-- Filter --}}
        <div class="row g-2 mb-3">
            <div class="col-md-5">
                <input type="text" id="searchInput" class="form-control form-control-sm"
                    placeholder="Cari tempat atau catatan...">
            </div>
            <div class="col-md-2">
                <select id="filterStatus" class="form-select form-select-sm">
                    <option value="">Semua Status</option>
                    <option value="aktif">Aktif</option>
                    <option value="nonaktif">Nonaktif</option>
                    <option value="selesai">Selesai</option>
                </select>
            </div>
            <div class="col-md-2">
                <select id="filterTime" class="form-select form-select-sm">
                    <option value="">Semua Waktu</option>
                    <option value="today">Hari Ini</option>
                    <option value="upcoming">Akan Datang</option>
                    <option value="past">Sudah Lewat</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="button" id="btnResetFilter" class="btn btn-outline-secondary btn-sm w-100">
                    <i class="ri ri-refresh-line me-1"></i> Reset Filter
                </button>
            </div>
        </div>

        {{-- DataTable --}}
        <div class="table-responsive">
            <table id="datatable" class="table table-hover w-100">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 4%">No</th>
                        <th style="width: 11%">Tgl Pelaksanaan</th>
                        <th class="text-center" style="width: 12%">Jam</th>
                        <th>Tempat</th>
                        <th class="text-center" style="width: 8%">Puasa</th>
                        <th class="text-center" style="width: 10%">Diinput Oleh</th>
                        <th class="text-center" style="width: 9%">Status</th>
                        <th class="text-center" style="width: 16%">Aksi</th>
                    </tr>
                </thead>
            </table>
        </div>
    </x-card>

@endsection

@push('scripts')
    <script>
        window.whenKesehatanReady(function() {
            'use strict';

            const CONFIG = {
                ROUTES: {
                    DATA: '{{ route('jadwal-cgd.data') }}',
                    SHOW: '{{ route('jadwal-cgd.show', ':id') }}',
                    EDIT: '{{ route('jadwal-cgd.edit', ':id') }}',
                    DESTROY: '{{ route('jadwal-cgd.destroy', ':id') }}',
                    DEACTIVATE: '{{ route('jadwal-cgd.deactivate', ':id') }}',
                    ACTIVATE: '{{ route('jadwal-cgd.activate', ':id') }}',
                    MARK_SELESAI: '{{ route('jadwal-cgd.mark-selesai', ':id') }}',
                },
                STORAGE_KEY: 'jadwal-cgd',
            };

            const STATUS_BADGES = {
                'aktif': '<span class="badge bg-success-subtle text-success"><i class="ri ri-check-line"></i> Aktif</span>',
                'nonaktif': '<span class="badge bg-secondary-subtle text-secondary"><i class="ri ri-pause-circle-line"></i> Nonaktif</span>',
                'selesai': '<span class="badge bg-info-subtle text-info"><i class="ri ri-flag-line"></i> Selesai</span>',
            };

            const PUASA_BADGES = {
                'Wajib': '<span class="badge bg-warning-subtle text-warning"><i class="ri ri-restaurant-line"></i> Wajib</span>',
                'Tidak': '<span class="badge bg-light text-muted">Tidak</span>',
            };

            const formatDate = (iso) => {
                if (!iso) return '-';
                const d = new Date(iso);
                return d.toLocaleDateString('id-ID', {
                    day: 'numeric',
                    month: 'short',
                    year: 'numeric'
                });
            };

            const formatJam = (time) => {
                if (!time) return '-';
                return time.substring(0, 5);
            };

            // Cek apakah tanggal hari ini
            const isToday = (iso) => {
                if (!iso) return false;
                const d = new Date(iso);
                const today = new Date();
                return d.toDateString() === today.toDateString();
            };

            // Cek apakah tanggal lewat
            const isPast = (iso) => {
                if (!iso) return false;
                const d = new Date(iso);
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                d.setHours(0, 0, 0, 0);
                return d < today;
            };

            const renderActions = (row) => {
                const id = row.id;
                const actions = [];

                actions.push(
                    `<a href="${CONFIG.ROUTES.SHOW.replace(':id', id)}" class="btn btn-sm btn-outline-info" title="Detail"><i class="ri ri-eye-line"></i></a>`
                    );

                @can('jadwal-cgd.edit')
                    actions.push(
                        `<a href="${CONFIG.ROUTES.EDIT.replace(':id', id)}" class="btn btn-sm btn-outline-warning" title="Edit"><i class="ri ri-pencil-line"></i></a>`
                        );

                    if (row.status === 'aktif') {
                        actions.push(
                            `<button class="btn btn-sm btn-outline-secondary btn-deactivate" data-id="${id}" data-tempat="${row.tempat}" title="Nonaktifkan"><i class="ri ri-pause-circle-line"></i></button>`
                            );
                        actions.push(
                            `<button class="btn btn-sm btn-outline-info btn-selesai" data-id="${id}" data-tempat="${row.tempat}" title="Tandai Selesai"><i class="ri ri-flag-line"></i></button>`
                            );
                    } else if (row.status === 'nonaktif') {
                        actions.push(
                            `<button class="btn btn-sm btn-outline-success btn-activate" data-id="${id}" data-tempat="${row.tempat}" title="Aktifkan"><i class="ri ri-play-circle-line"></i></button>`
                            );
                    }
                @endcan

                @can('jadwal-cgd.delete')
                    actions.push(
                        `<button class="btn btn-sm btn-outline-danger btn-delete-row" data-id="${id}" data-name="${row.tempat}" title="Hapus"><i class="ri ri-delete-bin-line"></i></button>`
                        );
                @endcan

                return `<div class="row-actions">${actions.join('')}</div>`;
            };

            new DataGrid({
                selector: '#datatable',
                ajaxUrl: CONFIG.ROUTES.DATA,
                storageKey: CONFIG.STORAGE_KEY,
                order: [
                    [0, 'desc']
                ],
                filters: {
                    search: '#searchInput',
                    status: '#filterStatus',
                    time_filter: '#filterTime',
                },
                columns: [{
                        data: null,
                        orderable: false,
                        className: 'text-center',
                        render: (d, t, r, meta) => meta.row + meta.settings._iDisplayStart + 1
                    },
                    {
                        data: 'tgl_jadwal_cgd',
                        render: (v) => {
                            let badge = '';
                            if (isToday(v)) badge =
                                ' <span class="badge bg-danger-subtle text-danger ms-1">Hari Ini</span>';
                            else if (isPast(v)) badge =
                                ' <span class="badge bg-light text-muted ms-1">Lewat</span>';
                            return `<strong>${formatDate(v)}</strong>${badge}`;
                        }
                    },
                    {
                        data: null,
                        className: 'text-center',
                        render: (d, t, row) => {
                            return `<span class="badge bg-light text-dark">${formatJam(row.jam_mulai)}</span> - <span class="badge bg-light text-dark">${formatJam(row.jam_berakhir)}</span>`;
                        }
                    },
                    {
                        data: 'tempat',
                        render: (v, t, row) => {
                            const catatan = row.catatan ?
                                `<br><small class="text-muted"><i class="ri ri-information-line"></i> ${row.catatan.substring(0, 60)}${row.catatan.length > 60 ? '...' : ''}</small>` :
                                '';
                            return `<i class="ri ri-map-pin-line text-danger"></i> <strong>${v}</strong>${catatan}`;
                        }
                    },
                    {
                        data: 'puasa',
                        className: 'text-center',
                        render: (v) => PUASA_BADGES[v] || v
                    },
                    {
                        data: null,
                        className: 'text-center small',
                        render: (d, t, row) => row.creator?.name || '<span class="text-muted">-</span>'
                    },
                    {
                        data: 'status',
                        className: 'text-center',
                        render: (v) => STATUS_BADGES[v] || v
                    },
                    {
                        data: null,
                        orderable: false,
                        className: 'text-center',
                        render: (d, t, row) => renderActions(row)
                    },
                ],
                onDelete: async (id) => {
                    const url = CONFIG.ROUTES.DESTROY.replace(':id', id);
                    return $.ajax({
                        url,
                        method: 'DELETE'
                    });
                },
            });

            // Filter helpers
            window.filterByStatus = (status) => $('#filterStatus').val(status).trigger('change');
            window.filterByTime = (time) => $('#filterTime').val(time).trigger('change');
            window.filterReset = () => {
                $('#searchInput').val('');
                $('#filterStatus').val('').trigger('change');
                $('#filterTime').val('').trigger('change');
            };

            $('#btnResetFilter').on('click', filterReset);

            // ============ STATUS ACTIONS ============
            const performStatusAction = (id, url, swalConfig) => {
                Swal.fire(swalConfig).then(function(result) {
                    if (!result.isConfirmed) return;

                    $.ajax({
                        url: url.replace(':id', id),
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

            $(document).on('click', '.btn-deactivate', function() {
                const id = $(this).data('id');
                const tempat = $(this).data('tempat');
                performStatusAction(id, CONFIG.ROUTES.DEACTIVATE, {
                    title: 'Nonaktifkan jadwal?',
                    html: 'Event di <strong>' + tempat + '</strong> akan dinonaktifkan.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Nonaktifkan',
                    cancelButtonText: 'Batal',
                    customClass: {
                        confirmButton: 'btn btn-warning me-2',
                        cancelButton: 'btn btn-secondary'
                    },
                    buttonsStyling: false,
                });
            });

            $(document).on('click', '.btn-activate', function() {
                const id = $(this).data('id');
                const tempat = $(this).data('tempat');
                performStatusAction(id, CONFIG.ROUTES.ACTIVATE, {
                    title: 'Aktifkan kembali?',
                    html: 'Event di <strong>' + tempat + '</strong> akan diaktifkan kembali.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Aktifkan',
                    cancelButtonText: 'Batal',
                    customClass: {
                        confirmButton: 'btn btn-success me-2',
                        cancelButton: 'btn btn-secondary'
                    },
                    buttonsStyling: false,
                });
            });

            $(document).on('click', '.btn-selesai', function() {
                const id = $(this).data('id');
                const tempat = $(this).data('tempat');
                performStatusAction(id, CONFIG.ROUTES.MARK_SELESAI, {
                    title: 'Tandai event selesai?',
                    html: 'Event di <strong>' + tempat + '</strong> akan ditandai selesai.',
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Selesai',
                    cancelButtonText: 'Batal',
                    customClass: {
                        confirmButton: 'btn btn-info me-2',
                        cancelButton: 'btn btn-secondary'
                    },
                    buttonsStyling: false,
                });
            });
        });
    </script>
@endpush
