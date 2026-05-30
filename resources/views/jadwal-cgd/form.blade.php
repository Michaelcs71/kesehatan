@extends('layouts.app')

@section('title', isset($id) ? 'Edit Jadwal CGD' : 'Tambah Jadwal CGD')

@section('page-header')
    <nav aria-label="breadcrumb" class="small">
        <ol class="breadcrumb mb-2">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('jadwal-cgd.index') }}" class="text-decoration-none">Jadwal CGD</a>
            </li>
            <li class="breadcrumb-item active">{{ isset($id) ? 'Edit' : 'Tambah' }}</li>
        </ol>
    </nav>
    <div>
        <h4 class="fw-bold mb-1" id="formTitle">{{ isset($id) ? 'Edit Jadwal CGD' : 'Tambah Jadwal CGD Baru' }}</h4>
        <small class="text-muted">Isi detail event pemeriksaan gula darah.</small>
    </div>
@endsection

@section('content')

    <form id="cgdForm" novalidate>
        @csrf
        @if (isset($id))
            @method('PUT')
            <input type="hidden" id="jadwalId" value="{{ $id }}">
        @endif

        <div class="row g-4">
            <div class="col-lg-8">

                {{-- ============ JADWAL ============ --}}
                <x-card title="Tanggal & Waktu" icon="ri-calendar-event-line">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label for="tgl_jadwal_cgd" class="form-label form-label-required">Tanggal Pelaksanaan</label>
                            <input type="date" id="tgl_jadwal_cgd" name="tgl_jadwal_cgd" class="form-control"
                                value="{{ date('Y-m-d') }}" required>
                            <div class="invalid-feedback"></div>
                            <small class="text-muted">Tanggal kapan event CGD akan diadakan.</small>
                        </div>
                        <div class="col-md-6">
                            <label for="jam_mulai" class="form-label form-label-required">Jam Mulai</label>
                            <input type="time" id="jam_mulai" name="jam_mulai" class="form-control" value="07:00"
                                required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="jam_berakhir" class="form-label form-label-required">Jam Berakhir</label>
                            <input type="time" id="jam_berakhir" name="jam_berakhir" class="form-control" value="10:00"
                                required>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                </x-card>

                <div class="mt-3"></div>

                {{-- ============ TEMPAT & DETAIL ============ --}}
                <x-card title="Tempat & Detail" icon="ri-map-pin-line">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label for="tempat" class="form-label form-label-required">Tempat Pelaksanaan</label>
                            <input type="text" id="tempat" name="tempat" class="form-control"
                                placeholder="Contoh: Posyandu Dusun Lebakharjo" maxlength="255" required>
                            <div class="invalid-feedback"></div>
                            <small class="text-muted">Nama lokasi event berlangsung.</small>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label form-label-required">Puasa</label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="puasa" id="puasa_wajib"
                                        value="Wajib" required>
                                    <label class="form-check-label" for="puasa_wajib">
                                        <i class="ri ri-restaurant-line text-warning"></i> Wajib Puasa
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="puasa" id="puasa_tidak"
                                        value="Tidak" required>
                                    <label class="form-check-label" for="puasa_tidak">
                                        Tidak Wajib Puasa
                                    </label>
                                </div>
                            </div>
                            <div class="invalid-feedback d-block" id="puasa_error"></div>
                            <small class="text-muted">Apakah pasien wajib puasa sebelum cek gula darah?</small>
                        </div>

                        <div class="col-md-12">
                            <label for="catatan" class="form-label">Catatan (Opsional)</label>
                            <textarea id="catatan" name="catatan" class="form-control" rows="3" maxlength="1000"
                                placeholder="Contoh: Bawa kartu peserta. Puasa sejak jam 22:00 malam sebelumnya."></textarea>
                            <div class="invalid-feedback"></div>
                            <small class="text-muted">Informasi tambahan untuk pasien.</small>
                        </div>
                    </div>
                </x-card>
            </div>

            {{-- ============ KOLOM KANAN ============ --}}
            <div class="col-lg-4">
                @if (isset($id))
                    <x-card title="Status" icon="ri-toggle-line">
                        <div class="mb-0">
                            <label for="status" class="form-label form-label-required">Status Event</label>
                            <select id="status" name="status" class="form-select" required>
                                <option value="aktif">Aktif</option>
                                <option value="nonaktif">Nonaktif</option>
                                <option value="selesai">Selesai</option>
                            </select>
                            <small class="text-muted">
                                <strong>Aktif</strong> = jadwal berlaku<br>
                                <strong>Nonaktif</strong> = dibatalkan<br>
                                <strong>Selesai</strong> = event sudah lewat
                            </small>
                        </div>
                    </x-card>

                    <div class="mt-3"></div>
                @endif

                <x-card class="border-start border-4 border-info">
                    <h6 class="fw-bold mb-2 small">
                        <i class="ri ri-information-line text-info me-1"></i> Info
                    </h6>
                    <p class="small text-muted mb-0">
                        Jadwal CGD ini akan terlihat oleh semua pasien & PMO sebagai informasi event.
                        Status default <strong>Aktif</strong>.
                    </p>
                </x-card>
            </div>
        </div>

        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-primary fw-semibold px-4" id="submitBtn">
                <span class="spinner-border spinner-border-sm d-none me-2"></span>
                <i class="ri ri-save-line me-1"></i> Simpan Jadwal
            </button>
            <a href="{{ route('jadwal-cgd.index') }}" class="btn btn-outline-secondary">Batal</a>
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
                    INDEX: '{{ route('jadwal-cgd.index') }}',
                    STORE: '{{ route('jadwal-cgd.store') }}',
                    UPDATE: '{{ isset($id) ? route('jadwal-cgd.update', $id) : '' }}',
                    SHOW_DATA: '{{ isset($id) ? route('jadwal-cgd.show-data', $id) : '' }}',
                },
            };

            const $form = $('#cgdForm');
            const $submitBtn = $('#submitBtn');

            init();

            async function init() {
                if (CONFIG.IS_EDIT) {
                    await loadExistingData();
                }
                $form.on('submit', submitForm);
            }

            async function loadExistingData() {
                try {
                    const data = await $.ajax({
                        url: CONFIG.ROUTES.SHOW_DATA,
                        method: 'GET'
                    });
                    $('#formTitle').text('Edit Jadwal: ' + data.tempat);

                    $('#tgl_jadwal_cgd').val(data.tgl_jadwal_cgd ? data.tgl_jadwal_cgd.substr(0, 10) : '');
                    $('#jam_mulai').val(data.jam_mulai ? data.jam_mulai.substr(0, 5) : '');
                    $('#jam_berakhir').val(data.jam_berakhir ? data.jam_berakhir.substr(0, 5) : '');
                    $('#tempat').val(data.tempat || '');
                    $('#catatan').val(data.catatan || '');
                    $('#status').val(data.status);

                    // Radio puasa
                    if (data.puasa === 'Wajib') {
                        $('#puasa_wajib').prop('checked', true);
                    } else if (data.puasa === 'Tidak') {
                        $('#puasa_tidak').prop('checked', true);
                    }
                } catch (e) {
                    Swal.fire({
                        title: 'Error!',
                        text: 'Gagal memuat data jadwal.',
                        icon: 'error',
                    }).then(() => window.location.href = CONFIG.ROUTES.INDEX);
                }
            }

            async function submitForm(e) {
                e.preventDefault();

                $form.find('.is-invalid').removeClass('is-invalid');
                $form.find('.invalid-feedback').text('');
                $('#puasa_error').text('');

                $submitBtn.prop('disabled', true).find('.spinner-border').removeClass('d-none');

                const data = {
                    tgl_jadwal_cgd: $('#tgl_jadwal_cgd').val(),
                    jam_mulai: $('#jam_mulai').val(),
                    jam_berakhir: $('#jam_berakhir').val(),
                    puasa: $('input[name="puasa"]:checked').val() || '',
                    tempat: $('#tempat').val().trim(),
                    catatan: $('#catatan').val().trim() || null,
                };

                if (CONFIG.IS_EDIT) {
                    data._method = 'PUT';
                    data.status = $('#status').val();
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
                            if (field === 'puasa') {
                                $('#puasa_error').text(messages[0]);
                                $('input[name="puasa"]').addClass('is-invalid');
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
@endpush
