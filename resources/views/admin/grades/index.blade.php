@extends('layouts.spark')

@section('title', 'Quản lý điểm')

@section('content')
    @php
        $letterClass = fn (string $letter): string => match (true) {
            $letter === 'A' => 'text-bg-success',
            $letter === 'B' => 'text-bg-info',
            $letter === 'C' => 'text-bg-warning',
            $letter === 'D' => 'text-bg-secondary',
            default => 'text-bg-danger',
        };
    @endphp

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h4 fw-bold mb-1">Quản lý điểm</h1>
            <p class="text-secondary small mb-0">
                Ba cột điểm thành phần quyết định điểm tổng kết; điểm do quản trị viên cập nhật được công bố ngay.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a class="btn btn-outline-dark btn-sm d-inline-flex align-items-center gap-2"
                href="{{ route('admin.grades.export', request()->query()) }}">
                <i class="bi bi-download" aria-hidden="true"></i>
                <span>Tải CSV</span>
            </a>
        </div>
    </div>

    <div class="card border shadow-sm rounded-3 bg-white mb-4">
        <div class="card-body p-3">
            <form class="row g-2 align-items-end" method="GET" action="{{ route('admin.grades.index') }}">
                <div class="col-sm-6 col-md-3">
                    <label class="form-label small text-secondary" for="filter_semester_id">Học kỳ</label>
                    <select class="form-select form-select-sm" id="filter_semester_id" name="semester_id">
                        <option value="">Mọi học kỳ</option>
                        @foreach ($semesters as $semester)
                            <option value="{{ $semester->id }}" @selected(request('semester_id') == $semester->id)>
                                {{ $semester->label }}{{ $semester->is_active ? ' (hiện hành)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-md-3">
                    <label class="form-label small text-secondary" for="filter_department_id">Khoa</label>
                    <select class="form-select form-select-sm" id="filter_department_id" name="department_id">
                        <option value="">Mọi khoa</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}" @selected(request('department_id') == $department->id)>
                                {{ $department->name }} ({{ $department->code }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-md-3">
                    <label class="form-label small text-secondary" for="filter_user_id">Sinh viên</label>
                    <select class="form-select form-select-sm" id="filter_user_id" name="user_id">
                        <option value="">Mọi sinh viên</option>
                        @foreach ($students as $student)
                            <option value="{{ $student->id }}" @selected(request('user_id') == $student->id)>
                                {{ $student->name }} ({{ $student->person_code }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto d-flex gap-2">
                    <button class="btn btn-dark btn-sm px-3 d-inline-flex align-items-center gap-2" type="submit">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <span>Tìm kiếm</span>
                    </button>
                    @if (request()->hasAny(['semester_id', 'department_id', 'user_id']))
                        <a class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-2"
                            href="{{ route('admin.grades.index') }}">
                            <i class="bi bi-arrow-clockwise" aria-hidden="true"></i>
                            <span>Đặt lại</span>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    @if ($selectedStudent)
        <div class="card border shadow-sm rounded-3 bg-white mb-4">
            <div class="card-body p-3 p-md-4">
                <div class="row align-items-center g-4">
                    <div class="col-lg-5">
                        <div class="d-flex align-items-start gap-3">
                            <img src="{{ e($selectedStudent->avatarUrl()) }}" alt=""
                                class="rounded-circle border bg-light flex-shrink-0" width="58" height="58">
                            <div>
                                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                    <h2 class="h5 fw-bold mb-0">{{ $selectedStudent->name }}</h2>
                                    <span class="badge bg-light text-dark border font-monospace">{{ $selectedStudent->person_code }}</span>
                                </div>
                                <div class="small text-secondary d-flex flex-wrap align-items-center gap-2">
                                    <span><i class="bi bi-building me-1"></i>Lớp {{ $selectedStudent->studentClass?->label() ?? 'chưa gán' }}</span>
                                    <span>•</span>
                                    <span>{{ $selectedStudent->studentClass?->major?->name ?? 'Chưa có chuyên ngành' }}</span>
                                </div>
                                <div class="small text-secondary mt-1">
                                    <i class="bi bi-mortarboard me-1"></i>{{ $selectedStudent->studentClass?->major?->department?->name ?? 'Chưa gán khoa' }}
                                </div>
                                <a class="btn btn-outline-dark btn-sm mt-2 d-inline-flex align-items-center gap-2"
                                    href="{{ route('admin.students.show', $selectedStudent) }}">
                                    <i class="bi bi-person-badge" aria-hidden="true"></i>
                                    <span>Hồ sơ sinh viên</span>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-7">
                        <div class="row g-2 text-center">
                            <div class="col-6 col-sm-3">
                                <div class="p-2 rounded-3 bg-light border h-100">
                                    <div class="small text-secondary">GPA hệ 4</div>
                                    <div class="fs-4 fw-bold mt-1">{{ $review['gpa'] === null ? '—' : number_format($review['gpa'], 2) }}</div>
                                    <span class="badge bg-light text-dark border small mt-1">{{ $review['classification'] }}</span>
                                </div>
                            </div>
                            <div class="col-6 col-sm-3">
                                <div class="p-2 rounded-3 bg-light border h-100">
                                    <div class="small text-secondary">Điểm TB hệ 10</div>
                                    <div class="fs-4 fw-bold mt-1">{{ $review['average_ten'] === null ? '—' : number_format($review['average_ten'], 2) }}</div>
                                    <div class="small text-secondary mt-1">Thang điểm 10</div>
                                </div>
                            </div>
                            <div class="col-6 col-sm-3">
                                <div class="p-2 rounded-3 bg-light border h-100">
                                    <div class="small text-secondary">Tín chỉ tích lũy</div>
                                    <div class="fs-4 fw-bold mt-1">
                                        {{ $review['credits_accumulated'] }}
                                        <span class="fs-6 fw-normal text-secondary">/ {{ $review['credits_required'] ?? '—' }}</span>
                                    </div>
                                    @if ($review['credits_required'])
                                        <div class="progress mt-2" style="height: 4px;" role="progressbar"
                                            aria-label="Tiến độ khung chương trình" aria-valuenow="{{ min(100, (int) round($review['credits_accumulated'] / $review['credits_required'] * 100)) }}"
                                            aria-valuemin="0" aria-valuemax="100">
                                            <div class="progress-bar" style="width: {{ min(100, (int) round($review['credits_accumulated'] / $review['credits_required'] * 100)) }}%"></div>
                                        </div>
                                    @else
                                        <div class="small text-secondary mt-1">Chưa có khung CTĐT</div>
                                    @endif
                                </div>
                            </div>
                            <div class="col-6 col-sm-3">
                                <div class="p-2 rounded-3 bg-light border h-100">
                                    <div class="small text-secondary">Chuyên cần</div>
                                    <div class="fs-4 fw-bold mt-1">{{ $review['attendance_rate'] === null ? '—' : number_format($review['attendance_rate'], 1).'%' }}</div>
                                    @if ($review['failing_subjects'] > 0)
                                        <span class="badge bg-light text-danger border border-danger-subtle small mt-1">{{ $review['failing_subjects'] }} môn F</span>
                                    @else
                                        <span class="small text-secondary mt-1 d-block">Không nợ môn</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="small text-secondary mt-2">
                            <span class="badge text-bg-info">
                                Gợi ý: {{ App\Support\PersonLabels::option($review['suggestion']['status'], App\Support\PersonLabels::ACADEMIC_STATUSES) }}
                            </span>
                            {{ implode(' ', $review['suggestion']['reasons']) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="row g-2 mb-4">
            <div class="col-6 col-lg-3">
                <div class="card border shadow-sm rounded-3 h-100">
                    <div class="card-body p-3 text-center">
                        <div class="small text-secondary">GPA hệ 4</div>
                        <div class="fs-4 fw-bold mt-1">{{ $summary['gpa'] === null ? '—' : number_format($summary['gpa'], 2) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card border shadow-sm rounded-3 h-100">
                    <div class="card-body p-3 text-center">
                        <div class="small text-secondary">Điểm TB hệ 10</div>
                        <div class="fs-4 fw-bold mt-1">{{ $summary['average_ten'] === null ? '—' : number_format($summary['average_ten'], 2) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card border shadow-sm rounded-3 h-100">
                    <div class="card-body p-3 text-center">
                        <div class="small text-secondary">Tín chỉ tích lũy</div>
                        <div class="fs-4 fw-bold mt-1">{{ $summary['credits_accumulated'] }} <span class="fs-6 fw-normal text-secondary">TC</span></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card border shadow-sm rounded-3 h-100">
                    <div class="card-body p-3 text-center">
                        <div class="small text-secondary">Xếp loại</div>
                        <div class="fs-4 fw-bold mt-1">{{ $summary['classification'] }}</div>
                        <div class="small text-secondary mt-1">{{ $summary['failing_subjects'] }} môn F</div>
                    </div>
                </div>
            </div>
        </div>
        <p class="text-secondary small mb-4">
            Bốn thẻ trên tính trên các môn đã công bố nằm trong bộ lọc đang áp dụng.
        </p>
    @endif

    <div class="card border shadow-sm rounded-3 bg-white mb-4">
        <div class="card-header bg-white fw-semibold">Cập nhật điểm</div>
        <div class="card-body p-3">
            <form class="row g-3 align-items-end" method="POST" action="{{ route('admin.grades.store') }}">
                @csrf
                <div class="col-md-4">
                    <label class="form-label" for="user_id">Sinh viên</label>
                    <select class="form-select" id="user_id" name="user_id" required>
                        <option value="">Chọn sinh viên</option>
                        @foreach ($students as $student)
                            <option value="{{ $student->id }}" @selected(old('user_id') == $student->id)>
                                {{ $student->name }} ({{ $student->person_code }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="course_offering_id">Lớp học phần</label>
                    <select class="form-select" id="course_offering_id" name="course_offering_id" required>
                        <option value="">Chọn lớp học phần đã đăng ký</option>
                        @foreach ($offerings as $offering)
                            <option value="{{ $offering->id }}" @selected(old('course_offering_id') == $offering->id)>
                                {{ $offering->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @foreach ($components as $component)
                    <div class="col-md-2 col-lg-1">
                        <label class="form-label" for="{{ $component['key'] }}">
                            {{ $component['label'] }} ({{ $component['weight'] }}%)
                        </label>
                        <input class="form-control" id="{{ $component['key'] }}" name="{{ $component['key'] }}" type="number"
                            min="0" max="10" step="0.01" value="{{ old($component['key']) }}">
                    </div>
                @endforeach
                <div class="col-12">
                    @error('final_score')
                        <div class="text-danger small mb-2">{{ $message }}</div>
                    @enderror
                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-check-lg me-1" aria-hidden="true"></i>Cập nhật
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border shadow-sm rounded-3 bg-white">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">Sinh viên</th>
                        <th scope="col">Mã môn</th>
                        <th scope="col">Tên môn</th>
                        <th scope="col">Nhóm</th>
                        <th scope="col">Học kỳ</th>
                        @foreach ($components as $component)
                            <th scope="col">{{ $component['label'] }} ({{ $component['weight'] }}%)</th>
                        @endforeach
                        <th scope="col">Tổng kết</th>
                        <th scope="col">Chữ</th>
                        <th scope="col">Hệ 4</th>
                        <th scope="col">Trạng thái</th>
                        <th scope="col">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($grades as $grade)
                        @php $total = $grade->subjectScore(); @endphp
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img src="{{ e($grade->user->avatarUrl()) }}" alt=""
                                        class="rounded-circle border bg-light flex-shrink-0" width="30" height="30">
                                    <div>
                                        <div class="fw-semibold">{{ $grade->user->name }}</div>
                                        <span class="badge bg-light text-dark border font-monospace small">{{ $grade->user->person_code }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="font-monospace small">{{ $grade->subject?->code }}</td>
                            <td>{{ $grade->subject?->name }}</td>
                            <td>{{ $grade->offering->group_code }}</td>
                            <td class="small">{{ $grade->offering->semester?->label ?? '—' }}</td>
                            @foreach ($components as $component)
                                <td>{{ $grade->{$component['key']} !== null ? number_format((float) $grade->{$component['key']}, 2) : '—' }}</td>
                            @endforeach
                            <td class="fw-bold">{{ $total === null ? '—' : number_format($total, 2) }}</td>
                            <td>
                                @if ($total === null)
                                    —
                                @else
                                    <span class="badge {{ $letterClass($grade->letter()) }}">{{ $grade->letter() }}</span>
                                @endif
                            </td>
                            <td>{{ $total === null ? '—' : number_format($grade->fourScore(), 2) }}</td>
                            <td>
                                <span class="badge bg-light border text-{{ $grade->is_published ? 'success' : 'warning' }}">
                                    {{ $grade->is_published ? 'Đã công bố' : 'Chưa công bố' }}
                                </span>
                            </td>
                            <td class="text-nowrap">
                                <form method="POST" action="{{ route('admin.grades.destroy', $grade) }}"
                                    data-confirm="Bạn có chắc muốn xóa điểm này?">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Xóa</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 10 + count($components) }}" class="text-center text-secondary py-4">Chưa có điểm.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body p-3">
            {{ $grades->links() }}
        </div>
    </div>
@endsection
