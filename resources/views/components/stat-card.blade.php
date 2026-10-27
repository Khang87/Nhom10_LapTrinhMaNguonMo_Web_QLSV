@props(['label', 'value', 'hint' => null])

<div class="card card-stat mb-0">
    <div class="stat-label">{{ $label }}</div>
    <div class="stat-value stat-value-amount">{{ $value }}</div>
    @if (isset($slot) && trim((string) $slot) !== '')
        <div class="fs-xs text-muted-green">{{ $slot }}</div>
    @endif
    @if ($hint)
        <div class="fs-xs text-muted-green">{{ $hint }}</div>
    @endif
</div>
