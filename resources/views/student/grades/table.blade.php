{{-- Bảng điểm: chỉ gồm thead/tbody để mỗi trang tự bọc trong <x-data-table>. --}}
<thead>
    <tr>
        <th scope="col">Mã môn</th>
        <th scope="col">Môn học</th>
        <th scope="col">Nhóm</th>
        <th scope="col">Học kỳ</th>
        @foreach ($components as $component)
            <th scope="col">{{ $component['label'] }} ({{ $component['weight'] }}%)</th>
        @endforeach
        <th scope="col">Tổng kết</th>
        <th scope="col">Chữ</th>
        <th scope="col">Hệ 4</th>
        @if ($showStatus ?? false)
            <th scope="col">Trạng thái lớp</th>
        @endif
    </tr>
</thead>
<tbody>
    @forelse ($rows as $grade)
        @php($visible = $grade->is_published && $grade->subjectScore() !== null)
        <tr>
            <td class="table-order-id">{{ $grade->subject?->code ?? '—' }}</td>
            <td class="table-product-name">{{ $grade->subject?->name ?? '—' }}</td>
            <td>{{ $grade->offering?->group_code ?? '—' }}</td>
            <td>{{ $grade->offering?->semester?->label ?? '—' }}</td>
            @foreach ($components as $component)
                <td class="table-amount">
                    {{ $visible && $grade->{$component['key']} !== null ? number_format((float) $grade->{$component['key']}, 2) : '—' }}
                </td>
            @endforeach
            <td class="table-amount">
                @if ($visible)
                    {{ number_format($grade->subjectScore(), 2) }}
                @else
                    <x-status-badge tone="pending">Chưa công bố</x-status-badge>
                @endif
            </td>
            <td>{{ $visible ? $grade->letter() : '—' }}</td>
            <td class="table-amount">{{ $visible ? number_format($grade->fourScore(), 2) : '—' }}</td>
            @if ($showStatus ?? false)
                <td>{{ $grade->enrollment?->statusLabel() ?? '—' }}</td>
            @endif
        </tr>
    @empty
        <tr>
            <td colspan="{{ 7 + count($components) + ($showStatus ?? false ? 1 : 0) }}" class="text-center py-5">
                <div class="empty-state-icon mb-2"><i class="bi bi-inbox" aria-hidden="true"></i></div>
                <div class="table-user-sub">
                    Chưa có điểm được cập nhật.
                    @isset($emptyNote)
                        {!! $emptyNote !!}
                    @endisset
                </div>
            </td>
        </tr>
    @endforelse
</tbody>
