@extends('layouts.app')

@section('title', 'Detail User')

@section('page-header')
    <nav aria-label="breadcrumb" class="small">
        <ol class="breadcrumb mb-2">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('master-user.index') }}" class="text-decoration-none">Master User</a>
            </li>
            <li class="breadcrumb-item active" id="bcEntityName">Detail</li>
        </ol>
    </nav>
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="ri ri-user-line text-primary me-2"></i>
                <span id="entityName">Detail User</span>
            </h4>
            <div class="text-muted small" id="entityStatus">
                <span class="spinner-border spinner-border-sm" role="status"></span>
            </div>
        </div>

        <div class="d-flex gap-2" id="headerActions" style="display:none !important;">
            {{-- Filled via JS --}}
        </div>
    </div>
@endsection

@section('content')

    @php
        $isSuper = auth()->user()->isSuperadmin();
    @endphp

    <div class="row g-4">
        {{-- ========== KOLOM KIRI ========== --}}
        <div class="col-lg-4">
            <x-card>
                <div class="text-center mb-3" id="avatarWrapper">
                    <span class="spinner-border spinner-border-sm" role="status"></span>
                </div>

                <hr>

                <table class="table table-borderless table-sm mb-0">
                    <tr>
                        <td class="text-muted small">Username</td>
                        <td class="fw-semibold"><code id="detail-username">-</code></td>
                    </tr>
                    <tr>
                        <td class="text-muted small">WhatsApp</td>
                        <td class="fw-semibold" id="detail-whatsapp">-</td>
                    </tr>
                    <tr>
                        <td class="text-muted small">Status</td>
                        <td id="detail-status">-</td>
                    </tr>
                </table>
            </x-card>

            <div class="mt-3">
                <x-card title="Aktivitas" icon="ri-time-line">
                    <div class="small">
                        <div class="text-muted">Bergabung sejak</div>
                        <div class="fw-semibold mb-0" id="detail-created_at">-</div>
                    </div>
                </x-card>
            </div>
        </div>

        {{-- ========== KOLOM KANAN ========== --}}
        <div class="col-lg-8">
            <x-card title="Informasi Akun" icon="ri-shield-user-line">
                <x-detail-field label="Nama Lengkap" icon="ri-user-line">
                    <p class="form-control-plaintext mb-0" id="detail-name">-</p>
                </x-detail-field>

                <x-detail-field label="Role / Hak Akses" icon="ri-vip-crown-line">
                    <p class="form-control-plaintext mb-0" id="detail-role">-</p>
                </x-detail-field>

                <x-detail-field label="ID User" icon="ri-key-line" :class="'mb-0'">
                    <p class="form-control-plaintext mb-0">
                        <code class="small" id="detail-id">-</code>
                    </p>
                </x-detail-field>
            </x-card>

            {{-- ========== BIODATA (kalau ada) ========== --}}
            <div class="mt-3" id="biodataSection" style="display:none;">
                <x-card title="Biodata" icon="ri-id-card-line">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <x-detail-field label="NIK" icon="ri-fingerprint-line">
                                <p class="form-control-plaintext mb-0"><code id="detail-nik">-</code></p>
                            </x-detail-field>
                        </div>
                        <div class="col-md-6">
                            <x-detail-field label="No. KK" icon="ri-team-line">
                                <p class="form-control-plaintext mb-0"><code id="detail-no_kk">-</code></p>
                            </x-detail-field>
                        </div>
                        <div class="col-md-6">
                            <x-detail-field label="Jenis Kelamin" icon="ri-user-line">
                                <p class="form-control-plaintext mb-0" id="detail-jenis_kelamin">-</p>
                            </x-detail-field>
                        </div>
                        <div class="col-md-6">
                            <x-detail-field label="Tempat, Tanggal Lahir" icon="ri-cake-line">
                                <p class="form-control-plaintext mb-0" id="detail-ttl">-</p>
                            </x-detail-field>
                        </div>
                        <div class="col-12">
                            <x-detail-field label="Alamat Lengkap" icon="ri-map-pin-line" :class="'mb-0'">
                                <p class="form-control-plaintext mb-0" id="detail-alamat">-</p>
                            </x-detail-field>
                        </div>
                    </div>
                </x-card>
            </div>

            @if ($isSuper)
                {{-- SECTION PERMISSION DETAIL (superadmin only) --}}
                <div class="mt-3">
                    <x-card title="Hak Akses Detail" icon="ri-key-2-line">
                        <x-slot:headerActions>
                            <button type="button" class="btn btn-outline-warning btn-sm" id="btnEditPermissions"
                                style="display:none;">
                                <i class="ri ri-edit-line me-1"></i> Atur Hak Akses
                            </button>
                        </x-slot:headerActions>

                        <div class="row g-2 mb-3" id="permStatsBar" style="display:none;">
                            <div class="col-md-4">
                                <div class="border rounded p-2 text-center">
                                    <div class="small text-muted">Dari Role (auto)</div>
                                    <div class="fw-bold text-primary fs-4" id="statRoleCount">0</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border rounded p-2 text-center">
                                    <div class="small text-muted">Custom Direct</div>
                                    <div class="fw-bold text-warning fs-4" id="statDirectCount">0</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border rounded p-2 text-center bg-success-subtle border-success">
                                    <div class="small text-muted">Total Effective</div>
                                    <div class="fw-bold text-success fs-4" id="statTotalCount">0</div>
                                </div>
                            </div>
                        </div>

                        <div id="permListWrapper">
                            <div class="text-center py-4 text-muted">
                                <span class="spinner-border spinner-border-sm" role="status"></span>
                                <div class="small mt-2">Memuat data permission...</div>
                            </div>
                        </div>
                    </x-card>
                </div>
            @else
                <div class="mt-3">
                    <x-card title="Hak Akses" icon="ri-key-line">
                        <p class="text-muted mb-0">
                            Hak akses user berdasarkan role <strong id="detail-role-summary">-</strong>.
                            <br>
                            <small>Hubungi superadmin untuk mengubah hak akses.</small>
                        </p>
                    </x-card>
                </div>
            @endif
        </div>
    </div>

    {{-- ========== MODAL RESET PASSWORD ========== --}}
    <div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">
                        <i class="ri ri-lock-password-line text-warning me-2"></i>
                        Reset Password User
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="resetPasswordForm" novalidate>
                    @csrf
                    <div class="modal-body">
                        <div class="alert alert-warning small">
                            <i class="ri ri-information-line me-1"></i>
                            Anda akan mereset password untuk user <strong id="resetPasswordUserName">-</strong>.
                            Password lama akan diganti dengan yang baru.
                        </div>

                        <div class="mb-3">
                            <label for="resetPassword_password" class="form-label form-label-required">Password
                                Baru</label>
                            <input type="password" id="resetPassword_password" name="password" class="form-control"
                                placeholder="Min. 8 karakter" autocomplete="new-password" required>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="mb-0">
                            <label for="resetPassword_password_confirmation"
                                class="form-label form-label-required">Konfirmasi Password</label>
                            <input type="password" id="resetPassword_password_confirmation" name="password_confirmation"
                                class="form-control" placeholder="Ulangi password baru" autocomplete="new-password"
                                required>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning fw-semibold" id="btnSubmitResetPassword">
                            <span class="spinner-border spinner-border-sm d-none me-2"></span>
                            <i class="ri ri-save-line me-1"></i> Reset Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        window.whenKesehatanReady(function() {
            'use strict';

            const CONFIG = {
                ID: '{{ $id }}',
                ID_LOGGED: '{{ auth()->id() }}',
                IS_SUPER: {{ auth()->user()->isSuperadmin() ? 'true' : 'false' }},
                ROUTES: {
                    INDEX: '{{ route('master-user.index') }}',
                    EDIT: '{{ route('master-user.edit', $id) }}',
                    DESTROY: '{{ route('master-user.destroy', $id) }}',
                    SHOW_DATA: '{{ route('master-user.show-data', $id) }}',
                    PERMS_GET: '{{ route('master-user.permissions.get', $id) }}',
                    RESET_PASSWORD: '{{ route('master-user.reset-password', $id) }}',
                },
            };

            const ROLE_BADGES = {
                superadmin: '<span class="badge bg-danger-subtle text-danger px-3 py-2"><i class="ri ri-shield-star-line"></i> Superadmin</span>',
                admin: '<span class="badge bg-warning-subtle text-warning px-3 py-2"><i class="ri ri-shield-user-line"></i> Admin</span>',
                pmo: '<span class="badge bg-info-subtle text-info px-3 py-2"><i class="ri ri-team-line"></i> PMO</span>',
                pasien: '<span class="badge bg-primary-subtle text-primary px-3 py-2"><i class="ri ri-user-heart-line"></i> Pasien</span>',
                pengunjung: '<span class="badge bg-secondary-subtle text-secondary px-3 py-2"><i class="ri ri-user-line"></i> Pengunjung</span>',
            };

            const STATUS_BADGES = {
                active: '<span class="badge bg-success-subtle text-success px-3 py-2"><i class="ri ri-check-line"></i> Aktif</span>',
                inactive: '<span class="badge bg-danger-subtle text-danger px-3 py-2"><i class="ri ri-close-line"></i> Nonaktif</span>',
            };

            const JK_LABELS = {
                L: 'Laki-laki',
                P: 'Perempuan'
            };

            const formatDate = (iso) => {
                if (!iso) return '-';
                const d = new Date(iso);
                return d.toLocaleDateString('id-ID', {
                    day: 'numeric',
                    month: 'long',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                });
            };

            const formatBirthDate = (iso) => {
                if (!iso) return '';
                const d = new Date(iso);
                return d.toLocaleDateString('id-ID', {
                    day: 'numeric',
                    month: 'long',
                    year: 'numeric'
                });
            };

            const formatAlamat = (b) => {
                if (!b) return '-';
                const parts = [
                    b.alamat_jalan,
                    b.alamat_rt ? 'RT ' + b.alamat_rt : null,
                    b.alamat_rw ? 'RW ' + b.alamat_rw : null,
                    b.alamat_dusun,
                    b.alamat_desa,
                    b.alamat_kecamatan,
                    b.alamat_kabupaten,
                    b.alamat_provinsi,
                    b.alamat_kodepos,
                ].filter(Boolean);
                return parts.length > 0 ? parts.join(', ') : '-';
            };

            let userData = null;

            // LOAD DATA
            $.ajax({
                url: CONFIG.ROUTES.SHOW_DATA,
                method: 'GET',
            }).done(function(d) {
                userData = d;

                const isMe = d.id === CONFIG.ID_LOGGED;

                // Header
                $('#entityName').html(d.name + (isMe ?
                    ' <span class="badge bg-secondary text-white">Anda</span>' : ''));
                $('#bcEntityName').text(d.name);
                $('#entityStatus').html(ROLE_BADGES[d.role] + ' <span class="ms-2">' + (d.is_active ?
                    '✓ Aktif' : '⊘ Nonaktif') + '</span>');

                // Avatar besar
                const initials = d.name.charAt(0).toUpperCase();
                $('#avatarWrapper').html(`
            <div class="avatar bg-primary text-white mx-auto mb-2" style="width:100px;height:100px;font-size:2.5rem;">
                ${initials}
            </div>
            <h5 class="fw-bold mb-1">${d.name}</h5>
            <small class="text-muted"><i class="ri ri-whatsapp-line"></i> ${d.whatsapp_number || '-'}</small>
        `);

                // Detail kiri
                $('#detail-username').text(d.username || '-');
                $('#detail-whatsapp').text(d.whatsapp_number || '-');
                $('#detail-status').html(d.is_active ? STATUS_BADGES.active : STATUS_BADGES.inactive);
                $('#detail-created_at').text(formatDate(d.created_at));

                // Detail kanan
                $('#detail-name').text(d.name);
                $('#detail-role').html(ROLE_BADGES[d.role] || d.role);
                $('#detail-id').text(d.id);

                $('#detail-role-summary').text(d.role_label || d.role);

                // BIODATA section
                if (d.biodata) {
                    const b = d.biodata;
                    $('#detail-nik').text(b.nik || '-');
                    $('#detail-no_kk').text(b.no_kk || '-');
                    $('#detail-jenis_kelamin').text(JK_LABELS[b.jenis_kelamin] || '-');

                    let ttl = '-';
                    if (b.tempat_lahir || b.tanggal_lahir) {
                        ttl = (b.tempat_lahir || '-') + (b.tanggal_lahir ? ', ' + formatBirthDate(b
                            .tanggal_lahir) : '');
                    }
                    $('#detail-ttl').text(ttl);

                    $('#detail-alamat').text(formatAlamat(b));
                    $('#biodataSection').show();
                }

                renderHeaderActions(d);

                if (CONFIG.IS_SUPER) {
                    loadPermissions(d, isMe);
                }
            }).fail(function(xhr) {
                Swal.fire({
                    title: 'Error!',
                    text: xhr.responseJSON?.message || 'Gagal memuat data',
                    icon: 'error',
                }).then(() => window.location.href = CONFIG.ROUTES.INDEX);
            });

            function renderHeaderActions(d) {
                const actions = [];
                const isMe = d.id === CONFIG.ID_LOGGED;

                const canManage = (() => {
                    if (CONFIG.IS_SUPER) return true;
                    if (isMe) return true;
                    return ['pmo', 'pasien', 'pengunjung'].includes(d.role);
                })();

                if (canManage) {
                    @can('master-user.edit')
                        actions.push(
                            `<a href="${CONFIG.ROUTES.EDIT}" class="btn btn-outline-warning btn-sm"><i class="ri ri-pencil-line me-1"></i> Edit</a>`
                            );

                        // Tombol Reset Password: tidak untuk diri sendiri
                        if (!isMe) {
                            actions.push(
                                `<button class="btn btn-outline-secondary btn-sm" id="btnOpenResetPassword"><i class="ri ri-lock-password-line me-1"></i> Reset Password</button>`
                                );
                        }
                    @endcan

                    @can('master-user.delete')
                        if (!isMe) {
                            actions.push(
                                `<button class="btn btn-outline-danger btn-sm" id="btnDelete"><i class="ri ri-delete-bin-line me-1"></i> Hapus</button>`
                                );
                        }
                    @endcan
                }

                if (actions.length) {
                    $('#headerActions').html(actions.join('')).attr('style', 'display:flex !important;');
                }
            }

            async function loadPermissions(d, isMe) {
                try {
                    const res = await $.ajax({
                        url: CONFIG.ROUTES.PERMS_GET,
                        method: 'GET'
                    });
                    const data = res.data;
                    const summary = data.summary;

                    $('#statRoleCount').text(summary.role_count);
                    $('#statDirectCount').text(summary.direct_count);
                    $('#statTotalCount').text(summary.effective_count);
                    $('#permStatsBar').show();

                    if (!isMe) {
                        $('#btnEditPermissions').show().on('click', function() {
                            window.location.href = CONFIG.ROUTES.EDIT;
                        });
                    }

                    renderPermissionTree(data.grouped_permissions);
                } catch (e) {
                    $('#permListWrapper').html(
                        '<div class="alert alert-warning small">Gagal memuat data permission.</div>');
                }
            }

            function renderPermissionTree(groups) {
                const filteredGroups = groups.filter(g => g.permissions.some(p => p.has));

                if (filteredGroups.length === 0) {
                    $('#permListWrapper').html(`
                <div class="text-center py-4 text-muted small">
                    <i class="ri ri-lock-line fs-3 d-block mb-2"></i>
                    User ini tidak memiliki permission apapun.
                </div>
            `);
                    return;
                }

                const html = filteredGroups.map(group => {
                    const userPerms = group.permissions.filter(p => p.has);
                    const itemsHtml = userPerms.map(p => `
                <span class="badge bg-light text-dark border me-1 mb-1" style="font-weight:500;">
                    <i class="ri ri-checkbox-circle-fill text-success me-1"></i>
                    <code style="font-size:0.7rem;">${p.action}</code>
                    <span class="text-muted ms-1">${p.label}</span>
                </span>
            `).join('');

                    return `
                <div class="perm-show-group mb-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="ri ${group.icon} text-${group.color} fs-5"></i>
                        <strong class="small">${group.label}</strong>
                        <span class="badge bg-secondary-subtle text-secondary small">${userPerms.length}/${group.permissions.length}</span>
                    </div>
                    <div class="ps-4">${itemsHtml}</div>
                </div>
            `;
                }).join('');

                $('#permListWrapper').html(html);
            }

            // ============ DELETE ============
            $(document).on('click', '#btnDelete', function() {
                Swal.fire({
                    title: 'Hapus user?',
                    html: 'User <strong>' + (userData?.name || '') +
                        '</strong> akan dihapus.<br><small class="text-danger">Tindakan ini tidak bisa dibatalkan.</small>',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, hapus!',
                    cancelButtonText: 'Batal',
                    customClass: {
                        confirmButton: 'btn btn-danger me-2',
                        cancelButton: 'btn btn-secondary'
                    },
                    buttonsStyling: false,
                }).then(function(result) {
                    if (!result.isConfirmed) return;

                    $.ajax({
                            url: CONFIG.ROUTES.DESTROY,
                            method: 'DELETE'
                        })
                        .done(function(res) {
                            Swal.fire({
                                    title: 'Berhasil!',
                                    text: res.message,
                                    icon: 'success',
                                    timer: 2000,
                                    showConfirmButton: false
                                })
                                .then(() => window.location.href = CONFIG.ROUTES.INDEX);
                        })
                        .fail(function(xhr) {
                            Swal.fire('Gagal!', xhr.responseJSON?.message ||
                                'Terjadi kesalahan', 'error');
                        });
                });
            });

            // ============ RESET PASSWORD ============
            const resetModalEl = document.getElementById('resetPasswordModal');
            const resetModal = new bootstrap.Modal(resetModalEl);

            $(document).on('click', '#btnOpenResetPassword', function() {
                $('#resetPasswordUserName').text(userData?.name || '-');
                $('#resetPassword_password, #resetPassword_password_confirmation').val('');
                $('#resetPasswordForm .is-invalid').removeClass('is-invalid');
                $('#resetPasswordForm .invalid-feedback').text('');
                resetModal.show();
            });

            $('#resetPasswordForm').on('submit', function(e) {
                e.preventDefault();

                const $btn = $('#btnSubmitResetPassword');
                $btn.prop('disabled', true).find('.spinner-border').removeClass('d-none');

                $('#resetPasswordForm .is-invalid').removeClass('is-invalid');
                $('#resetPasswordForm .invalid-feedback').text('');

                $.ajax({
                    url: CONFIG.ROUTES.RESET_PASSWORD,
                    method: 'POST',
                    data: {
                        _token: $('input[name=_token]').val(),
                        password: $('#resetPassword_password').val(),
                        password_confirmation: $('#resetPassword_password_confirmation').val(),
                    },
                }).done(function(res) {
                    resetModal.hide();
                    Swal.fire({
                        title: 'Berhasil!',
                        text: res.message,
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false,
                    });
                }).fail(function(xhr) {
                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        Object.entries(xhr.responseJSON.errors).forEach(function([field,
                        messages]) {
                            const $field = $('#resetPassword_' + field);
                            $field.addClass('is-invalid');
                            $field.siblings('.invalid-feedback').text(messages[0]);
                        });
                    } else {
                        Swal.fire('Gagal!', xhr.responseJSON?.message || 'Terjadi kesalahan',
                            'error');
                    }
                }).always(function() {
                    $btn.prop('disabled', false).find('.spinner-border').addClass('d-none');
                });
            });
        });
    </script>

    <style>
        .perm-show-group {
            padding: 12px;
            border-radius: 8px;
            background-color: #fafbfc;
            border: 1px solid #f0f1f3;
        }
    </style>
@endpush
