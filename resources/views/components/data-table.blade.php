@props(['title' => null])

<div class="table-card-custom">
    @if ($title || isset($controls))
        <div class="table-header-control">
            @if ($title)
                <div class="card-title">{{ $title }}</div>
            @endif
            @isset($controls)
                <div class="table-filter-group">{{ $controls }}</div>
            @endisset
        </div>
    @endif

    <div class="table-responsive">
        <table class="table-custom">
            {{ $slot }}
        </table>
    </div>

    @isset($footer)
        <div class="table-footer-control">{{ $footer }}</div>
    @endisset
</div>
