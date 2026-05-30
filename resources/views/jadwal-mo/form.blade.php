@extends('layouts.app')

@section('title', isset($id) ? 'Edit Jadwal' : 'Tambah Jadwal')

@section('page-header')
    <nav aria-label="breadcrumb" class="small">
        <ol class="breadcrumb mb-2">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('jadwal-mo.index') }}" class="text-decoration-none">Jadwal Minum
                    Obat</a></li>
            <li class="breadcrumb-item active">{{ isset($id) ? 'Edit' : 'Tambah' }}</li>
        </ol>
    </nav>
    <div>
        <h4 class="fw-bold mb-1" id="formTitle">{{ isset($id) ? 'Edit Jadwal' : 'Tambah Jadwal Baru' }}</h4>
        <small class="text-muted">
            @if (!isset($id))
                Pilih pasien dan tambahkan satu atau lebih obat sekaligus.
            @endif
        </small>
    </div>
@endsection

@section('content')

    @php
        $user = auth()->user();
        $isAdmin = $user->isAdmin() || $user->isSuperadmin();
    @endphp

    <form id="jadwalForm" novalidate>
        @csrf
        @if (isset($id))
            @method('PUT')
            <input type="hidden" id="jadwalId" value="{{ $id }}">
        @endif

        <div class="row g-4">
            <div class="col-lg-8">

                {{-- ============ PILIH PASIEN-PMO ============ --}}
                <x-card title="Pilih Pasien & PMO" icon="ri-user-heart-line">
                    <div class="mb-0">
                        <label for="id_pasien_pmo" class="form-label form-label-required">Pasien (yang dimapping dengan
                            PMO)</label>
                        <select id="id_pasien_pmo" name="id_pasien_pmo" class="form-select" required>
                            <option value="">-- Pilih mapping pasien-PMO --</option>
                            {{-- Filled by JS --}}
                        </select>
                        <div class="invalid-feedback"></div>
                        <small class="text-muted">
                            @if ($user->isPmo() || $user->isPasien())
                                Daftar di-filter sesuai akses Anda.
                            @else
                                Pilih mapping pasien-PMO yang akan dibuatkan jadwal.
                            @endif
                        </small>
                    </div>
                </x-card>

                <div class="mt-3"></div>

                {{-- ============ OBAT LIST (multi-obat) ============ --}}
                @if (!isset($id))
                    <x-card title="Daftar Jadwal Obat" icon="ri-medicine-bottle-line">
                        <x-slot:headerActions>
                            <span class="badge bg-primary-subtle text-primary">
                                <span id="selectedCount">0</span> obat
                            </span>
                        </x-slot:headerActions>

                        <div class="row g-2 mb-3">
                            <div class="col-md-7">
                                <label class="form-label form-label-required">Pilih obat untuk dijadwalkan</label>
                                <div class="position-relative">
                                    <input type="text" id="obatSearchInput" class="form-control"
                                        placeholder="Ketik nama obat..." autocomplete="off">
                                    <div id="obatSearchDropdown" class="dropdown-menu w-100"
                                        style="max-height: 300px; overflow-y: auto; display: none;">
                                        {{-- Filled by JS --}}
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-5 d-flex align-items-end">
                                <button type="button" id="btnOpenQuickAddObat" class="btn btn-outline-primary w-100">
                                    <i class="ri ri-add-circle-line me-1"></i> Tambah Obat Baru ke Master
                                </button>
                            </div>
                        </div>

                        {{-- Tabel jadwal multi-obat --}}
                        <div class="mb-0">
                            <div id="obatTableWrapper">
                                <div class="text-muted small text-center py-4 border rounded" id="emptyObatPlaceholder">
                                    <i class="ri ri-medicine-bottle-line fs-3 d-block mb-2"></i>
                                    Belum ada obat yang dipilih.<br>
                                    Cari & klik obat di atas, atau klik <strong>"Tambah Obat Baru"</strong> kalau belum ada
                                    di master.
                                </div>

                                <div class="table-responsive" id="obatTable" style="display:none;">
                                    <table class="table table-bordered mb-0 align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 4%" class="text-center">#</th>
                                                <th>Obat</th>
                                                <th style="width: 12%">Tgl Mulai <span class="text-danger">*</span></th>
                                                <th style="width: 10%">Jam Mulai <span class="text-danger">*</span></th>
                                                <th style="width: 12%" class="text-center">Sehari Berapa Kali? <span
                                                        class="text-danger">*</span></th>
                                                <th style="width: 14%">Catatan</th>
                                                <th style="width: 6%" class="text-center">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody id="obatTableBody">
                                            {{-- Filled by JS --}}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="invalid-feedback d-block" id="obats_error"></div>
                        </div>
                    </x-card>
                @else
                    {{-- EDIT MODE: form single jadwal --}}
                    <x-card title="Detail Jadwal" icon="ri-medicine-bottle-line">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label for="obat_id" class="form-label form-label-required">Obat</label>
                                <select id="obat_id" name="obat_id" class="form-select" required>
                                    <option value="">-- Pilih obat --</option>
                                    {{-- Filled by JS --}}
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label for="tgl_mulai" class="form-label form-label-required">Tanggal Mulai</label>
                                <input type="date" id="tgl_mulai" name="tgl_mulai" class="form-control" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label for="jam_mulai" class="form-label form-label-required">Jam Mulai</label>
                                <input type="time" id="jam_mulai" name="jam_mulai" class="form-control" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-12">
                                <label for="frekuensi_per_hari" class="form-label form-label-required">Frekuensi per Hari
                                    (kali sehari)</label>
                                <input type="number" id="frekuensi_per_hari" name="frekuensi_per_hari"
                                    class="form-control" min="1" max="12" required>
                                <small class="text-muted">Contoh: 1, 2, 3 (sehari berapa kali minum)</small>
                                <div class="invalid-feedback"></div>
                            </div>

                            <div class="col-md-12">
                                <label for="catatan_dosis" class="form-label">Catatan Dosis</label>
                                <textarea id="catatan_dosis" name="catatan_dosis" class="form-control" rows="2" maxlength="500"
                                    placeholder="Contoh: 1 tablet setelah makan"></textarea>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </x-card>
                @endif
            </div>

            {{-- ============ KOLOM KANAN ============ --}}
            <div class="col-lg-4">
                @if (isset($id))
                    <x-card title="Status Jadwal" icon="ri-toggle-line">
                        <div class="mb-0">
                            <label for="status" class="form-label form-label-required">Status</label>
                            <select id="status" name="status" class="form-select" required>
                                <option value="aktif">Aktif</option>
                                <option value="nonaktif">Nonaktif</option>
                                <option value="selesai">Selesai</option>
                            </select>
                            <small class="text-muted">
                                <strong>Aktif</strong> = sedang berjalan<br>
                                <strong>Nonaktif</strong> = dihentikan sementara<br>
                                <strong>Selesai</strong> = pengobatan tuntas
                            </small>
                        </div>
                    </x-card>

                    <div class="mt-3"></div>
                @endif

                <x-card class="border-start border-4 border-info">
                    <h6 class="fw-bold mb-2 small">
                        <i class="ri ri-information-line text-info me-1"></i> Info
                    </h6>
                    @if (!isset($id))
                        <p class="small text-muted mb-2">
                            Form ini akan membuat <strong>1 jadwal per obat</strong> dengan pasien-PMO yang sama.
                        </p>
                        <p class="small text-muted mb-0">
                            Kalau obat belum ada di master, klik <strong>"Tambah Obat Baru"</strong> — obat akan langsung
                            tersimpan ke master & terpilih di form.
                        </p>
                    @else
                        <p class="small text-muted mb-0">
                            Edit detail jadwal. Untuk ganti pasien, hapus jadwal ini dan buat baru.
                        </p>
                    @endif
                </x-card>
            </div>
        </div>

        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-primary fw-semibold px-4" id="submitBtn">
                <span class="spinner-border spinner-border-sm d-none me-2"></span>
                <i class="ri ri-save-line me-1"></i> Simpan Jadwal
            </button>
            <a href="{{ route('jadwal-mo.index') }}" class="btn btn-outline-secondary">Batal</a>
        </div>
    </form>

    {{-- ============ MODAL QUICK-ADD OBAT ============ --}}
    <div class="modal fade" id="quickAddObatModal" tabindex="-1" aria-labelledby="quickAddObatModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="quickAddObatModalLabel">
                        <i class="ri ri-add-circle-line text-primary me-1"></i> Tambah Obat Baru
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="quickAddObatForm" novalidate>
                        <div class="mb-3">
                            <label for="qa_nama" class="form-label form-label-required">Nama Obat</label>
                            <input type="text" id="qa_nama" class="form-control" placeholder="Contoh: Paracetamol"
                                required maxlength="100">
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="mb-3">
                            <label for="qa_satuan_id" class="form-label form-label-required">Satuan</label>
                            <select id="qa_satuan_id" class="form-select" required>
                                <option value="">-- Pilih satuan --</option>
                                {{-- Filled by JS --}}
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="mb-0">
                            <label for="qa_dosis_default" class="form-label">Dosis (opsional)</label>
                            <input type="text" id="qa_dosis_default" class="form-control" placeholder="Contoh: 500mg"
                                maxlength="50">
                            <small class="text-muted">Contoh: 500mg, 250mg, 5ml</small>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="alert alert-info small mt-3 mb-0">
                            <i class="ri ri-information-line me-1"></i>
                            Obat akan langsung tersimpan ke master & otomatis terpilih di jadwal.
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" id="btnSaveQuickObat">
                        <span class="spinner-border spinner-border-sm d-none me-2"></span>
                        <i class="ri ri-save-line me-1"></i> Simpan Obat
                    </button>
                </div>
            </div>
        </div>
    </div>

@endsection
@push('scripts')
    <script>
        window.whenKesehatanReady(function() {
            'use strict';

            const CONFIG = {
                IS_EDIT: {{ isset($id) ? 'true' : 'false' }},
                ID: '{{ $id ?? '' }}',
                ROUTES: {
                    INDEX: '{{ route('jadwal-mo.index') }}',
                    STORE: '{{ route('jadwal-mo.store') }}',
                    UPDATE: '{{ isset($id) ? route('jadwal-mo.update', $id) : '' }}',
                    SHOW_DATA: '{{ isset($id) ? route('jadwal-mo.show-data', $id) : '' }}',
                    OPT_PASIEN_PMO: '{{ route('jadwal-mo.options.pasien-pmo') }}',
                    OPT_OBAT: '{{ route('jadwal-mo.options.obat') }}',
                    OPT_SATUAN: '{{ route('jadwal-mo.options.satuan') }}',
                    QUICK_OBAT: '{{ route('jadwal-mo.quick-create-obat') }}',
                },
            };

            const $form = $('#jadwalForm');
            const $submitBtn = $('#submitBtn');

            let allObats = [];
            let
                selectedObats = []; // Array of { id, label, nama, ... + tgl_mulai, jam_mulai, frekuensi_per_hari, durasi_hari, catatan_dosis }
            let quickAddObatModal = null;

            // ============ INIT ============
            init();

            async function init() {
                await loadPasienPmoOptions();
                await loadObatOptions();

                if (CONFIG.IS_EDIT) {
                    await loadExistingData();
                }

                await loadSatuanOptions();
                setupQuickAddModal();
                setupObatSearch();
                $form.on('submit', submitForm);
            }

            async function loadPasienPmoOptions() {
                try {
                    const res = await $.ajax({
                        url: CONFIG.ROUTES.OPT_PASIEN_PMO,
                        method: 'GET'
                    });
                    const $select = $('#id_pasien_pmo');
                    res.data.forEach(m => {
                        $select.append(`<option value="${m.id}">${m.label}</option>`);
                    });

                    // Auto-select kalau cuma 1 option (PMO/pasien biasanya cuma punya 1 mapping)
                    if (res.data.length === 1) {
                        $select.val(res.data[0].id);
                    }
                } catch (e) {
                    console.error('Load pasien-pmo options failed:', e);
                }
            }

            async function loadObatOptions() {
                try {
                    const res = await $.ajax({
                        url: CONFIG.ROUTES.OPT_OBAT,
                        method: 'GET'
                    });
                    allObats = res.data;

                    if (CONFIG.IS_EDIT) {
                        const $select = $('#obat_id');
                        allObats.forEach(o => {
                            $select.append(`<option value="${o.id}">${o.label}</option>`);
                        });
                    }
                } catch (e) {
                    console.error('Load obat options failed:', e);
                }
            }

            async function loadSatuanOptions() {
                try {
                    const res = await $.ajax({
                        url: CONFIG.ROUTES.OPT_SATUAN,
                        method: 'GET'
                    });
                    const $select = $('#qa_satuan_id');
                    res.data.forEach(s => {
                        $select.append(`<option value="${s.id}">${s.label}</option>`);
                    });
                } catch (e) {
                    console.error('Load satuan options failed:', e);
                }
            }

            async function loadExistingData() {
                try {
                    const data = await $.ajax({
                        url: CONFIG.ROUTES.SHOW_DATA,
                        method: 'GET'
                    });

                    $('#formTitle').text('Edit Jadwal: ' + (data.obat?.nama ?? '-'));

                    // Set pasien-pmo (kalau ada di options, set; kalau tidak, tambahkan)
                    if ($('#id_pasien_pmo option[value="' + data.id_pasien_pmo + '"]').length === 0) {
                        $('#id_pasien_pmo').append(
                            `<option value="${data.id_pasien_pmo}">${data.nama_pasien} (PMO: ${data.nama_pmo})</option>`
                        );
                    }
                    $('#id_pasien_pmo').val(data.id_pasien_pmo);

                    // Obat
                    $('#obat_id').val(data.obat_id);

                    // Detail
                    $('#tgl_mulai').val(data.tgl_mulai ? data.tgl_mulai.substr(0, 10) : '');
                    $('#jam_mulai').val(data.jam_mulai ? data.jam_mulai.substr(0, 5) : '');
                    $('#frekuensi_per_hari').val(data.frekuensi_per_hari);
                    $('#catatan_dosis').val(data.catatan_dosis || '');
                    $('#status').val(data.status);
                } catch (e) {
                    Swal.fire({
                        title: 'Error!',
                        text: 'Gagal memuat data jadwal.',
                        icon: 'error',
                    }).then(() => window.location.href = CONFIG.ROUTES.INDEX);
                }
            }

            // ============ OBAT SEARCH (create mode) ============
            function setupObatSearch() {
                if (CONFIG.IS_EDIT) return;

                const $input = $('#obatSearchInput');
                const $dropdown = $('#obatSearchDropdown');

                $input.on('focus', () => {
                    renderDropdown($input.val());
                    $dropdown.show();
                });

                $input.on('input', () => {
                    renderDropdown($input.val());
                    $dropdown.show();
                });

                $(document).on('click', function(e) {
                    if (!$(e.target).closest('#obatSearchInput, #obatSearchDropdown').length) {
                        $dropdown.hide();
                    }
                });

                // Pilih obat dari dropdown
                $dropdown.on('click', '.obat-option', function(e) {
                    e.preventDefault();
                    const id = $(this).data('id');
                    const obat = allObats.find(o => o.id === id);
                    if (obat && !selectedObats.find(o => o.id === id)) {
                        addObatRow(obat);
                        $input.val('').focus();
                        renderDropdown('');
                    }
                });

                // Remove row dari tabel
                $('#obatTableBody').on('click', '.btn-remove-obat', function(e) {
                    e.preventDefault();
                    const id = $(this).data('id');
                    selectedObats = selectedObats.filter(o => o.id !== id);
                    renderObatTable();
                });

                // Update field per row
                $('#obatTableBody').on('input change', '.field-row', function() {
                    const id = $(this).closest('tr').data('id');
                    const field = $(this).data('field');
                    const value = $(this).val();
                    const obat = selectedObats.find(o => o.id === id);
                    if (obat) {
                        obat[field] = value;
                        $(this).removeClass('is-invalid');
                    }
                });
            }

            function renderDropdown(searchTerm) {
                const $dropdown = $('#obatSearchDropdown');
                const term = (searchTerm || '').toLowerCase().trim();

                const available = allObats.filter(o => {
                    if (selectedObats.find(s => s.id === o.id)) return false;
                    if (!term) return true;
                    return o.nama.toLowerCase().includes(term) ||
                        (o.dosis_default || '').toLowerCase().includes(term);
                });

                if (available.length === 0) {
                    $dropdown.html(`
                <div class="dropdown-item text-muted small text-center py-3">
                    Tidak ada obat yang cocok.<br>
                    <span class="text-primary">Klik "Tambah Obat Baru" untuk daftarkan ke master.</span>
                </div>
            `);
                    return;
                }

                const html = available.map(o => {
                    const satuan = o.satuan ? `<small class="text-muted ms-2">(${o.satuan})</small>` : '';
                    const aturan = o.aturan_minum ?
                        `<br><small class="text-muted"><i class="ri ri-information-line"></i> ${o.aturan_minum}</small>` :
                        '';
                    return `
                <a href="#" class="dropdown-item obat-option" data-id="${o.id}">
                    <strong>${o.nama}</strong>${o.dosis_default ? ' ' + o.dosis_default : ''}${satuan}${aturan}
                </a>
            `;
                }).join('');

                $dropdown.html(html);
            }

            function addObatRow(obat) {
                selectedObats.push({
                    id: obat.id,
                    label: obat.label,
                    nama: obat.nama,
                    dosis_default: obat.dosis_default,
                    satuan: obat.satuan,
                    // Default values
                    tgl_mulai: new Date().toISOString().substr(0, 10),
                    jam_mulai: '08:00',
                    frekuensi_per_hari: 1,
                    catatan_dosis: '',
                });
                renderObatTable();
            }

            function renderObatTable() {
                const $tbody = $('#obatTableBody');
                const $placeholder = $('#emptyObatPlaceholder');
                const $tableWrap = $('#obatTable');

                $('#selectedCount').text(selectedObats.length);

                if (selectedObats.length === 0) {
                    $placeholder.show();
                    $tableWrap.hide();
                    $tbody.empty();
                    return;
                }

                $placeholder.hide();
                $tableWrap.show();

                const today = new Date().toISOString().substr(0, 10);

                const rows = selectedObats.map((o, idx) => {
                    const obatLabel =
                        `<strong>${o.nama}</strong>${o.dosis_default ? ' ' + o.dosis_default : ''}${o.satuan ? ` <small class="text-muted">(${o.satuan})</small>` : ''}`;

                    return `
                <tr data-id="${o.id}">
                    <td class="text-center fw-bold">${idx + 1}</td>
                    <td>${obatLabel}</td>
                    <td>
                        <input type="date" class="form-control form-control-sm field-row"
                            data-field="tgl_mulai" value="${o.tgl_mulai}" max="${today}" required>
                    </td>
                    <td>
                        <input type="time" class="form-control form-control-sm field-row"
                            data-field="jam_mulai" value="${o.jam_mulai}" required>
                    </td>
                    <td>
                        <input type="number" class="form-control form-control-sm field-row text-center"
                            data-field="frekuensi_per_hari" value="${o.frekuensi_per_hari}" min="1" max="12" required>
                    </td>
                  
                    <td>
                        <input type="text" class="form-control form-control-sm field-row"
                            data-field="catatan_dosis" value="${o.catatan_dosis}" maxlength="500" placeholder="Opsional">
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-obat" data-id="${o.id}" title="Hapus">
                            <i class="ri ri-close-line"></i>
                        </button>
                    </td>
                </tr>
            `;
                }).join('');

                $tbody.html(rows);
            }

            // ============ QUICK-ADD OBAT MODAL ============
            function setupQuickAddModal() {
                const modalEl = document.getElementById('quickAddObatModal');
                quickAddObatModal = new bootstrap.Modal(modalEl);

                $('#btnOpenQuickAddObat').on('click', function() {
                    resetQuickAddForm();
                    quickAddObatModal.show();
                });

                $('#btnSaveQuickObat').on('click', submitQuickAddObat);
            }

            function resetQuickAddForm() {
                $('#qa_nama').val('').removeClass('is-invalid');
                $('#qa_satuan_id').val('').removeClass('is-invalid');
                $('#qa_dosis_default').val('').removeClass('is-invalid');
                $('#quickAddObatForm .invalid-feedback').text('');
            }

            async function submitQuickAddObat() {
                const $btn = $('#btnSaveQuickObat');
                const $spinner = $btn.find('.spinner-border');

                $('#quickAddObatForm .is-invalid').removeClass('is-invalid');
                $('#quickAddObatForm .invalid-feedback').text('');

                const data = {
                    nama: $('#qa_nama').val().trim(),
                    satuan_id: $('#qa_satuan_id').val(),
                    dosis_default: $('#qa_dosis_default').val().trim() || null,
                };

                if (!data.nama) {
                    $('#qa_nama').addClass('is-invalid').siblings('.invalid-feedback').text(
                        'Nama obat wajib diisi.');
                    return;
                }
                if (!data.satuan_id) {
                    $('#qa_satuan_id').addClass('is-invalid').siblings('.invalid-feedback').text(
                        'Satuan wajib dipilih.');
                    return;
                }

                $btn.prop('disabled', true);
                $spinner.removeClass('d-none');

                try {
                    const csrfToken = $('input[name=_token]').val() || $('meta[name=csrf-token]').attr(
                        'content');
                    const res = await $.ajax({
                        url: CONFIG.ROUTES.QUICK_OBAT,
                        method: 'POST',
                        contentType: 'application/json',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken
                        },
                        data: JSON.stringify(data),
                    });

                    // Tambahkan obat baru ke list cache
                    allObats.push(res.data);

                    // Auto-select ke jadwal (CREATE mode: tambah ke selectedObats)
                    if (!CONFIG.IS_EDIT) {
                        if (!selectedObats.find(o => o.id === res.data.id)) {
                            addObatRow(res.data);
                        }
                    } else {
                        // EDIT mode: tambah option ke select dan auto-select
                        const $select = $('#obat_id');
                        $select.append(`<option value="${res.data.id}">${res.data.label}</option>`);
                        $select.val(res.data.id);
                    }

                    quickAddObatModal.hide();

                    Swal.fire({
                        title: 'Berhasil!',
                        text: res.message,
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false,
                    });
                } catch (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        Object.entries(xhr.responseJSON.errors).forEach(function([field, messages]) {
                            const fieldId = 'qa_' + field;
                            const $field = $('#' + fieldId);
                            $field.addClass('is-invalid');
                            $field.siblings('.invalid-feedback').text(messages[0]);
                        });
                    } else {
                        Swal.fire('Gagal!', xhr.responseJSON?.message || 'Terjadi kesalahan', 'error');
                    }
                } finally {
                    $btn.prop('disabled', false);
                    $spinner.addClass('d-none');
                }
            }

            // ============ SUBMIT FORM ============
            async function submitForm(e) {
                e.preventDefault();

                $form.find('.is-invalid').removeClass('is-invalid');
                $form.find('.invalid-feedback').text('');
                $('#obats_error').text('');

                $submitBtn.prop('disabled', true).find('.spinner-border').removeClass('d-none');

                const data = {
                    id_pasien_pmo: $('#id_pasien_pmo').val(),
                };

                if (CONFIG.IS_EDIT) {
                    data._method = 'PUT';
                    data.obat_id = $('#obat_id').val();
                    data.tgl_mulai = $('#tgl_mulai').val();
                    data.jam_mulai = $('#jam_mulai').val();
                    data.frekuensi_per_hari = parseInt($('#frekuensi_per_hari').val());
                    data.durasi_hari = $('#durasi_hari').val() || null;
                    data.catatan_dosis = $('#catatan_dosis').val() || null;
                    data.status = $('#status').val();
                } else {
                    // CREATE: validate selected obats
                    if (selectedObats.length === 0) {
                        $('#obats_error').text('Minimal pilih 1 obat.');
                        $submitBtn.prop('disabled', false).find('.spinner-border').addClass('d-none');
                        Swal.fire('Validasi Gagal', 'Pilih minimal 1 obat untuk dijadwalkan.', 'warning');
                        return;
                    }

                    // Validate per-row required fields
                    let hasError = false;
                    selectedObats.forEach(o => {
                        if (!o.tgl_mulai || !o.jam_mulai || !o.frekuensi_per_hari || o
                            .frekuensi_per_hari < 1) {
                            hasError = true;
                            // Highlight row
                            if (!o.tgl_mulai) $(`tr[data-id="${o.id}"] [data-field="tgl_mulai"]`)
                                .addClass('is-invalid');
                            if (!o.jam_mulai) $(`tr[data-id="${o.id}"] [data-field="jam_mulai"]`)
                                .addClass('is-invalid');
                            if (!o.frekuensi_per_hari || o.frekuensi_per_hari < 1) $(
                                `tr[data-id="${o.id}"] [data-field="frekuensi_per_hari"]`).addClass(
                                'is-invalid');
                        }
                    });

                    if (hasError) {
                        $('#obats_error').text(
                            'Tgl Mulai, Jam Mulai, dan Frekuensi wajib diisi untuk setiap obat.');
                        $submitBtn.prop('disabled', false).find('.spinner-border').addClass('d-none');
                        Swal.fire('Validasi Gagal', 'Lengkapi field wajib di setiap baris obat.', 'warning');
                        return;
                    }

                    data.obats = selectedObats.map(o => ({
                        obat_id: o.id,
                        tgl_mulai: o.tgl_mulai,
                        jam_mulai: o.jam_mulai,
                        frekuensi_per_hari: parseInt(o.frekuensi_per_hari),
                        catatan_dosis: o.catatan_dosis || null,
                    }));
                }

                const url = CONFIG.IS_EDIT ? CONFIG.ROUTES.UPDATE : CONFIG.ROUTES.STORE;
                const csrfToken = $('input[name=_token]').val() || $('meta[name=csrf-token]').attr('content');

                try {
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
                            // Handle obats.*.field error
                            if (field.startsWith('obats.')) {
                                const match = field.match(/obats\.(\d+)\.(\w+)/);
                                if (match) {
                                    const idx = parseInt(match[1]);
                                    const subField = match[2];
                                    const obat = selectedObats[idx];
                                    if (obat) {
                                        $(`tr[data-id="${obat.id}"] [data-field="${subField}"]`)
                                            .addClass('is-invalid');
                                    }
                                }
                                $('#obats_error').text(messages[0]);
                                return;
                            }

                            const $field = $('[name="' + field + '"], #' + field);
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
        #obatSearchDropdown {
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

        #obatSearchDropdown .dropdown-item {
            padding: 8px 16px;
            border-bottom: 1px solid #f1f3f5;
            white-space: normal;
        }

        #obatSearchDropdown .dropdown-item:last-child {
            border-bottom: none;
        }

        #obatSearchDropdown .dropdown-item:hover {
            background-color: #e7f1ff;
        }

        #emptyObatPlaceholder {
            background-color: #f8f9fa;
        }

        #obatTable .field-row.is-invalid {
            border-color: #dc3545;
        }

        #obatTable .field-row {
            font-size: 0.875rem;
        }
    </style>
@endpush
