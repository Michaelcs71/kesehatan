@props([
    'label' => '',
    'icon' => null,
    'class' => 'mb-3',
])

<div class="{{ $class }}">
    <label class="form-label fw-bold text-muted small mb-1">
        @if($icon)<i class="ri {{ $icon }} me-1"></i>@endif
        {{ $label }}
    </label>
    {{ $slot }}
</div>