@extends('layouts.spark')

@section('title', 'Quản lý lớp học')

@section('content')
    <x-page-header title="Quản lý lớp học">
        <x-slot name="actions">
            <a class="btn btn-custom btn-custom-primary" href="{{ route('admin.classes.create') }}">Thêm lớp học</a>
        </x-slot>
    </x-page-header>

    <form class="row g-2 mb-3" method="GET" action="{{ route('admin.classes.index') }}">
        <div class="col-sm-8 col-md-5">
            <label class="visually-hidden" for="search">Tìm theo mã hoặc tên lớp</label>
            <input class="form-control form-control-custom" id="search" name="search" value="{{ $search }}"
                placeholder="Tìm theo mã hoặc tên lớp">
        </div>
        <div class="col-auto">
            <button class="btn btn-custom btn-custom-outline-primary" type="submit">Tìm kiếm</button>
            <a class="btn btn-custom btn-custom-light" href="{{ route('admin.classes.index') }}">Xóa lọc</a>
        </div>
    </form>

    <x-data-table>
        <thead>
            <tr>
                <th scope="col">STT</th>
                <th scope="col">Mã lớp</th>
                <th scope="col">Tên lớp</th>
                <th scope="col">Khóa</th>
                <th scope="col">Khoa</th>
                <th scope="col">Chuyên ngành</th>
                <th scope="col">GVCN</th>
                <th scope="col">Sĩ số</th>
                <th scope="col">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($classes as $class)
                <tr>
                    <td>{{ $classes->firstItem() + $loop->index }}</td>
                    <td class="table-order-id">{{ $class->class_code }}</td>
                    <td class="table-product-name">{{ $class->class_name }}</td>
                    <td>{{ $class->intake_year ?: '—' }}</td>
                    <td>{{ $class->department?->name ?? 'Chưa phân loại' }}</td>
                    <td class="table-user-sub">{{ $class->major?->name ?? 'Chưa phân chuyên ngành' }}</td>
                    <td><x-person-cell :name="$class->adviser_label" /></td>
                    <td>{{ $class->students_count }}</td>
                    <td class="text-nowrap">
                        <a class="btn btn-custom btn-custom-sm btn-custom-outline-primary"
                            href="{{ route('admin.classes.edit', $class) }}">Sửa</a>
                        <form class="d-inline" method="POST"
                            action="{{ route('admin.classes.destroy', $class) }}"
                            data-confirm="Bạn có chắc muốn xóa lớp này?">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-custom btn-custom-sm btn-custom-outline-danger" type="submit">Xóa</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center py-5">
                        <div class="empty-state-icon bi bi-inbox" aria-hidden="true"></div>
                        <div class="table-user-sub">Chưa có lớp học phù hợp.</div>
                    </td>
                </tr>
            @endforelse
        </tbody>
        <x-slot name="footer">
            {{ $classes->links() }}
        </x-slot>
    </x-data-table>
@endsection
