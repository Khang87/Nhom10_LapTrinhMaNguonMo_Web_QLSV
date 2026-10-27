@props(['title', 'subtitle' => null])

<div class="page-header">
    <div>
        <h1 class="page-title">{{ $title }}</h1>
        @if ($subtitle)
            <p class="page-subtitle">{{ $subtitle }}</p>
        @endif
    </div>
    @if (isset($actions) && trim((string) $actions) !== '')
        <div class="d-flex flex-wrap align-items-center gap-2">{{ $actions }}</div>
    @endif
</div>
