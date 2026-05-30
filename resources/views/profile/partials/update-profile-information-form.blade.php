<form method="POST" action="{{ route('profile.update') }}">
    @csrf
    @method('patch')

    <div class="row g-3">
        <div class="col-md-6">
            <label for="name" class="form-label fw-semibold small">Nama Lengkap</label>
            <input type="text" id="name" name="name"
                   class="form-control @error('name', 'updateProfileInformation') is-invalid @enderror"
                   value="{{ old('name', $user->name) }}" required autofocus>
            @error('name', 'updateProfileInformation')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-6">
            <label for="email" class="form-label fw-semibold small">Email</label>
            <input type="email" id="email" name="email"
                   class="form-control @error('email', 'updateProfileInformation') is-invalid @enderror"
                   value="{{ old('email', $user->email) }}" required>
            @error('email', 'updateProfileInformation')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="mt-2 small">
                    <span class="text-warning">⚠️ Email Anda belum diverifikasi.</span>
                    <button form="send-verification" class="btn btn-link p-0 small text-decoration-underline">
                        Kirim ulang email verifikasi
                    </button>

                    @if (session('status') === 'verification-link-sent')
                        <div class="text-success small mt-1">✅ Link verifikasi baru sudah dikirim.</div>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <div class="d-flex align-items-center gap-3 mt-4">
        <button type="submit" class="btn btn-primary fw-semibold">💾 Simpan Perubahan</button>

        @if (session('status') === 'profile-updated')
            <span class="text-success small">✅ Tersimpan.</span>
        @endif
    </div>
</form>

<form id="send-verification" method="POST" action="{{ route('verification.send') }}">
    @csrf
</form>
