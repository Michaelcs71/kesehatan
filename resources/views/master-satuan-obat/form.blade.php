@extends('layouts.app')

@section('title', isset($id) ? 'Edit Satuan' : 'Tambah Satuan')

@section('page-header')
    <nav aria-label="breadcrumb" class="small">
        <ol class="breadcrumb mb-2">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('master-satuan-obat.index') }}" class="text-decoration-none">Master
                    Satuan Obat</a></li>
            <li class="breadcrumb-item active">{{ isset($id) ? 'Edit' : 'Tambah' }}</li>
        </ol>
    </nav>
    <div>
        <h4 class="fw-bold mb-1" id="formTitle">{{ isset($id) ? 'Edit Satuan' : 'Tambah Satuan Baru' }}</h4>
    </div>
@endsection

@section('content')

    <form id="satuanForm" novalidate>
        @csrf
        @if (isset($id))
            @method('PUT')
            <input type="hidden" id="satuanId" value="{{ $id }}">
        @endif

        <div class="row g-4">
            <div class="col-lg-8">
                <x-card title="Informasi Satuan" icon="ri-ruler-line">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label for="nama" class="form-label form-label-required">Nama Satuan</label>
                            <input type="text" id="nama" name="nama" class="form-control"
                                placeholder="contoh: Tablet, Sirup, Tetes" required maxlength="50">
                            <div class="invalid-feedback"></div>
                            <small class="text-muted">Nama satuan harus unik.</small>
                        </div>
                        <div class="col-md-4">
                            <label for="singkatan" class="form-label">Singkatan</label>
                            <input type="text" id="singkatan" name="singkatan" class="form-control"
                                placeholder="tab, kap, ml" maxlength="20">
                            <div class="invalid-feedback"></div>
                            <small class="text-muted">Untuk display ringkas.</small>
                        </div>
                    </div>

                    <div class="mt-3 mb-0">
                        <label for="deskripsi" class="form-label">Deskripsi</label>
                        <textarea id="deskripsi" name="deskripsi" class="form-control" rows="3"
                            placeholder="Penjelasan singkat tentang satuan ini" maxlength="500"></textarea>
                        <div class="invalid-feedback"></div>
                    </div>
                </x-card>
            </div>

            <div class="col-lg-4">
                <x-card title="Status" icon="ri-toggle-line">
                    <div class="mb-0">
                        <label class="form-label">Status Satuan</label>
                        <div class="form-check form-switch">
                            <input type="hidden" name="is_active" value="0">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                                checked>
                            <label class="form-check-label" for="is_active">
                                <span id="statusLabel">Aktif</span>
                            </label>
                        </div>
                        <small class="text-muted">
                            Satuan nonaktif tidak akan muncul di dropdown saat input obat baru.
                        </small>
                    </div>
                </x-card>

                <div class="mt-3">
                    <x-card class="border-start border-4 border-info">
                        <h6 class="fw-bold mb-2 small">
                            <i class="ri ri-information-line text-info me-1"></i> Catatan
                        </h6>
                        <p class="small text-muted mb-0">
                            Satuan yang sedang digunakan oleh obat tidak dapat dihapus.
                        </p>
                    </x-card>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-primary fw-semibold px-4" id="submitBtn">
                <span class="spinner-border spinner-border-sm d-none me-2"></span>
                <i class="ri ri-save-line me-1"></i> Simpan
            </button>
            <a href="{{ route('master-satuan-obat.index') }}" class="btn btn-outline-secondary">Batal</a>
        </div>
    </form>

@endsection

@push('scripts')
    <script>
        window.whenKesehatanReady(function() {
            'use strict';

            const CONFIG = {
                IS_EDIT: {{ isset($id) ? 'true' : 'false' }},
                ID: '{{ $id ?? '' }}',
                ROUTES: {
                    INDEX: '{{ route('master-satuan-obat.index') }}',
                    STORE: '{{ route('master-satuan-obat.store') }}',
                    UPDATE: '{{ isset($id) ? route('master-satuan-obat.update', $id) : '' }}',
                    SHOW_DATA: '{{ isset($id) ? route('master-satuan-obat.show-data', $id) : '' }}',
                },
            };

            const $form = $('#satuanForm');
            const $submitBtn = $('#submitBtn');

            $('#is_active').on('change', function() {
                $('#statusLabel').text(this.checked ? 'Aktif' : 'Nonaktif');
            });

            if (CONFIG.IS_EDIT) {
                $.ajax({
                    url: CONFIG.ROUTES.SHOW_DATA,
                    method: 'GET',
                }).done(function(data) {
                    $('#formTitle').text('Edit: ' + data.nama);
                    $('#nama').val(data.nama);
                    $('#singkatan').val(data.singkatan || '');
                    $('#deskripsi').val(data.deskripsi || '');
                    $('#is_active').prop('checked', !!data.is_active).trigger('change');
                }).fail(function(xhr) {
                    Swal.fire({
                        title: 'Error!',
                        text: xhr.responseJSON?.message || 'Gagal memuat data',
                        icon: 'error',
                    }).then(() => window.location.href = CONFIG.ROUTES.INDEX);
                });
            }

            $form.on('submit', function(e) {
                e.preventDefault();

                $form.find('.is-invalid').removeClass('is-invalid');
                $form.find('.invalid-feedback').text('');

                $submitBtn.prop('disabled', true).find('.spinner-border').removeClass('d-none');

                const url = CONFIG.IS_EDIT ? CONFIG.ROUTES.UPDATE : CONFIG.ROUTES.STORE;

                const data = {
                    _token: $('input[name=_token]').val(),
                    nama: $('#nama').val(),
                    singkatan: $('#singkatan').val(),
                    deskripsi: $('#deskripsi').val(),
                    is_active: $('#is_active').is(':checked') ? 1 : 0,
                };

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
                            Object.entries(xhr.responseJSON.errors).forEach(function([field,
                            messages]) {
                                const $field = $('[name="' + field + '"]');
                                $field.addClass('is-invalid');
                                $field.siblings('.invalid-feedback').text(messages[0]);
                            });
                            Swal.fire('Validasi Gagal',
                                'Mohon periksa kembali data yang Anda masukkan.', 'warning');
                        } else {
                            Swal.fire('Gagal!', xhr.responseJSON?.message || 'Terjadi kesalahan',
                                'error');
                        }
                    });
            });
        });
    </script>
@endpush
