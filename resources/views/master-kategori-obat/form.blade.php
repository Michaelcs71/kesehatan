@extends('layouts.app')

@section('title', isset($id) ? 'Edit Kategori' : 'Tambah Kategori')

@section('page-header')
    <nav aria-label="breadcrumb" class="small">
        <ol class="breadcrumb mb-2">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('master-kategori-obat.index') }}" class="text-decoration-none">Master Kategori Obat</a></li>
            <li class="breadcrumb-item active">{{ isset($id) ? 'Edit' : 'Tambah' }}</li>
        </ol>
    </nav>
    <div>
        <h4 class="fw-bold mb-1" id="formTitle">{{ isset($id) ? 'Edit Kategori' : 'Tambah Kategori Baru' }}</h4>
        <small class="text-muted">
            @if(!isset($id))
                Tambah kategori baru untuk klasifikasi obat.
            @endif
        </small>
    </div>
@endsection

@section('content')

<form id="kategoriForm" novalidate>
    @csrf
    @if(isset($id))
        @method('PUT')
        <input type="hidden" id="kategoriId" value="{{ $id }}">
    @endif

    <div class="row g-4">
        <div class="col-lg-8">
            <x-card title="Informasi Kategori" icon="ri-price-tag-3-line">
                <div class="mb-3">
                    <label for="nama" class="form-label form-label-required">Nama Kategori</label>
                    <input type="text" id="nama" name="nama" class="form-control" placeholder="contoh: Tetes Mata, Salep, Inhaler" required maxlength="100">
                    <div class="invalid-feedback"></div>
                    <small class="text-muted">Nama kategori harus unik.</small>
                </div>

                <div class="mb-0">
                    <label for="deskripsi" class="form-label">Deskripsi</label>
                    <textarea id="deskripsi" name="deskripsi" class="form-control" rows="4" placeholder="Penjelasan singkat tentang kategori ini" maxlength="500"></textarea>
                    <div class="invalid-feedback"></div>
                    <small class="text-muted">Maksimal 500 karakter (opsional).</small>
                </div>
            </x-card>
        </div>

        <div class="col-lg-4">
            <x-card title="Status" icon="ri-toggle-line">
                <div class="mb-0">
                    <label class="form-label">Status Kategori</label>
                    <div class="form-check form-switch">
                        <input type="hidden" name="is_active" value="0">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" checked>
                        <label class="form-check-label" for="is_active">
                            <span id="statusLabel">Aktif</span>
                        </label>
                    </div>
                    <small class="text-muted">
                        Kategori nonaktif tidak akan muncul di dropdown saat input obat baru.
                    </small>
                </div>
            </x-card>

            <div class="mt-3">
                <x-card class="border-start border-4 border-info">
                    <h6 class="fw-bold mb-2 small">
                        <i class="ri ri-information-line text-info me-1"></i> Catatan
                    </h6>
                    <p class="small text-muted mb-0">
                        Kategori yang sedang digunakan oleh obat tidak dapat dihapus.
                        Pindahkan obat ke kategori lain terlebih dahulu sebelum menghapus.
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
        <a href="{{ route('master-kategori-obat.index') }}" class="btn btn-outline-secondary">Batal</a>
    </div>
</form>

@endsection

@push('scripts')
<script>
window.whenKesehatanReady(function() {
    'use strict';

    const CONFIG = {
        IS_EDIT:  {{ isset($id) ? 'true' : 'false' }},
        ID:       '{{ $id ?? '' }}',
        ROUTES: {
            INDEX:     '{{ route('master-kategori-obat.index') }}',
            STORE:     '{{ route('master-kategori-obat.store') }}',
            UPDATE:    '{{ isset($id) ? route('master-kategori-obat.update', $id) : '' }}',
            SHOW_DATA: '{{ isset($id) ? route('master-kategori-obat.show-data', $id) : '' }}',
        },
    };

    const $form = $('#kategoriForm');
    const $submitBtn = $('#submitBtn');

    // Status label toggle
    $('#is_active').on('change', function() {
        $('#statusLabel').text(this.checked ? 'Aktif' : 'Nonaktif');
    });

    // EDIT MODE: load data
    if (CONFIG.IS_EDIT) {
        $.ajax({
            url: CONFIG.ROUTES.SHOW_DATA,
            method: 'GET',
        }).done(function(data) {
            $('#formTitle').text('Edit: ' + data.nama);
            $('#nama').val(data.nama);
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

    // SUBMIT
    $form.on('submit', function(e) {
        e.preventDefault();

        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').text('');

        $submitBtn.prop('disabled', true).find('.spinner-border').removeClass('d-none');

        const url = CONFIG.IS_EDIT ? CONFIG.ROUTES.UPDATE : CONFIG.ROUTES.STORE;

        const data = {
            _token: $('input[name=_token]').val(),
            nama: $('#nama').val(),
            deskripsi: $('#deskripsi').val(),
            is_active: $('#is_active').is(':checked') ? 1 : 0,
        };

        if (CONFIG.IS_EDIT) data._method = 'PUT';

        $.ajax({ url: url, method: 'POST', data: data })
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
                Object.entries(xhr.responseJSON.errors).forEach(function([field, messages]) {
                    const $field = $('[name="' + field + '"]');
                    $field.addClass('is-invalid');
                    $field.siblings('.invalid-feedback').text(messages[0]);
                });
                Swal.fire('Validasi Gagal', 'Mohon periksa kembali data yang Anda masukkan.', 'warning');
            } else {
                Swal.fire('Gagal!', xhr.responseJSON?.message || 'Terjadi kesalahan', 'error');
            }
        });
    });
});
</script>
@endpush