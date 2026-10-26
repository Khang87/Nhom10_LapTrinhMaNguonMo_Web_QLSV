@extends('layouts.spark')

@section('title', 'Quản lý chuyên ngành')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="h3 mb-0">Quản lý chuyên ngành</h1>
        <a class="btn btn-primary" href="{{ route('admin.majors.create') }}">
            <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Thêm chuyên ngành
        </a>
    </div>
    <form class="row g-2 mb-3" method="GET" action="{{ route('admin.majors.index') }}" aria-label="Lọc chuyên ngành theo khoa">
        <div class="col-sm-8 col-md-5">
            <label class="visually-hidden" for="department_id">Lọc theo khoa</label>
            <select class="form-select" id="department_id" name="department_id">
                <option value="">Mọi khoa</option>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}" @selected($departmentId === $department->id)>
                        {{ $department->name }} ({{ $department->code }})
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-auto"><button class="btn btn-outline-primary" type="submit">Tìm kiếm</button></div>
    </form>
    <div class="table-responsive">
        <table class="table table-bordered table-hover bg-white align-middle">
            <thead class="table-light">
                <tr>
                    <th>Mã ngành</th><th>Tên ngành</th><th>Khoa</th>
                    <th>Môn học</th><th>Khung CTĐT</th><th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($majors as $major)
                    <tr>
                        <td>{{ $major->code }}</td>
                        <td>{{ $major->name }}</td>
                        <td>{{ $major->department?->name ?? '—' }}</td>
                        <td>{{ $major->subjects_count }}</td>
                        <td>{{ $major->study_plans_count }}</td>
                        <td class="text-nowrap">
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.majors.edit', $major) }}">Sửa</a>
                            <form class="d-inline" method="POST" action="{{ route('admin.majors.destroy', $major) }}"
                                data-confirm="Bạn có chắc muốn xóa chuyên ngành {{ $major->code }}? Chuyên ngành còn khung chương trình sẽ không xóa được.">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" type="submit">Xóa</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center">Chưa có chuyên ngành nào.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $majors->links() }}
@endsection
