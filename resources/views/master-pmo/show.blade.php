@extends('layouts.app')

@section('title', 'Detail PMO')

@section('page-header')
    <a href="{{ route('admin.master.pmo') }}" class="small text-decoration-none">&larr; Kembali ke direktori</a>
    <h4 class="fw-bold mb-1 mt-1">{{ $d['nama'] }}</h4>
@endsection

@section('content')
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-semibold">Profil PMO</div>
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-3">Kontak</dt><dd class="col-9">{{ $d['kontak'] }}</dd>
                <dt class="col-3">Status</dt>
                <dd class="col-9">
                    <span class="badge {{ $d['is_active'] ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                        {{ $d['is_active'] ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </dd>
            </dl>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-semibold">Pasien Binaan</div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Nama</th><th>Status Diabetes</th><th>Kepatuhan</th><th>GD Terakhir</th></tr></thead>
                <tbody>
                    @forelse($d['binaan'] as $b)
                        <tr>
                            <td class="fw-semibold">{{ $b['nama'] }}</td>
                            <td>{{ $b['status_diabetes'] }}</td>
                            <td><span class="badge bg-info-subtle text-info">{{ $b['kepatuhan'] }}%</span></td>
                            <td>{{ $b['gd_terakhir'] !== null ? $b['gd_terakhir'].' mg/dL' : '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">Belum ada pasien binaan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
