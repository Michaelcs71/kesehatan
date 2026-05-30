@extends('layouts.app')

@section('title', 'Pengingat Minum Obat')

@section('page-header')
    <nav aria-label="breadcrumb" class="small">
        <ol class="breadcrumb mb-2">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item active">Pengingat Minum Obat</li>
        </ol>
    </nav>
    <div>
        <h4 class="fw-bold mb-1"><i class="ri ri-notification-2-line text-success me-1"></i> Pengingat Minum Obat</h4>
        <small class="text-muted">
            @can('pengingat-mo.create')
                Catat setiap minum obat sebagai bukti kepatuhan.
            @else
                Pantau kepatuhan minum obat pasien.
            @endcan
        </small>
    </div>
@endsection

@section('content')

    @php
        $stats = \App\Services\PengingatMoLogService::getStats();
    @endphp

    {{-- ============ STATS CARDS ============ --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <a href="javascript:void(0)" onclick="filterReset()" class="text-decoration-none">
                <x-card class="card-hover h-100">
                    <div class="text-muted small"><i class="ri ri-list-check"></i> Total Hari Ini</div>
                    <div class="display-stat fs-2 text-primary">{{ $stats['total_today'] }}</div>
                </x-card>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="javascript:void(0)" onclick="filterByPatuh('tepat_waktu')" class="text-decoration-none">
                <x-card class="card-hover h-100">
                    <div class="text-muted small"><i class="ri ri-check-double-line text-success"></i> Tepat Waktu</div>
                    <div class="display-stat fs-2 text-success">{{ $stats['tepat_today'] }}</div>
                </x-card>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="javascript:void(0)" onclick="filterByPatuh('terlambat')" class="text-decoration-none">
                <x-card class="card-hover h-100">
                    <div class="text-muted small"><i class="ri ri-time-line text-warning"></i> Telat Hari Ini</div>
                    <div class="display-stat fs-2 text-warning">{{ $stats['telat_today'] }}</div>
                </x-card>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <x-card class="h-100">
                <div class="text-muted small"><i class="ri ri-archive-line"></i> Total Semua</div>
                <div class="display-stat fs-2 text-info">{{ $stats['total_all'] }}</div>
            </x-card>
        </div>
    </div>

    {{-- ============ DATA TABLE ============ --}}
    <x-card title="Riwayat Konfirmasi" icon="ri-history-line">
        <x-slot:headerActions>
            @can('pengingat-mo.create')
                <a href="{{ route('pengingat-mo.create') }}" class="btn btn-primary btn-sm">
                    <i class="ri ri-add-line me-1"></i> Konfirmasi Minum Obat
                </a>
            @endcan
        </x-slot:headerActions>

        {{-- Filter --}}
        <div class="row g-2 mb-3">
            <div class="col-md-4">
                <input type="text" id="searchInput" class="form-control form-control-sm"
                    placeholder="Cari nama pasien atau obat...">
            </div>
            <div class="col-md-2">
                <input type="date" id="filterStart" class="form-control form-control-sm" placeholder="Dari tanggal">
            </div>
            <div class="col-md-2">
                <input type="date" id="filterEnd" class="form-control form-control-sm" placeholder="Sampai tanggal">
            </div>
            <div class="col-md-2">
                <select id="filterPatuh" class="form-select form-select-sm">
                    <option value="">Semua Kepatuhan</option>
                    <option value="tepat_waktu">Tepat Waktu</option>
                    <option value="terlambat">Terlambat</option>
                    <option value="sangat_terlambat">Sangat Terlambat</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="button" id="btnResetFilter" class="btn btn-outline-secondary btn-sm w-100">
                    <i class="ri ri-refresh-line me-1"></i> Reset
                </button>
            </div>
        </div>

        {{-- DataTable --}}
        <div class="table-responsive">
            <table id="datatable" class="table table-hover w-100">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 4%">No</th>
                        <th style="width: 7%" class="text-center">Foto</th>
                        <th>Pasien</th>
                        <th>Obat</th>
                        <th class="text-center" style="width: 9%">Tgl</th>
                        <th class="text-center" style="width: 9%">Jam</th>
                        <th class="text-center" style="width: 12%">Kepatuhan</th>
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
                    DATA: '{{ route('pengingat-mo.data') }}',
                    SHOW: '{{ route('pengingat-mo.show', ':id') }}',
                    EDIT: '{{ route('pengingat-mo.edit', ':id') }}',
                    DESTROY: '{{ route('pengingat-mo.destroy', ':id') }}',
                },
                STORAGE_KEY: 'pengingat-mo',
            };

            const PATUH_BADGES = {
                'tepat_waktu': '<span class="badge bg-success-subtle text-success"><i class="ri ri-check-double-line"></i> Tepat</span>',
                'terlambat': '<span class="badge bg-warning-subtle text-warning"><i class="ri ri-time-line"></i> Terlambat</span>',
                'sangat_terlambat': '<span class="badge bg-danger-subtle text-danger"><i class="ri ri-alarm-warning-line"></i> Sangat Terlambat</span>',
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

            const getPatuhKategori = (menit) => {
                const abs = Math.abs(menit);
                if (abs <= 15) return 'tepat_waktu';
                if (abs <= 60) return 'terlambat';
                return 'sangat_terlambat';
            };

            const getPatuhLabel = (menit) => {
                if (menit === 0) return 'Tepat waktu';
                if (menit > 0) return `+${menit} menit (telat)`;
                return `${Math.abs(menit)} menit awal`;
            };

            const renderActions = (row) => {
                const id = row.id;
                const actions = [];

                actions.push(
                    `<a href="${CONFIG.ROUTES.SHOW.replace(':id', id)}" class="btn btn-sm btn-outline-info" title="Detail"><i class="ri ri-eye-line"></i></a>`
                    );

                @can('pengingat-mo.edit')
                    actions.push(
                        `<a href="${CONFIG.ROUTES.EDIT.replace(':id', id)}" class="btn btn-sm btn-outline-warning" title="Edit"><i class="ri ri-pencil-line"></i></a>`
                        );
                @endcan

                @can('pengingat-mo.delete')
                    actions.push(
                        `<button class="btn btn-sm btn-outline-danger btn-delete-row" data-id="${id}" data-name="${row.nama_obat}" title="Hapus"><i class="ri ri-delete-bin-line"></i></button>`
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
                    tgl_start: '#filterStart',
                    tgl_end: '#filterEnd',
                    patuh_kategori: '#filterPatuh',
                },
                columns: [{
                        data: null,
                        orderable: false,
                        className: 'text-center',
                        render: (d, t, r, meta) => meta.row + meta.settings._iDisplayStart + 1
                    },
                    {
                        data: 'foto_obat',
                        orderable: false,
                        className: 'text-center',
                        render: (v, t, row) => {
                            if (!v) return '<span class="text-muted">-</span>';
                            const url = '/storage/' + v;
                            return `<a href="${url}" target="_blank" data-img-zoom><img src="${url}" alt="foto" style="width:40px;height:40px;object-fit:cover;border-radius:4px;cursor:pointer;border:1px solid #dee2e6;"></a>`;
                        }
                    },
                    {
                        data: 'nama_pasien',
                        render: (v) => `<strong>${v || '-'}</strong>`
                    },
                    {
                        data: null,
                        render: (d, t, row) => {
                            const nama = row.nama_obat || '-';
                            const obat = row.jadwal_mo?.obat;
                            const dosis = obat?.dosis_default ? ` ${obat.dosis_default}` : '';
                            const satuan = obat?.satuan?.singkatan ? ` (${obat.satuan.singkatan})` :
                                '';
                            return `<strong>${nama}</strong>${dosis}${satuan}`;
                        }
                    },
                    {
                        data: 'tgl_minum_obat',
                        className: 'text-center',
                        render: (v) => formatDate(v)
                    },
                    {
                        data: null,
                        className: 'text-center',
                        render: (d, t, row) => {
                            const actual = formatJam(row.jam_minum_obat);
                            const slot = row.jam_slot_target ? formatJam(row.jam_slot_target) :
                            null;
                            if (!slot)
                            return `<span class="badge bg-light text-dark">${actual}</span>`;
                            return `<span class="badge bg-light text-dark">${actual}</span><br><small class="text-muted">slot: ${slot}</small>`;
                        }
                    },
                    {
                        data: 'patuh_menit',
                        className: 'text-center',
                        render: (v) => {
                            const kat = getPatuhKategori(v);
                            const label = getPatuhLabel(v);
                            return `${PATUH_BADGES[kat]}<br><small class="text-muted">${label}</small>`;
                        }
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
            window.filterByPatuh = (kat) => $('#filterPatuh').val(kat).trigger('change');
            window.filterReset = () => {
                $('#searchInput').val('');
                $('#filterStart').val('');
                $('#filterEnd').val('');
                $('#filterPatuh').val('').trigger('change');
            };

            $('#btnResetFilter').on('click', filterReset);

            // Image zoom on click
            $(document).on('click', '[data-img-zoom]', function(e) {
                e.preventDefault();
                const url = $(this).attr('href');
                Swal.fire({
                    imageUrl: url,
                    imageAlt: 'Foto bukti minum obat',
                    showConfirmButton: false,
                    showCloseButton: true,
                    width: '80%',
                    background: '#000',
                    customClass: {
                        image: 'img-zoom-modal'
                    },
                });
            });
        });
    </script>

    <style>
        .img-zoom-modal {
            max-height: 80vh !important;
            max-width: 100% !important;
            object-fit: contain;
        }
    </style>
@endpush
