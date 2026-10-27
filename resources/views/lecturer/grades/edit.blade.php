@extends('layouts.spark')

@section('title', 'Bảng Điểm Lớp Học Phần - ' . ($section->course?->course_name ?? 'Lớp HP'))

@section('content')
<div class="container-fluid px-0">
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
            </div>
            <h1 class="h3 fw-bold text-dark mb-1">
                {{ $section->course?->course_name }} ({{ $section->course?->course_code }})
            </h1>
            <p class="text-secondary small mb-0">
                Nhập và chỉnh sửa điểm cho sinh viên theo thang điểm 10. Điểm lưu sẽ được tự động đồng bộ sang hồ sơ sinh viên và phân hệ Quản trị viên.
            </p>
        </div>

        <div class="d-flex align-items-center gap-2">
            <button type="submit" form="gradeForm" class="btn btn-dark btn-sm d-inline-flex align-items-center gap-1.5 px-3 py-2 shadow-sm">
                <i class="bi bi-check-lg"></i>
                <span class="fw-semibold">Lưu bảng điểm</span>
            </button>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-light border shadow-sm rounded-3 d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="bi bi-check-circle-fill text-success fs-5"></i>
            <div class="text-dark small">{{ session('success') }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

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
                                           placeholder="0.0">
                                </td>

                                <!-- Điểm Giữa kỳ (20%) -->
                                <td>
                                    <input type="number" step="0.1" min="0" max="10"
                                           class="form-control form-control-sm text-center fw-bold score-input"
                                           name="grades[{{ $index }}][midterm_score]"
                                           id="gk-{{ $index }}"
                                           data-row="{{ $index }}"
                                           value="{{ $enr->midterm_score !== null ? $enr->midterm_score : '' }}"
                                           placeholder="0.0">
                                </td>

                                <!-- Điểm Cuối kỳ (60%) -->
                                <td>
                                    <input type="number" step="0.1" min="0" max="10"
                                           class="form-control form-control-sm text-center fw-bold score-input"
                                           name="grades[{{ $index }}][final_score]"
                                           id="ck-{{ $index }}"
                                           data-row="{{ $index }}"
                                           value="{{ $enr->final_score !== null ? $enr->final_score : '' }}"
                                           placeholder="0.0">
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

                                <!-- Kết quả Đạt / Rớt -->
                                <td class="pe-3" id="status-{{ $index }}">
                                    @if ($enr->total_score_10 !== null)
                                        @if ($enr->isPassed())
                                            <span class="badge bg-light text-success border border-success-subtle small">✓ Đạt</span>
                                        @else
                                            <span class="badge bg-light text-danger border border-danger-subtle small">✗ Rớt</span>
                                        @endif
                                    @else
                                        <span class="badge bg-light text-secondary border small">Chưa đủ điểm</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center py-5 text-muted small">
                                    <i class="bi bi-people fs-2 d-block mb-2 text-secondary opacity-50"></i>
                                    Chưa có sinh viên nào đăng ký lớp học phần này.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="card-footer bg-white py-3 border-top d-flex align-items-center justify-content-between">
                <span class="text-secondary small">
                    <i class="bi bi-info-circle me-1"></i>Hệ thống tự động tính điểm theo quy chế học vụ HUIT. Nhớ nhấn "Lưu bảng điểm" sau khi hoàn tất nhập liệu.
                </span>
                <button type="submit" class="btn btn-dark btn-sm d-inline-flex align-items-center gap-1.5 px-4 py-2 shadow-sm">
                    <i class="bi bi-check-lg"></i>
                    <span class="fw-semibold">Lưu bảng điểm</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('.score-input');
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            const rowIndex = this.dataset.row;
            updateRowCalculation(rowIndex);
        });
    });

    function updateRowCalculation(idx) {
        const qtVal = parseFloat(document.getElementById('qt-' + idx).value);
        const gkVal = parseFloat(document.getElementById('gk-' + idx).value);
        const ckVal = parseFloat(document.getElementById('ck-' + idx).value);

        const tot10El = document.getElementById('tot10-' + idx);
        const tot4El = document.getElementById('tot4-' + idx);
        const letEl = document.getElementById('let-' + idx);
        const statusEl = document.getElementById('status-' + idx);

        if (isNaN(qtVal) || isNaN(gkVal) || isNaN(ckVal)) {
            tot10El.innerText = '-';
            tot4El.innerText = '-';
            letEl.innerHTML = '<span class="text-muted">-</span>';
            statusEl.innerHTML = '<span class="badge bg-light text-secondary border small">Chưa đủ điểm</span>';
            return;
        }

        const total10 = Math.round((qtVal * 0.2 + gkVal * 0.2 + ckVal * 0.6) * 10) / 10;
        let total4 = 0.0;
        let letter = 'F';
        let isPassed = false;

        if (total10 >= 8.5) {
            total4 = 4.0; letter = 'A'; isPassed = true;
        } else if (total10 >= 7.0) {
            total4 = 3.0; letter = 'B'; isPassed = true;
        } else if (total10 >= 5.5) {
            total4 = 2.0; letter = 'C'; isPassed = true;
        } else if (total10 >= 4.0) {
            total4 = 1.0; letter = 'D'; isPassed = true;
        } else {
            total4 = 0.0; letter = 'F'; isPassed = false;
        }

        tot10El.innerText = total10.toFixed(1);
        tot4El.innerText = total4.toFixed(1);

        if (letter === 'F') {
            letEl.innerHTML = '<span class="badge bg-light text-danger border border-danger-subtle font-monospace">F</span>';
            statusEl.innerHTML = '<span class="badge bg-light text-danger border border-danger-subtle small">✗ Rớt</span>';
        } else {
            letEl.innerHTML = '<span class="badge bg-light text-dark border font-monospace">' + letter + '</span>';
            statusEl.innerHTML = '<span class="badge bg-light text-success border border-success-subtle small">✓ Đạt</span>';
        }
    }
});
</script>
@endsection
