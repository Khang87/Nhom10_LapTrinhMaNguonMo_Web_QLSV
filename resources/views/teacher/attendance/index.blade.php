@extends('layouts.spark')

@section('title', 'Điểm danh — '.$offering->label())

@section('content')
    <x-page-header title="Điểm danh" subtitle="{{ $offering->label() }}">
        <x-slot name="actions">
            <a class="btn btn-custom btn-custom-light" href="{{ route('teacher.offerings.index') }}">
                <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Quay lại
            </a>
        </x-slot>
    </x-page-header>

    <div class="row g-3 mb-2">
        <div class="col-6 col-lg-3">
            <x-stat-card label="Sĩ số đang học" :value="$size" />
        </div>
        <div class="col-6 col-lg-3">
            <x-stat-card label="Buổi đã điểm danh" :value="$sessionCount" />
        </div>
        <div class="col-12 col-lg-6">
            <x-stat-card label="Tỷ lệ chuyên cần trung bình"
                :value="$classRate === null ? '—' : $classRate.'%'"
                :hint="$classRate === null ? 'Chưa có dữ liệu' : 'Ngưỡng cảnh báo '.\App\Support\AttendanceRate::warningThreshold().'%'" />
        </div>
    </div>

    <div class="card">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <a class="btn btn-custom btn-custom-sm btn-custom-outline-secondary"
                href="{{ route('teacher.course-offerings.attendance', ['offering' => $offering, 'week' => $weekStart->copy()->subWeek()->toDateString()]) }}">
                <i class="bi bi-chevron-left" aria-hidden="true"></i>Tuần trước
            </a>
            <span class="fw-semibold">Tuần {{ $weekStart->format('d/m') }} – {{ $weekEnd->format('d/m/Y') }}</span>
            <a class="btn btn-custom btn-custom-sm btn-custom-outline-secondary"
                href="{{ route('teacher.course-offerings.attendance', ['offering' => $offering, 'week' => $weekStart->copy()->addWeek()->toDateString()]) }}">
                Tuần sau<i class="bi bi-chevron-right ms-1" aria-hidden="true"></i>
            </a>
            <a class="btn btn-custom btn-custom-sm btn-custom-light ms-auto"
                href="{{ route('teacher.course-offerings.attendance', $offering) }}">Tuần này</a>
        </div>
    </div>

    <x-data-table>
        <thead>
            <tr>
                @foreach (\App\Models\LectureSlot::WEEK_DAYS as $label)
                    <th scope="col">{{ $label }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            <tr>
                @foreach ($days as $day)
                    <td>
                        @forelse ($day['slots'] as $slot)
                            <div class="mb-3">
                                <div class="table-amount">{{ $slot->timeRange() }}</div>
                                <div class="fs-xs text-muted-green">{{ $slot->room ?: '—' }}</div>
                                @if ($day['session'])
                                    <x-status-badge tone="success">
                                        Đã điểm danh {{ $day['session']->records->count() }}/{{ $size }}
                                    </x-status-badge>
                                @else
                                    <x-status-badge tone="neutral">Chưa điểm danh</x-status-badge>
                                @endif
                                <a class="btn btn-custom btn-custom-sm btn-custom-outline-primary d-inline-block mt-2"
                                    href="{{ route('teacher.course-offerings.attendance.session.create', [
                                        'offering' => $offering,
                                        'date' => $day['date']->toDateString(),
                                        'slot' => $slot->id,
                                    ]) }}">Điểm danh</a>
                            </div>
                        @empty
                            <span class="text-muted-green">—</span>
                        @endforelse
                    </td>
                @endforeach
            </tr>
        </tbody>
    </x-data-table>
@endsection
