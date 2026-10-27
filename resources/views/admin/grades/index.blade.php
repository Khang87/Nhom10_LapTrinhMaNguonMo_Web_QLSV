@extends('layouts.spark')

@section('title', 'Quản lý điểm')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h3 mb-0">Quản lý điểm</h1>
            <p class="text-muted mb-0 small">
                Ba cột điểm thành phần quyết định điểm tổng kết; điểm do quản trị viên cập nhật được công bố ngay.
            </p>
        </div>
        <a class="btn btn-outline-primary" href="{{ route('admin.grades.export', request()->query()) }}">
            <i class="bi bi-download me-1" aria-hidden="true"></i>Tải CSV
        </a>
    </div>

    <form class="row g-3 align-items-end mb-4" method="GET" action="{{ route('admin.grades.index') }}">
        <div class="col-sm-8 col-md-3">
            <label class="visually-hidden" for="filter_semester_id">Lọc theo học kỳ</label>
            <select class="form-select" id="filter_semester_id" name="semester_id">
                <option value="">Mọi học kỳ</option>
                @foreach ($semesters as $semester)
                    <option value="{{ $semester->id }}" @selected(request('semester_id') == $semester->id)>
                        {{ $semester->label }}{{ $semester->is_active ? ' (hiện hành)' : '' }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-sm-8 col-md-3">
            <label class="visually-hidden" for="filter_department_id">Lọc theo khoa</label>
            <select class="form-select" id="filter_department_id" name="department_id">
                <option value="">Mọi khoa</option>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}" @selected(request('department_id') == $department->id)>
                        {{ $department->name }} ({{ $department->code }})
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-sm-8 col-md-4">
            <label class="visually-hidden" for="filter_user_id">Lọc theo sinh viên</label>
            <select class="form-select" id="filter_user_id" name="user_id">
                <option value="">Mọi sinh viên</option>
                @foreach ($students as $student)
                    <option value="{{ $student->id }}" @selected(request('user_id') == $student->id)>
                        {{ $student->name }} ({{ $student->person_code }})
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-auto"><button class="btn btn-outline-primary" type="submit">Tìm kiếm</button></div>
    </form>

    <div class="card mb-4">
        <div class="card-body small">
            GPA hệ 4 = {{ $summary['gpa'] === null ? '—' : number_format($summary['gpa'], 2) }},
            trung bình hệ 10 = {{ $summary['average_ten'] === null ? '—' : number_format($summary['average_ten'], 2) }},
            tín chỉ đã tích lũy = {{ $summary['credits_accumulated'] }} TC,
            xếp loại = {{ $summary['classification'] }}
            <div class="form-text">Tính trên các môn đã công bố nằm trong bộ lọc đang áp dụng.</div>
        </div>
    </div>

    <form class="row g-3 align-items-end mb-4 bg-white p-3 rounded" method="POST" action="{{ route('admin.grades.store') }}">
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
                <i class="bi bi-check-lg me-1" aria-hidden="true"></i>Lưu điểm
            </button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-bordered table-hover bg-white align-middle">
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
                    <th scope="col">Điểm hệ 4</th>
                    <th scope="col">Trạng thái</th>
                    <th scope="col">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($grades as $grade)
                    <tr>
                        <td>{{ $grade->user->name }} ({{ $grade->user->person_code }})</td>
                        <td>{{ $grade->subject?->code }}</td>
                        <td>{{ $grade->subject?->name }}</td>
                        <td>{{ $grade->offering->group_code }}</td>
                        <td>{{ $grade->offering->semester?->label ?? '—' }}</td>
                        @foreach ($components as $component)
                            <td>{{ $grade->{$component['key']} !== null ? number_format((float) $grade->{$component['key']}, 2) : '—' }}</td>
                        @endforeach
                        <td>{{ $grade->subjectScore() === null ? '—' : number_format($grade->subjectScore(), 2) }}</td>
                        <td>{{ $grade->subjectScore() === null ? '—' : $grade->letter() }}</td>
                        <td>{{ $grade->subjectScore() === null ? '—' : number_format($grade->fourScore(), 2) }}</td>
                        <td>
                            <span class="badge text-bg-{{ $grade->is_published ? 'success' : 'warning' }}">
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
                        <td colspan="{{ 10 + count($components) }}" class="text-center">Chưa có điểm.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $grades->links() }}
@endsection
