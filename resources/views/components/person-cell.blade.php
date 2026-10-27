@props(['name', 'sub' => null, 'avatar' => null])

<div class="table-user-cell">
    @if ($avatar)
        <img src="{{ e($avatar) }}" alt="" class="table-user-avatar">
    @endif
    <div>
        <div class="table-user-name">{{ $name }}</div>
        @if ($sub)
            <div class="table-user-sub">{{ $sub }}</div>
        @endif
    </div>
</div>
