{{-- ============================================================
     MODAL IMPORT EXCEL - 3 Step Wizard
     ============================================================ --}}
<div class="modal fade" id="importExcelModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
    data-bs-keyboard="false">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">

            {{-- Header --}}
            <div class="modal-header bg-light">
                <div>
                    <h5 class="modal-title fw-bold mb-1">
                        <i class="ri ri-file-excel-2-line text-success me-2"></i>
                        Import User dari Excel
                    </h5>
                    <small class="text-muted">
                        Upload file Excel berisi data user untuk import bulk.
                    </small>
                </div>
                <button type="button" class="btn-close" id="btnCloseImportModal"></button>
            </div>

            {{-- Step Indicator --}}
            <div class="px-4 pt-3 pb-2 bg-white border-bottom">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="step-item active" data-step="1">
                        <div class="step-circle">1</div>
                        <div class="step-label">Upload File</div>
                    </div>
                    <div class="step-line"></div>
                    <div class="step-item" data-step="2">
                        <div class="step-circle">2</div>
                        <div class="step-label">Preview & Validasi</div>
                    </div>
                    <div class="step-line"></div>
                    <div class="step-item" data-step="3">
                        <div class="step-circle">3</div>
                        <div class="step-label">Selesai</div>
                    </div>
                </div>
            </div>

            <div class="modal-body p-0">

                {{-- ============ STEP 1: UPLOAD ============ --}}
                <div class="step-content" data-step="1">
                    <div class="p-4">
                        {{-- Info Alert --}}
                        <div class="alert alert-info border-0 small mb-4">
                            <div class="d-flex">
                                <i class="ri ri-information-line fs-4 me-2"></i>
                                <div>
                                    <strong>Langkah Import:</strong>
                                    <ol class="mb-0 mt-1">
                                        <li>Download template Excel di bawah</li>
                                        <li>Isi data user (bisa multiple user dalam 1 file)</li>
                                        <li>Upload file yang sudah diisi</li>
                                        <li>Review preview, perbaiki konflik kalau ada</li>
                                        <li>Konfirmasi import</li>
                                    </ol>
                                </div>
                            </div>
                        </div>

                        {{-- Download Template Card --}}
                        <div class="card border-success-subtle mb-4">
                            <div class="card-body d-flex align-items-center justify-content-between flex-wrap gap-3">
                                <div>
                                    <h6 class="fw-bold mb-1">
                                        <i class="ri ri-download-cloud-2-line text-success me-1"></i>
                                        Template Excel
                                    </h6>
                                    <small class="text-muted">Belum punya template? Download dulu di sini.</small>
                                </div>
                                <a href="{{ route('master-user.import.template') }}"
                                    class="btn btn-outline-success btn-sm">
                                    <i class="ri ri-download-line me-1"></i> Download Template
                                </a>
                            </div>
                        </div>

                        {{-- Upload Area --}}
                        <div class="upload-zone" id="uploadZone">
                            <input type="file" id="importFileInput" accept=".xlsx,.xls" hidden>

                            <div class="upload-content text-center py-5">
                                <i class="ri ri-upload-cloud-2-line display-1 text-primary opacity-50 mb-2"></i>
                                <h6 class="fw-bold mb-2">Drag & drop file Excel di sini</h6>
                                <p class="text-muted small mb-3">atau klik tombol di bawah</p>
                                <button type="button" class="btn btn-primary" id="btnSelectFile">
                                    <i class="ri ri-folder-open-line me-1"></i> Pilih File
                                </button>
                                <div class="mt-3">
                                    <small class="text-muted">
                                        Format: .xlsx atau .xls &middot; Maksimal 5 MB
                                    </small>
                                </div>
                            </div>

                            {{-- File preview after select --}}
                            <div class="upload-file-preview py-5 text-center" style="display:none;">
                                <i class="ri ri-file-excel-2-fill display-1 text-success mb-2"></i>
                                <h6 class="fw-bold mb-1" id="selectedFileName">-</h6>
                                <p class="text-muted small mb-3" id="selectedFileSize">-</p>
                                <div class="d-flex gap-2 justify-content-center">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btnClearFile">
                                        <i class="ri ri-close-line"></i> Ganti File
                                    </button>
                                    <button type="button" class="btn btn-primary" id="btnUploadFile">
                                        <span class="spinner-border spinner-border-sm d-none me-2"></span>
                                        <i class="ri ri-arrow-right-line me-1"></i> Lanjutkan
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ============ STEP 2: PREVIEW ============ --}}
                <div class="step-content" data-step="2" style="display:none;">
                    {{-- Summary Bar --}}
                    <div class="p-3 bg-light border-bottom" id="previewSummary">
                        <div class="row g-2 align-items-center">
                            <div class="col-md-8">
                                <div class="d-flex flex-wrap gap-2">
                                    <span class="badge bg-primary px-3 py-2">
                                        Total: <strong id="sumTotal">0</strong>
                                    </span>
                                    <span class="badge bg-success px-3 py-2">
                                        <i class="ri ri-check-line"></i> Valid: <strong id="sumValid">0</strong>
                                    </span>
                                    <span class="badge bg-warning text-dark px-3 py-2">
                                        <i class="ri ri-error-warning-line"></i> Konflik: <strong
                                            id="sumConflict">0</strong>
                                    </span>
                                    <span class="badge bg-danger px-3 py-2">
                                        <i class="ri ri-close-circle-line"></i> Error: <strong id="sumError">0</strong>
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-4 text-md-end">
                                <div class="form-check form-switch d-inline-block">
                                    <input type="checkbox" id="filterValidOnly" class="form-check-input">
                                    <label for="filterValidOnly" class="form-check-label small">Tampilkan yang
                                        bermasalah saja</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Preview Table --}}
                    <div class="px-3 py-2" id="previewTableWrapper">
                        <div class="text-center py-5">
                            <span class="spinner-border" role="status"></span>
                            <div class="mt-2 text-muted">Memuat preview...</div>
                        </div>
                    </div>

                    {{-- Edit Row Inline Form (template - hidden) --}}
                    <div id="editRowTemplate" style="display:none;">
                        {{-- Filled by JS --}}
                    </div>
                </div>

                {{-- ============ STEP 3: RESULT ============ --}}
                <div class="step-content" data-step="3" style="display:none;">
                    <div class="p-4 text-center">
                        <div id="resultContent">
                            <i class="ri ri-checkbox-circle-fill display-1 text-success mb-3"></i>
                            <h4 class="fw-bold mb-2">Import Selesai!</h4>
                            <p class="text-muted mb-4" id="resultMessage">-</p>

                            <div class="row g-3 justify-content-center mb-4" id="resultStats">
                                <div class="col-md-4">
                                    <div class="card bg-success-subtle border-0">
                                        <div class="card-body py-3">
                                            <div class="text-muted small">Berhasil</div>
                                            <div class="fw-bold fs-3 text-success" id="resultImported">0</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-warning-subtle border-0">
                                        <div class="card-body py-3">
                                            <div class="text-muted small">Di-skip</div>
                                            <div class="fw-bold fs-3 text-warning" id="resultSkipped">0</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div id="resultFailedList" class="text-start small" style="display:none;">
                                <h6 class="fw-bold mb-2">Row yang gagal:</h6>
                                <ul class="mb-0" id="resultFailedItems"></ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="btnFooterClose">
                    Batal
                </button>

                <button type="button" class="btn btn-outline-secondary" id="btnPrevStep" style="display:none;">
                    <i class="ri ri-arrow-left-line me-1"></i> Kembali
                </button>

                <button type="button" class="btn btn-success" id="btnConfirmImport" style="display:none;">
                    <span class="spinner-border spinner-border-sm d-none me-2"></span>
                    <i class="ri ri-check-double-line me-1"></i> Konfirmasi Import
                </button>

                <button type="button" class="btn btn-primary" id="btnFinish" style="display:none;"
                    data-bs-dismiss="modal">
                    <i class="ri ri-check-line me-1"></i> Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    /* ============ STEP INDICATOR ============ */
    .step-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: #adb5bd;
        transition: color 0.2s;
    }

    .step-item .step-circle {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background-color: #e9ecef;
        color: #6c757d;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 0.875rem;
        transition: all 0.2s;
    }

    .step-item .step-label {
        font-size: 0.875rem;
        font-weight: 500;
    }

    .step-item.active {
        color: var(--bs-primary, #0d6efd);
    }

    .step-item.active .step-circle {
        background-color: var(--bs-primary, #0d6efd);
        color: #fff;
    }

    .step-item.completed {
        color: #198754;
    }

    .step-item.completed .step-circle {
        background-color: #198754;
        color: #fff;
    }

    .step-line {
        flex: 1;
        height: 2px;
        background-color: #e9ecef;
        margin: 0 1rem;
    }

    /* ============ UPLOAD ZONE ============ */
    .upload-zone {
        border: 2px dashed #adb5bd;
        border-radius: 12px;
        background-color: #f8f9fa;
        transition: all 0.2s;
        cursor: pointer;
    }

    .upload-zone:hover,
    .upload-zone.drag-over {
        border-color: var(--bs-primary, #0d6efd);
        background-color: #e7f1ff;
    }

    .upload-zone.drag-over .upload-content i {
        transform: scale(1.1);
        transition: transform 0.2s;
    }

    /* ============ PREVIEW TABLE ============ */
    .preview-table {
        font-size: 0.85rem;
    }

    .preview-table thead th {
        background-color: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
        font-weight: 600;
        font-size: 0.75rem;
        text-transform: uppercase;
        position: sticky;
        top: 0;
        z-index: 1;
    }

    .preview-table tbody tr.row-valid {
        background-color: #f0fdf4;
    }

    .preview-table tbody tr.row-conflict {
        background-color: #fefce8;
    }

    .preview-table tbody tr.row-error {
        background-color: #fef2f2;
    }

    .preview-table tbody tr.row-skipped {
        background-color: #f3f4f6;
        opacity: 0.5;
    }

    .preview-table tbody tr.row-skipped td {
        text-decoration: line-through;
        color: #6b7280;
    }

    .preview-table .row-status {
        font-size: 0.7rem;
        padding: 4px 8px;
        border-radius: 6px;
        font-weight: 600;
    }

    .preview-table .edit-input {
        font-size: 0.85rem;
        padding: 4px 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
        width: 100%;
        background: #fff;
    }

    .preview-table .edit-input.is-invalid {
        border-color: #dc3545;
        background-color: #fef2f2;
    }

    .preview-table .field-error {
        font-size: 0.7rem;
        color: #dc3545;
        margin-top: 2px;
    }

    .preview-table td {
        vertical-align: middle;
        padding: 8px 6px;
    }
</style>
