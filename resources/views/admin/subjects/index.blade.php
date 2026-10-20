@extends('layouts.spark')

@section('title', 'Quản lý môn học')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="h3 mb-0">Quản lý môn học</h1>
        <a class="btn btn-primary" href="{{ route('admin.subjects.create') }}">Thêm môn học</a>
    </div>
    <form class="row g-2 mb-3" method="GET" action="{{ route('admin.subjects.index') }}">
        <div class="col-sm-8 col-md-5">
            <label class="visually-hidden" for="search">Tìm môn học</label>
            <input class="form-control" id="search" name="search" value="{{ $search }}" placeholder="Mã hoặc tên môn">
        </div>
        <div class="col-auto"><button class="btn btn-outline-primary" type="submit">Tìm kiếm</button></div>
    </form>
    <div class="table-responsive">
        <table class="table table-bordered table-hover bg-white align-middle">
            <thead class="table-light"><tr><th>Mã môn</th><th>Tên môn</th><th>Tín chỉ</th><th>Thao tác</th></tr></thead>
            <tbody>
                @forelse ($subjects as $subject)
                    <tr>
                        <td>{{ $subject->code }}</td><td>{{ $subject->name }}</td><td>{{ $subject->credits }}</td>
                        <td class="text-nowrap">
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.subjects.edit', $subject) }}">Sửa</a>
                            <form class="d-inline" method="POST" action="{{ route('admin.subjects.destroy', $subject) }}"
                                data-confirm="Bạn có chắc muốn xóa môn học này?">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" type="submit">Xóa</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center">Chưa có môn học.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $subjects->links() }}
@endsection
