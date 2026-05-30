@extends('layouts.app')

@section('title', isset($id) ? 'Edit Obat' : 'Tambah Obat')

@section('page-header')
    <nav aria-label="breadcrumb" class="small">
        <ol class="breadcrumb mb-2">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('master-obat.index') }}" class="text-decoration-none">Master Obat</a>
            </li>
            <li class="breadcrumb-item active">{{ isset($id) ? 'Edit' : 'Tambah' }}</li>
        </ol>
    </nav>
    <div>
        <h4 class="fw-bold mb-1" id="formTitle">{{ isset($id) ? 'Edit Obat' : 'Tambah Obat Baru' }}</h4>
        <small class="text-muted">
            @if (!isset($id))
                @if (auth()->user()->isAdmin())
                    Obat akan langsung berstatus <strong>approved</strong>.
                @else
                    Obat akan masuk antrian verifikasi admin.
                @endif
            @endif
        </small>
    </div>
@endsection

@section('content')

    @php
        $kategoriOptions = \App\Services\MasterKategoriObatService::getActiveOptions();
        $satuanOptions = \App\Services\MasterSatuanObatService::getActiveOptions();
    @endphp

    <form id="obatForm" enctype="multipart/form-data" novalidate>
        @csrf
        @if (isset($id))
            @method('PUT')
            <input type="hidden" id="obatId" value="{{ $id }}">
        @endif

        <div class="row g-4">
            <div class="col-lg-8">
                <x-card title="Informasi Obat" icon="ri-information-line">
                    <div class="mb-3">
                        <label for="nama" class="form-label form-label-required">Nama Obat</label>
                        <input type="text" id="nama" name="nama" class="form-control"
                            placeholder="contoh: Metformin" required>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="kategori_id" class="form-label form-label-required">Kategori</label>
                            <select id="kategori_id" name="kategori_id" class="form-select" required>
                                <option value="">-- Pilih --</option>
                                @foreach ($kategoriOptions as $opt)
                                    <option value="{{ $opt['id'] }}">{{ $opt['nama'] }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                            @if (count($kategoriOptions) === 0)
                                <small class="text-warning">
                                    <i class="ri ri-error-warning-line"></i>
                                    Belum ada kategori aktif. Silakan tambah di
                                    <a href="{{ url('master-kategori-obat') }}">Master Kategori</a>.
                                </small>
                            @endif
                        </div>
                        <div class="col-md-4">
                            <label for="dosis_default" class="form-label form-label-required">Dosis</label>
                            <input type="text" id="dosis_default" name="dosis_default" class="form-control"
                                placeholder="contoh: 500mg" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-4">
                            <label for="satuan_id" class="form-label form-label-required">Satuan</label>
                            <select id="satuan_id" name="satuan_id" class="form-select" required>
                                <option value="">-- Pilih --</option>
                                @foreach ($satuanOptions as $opt)
                                    <option value="{{ $opt['id'] }}">
                                        {{ $opt['nama'] }}{{ $opt['singkatan'] ? ' (' . $opt['singkatan'] . ')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                            @if (count($satuanOptions) === 0)
                                <small class="text-warning">
                                    <i class="ri ri-error-warning-line"></i>
                                    Belum ada satuan aktif. Silakan tambah di
                                    <a href="{{ url('master-satuan-obat') }}">Master Satuan</a>.
                                </small>
                            @endif
                        </div>
                    </div>

                    <div class="mt-3">
                        <label for="aturan_minum" class="form-label">Aturan Minum</label>
                        <textarea id="aturan_minum" name="aturan_minum" class="form-control" rows="2"
                            placeholder="contoh: 2x sehari setelah makan"></textarea>
                    </div>

                    <div class="mt-3">
                        <label for="deskripsi" class="form-label">Deskripsi</label>
                        <textarea id="deskripsi" name="deskripsi" class="form-control" rows="3"
                            placeholder="Penjelasan singkat tentang obat ini"></textarea>
                    </div>
                </x-card>

                <div class="mt-3"></div>

                <x-card title="Info Medis (opsional)" icon="ri-stethoscope-line">
                    <div class="mb-3">
                        <label for="efek_samping" class="form-label">Efek Samping</label>
                        <textarea id="efek_samping" name="efek_samping" class="form-control" rows="3"
                            placeholder="contoh: mual, pusing, dll"></textarea>
                    </div>
                    <div class="mb-0">
                        <label for="kontraindikasi" class="form-label">Kontraindikasi</label>
                        <textarea id="kontraindikasi" name="kontraindikasi" class="form-control" rows="3"
                            placeholder="contoh: tidak boleh untuk ibu hamil, dll"></textarea>
                    </div>
                </x-card>
            </div>

            <div class="col-lg-4">
                <x-card title="Foto Obat" icon="ri-image-line">
                    <div id="currentFotoWrapper" class="mb-3 text-center" style="display:none;">
                        <img id="currentFoto" src="" alt="Foto saat ini" class="img-fluid rounded shadow-sm"
                            style="max-height: 200px;">
                        <div class="small text-muted mt-2">Foto saat ini</div>
                    </div>

                    <div class="mb-3">
                        <label for="foto" class="form-label {{ isset($id) ? '' : 'form-label-required' }}">Upload
                            Foto</label>
                        <input type="file" id="foto" name="foto" accept="image/*" class="form-control">
                        <div class="invalid-feedback"></div>
                        <small class="text-muted">
                            Max 5 MB. Format: jpg, jpeg, png, webp.
                            @if (isset($id))
                                <br>Kosongkan jika tidak ingin ganti foto.
                            @endif
                        </small>
                    </div>

                    <div id="fotoPreviewWrapper" class="text-center" style="display:none;">
                        <div class="small text-muted mb-2">Preview foto baru:</div>
                        <img id="fotoPreview" src="" alt="Preview" class="img-fluid rounded shadow-sm border"
                            style="max-height: 200px;">
                    </div>
                </x-card>
            </div>
        </div>

        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-primary fw-semibold px-4" id="submitBtn">
                <span class="spinner-border spinner-border-sm d-none me-2"></span>
                <i class="ri ri-save-line me-1"></i> Simpan
            </button>
            <a href="{{ route('master-obat.index') }}" class="btn btn-outline-secondary">Batal</a>
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
                    INDEX: '{{ route('master-obat.index') }}',
                    STORE: '{{ route('master-obat.store') }}',
                    UPDATE: '{{ isset($id) ? route('master-obat.update', $id) : '' }}',
                    SHOW_DATA: '{{ isset($id) ? route('master-obat.show-data', $id) : '' }}',
                },
            };

            const $form = $('#obatForm');
            const $submitBtn = $('#submitBtn');

            // Foto preview
            $('#foto').on('change', function(e) {
                const file = e.target.files[0];
                const wrapper = $('#fotoPreviewWrapper');
                const img = $('#fotoPreview');
                if (!file) {
                    wrapper.hide();
                    return;
                }
                const reader = new FileReader();
                reader.onload = (ev) => {
                    img.attr('src', ev.target.result);
                    wrapper.show();
                };
                reader.readAsDataURL(file);
            });

            // Edit mode: load data via AJAX
            if (CONFIG.IS_EDIT) {
                $.ajax({
                    url: CONFIG.ROUTES.SHOW_DATA,
                    method: 'GET',
                }).done(function(data) {
                    $('#formTitle').text('Edit: ' + data.nama);
                    $('#nama').val(data.nama);
                    $('#kategori_id').val(data.kategori_id);
                    $('#dosis_default').val(data.dosis_default);
                    $('#satuan_id').val(data.satuan_id);
                    $('#aturan_minum').val(data.aturan_minum || '');
                    $('#deskripsi').val(data.deskripsi || '');
                    $('#efek_samping').val(data.efek_samping || '');
                    $('#kontraindikasi').val(data.kontraindikasi || '');
                    if (data.foto_path) {
                        $('#currentFoto').attr('src', '/storage/' + data.foto_path);
                        $('#currentFotoWrapper').show();
                    }
                }).fail(function(xhr) {
                    Swal.fire({
                        title: 'Error!',
                        text: xhr.responseJSON?.message || 'Gagal memuat data',
                        icon: 'error',
                    }).then(() => window.location.href = CONFIG.ROUTES.INDEX);
                });
            }

            // Submit
            $form.on('submit', function(e) {
                e.preventDefault();

                $form.find('.is-invalid').removeClass('is-invalid');
                $form.find('.invalid-feedback').text('');

                $submitBtn.prop('disabled', true).find('.spinner-border').removeClass('d-none');

                const formData = new FormData($form[0]);
                const url = CONFIG.IS_EDIT ? CONFIG.ROUTES.UPDATE : CONFIG.ROUTES.STORE;

                $.ajax({
                    url: url,
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                }).done(function(res) {
                    Swal.fire({
                        title: 'Berhasil!',
                        text: res.message,
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false,
                    }).then(() => {
                        window.location.href = CONFIG.ROUTES.INDEX;
                    });
                }).fail(function(xhr) {
                    $submitBtn.prop('disabled', false).find('.spinner-border').addClass('d-none');

                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        Object.entries(xhr.responseJSON.errors).forEach(function([field,
                            messages
                        ]) {
                            const $field = $('[name="' + field + '"]');
                            $field.addClass('is-invalid');
                            $field.siblings('.invalid-feedback').text(messages[0]);
                        });
                        Swal.fire({
                            title: 'Validasi Gagal',
                            text: 'Mohon periksa kembali data yang Anda masukkan.',
                            icon: 'warning',
                        });
                    } else {
                        Swal.fire({
                            title: 'Gagal!',
                            text: xhr.responseJSON?.message || 'Terjadi kesalahan',
                            icon: 'error',
                        });
                    }
                });
            });
        });
    </script>
@endpush
