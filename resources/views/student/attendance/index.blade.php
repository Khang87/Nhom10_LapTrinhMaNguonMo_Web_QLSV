@extends('layouts.spark')

@section('title', 'Theo dõi điểm danh học phần - HUIT')

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h4 fw-bold text-dark mb-1">Theo dõi điểm danh học phần</h1>
            <p class="text-secondary small mb-0">
                Sinh viên: <strong class="text-dark">{{ $student->full_name ?? auth()->user()->name }}</strong> (MSSV: {{ $student->student_code ?? auth()->user()->username }})
                • Lớp: <strong class="text-dark">{{ $student->class_code ?? '14DHTH04' }}</strong>
                • Chuyên ngành: <strong class="text-dark">{{ $student->specialization?->name ?? ($student->specialization_name ?: 'Công nghệ phần mềm') }}</strong>
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('student.schedule.index') }}" class="btn btn-outline-dark btn-sm d-inline-flex align-items-center gap-1.5">
                <i class="bi bi-calendar-week"></i>
                <span>Thời khóa biểu</span>
            </a>
            <a href="{{ route('student.grades.index') }}" class="btn btn-outline-dark btn-sm d-inline-flex align-items-center gap-1.5">
                <i class="bi bi-award"></i>
                <span>Xem bảng điểm</span>
            </a>
        </div>
    </div>

    <!-- BỘ LỌC HỌC KỲ (THIẾT KẾ PHẲNG SPARK UI) -->
    <div class="card border shadow-sm rounded-3 bg-white mb-4">
        <div class="card-body p-3 p-md-4">
            <form method="GET" action="{{ route('student.attendance.index') }}" class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex flex-wrap align-items-center gap-3 flex-grow-1" style="max-width: 600px;">
                    <label for="semesterSelect" class="form-label mb-0 small text-dark fw-bold text-nowrap d-flex align-items-center gap-1.5">
                        <span class="badge bg-dark text-white rounded-circle px-2 py-1" style="font-size: 0.75rem;">1</span>
                        <span>Chọn Học kỳ theo dõi:</span>
                    </label>
                    <select id="semesterSelect" name="semester" class="form-select form-select-sm py-2" onchange="this.form.submit()">
                        @foreach ($semesters as $sem)
                            <option value="{{ $sem->semester_code }}" {{ $selectedSemesterCode === $sem->semester_code ? 'selected' : '' }}>
                                {{ $sem->semester_name }} {{ $sem->is_registration_open ? '(Học kỳ hiện tại)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="text-secondary small">
                    <i class="bi bi-shield-check text-success me-1"></i>
                    Quy chế: Sinh viên vắng <strong>quá 20%</strong> số tiết sẽ bị <strong>cấm thi kết thúc học phần</strong>.
                </div>
            </form>
        </div>
    </div>

    <!-- 4 THẺ KPI TỔNG QUAN CHUYÊN CẦN TRONG KỲ -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="p-3 rounded-3 bg-light border h-100">
                <span class="text-secondary small fw-medium">Số môn đã đăng ký</span>
                <div class="fs-4 fw-bold text-dark mt-1">
                    {{ $coursesAttendance->count() }} <span class="fs-6 fw-normal text-secondary">môn</span>
                </div>
                <span class="text-secondary small mt-0.5" style="font-size: 0.75rem;">Trong {{ $selectedSemester->semester_name ?? '' }}</span>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="p-3 rounded-3 bg-light border h-100">
                <span class="text-secondary small fw-medium">Tổng số buổi đã học</span>
                <div class="fs-4 fw-bold text-dark mt-1">
                    {{ $totalAllSessions }} <span class="fs-6 fw-normal text-secondary">buổi</span>
                </div>
                <span class="text-secondary small mt-0.5" style="font-size: 0.75rem;">Đã ghi nhận điểm danh</span>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="p-3 rounded-3 bg-light border h-100">
                <span class="text-secondary small fw-medium">Số buổi có mặt</span>
                <div class="fs-4 fw-bold text-success mt-1">
                    {{ $totalAllPresents }} <span class="fs-6 fw-normal text-secondary">buổi</span>
                </div>
                <span class="text-success small mt-0.5" style="font-size: 0.75rem;">Đúng giờ & đầy đủ</span>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="p-3 rounded-3 bg-light border h-100">
                <span class="text-secondary small fw-medium">Số buổi vắng mặt</span>
                <div class="fs-4 fw-bold {{ $totalAllAbsents > 0 ? 'text-danger' : 'text-dark' }} mt-1">
                    {{ $totalAllAbsents }} <span class="fs-6 fw-normal text-secondary">buổi</span>
                </div>
                <span class="text-secondary small mt-0.5" style="font-size: 0.75rem;">Chuyên cần: {{ $overallRate }}%</span>
            </div>
        </div>
    </div>

    <!-- KHỐI 1: DANH SÁCH CÁC MÔN HỌC ĐÃ ĐĂNG KÝ TRONG KỲ -->
    <div class="card border shadow-sm rounded-3 bg-white mb-4 overflow-hidden">
        <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <strong class="text-dark fs-6">Danh sách học phần đăng ký — {{ $selectedSemester->semester_name ?? $selectedSemesterCode }}</strong>
                <div class="text-secondary small mt-0.5">
                    Bấm vào từng môn học bên dưới để xem chi tiết ngày tháng vắng và có mặt
                </div>
            </div>
            <span class="badge bg-light text-dark border font-monospace">
                {{ $coursesAttendance->count() }} môn học
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-secondary small">
                        <tr>
                            <th class="ps-3 text-center" style="width: 50px;">#</th>
                            <th style="width: 120px;">Mã LHP</th>
                            <th>Tên môn học & Giảng viên</th>
                            <th class="text-center" style="width: 70px;">Số TC</th>
                            <th class="text-center" style="width: 110px;">Tổng số buổi</th>
                            <th class="text-center" style="width: 110px;">Có mặt</th>
                            <th class="text-center" style="width: 110px;">Số buổi vắng</th>
                            <th class="text-center" style="width: 130px;">Tỉ lệ chuyên cần</th>
                            <th class="text-center pe-3" style="width: 130px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($coursesAttendance as $index => $item)
                            @php
                                $isSelected = ($activeCourseAttendance && $activeCourseAttendance->section?->id === $item->section?->id);
                            @endphp
                            <tr class="{{ $isSelected ? 'table-active border-start border-4 border-dark' : '' }}" style="cursor: pointer;"
                                onclick="window.location.href='{{ route('student.attendance.index', ['semester' => $selectedSemesterCode, 'section_id' => $item->section?->id]) }}'">
                                <td class="ps-3 text-center text-muted small">{{ $index + 1 }}</td>
                                <td>
                                    <span class="badge bg-light text-dark border font-monospace">
                                        {{ $item->section?->section_code ?? 'LHP-' . $item->course?->course_code }}
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $item->course?->course_name }}</div>
                                    <div class="text-secondary small">
                                        <i class="bi bi-person-badge me-0.5"></i>GV: <strong class="text-dark">{{ $item->lecturer?->full_name ?? 'Chưa phân công' }}</strong>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border">{{ $item->course?->credits ?? 3 }}</span>
                                </td>
                                <td class="text-center fw-bold text-dark">
                                    {{ $item->total_sessions }}
                                </td>
                                <td class="text-center text-success fw-bold">
                                    {{ $item->present_count }}
                                </td>
                                <td class="text-center">
                                    @if ($item->absent_count > 0)
                                        <span class="badge bg-light text-danger border border-danger-subtle px-2 py-1">
                                            {{ $item->absent_count }} buổi vắng
                                        </span>
                                    @else
                                        <span class="badge bg-light text-success border border-success-subtle px-2 py-1">
                                            0 vắng
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="fw-bold {{ $item->attendance_rate < 80 ? 'text-danger' : 'text-dark' }}">
                                        {{ $item->attendance_rate }}%
                                    </span>
                                </td>
                                <td class="text-center pe-3">
                                    <a href="{{ route('student.attendance.index', ['semester' => $selectedSemesterCode, 'section_id' => $item->section?->id]) }}" 
                                       class="btn btn-sm {{ $isSelected ? 'btn-dark' : 'btn-outline-dark' }} py-1 px-2.5 d-inline-flex align-items-center gap-1"
                                       onclick="event.stopPropagation();">
                                        <i class="bi {{ $isSelected ? 'bi-eye-fill' : 'bi-eye' }}"></i>
                                        <span>{{ $isSelected ? 'Đang xem' : 'Xem vắng' }}</span>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4 text-secondary small">
                                    <i class="bi bi-inbox fs-3 d-block mb-2 text-secondary opacity-50"></i>
                                    Bạn chưa đăng ký môn học nào trong học kỳ này.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- KHỐI 2: CHI TIẾT CÁC BUỔI ĐIỂM DANH CỦA MÔN ĐƯỢC CHỌN -->
    @if ($activeCourseAttendance)
        <div class="card border shadow-sm rounded-3 bg-white overflow-hidden mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="badge bg-dark text-white font-monospace">
                            {{ $activeCourseAttendance->section?->section_code }}
                        </span>
                        <strong class="text-dark fs-6">
                            Chi tiết điểm danh: {{ $activeCourseAttendance->course?->course_name }}
                        </strong>
                        @if ($activeCourseAttendance->is_danger)
                            <span class="badge bg-light text-danger border border-danger-subtle">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i>Cảnh báo vắng quá 20%
                            </span>
                        @else
                            <span class="badge bg-light text-success border border-success-subtle">
                                <i class="bi bi-check-circle me-1"></i>Chuyên cần tốt
                            </span>
                        @endif
                    </div>
                    <div class="text-secondary small mt-1">
                        Giảng viên phụ trách: <strong>{{ $activeCourseAttendance->lecturer?->full_name ?? 'Chưa phân công' }}</strong>
                        • Tổng số: <strong>{{ $activeCourseAttendance->total_sessions }} buổi</strong>
                        • Có mặt: <strong class="text-success">{{ $activeCourseAttendance->present_count }}</strong>
                        • Vắng: <strong class="text-danger">{{ $activeCourseAttendance->absent_count }}</strong>
                        • Tỉ lệ chuyên cần: <strong>{{ $activeCourseAttendance->attendance_rate }}%</strong>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-light text-success border border-success-subtle px-2.5 py-1.5">
                        ✓ Có mặt: {{ $activeCourseAttendance->present_count }} buổi
                    </span>
                    <span class="badge bg-light text-danger border border-danger-subtle px-2.5 py-1.5">
                        ✗ Vắng: {{ $activeCourseAttendance->absent_count }} buổi
                    </span>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-secondary small">
                            <tr>
                                <th class="ps-3 text-center" style="width: 60px;">Buổi</th>
                                <th style="width: 180px;">Ngày tháng điểm danh</th>
                                <th style="width: 180px;">Ca học & Tiết học</th>
                                <th style="width: 180px;">Phòng học</th>
                                <th class="text-center" style="width: 160px;">Trạng thái điểm danh</th>
                                <th class="pe-3">Ghi chú của Giảng viên</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($activeCourseAttendance->records as $rIdx => $record)
                                @php
                                    $dayOfWeek = match ((int)$activeCourseAttendance->section?->day_of_week) {
                                        2 => 'Thứ 2',
                                        3 => 'Thứ 3',
                                        4 => 'Thứ 4',
                                        5 => 'Thứ 5',
                                        6 => 'Thứ 6',
                                        7 => 'Thứ 7',
                                        default => 'Chủ nhật',
                                    };
                                    $isAbsent = ($record->status === 'absent');
                                    $isLate = ($record->status === 'late');
                                @endphp
                                <tr class="{{ $isAbsent ? 'table-danger-subtle' : '' }}">
                                    <td class="ps-3 text-center fw-bold text-muted small">
                                        {{ $rIdx + 1 }}
                                    </td>
                                    <td>
                                        <div class="fw-bold {{ $isAbsent ? 'text-danger' : 'text-dark' }}">
                                            <i class="bi bi-calendar-check me-1 {{ $isAbsent ? 'text-danger' : 'text-secondary' }}"></i>
                                            {{ $record->attendance_date ? $record->attendance_date->format('d/m/Y') : '' }}
                                        </div>
                                        <div class="text-secondary small">({{ $dayOfWeek }})</div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            Tiết {{ $activeCourseAttendance->section?->start_period ?? 1 }} - {{ $activeCourseAttendance->section?->end_period ?? 3 }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="small text-secondary">
                                            <i class="bi bi-geo-alt me-0.5"></i>
                                            {{ $activeCourseAttendance->section?->room_name ?? 'Phòng A110' }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @if ($isAbsent)
                                            <span class="badge bg-danger text-white px-2.5 py-1.5 shadow-sm">
                                                <i class="bi bi-x-circle-fill me-1"></i>VẮNG MẶT
                                            </span>
                                        @elseif ($isLate)
                                            <span class="badge bg-warning text-dark px-2.5 py-1.5">
                                                <i class="bi bi-exclamation-triangle-fill me-1"></i>ĐI TRỄ
                                            </span>
                                        @else
                                            <span class="badge bg-light text-success border border-success-subtle px-2.5 py-1.5">
                                                <i class="bi bi-check-circle-fill me-1"></i>CÓ MẶT
                                            </span>
                                        @endif
                                    </td>
                                    <td class="pe-3 small text-secondary">
                                        @if ($record->note)
                                            <span class="{{ $isAbsent ? 'text-danger fw-semibold' : '' }}">
                                                {{ $record->note }}
                                            </span>
                                        @else
                                            <span class="text-muted">Có mặt đầy đủ</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-secondary small">
                                        Chưa có bản ghi điểm danh nào cho học phần này.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
