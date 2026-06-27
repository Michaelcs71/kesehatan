@extends('layouts.app')

@section('title', 'Master PMO')

@section('page-header')
    <h4 class="fw-bold mb-1">👥 Master PMO</h4>
    <small class="text-muted">Direktori Pendamping Minum Obat.</small>
@endsection

@section('content')
    <form method="GET" class="mb-3">
        <div class="input-group" style="max-width: 360px;">
            <input type="text" name="cari" value="{{ $cari }}" class="form-control" placeholder="Cari nama / email...">
            <button class="btn btn-primary" type="submit"><i class="ri ri-search-line"></i></button>
        </div>
    </form>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr>
                    <th>Nama PMO</th><th>Kontak</th><th>Pasien Binaan</th><th>Status</th><th></th>
                </tr></thead>
                <tbody>
                    @forelse($daftar as $row)
                        <tr>
                            <td class="fw-semibold">{{ $row['nama'] }}</td>
                            <td>{{ $row['kontak'] }}</td>
                            <td><span class="badge bg-info-subtle text-info">{{ $row['jumlah_binaan'] }}</span></td>
                            <td>
                                <span class="badge {{ $row['is_active'] ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                                    {{ $row['is_active'] ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.master.pmo.show', $row['id']) }}" class="btn btn-sm btn-outline-primary">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">Belum ada PMO terdaftar.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white">{{ $daftar->links() }}</div>
    </div>
@endsection
