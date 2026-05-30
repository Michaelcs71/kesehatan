<form method="POST" action="{{ route('password.update') }}">
    @csrf
    @method('put')

    <div class="row g-3">
        <div class="col-md-12">
            <label for="update_password_current_password" class="form-label fw-semibold small">Password Saat Ini</label>
            <input type="password" id="update_password_current_password" name="current_password"
                   class="form-control @error('current_password', 'updatePassword') is-invalid @enderror"
                   autocomplete="current-password">
            @error('current_password', 'updatePassword')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-6">
            <label for="update_password_password" class="form-label fw-semibold small">Password Baru</label>
            <input type="password" id="update_password_password" name="password"
                   class="form-control @error('password', 'updatePassword') is-invalid @enderror"
                   autocomplete="new-password">
            @error('password', 'updatePassword')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-6">
            <label for="update_password_password_confirmation" class="form-label fw-semibold small">Konfirmasi Password Baru</label>
            <input type="password" id="update_password_password_confirmation" name="password_confirmation"
                   class="form-control" autocomplete="new-password">
        </div>
    </div>

    <div class="d-flex align-items-center gap-3 mt-4">
        <button type="submit" class="btn btn-primary fw-semibold">🔒 Update Password</button>

        @if (session('status') === 'password-updated')
            <span class="text-success small">✅ Password tersimpan.</span>
        @endif
    </div>
</form>
