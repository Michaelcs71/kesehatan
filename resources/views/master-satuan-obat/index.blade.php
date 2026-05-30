@extends('layouts.app')

@section('title', 'Master Satuan Obat')

@section('page-header')
    <nav aria-label="breadcrumb" class="small">
        <ol class="breadcrumb mb-2">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item active">Master Satuan Obat</li>
        </ol>
    </nav>
    <div>
        <h4 class="fw-bold mb-1"><i class="ri ri-ruler-line text-primary me-1"></i> Master Satuan Obat</h4>
        <small class="text-muted">Kelola satuan untuk penggunaan obat di sistem.</small>
    </div>
@endsection

@section('content')

    @php
        $stats = \App\Services\MasterSatuanObatService::getStats();
    @endphp

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4">
            <a href="javascript:void(0)" onclick="filterByStatus('')" class="text-decoration-none">
                <x-card class="card-hover h-100">
                    <div class="text-muted small"><i class="ri ri-list-check"></i> Total Satuan</div>
                    <div class="display-stat fs-2 text-primary">{{ $stats['total'] }}</div>
                </x-card>
            </a>
        </div>
        <div class="col-6 col-md-4">
            <a href="javascript:void(0)" onclick="filterByStatus('1')" class="text-decoration-none">
                <x-card class="card-hover h-100">
                    <div class="text-muted small"><i class="ri ri-check-line text-success"></i> Aktif</div>
                    <div class="display-stat fs-2 text-success">{{ $stats['active'] }}</div>
                </x-card>
            </a>
        </div>
        <div class="col-6 col-md-4">
            <a href="javascript:void(0)" onclick="filterByStatus('0')" class="text-decoration-none">
                <x-card class="card-hover h-100">
                    <div class="text-muted small"><i class="ri ri-close-line text-secondary"></i> Nonaktif</div>
                    <div class="display-stat fs-2 text-secondary">{{ $stats['inactive'] }}</div>
                </x-card>
            </a>
        </div>
    </div>

    <x-card title="Daftar Satuan Obat" icon="ri-ruler-line">
        <x-slot:headerActions>
            @can('master-satuan-obat.create')
                <a href="{{ route('master-satuan-obat.create') }}" class="btn btn-primary btn-sm">
                    <i class="ri ri-add-line me-1"></i> Tambah Satuan
                </a>
            @endcan
        </x-slot:headerActions>

        <div class="row g-2 mb-3">
            <div class="col-md-8">
                <input type="text" id="searchInput" class="form-control form-control-sm"
                    placeholder="Cari nama, singkatan, atau deskripsi...">
            </div>
            <div class="col-md-4">
                <select id="filterStatus" class="form-select form-select-sm">
                    <option value="">Semua Status</option>
                    <option value="1">Aktif</option>
                    <option value="0">Nonaktif</option>
                </select>
            </div>
        </div>

        <div class="table-responsive">
            <table id="datatable" class="table table-hover w-100">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 5%">No</th>
                        <th>Nama Satuan</th>
                        <th style="width: 12%">Singkatan</th>
                        <th>Deskripsi</th>
                        <th class="text-center" style="width: 12%">Jumlah Obat</th>
                        <th class="text-center" style="width: 10%">Status</th>
                        <th class="text-center" style="width: 12%">Aksi</th>
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
                    DATA: '{{ route('master-satuan-obat.data') }}',
                    SHOW: '{{ route('master-satuan-obat.show', ':id') }}',
                    EDIT: '{{ route('master-satuan-obat.edit', ':id') }}',
                    DESTROY: '{{ route('master-satuan-obat.destroy', ':id') }}',
                },
                STORAGE_KEY: 'master-satuan-obat',
            };

            const STATUS_BADGES = {
                true: '<span class="badge bg-success-subtle text-success"><i class="ri ri-check-line"></i> Aktif</span>',
                false: '<span class="badge bg-secondary-subtle text-secondary"><i class="ri ri-close-line"></i> Nonaktif</span>',
            };

            const renderActions = (row) => {
                const id = row.id;
                const actions = [];
                actions.push(
                    `<a href="${CONFIG.ROUTES.SHOW.replace(':id', id)}" class="btn btn-sm btn-outline-info"><i class="ri ri-eye-line"></i></a>`
                    );
                @can('master-satuan-obat.edit')
                    actions.push(
                        `<a href="${CONFIG.ROUTES.EDIT.replace(':id', id)}" class="btn btn-sm btn-outline-warning"><i class="ri ri-pencil-line"></i></a>`
                        );
                @endcan
                @can('master-satuan-obat.delete')
                    actions.push(
                        `<button class="btn btn-sm btn-outline-danger btn-delete-row" data-id="${id}" data-name="${row.nama}"><i class="ri ri-delete-bin-line"></i></button>`
                        );
                @endcan
                return `<div class="row-actions">${actions.join('')}</div>`;
            };

            new DataGrid({
                selector: '#datatable',
                ajaxUrl: CONFIG.ROUTES.DATA,
                storageKey: CONFIG.STORAGE_KEY,
                order: [
                    [0, 'asc']
                ],
                filters: {
                    search: '#searchInput',
                    is_active: '#filterStatus',
                },
                columns: [{
                        data: null,
                        orderable: false,
                        className: 'text-center',
                        render: (d, t, r, meta) => meta.row + meta.settings._iDisplayStart + 1
                    },
                    {
                        data: 'nama',
                        name: 'nama',
                        render: (v, t, row) =>
                            `<strong>${v}</strong>${row.creator ? `<br><small class="text-muted">oleh ${row.creator.name}</small>` : ''}`
                    },
                    {
                        data: 'singkatan',
                        orderable: false,
                        render: (v) => v ? `<code class="small">${v}</code>` :
                            '<span class="text-muted">-</span>'
                    },
                    {
                        data: 'deskripsi',
                        orderable: false,
                        render: (v) => v ?
                            `<span class="text-muted small">${v.length > 60 ? v.substr(0, 60) + '...' : v}</span>` :
                            '<span class="text-muted">-</span>'
                    },
                    {
                        data: 'obats_count',
                        orderable: false,
                        className: 'text-center',
                        render: (v) =>
                            `<span class="badge bg-light text-dark border">${v || 0} obat</span>`
                    },
                    {
                        data: 'is_active',
                        orderable: false,
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

            window.filterByStatus = (status) => {
                $('#filterStatus').val(status).trigger('change');
            };
        });
    </script>
@endpush
