@extends('layouts.spark')

@section('title', 'Bảng Điểm Lớp Học Phần - ' . ($section->course?->course_name ?? 'Lớp HP'))

@section('content')
<div class="container-fluid px-0">
    @php
        $isLocked = $section->isGradeLocked();
        $isApproved = ($section->grade_status === 'APPROVED');
        $isSubmitted = ($section->grade_status === 'SUBMITTED');
    @endphp

    <!-- Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="{{ route('lecturer.grades.index') }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1 py-1 px-2.5">
                    <i class="bi bi-arrow-left"></i>
                    <span>Quay lại</span>
                </a>
                <span class="badge bg-light text-dark border font-monospace">{{ $section->section_code }}</span>
                <span class="badge bg-light text-secondary border">{{ $section->semester_code }}</span>
                @if ($isApproved)
                    <span class="badge bg-success text-white">
                        <i class="bi bi-check-circle-fill me-1"></i>PĐT Đã phê duyệt chính thức
                    </span>
                @elseif ($isSubmitted)
                    <span class="badge bg-primary text-white">
                        <i class="bi bi-lock-fill me-1"></i>Đã nộp về PĐT (Đang khóa)
                    </span>
                @else
                    <span class="badge bg-light text-secondary border">
                        <i class="bi bi-pencil me-1"></i>Đang nhập nháp
                    </span>
                @endif
            </div>
            <h1 class="h3 fw-bold text-dark mb-1">
                {{ $section->course?->course_name }} ({{ $section->course?->course_code }})
            </h1>
            <p class="text-secondary small mb-0">
                Nhập và nộp điểm theo thang điểm 10. Sau khi bấm nộp về Phòng Đào tạo, bảng điểm sẽ được khóa bảo vệ dữ liệu.
            </p>
        </div>

        <div class="d-flex align-items-center gap-2">
            @if (!$isLocked)
                <button type="submit" form="gradeForm" class="btn btn-outline-dark btn-sm d-inline-flex align-items-center gap-1.5 px-3 py-2">
                    <i class="bi bi-floppy"></i>
                    <span>Lưu nháp</span>
                </button>
                <button type="button" class="btn btn-dark btn-sm d-inline-flex align-items-center gap-1.5 px-3 py-2 shadow-sm"
                        data-bs-toggle="modal" data-bs-target="#submitGradeModal">
                    <i class="bi bi-send-check"></i>
                    <span class="fw-semibold">Nộp bảng điểm về PĐT</span>
                </button>
            @else
                <div class="badge bg-light text-dark border px-3 py-2 font-monospace">
                    <i class="bi bi-lock-fill text-secondary me-1"></i>BẢNG ĐIỂM ĐÃ KHÓA
                </div>
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

    @if (session('error'))
        <div class="alert alert-light border border-danger-subtle shadow-sm rounded-3 d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
            <div class="text-dark small">{{ session('error') }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($isLocked)
        <div class="alert alert-light border border-primary-subtle rounded-3 p-3 mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-info-circle-fill text-primary fs-5"></i>
                <div class="small">
                    <strong>Bảng điểm đang ở chế độ CHỈ XEM:</strong> 
                    @if ($isApproved)
                        Đã được Phòng Đào tạo phê duyệt vào ngày {{ $section->grade_approved_at ? $section->grade_approved_at->format('d/m/Y H:i') : '' }}.
                    @else
                        Đã nộp về Phòng Đào tạo vào ngày {{ $section->grade_submitted_at ? $section->grade_submitted_at->format('d/m/Y H:i') : '' }} và đang chờ duyệt.
                    @endif
                    Nếu cần điều chỉnh, Thầy/Cô vui lòng liên hệ Ban Quản trị để mở khóa.
                </div>
            </div>
            <span class="badge bg-light text-dark border">Chỉ đọc</span>
        </div>
    @endif

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

    <!-- Card Bảng điểm -->
    <div class="card border shadow-sm rounded-3 bg-white mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <strong class="text-dark fs-6">Danh sách Sinh viên Đăng ký ({{ $section->enrollments->count() }} sinh viên)</strong>
                <span class="text-secondary small ms-2">Công thức: QT (20%) + GK (20%) + CK (60%)</span>
            </div>
            <div class="small text-secondary">
                Giảng viên: <strong class="text-dark">{{ $section->lecturer?->full_name ?? 'Chưa phân công' }}</strong>
            </div>
        </div>

        <form method="POST" action="{{ route('lecturer.grades.update', $section) }}" id="gradeForm">
            @csrf
            @method('PUT')

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-center">
                    <thead class="table-light text-secondary small">
                        <tr>
                            <th class="ps-3 text-center" style="width: 50px;">#</th>
                            <th style="width: 130px;" class="text-start">MSSV</th>
                            <th class="text-start">Họ và tên</th>
                            <th style="width: 110px;">Lớp</th>
                            <th style="width: 120px;">QT (20%)</th>
                            <th style="width: 120px;">GK (20%)</th>
                            <th style="width: 120px;">CK (60%)</th>
                            <th class="bg-light" style="width: 110px;">Tổng (10)</th>
                            <th style="width: 90px;">Thang 4</th>
                            <th style="width: 80px;">Chữ</th>
                            <th class="pe-3" style="width: 110px;">Kết quả</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($section->enrollments as $index => $enr)
                            @php
                                $stu = $enr->student;
                            @endphp
                            <tr id="row-{{ $index }}">
                                <td class="ps-3 text-center text-muted small">{{ $index + 1 }}</td>
                                <td class="text-start">
                                    <span class="badge bg-light text-dark border font-monospace">
                                        {{ $stu?->student_code }}
                                    </span>
                                    <input type="hidden" name="grades[{{ $index }}][enrollment_id]" value="{{ $enr->id }}">
                                </td>
                                <td class="text-start">
                                    <div class="fw-semibold text-dark">{{ $stu?->full_name }}</div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-secondary border small">{{ $stu?->class_code ?? 'Lớp' }}</span>
                                </td>

                                <!-- Điểm Quá trình (20%) -->
                                <td>
                                    <input type="number" step="0.1" min="0" max="10"
                                           class="form-control form-control-sm text-center fw-bold score-input"
                                           name="grades[{{ $index }}][process_score]"
                                           id="qt-{{ $index }}"
                                           data-row="{{ $index }}"
                                           value="{{ $enr->process_score !== null ? $enr->process_score : '' }}"
                                           placeholder="0.0"
                                           {{ $isLocked ? 'disabled' : '' }}>
                                </td>

                                <!-- Điểm Giữa kỳ (20%) -->
                                <td>
                                    <input type="number" step="0.1" min="0" max="10"
                                           class="form-control form-control-sm text-center fw-bold score-input"
                                           name="grades[{{ $index }}][midterm_score]"
                                           id="gk-{{ $index }}"
                                           data-row="{{ $index }}"
                                           value="{{ $enr->midterm_score !== null ? $enr->midterm_score : '' }}"
                                           placeholder="0.0"
                                           {{ $isLocked ? 'disabled' : '' }}>
                                </td>

                                <!-- Điểm Cuối kỳ (60%) -->
                                <td>
                                    <input type="number" step="0.1" min="0" max="10"
                                           class="form-control form-control-sm text-center fw-bold score-input"
                                           name="grades[{{ $index }}][final_score]"
                                           id="ck-{{ $index }}"
                                           data-row="{{ $index }}"
                                           value="{{ $enr->final_score !== null ? $enr->final_score : '' }}"
                                           placeholder="0.0"
                                           {{ $isLocked ? 'disabled' : '' }}>
                                </td>

                                <!-- Tổng kết thang 10 -->
                                <td class="bg-light fw-bold text-dark" id="tot10-{{ $index }}">
                                    {{ $enr->total_score_10 !== null ? number_format($enr->total_score_10, 1) : '-' }}
                                </td>

                                <!-- Thang 4 -->
                                <td class="small fw-semibold text-dark" id="tot4-{{ $index }}">
                                    {{ $enr->total_score_4 !== null ? number_format($enr->total_score_4, 1) : '-' }}
                                </td>

                                <!-- Điểm Chữ -->
                                <td id="let-{{ $index }}">
                                    @if ($enr->letter_grade)
                                        <span class="badge {{ $enr->letter_grade === 'F' ? 'bg-light text-danger border border-danger-subtle' : 'bg-light text-dark border font-monospace' }}">
                                            {{ $enr->letter_grade }}
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>

                                <!-- Trạng thái Đạt / Không đạt -->
                                <td class="pe-3" id="res-{{ $index }}">
                                    @if ($enr->total_score_10 !== null)
                                        @if ($enr->total_score_10 >= 4.0)
                                            <span class="badge bg-light text-success border border-success-subtle">Đạt</span>
                                        @else
                                            <span class="badge bg-light text-danger border border-danger-subtle">Không đạt</span>
                                        @endif
                                    @else
                                        <span class="text-muted small">Chưa tổng kết</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center py-4 text-secondary small">
                                    Chưa có sinh viên đăng ký lớp học phần này.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </form>
    </div>
</div>

<!-- Modal Xác nhận nộp bảng điểm -->
@if (!$isLocked)
    <div class="modal fade" id="submitGradeModal" tabindex="-1" aria-labelledby="submitGradeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <form method="POST" action="{{ route('lecturer.grades.submit', $section) }}">
                    @csrf
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-bold text-dark" id="submitGradeModalLabel">
                            <i class="bi bi-send-check text-primary me-1"></i>Xác nhận nộp bảng điểm
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="alert alert-light border border-warning-subtle rounded-3 small text-dark mb-3">
                            <i class="bi bi-exclamation-triangle-fill text-warning me-1"></i>
                            <strong>Lưu ý quan trọng:</strong> Sau khi nộp về Phòng Đào tạo, bảng điểm sẽ được <strong>khóa lại</strong> và chuyển sang trạng thái chờ duyệt. Thầy/Cô sẽ không thể tự ý sửa đổi điểm trừ khi Quản trị viên mở khóa.
                        </div>
                        <p class="small text-secondary mb-3">
                            Lớp học phần: <strong>{{ $section->section_code }} — {{ $section->course?->course_name }}</strong>
                            <br>
                            Số lượng sinh viên: <strong>{{ $section->enrollments->count() }} sinh viên</strong>
                        </p>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark">Ghi chú gửi Phòng Đào tạo (nếu có):</label>
                            <textarea name="note" class="form-control form-control-sm" rows="3" placeholder="Ví dụ: Đã hoàn tất chấm điểm giữa kỳ và thi kết thúc môn..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-top bg-light">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Hủy bỏ</button>
                        <button type="submit" class="btn btn-sm btn-dark px-3">
                            <i class="bi bi-check-lg me-1"></i>Xác nhận nộp & Khóa bảng điểm
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

<script>
document.addEventListener('DOMContentLoaded', function () {
    @if (!$isLocked)
        const inputs = document.querySelectorAll('.score-input');
        inputs.forEach(input => {
            input.addEventListener('input', function () {
                const row = this.getAttribute('data-row');
                calculateRow(row);
            });
        });

        function calculateRow(row) {
            const qtInput = document.getElementById(`qt-${row}`);
            const gkInput = document.getElementById(`gk-${row}`);
            const ckInput = document.getElementById(`ck-${row}`);

            const qt = parseFloat(qtInput ? qtInput.value : 0);
            const gk = parseFloat(gkInput ? gkInput.value : 0);
            const ck = parseFloat(ckInput ? ckInput.value : 0);

            if (!isNaN(qt) && !isNaN(gk) && !isNaN(ck) && ckInput.value !== '') {
                const tot10 = Math.round((qt * 0.2 + gk * 0.2 + ck * 0.6) * 10) / 10;
                document.getElementById(`tot10-${row}`).textContent = tot10.toFixed(1);

                let tot4 = 0, letter = 'F', isPass = false;
                if (tot10 >= 8.5) { tot4 = 4.0; letter = 'A'; isPass = true; }
                else if (tot10 >= 8.0) { tot4 = 3.5; letter = 'B+'; isPass = true; }
                else if (tot10 >= 7.0) { tot4 = 3.0; letter = 'B'; isPass = true; }
                else if (tot10 >= 6.5) { tot4 = 2.5; letter = 'C+'; isPass = true; }
                else if (tot10 >= 5.5) { tot4 = 2.0; letter = 'C'; isPass = true; }
                else if (tot10 >= 5.0) { tot4 = 1.5; letter = 'D+'; isPass = true; }
                else if (tot10 >= 4.0) { tot4 = 1.0; letter = 'D'; isPass = true; }

                document.getElementById(`tot4-${row}`).textContent = tot4.toFixed(1);
                document.getElementById(`let-${row}`).innerHTML = `<span class="badge ${letter === 'F' ? 'bg-light text-danger border border-danger-subtle' : 'bg-light text-dark border font-monospace'}">${letter}</span>`;
                document.getElementById(`res-${row}`).innerHTML = isPass 
                    ? `<span class="badge bg-light text-success border border-success-subtle">Đạt</span>`
                    : `<span class="badge bg-light text-danger border border-danger-subtle">Không đạt</span>`;
            }
        }
    @endif
});
</script>
@endsection
