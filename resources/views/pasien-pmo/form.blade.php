@extends('layouts.app')

@section('title', isset($id) ? 'Edit Mapping' : 'Tambah Mapping')

@section('page-header')
    <nav aria-label="breadcrumb" class="small">
        <ol class="breadcrumb mb-2">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('pasien-pmo.index') }}" class="text-decoration-none">Pasien PMO</a>
            </li>
            <li class="breadcrumb-item active">{{ isset($id) ? 'Edit' : 'Tambah' }}</li>
        </ol>
    </nav>
    <div>
        <h4 class="fw-bold mb-1" id="formTitle">{{ isset($id) ? 'Edit Mapping' : 'Tambah Mapping Baru' }}</h4>
        <small class="text-muted">
            @if (!isset($id))
                Pilih 1 PMO dan satu atau lebih pasien untuk membuat mapping sekaligus.
            @endif
        </small>
    </div>
@endsection

@section('content')

    @php
        $isPmoLogin = auth()->user()->isPmo();
        $loggedInUser = auth()->user();
    @endphp

    <form id="pasienPmoForm" novalidate>
        @csrf
        @if (isset($id))
            @method('PUT')
            <input type="hidden" id="mappingId" value="{{ $id }}">
        @endif

        <div class="row g-4">
            <div class="col-lg-8">

                {{-- ============ PMO (single select) ============ --}}
                <x-card title="Pilih PMO" icon="ri-shield-user-line">
                    <div class="mb-0">
                        <label for="pmo_user_id" class="form-label form-label-required">PMO (Pendamping Minum Obat)</label>

                        @if ($isPmoLogin && !isset($id))
                            {{-- Login sebagai PMO: auto-fill, locked --}}
                            <input type="text" class="form-control bg-light" value="{{ $loggedInUser->name }}" readonly
                                disabled>
                            <input type="hidden" name="pmo_user_id" id="pmo_user_id" value="{{ $loggedInUser->id }}">
                            <small class="text-info">
                                <i class="ri ri-information-line"></i>
                                Anda login sebagai PMO. Mapping akan dibuat untuk akun Anda.
                            </small>
                        @else
                            {{-- Admin/Superadmin: select dropdown --}}
                            <select id="pmo_user_id" name="pmo_user_id" class="form-select" required>
                                <option value="">-- Pilih PMO --</option>
                                {{-- Filled by JS --}}
                            </select>
                            <div class="invalid-feedback"></div>
                            <small class="text-muted">Pilih PMO yang akan dimapping ke pasien.</small>
                        @endif
                    </div>
                </x-card>

                <div class="mt-3"></div>

                {{-- ============ PASIEN (multi/single select tergantung mode) ============ --}}
                <x-card title="{{ isset($id) ? 'Pilih Pasien' : 'Pilih Pasien (boleh lebih dari 1)' }}"
                    icon="ri-user-heart-line">

                    @if (!isset($id))
                        {{-- ============ CREATE MODE: tabel preview multi-pasien ============ --}}
                        <div class="mb-3">
                            <label class="form-label form-label-required">Cari & pilih pasien</label>
                            <div class="position-relative">
                                <input type="text" id="pasienSearchInput" class="form-control"
                                    placeholder="Ketik nama pasien atau NIK..." autocomplete="off">
                                <div id="pasienSearchDropdown" class="dropdown-menu w-100"
                                    style="max-height: 300px; overflow-y: auto; display: none;">
                                    {{-- Filled by JS --}}
                                </div>
                            </div>
                            <small class="text-muted">Hanya pasien yang belum punya mapping aktif yang muncul.</small>
                        </div>

                        <div class="mb-0">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label fw-semibold mb-0">
                                    <i class="ri ri-list-check me-1"></i>
                                    Pasien terpilih (<span id="selectedCount">0</span>)
                                </label>
                                <small class="text-muted">Set Status Diabetes per pasien</small>
                            </div>

                            <div id="selectedPasienTableWrapper">
                                <div class="text-muted small text-center py-4 border rounded" id="emptyTablePlaceholder">
                                    <i class="ri ri-user-add-line fs-3 d-block mb-2"></i>
                                    Belum ada pasien terpilih. Cari & klik pasien di atas.
                                </div>

                                <div class="table-responsive" id="selectedPasienTable" style="display:none;">
                                    <table class="table table-hover table-bordered mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 5%" class="text-center">#</th>
                                                <th>Nama Pasien</th>
                                                <th style="width: 22%">NIK</th>
                                                <th style="width: 22%">Status Diabetes <span class="text-danger">*</span>
                                                </th>
                                                <th style="width: 7%" class="text-center">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody id="selectedPasienTableBody">
                                            {{-- Filled by JS --}}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="invalid-feedback d-block" id="pasien_ids_error"></div>
                        </div>
                    @else
                        {{-- ============ EDIT MODE: single select ============ --}}
                        <div class="mb-3">
                            <label for="id_user" class="form-label form-label-required">Pasien</label>
                            <select id="id_user" name="id_user" class="form-select" required>
                                <option value="">-- Pilih Pasien --</option>
                                {{-- Filled by JS --}}
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="mb-0">
                            <label class="form-label">NIK Pasien</label>
                            <input type="text" id="nikDisplay" class="form-control bg-light" readonly>
                            <small class="text-muted">NIK akan otomatis terisi dari data pasien.</small>
                        </div>
                    @endif
                </x-card>
            </div>

            {{-- ============ KOLOM KANAN: Detail Mapping ============ --}}
            <div class="col-lg-4">
                <x-card title="Detail Mapping" icon="ri-settings-3-line">
                    <div class="mb-3">
                        <label for="jenis_pmo" class="form-label form-label-required">Jenis PMO</label>
                        <select id="jenis_pmo" name="jenis_pmo" class="form-select" required>
                            <option value="">-- Pilih --</option>
                            <option value="Keluarga">Keluarga</option>
                            <option value="Kader">Kader</option>
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>

                    @if (isset($id))
                        {{-- EDIT MODE: tetap ada Status Diabetes di card kanan --}}
                        <div class="mb-3">
                            <label for="status_diabetes" class="form-label form-label-required">Status Diabetes</label>
                            <select id="status_diabetes" name="status_diabetes" class="form-select" required>
                                <option value="">-- Pilih --</option>
                                <option value="Rendah">Rendah</option>
                                <option value="Sedang">Sedang</option>
                                <option value="Tinggi">Tinggi</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                    @endif
                    {{-- CREATE MODE: Status Diabetes per pasien ada di tabel preview --}}

                    <div class="mb-3">
                        <label for="tanggal_regis" class="form-label form-label-required">Tanggal Registrasi</label>
                        <input type="date" id="tanggal_regis" name="tanggal_regis" class="form-control"
                            max="{{ date('Y-m-d') }}" value="{{ date('Y-m-d') }}" required>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="mb-0">
                        <label for="catatan" class="form-label">Catatan (opsional)</label>
                        <textarea id="catatan" name="catatan" class="form-control" rows="3" maxlength="1000"
                            placeholder="Catatan tambahan..."></textarea>
                        <div class="invalid-feedback"></div>
                    </div>
                </x-card>

                @if (isset($id))
                    <div class="mt-3">
                        <x-card title="Status" icon="ri-toggle-line">
                            <div class="mb-0">
                                <label class="form-label">Status Mapping</label>
                                <div class="form-check form-switch">
                                    <input type="hidden" name="is_active" value="0">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active"
                                        value="1" checked>
                                    <label class="form-check-label" for="is_active">
                                        <span id="statusLabel">Aktif</span>
                                    </label>
                                </div>
                                <small class="text-muted">Mapping nonaktif tetap tersimpan sebagai history.</small>
                            </div>
                        </x-card>
                    </div>
                @endif

                @if (!isset($id))
                    <div class="mt-3">
                        <x-card class="border-start border-4 border-info">
                            <h6 class="fw-bold mb-2 small">
                                <i class="ri ri-information-line text-info me-1"></i> Info
                            </h6>
                            <p class="small text-muted mb-0">
                                Form ini akan membuat <strong>1 mapping per pasien</strong> dengan PMO yang sama.
                                Detail (Jenis PMO, Status Diabetes, Tanggal) akan berlaku untuk semua pasien terpilih.
                            </p>
                        </x-card>
                    </div>
                @endif
            </div>
        </div>

        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-primary fw-semibold px-4" id="submitBtn">
                <span class="spinner-border spinner-border-sm d-none me-2"></span>
                <i class="ri ri-save-line me-1"></i> Simpan
            </button>
            <a href="{{ route('pasien-pmo.index') }}" class="btn btn-outline-secondary">Batal</a>
        </div>
    </form>

@endsection

@push('scripts')
    <script>
        window.whenKesehatanReady(function() {
            'use strict';

            const CONFIG = {
                IS_EDIT: {{ isset($id) ? 'true' : 'false' }},
                IS_PMO_LOGIN: {{ $isPmoLogin ? 'true' : 'false' }},
                ID: '{{ $id ?? '' }}',
                ROUTES: {
                    INDEX: '{{ route('pasien-pmo.index') }}',
                    STORE: '{{ route('pasien-pmo.store') }}',
                    UPDATE: '{{ isset($id) ? route('pasien-pmo.update', $id) : '' }}',
                    SHOW_DATA: '{{ isset($id) ? route('pasien-pmo.show-data', $id) : '' }}',
                    PMO_OPTIONS: '{{ route('pasien-pmo.options.pmo') }}',
                    PASIEN_OPTS: '{{ route('pasien-pmo.options.pasien') }}',
                },
            };

            const $form = $('#pasienPmoForm');
            const $submitBtn = $('#submitBtn');

            let allPasiens = [];
            let selectedPasiens = []; // Array of { id, name, nik, whatsapp_number, status_diabetes }

            init();

            async function init() {
                if (!CONFIG.IS_PMO_LOGIN) await loadPmoOptions();
                await loadPasienOptions();
                if (CONFIG.IS_EDIT) await loadExistingData();
                setupEvents();
            }

            async function loadPmoOptions() {
                try {
                    const res = await $.ajax({
                        url: CONFIG.ROUTES.PMO_OPTIONS,
                        method: 'GET'
                    });
                    const $select = $('#pmo_user_id');
                    res.data.forEach(p => {
                        const wa = p.whatsapp_number ? ' (' + p.whatsapp_number + ')' : '';
                        $select.append(`<option value="${p.id}">${p.name}${wa}</option>`);
                    });
                } catch (e) {
                    console.error('Load PMO options failed:', e);
                }
            }

            async function loadPasienOptions() {
                try {
                    const url = CONFIG.IS_EDIT ?
                        CONFIG.ROUTES.PASIEN_OPTS + '?exclude_mapping_id=' + CONFIG.ID :
                        CONFIG.ROUTES.PASIEN_OPTS;
                    const res = await $.ajax({
                        url,
                        method: 'GET'
                    });
                    allPasiens = res.data;

                    if (CONFIG.IS_EDIT) {
                        const $select = $('#id_user');
                        allPasiens.forEach(p => {
                            const nik = p.nik ? ' - NIK: ' + p.nik : '';
                            $select.append(
                                `<option value="${p.id}" data-nik="${p.nik || ''}">${p.name}${nik}</option>`
                                );
                        });
                    }
                } catch (e) {
                    console.error('Load pasien options failed:', e);
                }
            }

            async function loadExistingData() {
                try {
                    const data = await $.ajax({
                        url: CONFIG.ROUTES.SHOW_DATA,
                        method: 'GET'
                    });

                    $('#formTitle').text('Edit: ' + data.nama_pasien);

                    if (!CONFIG.IS_PMO_LOGIN) {
                        if ($('#pmo_user_id option[value="' + data.pmo_user_id + '"]').length === 0 && data
                            .pmo) {
                            $('#pmo_user_id').append(
                                `<option value="${data.pmo_user_id}">${data.nama_pmo} (existing)</option>`);
                        }
                        $('#pmo_user_id').val(data.pmo_user_id);
                    }

                    if ($('#id_user option[value="' + data.id_user + '"]').length === 0) {
                        $('#id_user').append(
                            `<option value="${data.id_user}" data-nik="${data.nik}">${data.nama_pasien} - NIK: ${data.nik}</option>`
                            );
                    }
                    $('#id_user').val(data.id_user);
                    $('#nikDisplay').val(data.nik);

                    $('#jenis_pmo').val(data.jenis_pmo);
                    $('#status_diabetes').val(data.status_diabetes);
                    $('#tanggal_regis').val(data.tanggal_regis ? data.tanggal_regis.substr(0, 10) : '');
                    $('#catatan').val(data.catatan || '');
                    $('#is_active').prop('checked', !!data.is_active).trigger('change');
                } catch (e) {
                    Swal.fire({
                        title: 'Error!',
                        text: 'Gagal memuat data mapping.',
                        icon: 'error',
                    }).then(() => window.location.href = CONFIG.ROUTES.INDEX);
                }
            }

            function setupEvents() {
                $('#is_active').on('change', function() {
                    $('#statusLabel').text(this.checked ? 'Aktif' : 'Nonaktif');
                });

                if (CONFIG.IS_EDIT) {
                    $('#id_user').on('change', function() {
                        const nik = $(this).find('option:selected').data('nik') || '';
                        $('#nikDisplay').val(nik);
                    });
                } else {
                    setupPasienSearch();
                }

                $form.on('submit', submitForm);
            }

            // ============ PASIEN SEARCH (create mode) ============
            function setupPasienSearch() {
                const $input = $('#pasienSearchInput');
                const $dropdown = $('#pasienSearchDropdown');

                $input.on('focus', () => {
                    renderDropdown($input.val());
                    $dropdown.show();
                });

                $input.on('input', () => {
                    renderDropdown($input.val());
                    $dropdown.show();
                });

                $(document).on('click', function(e) {
                    if (!$(e.target).closest('#pasienSearchInput, #pasienSearchDropdown').length) {
                        $dropdown.hide();
                    }
                });

                // Pilih pasien dari dropdown
                $dropdown.on('click', '.pasien-option', function(e) {
                    e.preventDefault();
                    const id = $(this).data('id');
                    const pasien = allPasiens.find(p => p.id === id);
                    if (pasien && !selectedPasiens.find(p => p.id === id)) {
                        // Add ke selected dengan status_diabetes default empty (harus diset user)
                        selectedPasiens.push({
                            ...pasien,
                            status_diabetes: '',
                        });
                        renderTable();
                        $input.val('').focus();
                        renderDropdown('');
                    }
                });

                // Remove row dari tabel (delegated)
                $('#selectedPasienTableBody').on('click', '.btn-remove-row', function(e) {
                    e.preventDefault();
                    const id = $(this).data('id');
                    selectedPasiens = selectedPasiens.filter(p => p.id !== id);
                    renderTable();
                });

                // Change status_diabetes inline (delegated)
                $('#selectedPasienTableBody').on('change', '.select-status-row', function() {
                    const id = $(this).data('id');
                    const value = $(this).val();
                    const pasien = selectedPasiens.find(p => p.id === id);
                    if (pasien) {
                        pasien.status_diabetes = value;
                        // Visual feedback
                        $(this).removeClass('is-invalid');
                    }
                });
            }

            function renderDropdown(searchTerm) {
                const $dropdown = $('#pasienSearchDropdown');
                const term = (searchTerm || '').toLowerCase().trim();

                const available = allPasiens.filter(p => {
                    if (selectedPasiens.find(s => s.id === p.id)) return false;
                    if (!term) return true;
                    const haystack = (p.name + ' ' + (p.nik || '') + ' ' + (p.whatsapp_number || ''))
                        .toLowerCase();
                    return haystack.includes(term);
                });

                if (available.length === 0) {
                    $dropdown.html(
                    '<div class="dropdown-item text-muted small">Tidak ada pasien yang cocok.</div>');
                    return;
                }

                const html = available.map(p => {
                    const nik = p.nik ?
                        `<br><small class="text-muted"><code style="font-size:0.7rem;">NIK: ${p.nik}</code></small>` :
                        '';
                    const wa = p.whatsapp_number ?
                        `<small class="text-muted ms-2"><i class="ri ri-whatsapp-line"></i> ${p.whatsapp_number}</small>` :
                        '';
                    return `
                <a href="#" class="dropdown-item pasien-option" data-id="${p.id}">
                    <strong>${p.name}</strong>${wa}${nik}
                </a>
            `;
                }).join('');

                $dropdown.html(html);
            }

            function renderTable() {
                const $count = $('#selectedCount');
                const $placeholder = $('#emptyTablePlaceholder');
                const $tableWrap = $('#selectedPasienTable');
                const $tbody = $('#selectedPasienTableBody');

                $count.text(selectedPasiens.length);

                if (selectedPasiens.length === 0) {
                    $placeholder.show();
                    $tableWrap.hide();
                    $tbody.empty();
                    return;
                }

                $placeholder.hide();
                $tableWrap.show();

                const rows = selectedPasiens.map((p, idx) => {
                    const nik = p.nik ? `<code class="small">${p.nik}</code>` :
                        '<span class="text-muted">-</span>';
                    const opts = ['', 'Rendah', 'Sedang', 'Tinggi'].map(v => {
                        const label = v || '-- Pilih --';
                        const selected = p.status_diabetes === v ? 'selected' : '';
                        return `<option value="${v}" ${selected}>${label}</option>`;
                    }).join('');

                    return `
                <tr data-id="${p.id}">
                    <td class="text-center fw-bold">${idx + 1}</td>
                    <td><strong>${p.name}</strong></td>
                    <td>${nik}</td>
                    <td>
                        <select class="form-select form-select-sm select-status-row" data-id="${p.id}">
                            ${opts}
                        </select>
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row" data-id="${p.id}" title="Hapus">
                            <i class="ri ri-close-line"></i>
                        </button>
                    </td>
                </tr>
            `;
                }).join('');

                $tbody.html(rows);
            }

            // ============ SUBMIT ============
            async function submitForm(e) {
                e.preventDefault();

                $form.find('.is-invalid').removeClass('is-invalid');
                $form.find('.invalid-feedback').text('');
                $('#pasien_ids_error').text('');

                $submitBtn.prop('disabled', true).find('.spinner-border').removeClass('d-none');

                const data = {
                    pmo_user_id: $('#pmo_user_id').val(),
                    jenis_pmo: $('#jenis_pmo').val(),
                    tanggal_regis: $('#tanggal_regis').val(),
                    catatan: $('#catatan').val(),
                };

                if (CONFIG.IS_EDIT) {
                    data._method = 'PUT';
                    data.id_user = $('#id_user').val();
                    data.status_diabetes = $('#status_diabetes').val();
                    data.is_active = $('#is_active').is(':checked') ? 1 : 0;
                } else {
                    // Validate selected pasiens
                    if (selectedPasiens.length === 0) {
                        $('#pasien_ids_error').text('Minimal pilih 1 pasien.');
                        $submitBtn.prop('disabled', false).find('.spinner-border').addClass('d-none');
                        Swal.fire('Validasi Gagal', 'Pilih minimal 1 pasien.', 'warning');
                        return;
                    }

                    // Validate status_diabetes terisi semua
                    let hasEmptyStatus = false;
                    selectedPasiens.forEach(p => {
                        if (!p.status_diabetes || p.status_diabetes === '') {
                            hasEmptyStatus = true;
                            $(`.select-status-row[data-id="${p.id}"]`).addClass('is-invalid');
                        }
                    });

                    if (hasEmptyStatus) {
                        $('#pasien_ids_error').text('Status Diabetes wajib diisi untuk setiap pasien.');
                        $submitBtn.prop('disabled', false).find('.spinner-border').addClass('d-none');
                        Swal.fire('Validasi Gagal', 'Status Diabetes wajib diisi untuk setiap pasien.',
                            'warning');
                        return;
                    }

                    data.pasiens = selectedPasiens.map(p => ({
                        pasien_id: p.id,
                        status_diabetes: p.status_diabetes,
                    }));
                }

                const url = CONFIG.IS_EDIT ? CONFIG.ROUTES.UPDATE : CONFIG.ROUTES.STORE;
                const csrfToken = $('input[name=_token]').val() || $('meta[name=csrf-token]').attr('content');

                try {
                    // Pakai JSON untuk preserve type (boolean, nested array) - fix bug "must be array"
                    const res = await $.ajax({
                        url: url,
                        method: 'POST',
                        contentType: 'application/json',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken
                        },
                        data: JSON.stringify(data),
                    });

                    Swal.fire({
                        title: 'Berhasil!',
                        text: res.message,
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false,
                    }).then(() => {
                        window.location.href = CONFIG.ROUTES.INDEX;
                    });
                } catch (xhr) {
                    $submitBtn.prop('disabled', false).find('.spinner-border').addClass('d-none');

                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        Object.entries(xhr.responseJSON.errors).forEach(function([field, messages]) {
                            // Handle pasien-related errors
                            if (field === 'pasiens' || field.startsWith('pasiens.')) {
                                $('#pasien_ids_error').text(messages[0]);

                                // Cek apakah error spesifik per row
                                const match = field.match(/pasiens\.(\d+)\.(\w+)/);
                                if (match) {
                                    const idx = parseInt(match[1]);
                                    const subField = match[2];
                                    const pasien = selectedPasiens[idx];
                                    if (pasien && subField === 'status_diabetes') {
                                        $(`.select-status-row[data-id="${pasien.id}"]`).addClass(
                                            'is-invalid');
                                    }
                                }
                                return;
                            }

                            const $field = $('[name="' + field + '"]');
                            $field.addClass('is-invalid');
                            $field.siblings('.invalid-feedback').text(messages[0]);
                        });
                        Swal.fire('Validasi Gagal', 'Mohon periksa kembali data yang Anda masukkan.',
                        'warning');
                    } else {
                        Swal.fire('Gagal!', xhr.responseJSON?.message || 'Terjadi kesalahan', 'error');
                    }
                }
            }
        });
    </script>

    <style>
        #pasienSearchDropdown {
            position: absolute;
            top: 100%;
            left: 0;
            z-index: 1050;
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-top: 2px;
        }

        #pasienSearchDropdown .dropdown-item {
            padding: 8px 16px;
            border-bottom: 1px solid #f1f3f5;
        }

        #pasienSearchDropdown .dropdown-item:last-child {
            border-bottom: none;
        }

        #pasienSearchDropdown .dropdown-item:hover {
            background-color: #e7f1ff;
        }

        #selectedPasienTable .select-status-row.is-invalid {
            border-color: #dc3545;
        }

        #emptyTablePlaceholder {
            background-color: #f8f9fa;
        }
    </style>
@endpush
