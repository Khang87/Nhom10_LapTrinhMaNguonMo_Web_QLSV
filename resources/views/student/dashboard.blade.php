@extends('layouts.spark')

@section('title', 'Cổng Thông Tin Sinh Viên - HUIT')

@section('content')
<div class="container-fluid px-0">
    <!-- Header Chào mừng (Spark Minimalist Hero) -->
    <div class="card border shadow-sm rounded-3 bg-white mb-4">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-light text-dark border rounded-circle d-flex align-items-center justify-content-center fw-bold fs-3 flex-shrink-0" 
                         style="width: 62px; height: 62px;">
                        {{ mb_substr($student->full_name ?? $user->name, 0, 1) }}
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                            <h1 class="h4 fw-bold text-dark mb-0">{{ $student?->full_name ?? $user->name }}</h1>
                            <span class="badge bg-light text-dark border font-monospace">{{ $student?->student_code ?? $user->username }}</span>
                            <span class="badge bg-light text-success border border-success-subtle">
                                <i class="bi bi-check-circle me-1"></i>Đang học chính khóa
                            </span>
                        </div>
                        <div class="small text-secondary d-flex flex-wrap align-items-center gap-2">
                            <span><i class="bi bi-people me-1"></i>Lớp: <strong class="text-dark">{{ $student?->class_code ?? ($user->studentClass?->class_name ?? ($user->studentClass?->class_code ?? 'Chưa xếp lớp')) }}</strong></span>
                            <span>•</span>
                            <span><i class="bi bi-mortarboard me-1"></i>Chuyên ngành: <strong class="text-dark">{{ $student?->specialization?->name ?? ($student?->specialization_name ?: 'Công nghệ phần mềm') }}</strong></span>
                            <span>•</span>
                            <span><i class="bi bi-calendar3 me-1"></i>{{ $currentSemester->semester_name ?? $currentSemesterCode }}</span>
                        </div>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('student.schedule.index') }}" class="btn btn-outline-dark btn-sm d-inline-flex align-items-center gap-1.5">
                        <i class="bi bi-calendar-week"></i>
                        <span>Lịch tuần</span>
                    </a>
                    <a href="{{ route('student.profile') }}" class="btn btn-dark btn-sm d-inline-flex align-items-center gap-1.5">
                        <i class="bi bi-person-gear"></i>
                        <span>Hồ sơ cá nhân</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- 5 THẺ KPI TỔNG QUAN TIẾN ĐỘ & HỌC TẬP -->
    <div class="row g-3 mb-4">
        <!-- KPI 1: GPA -->
        <div class="col-6 col-lg">
            <div class="p-3 rounded-3 bg-light border h-100 d-flex flex-column justify-content-center">
                <span class="text-secondary small fw-medium">GPA Tích lũy (4.0)</span>
                <div class="fs-4 fw-bold text-dark mt-0.5">
                    {{ number_format($cumulativeGpa, 2) }}
                </div>
                <div class="mt-1">
                    <span class="badge bg-light text-dark border small">
                        {{ $academicRank }}
                    </span>
                </div>
            </div>
        </div>

        <!-- KPI 2: Tín chỉ tích lũy -->
        <div class="col-6 col-lg">
            <div class="p-3 rounded-3 bg-light border h-100 d-flex flex-column justify-content-center">
                <span class="text-secondary small fw-medium">Tín chỉ Tích lũy</span>
                <div class="fs-4 fw-bold text-dark mt-0.5">
                    {{ $totalEarnedCredits }} <span class="fs-6 fw-normal text-secondary">/ {{ $totalFrameworkCredits }}</span>
                </div>
                <div class="progress mt-1.5" style="height: 4px;">
                    <div class="progress-bar bg-dark" role="progressbar" style="width: {{ $completionRate }}%"></div>
                </div>
                <span class="text-secondary small mt-1" style="font-size: 0.75rem;">Đạt {{ $completionRate }}% Khung</span>
            </div>
        </div>

        <!-- KPI 3: Học phần kỳ này -->
        <div class="col-6 col-lg">
            <div class="p-3 rounded-3 bg-light border h-100 d-flex flex-column justify-content-center">
                <span class="text-secondary small fw-medium">Học phần kỳ này</span>
                <div class="fs-4 fw-bold text-dark mt-0.5">
                    {{ $currentEnrollments->count() }} <span class="fs-6 fw-normal text-secondary">môn</span>
                </div>
                <span class="text-secondary small mt-1" style="font-size: 0.75rem;">
                    Tổng cộng {{ $enrolledCredits }} tín chỉ
                </span>
            </div>
        </div>

        <!-- KPI 4: Chuyên cần điểm danh -->
        <div class="col-6 col-lg">
            <div class="p-3 rounded-3 bg-light border h-100 d-flex flex-column justify-content-center">
                <span class="text-secondary small fw-medium">Chuyên cần điểm danh</span>
                <div class="fs-4 fw-bold text-success mt-0.5">
                    {{ $attendanceRate }}%
                </div>
                <span class="text-secondary small mt-1" style="font-size: 0.75rem;">
                    Có mặt: {{ $presentCount }} • Vắng: {{ $absentCount }}
                </span>
            </div>
        </div>

        <!-- KPI 5: Công nợ học phí -->
        <div class="col-12 col-lg">
            <div class="p-3 rounded-3 bg-light border h-100 d-flex flex-column justify-content-center">
                <span class="text-secondary small fw-medium">Học phí kỳ này</span>
                <div class="fs-5 fw-bold mt-0.5 {{ $tuitionDebt > 0 ? 'text-danger' : 'text-success' }}">
                    @if ($tuitionDebt > 0)
                        {{ number_format($tuitionDebt, 0, ',', '.') }} đ
                    @else
                        Đã hoàn thành
                    @endif
                </div>
                <div class="mt-1">
                    @if ($tuitionDebt > 0)
                        <span class="badge bg-light text-danger border border-danger-subtle small">
                            Chưa thanh toán
                        </span>
                    @else
                        <span class="badge bg-light text-success border border-success-subtle small">
                            ✓ Đã đóng đủ
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- LỐI TẮT NHANH (QUICK ACTIONS - 6 TIỆN ÍCH HỌC TẬP) -->
    <div class="row row-cols-2 row-cols-sm-3 row-cols-lg-6 g-2 mb-4">
        <div class="col">
            <a href="{{ route('student.schedule.index') }}" class="card border rounded-3 text-decoration-none bg-white p-3 text-center h-100 transition-all hover-shadow">
                <i class="bi bi-calendar-week fs-3 text-dark mb-1"></i>
                <div class="fw-semibold text-dark small">Thời khóa biểu</div>
                <span class="text-secondary" style="font-size: 0.7rem;">Lịch học tuần</span>
            </a>
        </div>
        <div class="col">
            <a href="{{ route('student.grades.index') }}" class="card border rounded-3 text-decoration-none bg-white p-3 text-center h-100 transition-all hover-shadow">
                <i class="bi bi-award fs-3 text-dark mb-1"></i>
                <div class="fw-semibold text-dark small">Kết quả học tập</div>
                <span class="text-secondary" style="font-size: 0.7rem;">Bảng điểm 8 kỳ</span>
            </a>
        </div>
        <div class="col">
            <a href="{{ route('student.attendance.index') }}" class="card border rounded-3 text-decoration-none bg-white p-3 text-center h-100 transition-all hover-shadow">
                <i class="bi bi-person-check fs-3 text-dark mb-1"></i>
                <div class="fw-semibold text-dark small">Điểm danh</div>
                <span class="text-secondary" style="font-size: 0.7rem;">Theo dõi vắng</span>
            </a>
        </div>
        <div class="col">
            <a href="{{ route('student.curriculum.index') }}" class="card border rounded-3 text-decoration-none bg-white p-3 text-center h-100 transition-all hover-shadow">
                <i class="bi bi-diagram-3 fs-3 text-dark mb-1"></i>
                <div class="fw-semibold text-dark small">Khung CTĐT</div>
                <span class="text-secondary" style="font-size: 0.7rem;">Kế hoạch đào tạo</span>
            </a>
        </div>
        <div class="col">
            <a href="{{ route('student.subjects.index') }}" class="card border rounded-3 text-decoration-none bg-white p-3 text-center h-100 transition-all hover-shadow">
                <i class="bi bi-journal-plus fs-3 text-dark mb-1"></i>
                <div class="fw-semibold text-dark small">Đăng ký môn</div>
                <span class="text-secondary" style="font-size: 0.7rem;">Đăng ký học phần</span>
            </a>
        </div>
        <div class="col">
            <a href="{{ route('student.tuition.index') }}" class="card border rounded-3 text-decoration-none bg-white p-3 text-center h-100 transition-all hover-shadow">
                <i class="bi bi-qr-code fs-3 text-dark mb-1"></i>
                <div class="fw-semibold text-dark small">Công nợ & Học phí</div>
                <span class="text-secondary" style="font-size: 0.7rem;">Thanh toán VietQR</span>
            </a>
        </div>
    </div>

    <!-- BỐ CỤC 2 CỘT NỘI DUNG CHÍNH -->
    <div class="row g-4">
        <!-- CỘT TRÁI (7 COLS): LỊCH HỌC HÔM NAY & DANH SÁCH MÔN HỌC ĐANG HỌC -->
        <div class="col-lg-7">
            <!-- Khối 1: Lịch học hôm nay & Tuần này -->
            <div class="card border shadow-sm rounded-3 bg-white mb-4 overflow-hidden">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-calendar-event text-dark fs-5"></i>
                        <strong class="text-dark fs-6">Lịch học hôm nay ({{ now()->format('d/m/Y') }})</strong>
                    </div>
                    <a href="{{ route('student.schedule.index') }}" class="btn btn-sm btn-light border text-dark">
                        Xem cả tuần ({{ $thisWeekSchedulesCount }} buổi) <i class="bi bi-arrow-right ms-1"></i>
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
                                            <span><i class="bi bi-clock me-1"></i>Tiết {{ $sched->start_period }} - {{ $sched->end_period }}</span>
                                            <span class="mx-1">•</span>
                                            <span><i class="bi bi-geo-alt me-1"></i>{{ $sched->room_info }}</span>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-light text-dark border small">{{ $sched->section_code }}</span>
                                        <div class="small text-secondary mt-1">GV: {{ $sched->lecturer_name }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4 text-secondary small">
                            <i class="bi bi-cup-hot fs-2 d-block mb-2 text-secondary opacity-50"></i>
                            Hôm nay bạn không có lịch học. Hãy xem thời khóa biểu các ngày tiếp theo trong tuần!
                        </div>
                    @endif
                </div>
            </div>

            <!-- Khối 2: Danh sách các học phần đang học kỳ này -->
            <div class="card border shadow-sm rounded-3 bg-white overflow-hidden mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <div>
                        <strong class="text-dark fs-6">Học phần đang theo học kỳ này</strong>
                        <div class="text-secondary small mt-0.5">{{ $currentSemester->semester_name ?? $currentSemesterCode }}</div>
                    </div>
                    <span class="badge bg-light text-dark border font-monospace">
                        {{ $currentEnrollments->count() }} môn
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-secondary small">
                                <tr>
                                    <th class="ps-3" style="width: 50px;">#</th>
                                    <th>Môn học & Tín chỉ</th>
                                    <th>Lịch học tuần</th>
                                    <th>Giảng viên</th>
                                    <th class="text-center pe-3" style="width: 120px;">Điểm danh</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($currentEnrollments as $idx => $enr)
                                    @php
                                        $sec = $enr->section;
                                        $dayStr = match ((int)$sec?->day_of_week) {
                                            2 => 'Thứ 2',
                                            3 => 'Thứ 3',
                                            4 => 'Thứ 4',
                                            5 => 'Thứ 5',
                                            6 => 'Thứ 6',
                                            7 => 'Thứ 7',
                                            default => 'Chủ nhật',
                                        };
                                    @endphp
                                    <tr>
                                        <td class="ps-3 text-muted small">{{ $idx + 1 }}</td>
                                        <td>
                                            <div class="fw-semibold text-dark">{{ $sec?->course?->course_name }}</div>
                                            <div class="text-secondary small font-monospace">
                                                {{ $sec?->course?->course_code }} • {{ $sec?->course?->credits }} TC
                                            </div>
                                        </td>
                                        <td class="small">
                                            <div class="fw-medium text-dark">{{ $dayStr }}</div>
                                            <div class="text-secondary">Tiết {{ $sec?->start_period }}-{{ $sec?->end_period }} ({{ $sec?->room_name }})</div>
                                        </td>
                                        <td class="small text-secondary">
                                            {{ $sec?->lecturer?->full_name ?? 'Chưa phân công' }}
                                        </td>
                                        <td class="text-center pe-3">
                                            <a href="{{ route('student.attendance.index', ['semester' => $currentSemesterCode, 'section_id' => $sec?->id]) }}" 
                                               class="btn btn-sm btn-outline-dark py-1 px-2.5 d-inline-flex align-items-center gap-1">
                                                <i class="bi bi-person-check"></i>
                                                <span>Chi tiết</span>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-secondary small">
                                            Chưa đăng ký môn học nào trong học kỳ này.
                                            <a href="{{ route('student.subjects.index') }}" class="ms-1">Đăng ký ngay</a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- CỘT PHẢI (5 COLS): HỒ SƠ TÓM TẮT & TÌNH HÌNH HỌC PHÍ -->
        <div class="col-lg-5">
            <!-- Khối 1: Tóm tắt Hồ sơ cá nhân -->
            <div class="card border shadow-sm rounded-3 bg-white mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <strong class="text-dark fs-6">Hồ sơ sinh viên</strong>
                    <a href="{{ route('student.profile') }}" class="btn btn-sm btn-light border text-dark">
                        <i class="bi bi-pencil-square me-1"></i> Cập nhật
                    </a>
                </div>
                <div class="card-body p-3">
                    <table class="table table-sm table-borderless small mb-0">
                        <tbody>
                            <tr>
                                <th class="text-secondary" style="width: 40%;">Mã sinh viên:</th>
                                <td class="fw-bold text-dark">{{ $student?->student_code ?? ($user->username ?? '2001230283') }}</td>
                            </tr>
                            <tr>
                                <th class="text-secondary">Họ và tên:</th>
                                <td class="fw-semibold text-dark">{{ $student?->full_name ?? $user->name }}</td>
                            </tr>
                            <tr>
                                <th class="text-secondary">Ngày sinh:</th>
                                <td>{{ $student?->dob ? $student->dob->format('d/m/Y') : '04/03/2005' }} ({{ $student?->gender ?? 'Nam' }})</td>
                            </tr>
                            <tr>
                                <th class="text-secondary">Lớp danh nghĩa:</th>
                                <td class="fw-bold text-dark">{{ $student?->class_code ?? ($user->studentClass?->class_name ?? ($user->studentClass?->class_code ?? 'Chưa xếp lớp')) }}</td>
                            </tr>
                            <tr>
                                <th class="text-secondary">Chuyên ngành:</th>
                                <td>{{ $student?->specialization?->name ?? ($student?->specialization_name ?: 'Công nghệ phần mềm') }}</td>
                            </tr>
                            <tr>
                                <th class="text-secondary">Khoa quản lý:</th>
                                <td>{{ $student?->faculty_name ?? 'Công nghệ thông tin' }}</td>
                            </tr>
                            <tr>
                                <th class="text-secondary">Số điện thoại:</th>
                                <td>{{ $student?->phone ?? '0942367010' }}</td>
                            </tr>
                            <tr>
                                <th class="text-secondary">Email liên hệ:</th>
                                <td>{{ $student?->email ?? $user->email }}</td>
                            </tr>
                            @php
                                $advisor = $student?->academic_advisor;
                            @endphp
                            @if ($advisor)
                                <tr class="border-top">
                                    <th class="text-secondary align-top pt-2">Cố vấn học tập:</th>
                                    <td class="pt-2">
                                        <div class="fw-bold text-dark">{{ $advisor->full_name }}</div>
                                        <div class="text-secondary small mt-0.5" style="font-size: 0.75rem;">
                                            <i class="bi bi-envelope me-0.5"></i>{{ $advisor->email }}
                                            @if ($advisor->phone) • <i class="bi bi-telephone me-0.5"></i>{{ $advisor->phone }} @endif
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Khối 2: Tình hình Công nợ & Học phí -->
            <div class="card border shadow-sm rounded-3 bg-white mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <strong class="text-dark fs-6">Học phí & Công nợ</strong>
                    <a href="{{ route('student.tuition.index') }}" class="btn btn-sm btn-light border text-dark">
                        Chi tiết <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="card-body p-3">
                    @if ($tuitionDebt > 0)
                        <div class="alert alert-light border border-danger-subtle p-3 rounded-3 mb-3">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <span class="text-secondary small">Học phí còn nợ kỳ này:</span>
                                <span class="badge bg-danger text-white">Chưa nộp</span>
                            </div>
                            <div class="fs-4 fw-bold text-danger">
                                {{ number_format($tuitionDebt, 0, ',', '.') }} VNĐ
                            </div>
                            <div class="small text-secondary mt-1">
                                Áp dụng: {{ $currentSemester->semester_name ?? $currentSemesterCode }}
                            </div>
                        </div>
                        <a href="{{ route('student.tuition.index') }}" class="btn btn-dark btn-sm w-100 py-2 d-inline-flex align-items-center justify-content-center gap-1.5">
                            <i class="bi bi-qr-code"></i>
                            <span>Xem mã VietQR và Thanh toán</span>
                        </a>
                    @else
                        <div class="text-center py-4">
                            <i class="bi bi-check-circle-fill fs-1 text-success mb-2 d-block"></i>
                            <h6 class="fw-bold text-dark mb-1">Đã hoàn thành học phí</h6>
                            <p class="text-secondary small mb-0">Bạn không còn nợ học phí trong học kỳ này.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
