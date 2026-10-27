@extends('layouts.spark')

@section('title', 'Quản lý điểm')

@section('content')
    @php
        $letterTone = fn (string $letter): string => match ($letter) {
            'A' => 'success',
            'B' => 'info',
            'C' => 'pending',
            'D' => 'neutral',
            default => 'failed',
        };
    @endphp

    <x-page-header title="Quản lý điểm"
        subtitle="Ba cột điểm thành phần quyết định điểm tổng kết; điểm do quản trị viên cập nhật được công bố ngay.">
        <x-slot name="actions">
            <a class="btn btn-custom btn-custom-outline-primary"
                href="{{ route('admin.grades.export', request()->query()) }}">
                <i class="bi bi-download" aria-hidden="true"></i>Tải CSV
            </a>
        </x-slot>
    </x-page-header>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Bộ lọc</h2>
        </div>
        <form class="row g-3 align-items-end" method="GET" action="{{ route('admin.grades.index') }}">
            <div class="col-sm-6 col-md-3">
                <label class="form-label-custom" for="filter_semester_id">Học kỳ</label>
                <select class="form-select form-select-custom" id="filter_semester_id" name="semester_id">
                    <option value="">Mọi học kỳ</option>
                    @foreach ($semesters as $semester)
                        <option value="{{ $semester->id }}" @selected(request('semester_id') == $semester->id)>
                            {{ $semester->label }}{{ $semester->is_active ? ' (hiện hành)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-6 col-md-3">
                <label class="form-label-custom" for="filter_department_id">Khoa</label>
                <select class="form-select form-select-custom" id="filter_department_id" name="department_id">
                    <option value="">Mọi khoa</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}" @selected(request('department_id') == $department->id)>
                            {{ $department->name }} ({{ $department->code }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-6 col-md-3">
                <label class="form-label-custom" for="filter_user_id">Sinh viên</label>
                <select class="form-select form-select-custom" id="filter_user_id" name="user_id">
                    <option value="">Mọi sinh viên</option>
                    @foreach ($students as $student)
                        <option value="{{ $student->id }}" @selected(request('user_id') == $student->id)>
                            {{ $student->name }} ({{ $student->person_code }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto d-flex gap-2">
                <button class="btn btn-custom btn-custom-primary" type="submit">
                    <i class="bi bi-search" aria-hidden="true"></i>Tìm kiếm
                </button>
                @if (request()->hasAny(['semester_id', 'department_id', 'user_id']))
                    <a class="btn btn-custom btn-custom-light" href="{{ route('admin.grades.index') }}">
                        <i class="bi bi-arrow-clockwise" aria-hidden="true"></i>Đặt lại
                    </a>
                @endif
            </div>
        </form>
    </div>

    @if ($selectedStudent)
        <div class="card">
            <div class="table-user-cell">
                <img src="{{ e($selectedStudent->avatarUrl()) }}" alt="" class="table-user-avatar table-user-avatar-lg">
                <div>
                    <div class="table-user-name">{{ $selectedStudent->name }}</div>
                    <div class="table-user-sub">{{ $selectedStudent->person_code }}</div>
                    <div class="fs-xs text-muted-green mt-1">
                        <i class="bi bi-people me-1" aria-hidden="true"></i>Lớp {{ $selectedStudent->studentClass?->label() ?? 'chưa gán' }}
                        <span class="mx-1">•</span>
                        <i class="bi bi-mortarboard me-1" aria-hidden="true"></i>{{ $selectedStudent->studentClass?->major?->name ?? 'Chưa có chuyên ngành' }}
                        <span class="mx-1">•</span>
                        <i class="bi bi-building me-1" aria-hidden="true"></i>{{ $selectedStudent->studentClass?->major?->department?->name ?? 'Chưa gán khoa' }}
                    </div>
                    <a class="btn btn-custom btn-custom-sm btn-custom-outline-primary mt-2"
                        href="{{ route('admin.students.show', $selectedStudent) }}">
                        <i class="bi bi-person-badge" aria-hidden="true"></i>Hồ sơ sinh viên
                    </a>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-2">
            <div class="col-6 col-lg-3">
                <x-stat-card label="GPA hệ 4" :value="$review['gpa'] === null ? '—' : number_format($review['gpa'], 2)">
                    {{ $review['classification'] }}
                </x-stat-card>
            </div>
            <div class="col-6 col-lg-3">
                <x-stat-card label="Điểm TB hệ 10"
                    :value="$review['average_ten'] === null ? '—' : number_format($review['average_ten'], 2)" hint="Thang điểm 10" />
            </div>
            <div class="col-6 col-lg-3">
                @php $percent = $review['credits_required'] ? min(100, (int) round($review['credits_accumulated'] / $review['credits_required'] * 100)) : null; @endphp
                <x-stat-card label="Tín chỉ tích lũy"
                    :value="$review['credits_accumulated'].' / '.($review['credits_required'] ?? '—')">
                    @if ($percent !== null)
                        <div class="progress my-1" style="height: 4px;" role="progressbar"
                            aria-label="Tiến độ khung chương trình" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar" style="width: {{ $percent }}%"></div>
                        </div>
                        Đạt {{ $percent }}% khung
                    @else
                        Chưa có khung CTĐT
                    @endif
                </x-stat-card>
            </div>
            <div class="col-6 col-lg-3">
                <x-stat-card label="Chuyên cần"
                    :value="$review['attendance_rate'] === null ? '—' : number_format($review['attendance_rate'], 1).'%'">
                    {{ $review['failing_subjects'] > 0 ? $review['failing_subjects'].' môn F' : 'Không nợ môn' }}
                </x-stat-card>
            </div>
        </div>
        <p class="fs-xs">
            <x-status-badge tone="info">
                Gợi ý: {{ App\Support\PersonLabels::option($review['suggestion']['status'], App\Support\PersonLabels::ACADEMIC_STATUSES) }}
            </x-status-badge>
            <span class="text-muted-green">{{ implode(' ', $review['suggestion']['reasons']) }}</span>
        </p>
    @else
        <div class="row g-3 mb-2">
            <div class="col-6 col-lg-3">
                <x-stat-card label="GPA hệ 4" :value="$summary['gpa'] === null ? '—' : number_format($summary['gpa'], 2)" />
            </div>
            <div class="col-6 col-lg-3">
                <x-stat-card label="Điểm TB hệ 10" :value="$summary['average_ten'] === null ? '—' : number_format($summary['average_ten'], 2)" />
            </div>
            <div class="col-6 col-lg-3">
                <x-stat-card label="Tín chỉ tích lũy" :value="$summary['credits_accumulated'].' TC'" />
            </div>
            <div class="col-6 col-lg-3">
                <x-stat-card label="Xếp loại" :value="$summary['classification']" :hint="$summary['failing_subjects'].' môn F'" />
            </div>
        </div>
        <p class="fs-xs text-muted-green">Bốn thẻ trên tính trên các môn đã công bố nằm trong bộ lọc đang áp dụng.</p>
    @endif

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Cập nhật điểm</h2>
        </div>
        <form class="row g-3 align-items-end" method="POST" action="{{ route('admin.grades.store') }}">
            @csrf
            <div class="col-md-4">
                <label class="form-label-custom" for="user_id">Sinh viên</label>
                <select class="form-select form-select-custom" id="user_id" name="user_id" required>
                    <option value="">Chọn sinh viên</option>
                    @foreach ($students as $student)
                        <option value="{{ $student->id }}" @selected(old('user_id') == $student->id)>
                            {{ $student->name }} ({{ $student->person_code }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label-custom" for="course_offering_id">Lớp học phần</label>
                <select class="form-select form-select-custom" id="course_offering_id" name="course_offering_id" required>
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
                    <label class="form-label-custom" for="{{ $component['key'] }}">
                        {{ $component['label'] }} ({{ $component['weight'] }}%)
                    </label>
                    <input class="form-control form-control-custom" id="{{ $component['key'] }}"
                        name="{{ $component['key'] }}" type="number" min="0" max="10" step="0.01" value="{{ old($component['key']) }}">
                </div>
            @endforeach
            <div class="col-12">
                @error('final_score')
                    <div class="form-feedback-custom invalid-custom">{{ $message }}</div>
                @enderror
                <button class="btn btn-custom btn-custom-primary" type="submit">
                    <i class="bi bi-check-lg" aria-hidden="true"></i>Cập nhật
                </button>
            </div>
        </form>
    </div>

    <x-data-table>
        <thead>
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
                        <x-person-cell :name="$grade->user->name" :sub="$grade->user->person_code" :avatar="$grade->user->avatarUrl()" />
                    </td>
                    <td class="table-order-id">{{ $grade->subject?->code }}</td>
                    <td class="table-product-name">{{ $grade->subject?->name }}</td>
                    <td>{{ $grade->offering->group_code }}</td>
                    <td>{{ $grade->offering->semester?->label ?? '—' }}</td>
                    @foreach ($components as $component)
                        <td>{{ $grade->{$component['key']} !== null ? number_format((float) $grade->{$component['key']}, 2) : '—' }}</td>
                    @endforeach
                    <td class="table-amount">{{ $total === null ? '—' : number_format($total, 2) }}</td>
                    <td>
                        @if ($total === null)
                            —
                        @else
                            <x-status-badge :tone="$letterTone($grade->letter())">{{ $grade->letter() }}</x-status-badge>
                        @endif
                    </td>
                    <td>{{ $total === null ? '—' : number_format($grade->fourScore(), 2) }}</td>
                    <td>
                        <x-status-badge :tone="$grade->is_published ? 'success' : 'pending'">
                            {{ $grade->is_published ? 'Đã công bố' : 'Chưa công bố' }}
                        </x-status-badge>
                    </td>
                    <td class="text-nowrap">
                        <form method="POST" action="{{ route('admin.grades.destroy', $grade) }}"
                            data-confirm="Bạn có chắc muốn xóa điểm này?">
                            @csrf @method('DELETE')
                            <button class="btn btn-custom btn-custom-sm btn-custom-outline-danger" type="submit">Xóa</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ 10 + count($components) }}" class="text-center py-5">
                        <div class="empty-state-icon bi bi-inbox" aria-hidden="true"></div>
                        <div class="table-user-sub">Chưa có điểm.</div>
                    </td>
                </tr>
            @endforelse
        </tbody>
        <x-slot name="footer">
            <span class="table-pagination-info">{{ $grades->total() }} dòng điểm</span>
            {{ $grades->links() }}
        </x-slot>
    </x-data-table>
@endsection
