@extends('layouts.spark')

@section('title', 'Quản lý lớp học')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="h3 mb-0">Quản lý lớp học</h1>
        <a class="btn btn-primary" href="{{ route('admin.classes.create') }}">Thêm lớp học</a>
    </div>

    <form class="row g-2 mb-3" method="GET" action="{{ route('admin.classes.index') }}">
        <div class="col-sm-8 col-md-5">
            <label class="visually-hidden" for="search">Tìm theo mã hoặc tên lớp</label>
            <input class="form-control" id="search" name="search" value="{{ $search }}"
                placeholder="Tìm theo mã hoặc tên lớp">
        </div>
        <div class="col-auto">
            <button class="btn btn-outline-primary" type="submit">Tìm kiếm</button>
            <a class="btn btn-outline-secondary" href="{{ route('admin.classes.index') }}">Xóa lọc</a>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-bordered table-hover bg-white align-middle">
            <thead class="table-light">
                <tr>
                    <th scope="col">STT</th>
                    <th scope="col">Mã lớp</th>
                    <th scope="col">Tên lớp</th>
                    <th scope="col">Khóa</th>
                    <th scope="col">Khoa</th>
                    <th scope="col">GVCN</th>
                    <th scope="col">Sĩ số</th>
                    <th scope="col">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($classes as $class)
                    <tr>
                        <td>{{ $classes->firstItem() + $loop->index }}</td>
                        <td>{{ $class->class_code }}</td>
                        <td>{{ $class->class_name }}</td>
                        <td>{{ $class->intake_year ?: '—' }}</td>
                        <td>{{ $class->department?->name ?? 'Chưa phân loại' }}</td>
                        <td>{{ $class->adviser_label }}</td>
                        <td>{{ $class->students_count }}</td>
                        <td class="text-nowrap">
                            <a class="btn btn-sm btn-outline-primary"
                                href="{{ route('admin.classes.edit', $class) }}">Sửa</a>
                            <form class="d-inline" method="POST"
                                action="{{ route('admin.classes.destroy', $class) }}"
                                data-confirm="Bạn có chắc muốn xóa lớp này?">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" type="submit">Xóa</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center">Chưa có lớp học phù hợp.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $classes->links() }}
@endsection
