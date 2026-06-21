@extends('layouts.app')

@section('title', 'Pengaturan Pengingat')

@section('page-header')
    <div>
        <h4 class="fw-bold mb-1">Pengaturan Pengingat</h4>
        <small class="text-muted">Atur jumlah, interval, dan eskalasi pengingat Minum Obat & Cek Gula Darah.</small>
    </div>
@endsection

@section('content')
    <form id="pengaturanForm" novalidate>
        @csrf
        @method('PUT')

        <div class="row g-4">
            {{-- ============ MINUM OBAT ============ --}}
            <div class="col-lg-6">
                <x-card title="Pengingat Minum Obat" icon="ri-capsule-line">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="mo_aktif" name="mo_aktif"
                            {{ $pengaturan->mo_aktif ? 'checked' : '' }}>
                        <label class="form-check-label" for="mo_aktif">Aktifkan pengingat Minum Obat</label>
                    </div>

                    <div class="mb-3">
                        <label for="mo_jumlah" class="form-label form-label-required">Jumlah pengingat</label>
                        <input type="number" min="1" max="20" id="mo_jumlah" name="mo_jumlah" class="form-control"
                            value="{{ $pengaturan->mo_jumlah }}" required>
                        <div class="invalid-feedback"></div>
                        <small class="text-muted">Berapa kali pengingat dikirim sampai dikonfirmasi (1–20).</small>
                    </div>

                    <div class="mb-3">
                        <label for="mo_interval_menit" class="form-label form-label-required">Interval (menit)</label>
                        <input type="number" min="1" max="180" id="mo_interval_menit" name="mo_interval_menit"
                            class="form-control" value="{{ $pengaturan->mo_interval_menit }}" required>
                        <div class="invalid-feedback"></div>
                        <small class="text-muted">Jeda antar pengingat (1–180 menit).</small>
                    </div>

                    <div class="mb-0">
                        <label for="mo_pmo_mulai_ke" class="form-label form-label-required">PMO mulai dilibatkan pada pengingat ke-</label>
                        <input type="number" min="1" id="mo_pmo_mulai_ke" name="mo_pmo_mulai_ke" class="form-control"
                            value="{{ $pengaturan->mo_pmo_mulai_ke }}" required>
                        <div class="invalid-feedback"></div>
                        <small class="text-muted">PMO ikut dikirimi mulai pengingat ke-berapa (≤ jumlah pengingat).</small>
                    </div>
                </x-card>
            </div>

            {{-- ============ CEK GULA DARAH ============ --}}
            <div class="col-lg-6">
                <x-card title="Pengingat Cek Gula Darah" icon="ri-test-tube-line">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="cgd_aktif" name="cgd_aktif"
                            {{ $pengaturan->cgd_aktif ? 'checked' : '' }}>
                        <label class="form-check-label" for="cgd_aktif">Aktifkan pengingat Cek Gula Darah</label>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="cgd_dibuat_aktif" name="cgd_dibuat_aktif"
                            {{ $pengaturan->cgd_dibuat_aktif ? 'checked' : '' }}>
                        <label class="form-check-label" for="cgd_dibuat_aktif">Kirim notifikasi saat jadwal dibuat/diaktifkan</label>
                    </div>

                    <div class="mb-0">
                        <label for="cgd_jam_h1" class="form-label form-label-required">Jam kirim pengingat H-1</label>
                        <input type="time" id="cgd_jam_h1" name="cgd_jam_h1" class="form-control"
                            value="{{ $pengaturan->cgd_jam_h1 }}" required>
                        <div class="invalid-feedback"></div>
                        <small class="text-muted">Jam pengiriman pengingat sehari sebelum jadwal CGD.</small>
                    </div>
                    <div class="alert alert-info small mt-3 mb-0">
                        Jumlah pengingat CGD ditentukan otomatis: notifikasi saat dibuat (bila jauh hari) + 1× H-1.
                    </div>
                </x-card>
            </div>
        </div>

        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-primary fw-semibold px-4" id="submitBtn">
                <span class="spinner-border spinner-border-sm d-none me-2"></span>
                <i class="ri ri-save-line me-1"></i> Simpan Pengaturan
            </button>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        window.whenKesehatanReady(function() {
            'use strict';

            const $form = $('#pengaturanForm');
            const $submitBtn = $('#submitBtn');
            const UPDATE_URL = '{{ route('pengaturan-pengingat.update') }}';

            $form.on('submit', async function(e) {
                e.preventDefault();

                $form.find('.is-invalid').removeClass('is-invalid');
                $form.find('.invalid-feedback').text('');
                $submitBtn.prop('disabled', true).find('.spinner-border').removeClass('d-none');

                const data = {
                    _method: 'PUT',
                    mo_aktif: $('#mo_aktif').is(':checked'),
                    mo_jumlah: parseInt($('#mo_jumlah').val(), 10),
                    mo_interval_menit: parseInt($('#mo_interval_menit').val(), 10),
                    mo_pmo_mulai_ke: parseInt($('#mo_pmo_mulai_ke').val(), 10),
                    cgd_aktif: $('#cgd_aktif').is(':checked'),
                    cgd_dibuat_aktif: $('#cgd_dibuat_aktif').is(':checked'),
                    cgd_jam_h1: $('#cgd_jam_h1').val(),
                };

                const csrfToken = $('input[name=_token]').val() || $('meta[name=csrf-token]').attr('content');

                try {
                    const res = await $.ajax({
                        url: UPDATE_URL,
                        method: 'POST',
                        contentType: 'application/json',
                        headers: { 'X-CSRF-TOKEN': csrfToken },
                        data: JSON.stringify(data),
                    });

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
                            const $field = $('#' + field);
                            $field.addClass('is-invalid');
                            $field.siblings('.invalid-feedback').text(messages[0]);
                        });
                        Swal.fire('Validasi Gagal', 'Mohon periksa kembali isian Anda.', 'warning');
                    } else {
                        Swal.fire('Gagal!', xhr.responseJSON?.message || 'Terjadi kesalahan', 'error');
                    }
                } finally {
                    $submitBtn.prop('disabled', false).find('.spinner-border').addClass('d-none');
                }
            });
        });
    </script>
@endpush
