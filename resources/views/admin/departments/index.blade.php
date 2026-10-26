@extends('layouts.spark')

@section('title', 'Quản lý khoa')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="h3 mb-0">Quản lý khoa</h1>
        <a class="btn btn-primary" href="{{ route('admin.departments.create') }}">
            <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Thêm khoa
        </a>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered table-hover bg-white align-middle">
            <thead class="table-light">
                <tr><th>Mã khoa</th><th>Tên khoa</th><th>Chuyên ngành</th><th>Thao tác</th></tr>
            </thead>
            <tbody>
                @forelse ($departments as $department)
                    <tr>
                        <td>{{ $department->code }}</td>
                        <td>{{ $department->name }}</td>
                        <td>{{ $department->majors_count }}</td>
                        <td class="text-nowrap">
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.departments.edit', $department) }}">Sửa</a>
                            <form class="d-inline" method="POST" action="{{ route('admin.departments.destroy', $department) }}"
                                data-confirm="Bạn có chắc muốn xóa khoa {{ $department->code }}? Khoa còn chuyên ngành sẽ không xóa được.">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" type="submit">Xóa</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center">Chưa có khoa nào.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $departments->links() }}
@endsection
