@extends('layouts.app')

@section('title', 'Jadwal Minum Obat')

@section('page-header')
    <nav aria-label="breadcrumb" class="small">
        <ol class="breadcrumb mb-2">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item active">Jadwal Minum Obat</li>
        </ol>
    </nav>
    <div>
        <h4 class="fw-bold mb-1"><i class="ri ri-calendar-check-line text-primary me-1"></i> Jadwal Minum Obat</h4>
        <small class="text-muted">Kelola jadwal minum obat pasien.</small>
    </div>
@endsection

@section('content')

    @php
        $stats = \App\Services\JadwalMinumObatService::getStats();
    @endphp

    {{-- ============ STATS CARDS ============ --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <a href="javascript:void(0)" onclick="filterByStatus('')" class="text-decoration-none">
                <x-card class="card-hover h-100">
                    <div class="text-muted small"><i class="ri ri-list-check"></i> Total</div>
                    <div class="display-stat fs-2 text-primary">{{ $stats['total'] }}</div>
                </x-card>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="javascript:void(0)" onclick="filterByStatus('aktif')" class="text-decoration-none">
                <x-card class="card-hover h-100">
                    <div class="text-muted small"><i class="ri ri-check-line text-success"></i> Aktif</div>
                    <div class="display-stat fs-2 text-success">{{ $stats['aktif'] }}</div>
                </x-card>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="javascript:void(0)" onclick="filterByStatus('nonaktif')" class="text-decoration-none">
                <x-card class="card-hover h-100">
                    <div class="text-muted small"><i class="ri ri-pause-circle-line text-secondary"></i> Nonaktif</div>
                    <div class="display-stat fs-2 text-secondary">{{ $stats['nonaktif'] }}</div>
                </x-card>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="javascript:void(0)" onclick="filterByStatus('selesai')" class="text-decoration-none">
                <x-card class="card-hover h-100">
                    <div class="text-muted small"><i class="ri ri-flag-line text-info"></i> Selesai</div>
                    <div class="display-stat fs-2 text-info">{{ $stats['selesai'] }}</div>
                </x-card>
            </a>
        </div>
    </div>

    {{-- ============ DATA TABLE ============ --}}
    <x-card title="Daftar Jadwal" icon="ri-calendar-check-line">
        <x-slot:headerActions>
            @can('jadwal-mo.create')
                <a href="{{ route('jadwal-mo.create') }}" class="btn btn-primary btn-sm">
                    <i class="ri ri-add-line me-1"></i> Tambah Jadwal
                </a>
            @endcan
        </x-slot:headerActions>

        {{-- Filter --}}
        <div class="row g-2 mb-3">
            <div class="col-md-6">
                <input type="text" id="searchInput" class="form-control form-control-sm"
                    placeholder="Cari nama pasien, nama PMO, atau nama obat...">
            </div>
            <div class="col-md-3">
                <select id="filterStatus" class="form-select form-select-sm">
                    <option value="">Semua Status</option>
                    <option value="aktif">Aktif</option>
                    <option value="nonaktif">Nonaktif</option>
                    <option value="selesai">Selesai</option>
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
                        <th>Pasien</th>
                        <th>PMO</th>
                        <th>Obat</th>
                        <th class="text-center" style="width: 10%">Jam Mulai</th>
                        <th class="text-center" style="width: 7%">Frek/Hari</th>
                        <th class="text-center" style="width: 9%">Tgl Mulai</th>
                        <th class="text-center" style="width: 8%">Status</th>
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
                    DATA: '{{ route('jadwal-mo.data') }}',
                    SHOW: '{{ route('jadwal-mo.show', ':id') }}',
                    EDIT: '{{ route('jadwal-mo.edit', ':id') }}',
                    DESTROY: '{{ route('jadwal-mo.destroy', ':id') }}',
                    DEACTIVATE: '{{ route('jadwal-mo.deactivate', ':id') }}',
                    ACTIVATE: '{{ route('jadwal-mo.activate', ':id') }}',
                    MARK_SELESAI: '{{ route('jadwal-mo.mark-selesai', ':id') }}',
                },
                STORAGE_KEY: 'jadwal-mo',
            };

            const STATUS_BADGES = {
                'aktif': '<span class="badge bg-success-subtle text-success"><i class="ri ri-check-line"></i> Aktif</span>',
                'nonaktif': '<span class="badge bg-secondary-subtle text-secondary"><i class="ri ri-pause-circle-line"></i> Nonaktif</span>',
                'selesai': '<span class="badge bg-info-subtle text-info"><i class="ri ri-flag-line"></i> Selesai</span>',
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

            const renderActions = (row) => {
                const id = row.id;
                const actions = [];

                // Detail (selalu ada)
                actions.push(
                    `<a href="${CONFIG.ROUTES.SHOW.replace(':id', id)}" class="btn btn-sm btn-outline-info" title="Detail"><i class="ri ri-eye-line"></i></a>`
                    );

                @can('jadwal-mo.edit')
                    actions.push(
                        `<a href="${CONFIG.ROUTES.EDIT.replace(':id', id)}" class="btn btn-sm btn-outline-warning" title="Edit"><i class="ri ri-pencil-line"></i></a>`
                        );

                    // Status toggle
                    if (row.status === 'aktif') {
                        actions.push(
                            `<button class="btn btn-sm btn-outline-secondary btn-deactivate" data-id="${id}" data-name="${row.nama_pasien}" title="Nonaktifkan"><i class="ri ri-pause-circle-line"></i></button>`
                            );
                        actions.push(
                            `<button class="btn btn-sm btn-outline-info btn-selesai" data-id="${id}" data-name="${row.nama_pasien}" title="Tandai Selesai"><i class="ri ri-flag-line"></i></button>`
                            );
                    } else if (row.status === 'nonaktif') {
                        actions.push(
                            `<button class="btn btn-sm btn-outline-success btn-activate" data-id="${id}" data-name="${row.nama_pasien}" title="Aktifkan"><i class="ri ri-play-circle-line"></i></button>`
                            );
                    }
                @endcan

                @can('jadwal-mo.delete')
                    actions.push(
                        `<button class="btn btn-sm btn-outline-danger btn-delete-row" data-id="${id}" data-name="${row.nama_pasien}" title="Hapus"><i class="ri ri-delete-bin-line"></i></button>`
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
                },
                columns: [{
                        data: null,
                        orderable: false,
                        className: 'text-center',
                        render: (d, t, r, meta) => meta.row + meta.settings._iDisplayStart + 1
                    },
                    {
                        data: 'nama_pasien',
                        render: (v) => `<strong>${v}</strong>`
                    },
                    {
                        data: 'nama_pmo',
                        render: (v) => v || '<span class="text-muted">-</span>'
                    },
                    {
                        data: null,
                        render: (d, t, row) => {
                            const obat = row.obat;
                            if (!obat) return '<span class="text-muted">-</span>';
                            const nama = obat.nama || '-';
                            const dosis = obat.dosis_default ? ` ${obat.dosis_default}` : '';
                            const satuan = obat.satuan?.singkatan ? ` (${obat.satuan.singkatan})` :
                                '';
                            return `<strong>${nama}</strong>${dosis}${satuan}`;
                        }
                    },
                    {
                        data: 'jam_mulai',
                        className: 'text-center',
                        render: (v) => `<span class="badge bg-light text-dark">${formatJam(v)}</span>`
                    },
                    {
                        data: 'frekuensi_per_hari',
                        className: 'text-center',
                        render: (v) =>
                            `<span class="fw-bold">${v}x</span><br><small class="text-muted">sehari</small>`
                    },
                    {
                        data: 'tgl_mulai',
                        className: 'text-center',
                        render: (v) => formatDate(v)
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

            // Helper filter dari stats card
            window.filterByStatus = (status) => $('#filterStatus').val(status).trigger('change');

            // Reset filter
            $('#btnResetFilter').on('click', function() {
                $('#searchInput').val('');
                $('#filterStatus').val('').trigger('change');
            });

            // ============ DEACTIVATE ============
            $(document).on('click', '.btn-deactivate', function() {
                const id = $(this).data('id');
                const name = $(this).data('name');

                Swal.fire({
                    title: 'Nonaktifkan jadwal?',
                    html: 'Jadwal untuk <strong>' + name +
                        '</strong> akan dinonaktifkan.<br><small class="text-muted">Bisa diaktifkan kembali nanti.</small>',
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

                    const url = CONFIG.ROUTES.DEACTIVATE.replace(':id', id);
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
                        Swal.fire('Gagal!', xhr.responseJSON?.message ||
                            'Terjadi kesalahan', 'error');
                    });
                });
            });

            // ============ ACTIVATE ============
            $(document).on('click', '.btn-activate', function() {
                const id = $(this).data('id');
                const name = $(this).data('name');

                Swal.fire({
                    title: 'Aktifkan kembali jadwal?',
                    html: 'Jadwal untuk <strong>' + name + '</strong> akan diaktifkan kembali.',
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

                    const url = CONFIG.ROUTES.ACTIVATE.replace(':id', id);
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
                        Swal.fire('Gagal!', xhr.responseJSON?.message ||
                            'Terjadi kesalahan', 'error');
                    });
                });
            });

            // ============ MARK SELESAI ============
            $(document).on('click', '.btn-selesai', function() {
                const id = $(this).data('id');
                const name = $(this).data('name');

                Swal.fire({
                    title: 'Tandai jadwal selesai?',
                    html: 'Jadwal untuk <strong>' + name +
                        '</strong> akan ditandai selesai (pengobatan tuntas).<br><small class="text-muted">Status ini biasanya untuk pengobatan yang sudah berakhir.</small>',
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Tandai Selesai',
                    cancelButtonText: 'Batal',
                    customClass: {
                        confirmButton: 'btn btn-info me-2',
                        cancelButton: 'btn btn-secondary'
                    },
                    buttonsStyling: false,
                }).then(function(result) {
                    if (!result.isConfirmed) return;

                    const url = CONFIG.ROUTES.MARK_SELESAI.replace(':id', id);
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
                        Swal.fire('Gagal!', xhr.responseJSON?.message ||
                            'Terjadi kesalahan', 'error');
                    });
                });
            });
        });
    </script>
@endpush
