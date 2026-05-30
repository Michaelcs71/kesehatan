@extends('layouts.app')

@section('title', 'Master Obat')

@section('page-header')
    <nav aria-label="breadcrumb" class="small">
        <ol class="breadcrumb mb-2">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item active">Master Obat</li>
        </ol>
    </nav>
    <div>
        <h4 class="fw-bold mb-1"><i class="ri ri-medicine-bottle-line text-primary me-1"></i> Master Obat</h4>
        <small class="text-muted">
            @if (auth()->user()->isAdmin())
                Kelola seluruh master obat di sistem.
            @else
                Daftar obat tersedia.
            @endif
        </small>
    </div>
@endsection

@section('content')

    @php
        $stats = auth()->user()->isAdmin() ? \App\Services\MasterObatService::getStats() : null;
        $kategoriOptions = \App\Services\MasterKategoriObatService::getActiveOptions();
    @endphp

    @if ($stats)
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <a href="javascript:void(0)" onclick="filterByStatus('pending')" class="text-decoration-none">
                    <x-card class="card-hover h-100">
                        <div class="text-muted small"><i class="ri ri-time-line"></i> Menunggu Verifikasi</div>
                        <div class="display-stat fs-2 text-warning">{{ $stats['pending'] }}</div>
                    </x-card>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="javascript:void(0)" onclick="filterByStatus('approved')" class="text-decoration-none">
                    <x-card class="card-hover h-100">
                        <div class="text-muted small"><i class="ri ri-check-line text-success"></i> Disetujui</div>
                        <div class="display-stat fs-2 text-success">{{ $stats['approved'] }}</div>
                    </x-card>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="javascript:void(0)" onclick="filterByStatus('rejected')" class="text-decoration-none">
                    <x-card class="card-hover h-100">
                        <div class="text-muted small"><i class="ri ri-close-line text-danger"></i> Ditolak</div>
                        <div class="display-stat fs-2 text-danger">{{ $stats['rejected'] }}</div>
                    </x-card>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="javascript:void(0)" onclick="filterByStatus('')" class="text-decoration-none">
                    <x-card class="card-hover h-100">
                        <div class="text-muted small"><i class="ri ri-bar-chart-line"></i> Total</div>
                        <div class="display-stat fs-2 text-primary">{{ $stats['total'] }}</div>
                    </x-card>
                </a>
            </div>
        </div>
    @endif

    <x-card title="Daftar Obat" icon="ri-medicine-bottle-line">
        <x-slot:headerActions>
            @can('master-obat.create')
                <a href="{{ route('master-obat.create') }}" class="btn btn-primary btn-sm">
                    <i class="ri ri-add-line me-1"></i> Tambah Obat
                </a>
            @endcan
        </x-slot:headerActions>

        <div class="row g-2 mb-3">
            <div class="col-md-6">
                <input type="text" id="searchInput" class="form-control form-control-sm"
                    placeholder="Cari nama atau dosis...">
            </div>
            <div class="col-md-3">
                <select id="filterStatus" class="form-select form-select-sm">
                    <option value="">Semua Status</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>
            <div class="col-md-3">
                <select id="filterKategori" class="form-select form-select-sm">
                    <option value="">Semua Kategori</option>
                    @foreach ($kategoriOptions as $opt)
                        <option value="{{ $opt['id'] }}">{{ $opt['nama'] }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="table-responsive">
            <table id="datatable" class="table table-hover w-100">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 5%">No</th>
                        <th style="width: 10%">Foto</th>
                        <th>Nama Obat</th>
                        <th>Kategori</th>
                        <th>Dosis</th>
                        <th>Status</th>
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
                    DATA: '{{ route('master-obat.data') }}',
                    SHOW: '{{ route('master-obat.show', ':id') }}',
                    EDIT: '{{ route('master-obat.edit', ':id') }}',
                    DESTROY: '{{ route('master-obat.destroy', ':id') }}',
                },
                STORAGE_KEY: 'master-obat',
            };

            const STATUS_BADGES = {
                pending: '<span class="badge bg-warning-subtle text-warning"><i class="ri ri-time-line"></i> Pending</span>',
                approved: '<span class="badge bg-success-subtle text-success"><i class="ri ri-check-line"></i> Approved</span>',
                rejected: '<span class="badge bg-danger-subtle text-danger"><i class="ri ri-close-line"></i> Rejected</span>',
            };

            const renderFoto = (path) => {
                if (!path) return '<div class="text-muted small">-</div>';
                return `<img src="/storage/${path}" style="width:48px;height:48px;object-fit:cover;border-radius:6px;" />`;
            };

            const renderActions = (row) => {
                const id = row.id;
                const actions = [];
                actions.push(
                    `<a href="${CONFIG.ROUTES.SHOW.replace(':id', id)}" class="btn btn-sm btn-outline-info"><i class="ri ri-eye-line"></i></a>`
                    );
                @can('master-obat.edit')
                    actions.push(
                        `<a href="${CONFIG.ROUTES.EDIT.replace(':id', id)}" class="btn btn-sm btn-outline-warning"><i class="ri ri-pencil-line"></i></a>`
                        );
                @endcan
                @can('master-obat.delete')
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
                    status: '#filterStatus',
                    kategori_id: '#filterKategori',
                },
                columns: [{
                        data: null,
                        orderable: false,
                        className: 'text-center',
                        render: (d, t, r, meta) => meta.row + meta.settings._iDisplayStart + 1
                    },
                    {
                        data: 'foto_path',
                        orderable: false,
                        render: (v) => renderFoto(v)
                    },
                    {
                        data: 'nama',
                        name: 'nama',
                        render: (v, t, row) =>
                            `<strong>${v}</strong>${row.creator ? `<br><small class="text-muted">oleh ${row.creator.name}</small>` : ''}`
                    },
                    {
                        data: null,
                        orderable: false,
                        render: (d, t, row) => row.kategori ?
                            `<span class="badge bg-light text-dark border">${row.kategori.nama}</span>` :
                            '<span class="text-muted">-</span>'
                    },
                    {
                        data: null,
                        render: (d, t, row) => {
                            const satuan = row.satuan?.singkatan || row.satuan?.nama || '';
                            return `${row.dosis_default || '-'} <small class="text-muted">${satuan}</small>`;
                        }
                    },
                    {
                        data: 'status',
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

            window.filterByStatus = (status) => {
                $('#filterStatus').val(status).trigger('change');
            };
        });
    </script>
@endpush
