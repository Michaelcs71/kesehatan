@extends('layouts.app')

@section('title', 'Pasien PMO')

@section('page-header')
    <nav aria-label="breadcrumb" class="small">
        <ol class="breadcrumb mb-2">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item active">Pasien PMO</li>
        </ol>
    </nav>
    <div>
        <h4 class="fw-bold mb-1"><i class="ri ri-links-line text-primary me-1"></i> Pasien PMO Mapping</h4>
        <small class="text-muted">Kelola mapping pasien ke PMO (Pendamping Minum Obat).</small>
    </div>
@endsection

@section('content')

    @php
        $stats = \App\Services\PasienPmoService::getStats();
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
            <a href="javascript:void(0)" onclick="filterByStatus('1')" class="text-decoration-none">
                <x-card class="card-hover h-100">
                    <div class="text-muted small"><i class="ri ri-check-line text-success"></i> Aktif</div>
                    <div class="display-stat fs-2 text-success">{{ $stats['active'] }}</div>
                </x-card>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="javascript:void(0)" onclick="filterByJenis('Keluarga')" class="text-decoration-none">
                <x-card class="card-hover h-100">
                    <div class="text-muted small"><i class="ri ri-home-heart-line text-info"></i> Keluarga</div>
                    <div class="display-stat fs-2 text-info">{{ $stats['keluarga'] }}</div>
                </x-card>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="javascript:void(0)" onclick="filterByJenis('Kader')" class="text-decoration-none">
                <x-card class="card-hover h-100">
                    <div class="text-muted small"><i class="ri ri-shield-user-line text-warning"></i> Kader</div>
                    <div class="display-stat fs-2 text-warning">{{ $stats['kader'] }}</div>
                </x-card>
            </a>
        </div>
    </div>

    {{-- ============ DATA TABLE ============ --}}
    <x-card title="Daftar Mapping" icon="ri-links-line">
        <x-slot:headerActions>
            @can('pasien-pmo.create')
                <a href="{{ route('pasien-pmo.create') }}" class="btn btn-primary btn-sm">
                    <i class="ri ri-add-line me-1"></i> Tambah Mapping
                </a>
            @endcan
        </x-slot:headerActions>

        {{-- Filter --}}
        <div class="row g-2 mb-3">
            <div class="col-md-5">
                <input type="text" id="searchInput" class="form-control form-control-sm"
                    placeholder="Cari nama pasien, nama PMO, atau NIK...">
            </div>
            <div class="col-md-2">
                <select id="filterStatus" class="form-select form-select-sm">
                    <option value="">Semua Status</option>
                    <option value="1">Aktif</option>
                    <option value="0">Nonaktif</option>
                </select>
            </div>
            <div class="col-md-2">
                <select id="filterJenis" class="form-select form-select-sm">
                    <option value="">Semua Jenis</option>
                    <option value="Keluarga">Keluarga</option>
                    <option value="Kader">Kader</option>
                </select>
            </div>
            <div class="col-md-3">
                <select id="filterDiabetes" class="form-select form-select-sm">
                    <option value="">Semua Status Diabetes</option>
                    <option value="Rendah">Rendah</option>
                    <option value="Sedang">Sedang</option>
                    <option value="Tinggi">Tinggi</option>
                </select>
            </div>
        </div>

        {{-- DataTable --}}
        <div class="table-responsive">
            <table id="datatable" class="table table-hover w-100">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 4%">No</th>
                        <th>Pasien</th>
                        <th>NIK</th>
                        <th>PMO</th>
                        <th class="text-center" style="width: 10%">Jenis PMO</th>
                        <th class="text-center" style="width: 10%">Status Diabetes</th>
                        <th class="text-center" style="width: 9%">Tgl Regis</th>
                        <th class="text-center" style="width: 8%">Status</th>
                        <th class="text-center" style="width: 14%">Aksi</th>
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
                    DATA: '{{ route('pasien-pmo.data') }}',
                    SHOW: '{{ route('pasien-pmo.show', ':id') }}',
                    EDIT: '{{ route('pasien-pmo.edit', ':id') }}',
                    DESTROY: '{{ route('pasien-pmo.destroy', ':id') }}',
                    DEACTIVATE: '{{ route('pasien-pmo.deactivate', ':id') }}',
                    ACTIVATE: '{{ route('pasien-pmo.activate', ':id') }}',
                },
                STORAGE_KEY: 'pasien-pmo',
            };

            const JENIS_BADGES = {
                'Keluarga': '<span class="badge bg-info-subtle text-info"><i class="ri ri-home-heart-line"></i> Keluarga</span>',
                'Kader': '<span class="badge bg-warning-subtle text-warning"><i class="ri ri-shield-user-line"></i> Kader</span>',
            };

            const DIABETES_BADGES = {
                'Rendah': '<span class="badge bg-success-subtle text-success">Rendah</span>',
                'Sedang': '<span class="badge bg-warning-subtle text-warning">Sedang</span>',
                'Tinggi': '<span class="badge bg-danger-subtle text-danger">Tinggi</span>',
            };

            const STATUS_BADGES = {
                true: '<span class="badge bg-success-subtle text-success"><i class="ri ri-check-line"></i> Aktif</span>',
                false: '<span class="badge bg-secondary-subtle text-secondary"><i class="ri ri-close-line"></i> Nonaktif</span>',
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

            const renderActions = (row) => {
                const id = row.id;
                const actions = [];

                // Detail
                actions.push(
                    `<a href="${CONFIG.ROUTES.SHOW.replace(':id', id)}" class="btn btn-sm btn-outline-info" title="Detail"><i class="ri ri-eye-line"></i></a>`
                    );

                @can('pasien-pmo.edit')
                    actions.push(
                        `<a href="${CONFIG.ROUTES.EDIT.replace(':id', id)}" class="btn btn-sm btn-outline-warning" title="Edit"><i class="ri ri-pencil-line"></i></a>`
                        );

                    // Toggle aktif/nonaktif
                    if (row.is_active) {
                        actions.push(
                            `<button class="btn btn-sm btn-outline-secondary btn-deactivate" data-id="${id}" data-name="${row.nama_pasien}" title="Nonaktifkan"><i class="ri ri-pause-circle-line"></i></button>`
                            );
                    } else {
                        actions.push(
                            `<button class="btn btn-sm btn-outline-success btn-activate" data-id="${id}" data-name="${row.nama_pasien}" title="Aktifkan kembali"><i class="ri ri-play-circle-line"></i></button>`
                            );
                    }
                @endcan

                @can('pasien-pmo.delete')
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
                    is_active: '#filterStatus',
                    jenis_pmo: '#filterJenis',
                    status_diabetes: '#filterDiabetes',
                },
                columns: [{
                        data: null,
                        orderable: false,
                        className: 'text-center',
                        render: (d, t, r, meta) => meta.row + meta.settings._iDisplayStart + 1
                    },
                    {
                        data: 'nama_pasien',
                        render: (v, t, row) => {
                            const subtitle = row.pasien?.whatsapp_number ?
                                `<br><small class="text-muted"><i class="ri ri-whatsapp-line"></i> ${row.pasien.whatsapp_number}</small>` :
                                '';
                            return `<strong>${v}</strong>${subtitle}`;
                        }
                    },
                    {
                        data: 'nik',
                        render: (v) => v ? `<code class="small">${v}</code>` :
                            '<span class="text-muted">-</span>'
                    },
                    {
                        data: 'nama_pmo',
                        render: (v, t, row) => {
                            const subtitle = row.pmo?.whatsapp_number ?
                                `<br><small class="text-muted"><i class="ri ri-whatsapp-line"></i> ${row.pmo.whatsapp_number}</small>` :
                                '';
                            return `<strong>${v}</strong>${subtitle}`;
                        }
                    },
                    {
                        data: 'jenis_pmo',
                        className: 'text-center',
                        render: (v) => JENIS_BADGES[v] || v
                    },
                    {
                        data: 'status_diabetes',
                        className: 'text-center',
                        render: (v) => DIABETES_BADGES[v] || v
                    },
                    {
                        data: 'tanggal_regis',
                        className: 'text-center',
                        render: (v) => formatDate(v)
                    },
                    {
                        data: 'is_active',
                        className: 'text-center',
                        render: (v) => STATUS_BADGES[v ? 'true' : 'false']
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
            window.filterByJenis = (jenis) => $('#filterJenis').val(jenis).trigger('change');

            // ============ DEACTIVATE ============
            $(document).on('click', '.btn-deactivate', function() {
                const id = $(this).data('id');
                const name = $(this).data('name');

                Swal.fire({
                    title: 'Nonaktifkan mapping?',
                    html: 'Mapping pasien <strong>' + name +
                        '</strong> akan dinonaktifkan.<br><small class="text-muted">Data tetap tersimpan, bisa diaktifkan kembali nanti.</small>',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Nonaktifkan',
                    cancelButtonText: 'Batal',
                    customClass: {
                        confirmButton: 'btn btn-warning me-2',
                        cancelButton: 'btn btn-secondary',
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
                            showConfirmButton: false,
                        }).then(() => window.location.reload());
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
                    title: 'Aktifkan kembali mapping?',
                    html: 'Mapping pasien <strong>' + name + '</strong> akan diaktifkan kembali.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Aktifkan',
                    cancelButtonText: 'Batal',
                    customClass: {
                        confirmButton: 'btn btn-success me-2',
                        cancelButton: 'btn btn-secondary',
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
                            showConfirmButton: false,
                        }).then(() => window.location.reload());
                    }).fail(function(xhr) {
                        Swal.fire('Gagal!', xhr.responseJSON?.message ||
                            'Terjadi kesalahan', 'error');
                    });
                });
            });
        });
    </script>
@endpush
