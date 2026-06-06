@extends('layouts.app')

@section('title', isset($id) ? 'Edit Pengumuman' : 'Tambah Pengumuman')

@section('page-header')
    <nav aria-label="breadcrumb" class="small">
        <ol class="breadcrumb mb-2">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('konten-pengumuman.index') }}" class="text-decoration-none">Pengumuman</a></li>
            <li class="breadcrumb-item active">{{ isset($id) ? 'Edit' : 'Tambah' }}</li>
        </ol>
    </nav>
    <div>
        <h4 class="fw-bold mb-1" id="formTitle">{{ isset($id) ? 'Edit Pengumuman' : 'Tambah Pengumuman Baru' }}</h4>
        <small class="text-muted">Pengumuman yang dipublikasi akan tampil di halaman publik.</small>
    </div>
@endsection

@section('content')

<form id="pengumumanForm" novalidate enctype="multipart/form-data">
    @csrf
    @if(isset($id))
        @method('PUT')
        <input type="hidden" id="pengumumanId" value="{{ $id }}">
    @endif

    <div class="row g-4">
        <div class="col-lg-8">
            <x-card title="Informasi Pengumuman" icon="ri-megaphone-line">
                <div class="mb-3">
                    <label for="judul" class="form-label form-label-required">Judul</label>
                    <input type="text" id="judul" name="judul" class="form-control" placeholder="Judul pengumuman" required maxlength="200">
                    <div class="invalid-feedback"></div>
                </div>

                <div class="mb-3">
                    <label for="ringkasan" class="form-label">Ringkasan</label>
                    <textarea id="ringkasan" name="ringkasan" class="form-control" rows="2" placeholder="Ringkasan singkat (tampil di daftar)" maxlength="500"></textarea>
                    <div class="invalid-feedback"></div>
                    <small class="text-muted">Opsional, maksimal 500 karakter.</small>
                </div>

                <div class="mb-0">
                    <label for="konten" class="form-label form-label-required">Isi Pengumuman</label>
                    <textarea id="konten" name="konten" class="form-control" rows="10" placeholder="Tulis isi pengumuman di sini..." required></textarea>
                    <div class="invalid-feedback"></div>
                </div>
            </x-card>
        </div>

        <div class="col-lg-4">
            <x-card title="Gambar" icon="ri-image-line">
                <div class="mb-2 text-center">
                    <img id="gambarPreview" src="" alt="Preview"
                         class="img-fluid rounded border d-none" style="max-height: 180px; object-fit: cover;">
                </div>
                <input type="file" id="gambar" name="gambar" class="form-control" accept="image/*">
                <div class="invalid-feedback"></div>
                <small class="text-muted">Opsional. JPG/PNG/WEBP, maks 2 MB.</small>
            </x-card>

            <div class="mt-3">
                <x-card title="Status" icon="ri-toggle-line">
                    <div class="mb-0">
                        <div class="form-check form-switch">
                            <input type="hidden" name="is_published" value="0">
                            <input class="form-check-input" type="checkbox" id="is_published" name="is_published" value="1" checked>
                            <label class="form-check-label" for="is_published">
                                <span id="statusLabel">Dipublikasi</span>
                            </label>
                        </div>
                        <small class="text-muted">
                            Draft tidak akan tampil di halaman publik.
                        </small>
                    </div>
                </x-card>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-primary fw-semibold px-4" id="submitBtn">
            <span class="spinner-border spinner-border-sm d-none me-2"></span>
            <i class="ri ri-save-line me-1"></i> Simpan
        </button>
        <a href="{{ route('konten-pengumuman.index') }}" class="btn btn-outline-secondary">Batal</a>
    </div>
</form>

@endsection

@push('scripts')
<script>
window.whenKesehatanReady(function() {
    'use strict';

    const CONFIG = {
        IS_EDIT:  {{ isset($id) ? 'true' : 'false' }},
        ROUTES: {
            INDEX:     '{{ route('konten-pengumuman.index') }}',
            STORE:     '{{ route('konten-pengumuman.store') }}',
            UPDATE:    '{{ isset($id) ? route('konten-pengumuman.update', $id) : '' }}',
            SHOW_DATA: '{{ isset($id) ? route('konten-pengumuman.show-data', $id) : '' }}',
        },
    };

    const $form = $('#pengumumanForm');
    const $submitBtn = $('#submitBtn');

    $('#is_published').on('change', function() {
        $('#statusLabel').text(this.checked ? 'Dipublikasi' : 'Draft');
    });

    // Preview gambar saat pilih file
    $('#gambar').on('change', function() {
        const file = this.files[0];
        if (file) {
            $('#gambarPreview').attr('src', URL.createObjectURL(file)).removeClass('d-none');
        }
    });

    // EDIT MODE
    if (CONFIG.IS_EDIT) {
        $.ajax({ url: CONFIG.ROUTES.SHOW_DATA, method: 'GET' })
        .done(function(d) {
            $('#formTitle').text('Edit: ' + d.judul);
            $('#judul').val(d.judul);
            $('#ringkasan').val(d.ringkasan || '');
            $('#konten').val(d.konten || '');
            $('#is_published').prop('checked', !!d.is_published).trigger('change');
            if (d.gambar_path) {
                $('#gambarPreview').attr('src', '/storage/' + d.gambar_path).removeClass('d-none');
            }
        })
        .fail(function(xhr) {
            Swal.fire({ title: 'Error!', text: xhr.responseJSON?.message || 'Gagal memuat data', icon: 'error' })
                .then(() => window.location.href = CONFIG.ROUTES.INDEX);
        });
    }

    // SUBMIT (multipart)
    $form.on('submit', function(e) {
        e.preventDefault();

        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').text('');
        $submitBtn.prop('disabled', true).find('.spinner-border').removeClass('d-none');

        const url = CONFIG.IS_EDIT ? CONFIG.ROUTES.UPDATE : CONFIG.ROUTES.STORE;

        const fd = new FormData();
        fd.append('_token', $('input[name=_token]').val());
        fd.append('judul', $('#judul').val());
        fd.append('ringkasan', $('#ringkasan').val());
        fd.append('konten', $('#konten').val());
        fd.append('is_published', $('#is_published').is(':checked') ? 1 : 0);
        if ($('#gambar')[0].files[0]) fd.append('gambar', $('#gambar')[0].files[0]);
        if (CONFIG.IS_EDIT) fd.append('_method', 'PUT');

        $.ajax({ url, method: 'POST', data: fd, processData: false, contentType: false })
        .done(function(res) {
            Swal.fire({ title: 'Berhasil!', text: res.message, icon: 'success', timer: 1500, showConfirmButton: false })
                .then(() => window.location.href = CONFIG.ROUTES.INDEX);
        })
        .fail(function(xhr) {
            $submitBtn.prop('disabled', false).find('.spinner-border').addClass('d-none');
            if (xhr.status === 422 && xhr.responseJSON?.errors) {
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
