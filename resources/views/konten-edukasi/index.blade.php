@extends('layouts.app')

@section('title', 'Kelola Edukasi')

@section('page-header')
    <nav aria-label="breadcrumb" class="small">
        <ol class="breadcrumb mb-2">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item active">Edukasi</li>
        </ol>
    </nav>
    <div>
        <h4 class="fw-bold mb-1"><i class="ri ri-book-line text-primary me-1"></i> Edukasi</h4>
        <small class="text-muted">Kelola artikel edukasi yang tampil di halaman publik.</small>
    </div>
@endsection

@section('content')

@php
    $stats = \App\Services\EdukasiService::getStats();
@endphp

<div class="row g-3 mb-4">
    <div class="col-6 col-md-4">
        <a href="javascript:void(0)" onclick="filterByStatus('')" class="text-decoration-none">
            <x-card class="card-hover h-100">
                <div class="text-muted small"><i class="ri ri-list-check"></i> Total Artikel</div>
                <div class="display-stat fs-2 text-primary">{{ $stats['total'] }}</div>
            </x-card>
        </a>
    </div>
    <div class="col-6 col-md-4">
        <a href="javascript:void(0)" onclick="filterByStatus('1')" class="text-decoration-none">
            <x-card class="card-hover h-100">
                <div class="text-muted small"><i class="ri ri-global-line text-success"></i> Dipublikasi</div>
                <div class="display-stat fs-2 text-success">{{ $stats['published'] }}</div>
            </x-card>
        </a>
    </div>
    <div class="col-6 col-md-4">
        <a href="javascript:void(0)" onclick="filterByStatus('0')" class="text-decoration-none">
            <x-card class="card-hover h-100">
                <div class="text-muted small"><i class="ri ri-draft-line text-secondary"></i> Draft</div>
                <div class="display-stat fs-2 text-secondary">{{ $stats['draft'] }}</div>
            </x-card>
        </a>
    </div>
</div>

<x-card title="Daftar Artikel Edukasi" icon="ri-book-line">
    <x-slot:headerActions>
        @can('konten-edukasi.create')
            <a href="{{ route('konten-edukasi.create') }}" class="btn btn-primary btn-sm">
                <i class="ri ri-add-line me-1"></i> Tambah Artikel
            </a>
        @endcan
    </x-slot:headerActions>

    <div class="row g-2 mb-3">
        <div class="col-md-8">
            <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Cari judul, kategori, atau isi...">
        </div>
        <div class="col-md-4">
            <select id="filterStatus" class="form-select form-select-sm">
                <option value="">Semua Status</option>
                <option value="1">Dipublikasi</option>
                <option value="0">Draft</option>
            </select>
        </div>
    </div>

    <div class="table-responsive">
        <table id="datatable" class="table table-hover w-100">
            <thead>
                <tr>
                    <th class="text-center" style="width: 5%">No</th>
                    <th>Judul</th>
                    <th class="text-center" style="width: 16%">Kategori</th>
                    <th class="text-center" style="width: 12%">Status</th>
                    <th class="text-center" style="width: 14%">Tanggal</th>
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
            DATA:    '{{ route('konten-edukasi.data') }}',
            SHOW:    '{{ route('konten-edukasi.show', ':id') }}',
            EDIT:    '{{ route('konten-edukasi.edit', ':id') }}',
            DESTROY: '{{ route('konten-edukasi.destroy', ':id') }}',
        },
        STORAGE_KEY: 'konten-edukasi',
    };

    const STATUS_BADGES = {
        true:  '<span class="badge bg-success-subtle text-success"><i class="ri ri-global-line"></i> Publik</span>',
        false: '<span class="badge bg-secondary-subtle text-secondary"><i class="ri ri-draft-line"></i> Draft</span>',
    };

    const formatDate = (iso) => {
        if (!iso) return '<span class="text-muted">-</span>';
        const d = new Date(iso);
        return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
    };

    const renderActions = (row) => {
        const id = row.id;
        const actions = [];
        actions.push(`<a href="${CONFIG.ROUTES.SHOW.replace(':id', id)}" class="btn btn-sm btn-outline-info"><i class="ri ri-eye-line"></i></a>`);
        @can('konten-edukasi.edit')
        actions.push(`<a href="${CONFIG.ROUTES.EDIT.replace(':id', id)}" class="btn btn-sm btn-outline-warning"><i class="ri ri-pencil-line"></i></a>`);
        @endcan
        @can('konten-edukasi.delete')
        actions.push(`<button class="btn btn-sm btn-outline-danger btn-delete-row" data-id="${id}" data-name="${row.judul}"><i class="ri ri-delete-bin-line"></i></button>`);
        @endcan
        return `<div class="row-actions">${actions.join('')}</div>`;
    };

    new DataGrid({
        selector: '#datatable',
        ajaxUrl: CONFIG.ROUTES.DATA,
        storageKey: CONFIG.STORAGE_KEY,
        order: [[0, 'asc']],
        filters: {
            search: '#searchInput',
            is_published: '#filterStatus',
        },
        columns: [
            { data: null, orderable: false, className: 'text-center',
              render: (d, t, r, meta) => meta.row + meta.settings._iDisplayStart + 1 },
            { data: 'judul', name: 'judul',
              render: (v, t, row) => `<strong>${v}</strong>${row.creator ? `<br><small class="text-muted">oleh ${row.creator.name}</small>` : ''}` },
            { data: 'kategori', orderable: false, className: 'text-center',
              render: (v) => v ? `<span class="badge bg-info-subtle text-info">${v}</span>` : '<span class="text-muted">-</span>' },
            { data: 'is_published', orderable: false, className: 'text-center',
              render: (v) => STATUS_BADGES[v ? 'true' : 'false'] },
            { data: 'published_at', orderable: false, className: 'text-center',
              render: (v, t, row) => formatDate(v || row.created_at) },
            { data: null, orderable: false, className: 'text-center',
              render: (d, t, row) => renderActions(row) },
        ],
        onDelete: async (id) => {
            const url = CONFIG.ROUTES.DESTROY.replace(':id', id);
            return $.ajax({ url, method: 'DELETE' });
        },
    });

    window.filterByStatus = (status) => {
        $('#filterStatus').val(status).trigger('change');
    };
});
</script>
@endpush
