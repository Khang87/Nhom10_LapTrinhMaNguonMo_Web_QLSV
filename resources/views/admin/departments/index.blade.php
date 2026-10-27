@extends('layouts.spark')

@section('title', 'Quản lý khoa')

@section('content')
    <x-page-header title="Quản lý khoa">
        <x-slot name="actions">
            <a class="btn btn-custom btn-custom-primary" href="{{ route('admin.departments.create') }}">
                <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Thêm khoa
            </a>
        </x-slot>
    </x-page-header>

    <x-data-table>
        <thead>
            <tr><th scope="col">Mã khoa</th><th scope="col">Tên khoa</th><th scope="col">Chuyên ngành</th><th scope="col">Thao tác</th></tr>
        </thead>
        <tbody>
            @forelse ($departments as $department)
                <tr>
                    <td class="table-order-id">{{ $department->code }}</td>
                    <td class="table-product-name">{{ $department->name }}</td>
                    <td>{{ $department->majors_count }}</td>
                    <td class="text-nowrap">
                        <a class="btn btn-custom btn-custom-sm btn-custom-outline-primary" href="{{ route('admin.departments.edit', $department) }}">Sửa</a>
                        <form class="d-inline" method="POST" action="{{ route('admin.departments.destroy', $department) }}"
                            data-confirm="Bạn có chắc muốn xóa khoa {{ $department->code }}? Khoa còn chuyên ngành sẽ không xóa được.">
                            @csrf @method('DELETE')
                            <button class="btn btn-custom btn-custom-sm btn-custom-outline-danger" type="submit">Xóa</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center py-5">
                        <div class="empty-state-icon bi bi-inbox" aria-hidden="true"></div>
                        <div class="table-user-sub">Chưa có khoa nào.</div>
                    </td>
                </tr>
            @endforelse
        </tbody>
        <x-slot name="footer">
            {{ $departments->links() }}
        </x-slot>
    </x-data-table>
@endsection
