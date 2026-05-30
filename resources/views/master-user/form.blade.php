@extends('layouts.app')

@section('title', isset($id) ? 'Edit User' : 'Tambah User')

@section('page-header')
    <nav aria-label="breadcrumb" class="small">
        <ol class="breadcrumb mb-2">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('master-user.index') }}" class="text-decoration-none">Master User</a>
            </li>
            <li class="breadcrumb-item active">{{ isset($id) ? 'Edit' : 'Tambah' }}</li>
        </ol>
    </nav>
    <div>
        <h4 class="fw-bold mb-1" id="formTitle">{{ isset($id) ? 'Edit User' : 'Tambah User Baru' }}</h4>
        <small class="text-muted">
            @if (!isset($id))
                Buat akun user baru. Field biodata wajib diisi untuk role Pasien & PMO.
            @endif
        </small>
    </div>
@endsection

@section('content')

    @php
        $availableRoles = \App\Services\UserService::getAvailableRoles();
        $isSuper = auth()->user()->isSuperadmin();
    @endphp

    <form id="userForm" novalidate>
        @csrf
        @if (isset($id))
            @method('PUT')
            <input type="hidden" id="userId" value="{{ $id }}">
        @endif

        <div class="row g-4">
            {{-- ========== KOLOM KIRI ========== --}}
            <div class="col-lg-8">

                {{-- Informasi Akun --}}
                <x-card title="Informasi Akun" icon="ri-shield-user-line">
                    <div class="mb-3">
                        <label for="name" class="form-label form-label-required">Nama Lengkap</label>
                        <input type="text" id="name" name="name" class="form-control"
                            placeholder="contoh: Budi Santoso" required>
                        <div class="invalid-feedback"></div>
                        <small class="text-muted">Nama digunakan untuk login. Harus unik.</small>
                    </div>

                    <div class="mb-3">
                        <label for="whatsapp_number" class="form-label form-label-required">No. WhatsApp</label>
                        <input type="text" id="whatsapp_number" name="whatsapp_number" class="form-control"
                            placeholder="08xxxxxxxxxx" required>
                        <div class="invalid-feedback"></div>
                        <small class="text-muted">Bisa juga untuk login. Untuk pengingat via WhatsApp.</small>
                    </div>

                    @if (isset($id))
                        <div class="mb-0">
                            <label class="form-label">Username (auto-generated)</label>
                            <input type="text" id="usernameDisplay" class="form-control bg-light" readonly disabled>
                            <small class="text-muted">Username dibuat otomatis dari nama. Tidak bisa diubah.</small>
                        </div>
                    @endif
                </x-card>

                <div class="mt-3"></div>

                {{-- Password --}}
                <x-card title="Password" icon="ri-lock-line">
                    @if (isset($id))
                        <div class="alert alert-info small mb-3">
                            <i class="ri ri-information-line me-1"></i>
                            Kosongkan password jika tidak ingin mengubah password user.
                        </div>
                    @endif

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="password"
                                class="form-label {{ isset($id) ? '' : 'form-label-required' }}">Password</label>
                            <input type="password" id="password" name="password" class="form-control"
                                placeholder="Min. 8 karakter" autocomplete="new-password">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="password_confirmation"
                                class="form-label {{ isset($id) ? '' : 'form-label-required' }}">Konfirmasi Password</label>
                            <input type="password" id="password_confirmation" name="password_confirmation"
                                class="form-control" placeholder="Ulangi password" autocomplete="new-password">
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                </x-card>

                <div class="mt-3"></div>

                {{-- Biodata (dinamis - muncul kalau role pasien/pmo) --}}
                <x-card title="Biodata" icon="ri-id-card-line" id="biodataCard">
                    <div id="biodataAlert" class="alert alert-warning small mb-3" style="display:none;">
                        <i class="ri ri-information-line me-1"></i>
                        Pilih role <strong>Pasien</strong> atau <strong>PMO</strong> untuk menampilkan field biodata.
                    </div>

                    <div id="biodataFields">
                        <div class="mb-3">
                            <label for="nik" class="form-label form-label-required">NIK</label>
                            <input type="text" id="nik" name="nik" class="form-control" maxlength="16"
                                minlength="16" inputmode="numeric" pattern="[0-9]{16}" placeholder="16 digit NIK KTP">
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label form-label-required">Jenis Kelamin</label>
                                <div class="d-flex gap-2">
                                    <div class="form-check flex-fill">
                                        <input class="form-check-input" type="radio" name="jenis_kelamin"
                                            id="jk-L" value="L">
                                        <label class="form-check-label" for="jk-L">Laki-laki</label>
                                    </div>
                                    <div class="form-check flex-fill">
                                        <input class="form-check-input" type="radio" name="jenis_kelamin"
                                            id="jk-P" value="P">
                                        <label class="form-check-label" for="jk-P">Perempuan</label>
                                    </div>
                                </div>
                                <div class="invalid-feedback d-block" id="jk-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label for="tanggal_lahir" class="form-label form-label-required">Tanggal Lahir</label>
                                <input type="date" id="tanggal_lahir" name="tanggal_lahir" class="form-control"
                                    max="{{ date('Y-m-d') }}">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="tempat_lahir" class="form-label form-label-required">Tempat Lahir</label>
                            <input type="text" id="tempat_lahir" name="tempat_lahir" class="form-control"
                                maxlength="50" placeholder="contoh: Malang">
                            <div class="invalid-feedback"></div>
                        </div>

                        {{-- Detail tambahan (collapsible) --}}
                        <button class="btn btn-outline-secondary btn-sm w-100 mb-3" type="button"
                            data-bs-toggle="collapse" data-bs-target="#detailBiodata">
                            <i class="ri ri-add-line"></i> Lengkapi Alamat & No. KK (Opsional)
                        </button>

                        <div class="collapse" id="detailBiodata">
                            <div class="card card-body bg-light border-0 mb-0">
                                <div class="mb-3">
                                    <label for="no_kk" class="form-label">No. KK</label>
                                    <input type="text" id="no_kk" name="no_kk" class="form-control"
                                        maxlength="16" minlength="16" inputmode="numeric"
                                        placeholder="16 digit Nomor Kartu Keluarga">
                                    <div class="invalid-feedback"></div>
                                </div>

                                <div class="mb-3">
                                    <label for="alamat_jalan" class="form-label">Alamat Jalan</label>
                                    <input type="text" id="alamat_jalan" name="alamat_jalan" class="form-control"
                                        placeholder="Jl. Merpati No 10">
                                </div>

                                <div class="row g-2 mb-3">
                                    <div class="col-3">
                                        <label for="alamat_rt" class="form-label">RT</label>
                                        <input type="text" id="alamat_rt" name="alamat_rt" class="form-control"
                                            maxlength="5" placeholder="003">
                                    </div>
                                    <div class="col-3">
                                        <label for="alamat_rw" class="form-label">RW</label>
                                        <input type="text" id="alamat_rw" name="alamat_rw" class="form-control"
                                            maxlength="5" placeholder="007">
                                    </div>
                                    <div class="col-6">
                                        <label for="alamat_kodepos" class="form-label">Kode Pos</label>
                                        <input type="text" id="alamat_kodepos" name="alamat_kodepos"
                                            class="form-control" maxlength="10" placeholder="40123">
                                    </div>
                                </div>

                                <div class="row g-2 mb-3">
                                    <div class="col-md-6">
                                        <label for="alamat_dusun" class="form-label">Dusun</label>
                                        <input type="text" id="alamat_dusun" name="alamat_dusun" class="form-control"
                                            maxlength="100">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="alamat_desa" class="form-label">Desa/Kelurahan</label>
                                        <input type="text" id="alamat_desa" name="alamat_desa" class="form-control"
                                            maxlength="100">
                                    </div>
                                </div>

                                <div class="row g-2 mb-3">
                                    <div class="col-md-6">
                                        <label for="alamat_kecamatan" class="form-label">Kecamatan</label>
                                        <input type="text" id="alamat_kecamatan" name="alamat_kecamatan"
                                            class="form-control" maxlength="100">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="alamat_kabupaten" class="form-label">Kabupaten/Kota</label>
                                        <input type="text" id="alamat_kabupaten" name="alamat_kabupaten"
                                            class="form-control" maxlength="100">
                                    </div>
                                </div>

                                <div class="mb-0">
                                    <label for="alamat_provinsi" class="form-label">Provinsi</label>
                                    <input type="text" id="alamat_provinsi" name="alamat_provinsi"
                                        class="form-control" maxlength="100">
                                </div>
                            </div>
                        </div>
                    </div>
                </x-card>
            </div>

            {{-- ========== KOLOM KANAN ========== --}}
            <div class="col-lg-4">
                <x-card title="Role & Status" icon="ri-shield-user-line">
                    <div class="mb-3">
                        <label for="role" class="form-label form-label-required">Role</label>
                        <select id="role" name="role" class="form-select" required>
                            <option value="">-- Pilih Role --</option>
                            @foreach ($availableRoles as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback"></div>
                        <small class="text-muted mt-1 d-block" id="roleHint"></small>
                    </div>

                    <div class="mb-0">
                        <label class="form-label">Status Akun</label>
                        <div class="form-check form-switch">
                            <input type="hidden" name="is_active" value="0">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active"
                                value="1" checked>
                            <label class="form-check-label" for="is_active">
                                <span id="statusLabel">Akun Aktif</span>
                            </label>
                        </div>
                        <small class="text-muted">User yang nonaktif tidak bisa login.</small>
                    </div>
                </x-card>

                <div class="mt-3">
                    <x-card class="border-start border-4 border-info">
                        <h6 class="fw-bold mb-2 small">
                            <i class="ri ri-information-line text-info me-1"></i> Hak Akses
                        </h6>
                        <p class="small text-muted mb-2" id="permissionPreview">
                            Pilih role untuk melihat preview hak akses yang akan diberikan.
                        </p>
                    </x-card>
                </div>

                @if ($isSuper && isset($id))
                    <div class="mt-3" id="permissionEditorWrapper" style="display:none;">
                        <x-card class="border-start border-4 border-warning">
                            <h6 class="fw-bold mb-2 small">
                                <i class="ri ri-key-2-line text-warning me-1"></i> Hak Akses Custom (Superadmin)
                            </h6>
                            <p class="small text-muted mb-3">
                                Atur permission user secara penuh. Bisa add atau remove permission yang ada di role.
                            </p>
                            <div class="d-flex flex-column gap-2">
                                <button type="button" class="btn btn-warning btn-sm" id="btnOpenPermissionModal">
                                    <i class="ri ri-key-2-line me-1"></i> Atur Hak Akses
                                </button>
                                <small class="text-muted" id="permissionStat">
                                    <span class="spinner-border spinner-border-sm" role="status"></span> Loading...
                                </small>
                            </div>
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
            <a href="{{ route('master-user.index') }}" class="btn btn-outline-secondary">Batal</a>
        </div>
    </form>

    {{-- ===== MODAL PERMISSION (sama seperti sebelumnya) ===== --}}
    @if ($isSuper && isset($id))
        <div class="modal fade" id="permissionModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-light">
                        <div>
                            <h5 class="modal-title fw-bold mb-1">
                                <i class="ri ri-key-2-line text-warning me-2"></i>
                                Pengaturan Hak Akses
                            </h5>
                            <small class="text-muted">User: <span id="permModalUserName">-</span></small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-0">
                        <div class="alert alert-info border-0 rounded-0 mb-0 px-4 py-3">
                            <div class="row g-3 align-items-center">
                                <div class="col-md-3">
                                    <div class="small text-muted">Role User</div>
                                    <div class="fw-bold" id="permModalRole">-</div>
                                </div>
                                <div class="col-md-3">
                                    <div class="small text-muted">Mode</div>
                                    <div class="fw-bold" id="permModalMode">-</div>
                                </div>
                                <div class="col-md-3">
                                    <div class="small text-muted">Effective Permissions</div>
                                    <div class="fw-bold text-success fs-5"><span id="permModalTotal">0</span></div>
                                </div>
                                <div class="col-md-3">
                                    <div class="small text-muted" id="permModalRefLabel">Default Role</div>
                                    <div class="fw-bold text-secondary"><span id="permModalRefCount">0</span> permissions
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="p-3 border-bottom bg-white sticky-top" style="top:0;z-index:5;">
                            <div class="row g-2">
                                <div class="col-md-8">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text"><i class="ri ri-search-line"></i></span>
                                        <input type="text" id="permSearch" class="form-control"
                                            placeholder="Cari permission...">
                                    </div>
                                </div>
                                <div class="col-md-4 d-flex justify-content-end gap-2">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btnExpandAll">
                                        <i class="ri ri-expand-up-down-line"></i> Buka Semua
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btnCollapseAll">
                                        <i class="ri ri-contract-up-down-line"></i> Tutup Semua
                                    </button>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap gap-3 mt-2 small text-muted">
                                <span><i class="ri ri-checkbox-fill text-success"></i> Granted</span>
                                <span><i class="ri ri-checkbox-blank-line"></i> Belum diberikan</span>
                            </div>
                        </div>
                        <div class="p-3" id="permTreeWrapper">
                            <div class="text-center py-5 text-muted">
                                <span class="spinner-border" role="status"></span>
                                <div class="mt-2">Memuat data permission...</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-outline-danger me-auto" id="btnResetPermissions">
                            <i class="ri ri-refresh-line me-1"></i> Reset ke Default Role
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="button" class="btn btn-warning fw-semibold" id="btnSavePermissions">
                            <span class="spinner-border spinner-border-sm d-none me-2"></span>
                            <i class="ri ri-save-line me-1"></i> Simpan Hak Akses
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

@endsection

@push('scripts')
    <script>
        window.whenKesehatanReady(function() {
            'use strict';

            const CONFIG = {
                IS_EDIT: {{ isset($id) ? 'true' : 'false' }},
                ID: '{{ $id ?? '' }}',
                ID_LOGGED: '{{ auth()->id() }}',
                IS_SUPER: {{ auth()->user()->isSuperadmin() ? 'true' : 'false' }},
                ROUTES: {
                    INDEX: '{{ route('master-user.index') }}',
                    STORE: '{{ route('master-user.store') }}',
                    UPDATE: '{{ isset($id) ? route('master-user.update', $id) : '' }}',
                    SHOW_DATA: '{{ isset($id) ? route('master-user.show-data', $id) : '' }}',
                    PERMS_GET: '{{ isset($id) ? route('master-user.permissions.get', $id) : '' }}',
                    PERMS_UPDATE: '{{ isset($id) ? route('master-user.permissions.update', $id) : '' }}',
                    PERMS_RESET: '{{ isset($id) ? route('master-user.permissions.reset', $id) : '' }}',
                },
            };

            const ROLE_HINTS = {
                superadmin: 'Akses penuh ke seluruh sistem.',
                admin: 'Bisa kelola data master, transaksi, konten, dan user pmo/pasien.',
                pmo: 'Pendamping Minum Obat. Pantau pasien binaan.',
                pasien: 'Input jadwal obat & cek gula darah pribadi.',
                pengunjung: 'Hanya bisa lihat halaman publik.',
            };

            const ROLES_WITH_BIODATA = ['pasien', 'pmo'];

            const $form = $('#userForm');
            const $submitBtn = $('#submitBtn');

            // Status toggle
            $('#is_active').on('change', function() {
                $('#statusLabel').text(this.checked ? 'Akun Aktif' : 'Akun Nonaktif');
            });

            // Role change: show/hide biodata + update hint
            $('#role').on('change', function() {
                const val = this.value;
                const hint = ROLE_HINTS[val] || '';
                $('#roleHint').text(hint);
                $('#permissionPreview').text(hint || 'Pilih role untuk melihat preview hak akses.');

                // Toggle biodata fields
                const needsBiodata = ROLES_WITH_BIODATA.includes(val);
                if (needsBiodata) {
                    $('#biodataAlert').hide();
                    $('#biodataFields').show();
                    // Set required pada field utama
                    $('#nik, #tempat_lahir, #tanggal_lahir').prop('required', true);
                    $('input[name=jenis_kelamin]').prop('required', true);
                } else {
                    $('#biodataAlert').show();
                    $('#biodataFields').hide();
                    $('#nik, #tempat_lahir, #tanggal_lahir').prop('required', false);
                    $('input[name=jenis_kelamin]').prop('required', false);
                }
            });

            // Normalize WA input
            $('#whatsapp_number').on('blur', function() {
                let wa = $(this).val().replace(/[\s\+\-\(\)]/g, '');
                if (wa.startsWith('62')) wa = '0' + wa.substr(2);
                $(this).val(wa);
            });

            // EDIT MODE: load data
            if (CONFIG.IS_EDIT) {
                $.ajax({
                    url: CONFIG.ROUTES.SHOW_DATA,
                    method: 'GET',
                }).done(function(data) {
                    $('#formTitle').text('Edit: ' + data.name);
                    $('#name').val(data.name);
                    $('#whatsapp_number').val(data.whatsapp_number || '');
                    $('#usernameDisplay').val(data.username || '-');
                    $('#role').val(data.role).trigger('change');
                    $('#is_active').prop('checked', !!data.is_active).trigger('change');

                    // Populate biodata kalau ada
                    if (data.biodata) {
                        const b = data.biodata;
                        $('#nik').val(b.nik || '');
                        if (b.jenis_kelamin === 'L') $('#jk-L').prop('checked', true);
                        else if (b.jenis_kelamin === 'P') $('#jk-P').prop('checked', true);
                        $('#tempat_lahir').val(b.tempat_lahir || '');
                        $('#tanggal_lahir').val(b.tanggal_lahir ? b.tanggal_lahir.substr(0, 10) : '');
                        $('#no_kk').val(b.no_kk || '');
                        $('#alamat_jalan').val(b.alamat_jalan || '');
                        $('#alamat_rt').val(b.alamat_rt || '');
                        $('#alamat_rw').val(b.alamat_rw || '');
                        $('#alamat_dusun').val(b.alamat_dusun || '');
                        $('#alamat_desa').val(b.alamat_desa || '');
                        $('#alamat_kecamatan').val(b.alamat_kecamatan || '');
                        $('#alamat_kabupaten').val(b.alamat_kabupaten || '');
                        $('#alamat_provinsi').val(b.alamat_provinsi || '');
                        $('#alamat_kodepos').val(b.alamat_kodepos || '');
                    }

                    // Disable role kalau edit diri sendiri
                    const isSelf = data.id === CONFIG.ID_LOGGED;
                    if (isSelf) {
                        $('#role').prop('disabled', true);
                        $('#roleHint').html(
                            '<span class="text-warning">Anda tidak dapat mengubah role akun sendiri.</span>'
                            );
                    }

                    // Permission editor (superadmin only, not for self)
                    if (CONFIG.IS_SUPER && !isSelf) {
                        $('#permissionEditorWrapper').show();
                        PermissionEditor.init(data);
                    }
                }).fail(function(xhr) {
                    Swal.fire({
                        title: 'Error!',
                        text: xhr.responseJSON?.message || 'Gagal memuat data',
                        icon: 'error',
                    }).then(() => window.location.href = CONFIG.ROUTES.INDEX);
                });
            }

            // SUBMIT
            $form.on('submit', function(e) {
                e.preventDefault();

                $form.find('.is-invalid').removeClass('is-invalid');
                $form.find('.invalid-feedback').text('');

                $submitBtn.prop('disabled', true).find('.spinner-border').removeClass('d-none');

                const url = CONFIG.IS_EDIT ? CONFIG.ROUTES.UPDATE : CONFIG.ROUTES.STORE;
                const role = $('#role').val();
                const needsBiodata = ROLES_WITH_BIODATA.includes(role);

                const data = {
                    _token: $('input[name=_token]').val(),
                    name: $('#name').val(),
                    whatsapp_number: $('#whatsapp_number').val(),
                    role: role,
                    is_active: $('#is_active').is(':checked') ? 1 : 0,
                };

                // Password (kalau diisi)
                const password = $('#password').val();
                if (password && password.trim() !== '') {
                    data.password = password;
                    data.password_confirmation = $('#password_confirmation').val();
                }

                // Biodata fields (kalau role pasien/pmo)
                if (needsBiodata) {
                    data.nik = $('#nik').val();
                    data.jenis_kelamin = $('input[name=jenis_kelamin]:checked').val() || '';
                    data.tempat_lahir = $('#tempat_lahir').val();
                    data.tanggal_lahir = $('#tanggal_lahir').val();
                    data.no_kk = $('#no_kk').val();
                    data.alamat_jalan = $('#alamat_jalan').val();
                    data.alamat_rt = $('#alamat_rt').val();
                    data.alamat_rw = $('#alamat_rw').val();
                    data.alamat_dusun = $('#alamat_dusun').val();
                    data.alamat_desa = $('#alamat_desa').val();
                    data.alamat_kecamatan = $('#alamat_kecamatan').val();
                    data.alamat_kabupaten = $('#alamat_kabupaten').val();
                    data.alamat_provinsi = $('#alamat_provinsi').val();
                    data.alamat_kodepos = $('#alamat_kodepos').val();
                }

                if (CONFIG.IS_EDIT) data._method = 'PUT';

                $.ajax({
                        url: url,
                        method: 'POST',
                        data: data
                    })
                    .done(function(res) {
                        Swal.fire({
                            title: 'Berhasil!',
                            text: res.message,
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false,
                        }).then(() => {
                            window.location.href = CONFIG.ROUTES.INDEX;
                        });
                    })
                    .fail(function(xhr) {
                        $submitBtn.prop('disabled', false).find('.spinner-border').addClass('d-none');

                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            let hasDetailError = false;
                            const detailFields = ['no_kk', 'alamat_jalan', 'alamat_rt', 'alamat_rw',
                                'alamat_dusun', 'alamat_desa', 'alamat_kecamatan',
                                'alamat_kabupaten', 'alamat_provinsi', 'alamat_kodepos'
                            ];

                            Object.entries(xhr.responseJSON.errors).forEach(function([field,
                            messages]) {
                                const $field = $('[name="' + field + '"]');
                                $field.addClass('is-invalid');

                                if (field === 'jenis_kelamin') {
                                    $('#jk-feedback').text(messages[0]);
                                } else {
                                    $field.closest(
                                        '.col-md-6, .col-md-3, .col-3, .col-6, .col-md-4, .mb-3, .col-12'
                                        ).find('.invalid-feedback').first().text(messages[
                                        0]);
                                }

                                if (detailFields.includes(field)) hasDetailError = true;
                            });

                            // Auto-expand detail biodata kalau ada error
                            if (hasDetailError) {
                                const collapseEl = document.getElementById('detailBiodata');
                                if (collapseEl) bootstrap.Collapse.getOrCreateInstance(collapseEl)
                                .show();
                            }

                            Swal.fire('Validasi Gagal',
                                'Mohon periksa kembali data yang Anda masukkan.', 'warning');
                        } else {
                            Swal.fire('Gagal!', xhr.responseJSON?.message || 'Terjadi kesalahan',
                                'error');
                        }
                    });
            });

            // ============ PermissionEditor (sama seperti sebelumnya) ============
            const PermissionEditor = {
                modalEl: null,
                modal: null,
                userData: null,
                permData: null,

                init(userData) {
                    this.userData = userData;
                    this.modalEl = document.getElementById('permissionModal');
                    if (!this.modalEl) return;
                    this.modal = new bootstrap.Modal(this.modalEl);

                    this.updateInlineStat();

                    $('#btnOpenPermissionModal').on('click', () => this.open());
                    $('#btnSavePermissions').on('click', () => this.save());
                    $('#btnResetPermissions').on('click', () => this.reset());
                    $('#btnExpandAll').on('click', () => this.expandAll(true));
                    $('#btnCollapseAll').on('click', () => this.expandAll(false));
                    $('#permSearch').on('input', () => this.applyFilter());
                },

                async updateInlineStat() {
                    try {
                        const res = await $.ajax({
                            url: CONFIG.ROUTES.PERMS_GET,
                            method: 'GET'
                        });
                        const payload = res.data || res;
                        const s = payload.summary;
                        if (!s) throw new Error('Invalid response');

                        const modeText = payload.is_overridden ?
                            '<span class="badge bg-warning text-dark">Custom</span>' :
                            '<span class="badge bg-success-subtle text-success">Default Role</span>';

                        $('#permissionStat').html(
                            modeText + ' &middot; ' +
                            '<strong class="text-success">' + s.effective_count +
                            '</strong> permissions'
                        );
                    } catch (e) {
                        $('#permissionStat').html('<span class="text-danger">Gagal memuat info.</span>');
                    }
                },

                async open() {
                    $('#permTreeWrapper').html(
                        '<div class="text-center py-5 text-muted"><span class="spinner-border" role="status"></span><div class="mt-2">Memuat...</div></div>'
                        );
                    this.modal.show();

                    try {
                        const res = await $.ajax({
                            url: CONFIG.ROUTES.PERMS_GET,
                            method: 'GET'
                        });
                        const payload = res.data || res;
                        if (!payload.summary || !payload.grouped_permissions) throw new Error('Invalid');
                        this.permData = payload;
                        this.render();
                    } catch (e) {
                        $('#permTreeWrapper').html(
                            '<div class="alert alert-danger">Gagal memuat data.</div>');
                    }
                },

                render() {
                    const d = this.permData;
                    $('#permModalUserName').text(d.user_name);
                    $('#permModalRole').text(d.user_role_label);

                    const modeBadge = d.is_overridden ?
                        '<span class="badge bg-warning text-dark">Custom Override</span>' :
                        '<span class="badge bg-success-subtle text-success">Default Role</span>';
                    $('#permModalMode').html(modeBadge);
                    $('#permModalTotal').text(d.summary.effective_count);
                    $('#permModalRefCount').text(d.summary.role_count);
                    $('#permModalRefLabel').text('Permission Role ' + d.user_role_label);

                    const renderPermItem = (p, groupId) => `
                <div class="form-check perm-item" data-perm="${p.name}">
                    <input class="form-check-input perm-check" type="checkbox" id="perm-${p.name}" data-name="${p.name}" data-group="${groupId}" ${p.has ? 'checked' : ''}>
                    <label class="form-check-label small ${p.has ? 'fw-semibold' : ''}" for="perm-${p.name}">
                        <code style="font-size:0.75rem;">${p.action}</code>
                        <span class="text-muted ms-1">${p.label}</span>
                    </label>
                </div>
            `;

                    const groupsHtml = d.grouped_permissions.map((group, idx) => {
                        const groupId = `permGroup-${idx}`;
                        const allChecked = group.permissions.every(p => p.has);
                        const grantedCount = group.permissions.filter(p => p.has).length;
                        const itemsCols = group.permissions.map(p =>
                            `<div class="col-md-6 col-lg-4">${renderPermItem(p, groupId)}</div>`
                        ).join('');

                        return `
                    <div class="perm-group mb-3" data-group="${groupId}">
                        <div class="d-flex align-items-center justify-content-between bg-light rounded px-3 py-2 cursor-pointer perm-group-header" data-target="${groupId}-body">
                            <div class="d-flex align-items-center gap-2">
                                <i class="ri ri-arrow-down-s-line perm-group-toggle"></i>
                                <i class="ri ${group.icon} text-${group.color}"></i>
                                <strong class="small">${group.label}</strong>
                                <span class="badge bg-${grantedCount === group.permissions.length ? 'success' : (grantedCount > 0 ? 'warning' : 'secondary')}-subtle text-${grantedCount === group.permissions.length ? 'success' : (grantedCount > 0 ? 'warning' : 'secondary')} small">${grantedCount}/${group.permissions.length}</span>
                            </div>
                            <div class="form-check form-check-inline m-0" onclick="event.stopPropagation()">
                                <input class="form-check-input perm-group-check" type="checkbox" data-group="${groupId}" id="${groupId}-check" ${allChecked ? 'checked' : ''}>
                                <label class="form-check-label small text-muted" for="${groupId}-check">Pilih Semua</label>
                            </div>
                        </div>
                        <div class="perm-group-body p-3" id="${groupId}-body">
                            <div class="row g-2">${itemsCols}</div>
                        </div>
                    </div>
                `;
                    }).join('');

                    $('#permTreeWrapper').html(groupsHtml);

                    // Set indeterminate state
                    d.grouped_permissions.forEach((group, idx) => {
                        const groupId = `permGroup-${idx}`;
                        const allChecked = group.permissions.every(p => p.has);
                        const someChecked = group.permissions.some(p => p.has);
                        if (someChecked && !allChecked) $(`#${groupId}-check`).prop('indeterminate',
                            true);
                    });

                    $('.perm-group-header').off('click').on('click', function(e) {
                        if (e.target.classList.contains('form-check-input')) return;
                        const target = $(this).data('target');
                        $('#' + target).slideToggle(150);
                        $(this).find('.perm-group-toggle').toggleClass(
                            'ri-arrow-down-s-line ri-arrow-right-s-line');
                    });

                    $('.perm-group-check').off('change').on('change', function() {
                        const groupId = $(this).data('group');
                        $(`.perm-check[data-group="${groupId}"]`).prop('checked', this.checked).trigger(
                            'change');
                    });

                    $('.perm-check').off('change').on('change', function() {
                        PermissionEditor.updateGroupCheckState($(this).data('group'));
                    });
                },

                updateGroupCheckState(groupId) {
                    const $checks = $(`.perm-check[data-group="${groupId}"]`);
                    const allChecked = $checks.toArray().every(c => c.checked);
                    const someChecked = $checks.toArray().some(c => c.checked);
                    const $groupCheck = $(`#${groupId}-check`);
                    $groupCheck.prop('checked', allChecked);
                    $groupCheck.prop('indeterminate', someChecked && !allChecked);

                    $checks.each(function() {
                        $(this).siblings('label').toggleClass('fw-semibold', this.checked);
                    });
                },

                applyFilter() {
                    const term = $('#permSearch').val().toLowerCase().trim();
                    $('.perm-group').each(function() {
                        const $group = $(this);
                        let hasMatch = false;
                        $group.find('.perm-item').each(function() {
                            const text = $(this).text().toLowerCase() + ' ' + $(this).data(
                                'perm').toLowerCase();
                            const match = !term || text.includes(term);
                            $(this).closest('.col-md-6').toggle(match);
                            if (match) hasMatch = true;
                        });
                        $group.toggle(hasMatch || !term);
                        if (term && hasMatch) {
                            $group.find('.perm-group-body').show();
                            $group.find('.perm-group-toggle').removeClass('ri-arrow-right-s-line')
                                .addClass('ri-arrow-down-s-line');
                        }
                    });
                },

                expandAll(expand) {
                    $('.perm-group-body').toggle(expand);
                    $('.perm-group-toggle').each(function() {
                        $(this).toggleClass('ri-arrow-down-s-line', expand).toggleClass(
                            'ri-arrow-right-s-line', !expand);
                    });
                },

                async save() {
                    const $btn = $('#btnSavePermissions');
                    $btn.prop('disabled', true).find('.spinner-border').removeClass('d-none');
                    const permissions = $('.perm-check:checked').map((i, el) => $(el).data('name')).get();

                    try {
                        const res = await $.ajax({
                            url: CONFIG.ROUTES.PERMS_UPDATE,
                            method: 'POST',
                            data: {
                                _token: $('input[name=_token]').val(),
                                permissions: permissions
                            },
                        });
                        this.modal.hide();
                        Swal.fire({
                                title: 'Berhasil!',
                                text: res.message,
                                icon: 'success',
                                timer: 1500,
                                showConfirmButton: false
                            })
                            .then(() => this.updateInlineStat());
                    } catch (xhr) {
                        Swal.fire('Gagal!', xhr.responseJSON?.message || 'Terjadi kesalahan', 'error');
                    } finally {
                        $btn.prop('disabled', false).find('.spinner-border').addClass('d-none');
                    }
                },

                async reset() {
                    const result = await Swal.fire({
                        title: 'Reset Permission?',
                        html: 'Permission custom akan dihapus.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, Reset',
                        cancelButtonText: 'Batal',
                        customClass: {
                            confirmButton: 'btn btn-danger me-2',
                            cancelButton: 'btn btn-secondary'
                        },
                        buttonsStyling: false,
                    });
                    if (!result.isConfirmed) return;

                    try {
                        const res = await $.ajax({
                            url: CONFIG.ROUTES.PERMS_RESET,
                            method: 'POST',
                            data: {
                                _token: $('input[name=_token]').val()
                            },
                        });
                        Swal.fire({
                                title: 'Berhasil!',
                                text: res.message,
                                icon: 'success',
                                timer: 1500,
                                showConfirmButton: false
                            })
                            .then(() => {
                                this.open();
                                this.updateInlineStat();
                            });
                    } catch (xhr) {
                        Swal.fire('Gagal!', xhr.responseJSON?.message || 'Terjadi kesalahan', 'error');
                    }
                },
            };
        });
    </script>

    <style>
        .cursor-pointer {
            cursor: pointer;
        }

        .perm-group-header {
            transition: background-color 0.15s;
        }

        .perm-group-header:hover {
            background-color: #f1f3f5 !important;
        }

        .perm-group-toggle {
            transition: transform 0.15s;
            color: #6c757d;
        }

        .perm-item {
            padding: 4px 6px;
            border-radius: 4px;
            transition: background-color 0.15s;
        }

        .perm-item:hover {
            background-color: #f8f9fa;
        }

        .perm-group-body {
            border: 1px solid #f1f3f5;
            border-top: none;
            border-radius: 0 0 6px 6px;
        }

        #permSearch {
            transition: border-color 0.2s;
        }

        #permSearch:focus {
            border-color: var(--kesehatan-primary);
        }
    </style>
@endpush
