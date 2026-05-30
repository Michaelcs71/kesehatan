@props(['status'])

@php
    $statusEnum = $status instanceof \App\Enums\StatusObat
        ? $status
        : \App\Enums\StatusObat::tryFrom($status);
@endphp

@if($statusEnum)
    <span class="badge bg-{{ $statusEnum->badgeColor() }}-subtle text-{{ $statusEnum->badgeColor() }} px-3 py-2">
        {{ $statusEnum->icon() }} {{ $statusEnum->label() }}
    </span>
@endif