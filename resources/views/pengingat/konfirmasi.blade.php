@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width: 480px;">
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Konfirmasi Minum Obat</h5>
            <p class="mb-1"><strong>{{ $namaObat }}</strong></p>
            <p class="text-muted">Jadwal jam {{ $jamSlot }}</p>

            @if($kejadian->status !== \App\Models\PengingatKejadian::STATUS_MENUNGGU)
                <div class="alert alert-info">Pengingat ini sudah ditindaklanjuti.</div>
            @else
                <form method="POST" action="{{ route('pengingat.konfirmasi.store', $kejadian->id) }}" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Foto bukti minum obat</label>
                        <input type="file" name="foto_obat" accept="image/*" capture="environment" class="form-control @error('foto_obat') is-invalid @enderror" required>
                        @error('foto_obat')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="btn btn-success w-100">
                        <i class="ri-check-line"></i> Sudah Minum Obat
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
