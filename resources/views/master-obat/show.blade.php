@extends('layouts.app')

@section('title', 'Detail Obat')

@section('page-header')
    <nav aria-label="breadcrumb" class="small">
        <ol class="breadcrumb mb-2">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('master-obat.index') }}" class="text-decoration-none">Master Obat</a>
            </li>
            <li class="breadcrumb-item active" id="bcEntityName">Detail</li>
        </ol>
    </nav>
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="ri ri-medicine-bottle-line text-primary me-2"></i>
                <span id="entityName">Detail Obat</span>
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

    <div class="row g-4">
        <div class="col-lg-4">
            <x-card>
                <div class="text-center" id="fotoWrapper">
                    <span class="spinner-border spinner-border-sm" role="status"></span>
                </div>

                <hr>

                <table class="table table-borderless table-sm mb-0">
                    <tr>
                        <td class="text-muted small">Dosis</td>
                        <td class="fw-semibold" id="detail-dosis">-</td>
                    </tr>
                    <tr>
                        <td class="text-muted small">Satuan</td>
                        <td class="fw-semibold" id="detail-satuan">-</td>
                    </tr>
                    <tr>
                        <td class="text-muted small">Kategori</td>
                        <td class="fw-semibold" id="detail-kategori">-</td>
                    </tr>
                </table>
            </x-card>

            <div class="mt-3">
                <x-card title="Informasi Pengaju" icon="ri-user-line">
                    <div id="creatorWrapper">
                        <span class="spinner-border spinner-border-sm" role="status"></span>
                    </div>
                </x-card>
            </div>

            <div class="mt-3" id="verifierSection" style="display:none;">
                <x-card>
                    <h6 class="fw-bold mb-3 small text-muted text-uppercase" id="verifierTitle">
                        Info Verifikasi
                    </h6>
                    <div id="verifierWrapper"></div>
                </x-card>
            </div>
        </div>

        <div class="col-lg-8">
            <div id="verifyActionSection" style="display:none;">
                <x-card class="border-start border-4 border-warning mb-3">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div>
                            <h6 class="fw-bold mb-1"><i class="ri ri-time-line text-warning"></i> Menunggu Verifikasi Anda
                            </h6>
                            <small class="text-muted">
                                Sebagai admin, silakan tinjau data obat dan tentukan keputusan.
                            </small>
                        </div>
                        <div class="d-flex gap-2">
                            @can('master-obat.verify')
                                <button type="button" class="btn btn-success fw-semibold" data-bs-toggle="modal"
                                    data-bs-target="#approveModal">
                                    <i class="ri ri-check-line me-1"></i> Approve
                                </button>
                                <button type="button" class="btn btn-danger fw-semibold" data-bs-toggle="modal"
                                    data-bs-target="#rejectModal">
                                    <i class="ri ri-close-line me-1"></i> Reject
                                </button>
                            @endcan
                        </div>
                    </div>
                </x-card>
            </div>

            <x-card title="Detail Informasi" icon="ri-file-text-line">
                <x-detail-field label="Aturan Minum" icon="ri-time-line">
                    <p class="form-control-plaintext mb-0" id="detail-aturan_minum">-</p>
                </x-detail-field>

                <x-detail-field label="Deskripsi" icon="ri-file-list-line">
                    <p class="form-control-plaintext mb-0" id="detail-deskripsi" style="white-space: pre-line;">-</p>
                </x-detail-field>

                <x-detail-field label="Efek Samping" icon="ri-error-warning-line">
                    <p class="form-control-plaintext mb-0" id="detail-efek_samping" style="white-space: pre-line;">-</p>
                </x-detail-field>

                <x-detail-field label="Kontraindikasi" icon="ri-forbid-line" :class="'mb-0'">
                    <p class="form-control-plaintext mb-0" id="detail-kontraindikasi" style="white-space: pre-line;">-</p>
                </x-detail-field>
            </x-card>
        </div>
    </div>

    @can('master-obat.verify')
        <div class="modal fade" id="approveModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header border-0">
                        <h5 class="modal-title fw-bold text-success">
                            <i class="ri ri-check-line me-1"></i> Approve Obat
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted">
                            Anda akan menyetujui obat <strong id="approveObatName">-</strong>.
                        </p>
                        <div>
                            <label class="form-label fw-semibold small">Catatan (opsional)</label>
                            <textarea id="approveCatatan" class="form-control" rows="2"
                                placeholder="contoh: Data sudah valid, sesuai standar."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="button" class="btn btn-success fw-semibold" id="btnApprove">
                            <i class="ri ri-check-line me-1"></i> Ya, Approve
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header border-0">
                        <h5 class="modal-title fw-bold text-danger">
                            <i class="ri ri-close-line me-1"></i> Reject Obat
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted">
                            Anda akan menolak obat <strong id="rejectObatName">-</strong>.
                        </p>
                        <div>
                            <label class="form-label fw-semibold small">
                                Alasan Penolakan <span class="text-danger">*</span>
                            </label>
                            <textarea id="rejectCatatan" class="form-control" rows="3" required
                                placeholder="contoh: Foto kurang jelas, dosis tidak standar"></textarea>
                            <small class="text-muted">Wajib diisi agar pengaju tahu apa yang harus diperbaiki.</small>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="button" class="btn btn-danger fw-semibold" id="btnReject">
                            <i class="ri ri-close-line me-1"></i> Ya, Reject
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endcan

@endsection

@push('scripts')
    <script>
        window.whenKesehatanReady(function() {
            'use strict';

            const CONFIG = {
                ID: '{{ $id }}',
                ROUTES: {
                    INDEX: '{{ route('master-obat.index') }}',
                    EDIT: '{{ route('master-obat.edit', $id) }}',
                    DESTROY: '{{ route('master-obat.destroy', $id) }}',
                    SHOW_DATA: '{{ route('master-obat.show-data', $id) }}',
                    VERIFY: '{{ route('master-obat.verify', $id) }}',
                },
            };



            const STATUS_BADGES = {
                pending: '<span class="badge bg-warning-subtle text-warning px-3 py-2"><i class="ri ri-time-line"></i> Menunggu Verifikasi</span>',
                approved: '<span class="badge bg-success-subtle text-success px-3 py-2"><i class="ri ri-check-line"></i> Disetujui</span>',
                rejected: '<span class="badge bg-danger-subtle text-danger px-3 py-2"><i class="ri ri-close-line"></i> Ditolak</span>',
            };

            const ROLE_BADGES = {
                pasien: '<span class="badge bg-primary-subtle text-primary">Pasien</span>',
                pmo: '<span class="badge bg-info-subtle text-info">PMO</span>',
                admin: '<span class="badge bg-warning-subtle text-warning">Admin</span>',
                superadmin: '<span class="badge bg-danger-subtle text-danger">Superadmin</span>',
            };

            const formatDate = (iso) => {
                if (!iso) return '-';
                const d = new Date(iso);
                return d.toLocaleDateString('id-ID', {
                    day: 'numeric',
                    month: 'short',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                });
            };

            let dataObat = null;

            // LOAD DATA
            $.ajax({
                url: CONFIG.ROUTES.SHOW_DATA,
                method: 'GET',
            }).done(function(d) {
                dataObat = d;

                // Header
                const kategoriNama = d.kategori?.nama || '-';
                $('#entityName').text(d.nama);
                $('#bcEntityName').text(d.nama);
                $('#entityStatus').html((STATUS_BADGES[d.status] || '') +
                    ' <span class="ms-2 text-muted">' + kategoriNama + '</span>');

                // Foto
                if (d.foto_path) {
                    $('#fotoWrapper').html('<img src="/storage/' + d.foto_path + '" alt="' + d.nama +
                        '" class="img-fluid rounded shadow-sm" style="max-height: 280px; object-fit:cover;">'
                    );
                } else {
                    $('#fotoWrapper').html(
                        '<div style="font-size: 5rem;"><i class="ri ri-medicine-bottle-line text-muted"></i></div>'
                    );
                }

                // Info dasar
                $('#detail-dosis').text(d.dosis_default);
                $('#detail-satuan').text(d.satuan?.nama || '-');
                $('#detail-kategori').text(kategoriNama);

                // Detail
                $('#detail-aturan_minum').text(d.aturan_minum || '-');
                $('#detail-deskripsi').text(d.deskripsi || '-');
                $('#detail-efek_samping').text(d.efek_samping || '-');
                $('#detail-kontraindikasi').text(d.kontraindikasi || '-');

                // Creator
                if (d.creator) {
                    $('#creatorWrapper').html(
                        '<div class="d-flex align-items-center gap-3">' +
                        '<div class="avatar bg-primary text-white" style="width:40px;height:40px;">' +
                        d.creator.name.charAt(0).toUpperCase() +
                        '</div>' +
                        '<div>' +
                        '<div class="fw-semibold small">' + d.creator.name + '</div>' +
                        (ROLE_BADGES[d.creator.role] || '') +
                        '</div>' +
                        '</div>' +
                        '<div class="small text-muted mt-2">Diajukan: ' + formatDate(d.created_at) +
                        '</div>'
                    );
                }

                // Verifier
                if (d.verifier && d.verified_at) {
                    const borderColor = d.status === 'approved' ? 'success' : 'danger';
                    const iconClass = d.status === 'approved' ? 'ri-check-line text-success' :
                        'ri-close-line text-danger';
                    $('#verifierSection').find('.card').addClass('border-start border-4 border-' +
                        borderColor);
                    $('#verifierTitle').html('<i class="ri ' + iconClass + '"></i> Info Verifikasi');
                    $('#verifierWrapper').html(
                        '<div class="d-flex align-items-center gap-3 mb-2">' +
                        '<div class="avatar bg-' + borderColor +
                        ' text-white" style="width:40px;height:40px;">' +
                        d.verifier.name.charAt(0).toUpperCase() +
                        '</div>' +
                        '<div>' +
                        '<div class="fw-semibold small">' + d.verifier.name + '</div>' +
                        (ROLE_BADGES[d.verifier.role] || '') +
                        '</div>' +
                        '</div>' +
                        '<div class="small text-muted">Pada: ' + formatDate(d.verified_at) + '</div>' +
                        (d.catatan_verifikasi ?
                            '<div class="mt-2 p-2 bg-light rounded small"><strong>Catatan:</strong><br>' +
                            d.catatan_verifikasi + '</div>' : '')
                    );
                    $('#verifierSection').show();
                }

                // Verify action (admin only + pending)
                @can('master-obat.verify')
                    if (d.status === 'pending') {
                        $('#verifyActionSection').show();
                        $('#approveObatName, #rejectObatName').text(d.nama + ' (' + d.dosis_default + ')');
                    }
                @endcan

                // Header actions
                const actions = [];
                @can('master-obat.edit')
                    actions.push('<a href="' + CONFIG.ROUTES.EDIT +
                        '" class="btn btn-outline-warning btn-sm"><i class="ri ri-pencil-line me-1"></i> Edit</a>'
                    );
                @endcan
                @can('master-obat.delete')
                    actions.push(
                        '<button class="btn btn-outline-danger btn-sm" id="btnDelete"><i class="ri ri-delete-bin-line me-1"></i> Hapus</button>'
                    );
                @endcan
                if (actions.length) {
                    $('#headerActions').html(actions.join('')).attr('style', 'display:flex !important;');
                }
            }).fail(function(xhr) {
                Swal.fire({
                    title: 'Error!',
                    text: xhr.responseJSON?.message || 'Gagal memuat data',
                    icon: 'error',
                }).then(() => window.location.href = CONFIG.ROUTES.INDEX);
            });

            // DELETE
            $(document).on('click', '#btnDelete', function() {
                Swal.fire({
                    title: 'Hapus obat?',
                    html: 'Obat <strong>' + (dataObat?.nama || '') + '</strong> akan dihapus.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, hapus!',
                    cancelButtonText: 'Batal',
                    customClass: {
                        confirmButton: 'btn btn-danger me-2',
                        cancelButton: 'btn btn-secondary',
                    },
                    buttonsStyling: false,
                }).then(function(result) {
                    if (!result.isConfirmed) return;

                    $.ajax({
                        url: CONFIG.ROUTES.DESTROY,
                        method: 'DELETE',
                    }).done(function(res) {
                        Swal.fire({
                            title: 'Berhasil!',
                            text: res.message,
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false,
                        }).then(() => window.location.href = CONFIG.ROUTES.INDEX);
                    }).fail(function(xhr) {
                        Swal.fire('Gagal!', xhr.responseJSON?.message ||
                            'Terjadi kesalahan', 'error');
                    });
                });
            });

            // VERIFY
            function handleVerify(status) {
                const catatan = status === 'approved' ? $('#approveCatatan').val() : $('#rejectCatatan').val();

                if (status === 'rejected' && !catatan.trim()) {
                    Swal.fire('Validasi Gagal', 'Alasan penolakan wajib diisi.', 'warning');
                    return;
                }

                $.ajax({
                    url: CONFIG.ROUTES.VERIFY,
                    method: 'POST',
                    data: {
                        status: status,
                        catatan_verifikasi: catatan,
                        _token: '{{ csrf_token() }}',
                    },
                }).done(function(res) {
                    const modalId = status === 'approved' ? 'approveModal' : 'rejectModal';
                    bootstrap.Modal.getInstance(document.getElementById(modalId))?.hide();

                    Swal.fire({
                        title: 'Berhasil!',
                        text: res.message,
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false,
                    }).then(() => window.location.reload());
                }).fail(function(xhr) {
                    Swal.fire('Gagal!', xhr.responseJSON?.message || 'Terjadi kesalahan', 'error');
                });
            }

            $(document).on('click', '#btnApprove', () => handleVerify('approved'));
            $(document).on('click', '#btnReject', () => handleVerify('rejected'));

        });
    </script>
@endpush
