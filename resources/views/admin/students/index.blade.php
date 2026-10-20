@extends('layouts.spark')
@section('title', 'Quản lý sinh viên')
@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="h3 mb-0">Quản lý sinh viên</h1>
        <a class="btn btn-primary" href="{{ route('admin.students.create') }}">Thêm sinh viên</a>
    </div>
    <form class="row g-2 mb-3" method="GET" action="{{ route('admin.students.index') }}">
        <div class="col-sm-8 col-md-5">
            <label class="visually-hidden" for="search">Tìm sinh viên</label>
            <input class="form-control" id="search" name="search" value="{{ $search }}" placeholder="Tên hoặc email">
        </div>
        <div class="col-auto"><button class="btn btn-outline-primary" type="submit">Tìm kiếm</button></div>
    </form>
    <div class="table-responsive">
        <table class="table table-bordered table-hover bg-white align-middle">
            <thead class="table-light"><tr><th>Họ tên</th><th>Email</th><th>Lớp</th><th>Thao tác</th></tr></thead>
            <tbody>
                @forelse ($students as $student)
                    <tr>
                        <td>{{ $student->name }}</td><td>{{ $student->email }}</td>
                        <td>{{ $student->studentClass?->class_name ?? 'Chưa xếp lớp' }}</td>
                        <td class="text-nowrap">
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.students.edit', $student) }}">Sửa</a>
                            <form class="d-inline" method="POST" action="{{ route('admin.students.destroy', $student) }}"
                                data-confirm="Bạn có chắc muốn xóa sinh viên này?">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" type="submit">Xóa</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center">Chưa có sinh viên.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $students->links() }}
@endsection
