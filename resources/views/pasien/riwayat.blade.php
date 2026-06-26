@extends('layouts.app')

@section('title', 'Riwayat')

@section('page-header')
    <h4 class="fw-bold mb-1">📋 Riwayat</h4>
    <small class="text-muted">Riwayat minum obat & cek gula darah Anda.</small>
@endsection

@section('content')
    {{-- Banner konfirmasi pending (MO) --}}
    @if($pending->isNotEmpty())
        <div class="card border-0 shadow-sm mb-4 border-start border-warning border-4">
            <div class="card-body">
                <h6 class="fw-bold mb-3">⏰ Perlu Konfirmasi</h6>
                <div class="list-group list-group-flush">
                    @foreach($pending as $k)
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <div class="fw-semibold">Minum Obat</div>
                                <small class="text-muted">Jadwal pukul {{ $k->waktu_jadwal->format('H:i') }}, {{ $k->waktu_jadwal->translatedFormat('d M Y') }}</small>
                            </div>
                            <a href="{{ route('pengingat.konfirmasi.show', $k->id) }}" class="btn btn-sm btn-warning">
                                Konfirmasi
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- Tabs --}}
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link {{ $tab === 'obat' ? 'active' : '' }}" href="{{ route('pasien.riwayat', ['tab' => 'obat']) }}">Minum Obat</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $tab === 'gula' ? 'active' : '' }}" href="{{ route('pasien.riwayat', ['tab' => 'gula']) }}">Cek Gula Darah</a>
        </li>
    </ul>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                @if($tab === 'obat')
                    <thead><tr><th>Tanggal</th><th>Jam</th><th>Obat</th><th>Ketepatan</th></tr></thead>
                    <tbody>
                        @forelse($riwayatMo as $log)
                            <tr>
                                <td>{{ $log->tgl_minum_obat->translatedFormat('d M Y') }}</td>
                                <td>{{ $log->jam_minum_obat_format }}</td>
                                <td>{{ $log->nama_obat }}</td>
                                <td><span class="badge bg-{{ $log->patuh_badge_color }}-subtle text-{{ $log->patuh_badge_color }}">{{ $log->patuh_label }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">Belum ada riwayat minum obat.</td></tr>
                        @endforelse
                    </tbody>
                @else
                    <thead><tr><th>Tanggal</th><th>Jam</th><th>Hasil (mg/dL)</th><th>Kategori</th><th>Tempat</th></tr></thead>
                    <tbody>
                        @forelse($riwayatCgd as $log)
                            <tr>
                                <td>{{ $log->tgl_cgd->translatedFormat('d M Y') }}</td>
                                <td>{{ $log->jam_cgd_format }}</td>
                                <td class="fw-semibold">{{ $log->hasil_mgdl }}</td>
                                <td><span class="badge bg-{{ $log->kategori_color }}-subtle text-{{ $log->kategori_color }}">{{ $log->kategori_label }}</span></td>
                                <td>{{ $log->tempat_cgd }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">Belum ada riwayat cek gula darah.</td></tr>
                        @endforelse
                    </tbody>
                @endif
            </table>
        </div>
        <div class="card-footer bg-white">
            @if($tab === 'obat' && $riwayatMo)
                {{ $riwayatMo->links() }}
            @elseif($tab === 'gula' && $riwayatCgd)
                {{ $riwayatCgd->links() }}
            @endif
        </div>
    </div>
@endsection
