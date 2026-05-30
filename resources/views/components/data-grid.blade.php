@props([
    'gridId' => 'datatable',
    'searchId' => 'searchInput',
    'createUrl' => null,
    'createLabel' => 'Tambah',
    'createIcon' => 'ri-add-line',
    'permission' => null,
    'title' => null,
    'classCreatebutton' => 'btn btn-primary btn-sm',
    'showSearch' => true,
])

{{-- Header dengan search + tombol tambah --}}
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        {{ $filterSlot ?? '' }}
    </div>
    
    <div class="d-flex gap-2 flex-wrap">
        @if($showSearch)
            <div class="position-relative">
                <input type="text" 
                    id="{{ $searchId }}" 
                    class="form-control form-control-sm ps-4" 
                    placeholder="🔍 Cari..."
                    style="min-width: 200px;">
            </div>
        @endif
        
        @if($createUrl)
            @if($permission)
                @can($permission)
                    <a href="{{ $createUrl }}" class="{{ $classCreatebutton }}">
                        <i class="ri {{ $createIcon }} me-1"></i>
                        {{ $createLabel }} {{ $title }}
                    </a>
                @endcan
            @else
                <a href="{{ $createUrl }}" class="{{ $classCreatebutton }}">
                    <i class="ri {{ $createIcon }} me-1"></i>
                    {{ $createLabel }} {{ $title }}
                </a>
            @endif
        @endif
    </div>
</div>

{{-- Table --}}
<div class="table-responsive">
    <table id="{{ $gridId }}" class="table table-hover table-striped w-100">
        <thead>
            {{ $head ?? '' }}
        </thead>
        <tbody>
            {{-- DataTables.net akan populate via AJAX --}}
        </tbody>
    </table>
</div>