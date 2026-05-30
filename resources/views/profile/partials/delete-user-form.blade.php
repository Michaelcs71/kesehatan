<button type="button" class="btn btn-danger fw-semibold" data-coreui-toggle="modal" data-coreui-target="#deleteUserModal">
    🗑️ Hapus Akun Saya
</button>

{{-- Modal --}}
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <form method="POST" action="{{ route('profile.destroy') }}">
                @csrf
                @method('delete')

                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold text-danger">⚠️ Hapus Akun Permanen</h5>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <p class="text-muted small">
                        Yakin mau hapus akun? Setelah dihapus, <strong>seluruh data akan hilang permanen</strong> dan tidak bisa dikembalikan.
                        Masukkan password Anda untuk konfirmasi.
                    </p>

                    <div class="mt-3">
                        <label for="password" class="form-label fw-semibold small">Password</label>
                        <input id="password" type="password" name="password"
                               class="form-control @error('password', 'userDeletion') is-invalid @enderror"
                               placeholder="Password Anda" autocomplete="current-password">
                        @error('password', 'userDeletion')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger fw-semibold">
                        🗑️ Hapus Akun Saya
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@if ($errors->userDeletion->isNotEmpty())
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            new coreui.Modal(document.getElementById('deleteUserModal')).show();
        });
    </script>
@endif
