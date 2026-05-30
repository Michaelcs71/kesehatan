@extends('layouts.app')

@section('title', 'Master User')

@section('page-header')
    <nav aria-label="breadcrumb" class="small">
        <ol class="breadcrumb mb-2">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item active">Master User</li>
        </ol>
    </nav>
    <div>
        <h4 class="fw-bold mb-1"><i class="ri ri-team-line me-2 text-primary"></i> Master User</h4>
        <small class="text-muted">
            @if (auth()->user()->isSuperadmin())
                Kelola seluruh user di sistem.
            @else
                Kelola user pasien, PMO, dan pengunjung.
            @endif
        </small>
    </div>
@endsection

@section('content')

    @php
        $stats = \App\Services\UserService::getStats();
        $isSuper = auth()->user()->isSuperadmin();
    @endphp

    {{-- Stats Cards --}}
    <div class="row g-3 mb-4">
        @if ($isSuper)
            <div class="col-6 col-md-3">
                <a href="javascript:void(0)" onclick="filterByRole('superadmin')" class="text-decoration-none">
                    <x-card class="card-hover h-100">
                        <div class="text-muted small"><i class="ri ri-shield-star-line text-danger"></i> Superadmin</div>
                        <div class="display-stat fs-2 text-danger">{{ $stats['superadmin'] }}</div>
                    </x-card>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="javascript:void(0)" onclick="filterByRole('admin')" class="text-decoration-none">
                    <x-card class="card-hover h-100">
                        <div class="text-muted small"><i class="ri ri-shield-user-line text-warning"></i> Admin</div>
                        <div class="display-stat fs-2 text-warning">{{ $stats['admin'] }}</div>
                    </x-card>
                </a>
            </div>
        @endif
        <div class="col-6 col-md-3">
            <a href="javascript:void(0)" onclick="filterByRole('pmo')" class="text-decoration-none">
                <x-card class="card-hover h-100">
                    <div class="text-muted small"><i class="ri ri-team-line text-info"></i> PMO</div>
                    <div class="display-stat fs-2 text-info">{{ $stats['pmo'] }}</div>
                </x-card>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="javascript:void(0)" onclick="filterByRole('pasien')" class="text-decoration-none">
                <x-card class="card-hover h-100">
                    <div class="text-muted small"><i class="ri ri-user-heart-line text-primary"></i> Pasien</div>
                    <div class="display-stat fs-2 text-primary">{{ $stats['pasien'] }}</div>
                </x-card>
            </a>
        </div>
    </div>

    <x-card title="Daftar User" icon="ri-user-line">
        <x-slot:headerActions>
            @can('master-user.create')
                <button type="button" class="btn btn-outline-success btn-sm me-2" id="btnOpenImportModal">
                    <i class="ri ri-file-excel-2-line me-1"></i> Import Excel
                </button>
                <a href="{{ route('master-user.create') }}" class="btn btn-primary btn-sm">
                    <i class="ri ri-user-add-line me-1"></i> Tambah User
                </a>
            @endcan
        </x-slot:headerActions>

        {{-- Filter --}}
        <div class="row g-2 mb-3">
            <div class="col-md-5">
                <input type="text" id="searchInput" class="form-control form-control-sm"
                    placeholder="Cari nama, username, WhatsApp, atau NIK...">
            </div>
            <div class="col-md-4">
                <select id="filterRole" class="form-select form-select-sm">
                    <option value="">Semua Role</option>
                    @if ($isSuper)
                        <option value="superadmin">Superadmin</option>
                        <option value="admin">Admin</option>
                    @endif
                    <option value="pmo">PMO</option>
                    <option value="pasien">Pasien</option>
                    <option value="pengunjung">Pengunjung</option>
                </select>
            </div>
            <div class="col-md-3">
                <select id="filterStatus" class="form-select form-select-sm">
                    <option value="">Semua Status</option>
                    <option value="1">Aktif</option>
                    <option value="0">Nonaktif</option>
                </select>
            </div>
        </div>

        {{-- DataTable --}}
        <div class="table-responsive">
            <table id="datatable" class="table table-hover w-100">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 5%">No</th>
                        <th style="width: 8%">Avatar</th>
                        <th>Nama & Username</th>
                        <th>WhatsApp</th>
                        <th>Role</th>
                        <th class="text-center">Biodata</th>
                        <th class="text-center">Status</th>
                        <th class="text-center" style="width: 12%">Aksi</th>
                    </tr>
                </thead>
            </table>
        </div>
    </x-card>

    {{-- Modal Import Excel --}}
    @include('master-user._import_modal')
@endsection

@push('scripts')
    <script>
        window.whenKesehatanReady(function() {
            'use strict';

            // ============ EXISTING CONFIG & RENDER LOGIC ============
            const CONFIG = {
                ID_LOGGED: '{{ auth()->id() }}',
                IS_SUPER: {{ auth()->user()->isSuperadmin() ? 'true' : 'false' }},
                ROUTES: {
                    DATA: '{{ route('master-user.data') }}',
                    SHOW: '{{ route('master-user.show', ':id') }}',
                    EDIT: '{{ route('master-user.edit', ':id') }}',
                    DESTROY: '{{ route('master-user.destroy', ':id') }}',
                    // IMPORT routes
                    IMPORT_PREVIEW: '{{ route('master-user.import.preview') }}',
                    IMPORT_VALIDATE: '{{ route('master-user.import.validate-row') }}',
                    IMPORT_CONFIRM: '{{ route('master-user.import.confirm') }}',
                },
                STORAGE_KEY: 'master-user',
            };

            const ROLE_BADGES = {
                superadmin: '<span class="badge bg-danger-subtle text-danger"><i class="ri ri-shield-star-line"></i> Superadmin</span>',
                admin: '<span class="badge bg-warning-subtle text-warning"><i class="ri ri-shield-user-line"></i> Admin</span>',
                pmo: '<span class="badge bg-info-subtle text-info"><i class="ri ri-team-line"></i> PMO</span>',
                pasien: '<span class="badge bg-primary-subtle text-primary"><i class="ri ri-user-heart-line"></i> Pasien</span>',
                pengunjung: '<span class="badge bg-secondary-subtle text-secondary"><i class="ri ri-user-line"></i> Pengunjung</span>',
            };

            const ROLES_WITH_BIODATA = ['pasien', 'pmo'];

            const renderAvatar = (row) => {
                const initials = (row.name || 'U').charAt(0).toUpperCase();
                const colors = ['primary', 'info', 'success', 'warning', 'danger'];
                const color = colors[(row.id || '').charCodeAt(0) % colors.length] || 'primary';
                return `<div class="avatar bg-${color} text-white" style="width:36px;height:36px;font-size:0.875rem;">${initials}</div>`;
            };

            const renderStatus = (val) => val ?
                '<span class="badge bg-success-subtle text-success"><i class="ri ri-check-line"></i> Aktif</span>' :
                '<span class="badge bg-danger-subtle text-danger"><i class="ri ri-close-line"></i> Nonaktif</span>';

            const renderBiodata = (row) => {
                if (!ROLES_WITH_BIODATA.includes(row.role)) {
                    return '<span class="text-muted small">-</span>';
                }
                const hasBiodata = row.biodata && row.biodata.nik;
                if (hasBiodata) {
                    return '<span class="badge bg-success-subtle text-success" title="Biodata lengkap"><i class="ri ri-check-line"></i> Lengkap</span>';
                }
                return '<span class="badge bg-warning-subtle text-warning" title="Belum diisi"><i class="ri ri-error-warning-line"></i> Belum diisi</span>';
            };

            const renderNameUsername = (row) => {
                const isMe = row.id === CONFIG.ID_LOGGED;
                const meBadge = isMe ? ' <span class="badge bg-secondary text-white ms-1">Anda</span>' : '';
                const username = row.username ?
                    `<br><small class="text-muted"><code style="font-size:0.7rem;">${row.username}</code></small>` :
                    '';
                return `<strong>${row.name}</strong>${meBadge}${username}`;
            };

            const renderActions = (row) => {
                const id = row.id;
                const actions = [];

                actions.push(
                    `<a href="${CONFIG.ROUTES.SHOW.replace(':id', id)}" class="btn btn-sm btn-outline-info" title="Detail"><i class="ri ri-eye-line"></i></a>`
                );

                const canManageThis = (() => {
                    if (CONFIG.IS_SUPER) return true;
                    if (id === CONFIG.ID_LOGGED) return true;
                    return ['pmo', 'pasien', 'pengunjung'].includes(row.role);
                })();

                if (canManageThis) {
                    @can('master-user.edit')
                        actions.push(
                            `<a href="${CONFIG.ROUTES.EDIT.replace(':id', id)}" class="btn btn-sm btn-outline-warning" title="Edit"><i class="ri ri-pencil-line"></i></a>`
                        );
                    @endcan

                    @can('master-user.delete')
                        if (id !== CONFIG.ID_LOGGED) {
                            actions.push(
                                `<button class="btn btn-sm btn-outline-danger btn-delete-row" data-id="${id}" data-name="${row.name}" title="Hapus"><i class="ri ri-delete-bin-line"></i></button>`
                            );
                        }
                    @endcan
                }

                return `<div class="row-actions">${actions.join('')}</div>`;
            };

            const grid = new DataGrid({
                selector: '#datatable',
                ajaxUrl: CONFIG.ROUTES.DATA,
                storageKey: CONFIG.STORAGE_KEY,
                order: [
                    [0, 'asc']
                ],
                filters: {
                    search: '#searchInput',
                    role: '#filterRole',
                    is_active: '#filterStatus',
                },
                columns: [{
                        data: null,
                        orderable: false,
                        className: 'text-center',
                        render: (d, t, r, meta) => meta.row + meta.settings._iDisplayStart + 1
                    },
                    {
                        data: null,
                        orderable: false,
                        render: (d, t, row) => renderAvatar(row)
                    },
                    {
                        data: 'name',
                        name: 'name',
                        render: (val, t, row) => renderNameUsername(row)
                    },
                    {
                        data: 'whatsapp_number',
                        render: (v) => v ? `<code>${v}</code>` : '<span class="text-muted">-</span>'
                    },
                    {
                        data: 'role',
                        render: (v) => ROLE_BADGES[v] || v
                    },
                    {
                        data: null,
                        orderable: false,
                        className: 'text-center',
                        render: (d, t, row) => renderBiodata(row)
                    },
                    {
                        data: 'is_active',
                        className: 'text-center',
                        render: (v) => renderStatus(v)
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

            window.filterByRole = (role) => {
                $('#filterRole').val(role).trigger('change');
            };

            // ============================================================
            // ========== IMPORT EXCEL MODULE ==========
            // ============================================================
            const ImportExcel = {
                modal: null,
                currentStep: 1,
                selectedFile: null,
                previewData: null,
                lastImportedCount: 0, // ← TAMBAH INI

                init() {
                    const modalEl = document.getElementById('importExcelModal');
                    if (!modalEl) return;
                    this.modal = new bootstrap.Modal(modalEl);

                    // Open modal
                    $('#btnOpenImportModal').on('click', () => this.open());

                    // Step 1: Upload events
                    $('#btnSelectFile').on('click', () => $('#importFileInput').click());
                    $('#importFileInput').on('change', (e) => this.handleFileSelect(e.target.files[0]));
                    $('#btnClearFile').on('click', () => this.clearFile());
                    $('#btnUploadFile').on('click', () => this.uploadAndPreview());

                    // Drag & drop
                    const $uploadZone = $('#uploadZone');
                    const $uploadContent = $('.upload-content');

                    // Klik area upload (TAPI bukan tombolnya - tombol punya handler sendiri)
                    $uploadContent.on('click', (e) => {
                        // Skip kalau klik tombol (tombol punya handler tersendiri)
                        if ($(e.target).closest('button').length) return;
                        $('#importFileInput').click();
                    });

                    $uploadZone.on('dragover', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        $uploadZone.addClass('drag-over');
                    });

                    $uploadZone.on('dragleave', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        $uploadZone.removeClass('drag-over');
                    });

                    $uploadZone.on('drop', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        $uploadZone.removeClass('drag-over');
                        const file = e.originalEvent.dataTransfer.files[0];
                        if (file) this.handleFileSelect(file);
                    });

                    // Step 2: Filter & confirm
                    $('#filterValidOnly').on('change', () => this.renderPreviewTable());
                    $('#btnConfirmImport').on('click', () => this.confirmImport());
                    $('#btnPrevStep').on('click', () => this.goToStep(1));

                    // Edit inline & skip row (delegated)
                    $(document).on('blur', '.preview-table .edit-input', (e) => this.handleFieldEdit(e.target));
                    $(document).on('click', '.btn-skip-row', (e) => this.toggleSkipRow($(e.currentTarget).data(
                        'row')));

                    // Close handlers
                    // Close handlers
                    $('#btnFooterClose, #btnCloseImportModal, #btnFinish').on('click', () => this.close());

                    // Bootstrap modal close event - catches X button & ESC key & backdrop click
                    $('#importExcelModal').on('hidden.bs.modal', () => {
                        if (this.currentStep === 3 && this.lastImportedCount > 0) {
                            window.location.reload();
                        }
                    });
                },

                open() {
                    this.reset();
                    this.modal.show();
                },

                close() {
                    // Reload page kalau sudah ada import sukses (step 3)
                    const needReload = this.currentStep === 3 && this.lastImportedCount > 0;

                    this.modal.hide();

                    if (needReload) {
                        window.location.reload();
                    }
                },

                reset() {
                    this.currentStep = 1;
                    this.selectedFile = null;
                    this.previewData = null;
                    this.lastImportedCount = 0; // ← TAMBAH INI
                    this.goToStep(1);
                    this.clearFile();
                    $('#importFileInput').val('');
                },

                goToStep(step) {
                    this.currentStep = step;

                    // Update step indicator
                    $('.step-item').removeClass('active completed');
                    for (let i = 1; i <= 3; i++) {
                        if (i < step) $(`.step-item[data-step="${i}"]`).addClass('completed');
                        else if (i === step) $(`.step-item[data-step="${i}"]`).addClass('active');
                    }

                    // Show right content
                    $('.step-content').hide();
                    $(`.step-content[data-step="${step}"]`).show();

                    // Footer buttons
                    $('#btnFooterClose, #btnPrevStep, #btnConfirmImport, #btnFinish').hide();
                    if (step === 1) {
                        $('#btnFooterClose').show();
                    } else if (step === 2) {
                        $('#btnPrevStep').show();
                        $('#btnConfirmImport').show();
                        this.updateConfirmButtonState();
                    } else if (step === 3) {
                        $('#btnFinish').show();
                    }
                },

                // ============ STEP 1: FILE SELECT & UPLOAD ============

                handleFileSelect(file) {
                    if (!file) return;

                    // Validasi extension
                    const validExt = ['xlsx', 'xls'];
                    const ext = file.name.split('.').pop().toLowerCase();
                    if (!validExt.includes(ext)) {
                        Swal.fire('Format Salah', 'Hanya menerima file .xlsx atau .xls', 'warning');
                        return;
                    }

                    // Validasi size (5 MB)
                    if (file.size > 5 * 1024 * 1024) {
                        Swal.fire('File Terlalu Besar', 'Maksimal ukuran file 5 MB.', 'warning');
                        return;
                    }

                    this.selectedFile = file;
                    $('#selectedFileName').text(file.name);
                    $('#selectedFileSize').text(this.formatFileSize(file.size));
                    $('.upload-content').hide();
                    $('.upload-file-preview').show();
                },

                clearFile() {
                    this.selectedFile = null;
                    $('#importFileInput').val('');
                    $('.upload-content').show();
                    $('.upload-file-preview').hide();
                },

                formatFileSize(bytes) {
                    if (bytes < 1024) return bytes + ' B';
                    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
                    return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
                },

                async uploadAndPreview() {
                    if (!this.selectedFile) {
                        Swal.fire('File Belum Dipilih', 'Pilih file Excel terlebih dahulu.', 'warning');
                        return;
                    }

                    const $btn = $('#btnUploadFile');
                    $btn.prop('disabled', true).find('.spinner-border').removeClass('d-none');

                    const formData = new FormData();
                    formData.append('file', this.selectedFile);
                    formData.append('_token', $('input[name=_token]').val() || $('meta[name=csrf-token]')
                        .attr('content'));

                    try {
                        const res = await $.ajax({
                            url: CONFIG.ROUTES.IMPORT_PREVIEW,
                            method: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                        });

                        this.previewData = res.data;
                        this.goToStep(2);
                        this.renderPreviewSummary();
                        this.renderPreviewTable();
                    } catch (xhr) {
                        Swal.fire('Gagal!', xhr.responseJSON?.message || 'Terjadi kesalahan parsing file.',
                            'error');
                    } finally {
                        $btn.prop('disabled', false).find('.spinner-border').addClass('d-none');
                    }
                },

                // ============ STEP 2: PREVIEW & EDIT ============

                renderPreviewSummary() {
                    const s = this.previewData.summary;
                    $('#sumTotal').text(s.total);
                    $('#sumValid').text(s.valid);
                    $('#sumConflict').text(s.conflict);
                    $('#sumError').text(s.error);
                },

                renderPreviewTable() {
                    const filterValidOnly = $('#filterValidOnly').is(':checked');
                    let rows = this.previewData.rows;
                    if (filterValidOnly) {
                        rows = rows.filter(r => r.status !== 'valid');
                    }

                    if (rows.length === 0) {
                        $('#previewTableWrapper').html(`
                    <div class="alert alert-info m-3">
                        <i class="ri ri-information-line me-1"></i>
                        ${filterValidOnly ? 'Tidak ada row yang bermasalah - semua valid!' : 'Tidak ada data untuk ditampilkan.'}
                    </div>
                `);
                        return;
                    }

                    const tableHtml = `
                <div class="table-responsive">
                    <table class="table table-bordered preview-table mb-0">
                        <thead>
                            <tr>
                                <th style="width: 4%;" class="text-center">#</th>
                                <th style="width: 8%;">Status</th>
                                <th style="width: 13%;">Nama *</th>
                                <th style="width: 7%;">Role *</th>
                                <th style="width: 11%;">WhatsApp *</th>
                                <th style="width: 12%;">NIK *</th>
                                <th style="width: 5%;" class="text-center">JK *</th>
                                <th style="width: 9%;">Tempat *</th>
                                <th style="width: 9%;">Tgl Lahir *</th>
                                <th style="width: 15%;">Password *</th>
                                <th style="width: 7%;" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${rows.map(row => this.renderRow(row)).join('')}
                        </tbody>
                    </table>
                </div>
            `;
                    $('#previewTableWrapper').html(tableHtml);
                },

                renderRow(row) {
                    const rowIdx = row.row_number;
                    const isSkipped = row.skipped;
                    const rowClass = isSkipped ? 'row-skipped' : `row-${row.status}`;

                    const statusBadge = {
                        valid: '<span class="row-status bg-success-subtle text-success"><i class="ri ri-check-line"></i> Valid</span>',
                        conflict: '<span class="row-status bg-warning-subtle text-warning"><i class="ri ri-error-warning-line"></i> Konflik</span>',
                        error: '<span class="row-status bg-danger-subtle text-danger"><i class="ri ri-close-circle-line"></i> Error</span>',
                    } [row.status];

                    // Editable input untuk field utama
                    const field = (name, value, type = 'text') => {
                        const err = row.errors[name] || '';
                        const cls = err ? 'edit-input is-invalid' : 'edit-input';
                        const errHtml = err ? `<div class="field-error">${err}</div>` : '';
                        return `
                    <input type="${type}" class="${cls}" data-field="${name}" data-row="${rowIdx}"
                           value="${this.escapeHtml(value || '')}" ${isSkipped ? 'disabled' : ''}>
                    ${errHtml}
                `;
                    };

                    // Special: role (select)
                    const roleField = () => {
                        const err = row.errors.role || '';
                        const cls = err ? 'edit-input is-invalid' : 'edit-input';
                        const errHtml = err ? `<div class="field-error">${err}</div>` : '';
                        const val = row.data.role || '';
                        return `
                    <select class="${cls}" data-field="role" data-row="${rowIdx}" ${isSkipped ? 'disabled' : ''}>
                        <option value="">-</option>
                        <option value="pasien" ${val === 'pasien' ? 'selected' : ''}>pasien</option>
                        <option value="pmo" ${val === 'pmo' ? 'selected' : ''}>pmo</option>
                    </select>
                    ${errHtml}
                `;
                    };

                    // Special: jenis kelamin (select)
                    const jkField = () => {
                        const err = row.errors.jenis_kelamin || '';
                        const cls = err ? 'edit-input is-invalid' : 'edit-input';
                        const errHtml = err ? `<div class="field-error">${err}</div>` : '';
                        const val = row.data.jenis_kelamin || '';
                        return `
                    <select class="${cls}" data-field="jenis_kelamin" data-row="${rowIdx}" ${isSkipped ? 'disabled' : ''}>
                        <option value="">-</option>
                        <option value="L" ${val === 'L' ? 'selected' : ''}>L</option>
                        <option value="P" ${val === 'P' ? 'selected' : ''}>P</option>
                    </select>
                    ${errHtml}
                `;
                    };

                    const skipBtn = isSkipped ?
                        `<button class="btn btn-sm btn-outline-success btn-skip-row" data-row="${rowIdx}" title="Batal skip"><i class="ri ri-arrow-go-back-line"></i></button>` :
                        `<button class="btn btn-sm btn-outline-danger btn-skip-row" data-row="${rowIdx}" title="Skip baris ini"><i class="ri ri-close-line"></i></button>`;

                    return `
                <tr class="${rowClass}" data-row="${rowIdx}">
                    <td class="text-center fw-bold">${rowIdx}</td>
                    <td>${statusBadge}</td>
                    <td>${field('nama', row.data.nama)}</td>
                    <td>${roleField()}</td>
                    <td>${field('whatsapp_number', row.data.whatsapp_number)}</td>
                    <td>${field('nik', row.data.nik)}</td>
                    <td class="text-center">${jkField()}</td>
                    <td>${field('tempat_lahir', row.data.tempat_lahir)}</td>
                    <td>${field('tanggal_lahir', row.data.tanggal_lahir, 'date')}</td>
                    <td>${field('password', row.data.password)}</td>
                    <td class="text-center">${skipBtn}</td>
                </tr>
            `;
                },

                escapeHtml(str) {
                    return String(str)
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#039;');
                },

                async handleFieldEdit(inputEl) {
                    const $input = $(inputEl);
                    const rowIdx = $input.data('row');
                    const field = $input.data('field');
                    const newValue = $input.val();

                    // Find row
                    const rowObj = this.previewData.rows.find(r => r.row_number === rowIdx);
                    if (!rowObj) return;

                    // Update value
                    rowObj.data[field] = newValue;

                    // Build exclude lists (cross-row check)
                    const otherRows = this.previewData.rows.filter(r => r.row_number !== rowIdx && !r
                        .skipped);
                    const excludeNamas = otherRows.map(r => (r.data.nama || '').toLowerCase()).filter(
                        Boolean);
                    const excludeWAs = otherRows.map(r => r.data.whatsapp_number).filter(Boolean);
                    const excludeNiks = otherRows.map(r => r.data.nik).filter(Boolean);

                    try {
                        const res = await $.ajax({
                            url: CONFIG.ROUTES.IMPORT_VALIDATE,
                            method: 'POST',
                            data: {
                                _token: $('input[name=_token]').val() || $('meta[name=csrf-token]')
                                    .attr('content'),
                                data: rowObj.data,
                                exclude_namas: excludeNamas,
                                exclude_was: excludeWAs,
                                exclude_niks: excludeNiks,
                            },
                        });

                        // Update row data
                        rowObj.data = res.data.data;
                        rowObj.errors = res.data.errors;
                        rowObj.status = res.data.status;

                        // Re-render whole row
                        this.refreshRow(rowIdx);
                        this.recalcSummary();
                        this.updateConfirmButtonState();
                    } catch (xhr) {
                        console.error('Validate failed:', xhr);
                    }
                },

                refreshRow(rowIdx) {
                    const rowObj = this.previewData.rows.find(r => r.row_number === rowIdx);
                    if (!rowObj) return;

                    const $oldTr = $(`#previewTableWrapper tr[data-row="${rowIdx}"]`);
                    if ($oldTr.length === 0) return;

                    const newHtml = this.renderRow(rowObj);
                    $oldTr.replaceWith(newHtml);
                },

                toggleSkipRow(rowIdx) {
                    const rowObj = this.previewData.rows.find(r => r.row_number === rowIdx);
                    if (!rowObj) return;

                    rowObj.skipped = !rowObj.skipped;
                    this.refreshRow(rowIdx);
                    this.recalcSummary();
                    this.updateConfirmButtonState();
                },

                recalcSummary() {
                    let valid = 0,
                        conflict = 0,
                        error = 0;
                    for (const row of this.previewData.rows) {
                        if (row.skipped) continue;
                        if (row.status === 'valid') valid++;
                        else if (row.status === 'conflict') conflict++;
                        else if (row.status === 'error') error++;
                    }
                    this.previewData.summary = {
                        total: this.previewData.rows.length,
                        valid,
                        conflict,
                        error,
                    };
                    this.renderPreviewSummary();
                },

                updateConfirmButtonState() {
                    const s = this.previewData.summary;
                    const $btn = $('#btnConfirmImport');
                    const canImport = s.valid > 0 && s.conflict === 0 && s.error === 0;

                    if (canImport) {
                        $btn.prop('disabled', false)
                            .removeClass('btn-secondary')
                            .addClass('btn-success')
                            .html(
                                `<span class="spinner-border spinner-border-sm d-none me-2"></span><i class="ri ri-check-double-line me-1"></i> Konfirmasi Import (${s.valid} user)`
                            );
                    } else if (s.valid === 0) {
                        $btn.prop('disabled', true)
                            .removeClass('btn-success')
                            .addClass('btn-secondary')
                            .html(
                                '<i class="ri ri-error-warning-line me-1"></i> Tidak ada data valid untuk diimport'
                            );
                    } else {
                        $btn.prop('disabled', true)
                            .removeClass('btn-success')
                            .addClass('btn-secondary')
                            .html(
                                `<i class="ri ri-error-warning-line me-1"></i> Perbaiki ${s.conflict + s.error} row bermasalah dulu`
                            );
                    }
                },

                async confirmImport() {
                    const $btn = $('#btnConfirmImport');
                    $btn.prop('disabled', true).find('.spinner-border').removeClass('d-none');

                    try {
                        const csrfToken = $('input[name=_token]').val() || $('meta[name=csrf-token]').attr(
                            'content');
                        const res = await $.ajax({
                            url: CONFIG.ROUTES.IMPORT_CONFIRM,
                            method: 'POST',
                            contentType: 'application/json',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken
                            },
                            data: JSON.stringify({
                                rows: this.previewData.rows,
                            }),
                        });

                        this.renderResult(res.data, res.message);
                        this.goToStep(3);
                    } catch (xhr) {
                        Swal.fire('Gagal Import!', xhr.responseJSON?.message || 'Terjadi kesalahan.',
                            'error');
                    } finally {
                        $btn.prop('disabled', false).find('.spinner-border').addClass('d-none');
                    }
                },

                // ============ STEP 3: RESULT ============

                renderResult(data, message) {
                    this.lastImportedCount = data.imported || 0; // ← TAMBAH BARIS INI

                    $('#resultMessage').text(message || `${data.imported} user berhasil diimport.`);
                    $('#resultImported').text(data.imported);
                    $('#resultSkipped').text(data.skipped);

                    if (data.failed && data.failed.length > 0) {
                        const items = data.failed.map(f => `
                    <li>
                        <strong>Baris ${f.row_number}</strong> (${f.name}):
                        ${Object.values(f.errors).join(', ')}
                    </li>
                `).join('');
                        $('#resultFailedItems').html(items);
                        $('#resultFailedList').show();
                    } else {
                        $('#resultFailedList').hide();
                    }
                },
            };

            ImportExcel.init();
        });
    </script>
@endpush
