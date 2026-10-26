@extends('layouts.spark')
@section('title', 'Hồ sơ sinh viên')
@section('content')
    @php
        $totalSessions = $student->attendances->count();
        $presentRate = $totalSessions > 0 ? round($presentCount / $totalSessions * 100, 1) : null;
    @endphp
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="h3 mb-0">Hồ sơ sinh viên</h1>
        <div class="d-flex gap-2">
            <a class="btn btn-primary" href="{{ route('admin.students.edit', $student) }}">
                <i class="bi bi-pencil me-1" aria-hidden="true"></i>Sửa
            </a>
            <a class="btn btn-outline-secondary" href="{{ route('admin.students.index') }}">Quay lại</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex flex-wrap gap-4">
                <img class="rounded-circle border" width="96" height="96" alt="Ảnh đại diện của {{ $student->name }}"
                    src="{{ e($student->avatarUrl()) }}">
                <dl class="row mb-0 flex-grow-1">
                    <dt class="col-sm-3">Họ và tên</dt>
                    <dd class="col-sm-9">{{ $student->name }}</dd>
                    <dt class="col-sm-3">Mã sinh viên</dt>
                    <dd class="col-sm-9">{{ $student->student_code ?: 'Chưa cập nhật' }}</dd>
                    <dt class="col-sm-3">Email</dt>
                    <dd class="col-sm-9">{{ $student->email }}</dd>
                    <dt class="col-sm-3">Số điện thoại</dt>
                    <dd class="col-sm-9">{{ $student->phone ?: 'Chưa cập nhật' }}</dd>
                    <dt class="col-sm-3">Ngày sinh</dt>
                    <dd class="col-sm-9">{{ $student->dob?->format('d/m/Y') ?: 'Chưa cập nhật' }}</dd>
                    <dt class="col-sm-3">Giới tính</dt>
                    <dd class="col-sm-9">{{ $student->gender ? $student->gender_label.' ('.$student->gender.')' : 'Chưa cập nhật' }}</dd>
                    <dt class="col-sm-3">Quê quán</dt>
                    <dd class="col-sm-9">{{ $student->hometown ?: 'Chưa cập nhật' }}</dd>
                    <dt class="col-sm-3">Lớp hành chính</dt>
                    <dd class="col-sm-9">{{ $student->studentClass?->label() ?? 'Chưa xếp lớp' }}</dd>
                    <dt class="col-sm-3">Khóa tuyển sinh</dt>
                    <dd class="col-sm-9">{{ $student->studentClass?->intake_year ?? '—' }}</dd>
                    <dt class="col-sm-3">Khoa</dt>
                    <dd class="col-sm-9">
                        {{ $student->studentClass?->department?->name ?? 'Chưa phân loại' }}
                        @if ($student->studentClass?->department)
                            ({{ $student->studentClass->department->code }})
                        @endif
                    </dd>
                    <dt class="col-sm-3">Chuyên ngành</dt>
                    <dd class="col-sm-9">
                        {{ $student->studentClass?->major?->name ?? 'Chưa phân loại' }}
                        @if ($student->studentClass?->major)
                            ({{ $student->studentClass->major->code }})
                        @endif
                    </dd>
                    <dt class="col-sm-3">Tình trạng học tập</dt>
                    <dd class="col-sm-9">{{ $student->academic_status_label }} ({{ $student->academic_status }})</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">Lớp học phần đã đăng ký</div>
                <div class="card-body p-0">
                    <table class="table table-bordered mb-0 align-middle">
                        <thead class="table-light"><tr><th>Môn học</th><th>Nhóm</th><th>Học kỳ</th><th>Tín chỉ</th></tr></thead>
                        <tbody>
                            @forelse ($student->offerings
                                ->filter(fn ($offering) => $offering->pivot->status === 'ACTIVE')
                                ->sortBy(fn ($offering) => [$offering->subject?->code, $offering->group_code]) as $offering)
                                <tr>
                                    <td>{{ $offering->subject?->label() }}</td>
                                    <td>{{ $offering->group_code }}</td>
                                    <td>{{ $offering->semester?->slug ?? '—' }}</td>
                                    <td>{{ $offering->subject?->credits }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center">Chưa đăng ký học phần nào.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">Kết quả học tập</div>
                <div class="card-body p-0">
                    <table class="table table-bordered mb-0 align-middle">
                        <thead class="table-light"><tr><th>Môn học</th><th>Nhóm</th><th>Điểm</th></tr></thead>
                        <tbody>
                            @forelse ($student->grades->sortBy(fn ($grade) => $grade->subject->code) as $grade)
                                <tr>
                                    <td>{{ $grade->subject->code }} - {{ $grade->subject->name }}</td>
                                    <td>{{ $grade->offering?->group_code }}</td>
                                    <td>{{ number_format((float) $grade->final_score, 1) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center">Chưa có điểm.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="card">
                <div class="card-header">Tỷ lệ chuyên cần</div>
                <div class="card-body">
                    @if ($presentRate === null)
                        <p class="mb-0 text-muted">Chưa có dữ liệu điểm danh.</p>
                    @else
                        <p class="mb-2">
                            Có mặt {{ $presentCount }}/{{ $totalSessions }} buổi =
                            <strong>{{ $presentRate }}%</strong>.
                        </p>
                        <div class="progress" role="progressbar" aria-label="Tỷ lệ chuyên cần"
                            aria-valuenow="{{ $presentRate }}" aria-valuemin="0" aria-valuemax="100" style="height:12px">
                            <div class="progress-bar {{ $presentRate >= 80 ? 'bg-success' : 'bg-warning' }}"
                                style="width: {{ $presentRate }}%"></div>
                        </div>
                        <div class="form-text">Tỷ lệ = số buổi có mặt / tổng số buổi ghi nhận.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
