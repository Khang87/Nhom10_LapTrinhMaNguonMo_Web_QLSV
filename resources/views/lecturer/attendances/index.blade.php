@extends('layouts.spark')

@section('title', 'Điểm danh Lớp học phần - Giảng viên')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1">Điểm danh Lớp học phần</h1>
            <p class="text-secondary mb-0">Chọn lớp học phần bạn đang phụ trách giảng dạy trong tuần để tiến hành điểm danh</p>
        </div>
        <a href="{{ route('lecturer.schedule.index') }}" class="btn btn-outline-primary d-inline-flex align-items-center gap-2">
            <i class="bi bi-calendar-week"></i>
            <span>Xem Thời khóa biểu tuần</span>
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="bi bi-check-circle-fill fs-5"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- LỊCH DẠY TRONG TUẦN HIỆN TẠI --}}
    @if ($weekSchedules->isNotEmpty())
        <div class="card border-0 shadow-sm rounded-3 mb-4 border-start border-4 border-primary">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                    <i class="bi bi-calendar-check-fill text-primary"></i>
                    Các buổi dạy trong tuần này ({{ $weekSchedules->count() }} buổi)
                </h5>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1">Tuần hiện tại</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small">
                            <tr>
                                <th class="ps-3">Ngày dạy</th>
                                <th>Môn học</th>
                                <th>Lớp học phần</th>
                                <th class="text-center">Tiết</th>
                                <th>Phòng học</th>
                                <th class="text-end pe-3">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($weekSchedules as $sch)
                                @php
                                    $isToday = \Carbon\Carbon::parse($sch->schedule_date)->isToday();
                                @endphp
                                <tr class="{{ $isToday ? 'table-primary table-opacity-10' : '' }}">
                                    <td class="ps-3">
                                        <div class="fw-bold {{ $isToday ? 'text-primary' : 'text-dark' }}">
                                            {{ \Carbon\Carbon::parse($sch->schedule_date)->translatedFormat('l, d/m/Y') }}
                                        </div>
                                        @if ($isToday)
                                            <span class="badge bg-primary">Hôm nay</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $sch->subject_name }}</div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border font-monospace">
                                            {{ $sch->section_code ?? $sch->class_group_name }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary-subtle text-secondary">
                                            Tiết {{ $sch->start_period }} - {{ $sch->end_period }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="small text-secondary">{{ $sch->room_info }}</span>
                                    </td>
                                    <td class="text-end pe-3">
                                        @if ($sch->section_id)
                                            <a href="{{ route('lecturer.attendances.take', ['section' => $sch->section_id, 'date' => $sch->schedule_date]) }}" 
                                               class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1 shadow-sm">
                                                <i class="bi bi-check2-square"></i> Điểm danh ngay
                                            </a>
                                        @else
                                            <span class="text-muted small">Chưa link HP</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    {{-- TẤT CẢ CÁC LỚP HỌC PHẦN ĐANG PHỤ TRÁCH --}}
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
            <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                <i class="bi bi-collection-fill text-primary"></i>
                Tất cả các lớp học phần đang giảng dạy ({{ $sections->count() }})
            </h5>
            <span class="text-secondary small">Bấm vào lớp học phần để mở trang điểm danh</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small">
                    <tr>
                        <th class="ps-3" style="width: 140px;">Mã Lớp HP</th>
                        <th>Tên Môn Học</th>
                        <th>Học kỳ</th>
                        <th class="text-center" style="width: 120px;">Lịch học</th>
                        <th>Phòng</th>
                        <th class="text-center" style="width: 100px;">Sĩ số</th>
                        <th class="text-end pe-3" style="width: 150px;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sections as $sec)
                        @php
                            $dayText = match ((int)$sec->day_of_week) {
                                2 => 'Thứ 2',
                                3 => 'Thứ 3',
                                4 => 'Thứ 4',
                                5 => 'Thứ 5',
                                6 => 'Thứ 6',
                                7 => 'Thứ 7',
                                8 => 'Chủ nhật',
                                default => 'Thứ ' . $sec->day_of_week,
                            };
                        @endphp
                        <tr>
                            <td class="ps-3 font-monospace fw-bold text-primary">
                                {{ $sec->section_code }}
                            </td>
                            <td>
                                <div class="fw-bold text-dark">{{ $sec->course?->course_name }}</div>
                                <div class="text-muted small">Mã môn: {{ $sec->course?->course_code }} ({{ $sec->course?->credits }} TC)</div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ $sec->semester_code }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-secondary-subtle text-secondary px-2 py-1">
                                    {{ $dayText }} (Tiết {{ $sec->start_period }}-{{ $sec->end_period }})
                                </span>
                            </td>
                            <td>
                                <span class="small text-secondary">{{ $sec->room_name }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                                    {{ $sec->enrollments_count }} SV
                                </span>
                            </td>
                            <td class="text-end pe-3">
                                <a href="{{ route('lecturer.attendances.take', $sec) }}" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1 shadow-sm">
                                    <i class="bi bi-person-check-fill"></i> Điểm danh
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                Giảng viên hiện chưa được phân công lớp học phần nào.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
