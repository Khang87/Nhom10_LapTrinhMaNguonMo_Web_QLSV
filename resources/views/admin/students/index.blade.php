@extends('layouts.spark')
@section('title', 'Quản lý sinh viên')
@section('content')
    <x-page-header title="Quản lý sinh viên">
        <x-slot name="actions">
            <a class="btn btn-custom btn-custom-outline-primary" href="{{ route('admin.students.import') }}">
                <i class="bi bi-file-earmark-excel" aria-hidden="true"></i>Import Excel
            </a>
            <a class="btn btn-custom btn-custom-outline-primary" href="{{ route('admin.students.export', request()->query()) }}">
                <i class="bi bi-download" aria-hidden="true"></i>Xuất Excel
            </a>
            <a class="btn btn-custom btn-custom-primary" href="{{ route('admin.students.create') }}">
                <i class="bi bi-plus-lg" aria-hidden="true"></i>Thêm sinh viên
            </a>
        </x-slot>
    </x-page-header>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Bộ lọc</h2>
        </div>
        <form class="row g-3 align-items-end" method="GET" action="{{ route('admin.students.index') }}"
            data-live-search data-target="#student-table-body" aria-label="Lọc sinh viên">
            <div class="col-sm-6 col-md-3">
                <label class="form-label-custom" for="search">Tìm sinh viên</label>
                <input class="form-control form-control-custom" id="search" name="search" value="{{ $filters['search'] }}"
                    placeholder="Tên, email hoặc mã sinh viên" autocomplete="off">
            </div>
            <div class="col-sm-6 col-md-2">
                <label class="form-label-custom" for="intake_year">Khóa tuyển sinh</label>
                <select class="form-select form-select-custom" id="intake_year" name="intake_year">
                    <option value="">Mọi khóa</option>
                    @foreach ($intakeYears as $year)
                        <option value="{{ $year }}" @selected($filters['intake_year'] === $year)>Khóa {{ $year }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-6 col-md-2">
                <label class="form-label-custom" for="department_id">Khoa</label>
                <select class="form-select form-select-custom" id="department_id" name="department_id">
                    <option value="">Mọi khoa</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}" @selected($filters['department_id'] === $department->id)>
                            {{ $department->name }} ({{ $department->code }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-6 col-md-2">
                <label class="form-label-custom" for="major_id">Chuyên ngành</label>
                <select class="form-select form-select-custom" id="major_id" name="major_id">
                    <option value="">Mọi chuyên ngành</option>
                    @foreach ($majors as $major)
                        <option value="{{ $major->id }}" @selected($filters['major_id'] === $major->id)>
                            {{ $major->name }} ({{ $major->code }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-6 col-md-2">
                <label class="form-label-custom" for="class_id">Lớp</label>
                <select class="form-select form-select-custom" id="class_id" name="class_id">
                    <option value="">Mọi lớp</option>
                    @if ($hasUnassigned)
                        <option value="none" @selected($filters['class_id'] === 'none')>Chưa xếp lớp</option>
                    @endif
                    @foreach ($classOptions as $class)
                        <option value="{{ $class->id }}" @selected($filters['class_id'] === $class->id)>
                            {{ $class->class_code }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-6 col-md-2">
                <label class="form-label-custom" for="academic_status">Tình trạng học tập</label>
                <select class="form-select form-select-custom" id="academic_status" name="academic_status">
                    <option value="">Mọi tình trạng</option>
                    @foreach ($activeStatuses as $code => $label)
                        <option value="{{ $code }}" @selected($filters['academic_status'] === $code)>
                            {{ $label }} ({{ $code }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto d-flex gap-2">
                <button class="btn btn-custom btn-custom-primary" type="submit">
                    <i class="bi bi-search" aria-hidden="true"></i>Tìm kiếm
                </button>
                @if (array_filter($filters))
                    <a class="btn btn-custom btn-custom-light" href="{{ route('admin.students.index') }}">
                        <i class="bi bi-arrow-clockwise" aria-hidden="true"></i>Xóa lọc
                    </a>
                @endif
            </div>
        </form>
    </div>

    <p class="fs-xs text-muted-green" id="live-search-status" aria-live="polite">
        {{ $students->total() }} sinh viên.
    </p>

    <x-data-table>
        <thead>
            <tr>
                <th scope="col">#</th>
                <th scope="col">Mã SV</th>
                <th scope="col">Họ tên</th>
                <th scope="col">Ngày sinh</th>
                <th scope="col">Giới tính</th>
                <th scope="col">Lớp</th>
                <th scope="col">Khóa</th>
                <th scope="col">Chuyên ngành</th>
                <th scope="col">Email liên hệ</th>
                <th scope="col">Tình trạng</th>
                <th scope="col">Thao tác</th>
            </tr>
        </thead>
        <tbody id="student-table-body">
            @include('admin.students.rows')
        </tbody>
        <x-slot name="footer">
            <div id="student-pagination">{{ $students->links() }}</div>
        </x-slot>
    </x-data-table>
@endsection
