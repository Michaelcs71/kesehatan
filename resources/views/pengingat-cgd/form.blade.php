@extends('layouts.app')

@section('title', isset($id) ? 'Edit Hasil CGD' : 'Input Hasil CGD')

@section('page-header')
    <nav aria-label="breadcrumb" class="small">
        <ol class="breadcrumb mb-2">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('pengingat-cgd.index') }}" class="text-decoration-none">Pengingat Cek
                    Gula Darah</a></li>
            <li class="breadcrumb-item active">{{ isset($id) ? 'Edit' : 'Input' }}</li>
        </ol>
    </nav>
    <div>
        <h4 class="fw-bold mb-1" id="formTitle">{{ isset($id) ? 'Edit Hasil CGD' : 'Input Hasil Cek Gula Darah' }}</h4>
        <small class="text-muted">Catat hasil cek gula darah dengan foto bukti.</small>
    </div>
@endsection

@section('content')

    <form id="cgdForm" novalidate enctype="multipart/form-data">
        @csrf
        @if (isset($id))
            <input type="hidden" id="logId" value="{{ $id }}">
        @endif

        <div class="row g-4">
            <div class="col-lg-8">

                {{-- ============ PILIH EVENT CGD ============ --}}
                <x-card title="Pilih Event CGD" icon="ri-calendar-event-line">
                    <div class="mb-0">
                        <label for="id_cgd" class="form-label form-label-required">Event CGD</label>
                        <select id="id_cgd" name="id_cgd" class="form-select" required
                            {{ isset($id) ? 'disabled' : '' }}>
                            <option value="">-- Pilih event CGD --</option>
                            {{-- Filled by JS --}}
                        </select>
                        <div class="invalid-feedback"></div>
                        <small class="text-muted">
                            Pilih event CGD yang kamu hadiri.
                            @if (isset($id))
                                <br><strong>Catatan:</strong> Event tidak bisa diubah saat edit.
                            @endif
                        </small>
                    </div>

                    {{-- Info event yang dipilih --}}
                    <div id="cgdInfoBox" class="alert alert-light border mt-3 d-none">
                        <div class="row g-2 small">
                            <div class="col-md-6">
                                <i class="ri ri-map-pin-line text-danger"></i> <strong>Tempat:</strong> <span
                                    id="info-tempat">-</span>
                            </div>
                            <div class="col-md-6">
                                <i class="ri ri-calendar-line text-muted"></i> <strong>Tanggal:</strong> <span
                                    id="info-tgl">-</span>
                            </div>
                            <div class="col-md-6">
                                <i class="ri ri-time-line text-muted"></i> <strong>Waktu:</strong> <span
                                    id="info-jam">-</span>
                            </div>
                            <div class="col-md-6">
                                <i class="ri ri-restaurant-line text-warning"></i> <strong>Puasa:</strong> <span
                                    id="info-puasa">-</span>
                            </div>
                        </div>
                    </div>
                </x-card>

                <div class="mt-3"></div>

                {{-- ============ WAKTU & HASIL ============ --}}
                <x-card title="Waktu & Hasil" icon="ri-flask-line">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="tgl_cgd" class="form-label form-label-required">Tanggal Cek</label>
                            <input type="date" id="tgl_cgd" name="tgl_cgd" class="form-control"
                                value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="jam_cgd" class="form-label form-label-required">Jam Cek</label>
                            <input type="time" id="jam_cgd" name="jam_cgd" class="form-control" required>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-md-12">
                            <label for="hasil_mgdl" class="form-label form-label-required">Hasil Tes (mg/dL)</label>
                            <div class="input-group input-group-lg">
                                <input type="number" id="hasil_mgdl" name="hasil_mgdl"
                                    class="form-control text-center fw-bold" min="20" max="800"
                                    placeholder="Contoh: 145" required>
                                <span class="input-group-text">mg/dL</span>
                            </div>
                            <div class="invalid-feedback"></div>
                            <small class="text-muted">Masukkan angka hasil yang tertera di alat cek gula darah.</small>
                        </div>

                        {{-- Live kategori preview --}}
                        <div class="col-md-12" id="kategoriPreviewBox" style="display:none;">
                            <div class="alert mb-0 p-3" id="kategoriPreviewAlert">
                                <div class="d-flex align-items-center gap-3">
                                    <i id="kategoriIcon" class="fs-2"></i>
                                    <div class="flex-grow-1">
                                        <h5 class="fw-bold mb-1" id="kategoriLabel">-</h5>
                                        <p class="mb-0 small" id="kategoriPesan">-</p>
                                    </div>
                                    <div class="text-end">
                                        <div class="small text-muted">Selisih dari batas normal</div>
                                        <strong class="fs-5" id="patuhDisplay">-</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </x-card>

                <div class="mt-3"></div>

                {{-- ============ UPLOAD FOTO ============ --}}
                <x-card title="Foto Bukti Hasil" icon="ri-camera-line">
                    <div class="mb-3">
                        <label for="foto_layar" class="form-label form-label-required">Upload Foto Layar Alat CGD</label>

                        <div class="d-flex gap-2 mb-2">
                            <label for="foto_layar" class="btn btn-outline-primary flex-grow-1">
                                <i class="ri ri-camera-line me-1"></i> Ambil Foto / Pilih dari Galeri
                            </label>
                            <button type="button" id="btnClearPhoto" class="btn btn-outline-danger"
                                style="display:none;" title="Hapus">
                                <i class="ri ri-close-line"></i>
                            </button>
                        </div>

                        <input type="file" id="foto_layar" name="foto_layar"
                            accept="image/jpeg,image/jpg,image/png,image/webp" capture="environment" class="d-none"
                            required>

                        <div class="invalid-feedback d-block" id="foto_layar_error"></div>

                        <small class="text-muted d-block">
                            <i class="ri ri-information-line"></i>
                            Foto akan otomatis dikompres. Max 8MB. Format: JPG/PNG/WEBP.
                            @if (isset($id))
                                <br><strong>Catatan:</strong> Kosongkan kalau tidak ingin ganti foto.
                            @endif
                        </small>
                    </div>

                    <div id="photoPreviewBox" class="text-center" style="display:none;">
                        <img id="photoPreview" src="" alt="Preview"
                            style="max-width: 100%; max-height: 400px; border-radius: 8px; border: 2px solid #dee2e6;">
                        <div class="mt-2">
                            <small class="text-muted">
                                <span id="photoSize">-</span>
                                <span id="photoCompressInfo" class="ms-2"></span>
                            </small>
                        </div>
                    </div>

                    @if (isset($id))
                        <div id="existingPhotoBox" class="text-center mt-3" style="display:none;">
                            <small class="text-muted d-block mb-2">Foto saat ini:</small>
                            <img id="existingPhoto" src="" alt="Foto existing"
                                style="max-width: 100%; max-height: 300px; border-radius: 8px; border: 1px solid #dee2e6;">
                        </div>
                    @endif
                </x-card>
            </div>

            {{-- ============ KOLOM KANAN ============ --}}
            <div class="col-lg-4">
                @if (isset($id))
                    <x-card title="Status" icon="ri-toggle-line">
                        <div class="mb-0">
                            <label for="status" class="form-label form-label-required">Status</label>
                            <select id="status" name="status" class="form-select" required>
                                <option value="aktif">Aktif</option>
                                <option value="nonaktif">Nonaktif</option>
                            </select>
                        </div>
                    </x-card>
                    <div class="mt-3"></div>
                @endif

                <x-card class="border-start border-4 border-info">
                    <h6 class="fw-bold mb-2 small">
                        <i class="ri ri-information-line text-info me-1"></i> Batas Normal
                    </h6>
                    <table class="small w-100">
                        <tr>
                            <td><strong>≤140 mg/dL</strong></td>
                            <td class="text-success">Normal</td>
                        </tr>
                        <tr>
                            <td><strong>141-199</strong></td>
                            <td class="text-warning">Tidak Terkontrol</td>
                        </tr>
                        <tr>
                            <td><strong>200-299</strong></td>
                            <td class="text-danger">Tinggi</td>
                        </tr>
                        <tr>
                            <td><strong>≥300</strong></td>
                            <td class="text-dark fw-bold">Berbahaya</td>
                        </tr>
                    </table>
                    <hr class="my-2">
                    <small class="text-muted">
                        Selisih dihitung dari batas normal per gender:
                        <br>Perempuan: <strong>140</strong>, Laki-laki: <strong>200</strong>
                    </small>
                </x-card>
            </div>
        </div>

        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-primary fw-semibold px-4" id="submitBtn">
                <span class="spinner-border spinner-border-sm d-none me-2"></span>
                <i class="ri ri-save-line me-1"></i> Simpan Hasil
            </button>
            <a href="{{ route('pengingat-cgd.index') }}" class="btn btn-outline-secondary">Batal</a>
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
                    INDEX: '{{ route('pengingat-cgd.index') }}',
                    STORE: '{{ route('pengingat-cgd.store') }}',
                    UPDATE: '{{ isset($id) ? route('pengingat-cgd.update', $id) : '' }}',
                    SHOW_DATA: '{{ isset($id) ? route('pengingat-cgd.show-data', $id) : '' }}',
                    OPT_JADWAL: '{{ route('pengingat-cgd.options.jadwal-cgd') }}',
                },
                // Threshold sesuai PengingatCgdLog model
                THRESHOLDS: {
                    NORMAL: 140,
                    TIDAK_TERKONTROL: 199,
                    TINGGI: 299,
                },
                BATAS_NORMAL: {
                    'L': 200,
                    'P': 140,
                },
                COMPRESS: {
                    MAX_WIDTH: 1920,
                    QUALITY: 0.85,
                },
            };

            const KATEGORI_INFO = {
                'normal': {
                    label: 'Normal Terkontrol',
                    pesan: 'Gula darah normal. Tetap patuh minum obat ya!',
                    color: 'success',
                    icon: 'ri-check-double-line',
                    alertClass: 'alert-success',
                },
                'tidak_terkontrol': {
                    label: 'Tidak Terkontrol',
                    pesan: 'Kurangi asupan gula. Patuh minum obat rutin ya!',
                    color: 'warning',
                    icon: 'ri-alert-line',
                    alertClass: 'alert-warning',
                },
                'tinggi': {
                    label: 'Tinggi',
                    pesan: 'Segera ke rumah sakit / puskesmas terdekat.',
                    color: 'danger',
                    icon: 'ri-alarm-warning-line',
                    alertClass: 'alert-danger',
                },
                'berbahaya': {
                    label: 'BERBAHAYA',
                    pesan: 'Anda memerlukan bantuan dokter SEKARANG juga!',
                    color: 'dark',
                    icon: 'ri-error-warning-fill',
                    alertClass: 'alert-dark',
                },
            };

            const $form = $('#cgdForm');
            const $submitBtn = $('#submitBtn');

            let allCgdEvents = [];
            let currentCgd = null;
            let selectedFile = null;
            let userGender = null; // 'L' / 'P' / null (akan di-load saat init)

            init();

            async function init() {
                await Promise.all([
                    loadCgdOptions(),
                    loadUserGender(),
                ]);

                if (CONFIG.IS_EDIT) {
                    await loadExistingData();
                } else {
                    // Set jam_cgd ke jam sekarang sebagai default
                    const now = new Date();
                    const hh = String(now.getHours()).padStart(2, '0');
                    const mm = String(now.getMinutes()).padStart(2, '0');
                    $('#jam_cgd').val(`${hh}:${mm}`);
                }

                // Event handlers
                $('#id_cgd').on('change', onCgdChange);
                $('#hasil_mgdl').on('input', updateKategoriPreview);
                $('#foto_layar').on('change', onPhotoChange);
                $('#btnClearPhoto').on('click', clearPhoto);
                $form.on('submit', submitForm);
            }

            async function loadCgdOptions() {
                try {
                    const res = await $.ajax({
                        url: CONFIG.ROUTES.OPT_JADWAL,
                        method: 'GET'
                    });
                    allCgdEvents = res.data;

                    const $select = $('#id_cgd');
                    allCgdEvents.forEach(c => {
                        $select.append(`<option value="${c.id}">${c.label}</option>`);
                    });

                    if (allCgdEvents.length === 1 && !CONFIG.IS_EDIT) {
                        $select.val(allCgdEvents[0].id).trigger('change');
                    } else if (allCgdEvents.length === 0) {
                        Swal.fire({
                            title: 'Belum Ada Event CGD',
                            text: 'Belum ada event CGD aktif yang bisa diinput hasilnya. Hubungi admin.',
                            icon: 'info',
                        });
                    }
                } catch (e) {
                    console.error('Load CGD options failed:', e);
                }
            }

            async function loadUserGender() {
                // Get user gender via API (simple workaround: pakai endpoint show-data kalau edit, atau dari current auth)
                // Untuk simplifikasi: pakai default 'P' (140 = konservatif) untuk preview
                // Saat submit, server-side akan auto-detect dari biodata pasien
                // Kalau mau lebih akurat, bisa buat endpoint /me untuk get current user biodata
                userGender = 'P'; // default fallback
            }

            function onCgdChange() {
                const id = $('#id_cgd').val();
                currentCgd = allCgdEvents.find(c => c.id === id);

                if (!currentCgd) {
                    $('#cgdInfoBox').addClass('d-none');
                    return;
                }

                $('#info-tempat').text(currentCgd.tempat);
                $('#info-tgl').text(currentCgd.tgl_display);
                $('#info-jam').text(currentCgd.jam);
                $('#info-puasa').html(
                    currentCgd.puasa === 'Wajib' ?
                    '<span class="text-warning"><strong>Wajib Puasa</strong></span>' :
                    '<span class="text-muted">Tidak Wajib</span>'
                );
                $('#cgdInfoBox').removeClass('d-none');
            }

            function updateKategoriPreview() {
                const hasil = parseInt($('#hasil_mgdl').val());

                if (!hasil || hasil < 20) {
                    $('#kategoriPreviewBox').hide();
                    return;
                }

                // Determine kategori (sama dengan PHP logic)
                let kategori;
                if (hasil <= CONFIG.THRESHOLDS.NORMAL) kategori = 'normal';
                else if (hasil <= CONFIG.THRESHOLDS.TIDAK_TERKONTROL) kategori = 'tidak_terkontrol';
                else if (hasil <= CONFIG.THRESHOLDS.TINGGI) kategori = 'tinggi';
                else kategori = 'berbahaya';

                const info = KATEGORI_INFO[kategori];

                // Calculate patuh selisih based on gender
                const batasNormal = CONFIG.BATAS_NORMAL[userGender] || 140;
                const selisih = hasil - batasNormal;
                const selisihStr = selisih > 0 ? `+${selisih}` : selisih;

                // Update alert
                $('#kategoriPreviewAlert').removeClass('alert-success alert-warning alert-danger alert-dark')
                    .addClass(info.alertClass);
                $('#kategoriIcon').removeClass().addClass(info.icon + ' text-' + info.color);
                $('#kategoriLabel').text(info.label).removeClass().addClass('fw-bold mb-1 text-' + info.color);
                $('#kategoriPesan').text(info.pesan);
                $('#patuhDisplay').html(`${selisihStr} <small class="text-muted">mg/dL</small>`);

                $('#kategoriPreviewBox').show();
            }

            // ============ PHOTO UPLOAD + COMPRESSION ============
            async function onPhotoChange(e) {
                const file = e.target.files[0];
                if (!file) return;

                if (!file.type.match(/image\/(jpeg|jpg|png|webp)/)) {
                    Swal.fire('Format Salah', 'File harus berupa gambar.', 'warning');
                    $('#foto_layar').val('');
                    return;
                }

                const originalSizeMB = file.size / 1024 / 1024;
                if (originalSizeMB > 12) {
                    Swal.fire('File Terlalu Besar', `Max 12MB. File kamu ${originalSizeMB.toFixed(1)} MB.`,
                        'warning');
                    $('#foto_layar').val('');
                    return;
                }

                $('#photoPreviewBox').show();
                $('#photoPreview').attr('src', URL.createObjectURL(file));
                $('#photoSize').text(`Memproses... (${originalSizeMB.toFixed(1)} MB)`);

                try {
                    const compressed = await compressImage(file);
                    selectedFile = compressed;

                    const compressedMB = compressed.size / 1024 / 1024;
                    const reduction = ((1 - compressed.size / file.size) * 100).toFixed(0);

                    $('#photoPreview').attr('src', URL.createObjectURL(compressed));
                    $('#photoSize').text(`${compressedMB.toFixed(2)} MB`);
                    $('#photoCompressInfo').html(
                        `<i class="ri ri-arrow-down-line text-success"></i> Dari ${originalSizeMB.toFixed(1)} MB (${reduction}% lebih kecil)`
                        );
                    $('#btnClearPhoto').show();
                    $('#foto_layar_error').text('');
                } catch (err) {
                    selectedFile = file;
                    $('#photoSize').text(`${originalSizeMB.toFixed(2)} MB`);
                    $('#photoCompressInfo').html(
                        `<i class="ri ri-information-line text-muted"></i> Compression skipped`);
                    $('#btnClearPhoto').show();
                }
            }

            function clearPhoto() {
                selectedFile = null;
                $('#foto_layar').val('');
                $('#photoPreview').attr('src', '');
                $('#photoPreviewBox').hide();
                $('#btnClearPhoto').hide();
                $('#photoSize').text('-');
                $('#photoCompressInfo').text('');
            }

            function compressImage(file) {
                return new Promise((resolve, reject) => {
                    const img = new Image();
                    img.onload = () => {
                        let {
                            width,
                            height
                        } = img;
                        if (width > CONFIG.COMPRESS.MAX_WIDTH) {
                            height = Math.round(height * (CONFIG.COMPRESS.MAX_WIDTH / width));
                            width = CONFIG.COMPRESS.MAX_WIDTH;
                        }
                        const canvas = document.createElement('canvas');
                        canvas.width = width;
                        canvas.height = height;
                        canvas.getContext('2d').drawImage(img, 0, 0, width, height);
                        canvas.toBlob(blob => {
                            if (!blob) return reject(new Error('Compression failed'));
                            resolve(new File([blob], file.name.replace(/\.[^.]+$/, '.jpg'), {
                                type: 'image/jpeg',
                                lastModified: Date.now()
                            }));
                        }, 'image/jpeg', CONFIG.COMPRESS.QUALITY);
                    };
                    img.onerror = () => reject(new Error('Failed to load image'));
                    img.src = URL.createObjectURL(file);
                });
            }

            // ============ LOAD EXISTING DATA ============
            async function loadExistingData() {
                try {
                    const data = await $.ajax({
                        url: CONFIG.ROUTES.SHOW_DATA,
                        method: 'GET'
                    });

                    $('#formTitle').text('Edit Hasil CGD: ' + (data.nama_pasien || '-'));

                    // Update user gender from existing log
                    userGender = data.jenis_kelamin;

                    // Set event (locked)
                    $('#id_cgd').val(data.id_cgd).trigger('change');

                    // Set fields
                    $('#tgl_cgd').val(data.tgl_cgd?.substr(0, 10) || '');
                    $('#jam_cgd').val(data.jam_cgd?.substr(0, 5) || '');
                    $('#hasil_mgdl').val(data.hasil_mgdl);

                    // Trigger preview
                    updateKategoriPreview();

                    // Status
                    $('#status').val(data.status);

                    // Existing foto
                    if (data.foto_layar) {
                        $('#existingPhoto').attr('src', '/storage/' + data.foto_layar);
                        $('#existingPhotoBox').show();
                        $('#foto_layar').prop('required', false);
                    }
                } catch (e) {
                    Swal.fire({
                        title: 'Error!',
                        text: 'Gagal memuat data log.',
                        icon: 'error',
                    }).then(() => window.location.href = CONFIG.ROUTES.INDEX);
                }
            }

            // ============ SUBMIT ============
            async function submitForm(e) {
                e.preventDefault();

                $form.find('.is-invalid').removeClass('is-invalid');
                $form.find('.invalid-feedback').text('');
                $('#foto_layar_error').text('');

                if (!CONFIG.IS_EDIT && !selectedFile) {
                    $('#foto_layar_error').text('Foto bukti hasil wajib diupload.');
                    Swal.fire('Validasi Gagal', 'Foto wajib diupload.', 'warning');
                    return;
                }

                $submitBtn.prop('disabled', true).find('.spinner-border').removeClass('d-none');

                const formData = new FormData();
                formData.append('_token', $('input[name=_token]').val());

                if (!CONFIG.IS_EDIT) {
                    formData.append('id_cgd', $('#id_cgd').val());
                }

                formData.append('tgl_cgd', $('#tgl_cgd').val());
                formData.append('jam_cgd', $('#jam_cgd').val());
                formData.append('hasil_mgdl', $('#hasil_mgdl').val());

                if (CONFIG.IS_EDIT) {
                    formData.append('status', $('#status').val());
                }

                if (selectedFile) {
                    formData.append('foto_layar', selectedFile, selectedFile.name);
                }

                const url = CONFIG.IS_EDIT ? CONFIG.ROUTES.UPDATE : CONFIG.ROUTES.STORE;

                try {
                    const res = await $.ajax({
                        url: url,
                        method: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
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
                            if (field === 'foto_layar') {
                                $('#foto_layar_error').text(messages[0]);
                                return;
                            }
                            const $field = $('[name="' + field + '"], #' + field);
                            $field.addClass('is-invalid');
                            $field.siblings('.invalid-feedback').text(messages[0]);
                        });
                        Swal.fire('Validasi Gagal', 'Mohon periksa kembali data.', 'warning');
                    } else {
                        Swal.fire('Gagal!', xhr.responseJSON?.message || 'Terjadi kesalahan', 'error');
                    }
                }
            }
        });
    </script>

    <style>
        #kategoriPreviewBox .alert {
            transition: all 0.3s;
        }

        #kategoriIcon {
            width: 50px;
            text-align: center;
        }
    </style>
@endpush
