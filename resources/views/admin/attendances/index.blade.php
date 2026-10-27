@extends('layouts.spark')

@section('title', 'Báo cáo Điểm danh Sinh viên - Admin HUIT')

@section('content')
<div class="container-fluid px-0">
    <!-- Header trang tối giản -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h4 fw-bold text-dark mb-1">Báo cáo Điểm danh Sinh viên</h1>
            <p class="text-secondary small mb-0">Theo dõi chuyên cần theo học kỳ, đối soát dữ liệu từ giảng viên và thống kê sinh viên vắng học phần</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            @if ($activeSection)
                <a href="{{ route('admin.attendances.index', ['semester' => $selectedSemester, 'specialization_id' => $selectedSpecId, 'keyword' => $keyword]) }}" 
                   class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1.5 shadow-sm">
                    <i class="bi bi-arrow-left"></i>
                    <span>Danh sách lớp học phần</span>
                </a>
            @endif
            <button type="button" onclick="window.print()" class="btn btn-dark btn-sm d-inline-flex align-items-center gap-1.5 shadow-sm">
                <i class="bi bi-printer"></i>
                <span>In báo cáo</span>
            </button>
        </div>
    </div>

    <!-- 4 THẺ THỐNG KÊ TỔNG QUAN (MONOCHROME MINIMALIST) -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6">
            <div class="card border rounded-3 bg-white shadow-sm h-100">
                <div class="card-body p-3.5 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-secondary small fw-medium mb-1">Tổng lớp học phần</div>
                        <div class="fs-4 fw-bold text-dark">{{ $totalSectionsCount }}</div>
                        <div class="text-secondary small" style="font-size: 0.78rem;">Đang mở trong học kỳ</div>
                    </div>
                    <div class="rounded-3 bg-light text-dark border d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                        <i class="bi bi-collection fs-5"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6">
            <div class="card border rounded-3 bg-white shadow-sm h-100">
                <div class="card-body p-3.5 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-secondary small fw-medium mb-1">Đã có điểm danh</div>
                        <div class="fs-4 fw-bold text-dark">{{ $recordedSectionsCount }} <span class="text-muted fs-6 fw-normal">/ {{ $totalSectionsCount }}</span></div>
                        <div class="text-secondary small" style="font-size: 0.78rem;">Lớp đã được GV ghi nhận</div>
                    </div>
                    <div class="rounded-3 bg-light text-dark border d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                        <i class="bi bi-clipboard-check fs-5"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6">
            <div class="card border rounded-3 bg-white shadow-sm h-100">
                <div class="card-body p-3.5 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-secondary small fw-medium mb-1">Tỷ lệ chuyên cần TB</div>
                        <div class="fs-4 fw-bold text-dark">{{ $overallAttendanceRate }}%</div>
                        <div class="text-secondary small" style="font-size: 0.78rem;">Toàn bộ các buổi đã học</div>
                    </div>
                    <div class="rounded-3 bg-light text-dark border d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                        <i class="bi bi-graph-up fs-5"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6">
            <div class="card border rounded-3 bg-white shadow-sm h-100">
                <div class="card-body p-3.5 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-secondary small fw-medium mb-1">Nguy cơ cấm thi</div>
                        <div class="fs-4 fw-bold text-dark">{{ $warningStudentsTotal }} <span class="text-muted fs-6 fw-normal">sinh viên</span></div>
                        <div class="text-secondary small" style="font-size: 0.78rem;">Vắng &ge; 20% tổng số buổi</div>
                    </div>
                    <div class="rounded-3 bg-light text-dark border d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                        <i class="bi bi-exclamation-circle fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- BỘ LỌC TÌM KIẾM TINH GỌN (SPARK STYLE) -->
    <div class="card border rounded-3 bg-white shadow-sm mb-4 no-print">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('admin.attendances.index') }}" class="row g-2 align-items-center" id="attendanceFilterForm">
                @if ($selectedSectionId)
                    <input type="hidden" name="section_id" value="{{ $selectedSectionId }}">
                @endif
                @if ($attendanceDate)
                    <input type="hidden" name="date" value="{{ $attendanceDate }}">
                @endif

                <!-- 1. Học kỳ đào tạo -->
                <div class="col-lg-3 col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-secondary border"><i class="bi bi-calendar3"></i></span>
                        <select name="semester" class="form-select form-select-sm" onchange="this.form.submit()">
                            @foreach ($semesters as $sem)
                                <option value="{{ $sem->semester_code }}" {{ $selectedSemester == $sem->semester_code ? 'selected' : '' }}>
                                    {{ $sem->semester_name ?? $sem->semester_code }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- 2. Chuyên ngành đào tạo -->
                <div class="col-lg-3 col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-secondary border"><i class="bi bi-mortarboard"></i></span>
                        <select name="specialization_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">-- Tất cả Chuyên ngành --</option>
                            @foreach ($specializations as $spec)
                                <option value="{{ $spec->id }}" {{ $selectedSpecId == $spec->id ? 'selected' : '' }}>
                                    [{{ $spec->code }}] {{ $spec->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- 3. Tìm kiếm theo mã lớp HP, tên môn, giảng viên -->
                <div class="col-lg-4 col-md-4">
                    <div class="input-group input-group-sm">
                        <input type="text" name="keyword" class="form-control" 
                               value="{{ $keyword }}" placeholder="Tìm mã LHP, môn học, giảng viên...">
                        @if ($keyword)
                            <a href="{{ route('admin.attendances.index', ['semester' => $selectedSemester, 'specialization_id' => $selectedSpecId]) }}" 
                               class="btn btn-outline-secondary d-flex align-items-center" title="Xóa tìm kiếm">
                                <i class="bi bi-x"></i>
                            </a>
                        @endif
                        <button type="submit" class="btn btn-dark px-3">
                            <i class="bi bi-search me-1"></i>Tìm
                        </button>
                    </div>
                </div>

                <!-- 4. Nút Đặt lại -->
                <div class="col-lg-2 col-md-12 text-md-end">
                    @if ($selectedSpecId || $keyword || $selectedSectionId)
                        <a href="{{ route('admin.attendances.index', ['semester' => $selectedSemester]) }}" 
                           class="btn btn-outline-secondary btn-sm py-1.5 px-3 w-100 d-inline-flex align-items-center justify-content-center gap-1">
                            <i class="bi bi-arrow-clockwise"></i>
                            <span>Đặt lại</span>
                        </a>
                    @else
                        <span class="text-muted small fst-italic">Học kỳ: {{ $selectedSemester }}</span>
                    @endif
                </div>
            </form>
        </div>
    </div>

    @if ($activeSection)
        <!-- ========================================================================= -->
        <!-- CHẾ ĐỘ 2: XEM CHI TIẾT ĐIỂM DANH CỦA MỘT LỚP HỌC PHẦN CỤ THỂ              -->
        <!-- ========================================================================= -->
        
        <!-- Breadcrumb & Tiêu đề lớp học phần -->
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 bg-white border rounded-3 p-3 mb-4 shadow-sm">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <a href="{{ route('admin.attendances.index', ['semester' => $selectedSemester, 'specialization_id' => $selectedSpecId, 'keyword' => $keyword]) }}" 
                   class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1.5">
                    <i class="bi bi-arrow-left"></i>
                    <span>Tất cả lớp học phần</span>
                </a>
                <span class="text-secondary small">|</span>
                <span class="badge bg-dark text-white font-monospace px-2.5 py-1">LHP: {{ $activeSection->section_code }}</span>
                <strong class="text-dark">{{ $activeSection->course?->course_name }}</strong>
                <span class="badge bg-light text-secondary border font-monospace">{{ $activeSection->course?->credits ?? 3 }} TC</span>
            </div>

            <div class="d-flex align-items-center gap-2">
                <span class="small text-secondary">Giảng viên phụ trách:</span>
                <strong class="text-dark small">{{ $activeSection->lecturer?->full_name ?? 'Chưa phân công' }}</strong>
            </div>
        </div>

        <!-- Khối Thông tin lớp & Thống kê Chuyên cần của buổi -->
        <div class="row g-3 mb-4">
            <!-- Thẻ 1: Thông tin Lớp học phần -->
            <div class="col-lg-6">
                <div class="card border rounded-3 bg-white shadow-sm h-100">
                    <div class="card-header bg-white py-3 border-bottom">
                        <strong class="text-dark small text-uppercase">Thông tin Lớp học phần</strong>
                    </div>
                    <div class="card-body p-3.5">
                        <table class="table table-borderless table-sm mb-0 small">
                            <tbody>
                                <tr>
                                    <td class="text-secondary" style="width: 150px;">Mã lớp học phần:</td>
                                    <td><span class="badge bg-light text-dark border font-monospace fw-bold">{{ $activeSection->section_code }}</span></td>
                                </tr>
                                <tr>
                                    <td class="text-secondary">Học phần:</td>
                                    <td><strong class="text-dark">[{{ $activeSection->course?->course_code }}] {{ $activeSection->course?->course_name }}</strong></td>
                                </tr>
                                <tr>
                                    <td class="text-secondary">Giảng viên giảng dạy:</td>
                                    <td><span class="text-dark fw-semibold">{{ $activeSection->lecturer?->full_name ?? 'Chưa phân công' }}</span> ({{ $activeSection->lecturer?->email ?? '-' }})</td>
                                </tr>
                                <tr>
                                    <td class="text-secondary">Phòng học & Lịch học:</td>
                                    <td class="text-dark">{{ $activeSection->room_name ?? 'Chưa xếp phòng' }} (Thứ {{ $activeSection->day_of_week }}, Tiết {{ $activeSection->start_period }}-{{ $activeSection->end_period }})</td>
                                </tr>
                                <tr>
                                    <td class="text-secondary">Chuyên ngành đào tạo:</td>
                                    <td class="text-dark">{{ $activeSection->specialization_name ?? 'Học phần chung' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-secondary">Tiến độ điểm danh:</td>
                                    <td>
                                        <span class="badge bg-light text-dark border fw-normal">
                                            Đã điểm danh <strong>{{ $recordedDates->count() }}</strong> buổi học
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Thẻ 2: Thống kê Điểm danh ngày đang chọn -->
            <div class="col-lg-6">
                <div class="card border rounded-3 bg-white shadow-sm h-100">
                    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                        <strong class="text-dark small text-uppercase">Báo cáo Buổi học: {{ \Carbon\Carbon::parse($attendanceDate)->format('d/m/Y') }}</strong>
                        <span class="badge bg-light text-dark border font-monospace">
                            Tỷ lệ có mặt: {{ $attendanceRate }}%
                        </span>
                    </div>
                    <div class="card-body p-3.5 d-flex flex-column justify-content-between">
                        <div class="row g-2 text-center mb-3">
                            <div class="col-4">
                                <div class="p-2.5 bg-light rounded-2 border">
                                    <div class="fs-4 fw-bold text-dark">{{ $totalStudents }}</div>
                                    <div class="small text-secondary" style="font-size: 0.78rem;">Sĩ số sinh viên</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="p-2.5 bg-light rounded-2 border">
                                    <div class="fs-4 fw-bold text-dark">{{ $presentCount }}</div>
                                    <div class="small text-secondary" style="font-size: 0.78rem;">Có mặt</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="p-2.5 bg-light rounded-2 border">
                                    <div class="fs-4 fw-bold text-dark">{{ $absentCount }}</div>
                                    <div class="small text-secondary" style="font-size: 0.78rem;">Vắng mặt</div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <div class="d-flex align-items-center justify-content-between small text-secondary mb-1">
                                <span>Tiến độ chuyên cần của buổi</span>
                                <span class="fw-bold text-dark font-monospace">{{ $attendanceRate }}%</span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-dark" role="progressbar" style="width: {{ $attendanceRate }}%;" aria-valuenow="{{ $attendanceRate }}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <div class="text-secondary small mt-2">
                                <i class="bi bi-info-circle me-1"></i>Dữ liệu điểm danh được giảng viên phụ trách đồng bộ trực tiếp lên hệ thống.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Thanh Chọn Ngày / Buổi học của lớp này -->
        <div class="card border rounded-3 bg-white shadow-sm mb-4 no-print">
            <div class="card-body p-3">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="small fw-semibold text-secondary me-1">
                            <i class="bi bi-calendar-event me-1"></i>Chọn buổi học:
                        </span>
                        @forelse ($recordedDates as $rDate)
                            <a href="{{ route('admin.attendances.index', ['semester' => $selectedSemester, 'specialization_id' => $selectedSpecId, 'keyword' => $keyword, 'section_id' => $activeSection->id, 'date' => $rDate]) }}" 
                               class="btn btn-sm {{ $attendanceDate === $rDate ? 'btn-dark' : 'btn-outline-secondary' }} py-1 px-2.5 shadow-sm font-monospace"
                               style="font-size: 0.8rem;">
                                <i class="bi bi-calendar3 me-1"></i>{{ \Carbon\Carbon::parse($rDate)->format('d/m/Y') }}
                            </a>
                        @empty
                            <span class="badge bg-light text-secondary border">Chưa có buổi học nào được điểm danh</span>
                        @endforelse
                    </div>

                    <!-- Ô chọn ngày bất kỳ -->
                    <form method="GET" action="{{ route('admin.attendances.index') }}" class="d-flex align-items-center gap-1.5 ms-auto">
                        <input type="hidden" name="semester" value="{{ $selectedSemester }}">
                        <input type="hidden" name="specialization_id" value="{{ $selectedSpecId }}">
                        <input type="hidden" name="keyword" value="{{ $keyword }}">
                        <input type="hidden" name="section_id" value="{{ $activeSection->id }}">
                        <div class="input-group input-group-sm">
                            <input type="date" name="date" class="form-control" value="{{ $attendanceDate }}">
                            <button type="submit" class="btn btn-dark" title="Xem ngày này">
                                <span>Xem</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- CẢNH BÁO NGUY CƠ CẤM THI TRONG LỚP (NẾU CÓ - PHONG CÁCH TỐI GIẢN) -->
        @if ($warningStudents->isNotEmpty())
            <div class="card border rounded-3 bg-light p-3 mb-4 shadow-sm">
                <div class="d-flex align-items-start gap-2.5">
                    <i class="bi bi-exclamation-circle text-dark fs-5 mt-0.5"></i>
                    <div class="w-100">
                        <div class="fw-bold text-dark small mb-1">
                            Lưu ý học vụ: Có {{ $warningStudents->count() }} sinh viên vắng &ge; 20% tổng số buổi của học phần này (nguy cơ cấm thi)
                        </div>
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            @foreach ($warningStudents as $wStu)
                                <span class="badge bg-white text-dark border py-1.5 px-2 font-monospace">
                                    {{ $wStu['student']?->student_code }} - {{ $wStu['student']?->full_name }} (Vắng {{ $wStu['absent_sessions'] }}/{{ $wStu['total_sessions'] }} buổi: {{ $wStu['absent_rate'] }}%)
                                </span>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- BẢNG DANH SÁCH CHI TIẾT SINH VIÊN CỦA BUỔI ĐIỂM DANH -->
        <div class="card border rounded-3 bg-white shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <strong class="text-dark fs-6">
                        Danh sách Điểm danh Sinh viên (Buổi ngày {{ \Carbon\Carbon::parse($attendanceDate)->format('d/m/Y') }})
                    </strong>
                    <div class="text-secondary small mt-0.5">
                        Tổng số <strong>{{ $totalStudents }}</strong> sinh viên trong lớp học phần.
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-light text-dark border">
                        Có mặt: {{ $presentCount }}
                    </span>
                    <span class="badge bg-light text-secondary border">
                        Vắng mặt: {{ $absentCount }}
                    </span>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-secondary small">
                        <tr>
                            <th class="ps-3 text-center" style="width: 50px;">#</th>
                            <th style="width: 140px;">MSSV</th>
                            <th>Họ và tên sinh viên</th>
                            <th style="width: 120px;">Lớp danh nghĩa</th>
                            <th>Chuyên ngành</th>
                            <th class="text-center" style="width: 150px;">Trạng thái buổi học</th>
                            <th style="width: 200px;">Ghi chú của GV</th>
                            <th class="text-center" style="width: 170px;">Tích lũy vắng cả môn</th>
                            <th class="text-end pe-3" style="width: 140px;">Giờ ghi nhận</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($attendanceRecords as $index => $rec)
                            @php
                                $stu = $rec['student'];
                                $status = $rec['status'];
                                $isRecorded = $rec['is_recorded'];
                                $isWarning = $rec['is_warning'];
                            @endphp
                            <tr>
                                <td class="ps-3 text-center text-muted small">{{ $index + 1 }}</td>
                                <td>
                                    <a href="{{ route('admin.students.show', $stu) }}" class="badge bg-light text-dark border font-monospace text-decoration-none fw-bold" target="_blank" title="Xem hồ sơ sinh viên">
                                        {{ $stu?->student_code }}
                                    </a>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="bg-light text-dark border rounded-circle d-flex align-items-center justify-content-center fw-semibold small flex-shrink-0"
                                             style="width: 32px; height: 32px;">
                                            {{ mb_substr($stu?->full_name ?? 'S', 0, 1) }}
                                        </div>
                                        <div>
                                            <div class="fw-semibold text-dark">{{ $stu?->full_name }}</div>
                                            <div class="text-muted small" style="font-size: 0.78rem;">{{ $stu?->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-secondary border small">{{ $stu?->class_code ?? 'Lớp' }}</span>
                                </td>
                                <td class="small text-secondary">
                                    {{ $stu?->specialization?->name ?? $stu?->specialization_name }}
                                </td>
                                <td class="text-center">
                                    @if (!$isRecorded)
                                        <span class="badge bg-light text-secondary border small px-2 py-1">
                                            <i class="bi bi-clock me-1"></i>Chưa điểm danh
                                        </span>
                                    @elseif ($status === 'present')
                                        <span class="badge bg-light text-dark border small px-2 py-1 fw-bold">
                                            <i class="bi bi-check2 me-1"></i>Có mặt
                                        </span>
                                    @else
                                        <span class="badge bg-light text-secondary border small px-2 py-1 fw-semibold">
                                            <i class="bi bi-x-lg me-1"></i>Vắng mặt
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if ($rec['note'])
                                        <span class="small text-dark fst-italic">"{{ $rec['note'] }}"</span>
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if ($rec['total_sessions'] > 0)
                                        <span class="small text-secondary font-monospace">
                                            Vắng {{ $rec['absent_sessions'] }}/{{ $rec['total_sessions'] }} buổi ({{ $rec['absent_rate'] }}%)
                                        </span>
                                        @if ($isWarning)
                                            <span class="badge bg-dark text-white rounded-pill ms-1 font-monospace" style="font-size: 0.68rem;" title="Vắng &ge; 20% số buổi">
                                                &ge; 20%
                                            </span>
                                        @endif
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                                <td class="text-end pe-3 small text-secondary">
                                    {{ $rec['updated_at'] ? \Carbon\Carbon::parse($rec['updated_at'])->format('H:i d/m') : '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted small">
                                    <i class="bi bi-person-x fs-2 d-block mb-2 text-secondary opacity-50"></i>
                                    Lớp học phần này chưa có sinh viên nào đăng ký ghi danh.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    @else
        <!-- ========================================================================= -->
        <!-- CHẾ ĐỘ 1: TỔNG QUAN TẤT CẢ CÁC LỚP HỌC PHẦN TRONG HỌC KỲ                  -->
        <!-- ========================================================================= -->
        
        <div class="card border rounded-3 bg-white shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <strong class="text-dark fs-6">
                        Danh sách Lớp học phần & Tình hình Chuyên cần (Học kỳ {{ $selectedSemester }})
                    </strong>
                    <div class="text-secondary small mt-0.5">
                        Tìm thấy <strong>{{ $sections->count() }}</strong> lớp học phần. Bấm <strong>"Xem chi tiết"</strong> để kiểm tra danh sách sinh viên và đối soát từng buổi học.
                    </div>
                </div>
                <span class="badge bg-light text-dark border font-monospace">
                    {{ $sections->count() }} lớp học phần
                </span>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-secondary small">
                        <tr>
                            <th class="ps-3 text-center" style="width: 50px;">#</th>
                            <th style="width: 140px;">Mã LHP</th>
                            <th>Môn học / Học phần</th>
                            <th>Giảng viên phụ trách</th>
                            <th style="width: 200px;">Lịch học & Phòng</th>
                            <th class="text-center" style="width: 90px;">Sĩ số</th>
                            <th class="text-center" style="width: 130px;">Số buổi đã học</th>
                            <th style="width: 160px;">Tỷ lệ chuyên cần</th>
                            <th class="text-end pe-3" style="width: 160px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($sections as $index => $sec)
                            <tr>
                                <td class="ps-3 text-center text-muted small">{{ $index + 1 }}</td>
                                <td>
                                    <span class="badge bg-dark text-white font-monospace px-2 py-1">
                                        {{ $sec->section_code }}
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $sec->course?->course_name }}</div>
                                    <div class="text-secondary small d-flex align-items-center gap-1.5" style="font-size: 0.78rem;">
                                        <span class="badge bg-light text-secondary border font-monospace">{{ $sec->course?->course_code }}</span>
                                        <span>{{ $sec->course?->credits ?? 3 }} tín chỉ</span>
                                        <span>&bull;</span>
                                        <span>{{ $sec->specialization_name ?? 'Môn chung' }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark small">{{ $sec->lecturer?->full_name ?? 'Chưa phân công' }}</div>
                                    <div class="text-muted small" style="font-size: 0.76rem;">{{ $sec->lecturer?->email ?? '-' }}</div>
                                </td>
                                <td>
                                    <div class="small text-dark">
                                        <i class="bi bi-geo-alt me-1 text-secondary"></i>{{ $sec->room_name ?? 'Chưa xếp phòng' }}
                                    </div>
                                    <div class="small text-secondary" style="font-size: 0.78rem;">
                                        Thứ {{ $sec->day_of_week }}, Tiết {{ $sec->start_period }}-{{ $sec->end_period }}
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border font-monospace">
                                        {{ $sec->enrollments_count }} SV
                                    </span>
                                </td>
                                <td class="text-center">
                                    @if ($sec->session_count > 0)
                                        <span class="badge bg-light text-dark border font-monospace">
                                            {{ $sec->session_count }} buổi
                                        </span>
                                    @else
                                        <span class="badge bg-light text-secondary border small">Chưa điểm danh</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($sec->attendance_rate !== null)
                                        <div class="d-flex align-items-center justify-content-between small mb-1">
                                            <span class="fw-bold text-dark font-monospace">
                                                {{ $sec->attendance_rate }}%
                                            </span>
                                            <span class="text-secondary" style="font-size: 0.74rem;">có mặt</span>
                                        </div>
                                        <div class="progress" style="height: 5px;">
                                            <div class="progress-bar bg-dark" 
                                                 role="progressbar" style="width: {{ $sec->attendance_rate }}%;" aria-valuenow="{{ $sec->attendance_rate }}" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                                <td class="text-end pe-3">
                                    <a href="{{ route('admin.attendances.index', ['semester' => $selectedSemester, 'specialization_id' => $selectedSpecId, 'keyword' => $keyword, 'section_id' => $sec->id]) }}" 
                                       class="btn btn-sm btn-dark d-inline-flex align-items-center gap-1.5 px-3 py-1.5 shadow-sm">
                                        <i class="bi bi-eye"></i>
                                        <span>Xem chi tiết</span>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted small">
                                    <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                    Không tìm thấy lớp học phần nào phù hợp trong học kỳ <strong>{{ $selectedSemester }}</strong>.
                                    <div class="mt-2">
                                        <a href="{{ route('admin.attendances.index', ['semester' => $selectedSemester]) }}" class="btn btn-outline-secondary btn-sm">
                                            <i class="bi bi-arrow-clockwise me-1"></i>Làm mới bộ lọc
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

<style>
@media print {
    .btn, .navbar, .sidebar, .no-print, form, nav[aria-label="breadcrumb"] {
        display: none !important;
    }
    .card {
        border: 1px solid #ddd !important;
        box-shadow: none !important;
    }
    body {
        background: #fff !important;
    }
}
</style>
@endsection
