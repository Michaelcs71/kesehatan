@props([
    'title' => null,
    'subtitle' => null,
    'icon' => null,
    'headerActions' => null,
    'class' => '',
    'bodyClass' => 'p-4',
])

<div {{ $attributes->merge(['class' => "card border-0 shadow-sm $class"]) }}>
    @if($title || $subtitle || $headerActions)
        <div class="card-header bg-white border-bottom-0 pt-3 pb-2 d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                @if($title)
                    <h5 class="mb-0 fw-bold">
                        @if($icon)<i class="ri {{ $icon }} me-2 text-primary"></i>@endif
                        {{ $title }}
                    </h5>
                @endif
                @if($subtitle)
                    <small class="text-muted">{{ $subtitle }}</small>
                @endif
            </div>
            @if($headerActions)
                <div class="d-flex gap-2">
                    {{ $headerActions }}
                </div>
            @endif
        </div>
    @endif
    
    <div class="card-body {{ $bodyClass }}">
        {{ $slot }}
    </div>
</div>