@props(['tone' => 'neutral'])

{{-- tone: success | pending | failed | info | neutral --}}
<span class="badge-table {{ $tone }}">{{ $slot }}</span>
