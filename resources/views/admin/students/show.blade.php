@extends('layouts.spark')
@section('title', 'Hồ sơ sinh viên')
@section('content')
    @php
        $totalSessions = $student->attendances->count();
        $attendanceRate = $review['attendance_rate'];
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
                    <dd class="col-sm-9">
                        {{ $student->academic_status_label }} ({{ $student->academic_status }})
                        @if ($student->academic_status_updated_at)
                            <span class="text-muted small">· xét duyệt ngày
                                {{ $student->academic_status_updated_at->format('d/m/Y H:i') }}</span>
                        @endif
                    </dd>
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
                <div class="card-header">Tỷ lệ chuyên cần</div>
                <div class="card-body">
                    @if ($attendanceRate === null)
                        <p class="mb-0 text-muted">Chưa có dữ liệu điểm danh.</p>
                    @else
                        <p class="mb-2">
                            Có mặt {{ $presentCount }}/{{ $totalSessions }} buổi =
                            <strong>{{ $attendanceRate }}%</strong>.
                        </p>
                        <div class="progress" role="progressbar" aria-label="Tỷ lệ chuyên cần"
                            aria-valuenow="{{ $attendanceRate }}" aria-valuemin="0" aria-valuemax="100" style="height:12px">
                            <div class="progress-bar {{ $attendanceRate >= 80 ? 'bg-success' : 'bg-warning' }}"
                                style="width: {{ $attendanceRate }}%"></div>
                        </div>
                        <div class="form-text">Tỷ lệ = số buổi có mặt / tổng số buổi ghi nhận.</div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header">Xét duyệt học vụ</div>
                <div class="card-body">
                    <p class="mb-2">
                        GPA hệ 4 = {{ $review['gpa'] === null ? '—' : number_format($review['gpa'], 2) }},
                        hệ 10 = {{ $review['average_ten'] === null ? '—' : number_format($review['average_ten'], 2) }},
                        tín chỉ tích lũy = {{ $review['credits_accumulated'] }}/{{ $review['credits_required'] ?? '—' }} TC,
                        môn F = {{ $review['failing_subjects'] }}.
                    </p>
                    <p class="mb-2">
                        <span class="badge text-bg-info">
                            Gợi ý: {{ App\Support\PersonLabels::option($review['suggestion']['status'], App\Support\PersonLabels::ACADEMIC_STATUSES) }}
                        </span>
                    </p>
                    <ul class="mb-3">
                        @foreach ($review['suggestion']['reasons'] as $reason)
                            <li>{{ $reason }}</li>
                        @endforeach
                    </ul>
                    <form class="row g-2 align-items-end" method="POST"
                        action="{{ route('admin.students.academic-status.update', $student) }}">
                        @csrf
                        <div class="col-sm-6 col-md-4">
                            <label class="form-label" for="academic_status">Tình trạng sau xét duyệt</label>
                            <select class="form-select" id="academic_status" name="academic_status" required>
                                @foreach (App\Support\PersonLabels::ACADEMIC_STATUSES as $code => $label)
                                    <option value="{{ $code }}"
                                        @selected(old('academic_status', $student->academic_status) === $code)>
                                        {{ App\Support\PersonLabels::option($code, App\Support\PersonLabels::ACADEMIC_STATUSES) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-auto">
                            <button class="btn btn-primary" type="submit">
                                <i class="bi bi-check-lg me-1" aria-hidden="true"></i>Xét duyệt
                            </button>
                        </div>
                        @error('academic_status')
                            <div class="col-12"><div class="text-danger small">{{ $message }}</div></div>
                        @enderror
                    </form>
                    <div class="form-text mt-2">
                        Hệ thống chỉ gợi ý theo bảng điểm và điểm danh; quyết định lên lớp hay tốt nghiệp thuộc hội
                        đồng đào tạo, nên nút này ghi đúng tình trạng mà quản trị viên chọn.
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header">Kết quả học tập</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Môn học</th>
                                    <th>Nhóm</th>
                                    @foreach (App\Support\GradingScale::components() as $component)
                                        <th>{{ $component['label'] }} ({{ $component['weight'] }}%)</th>
                                    @endforeach
                                    <th>Tổng kết</th>
                                    <th>Chữ</th>
                                    <th>Hệ 4</th>
                                    <th>Trạng thái lớp</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($student->grades->sortBy(fn ($grade) => $grade->subject?->code) as $grade)
                                    <tr>
                                        <td>{{ $grade->subject?->code }} - {{ $grade->subject?->name }}</td>
                                        <td>{{ $grade->offering?->group_code }}</td>
                                        @foreach (App\Support\GradingScale::components() as $component)
                                            <td>{{ $grade->{$component['key']} !== null ? number_format((float) $grade->{$component['key']}, 2) : '—' }}</td>
                                        @endforeach
                                        <td>{{ $grade->subjectScore() === null ? '—' : number_format($grade->subjectScore(), 2) }}</td>
                                        <td>{{ $grade->subjectScore() === null ? '—' : $grade->letter() }}</td>
                                        <td>{{ $grade->subjectScore() === null ? '—' : number_format($grade->fourScore(), 2) }}</td>
                                        <td>
                                            {{ $grade->is_published ? 'Đã công bố' : 'Chưa công bố' }}
                                            @if ($grade->enrollment)
                                                · {{ $grade->enrollment->statusLabel() }}
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="10" class="text-center">Chưa có điểm.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
