@extends('layouts.app')

@section('title', 'Pengingat Cek Gula Darah')

@section('page-header')
    <nav aria-label="breadcrumb" class="small">
        <ol class="breadcrumb mb-2">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item active">Pengingat Cek Gula Darah</li>
        </ol>
    </nav>
    <div>
        <h4 class="fw-bold mb-1"><i class="ri ri-test-tube-line text-info me-1"></i> Pengingat Cek Gula Darah</h4>
        <small class="text-muted">
            @can('pengingat-cgd.create')
                Catat hasil pemeriksaan gula darah dengan foto bukti.
            @else
                Pantau hasil pemeriksaan gula darah pasien.
            @endcan
        </small>
    </div>
@endsection

@section('content')

    @php
        $stats = \App\Services\PengingatCgdLogService::getStats();
    @endphp

    {{-- ============ STATS CARDS (4 KATEGORI) ============ --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <a href="javascript:void(0)" onclick="filterByKategori('normal')" class="text-decoration-none">
                <x-card class="card-hover h-100">
                    <div class="text-muted small"><i class="ri ri-check-double-line text-success"></i> Normal</div>
                    <div class="display-stat fs-2 text-success">{{ $stats['normal'] }}</div>
                    <small class="text-muted">≤140 mg/dL</small>
                </x-card>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="javascript:void(0)" onclick="filterByKategori('tidak_terkontrol')" class="text-decoration-none">
                <x-card class="card-hover h-100">
                    <div class="text-muted small"><i class="ri ri-alert-line text-warning"></i> Tidak Terkontrol</div>
                    <div class="display-stat fs-2 text-warning">{{ $stats['tidak_terkontrol'] }}</div>
                    <small class="text-muted">141-199 mg/dL</small>
                </x-card>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="javascript:void(0)" onclick="filterByKategori('tinggi')" class="text-decoration-none">
                <x-card class="card-hover h-100">
                    <div class="text-muted small"><i class="ri ri-alarm-warning-line text-danger"></i> Tinggi</div>
                    <div class="display-stat fs-2 text-danger">{{ $stats['tinggi'] }}</div>
                    <small class="text-muted">200-299 mg/dL</small>
                </x-card>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="javascript:void(0)" onclick="filterByKategori('berbahaya')" class="text-decoration-none">
                <x-card class="card-hover h-100">
                    <div class="text-muted small"><i class="ri ri-error-warning-fill text-dark"></i> Berbahaya</div>
                    <div class="display-stat fs-2 text-dark">{{ $stats['berbahaya'] }}</div>
                    <small class="text-muted">≥300 mg/dL</small>
                </x-card>
            </a>
        </div>
    </div>

    {{-- ============ DATA TABLE ============ --}}
    <x-card title="Riwayat Hasil CGD" icon="ri-history-line">
        <x-slot:headerActions>
            @can('pengingat-cgd.create')
                <a href="{{ route('pengingat-cgd.create') }}" class="btn btn-primary btn-sm">
                    <i class="ri ri-add-line me-1"></i> Input Hasil CGD
                </a>
            @endcan
        </x-slot:headerActions>

        <div class="row g-2 mb-3">
            <div class="col-md-4">
                <input type="text" id="searchInput" class="form-control form-control-sm"
                    placeholder="Cari nama pasien atau tempat...">
            </div>
            <div class="col-md-2">
                <input type="date" id="filterStart" class="form-control form-control-sm">
            </div>
            <div class="col-md-2">
                <input type="date" id="filterEnd" class="form-control form-control-sm">
            </div>
            <div class="col-md-2">
                <select id="filterKategori" class="form-select form-select-sm">
                    <option value="">Semua Kategori</option>
                    <option value="normal">Normal</option>
                    <option value="tidak_terkontrol">Tidak Terkontrol</option>
                    <option value="tinggi">Tinggi</option>
                    <option value="berbahaya">Berbahaya</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="button" id="btnResetFilter" class="btn btn-outline-secondary btn-sm w-100">
                    <i class="ri ri-refresh-line me-1"></i> Reset
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table id="datatable" class="table table-hover w-100">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 4%">No</th>
                        <th style="width: 7%" class="text-center">Foto</th>
                        <th>Pasien</th>
                        <th class="text-center" style="width: 9%">Tgl CGD</th>
                        <th class="text-center" style="width: 7%">Jam</th>
                        <th class="text-center" style="width: 9%">Hasil</th>
                        <th class="text-center" style="width: 13%">Kategori</th>
                        <th>Tempat</th>
                        <th class="text-center" style="width: 13%">Aksi</th>
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
                    DATA: '{{ route('pengingat-cgd.data') }}',
                    SHOW: '{{ route('pengingat-cgd.show', ':id') }}',
                    EDIT: '{{ route('pengingat-cgd.edit', ':id') }}',
                    DESTROY: '{{ route('pengingat-cgd.destroy', ':id') }}',
                },
                STORAGE_KEY: 'pengingat-cgd',
            };

            const KATEGORI_BADGES = {
                'normal': '<span class="badge bg-success-subtle text-success"><i class="ri ri-check-double-line"></i> Normal</span>',
                'tidak_terkontrol': '<span class="badge bg-warning-subtle text-warning"><i class="ri ri-alert-line"></i> Tidak Terkontrol</span>',
                'tinggi': '<span class="badge bg-danger-subtle text-danger"><i class="ri ri-alarm-warning-line"></i> Tinggi</span>',
                'berbahaya': '<span class="badge bg-dark text-white"><i class="ri ri-error-warning-fill"></i> Berbahaya</span>',
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

            const formatJam = (time) => time ? time.substring(0, 5) : '-';

            const renderActions = (row) => {
                const id = row.id;
                const actions = [];

                actions.push(
                    `<a href="${CONFIG.ROUTES.SHOW.replace(':id', id)}" class="btn btn-sm btn-outline-info" title="Detail"><i class="ri ri-eye-line"></i></a>`
                    );

                @can('pengingat-cgd.edit')
                    actions.push(
                        `<a href="${CONFIG.ROUTES.EDIT.replace(':id', id)}" class="btn btn-sm btn-outline-warning" title="Edit"><i class="ri ri-pencil-line"></i></a>`
                        );
                @endcan

                @can('pengingat-cgd.delete')
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
                    tgl_start: '#filterStart',
                    tgl_end: '#filterEnd',
                    kategori_hasil: '#filterKategori',
                },
                columns: [{
                        data: null,
                        orderable: false,
                        className: 'text-center',
                        render: (d, t, r, meta) => meta.row + meta.settings._iDisplayStart + 1
                    },
                    {
                        data: 'foto_layar',
                        orderable: false,
                        className: 'text-center',
                        render: (v) => {
                            if (!v) return '<span class="text-muted">-</span>';
                            const url = '/storage/' + v;
                            return `<a href="${url}" data-img-zoom><img src="${url}" alt="foto" style="width:40px;height:40px;object-fit:cover;border-radius:4px;cursor:pointer;border:1px solid #dee2e6;"></a>`;
                        }
                    },
                    {
                        data: 'nama_pasien',
                        render: (v, t, row) => {
                            const gender = row.jenis_kelamin === 'L' ? '♂ L' : (row
                                .jenis_kelamin === 'P' ? '♀ P' : '-');
                            return `<strong>${v || '-'}</strong><br><small class="text-muted">${gender}</small>`;
                        }
                    },
                    {
                        data: 'tgl_cgd',
                        className: 'text-center',
                        render: (v) => formatDate(v)
                    },
                    {
                        data: 'jam_cgd',
                        className: 'text-center',
                        render: (v) => `<span class="badge bg-light text-dark">${formatJam(v)}</span>`
                    },
                    {
                        data: 'hasil_mgdl',
                        className: 'text-center',
                        render: (v, t, row) => {
                            const selisih = row.patuh_selisih;
                            const selisihStr = selisih > 0 ? `+${selisih}` : selisih;
                            const selisihColor = selisih > 0 ? 'text-danger' : (selisih < 0 ?
                                'text-success' : 'text-muted');
                            return `<strong class="fs-5">${v}</strong> <small class="text-muted">mg/dL</small><br><small class="${selisihColor}">${selisihStr}</small>`;
                        }
                    },
                    {
                        data: 'kategori_hasil',
                        className: 'text-center',
                        render: (v) => KATEGORI_BADGES[v] || v
                    },
                    {
                        data: 'tempat_cgd',
                        render: (v) => v ? `<i class="ri ri-map-pin-line text-danger"></i> ${v}` :
                            '<span class="text-muted">-</span>'
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
            window.filterByKategori = (kat) => $('#filterKategori').val(kat).trigger('change');
            window.filterReset = () => {
                $('#searchInput').val('');
                $('#filterStart').val('');
                $('#filterEnd').val('');
                $('#filterKategori').val('').trigger('change');
            };

            $('#btnResetFilter').on('click', filterReset);

            // Image zoom
            $(document).on('click', '[data-img-zoom]', function(e) {
                e.preventDefault();
                const url = $(this).attr('href') || $(this).attr('data-img-zoom');
                Swal.fire({
                    imageUrl: url,
                    imageAlt: 'Foto hasil CGD',
                    showConfirmButton: false,
                    showCloseButton: true,
                    width: '85%',
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
            max-height: 85vh !important;
            max-width: 100% !important;
            object-fit: contain;
        }
    </style>
@endpush
