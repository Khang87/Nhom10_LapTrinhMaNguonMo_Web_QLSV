@extends('layouts.spark')

@section('title', 'Chi tiết Bảng Điểm Lớp Học Phần - ' . ($section->course?->course_name ?? 'Lớp HP'))

@section('content')
<div class="container-fluid px-0">
    @php
        $isApproved = ($section->grade_status === 'APPROVED');
        $isSubmitted = ($section->grade_status === 'SUBMITTED');
        $isDraft = ($section->grade_status === 'DRAFT');
    @endphp

    <!-- Header & Breadcrumb -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                <a href="{{ route('admin.grades.approvals', ['semester' => $section->semester_code]) }}" 
                   class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1 py-1 px-2.5">
                    <i class="bi bi-arrow-left"></i>
                    <span>Danh sách phê duyệt</span>
                </a>
                <span class="badge bg-light text-dark border font-monospace">{{ $section->section_code }}</span>
                <span class="badge bg-light text-secondary border">{{ $section->semester_code }}</span>
                @if ($isApproved)
                    <span class="badge bg-success text-white">
                        <i class="bi bi-check-circle-fill me-1"></i>Đã phê duyệt chính thức
                    </span>
                @elseif ($isSubmitted)
                    <span class="badge bg-primary text-white">
                        <i class="bi bi-clock-history me-1"></i>Chờ Phòng Đào tạo duyệt
                    </span>
                @else
                    <span class="badge bg-light text-secondary border">
                        <i class="bi bi-pencil me-1"></i>Giảng viên đang nhập nháp
                    </span>
                @endif
            </div>
            <h1 class="h3 fw-bold text-dark mb-1">
                {{ $section->course?->course_name }} ({{ $section->course?->course_code }})
            </h1>
            <p class="text-secondary small mb-0">
                Lớp học phần: <strong>{{ $section->section_code }}</strong> • Giảng viên: <strong>{{ $section->lecturer?->full_name ?? 'Chưa phân công' }}</strong> ({{ $section->lecturer?->lecturer_code }})
            </p>
        </div>

        <div class="d-flex align-items-center gap-2">
            <button type="button" onclick="window.print()" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1.5 px-3 py-2">
                <i class="bi bi-printer"></i>
                <span>In bảng điểm</span>
            </button>

            @if ($isSubmitted)
                <!-- Nút Duyệt -->
                <form method="POST" action="{{ route('admin.grades.approve', $section) }}" class="d-inline"
                      onsubmit="return confirm('Xác nhận phê duyệt chính thức bảng điểm môn {{ $section->course?->course_name }}? Điểm sẽ được công bố chính thức cho sinh viên tra cứu.');">
                    @csrf
                    <button type="submit" class="btn btn-success btn-sm d-inline-flex align-items-center gap-1.5 px-3 py-2 shadow-sm">
                        <i class="bi bi-check-circle-fill"></i>
                        <span class="fw-semibold">Phê duyệt chính thức</span>
                    </button>
                </form>

                <!-- Nút Mở khóa / Trả về -->
                <button type="button" class="btn btn-warning text-dark btn-sm d-inline-flex align-items-center gap-1.5 px-3 py-2 shadow-sm"
                        data-bs-toggle="modal" data-bs-target="#unlockModal">
                    <i class="bi bi-unlock"></i>
                    <span>Mở khóa cho GV</span>
                </button>
            @elseif ($isApproved)
                <!-- Mở khóa lại nếu có sai sót -->
                <button type="button" class="btn btn-outline-warning text-dark btn-sm d-inline-flex align-items-center gap-1.5 px-3 py-2"
                        data-bs-toggle="modal" data-bs-target="#unlockModal">
                    <i class="bi bi-unlock"></i>
                    <span>Mở khóa lại bảng điểm</span>
                </button>
            @endif
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-light border shadow-sm rounded-3 d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="bi bi-check-circle-fill text-success fs-5"></i>
            <div class="text-dark small">{{ session('success') }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- THÔNG TIN TỔNG QUAN PHÊ DUYỆT -->
    <div class="card border shadow-sm rounded-3 bg-white mb-4">
        <div class="card-body p-3 p-md-4">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="text-secondary small">Trạng thái phê duyệt</div>
                    <div class="fw-bold mt-1">
                        @if ($isApproved)
                            <span class="text-success"><i class="bi bi-check-circle-fill me-1"></i>ĐÃ DUYỆT CÔNG BỐ</span>
                        @elseif ($isSubmitted)
                            <span class="text-primary"><i class="bi bi-clock-history me-1"></i>CHỜ DUYỆT</span>
                        @else
                            <span class="text-secondary"><i class="bi bi-pencil me-1"></i>ĐANG NHẬP NHÁP</span>
                        @endif
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-secondary small">Thời gian Giảng viên nộp</div>
                    <div class="fw-semibold text-dark mt-1">
                        {{ $section->grade_submitted_at ? $section->grade_submitted_at->format('d/m/Y H:i') : 'Chưa nộp' }}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-secondary small">Thời gian duyệt chính thức</div>
                    <div class="fw-semibold text-dark mt-1">
                        {{ $section->grade_approved_at ? $section->grade_approved_at->format('d/m/Y H:i') : 'Chưa duyệt' }}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-secondary small">Ghi chú phê duyệt / Mở khóa</div>
                    <div class="text-dark small mt-1 font-monospace">
                        {{ $section->grade_note ?: 'Không có ghi chú' }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- KHỐI THỐNG KÊ PHỔ ĐIỂM CỦA LỚP HỌC PHẦN -->
    <div class="row g-2 mb-4">
        <div class="col-6 col-md-2">
            <div class="p-2.5 rounded-3 bg-light border text-center">
                <span class="text-secondary small" style="font-size: 0.75rem;">Điểm A (Giỏi/XS)</span>
                <div class="fs-5 fw-bold text-success mt-0.5">{{ $gradeDistribution['A'] }} SV</div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="p-2.5 rounded-3 bg-light border text-center">
                <span class="text-secondary small" style="font-size: 0.75rem;">Điểm B (Khá)</span>
                <div class="fs-5 fw-bold text-primary mt-0.5">{{ $gradeDistribution['B'] }} SV</div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="p-2.5 rounded-3 bg-light border text-center">
                <span class="text-secondary small" style="font-size: 0.75rem;">Điểm C (Trung bình)</span>
                <div class="fs-5 fw-bold text-dark mt-0.5">{{ $gradeDistribution['C'] }} SV</div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="p-2.5 rounded-3 bg-light border text-center">
                <span class="text-secondary small" style="font-size: 0.75rem;">Điểm D (TB Yếu)</span>
                <div class="fs-5 fw-bold text-warning mt-0.5">{{ $gradeDistribution['D'] }} SV</div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="p-2.5 rounded-3 bg-light border text-center">
                <span class="text-secondary small" style="font-size: 0.75rem;">Điểm F (Rớt môn)</span>
                <div class="fs-5 fw-bold text-danger mt-0.5">{{ $gradeDistribution['F'] }} SV</div>
            </div>
        </div>
        <div class="col-12 col-md-2">
            <div class="p-2.5 rounded-3 bg-light border text-center">
                <span class="text-secondary small" style="font-size: 0.75rem;">Tỷ lệ Đạt môn</span>
                <div class="fs-5 fw-bold text-dark mt-0.5">{{ $passRate }}%</div>
            </div>
        </div>
    </div>

    <!-- BẢNG CHI TIẾT ĐIỂM TOÀN BỘ LỚP -->
    <div class="card border shadow-sm rounded-3 bg-white mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <strong class="text-dark fs-6">Danh sách Sinh viên & Điểm số ({{ $section->enrollments->count() }} sinh viên)</strong>
                <span class="text-secondary small ms-2">Công thức: QT (20%) + GK (20%) + CK (60%)</span>
            </div>
            <div class="small text-secondary">
                Điểm trung bình cả lớp: <strong class="text-dark">{{ $averageScore }} / 10</strong>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-center">
                <thead class="table-light text-secondary small">
                    <tr>
                        <th class="ps-3 text-center" style="width: 50px;">#</th>
                        <th style="width: 130px;" class="text-start">MSSV</th>
                        <th class="text-start">Họ và tên</th>
                        <th style="width: 110px;">Lớp</th>
                        <th style="width: 110px;">QT (20%)</th>
                        <th style="width: 110px;">GK (20%)</th>
                        <th style="width: 110px;">CK (60%)</th>
                        <th class="bg-light fw-bold" style="width: 110px;">Tổng (10)</th>
                        <th style="width: 90px;">Thang 4</th>
                        <th style="width: 80px;">Chữ</th>
                        <th class="pe-3" style="width: 110px;">Kết quả</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($section->enrollments as $index => $enr)
                        @php
                            $stu = $enr->student;
                            $hasScore = ($enr->total_score_10 !== null);
                            $isPass = ($hasScore && $enr->total_score_10 >= 4.0);
                        @endphp
                        <tr>
                            <td class="ps-3 text-center text-muted small">{{ $index + 1 }}</td>
                            <td class="text-start">
                                <span class="badge bg-light text-dark border font-monospace">
                                    {{ $stu?->student_code }}
                                </span>
                            </td>
                            <td class="text-start">
                                <div class="fw-semibold text-dark">{{ $stu?->full_name }}</div>
                            </td>
                            <td>
                                <span class="badge bg-light text-secondary border small">{{ $stu?->class_code ?? 'Lớp' }}</span>
                            </td>

                            <!-- QT -->
                            <td class="fw-bold {{ $enr->process_score !== null ? 'text-dark' : 'text-muted' }}">
                                {{ $enr->process_score !== null ? number_format($enr->process_score, 1) : '-' }}
                            </td>

                            <!-- GK -->
                            <td class="fw-bold {{ $enr->midterm_score !== null ? 'text-dark' : 'text-muted' }}">
                                {{ $enr->midterm_score !== null ? number_format($enr->midterm_score, 1) : '-' }}
                            </td>

                            <!-- CK -->
                            <td class="fw-bold {{ $enr->final_score !== null ? 'text-dark' : 'text-muted' }}">
                                {{ $enr->final_score !== null ? number_format($enr->final_score, 1) : '-' }}
                            </td>

                            <!-- Tổng kết 10 -->
                            <td class="bg-light fw-bold fs-6 {{ $hasScore ? ($isPass ? 'text-dark' : 'text-danger') : 'text-muted' }}">
                                {{ $hasScore ? number_format($enr->total_score_10, 2) : '-' }}
                            </td>

                            <!-- Thang 4 -->
                            <td class="text-secondary small fw-medium">
                                {{ $enr->total_score_4 !== null ? number_format($enr->total_score_4, 2) : '-' }}
                            </td>

                            <!-- Điểm chữ -->
                            <td>
                                @if ($enr->letter_grade)
                                    <span class="badge {{ $enr->letter_grade === 'F' ? 'bg-danger text-white' : 'bg-light text-dark border' }} font-monospace">
                                        {{ $enr->letter_grade }}
                                    </span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>

                            <!-- Kết quả -->
                            <td class="pe-3">
                                @if ($hasScore)
                                    @if ($isPass)
                                        <span class="badge bg-light text-success border border-success-subtle px-2 py-1">
                                            ✓ Đạt môn
                                        </span>
                                    @else
                                        <span class="badge bg-light text-danger border border-danger-subtle px-2 py-1">
                                            ✕ Rớt môn
                                        </span>
                                    @endif
                                @else
                                    <span class="badge bg-light text-secondary border px-2 py-1">
                                        Chưa có điểm
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center py-4 text-secondary small">
                                Chưa có sinh viên nào đăng ký lớp học phần này.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Mở khóa bảng điểm -->
    <div class="modal fade" id="unlockModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <form method="POST" action="{{ route('admin.grades.unlock', $section) }}">
                    @csrf
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-bold text-dark">
                            <i class="bi bi-unlock text-warning me-1"></i>Mở khóa bảng điểm
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4 text-start">
                        <p class="small text-secondary mb-3">
                            Mở khóa bảng điểm lớp <strong>{{ $section->section_code }} — {{ $section->course?->course_name }}</strong> để Giảng viên <strong>{{ $section->lecturer?->full_name }}</strong> có thể chỉnh sửa lại điểm số.
                        </p>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark">Lý do mở khóa cho Giảng viên:</label>
                            <textarea name="reason" class="form-control form-control-sm" rows="3" required 
                                      placeholder="Ví dụ: Giảng viên xin cập nhật lại điểm bài tập lớn cho sinh viên..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-top bg-light">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-sm btn-warning text-dark px-3">
                            Xác nhận mở khóa
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
