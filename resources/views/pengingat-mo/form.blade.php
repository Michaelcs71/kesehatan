@extends('layouts.app')

@section('title', isset($id) ? 'Edit Konfirmasi' : 'Konfirmasi Minum Obat')

@section('page-header')
    <nav aria-label="breadcrumb" class="small">
        <ol class="breadcrumb mb-2">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('pengingat-mo.index') }}" class="text-decoration-none">Pengingat
                    Minum Obat</a></li>
            <li class="breadcrumb-item active">{{ isset($id) ? 'Edit' : 'Konfirmasi' }}</li>
        </ol>
    </nav>
    <div>
        <h4 class="fw-bold mb-1" id="formTitle">{{ isset($id) ? 'Edit Konfirmasi Minum Obat' : 'Konfirmasi Minum Obat' }}
        </h4>
        <small class="text-muted">Catat bukti minum obat dengan foto pillbox.</small>
    </div>
@endsection

@section('content')

    <form id="pmLogForm" novalidate enctype="multipart/form-data">
        @csrf
        @if (isset($id))
            <input type="hidden" id="logId" value="{{ $id }}">
        @endif

        <div class="row g-4">
            <div class="col-lg-8">

                {{-- ============ PILIH JADWAL ============ --}}
                <x-card title="Pilih Jadwal Minum Obat" icon="ri-calendar-check-line">
                    <div class="mb-0">
                        <label for="id_jo" class="form-label form-label-required">Jadwal</label>
                        <select id="id_jo" name="id_jo" class="form-select" required
                            {{ isset($id) ? 'disabled' : '' }}>
                            <option value="">-- Pilih jadwal --</option>
                            {{-- Filled by JS --}}
                        </select>
                        <div class="invalid-feedback"></div>
                        <small class="text-muted">
                            Pilih jadwal obat yang baru saja kamu minum.
                            @if (isset($id))
                                <br><strong>Catatan:</strong> Jadwal tidak bisa diubah saat edit.
                            @endif
                        </small>
                    </div>

                    {{-- Info jadwal yang dipilih --}}
                    <div id="jadwalInfoBox" class="alert alert-light border mt-3 d-none">
                        <div class="row g-2 small">
                            <div class="col-md-6">
                                <i class="ri ri-user-line text-muted"></i> <strong>Pasien:</strong> <span
                                    id="info-pasien">-</span>
                            </div>
                            <div class="col-md-6">
                                <i class="ri ri-medicine-bottle-line text-muted"></i> <strong>Obat:</strong> <span
                                    id="info-obat">-</span>
                            </div>
                            <div class="col-md-6">
                                <i class="ri ri-time-line text-muted"></i> <strong>Jam Mulai:</strong> <span
                                    id="info-jam-mulai">-</span>
                            </div>
                            <div class="col-md-6">
                                <i class="ri ri-repeat-line text-muted"></i> <strong>Frekuensi:</strong> <span
                                    id="info-frekuensi">-</span>x sehari
                            </div>
                        </div>
                    </div>
                </x-card>

                <div class="mt-3"></div>

                {{-- ============ WAKTU MINUM ============ --}}
                <x-card title="Waktu Minum Obat" icon="ri-time-line">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="tgl_minum_obat" class="form-label form-label-required">Tanggal Minum</label>
                            <input type="date" id="tgl_minum_obat" name="tgl_minum_obat" class="form-control"
                                value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="jam_minum_obat" class="form-label form-label-required">Jam Minum (Sekarang)</label>
                            <input type="time" id="jam_minum_obat" name="jam_minum_obat" class="form-control" required>
                            <div class="invalid-feedback"></div>
                            <small class="text-muted">Jam saat kamu minum obat tadi.</small>
                        </div>

                        <div class="col-md-12" id="slotSuggestionBox" style="display:none;">
                            <label class="form-label">Slot Jam Seharusnya (Pilih yang paling dekat)</label>
                            <div id="slotButtons" class="d-flex flex-wrap gap-2 mb-2">
                                {{-- Filled by JS --}}
                            </div>
                            <input type="hidden" id="jam_slot_target" name="jam_slot_target" value="">

                            <div id="patuhDisplay" class="mt-2" style="display:none;">
                                <small class="text-muted">Status kepatuhan:</small>
                                <div id="patuhBadge" class="d-inline-block ms-2"></div>
                            </div>
                        </div>
                    </div>
                </x-card>

                <div class="mt-3"></div>

                {{-- ============ UPLOAD FOTO ============ --}}
                <x-card title="Foto Bukti Minum Obat" icon="ri-camera-line">
                    <div class="mb-3">
                        <label for="foto_obat" class="form-label form-label-required">Upload Foto Pillbox / Obat</label>

                        <div class="d-flex gap-2 mb-2">
                            <label for="foto_obat" class="btn btn-outline-primary flex-grow-1">
                                <i class="ri ri-camera-line me-1"></i> Ambil Foto / Pilih dari Galeri
                            </label>
                            <button type="button" id="btnClearPhoto" class="btn btn-outline-danger" style="display:none;"
                                title="Hapus foto">
                                <i class="ri ri-close-line"></i>
                            </button>
                        </div>

                        <input type="file" id="foto_obat" name="foto_obat"
                            accept="image/jpeg,image/jpg,image/png,image/webp" capture="environment" class="d-none"
                            required>

                        <div class="invalid-feedback d-block" id="foto_obat_error"></div>

                        <small class="text-muted d-block">
                            <i class="ri ri-information-line"></i>
                            Foto akan otomatis dikompres. Max 8MB. Format: JPG/PNG/WEBP.
                            @if (isset($id))
                                <br><strong>Catatan:</strong> Kosongkan kalau tidak ingin ganti foto.
                            @endif
                        </small>
                    </div>

                    {{-- Preview foto --}}
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

                    {{-- Foto existing saat edit --}}
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

                <x-card class="border-start border-4 border-success">
                    <h6 class="fw-bold mb-2 small">
                        <i class="ri ri-information-line text-success me-1"></i> Tips
                    </h6>
                    <ul class="small text-muted mb-0 ps-3">
                        <li>Pastikan foto menunjukkan pillbox/obat dengan jelas</li>
                        <li>Pilih slot jam yang paling dekat dengan jam minum kamu</li>
                        <li>Sistem otomatis hitung kepatuhan dari selisih jam slot vs jam minum</li>
                    </ul>
                </x-card>
            </div>
        </div>

        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-primary fw-semibold px-4" id="submitBtn">
                <span class="spinner-border spinner-border-sm d-none me-2"></span>
                <i class="ri ri-save-line me-1"></i> Simpan Konfirmasi
            </button>
            <a href="{{ route('pengingat-mo.index') }}" class="btn btn-outline-secondary">Batal</a>
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
                    INDEX: '{{ route('pengingat-mo.index') }}',
                    STORE: '{{ route('pengingat-mo.store') }}',
                    UPDATE: '{{ isset($id) ? route('pengingat-mo.update', $id) : '' }}',
                    SHOW_DATA: '{{ isset($id) ? route('pengingat-mo.show-data', $id) : '' }}',
                    OPT_JADWAL: '{{ route('pengingat-mo.options.jadwal') }}',
                },
                COMPRESS: {
                    MAX_WIDTH: 1920, // max width client-side
                    QUALITY: 0.85, // 85% JPEG quality
                    MAX_SIZE_KB: 8192, // 8MB limit
                },
            };

            const $form = $('#pmLogForm');
            const $submitBtn = $('#submitBtn');

            let allJadwals = [];
            let currentJadwal = null;
            let selectedFile = null; // compressed file Blob

            init();

            async function init() {
                await loadJadwalOptions();

                if (CONFIG.IS_EDIT) {
                    await loadExistingData();
                } else {
                    // Set jam_minum_obat ke jam sekarang sebagai default
                    const now = new Date();
                    const hh = String(now.getHours()).padStart(2, '0');
                    const mm = String(now.getMinutes()).padStart(2, '0');
                    $('#jam_minum_obat').val(`${hh}:${mm}`);
                }

                // Event handlers
                $('#id_jo').on('change', onJadwalChange);
                $('#jam_minum_obat').on('input', updatePatuhPreview);
                $('#foto_obat').on('change', onPhotoChange);
                $('#btnClearPhoto').on('click', clearPhoto);
                $form.on('submit', submitForm);
            }

            async function loadJadwalOptions() {
                try {
                    const res = await $.ajax({
                        url: CONFIG.ROUTES.OPT_JADWAL,
                        method: 'GET'
                    });
                    allJadwals = res.data;

                    const $select = $('#id_jo');
                    allJadwals.forEach(j => {
                        $select.append(`<option value="${j.id}">${j.label}</option>`);
                    });

                    // Auto-select kalau cuma 1
                    if (allJadwals.length === 1 && !CONFIG.IS_EDIT) {
                        $select.val(allJadwals[0].id).trigger('change');
                    } else if (allJadwals.length === 0) {
                        Swal.fire({
                            title: 'Belum Ada Jadwal',
                            text: 'Kamu belum memiliki jadwal minum obat aktif. Buat jadwal di menu Jadwal Minum Obat dulu.',
                            icon: 'info',
                        });
                    }
                } catch (e) {
                    console.error('Load jadwal options failed:', e);
                }
            }

            function onJadwalChange() {
                const id = $('#id_jo').val();
                currentJadwal = allJadwals.find(j => j.id === id);

                if (!currentJadwal) {
                    $('#jadwalInfoBox').addClass('d-none');
                    $('#slotSuggestionBox').hide();
                    return;
                }

                // Show info
                $('#info-pasien').text(currentJadwal.nama_pasien);
                $('#info-obat').text(currentJadwal.nama_obat);
                $('#info-jam-mulai').text(currentJadwal.jam_mulai);
                $('#info-frekuensi').text(currentJadwal.frekuensi);
                $('#jadwalInfoBox').removeClass('d-none');

                // Render slot buttons
                renderSlotButtons();
            }

            function renderSlotButtons() {
                if (!currentJadwal || !currentJadwal.slot_jam || !currentJadwal.slot_jam.length) {
                    $('#slotSuggestionBox').hide();
                    return;
                }

                const $box = $('#slotButtons');
                const currentTime = $('#jam_minum_obat').val();

                // Cari slot terdekat
                let closestSlot = null;
                let minDiff = Infinity;
                if (currentTime) {
                    const currentMin = timeToMinutes(currentTime);
                    currentJadwal.slot_jam.forEach(slot => {
                        const slotMin = timeToMinutes(slot);
                        const diff = Math.abs(currentMin - slotMin);
                        if (diff < minDiff) {
                            minDiff = diff;
                            closestSlot = slot;
                        }
                    });
                }

                $box.empty();
                currentJadwal.slot_jam.forEach(slot => {
                    const isClosest = slot === closestSlot;
                    $box.append(`
                <button type="button" class="btn btn-sm ${isClosest ? 'btn-primary' : 'btn-outline-primary'} btn-slot"
                        data-slot="${slot}">
                    ${slot}
                    ${isClosest ? '<i class="ri ri-checkbox-circle-line ms-1"></i>' : ''}
                </button>
            `);
                });

                $('#slotSuggestionBox').show();

                // Auto-select closest
                if (closestSlot) {
                    $('#jam_slot_target').val(closestSlot);
                    updatePatuhPreview();
                }

                // Click handler
                $('.btn-slot').off('click').on('click', function() {
                    const slot = $(this).data('slot');
                    $('.btn-slot').removeClass('btn-primary').addClass('btn-outline-primary').find('i')
                        .remove();
                    $(this).removeClass('btn-outline-primary').addClass('btn-primary').append(
                        ' <i class="ri ri-checkbox-circle-line ms-1"></i>');
                    $('#jam_slot_target').val(slot);
                    updatePatuhPreview();
                });
            }

            function updatePatuhPreview() {
                const slot = $('#jam_slot_target').val();
                const actual = $('#jam_minum_obat').val();
                if (!slot || !actual) {
                    $('#patuhDisplay').hide();
                    return;
                }

                const slotMin = timeToMinutes(slot);
                const actualMin = timeToMinutes(actual);
                const diff = actualMin - slotMin;

                let kategori = 'tepat_waktu',
                    label = 'Tepat waktu',
                    color = 'success',
                    icon = 'ri-check-double-line';
                const absDiff = Math.abs(diff);

                if (absDiff > 60) {
                    kategori = 'sangat_terlambat';
                    label = (diff > 0 ? '+' : '') + diff + ' menit (sangat terlambat)';
                    color = 'danger';
                    icon = 'ri-alarm-warning-line';
                } else if (absDiff > 15) {
                    kategori = 'terlambat';
                    label = (diff > 0 ? '+' : '') + diff + ' menit ' + (diff > 0 ? '(telat)' : '(lebih awal)');
                    color = 'warning';
                    icon = 'ri-time-line';
                } else if (diff !== 0) {
                    label = (diff > 0 ? '+' : '') + diff + ' menit';
                }

                $('#patuhBadge').html(
                    `<span class="badge bg-${color}-subtle text-${color}"><i class="${icon} me-1"></i>${label}</span>`
                    );
                $('#patuhDisplay').show();

                // Re-render slot buttons to update closest indicator
                renderSlotButtons();
            }

            // ============ PHOTO UPLOAD + COMPRESSION ============
            async function onPhotoChange(e) {
                const file = e.target.files[0];
                if (!file) return;

                // Validation
                if (!file.type.match(/image\/(jpeg|jpg|png|webp)/)) {
                    Swal.fire('Format Salah', 'File harus berupa gambar JPG, PNG, atau WEBP.', 'warning');
                    $('#foto_obat').val('');
                    return;
                }

                const originalSizeMB = file.size / 1024 / 1024;
                if (originalSizeMB > 12) { // hard limit 12MB sebelum compress
                    Swal.fire('File Terlalu Besar',
                        `Maksimal 12MB sebelum compress. File kamu ${originalSizeMB.toFixed(1)} MB.`,
                        'warning');
                    $('#foto_obat').val('');
                    return;
                }

                // Show loading
                $('#photoPreviewBox').show();
                $('#photoPreview').attr('src', URL.createObjectURL(file));
                $('#photoSize').text(`Memproses... (${originalSizeMB.toFixed(1)} MB)`);

                try {
                    // Compress
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
                    $('#foto_obat_error').text('');
                    $('#foto_obat').removeClass('is-invalid');
                } catch (err) {
                    console.error('Compression failed:', err);
                    // Fallback: pakai file asli
                    selectedFile = file;
                    $('#photoSize').text(`${originalSizeMB.toFixed(2)} MB`);
                    $('#photoCompressInfo').html(
                        `<i class="ri ri-information-line text-muted"></i> Compression skipped`);
                    $('#btnClearPhoto').show();
                }
            }

            function clearPhoto() {
                selectedFile = null;
                $('#foto_obat').val('');
                $('#photoPreview').attr('src', '');
                $('#photoPreviewBox').hide();
                $('#btnClearPhoto').hide();
                $('#photoSize').text('-');
                $('#photoCompressInfo').text('');
            }

            /**
             * Client-side compression menggunakan Canvas API
             * Maintain aspect ratio + auto-rotate (EXIF) + JPEG quality
             */
            function compressImage(file) {
                return new Promise((resolve, reject) => {
                    const img = new Image();
                    img.onload = () => {
                        let {
                            width,
                            height
                        } = img;

                        // Scale down if exceeds max width
                        if (width > CONFIG.COMPRESS.MAX_WIDTH) {
                            height = Math.round(height * (CONFIG.COMPRESS.MAX_WIDTH / width));
                            width = CONFIG.COMPRESS.MAX_WIDTH;
                        }

                        const canvas = document.createElement('canvas');
                        canvas.width = width;
                        canvas.height = height;

                        const ctx = canvas.getContext('2d');
                        ctx.drawImage(img, 0, 0, width, height);

                        canvas.toBlob(blob => {
                            if (!blob) {
                                reject(new Error('Compression resulted in null blob'));
                                return;
                            }
                            // Wrap as File object
                            const compressedFile = new File([blob], file.name.replace(
                                /\.[^.]+$/, '.jpg'), {
                                type: 'image/jpeg',
                                lastModified: Date.now(),
                            });
                            resolve(compressedFile);
                        }, 'image/jpeg', CONFIG.COMPRESS.QUALITY);
                    };
                    img.onerror = () => reject(new Error('Failed to load image'));
                    img.src = URL.createObjectURL(file);
                });
            }

            // ============ LOAD EXISTING DATA (EDIT MODE) ============
            async function loadExistingData() {
                try {
                    const data = await $.ajax({
                        url: CONFIG.ROUTES.SHOW_DATA,
                        method: 'GET'
                    });

                    $('#formTitle').text('Edit Konfirmasi: ' + (data.nama_obat || '-'));

                    // Set jadwal (lock, sudah disabled)
                    $('#id_jo').val(data.id_jo).trigger('change');

                    // Set waktu
                    $('#tgl_minum_obat').val(data.tgl_minum_obat?.substr(0, 10) || '');
                    $('#jam_minum_obat').val(data.jam_minum_obat?.substr(0, 5) || '');
                    $('#jam_slot_target').val(data.jam_slot_target?.substr(0, 5) || '');

                    // Status
                    $('#status').val(data.status);

                    // Existing foto
                    if (data.foto_obat) {
                        $('#existingPhoto').attr('src', '/storage/' + data.foto_obat);
                        $('#existingPhotoBox').show();
                        $('#foto_obat').prop('required', false); // tidak wajib saat edit
                    }

                    // Re-render slot buttons after data loaded
                    setTimeout(() => {
                        renderSlotButtons();
                        updatePatuhPreview();
                    }, 100);
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
                $('#foto_obat_error').text('');

                // Validasi foto
                if (!CONFIG.IS_EDIT && !selectedFile) {
                    $('#foto_obat_error').text('Foto bukti minum obat wajib diupload.');
                    Swal.fire('Validasi Gagal', 'Foto wajib diupload sebagai bukti.', 'warning');
                    return;
                }

                $submitBtn.prop('disabled', true).find('.spinner-border').removeClass('d-none');

                // FormData karena ada file upload
                const formData = new FormData();
                formData.append('_token', $('input[name=_token]').val());

                if (!CONFIG.IS_EDIT) {
                    formData.append('id_jo', $('#id_jo').val());
                }

                formData.append('tgl_minum_obat', $('#tgl_minum_obat').val());
                formData.append('jam_minum_obat', $('#jam_minum_obat').val());

                const slot = $('#jam_slot_target').val();
                if (slot) formData.append('jam_slot_target', slot);

                if (CONFIG.IS_EDIT) {
                    formData.append('status', $('#status').val());
                }

                // Append file
                if (selectedFile) {
                    formData.append('foto_obat', selectedFile, selectedFile.name);
                }

                const url = CONFIG.IS_EDIT ? CONFIG.ROUTES.UPDATE : CONFIG.ROUTES.STORE;

                try {
                    const res = await $.ajax({
                        url: url,
                        method: 'POST',
                        data: formData,
                        processData: false, // jangan diserialize
                        contentType: false, // browser set boundary multipart
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
                            if (field === 'foto_obat') {
                                $('#foto_obat_error').text(messages[0]);
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

            // ============ HELPER ============
            function timeToMinutes(time) {
                const [h, m] = time.split(':').map(Number);
                return h * 60 + (m || 0);
            }
        });
    </script>

    <style>
        .btn-slot {
            min-width: 70px;
            transition: all 0.2s;
        }

        .btn-slot:hover {
            transform: translateY(-1px);
        }

        #photoPreview,
        #existingPhoto {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
    </style>
@endpush
