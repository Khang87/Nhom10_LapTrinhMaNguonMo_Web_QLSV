@extends('layouts.spark')

@section('title', 'Quản lý môn học')

@section('content')
    <x-page-header title="Quản lý môn học">
        <x-slot name="actions">
            <a class="btn btn-custom btn-custom-primary" href="{{ route('admin.subjects.create') }}">
                <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Thêm môn học
            </a>
        </x-slot>
    </x-page-header>

    <form class="row g-2 mb-3" method="GET" action="{{ route('admin.subjects.index') }}" aria-label="Tìm môn học">
        <div class="col-sm-8 col-md-5">
            <label class="visually-hidden" for="search">Tìm môn học</label>
            <input class="form-control form-control-custom" id="search" name="search" value="{{ $search }}" placeholder="Mã hoặc tên môn">
        </div>
        <div class="col-auto"><button class="btn btn-custom btn-custom-outline-primary" type="submit">Tìm kiếm</button></div>
    </form>

    <x-data-table>
        <thead>
            <tr>
                <th scope="col">STT</th>
                <th scope="col">Mã môn</th><th scope="col">Tên môn</th><th scope="col">Tín chỉ</th><th scope="col">Chuyên ngành</th>
                <th scope="col">Môn tiên quyết</th><th scope="col">Môn song hành</th><th scope="col">Khung CTĐT</th>
                <th scope="col">Lớp học phần</th><th scope="col">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($subjects as $subject)
                @php
                    $prerequisites = $subject->prerequisites_count + $subject->dependentSubjects_count;
                    $concurrents = $subject->concurrents_count + $subject->concurrentOf_count;
                    $confirm = 'Bạn có chắc muốn xóa môn học này?'
                        .($prerequisites > 0 ? " {$prerequisites} quan hệ tiên quyết sẽ bị gỡ theo." : '')
                        .($concurrents > 0 ? " {$concurrents} quan hệ song hành sẽ bị gỡ theo." : '')
                        .($subject->study_plans_count > 0 ? " {$subject->study_plans_count} dòng khung chương trình sẽ bị xóa." : '')
                        .($subject->offerings_count > 0
                            ? " {$subject->offerings_count} lớp học phần cùng đăng ký, điểm và điểm danh của chúng cũng bị xóa."
                            : '');
                @endphp
                <tr>
                    <td>{{ $subjects->firstItem() + $loop->index }}</td>
                    <td class="table-order-id">{{ $subject->code }}</td>
                    <td class="table-product-name">{{ $subject->name }}</td>
                    <td>{{ $subject->credits }}</td>
                    <td>{{ $subject->major?->name ?? 'Chưa phân chuyên ngành' }}</td>
                    <td>{{ $prerequisites }}</td>
                    <td>{{ $concurrents }}</td>
                    <td>{{ $subject->study_plans_count }}</td>
                    <td>{{ $subject->offerings_count }}</td>
                    <td class="text-nowrap">
                        <a class="btn btn-custom btn-custom-sm btn-custom-outline-primary" href="{{ route('admin.subjects.edit', $subject) }}">Sửa</a>
                        <form class="d-inline" method="POST" action="{{ route('admin.subjects.destroy', $subject) }}"
                            data-confirm="{{ $confirm }}">
                            @csrf @method('DELETE')
                            <button class="btn btn-custom btn-custom-sm btn-custom-outline-danger" type="submit">Xóa</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center py-5">
                        <div class="empty-state-icon bi bi-inbox" aria-hidden="true"></div>
                        <div class="table-user-sub">Chưa có môn học.</div>
                    </td>
                </tr>
            @endforelse
        </tbody>
        <x-slot name="footer">
            {{ $subjects->links() }}
        </x-slot>
    </x-data-table>
@endsection
