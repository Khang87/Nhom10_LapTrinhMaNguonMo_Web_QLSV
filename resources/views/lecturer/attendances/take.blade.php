@extends('layouts.spark')

@section('title', 'Điểm danh: ' . $section->course?->course_name . ' (' . $section->section_code . ')')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-primary px-3 py-1 fs-6 font-monospace">{{ $section->section_code }}</span>
                <h1 class="h3 fw-bold text-dark mb-0">{{ $section->course?->course_name }}</h1>
            </div>
            <p class="text-secondary mb-0 small">
                Giảng viên: <strong>{{ $section->lecturer?->full_name }}</strong> |
                Phòng: <strong>{{ $section->room_name }}</strong> |
                Sĩ số: <strong>{{ $enrollments->count() }} sinh viên</strong> |
                Học kỳ: <strong>{{ $section->semester_code }}</strong>
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('lecturer.attendances.index') }}" class="btn btn-outline-secondary d-inline-flex align-items-center gap-1">
                <i class="bi bi-arrow-left"></i> DS Lớp học phần
            </a>
            <a href="{{ route('lecturer.schedule.index') }}" class="btn btn-light border d-inline-flex align-items-center gap-1">
                <i class="bi bi-calendar-week"></i> TKB Tuần
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="bi bi-check-circle-fill fs-5"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- KHUNG CHỌN NGÀY VÀ CÔNG CỤ NHANH --}}
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <form method="GET" action="{{ route('lecturer.attendances.take', $section) }}" class="d-flex align-items-center gap-2">
                    <label class="form-label small fw-bold text-secondary mb-0 text-nowrap">
                        <i class="bi bi-calendar-event text-primary me-1"></i>Ngày điểm danh:
                    </label>
                    <input type="date" name="date" class="form-control form-control-sm border-primary fw-bold" 
                           value="{{ $attendanceDate }}" onchange="this.form.submit()">
                    <button type="submit" class="btn btn-sm btn-outline-primary">Xem ngày này</button>
                </form>

                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-outline-success d-inline-flex align-items-center gap-1" onclick="setAllToggles(true)">
                        <i class="bi bi-check-all"></i> Bật tất cả ON (Có mặt)
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1" onclick="setAllToggles(false)">
                        <i class="bi bi-x"></i> Tắt tất cả OFF (Vắng)
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- FORM ĐIỂM DANH SINH VIÊN VỚI NÚT TOGGLE ON/OFF --}}
    <form method="POST" action="{{ route('lecturer.attendances.store', $section) }}">
        @csrf
        <input type="hidden" name="attendance_date" value="{{ $attendanceDate }}">

        <div class="card border-0 shadow-sm rounded-3 overflow-hidden mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                    <i class="bi bi-people-fill text-primary"></i>
                    Danh sách sinh viên điểm danh ngày [{{ \Carbon\Carbon::parse($attendanceDate)->format('d/m/Y') }}]
                </h5>
                <span class="badge bg-primary rounded-pill px-3 py-2">
                    Tổng: {{ $enrollments->count() }} sinh viên
                </span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th class="ps-4" style="width: 70px;">STT</th>
                            <th style="width: 130px;">MSSV</th>
                            <th>Họ và tên sinh viên</th>
                            <th>Chuyên ngành</th>
                            <th>Lớp</th>
                            <th class="text-center" style="width: 180px;">Điểm danh (ON / OFF)</th>
                            <th style="min-width: 200px;">Ghi chú</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($enrollments as $index => $enr)
                            @php
                                $stu = $enr->student;
                                $saved = $existingAttendances->get($stu?->id);
                                // Mặc định là ON (present) nếu chưa từng điểm danh
                                $isPresent = $saved ? ($saved->status === 'present') : true;
                            @endphp
                            <tr>
                                <td class="ps-4 text-secondary">{{ $index + 1 }}</td>
                                <td>
                                    <span class="font-monospace fw-bold text-primary">{{ $stu?->student_code }}</span>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $stu?->full_name }}</div>
                                    <div class="text-secondary small">{{ $stu?->email }}</div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        {{ $stu?->specialization?->code ?? $stu?->specialization_name }}
                                    </span>
                                </td>
                                <td>
                                    <span class="small">{{ $stu?->class_code }}</span>
                                </td>
                                <td class="text-center">
                                    {{-- Custom Switch Toggle ON / OFF --}}
                                    <div class="d-inline-flex align-items-center justify-content-center gap-2">
                                        <div class="form-check form-switch fs-4 mb-0">
                                            <input class="form-check-input attendance-switch" type="checkbox" role="switch"
                                                   id="switch_{{ $stu?->id }}" 
                                                   name="statuses[{{ $stu?->id }}]" 
                                                   value="present"
                                                   {{ $isPresent ? 'checked' : '' }}
                                                   onchange="updateSwitchLabel({{ $stu?->id }})">
                                        </div>
                                        <span class="badge {{ $isPresent ? 'bg-success' : 'bg-danger' }} px-3 py-2 fw-bold" id="badge_{{ $stu?->id }}" style="min-width: 80px;">
                                            {{ $isPresent ? 'ON (Có mặt)' : 'OFF (Vắng)' }}
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <input type="text" name="notes[{{ $stu?->id }}]" class="form-control form-control-sm" 
                                           placeholder="Ghi chú (đi trễ, phép...)" value="{{ $saved?->note }}">
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    Chưa có sinh viên nào đăng ký vào lớp học phần này.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($enrollments->isNotEmpty())
                <div class="card-footer bg-white py-3 border-top d-flex align-items-center justify-content-between">
                    <span class="text-secondary small">
                        Dữ liệu sẽ được lưu vào hệ thống và quản trị viên (Admin) có thể xem báo cáo điểm danh ngay lập tức.
                    </span>
                    <button type="submit" class="btn btn-primary px-5 py-2 fw-bold shadow d-inline-flex align-items-center gap-2">
                        <i class="bi bi-save2-fill"></i>
                        <span>Lưu kết quả điểm danh</span>
                    </button>
                </div>
            @endif
        </div>
    </form>
</div>

<script>
function updateSwitchLabel(studentId) {
    const sw = document.getElementById('switch_' + studentId);
    const badge = document.getElementById('badge_' + studentId);
    if (sw.checked) {
        badge.className = 'badge bg-success px-3 py-2 fw-bold';
        badge.innerText = 'ON (Có mặt)';
    } else {
        badge.className = 'badge bg-danger px-3 py-2 fw-bold';
        badge.innerText = 'OFF (Vắng)';
    }
}

function setAllToggles(state) {
    const switches = document.querySelectorAll('.attendance-switch');
    switches.forEach(sw => {
        sw.checked = state;
        const studentId = sw.id.replace('switch_', '');
        updateSwitchLabel(studentId);
    });
}
</script>
@endsection
