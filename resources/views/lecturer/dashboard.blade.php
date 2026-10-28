@extends('layouts.spark')

@section('title', 'Cổng Thông Tin Giảng Viên - HUIT')

@section('content')
<div class="container-fluid px-0">
    <!-- Header Chào mừng (Spark Minimalist Hero) -->
    <div class="card border shadow-sm rounded-3 bg-white mb-4">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-light text-dark border rounded-circle d-flex align-items-center justify-content-center fw-bold fs-3 flex-shrink-0" 
                         style="width: 62px; height: 62px;">
                        {{ mb_substr($lecturer->full_name ?? $user->name, 0, 1) }}
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                            <h1 class="h4 fw-bold text-dark mb-0">{{ $lecturer->full_name ?? $user->name }}</h1>
                            <span class="badge bg-light text-dark border font-monospace">{{ $lecturer->lecturer_code ?? $user->username }}</span>
                            <span class="badge bg-light text-primary border border-primary-subtle">
                                <i class="bi bi-person-workspace me-1"></i>Giảng viên chính thức
                            </span>
                        </div>
                        <div class="small text-secondary d-flex flex-wrap align-items-center gap-2">
                            <span><i class="bi bi-mortarboard me-1"></i>Học vị: <strong class="text-dark">{{ $lecturer->degree ?? 'Thạc sĩ' }}</strong></span>
                            <span>•</span>
                            <span><i class="bi bi-building me-1"></i>Khoa: <strong class="text-dark">{{ $lecturer->faculty?->faculty_name ?? 'Công nghệ thông tin' }}</strong></span>
                            <span>•</span>
                            <span><i class="bi bi-calendar3 me-1"></i>{{ $currentSemester->semester_name ?? $currentSemesterCode }}</span>
                        </div>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('lecturer.schedule.index') }}" class="btn btn-outline-dark btn-sm d-inline-flex align-items-center gap-1.5">
                        <i class="bi bi-calendar-week"></i>
                        <span>Lịch dạy tuần</span>
                    </a>
                    <a href="{{ route('lecturer.attendances.index') }}" class="btn btn-dark btn-sm d-inline-flex align-items-center gap-1.5">
                        <i class="bi bi-calendar-check"></i>
                        <span>Điểm danh ngay</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- 5 THẺ KPI TỔNG QUAN GIẢNG DẠY & CỐ VẤN -->
    <div class="row g-3 mb-4">
        <!-- KPI 1: Số lớp học phần -->
        <div class="col-6 col-lg">
            <div class="p-3 rounded-3 bg-light border h-100 d-flex flex-column justify-content-center">
                <span class="text-secondary small fw-medium">Lớp Học Phần kỳ này</span>
                <div class="fs-4 fw-bold text-dark mt-0.5">
                    {{ $sections->count() }} <span class="fs-6 fw-normal text-secondary">lớp</span>
                </div>
                <span class="text-secondary small mt-1" style="font-size: 0.75rem;">
                    {{ $currentSemester->semester_name ?? $currentSemesterCode }}
                </span>
            </div>
        </div>

        <!-- KPI 2: Tổng sinh viên đang dạy -->
        <div class="col-6 col-lg">
            <div class="p-3 rounded-3 bg-light border h-100 d-flex flex-column justify-content-center">
                <span class="text-secondary small fw-medium">Tổng Sinh viên theo học</span>
                <div class="fs-4 fw-bold text-dark mt-0.5">
                    {{ $totalStudentsCount }} <span class="fs-6 fw-normal text-secondary">sinh viên</span>
                </div>
                <span class="text-secondary small mt-1" style="font-size: 0.75rem;">
                    Trên các lớp học phần
                </span>
            </div>
        </div>

        <!-- KPI 3: Chuyên cần bình quân -->
        <div class="col-6 col-lg">
            <div class="p-3 rounded-3 bg-light border h-100 d-flex flex-column justify-content-center">
                <span class="text-secondary small fw-medium">Chuyên cần bình quân</span>
                <div class="fs-4 fw-bold text-success mt-0.5">
                    {{ $avgAttendanceRate }}%
                </div>
                <span class="text-secondary small mt-1" style="font-size: 0.75rem;">
                    Tổng hợp từ các buổi điểm danh
                </span>
            </div>
        </div>

        <!-- KPI 4: Lớp Cố vấn học tập (CVHT) -->
        <div class="col-6 col-lg">
            <div class="p-3 rounded-3 bg-light border h-100 d-flex flex-column justify-content-center">
                <span class="text-secondary small fw-medium">Lớp Cố vấn (CVHT)</span>
                <div class="fs-4 fw-bold text-dark mt-0.5">
                    {{ $homeroomClasses->count() }} <span class="fs-6 fw-normal text-secondary">lớp</span>
                </div>
                <span class="text-secondary small mt-1" style="font-size: 0.75rem;">
                    Quản lý {{ $totalHomeroomStudents }} sinh viên
                </span>
            </div>
        </div>

        <!-- KPI 5: Tiến độ nộp bảng điểm -->
        <div class="col-12 col-lg">
            <div class="p-3 rounded-3 bg-light border h-100 d-flex flex-column justify-content-center">
                <span class="text-secondary small fw-medium">Tiến độ nộp điểm</span>
                <div class="fs-4 fw-bold text-dark mt-0.5">
                    {{ $sectionsWithGrades }} / {{ $sections->count() }} <span class="fs-6 fw-normal text-secondary">lớp</span>
                </div>
                <div class="mt-1">
                    @if ($sections->count() > 0 && $sectionsWithGrades === $sections->count())
                        <span class="badge bg-light text-success border border-success-subtle small">
                            ✓ Hoàn thành 100%
                        </span>
                    @else
                        <span class="badge bg-light text-warning border border-warning-subtle small">
                            Đang xử lý nhập điểm
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- 6 LỐI TẮT NHANH (QUICK ACTIONS) -->
    <div class="row row-cols-2 row-cols-sm-3 row-cols-lg-6 g-2 mb-4">
        <div class="col">
            <a href="{{ route('lecturer.schedule.index') }}" class="card border rounded-3 text-decoration-none bg-white p-3 text-center h-100 transition-all hover-shadow">
                <i class="bi bi-calendar-week fs-3 text-dark mb-1"></i>
                <div class="fw-semibold text-dark small">Lịch giảng dạy</div>
                <span class="text-secondary" style="font-size: 0.7rem;">Lịch dạy tuần</span>
            </a>
        </div>
        <div class="col">
            <a href="{{ route('lecturer.attendances.index') }}" class="card border rounded-3 text-decoration-none bg-white p-3 text-center h-100 transition-all hover-shadow">
                <i class="bi bi-calendar-check fs-3 text-dark mb-1"></i>
                <div class="fw-semibold text-dark small">Điểm danh lớp</div>
                <span class="text-secondary" style="font-size: 0.7rem;">Theo dõi chuyên cần</span>
            </a>
        </div>
        <div class="col">
            <a href="{{ route('lecturer.grades.index') }}" class="card border rounded-3 text-decoration-none bg-white p-3 text-center h-100 transition-all hover-shadow">
                <i class="bi bi-pencil-square fs-3 text-dark mb-1"></i>
                <div class="fw-semibold text-dark small">Nhập & Nộp điểm</div>
                <span class="text-secondary" style="font-size: 0.7rem;">Khóa & Nộp PĐT</span>
            </a>
        </div>
        <div class="col">
            <a href="{{ route('lecturer.advisor.index') }}" class="card border rounded-3 text-decoration-none bg-white p-3 text-center h-100 transition-all hover-shadow">
                <i class="bi bi-people-fill fs-3 text-dark mb-1"></i>
                <div class="fw-semibold text-dark small">Cố vấn học tập</div>
                <span class="text-secondary" style="font-size: 0.7rem;">Quản lý lớp CVHT</span>
            </a>
        </div>
        <div class="col">
            <a href="{{ route('lecturer.appeals.index') }}" class="card border rounded-3 text-decoration-none bg-white p-3 text-center h-100 transition-all hover-shadow">
                <div class="position-relative d-inline-block">
                    <i class="bi bi-chat-left-dots fs-3 text-dark mb-1"></i>
                    @if ($pendingAppealsCount > 0)
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.65rem;">
                            {{ $pendingAppealsCount }}
                        </span>
                    @endif
                </div>
                <div class="fw-semibold text-dark small">Phúc khảo điểm</div>
                <span class="text-secondary" style="font-size: 0.7rem;">Thắc mắc của SV</span>
            </a>
        </div>
        <div class="col">
            <a href="{{ route('lecturer.requests.index') }}" class="card border rounded-3 text-decoration-none bg-white p-3 text-center h-100 transition-all hover-shadow">
                <div class="position-relative d-inline-block">
                    <i class="bi bi-calendar2-range fs-3 text-dark mb-1"></i>
                    @if ($pendingScheduleRequestsCount > 0)
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark" style="font-size: 0.65rem;">
                            {{ $pendingScheduleRequestsCount }}
                        </span>
                    @endif
                </div>
                <div class="fw-semibold text-dark small">Báo nghỉ / Dạy bù</div>
                <span class="text-secondary" style="font-size: 0.7rem;">Đổi lịch & Học online</span>
            </a>
        </div>
    </div>

    <!-- BỐ CỤC 2 CỘT NỘI DUNG CHÍNH -->
    <div class="row g-4">
        <!-- CỘT TRÁI (7 COLS): LỊCH DẠY HÔM NAY & DANH SÁCH LỚP HỌC PHẦN KỲ NÀY -->
        <div class="col-lg-7">
            <!-- Khối 1: Lịch giảng dạy hôm nay -->
            <div class="card border shadow-sm rounded-3 bg-white mb-4 overflow-hidden">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-calendar-event text-dark fs-5"></i>
                        <strong class="text-dark fs-6">Lịch giảng dạy hôm nay ({{ now()->format('d/m/Y') }})</strong>
                    </div>
                    <a href="{{ route('lecturer.schedule.index') }}" class="btn btn-sm btn-light border text-dark">
                        Xem tuần này ({{ $thisWeekSchedules->count() }} buổi) <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="card-body p-3">
                    @if ($todaySchedules->isNotEmpty())
                        <div class="d-flex flex-column gap-2">
                            @foreach ($todaySchedules as $sched)
                                <div class="p-3 border rounded-3 bg-light d-flex flex-wrap align-items-center justify-content-between gap-2">
                                    <div>
                                        <div class="fw-semibold text-dark">{{ $sched->subject_name }}</div>
                                        <div class="text-secondary small mt-0.5">
                                            <span><i class="bi bi-clock me-1"></i>Tiết {{ $sched->start_period }} - {{ $sched->end_period }} ({{ $sched->shift === 'MORNING' ? 'Sáng' : ($sched->shift === 'AFTERNOON' ? 'Chiều' : 'Tối') }})</span>
                                            <span class="mx-1">•</span>
                                            <span><i class="bi bi-geo-alt me-1"></i>{{ $sched->room_info }}</span>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        @if ($sched->section)
                                            <a href="{{ route('lecturer.attendances.take', ['section' => $sched->section_id, 'date' => now()->toDateString()]) }}" 
                                               class="btn btn-sm btn-dark py-1 px-2.5 d-inline-flex align-items-center gap-1">
                                                <i class="bi bi-check2-square"></i>
                                                <span>Điểm danh</span>
                                            </a>
                                        @endif
                                        <span class="badge bg-light text-dark border small">{{ $sched->section_code }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4 text-secondary small">
                            <i class="bi bi-cup-hot fs-2 d-block mb-2 text-secondary opacity-50"></i>
                            Hôm nay Thầy/Cô không có ca giảng dạy. Hãy xem lịch giảng dạy các ngày tiếp theo trong tuần!
                        </div>
                    @endif
                </div>
            </div>

            <!-- Khối 2: Danh sách các lớp học phần đang giảng dạy -->
            <div class="card border shadow-sm rounded-3 bg-white overflow-hidden mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <div>
                        <strong class="text-dark fs-6">Lớp học phần giảng dạy kỳ này</strong>
                        <div class="text-secondary small mt-0.5">{{ $currentSemester->semester_name ?? $currentSemesterCode }}</div>
                    </div>
                    <span class="badge bg-light text-dark border font-monospace">
                        {{ $sections->count() }} lớp
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-secondary small">
                                <tr>
                                    <th class="ps-3" style="width: 50px;">#</th>
                                    <th>Mã LHP & Tên Môn</th>
                                    <th class="text-center" style="width: 100px;">Sĩ số</th>
                                    <th>Lịch học tuần</th>
                                    <th class="text-center" style="width: 130px;">Trạng thái điểm</th>
                                    <th class="text-center pe-3" style="width: 140px;">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($sections as $idx => $sec)
                                    @php
                                        $dayStr = match ((int)$sec->day_of_week) {
                                            2 => 'Thứ 2',
                                            3 => 'Thứ 3',
                                            4 => 'Thứ 4',
                                            5 => 'Thứ 5',
                                            6 => 'Thứ 6',
                                            7 => 'Thứ 7',
                                            default => 'Chủ nhật',
                                        };
                                        $isApproved = ($sec->grade_status === 'APPROVED');
                                        $isSubmitted = ($sec->grade_status === 'SUBMITTED');
                                    @endphp
                                    <tr>
                                        <td class="ps-3 text-muted small">{{ $idx + 1 }}</td>
                                        <td>
                                            <div class="fw-semibold text-dark">{{ $sec->course?->course_name }}</div>
                                            <div class="text-secondary small font-monospace">
                                                {{ $sec->section_code }} • {{ $sec->course?->credits }} TC
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-light text-dark border font-monospace">
                                                {{ $sec->enrollments_count }} SV
                                            </span>
                                        </td>
                                        <td class="small">
                                            <div class="fw-medium text-dark">{{ $dayStr }}</div>
                                            <div class="text-secondary">Tiết {{ $sec->start_period }}-{{ $sec->end_period }} ({{ $sec->room_name }})</div>
                                        </td>
                                        <td class="text-center">
                                            @if ($isApproved)
                                                <span class="badge bg-light text-success border border-success-subtle px-2 py-1">
                                                    ✓ Đã phê duyệt
                                                </span>
                                            @elseif ($isSubmitted)
                                                <span class="badge bg-light text-primary border border-primary-subtle px-2 py-1">
                                                    <i class="bi bi-lock-fill me-0.5"></i>Chờ PĐT duyệt
                                                </span>
                                            @else
                                                <span class="badge bg-light text-secondary border px-2 py-1">
                                                    Đang nhập nháp
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-center pe-3">
                                            <div class="d-inline-flex align-items-center gap-1">
                                                <a href="{{ route('lecturer.attendances.take', ['section' => $sec->id]) }}" 
                                                   class="btn btn-sm btn-outline-dark py-1 px-2" title="Điểm danh">
                                                    <i class="bi bi-person-check"></i>
                                                </a>
                                                <a href="{{ route('lecturer.grades.edit', $sec) }}" 
                                                   class="btn btn-sm {{ $sec->isGradeLocked() ? 'btn-outline-secondary' : 'btn-dark' }} py-1 px-2" 
                                                   title="{{ $sec->isGradeLocked() ? 'Xem bảng điểm đã khóa' : 'Nhập bảng điểm' }}">
                                                    <i class="bi bi-pencil-square"></i>
                                                    <span class="small ms-1">{{ $sec->isGradeLocked() ? 'Xem' : 'Nhập' }}</span>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-secondary small">
                                            Chưa có lớp học phần nào được phân công cho Thầy/Cô trong học kỳ này.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- CỘT PHẢI (5 COLS): LỚP CỐ VẤN HỌC TẬP (CVHT) & THÔNG BÁO TỔNG HỢP -->
        <div class="col-lg-5">
            <!-- Khối 1: Lớp Cố vấn học tập (CVHT) -->
            <div class="card border shadow-sm rounded-3 bg-white mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <div>
                        <strong class="text-dark fs-6">Lớp Cố vấn học tập (CVHT)</strong>
                        <div class="text-secondary small mt-0.5">Lớp sinh hoạt Thầy/Cô phụ trách chủ nhiệm</div>
                    </div>
                    <a href="{{ route('lecturer.advisor.index') }}" class="btn btn-sm btn-light border text-dark">
                        Xem chi tiết <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="card-body p-3">
                    @forelse ($homeroomClasses as $hClass)
                        <div class="p-3 border rounded-3 bg-light mb-2">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <span class="badge bg-dark text-white font-monospace">{{ $hClass->class_code }}</span>
                                <span class="badge bg-light text-dark border">{{ $hClass->students_count }} sinh viên</span>
                            </div>
                            <div class="fw-semibold text-dark">{{ $hClass->class_name ?? 'Lớp ' . $hClass->class_code }}</div>
                            <div class="small text-secondary mt-1">
                                Chuyên ngành: <strong>{{ $hClass->specialization?->name ?? 'Công nghệ thông tin' }}</strong>
                            </div>
                            <div class="mt-2 pt-2 border-top d-flex align-items-center justify-content-between">
                                <span class="small text-secondary">Khóa: {{ $hClass->cohort_year ?? '2023' }}</span>
                                <a href="{{ route('lecturer.advisor.class', $hClass) }}" class="btn btn-sm btn-dark py-1 px-2.5">
                                    <i class="bi bi-people me-1"></i>Quản lý lớp
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4 text-secondary small">
                            <i class="bi bi-info-circle fs-3 d-block mb-2 text-secondary opacity-50"></i>
                            Thầy/Cô hiện chưa được phân công làm Cố vấn học tập cho lớp sinh hoạt nào.
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Khối 2: Widget Phúc khảo điểm & Báo nghỉ/bù -->
            <div class="card border shadow-sm rounded-3 bg-white mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <strong class="text-dark fs-6">Việc cần xử lý</strong>
                    <span class="badge bg-light text-dark border">Thông báo</span>
                </div>
                <div class="card-body p-3">
                    <div class="d-flex flex-column gap-3">
                        <!-- Thắc mắc điểm -->
                        <div class="p-3 border rounded-3 bg-light d-flex align-items-center justify-content-between">
                            <div>
                                <div class="fw-semibold text-dark d-flex align-items-center gap-1.5">
                                    <i class="bi bi-chat-left-dots text-primary"></i>
                                    <span>Phúc khảo / Thắc mắc điểm</span>
                                </div>
                                <div class="small text-secondary mt-0.5">
                                    {{ $pendingAppealsCount > 0 ? "Có {$pendingAppealsCount} thắc mắc đang chờ phản hồi" : 'Hiện không có thắc mắc điểm mới' }}
                                </div>
                            </div>
                            <a href="{{ route('lecturer.appeals.index') }}" class="btn btn-sm {{ $pendingAppealsCount > 0 ? 'btn-danger' : 'btn-light border' }}">
                                {{ $pendingAppealsCount > 0 ? 'Xử lý ngay' : 'Xem' }}
                            </a>
                        </div>

                        <!-- Đổi lịch học -->
                        <div class="p-3 border rounded-3 bg-light d-flex align-items-center justify-content-between">
                            <div>
                                <div class="fw-semibold text-dark d-flex align-items-center gap-1.5">
                                    <i class="bi bi-calendar2-range text-dark"></i>
                                    <span>Yêu cầu Đổi lịch & Học Online</span>
                                </div>
                                <div class="small text-secondary mt-0.5">
                                    {{ $pendingScheduleRequestsCount > 0 ? "Có {$pendingScheduleRequestsCount} yêu cầu chờ Admin duyệt" : 'Gửi yêu cầu báo nghỉ hoặc học bù' }}
                                </div>
                            </div>
                            <a href="{{ route('lecturer.requests.create') }}" class="btn btn-sm btn-dark">
                                Gửi yêu cầu
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
