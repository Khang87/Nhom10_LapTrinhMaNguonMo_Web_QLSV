@props(['grid', 'title' => null, 'attendanceLinks' => false])

<x-data-table :title="$title">
    <thead>
        <tr>
            @foreach (\App\Models\LectureSlot::WEEK_DAYS as $weekday => $label)
                <th scope="col">{{ $label }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        <tr>
            @foreach ($grid as $weekday => $slots)
                <td>
                    @forelse ($slots as $slot)
                        <div class="mb-3">
                            <div class="table-amount">{{ $slot->timeRange() }}</div>
                            <div class="table-user-sub">{{ $slot->offering?->subject?->label() ?? '—' }}</div>
                            <div class="fs-xs text-muted-green">
                                Nhóm {{ $slot->offering?->group_code ?? '—' }}
                                · {{ $slot->room ?: 'Chưa xếp phòng' }}
                                @if ($slot->offering?->teacher)
                                    · {{ $slot->offering->teacher->name }}
                                @endif
                            </div>
                            @if ($attendanceLinks)
                                <a class="btn btn-custom btn-custom-sm btn-custom-outline-primary d-inline-block mt-2"
                                    href="{{ route('teacher.course-offerings.attendance', $slot->course_offering_id) }}">
                                    Điểm danh
                                </a>
                            @endif
                        </div>
                    @empty
                        <span class="text-muted-green">—</span>
                    @endforelse
                </td>
            @endforeach
        </tr>
    </tbody>
</x-data-table>
