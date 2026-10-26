@extends('layouts.spark')
@section('title', 'Quản lý sinh viên')
@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="h3 mb-0">Quản lý sinh viên</h1>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-outline-primary" href="{{ route('admin.students.import') }}">
                <i class="bi bi-file-earmark-excel me-1" aria-hidden="true"></i>Import Excel
            </a>
            <a class="btn btn-outline-success" href="{{ route('admin.students.export', request()->query()) }}">
                <i class="bi bi-download me-1" aria-hidden="true"></i>Xuất Excel
            </a>
            <a class="btn btn-primary" href="{{ route('admin.students.create') }}">Thêm sinh viên</a>
        </div>
    </div>
    <form class="row g-2 mb-3" method="GET" action="{{ route('admin.students.index') }}" data-live-search
        data-target="#student-table-body" aria-label="Lọc sinh viên">
        <div class="col-sm-8 col-md-4">
            <label class="visually-hidden" for="search">Tìm sinh viên</label>
            <input class="form-control" id="search" name="search" value="{{ $filters['search'] }}"
                placeholder="Tên, email hoặc mã sinh viên" autocomplete="off">
        </div>
        <div class="col-sm-4 col-md-2">
            <label class="visually-hidden" for="intake_year">Khóa tuyển sinh</label>
            <select class="form-select" id="intake_year" name="intake_year">
                <option value="">Mọi khóa</option>
                @foreach ($intakeYears as $year)
                    <option value="{{ $year }}" @selected($filters['intake_year'] === $year)>Khóa {{ $year }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-sm-4 col-md-2">
            <label class="visually-hidden" for="department_id">Khoa</label>
            <select class="form-select" id="department_id" name="department_id">
                <option value="">Mọi khoa</option>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}" @selected($filters['department_id'] === $department->id)>
                        {{ $department->name }} ({{ $department->code }})
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-sm-4 col-md-2">
            <label class="visually-hidden" for="major_id">Chuyên ngành</label>
            <select class="form-select" id="major_id" name="major_id">
                <option value="">Mọi chuyên ngành</option>
                @foreach ($majors as $major)
                    <option value="{{ $major->id }}" @selected($filters['major_id'] === $major->id)>
                        {{ $major->name }} ({{ $major->code }})
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-sm-4 col-md-2">
            <label class="visually-hidden" for="class_id">Lớp</label>
            <select class="form-select" id="class_id" name="class_id">
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
        <div class="col-sm-4 col-md-2">
            <label class="visually-hidden" for="academic_status">Tình trạng học tập</label>
            <select class="form-select" id="academic_status" name="academic_status">
                <option value="">Mọi tình trạng</option>
                @foreach ($activeStatuses as $code => $label)
                    <option value="{{ $code }}" @selected($filters['academic_status'] === $code)>
                        {{ $label }} ({{ $code }})
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-auto"><button class="btn btn-outline-primary" type="submit">Tìm kiếm</button></div>
    </form>
    <p class="text-muted small mb-2" id="live-search-status" aria-live="polite">
        {{ $students->total() }} sinh viên.
    </p>
    <div class="table-responsive">
        <table class="table table-bordered table-hover bg-white align-middle">
            <thead class="table-light">
                <tr>
                    <th>Mã SV</th>
                    <th>Họ tên</th>
                    <th>Email</th>
                    <th>Lớp</th>
                    <th>Khóa</th>
                    <th>Tình trạng</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody id="student-table-body">
                @include('admin.students.rows')
            </tbody>
        </table>
    </div>
    <div id="student-pagination">{{ $students->links() }}</div>
@endsection
