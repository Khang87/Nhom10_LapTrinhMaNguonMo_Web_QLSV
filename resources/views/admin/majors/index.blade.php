@extends('layouts.spark')

@section('title', 'Quản lý chuyên ngành')

@section('content')
    <x-page-header title="Quản lý chuyên ngành">
        <x-slot name="actions">
            <a class="btn btn-custom btn-custom-primary" href="{{ route('admin.majors.create') }}">
                <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Thêm chuyên ngành
            </a>
        </x-slot>
    </x-page-header>

    <form class="row g-2 mb-3" method="GET" action="{{ route('admin.majors.index') }}" aria-label="Lọc chuyên ngành theo khoa">
        <div class="col-sm-8 col-md-5">
            <label class="visually-hidden" for="department_id">Lọc theo khoa</label>
            <select class="form-select form-select-custom" id="department_id" name="department_id">
                <option value="">Mọi khoa</option>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}" @selected($departmentId === $department->id)>
                        {{ $department->name }} ({{ $department->code }})
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-auto"><button class="btn btn-custom btn-custom-outline-primary" type="submit">Tìm kiếm</button></div>
    </form>

    <x-data-table>
        <thead>
            <tr>
                <th scope="col">Mã ngành</th><th scope="col">Tên ngành</th><th scope="col">Khoa</th>
                <th scope="col">Môn học</th><th scope="col">Khung CTĐT</th><th scope="col">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($majors as $major)
                <tr>
                    <td class="table-order-id">{{ $major->code }}</td>
                    <td class="table-product-name">{{ $major->name }}</td>
                    <td>{{ $major->department?->name ?? '—' }}</td>
                    <td>{{ $major->subjects_count }}</td>
                    <td>{{ $major->study_plans_count }}</td>
                    <td class="text-nowrap">
                        <a class="btn btn-custom btn-custom-sm btn-custom-outline-primary" href="{{ route('admin.majors.edit', $major) }}">Sửa</a>
                        <form class="d-inline" method="POST" action="{{ route('admin.majors.destroy', $major) }}"
                            data-confirm="Bạn có chắc muốn xóa chuyên ngành {{ $major->code }}? Chuyên ngành còn khung chương trình sẽ không xóa được.">
                            @csrf @method('DELETE')
                            <button class="btn btn-custom btn-custom-sm btn-custom-outline-danger" type="submit">Xóa</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center py-5">
                        <div class="empty-state-icon bi bi-inbox" aria-hidden="true"></div>
                        <div class="table-user-sub">Chưa có chuyên ngành nào.</div>
                    </td>
                </tr>
            @endforelse
        </tbody>
        <x-slot name="footer">
            {{ $majors->links() }}
        </x-slot>
    </x-data-table>
@endsection
