@extends('layouts.app')

@section('title', 'Detail Pasien')

@section('page-header')
    <a href="{{ route('admin.master.pasien') }}" class="small text-decoration-none">&larr; Kembali ke direktori</a>
    <div class="d-flex align-items-center justify-content-between mt-1">
        <h4 class="fw-bold mb-1">{{ $d['nama'] }}</h4>
        @can('master-user.edit')
            <a href="{{ route('master-user.edit', $id) }}" class="btn btn-sm btn-outline-secondary">Kelola akun</a>
        @endcan
    </div>
@endsection

@section('content')
    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">Profil &amp; PMO</div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-5">NIK</dt><dd class="col-7">{{ $d['nik'] }}</dd>
                        <dt class="col-5">Jenis Kelamin</dt><dd class="col-7">{{ $d['jenis_kelamin'] }}</dd>
                        <dt class="col-5">Tanggal Lahir</dt><dd class="col-7">{{ $d['tanggal_lahir']?->translatedFormat('d F Y') ?? '-' }}</dd>
                        <dt class="col-5">Alamat</dt><dd class="col-7">{{ $d['alamat'] }}</dd>
                        <dt class="col-5">Kontak</dt><dd class="col-7">{{ $d['kontak'] }}</dd>
                        <dt class="col-5">Status Diabetes</dt><dd class="col-7">{{ $d['status_diabetes'] }}</dd>
                        <dt class="col-5">PMO Pendamping</dt>
                        <dd class="col-7">{{ $d['pmo']['nama'] ?? 'Belum ada' }} @if($d['pmo']) <small class="text-muted">({{ $d['pmo']['kontak'] }})</small> @endif</dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">Ringkasan Kepatuhan</div>
                <div class="card-body d-flex gap-4">
                    <div><div class="h3 fw-bold mb-0">{{ $d['kepatuhan'] }}%</div><small class="text-muted">Kepatuhan obat (30 hari)</small></div>
                    <div><div class="h3 fw-bold mb-0">{{ $d['jumlah_cgd'] }}</div><small class="text-muted">Jumlah cek gula darah</small></div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">Jadwal Aktif</div>
                <div class="card-body">
                    <h6 class="small text-muted">Minum Obat</h6>
                    <ul class="list-unstyled mb-3">
                        @forelse($d['jadwal_mo'] as $j)
                            <li>{{ $j['obat'] }} &mdash; {{ $j['jam'] }} ({{ $j['frekuensi'] }}x/hari)</li>
                        @empty
                            <li class="text-muted">Belum ada jadwal obat aktif.</li>
                        @endforelse
                    </ul>
                    <h6 class="small text-muted">Cek Gula Darah (mendatang)</h6>
                    <ul class="list-unstyled mb-0">
                        @forelse($d['jadwal_cgd'] as $j)
                            <li>{{ $j['tanggal']->translatedFormat('d M Y') }} &mdash; {{ $j['jam'] }} @if($j['tempat']) &bull; {{ $j['tempat'] }} @endif</li>
                        @empty
                            <li class="text-muted">Belum ada jadwal CGD mendatang.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">Riwayat Terbaru</div>
                <div class="card-body">
                    <h6 class="small text-muted">Minum Obat</h6>
                    <ul class="list-unstyled mb-3">
                        @forelse($d['riwayat_mo'] as $log)
                            <li>{{ $log->tgl_minum_obat->translatedFormat('d M') }} &mdash; {{ $log->nama_obat }} <span class="badge bg-{{ $log->patuh_badge_color }}-subtle text-{{ $log->patuh_badge_color }}">{{ $log->patuh_label }}</span></li>
                        @empty
                            <li class="text-muted">Belum ada riwayat.</li>
                        @endforelse
                    </ul>
                    <h6 class="small text-muted">Cek Gula Darah</h6>
                    <ul class="list-unstyled mb-0">
                        @forelse($d['riwayat_cgd'] as $log)
                            <li>{{ $log->tgl_cgd->translatedFormat('d M') }} &mdash; {{ $log->hasil_mgdl }} mg/dL <span class="badge bg-{{ $log->kategori_color }}-subtle text-{{ $log->kategori_color }}">{{ $log->kategori_label }}</span></li>
                        @empty
                            <li class="text-muted">Belum ada riwayat.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection
