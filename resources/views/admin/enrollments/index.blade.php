@extends('layouts.spark')

@section('title', 'Quản lý đăng ký học phần')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h3 mb-0">Quản lý đăng ký học phần</h1>
            <p class="text-muted mb-0 small">
                Mỗi đăng ký đều ghi lại người thực hiện. Hủy hộ không xóa điểm hay điểm danh đã có.
            </p>
        </div>
        <a class="btn btn-outline-primary" href="{{ route('admin.enrollments.export') }}">
            <i class="bi bi-download me-1" aria-hidden="true"></i>Tải CSV
        </a>
    </div>

    <form class="row g-3 align-items-end mb-4" method="GET" action="{{ route('admin.enrollments.index') }}">
        <div class="col-sm-8 col-md-4">
            <label class="visually-hidden" for="filter_semester_id">Lọc theo học kỳ</label>
            <select class="form-select" id="filter_semester_id" name="semester_id">
                <option value="">Mọi học kỳ</option>
                @foreach ($semesters as $semester)
                    <option value="{{ $semester->id }}" @selected($semesterId === $semester->id)>
                        {{ $semester->label }}{{ $semester->is_active ? ' (hiện hành)' : '' }}
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

    <form class="row g-3 align-items-end mb-4 bg-white p-3 rounded" method="POST" action="{{ route('admin.enrollments.store') }}">
        @csrf
        <div class="col-md-4">
            <label class="form-label" for="enroll_user_id">Sinh viên</label>
            <select class="form-select" id="enroll_user_id" name="user_id" required>
                <option value="">Chọn sinh viên</option>
                @foreach ($students as $student)
                    <option value="{{ $student->id }}" @selected(old('user_id') == $student->id)>
                        {{ $student->name }} ({{ $student->person_code }})
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="enroll_course_offering_id">Lớp học phần</label>
            <select class="form-select" id="enroll_course_offering_id" name="course_offering_id" required>
                <option value="">Chọn lớp học phần</option>
                @foreach ($offerings as $offering)
                    <option value="{{ $offering->id }}" @selected(old('course_offering_id') == $offering->id)>
                        {{ $offering->label() }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <button class="btn btn-primary w-100" type="submit">
                <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Đăng ký hộ
            </button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-bordered table-hover bg-white align-middle">
            <thead class="table-light">
                <tr>
                    <th scope="col">STT</th>
                    <th scope="col">Sinh viên</th>
                    <th scope="col">Môn học</th>
                    <th scope="col">Nhóm</th>
                    <th scope="col">Học kỳ</th>
                    <th scope="col">Trạng thái</th>
                    <th scope="col">Người thực hiện</th>
                    <th scope="col">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($enrollments as $enrollment)
                    <tr>
                        <td>{{ $enrollments->firstItem() + $loop->index }}</td>
                        <td>{{ $enrollment->user->name }} ({{ $enrollment->user->person_code }})</td>
                        <td>{{ $enrollment->offering->subject?->label() ?? '—' }}</td>
                        <td>{{ $enrollment->offering->group_code }}</td>
                        <td>{{ $enrollment->offering->semester?->slug ?? '—' }}</td>
                        <td>
                            <span class="badge text-bg-{{ $enrollment->isActive() ? 'success' : 'secondary' }}">
                                {{ $enrollment->isActive() ? 'Đang học (ACTIVE)' : 'Đã hủy (DROPPED)' }}
                            </span>
                            @if (! $enrollment->isActive() && $enrollment->dropped_at)
                                <div class="small text-muted">{{ $enrollment->dropped_at->format('d/m/Y H:i') }}</div>
                            @endif
                        </td>
                        <td>{{ $enrollment->registeredByLabel() }}</td>
                        <td class="text-nowrap">
                            @if ($enrollment->isActive())
                                <form class="d-inline" method="POST"
                                    action="{{ route('admin.enrollments.drop', $enrollment) }}"
                                    data-confirm="Hủy đăng ký {{ $enrollment->offering->label() }} của {{ $enrollment->user->name }}?">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Hủy đăng ký</button>
                                </form>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center">Chưa có đăng ký học phần nào.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $enrollments->links() }}
@endsection
