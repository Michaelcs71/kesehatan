@extends('layouts.app')

@section('title', 'Jadwal Cek Gula Darah')

@section('page-header')
    <h4 class="fw-bold mb-1">🩸 Jadwal Cek Gula Darah</h4>
    <small class="text-muted">Jadwal pemeriksaan gula darah Anda.</small>
@endsection

@section('content')
    @php
        $renderItem = function ($item) {
            return $item;
        };
    @endphp

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-semibold">Mendatang</div>
        <div class="list-group list-group-flush">
            @forelse ($mendatang as $item)
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-semibold">{{ $item['tanggal']->translatedFormat('l, d F Y') }}</div>
                        <small class="text-muted">
                            Pukul {{ $item['jam'] }} @if($item['tempat']) • {{ $item['tempat'] }} @endif
                        </small>
                    </div>
                    <span class="badge {{ ($item['puasa'] ?? '') === 'Wajib' ? 'bg-warning-subtle text-warning' : 'bg-secondary-subtle text-secondary' }}">
                        {{ ($item['puasa'] ?? '') === 'Wajib' ? 'Puasa wajib' : 'Tidak perlu puasa' }}
                    </span>
                </div>
            @empty
                <div class="list-group-item text-muted text-center py-4">Belum ada jadwal mendatang.</div>
            @endforelse
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-semibold">Selesai / Lewat</div>
        <div class="list-group list-group-flush">
            @forelse ($lewat as $item)
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-semibold">{{ $item['tanggal']->translatedFormat('l, d F Y') }}</div>
                        <small class="text-muted">
                            Pukul {{ $item['jam'] }} @if($item['tempat']) • {{ $item['tempat'] }} @endif
                        </small>
                    </div>
                </div>
            @empty
                <div class="list-group-item text-muted text-center py-4">Belum ada jadwal yang lewat.</div>
            @endforelse
        </div>
    </div>
@endsection
