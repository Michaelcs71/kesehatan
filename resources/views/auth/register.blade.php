@extends('layouts.guest')

@section('title', 'Daftar Akun Baru')

@section('content')

    <div class="text-center mb-4">
        <h3 class="fw-bold mb-1">Daftar Akun</h3>
        <p class="text-muted small mb-0">Sistem Pengingat Diabetes Care</p>
    </div>

    <form method="POST" action="{{ route('register') }}" id="registerForm">
        @csrf

        {{-- ============ ROLE ============ --}}
        <div class="mb-4">
            <label class="form-label fw-semibold small">Daftar sebagai</label>
            <div class="row g-2">
                <div class="col-6">
                    <input type="radio" name="role" id="role-pasien" value="pasien" class="btn-check"
                        {{ old('role', 'pasien') == 'pasien' ? 'checked' : '' }} required>
                    <label class="btn btn-outline-primary w-100 py-3" for="role-pasien">
                        <div><i class="ri ri-user-heart-line" style="font-size: 1.5rem;"></i></div>
                        <div class="fw-semibold mt-1">Pasien</div>
                        <small class="d-block text-muted" style="font-size: 0.7rem;">Saya pengidap diabetes</small>
                    </label>
                </div>
                <div class="col-6">
                    <input type="radio" name="role" id="role-pmo" value="pmo" class="btn-check"
                        {{ old('role') == 'pmo' ? 'checked' : '' }}>
                    <label class="btn btn-outline-success w-100 py-3" for="role-pmo">
                        <div><i class="ri ri-team-line" style="font-size: 1.5rem;"></i></div>
                        <div class="fw-semibold mt-1">PMO</div>
                        <small class="d-block text-muted" style="font-size: 0.7rem;">Pendamping Minum Obat</small>
                    </label>
                </div>
            </div>
            @error('role')
                <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
        </div>

        {{-- ============ AKUN ============ --}}
        <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">
            <i class="ri ri-shield-user-line me-1"></i> Akun Login
        </h6>

        <div class="mb-3">
            <label for="name" class="form-label fw-semibold small">
                Nama Lengkap <span class="text-danger">*</span>
            </label>
            <input id="name" type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                value="{{ old('name') }}" required autofocus autocomplete="name" placeholder="contoh: Ahmad Fauzi">
            <small class="text-muted">Digunakan untuk login. Harus unik.</small>
            @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="whatsapp_number" class="form-label fw-semibold small">
                Nomor WhatsApp <span class="text-danger">*</span>
            </label>
            <input id="whatsapp_number" type="tel" name="whatsapp_number"
                class="form-control @error('whatsapp_number') is-invalid @enderror" value="{{ old('whatsapp_number') }}"
                required placeholder="contoh: 081234567890">
            <small class="text-muted">Bisa juga untuk login. Pengingat akan dikirim ke nomor ini.</small>
            @error('whatsapp_number')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label for="password" class="form-label fw-semibold small">
                    Password <span class="text-danger">*</span>
                </label>
                <input id="password" type="password" name="password"
                    class="form-control @error('password') is-invalid @enderror" required autocomplete="new-password"
                    placeholder="Min. 8 karakter">
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label for="password_confirmation" class="form-label fw-semibold small">
                    Konfirmasi Password <span class="text-danger">*</span>
                </label>
                <input id="password_confirmation" type="password" name="password_confirmation" class="form-control" required
                    autocomplete="new-password" placeholder="Ulangi password">
            </div>
        </div>

        {{-- ============ BIODATA - Identitas Utama (WAJIB) ============ --}}
        <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">
            <i class="ri ri-id-card-line me-1"></i> Biodata
        </h6>

        <div class="mb-3">
            <label for="nik" class="form-label fw-semibold small">
                NIK <span class="text-danger">*</span>
            </label>
            <input id="nik" type="text" name="nik" class="form-control @error('nik') is-invalid @enderror"
                value="{{ old('nik') }}" required maxlength="16" minlength="16" inputmode="numeric" pattern="[0-9]{16}"
                placeholder="16 digit NIK KTP">
            <small class="text-muted">16 digit Nomor Induk Kependudukan dari KTP.</small>
            @error('nik')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label fw-semibold small">
                    Jenis Kelamin <span class="text-danger">*</span>
                </label>
                <div class="d-flex gap-2">
                    <div class="form-check flex-fill">
                        <input class="form-check-input" type="radio" name="jenis_kelamin" id="jk-L"
                            value="L" {{ old('jenis_kelamin') == 'L' ? 'checked' : '' }} required>
                        <label class="form-check-label" for="jk-L">Laki-laki</label>
                    </div>
                    <div class="form-check flex-fill">
                        <input class="form-check-input" type="radio" name="jenis_kelamin" id="jk-P"
                            value="P" {{ old('jenis_kelamin') == 'P' ? 'checked' : '' }}>
                        <label class="form-check-label" for="jk-P">Perempuan</label>
                    </div>
                </div>
                @error('jenis_kelamin')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label for="tanggal_lahir" class="form-label fw-semibold small">
                    Tanggal Lahir <span class="text-danger">*</span>
                </label>
                <input id="tanggal_lahir" type="date" name="tanggal_lahir"
                    class="form-control @error('tanggal_lahir') is-invalid @enderror" value="{{ old('tanggal_lahir') }}"
                    required max="{{ date('Y-m-d') }}">
                @error('tanggal_lahir')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="mb-4">
            <label for="tempat_lahir" class="form-label fw-semibold small">
                Tempat Lahir <span class="text-danger">*</span>
            </label>
            <input id="tempat_lahir" type="text" name="tempat_lahir"
                class="form-control @error('tempat_lahir') is-invalid @enderror" value="{{ old('tempat_lahir') }}"
                required maxlength="50" placeholder="contoh: Malang">
            @error('tempat_lahir')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        {{-- ============ BIODATA - Detail Tambahan (OPSIONAL, Collapsed) ============ --}}
        <div class="mb-3">
            <button class="btn btn-outline-secondary btn-sm w-100" type="button" data-bs-toggle="collapse"
                data-bs-target="#detailBiodata">
                <i class="ri ri-add-line"></i> Lengkapi Alamat & No. KK (Opsional)
            </button>
        </div>

        <div class="collapse mb-4" id="detailBiodata">
            <div class="card card-body bg-light border-0">
                <div class="mb-3">
                    <label for="no_kk" class="form-label fw-semibold small">No. KK</label>
                    <input id="no_kk" type="text" name="no_kk"
                        class="form-control @error('no_kk') is-invalid @enderror" value="{{ old('no_kk') }}"
                        maxlength="16" minlength="16" inputmode="numeric" pattern="[0-9]{16}"
                        placeholder="16 digit Nomor Kartu Keluarga">
                    @error('no_kk')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="alamat_jalan" class="form-label fw-semibold small">Alamat Jalan</label>
                    <input id="alamat_jalan" type="text" name="alamat_jalan" class="form-control"
                        value="{{ old('alamat_jalan') }}" placeholder="contoh: Jl. Merpati No 10">
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-3">
                        <label for="alamat_rt" class="form-label fw-semibold small">RT</label>
                        <input id="alamat_rt" type="text" name="alamat_rt" class="form-control"
                            value="{{ old('alamat_rt') }}" maxlength="5" placeholder="003">
                    </div>
                    <div class="col-3">
                        <label for="alamat_rw" class="form-label fw-semibold small">RW</label>
                        <input id="alamat_rw" type="text" name="alamat_rw" class="form-control"
                            value="{{ old('alamat_rw') }}" maxlength="5" placeholder="007">
                    </div>
                    <div class="col-6">
                        <label for="alamat_kodepos" class="form-label fw-semibold small">Kode Pos</label>
                        <input id="alamat_kodepos" type="text" name="alamat_kodepos" class="form-control"
                            value="{{ old('alamat_kodepos') }}" maxlength="10" placeholder="40123">
                    </div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label for="alamat_dusun" class="form-label fw-semibold small">Dusun</label>
                        <input id="alamat_dusun" type="text" name="alamat_dusun" class="form-control"
                            value="{{ old('alamat_dusun') }}" maxlength="100" placeholder="Dusun Lebakrejo">
                    </div>
                    <div class="col-md-6">
                        <label for="alamat_desa" class="form-label fw-semibold small">Desa/Kelurahan</label>
                        <input id="alamat_desa" type="text" name="alamat_desa" class="form-control"
                            value="{{ old('alamat_desa') }}" maxlength="100" placeholder="Desa Ngenep">
                    </div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label for="alamat_kecamatan" class="form-label fw-semibold small">Kecamatan</label>
                        <input id="alamat_kecamatan" type="text" name="alamat_kecamatan" class="form-control"
                            value="{{ old('alamat_kecamatan') }}" maxlength="100" placeholder="Sukajadi">
                    </div>
                    <div class="col-md-6">
                        <label for="alamat_kabupaten" class="form-label fw-semibold small">Kabupaten/Kota</label>
                        <input id="alamat_kabupaten" type="text" name="alamat_kabupaten" class="form-control"
                            value="{{ old('alamat_kabupaten') }}" maxlength="100" placeholder="Kota Bandung">
                    </div>
                </div>

                <div class="mb-0">
                    <label for="alamat_provinsi" class="form-label fw-semibold small">Provinsi</label>
                    <input id="alamat_provinsi" type="text" name="alamat_provinsi" class="form-control"
                        value="{{ old('alamat_provinsi') }}" maxlength="100" placeholder="Jawa Barat">
                </div>
            </div>
        </div>

        {{-- ============ SUBMIT ============ --}}
        <button type="submit" class="btn btn-primary btn-lg w-100 fw-semibold mb-3">
            <i class="ri ri-user-add-line me-1"></i> Daftar Sekarang
        </button>

        <div class="text-center">
            <span class="text-muted small">Sudah punya akun?</span>
            <a href="{{ route('login') }}" class="small fw-semibold text-decoration-none">Masuk di sini</a>
        </div>
    </form>

    @push('scripts')
        <script>
            // Auto-open detail biodata kalau ada error di field-nya
            document.addEventListener('DOMContentLoaded', function() {
                const detailFields = ['no_kk', 'alamat_jalan', 'alamat_rt', 'alamat_rw', 'alamat_dusun',
                    'alamat_desa', 'alamat_kecamatan', 'alamat_kabupaten',
                    'alamat_provinsi', 'alamat_kodepos'
                ];
                const hasDetailError = detailFields.some(f => document.querySelector(`#${f}.is-invalid`));
                if (hasDetailError) {
                    const collapse = new bootstrap.Collapse(document.getElementById('detailBiodata'), {
                        show: true
                    });
                }
            });
        </script>
    @endpush

@endsection
