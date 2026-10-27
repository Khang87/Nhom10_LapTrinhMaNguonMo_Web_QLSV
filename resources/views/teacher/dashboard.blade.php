@extends('layouts.spark')

@section('title', 'Trang giảng viên')

@section('content')
    <x-page-header title="Trang giảng viên"
        subtitle="Khu làm việc của {{ $teacher->roleLabel() }}." />

    <div class="card">
        <div class="card-body">
            <div class="table-user-cell">
                <img class="table-user-avatar table-user-avatar-lg" width="58" height="58" alt=""
                    src="{{ e($teacher->avatarUrl()) }}">
                <div>
                    <div class="table-user-name">{{ $teacher->name }}</div>
                    <div class="table-user-sub">{{ $teacher->email }}</div>
                    <div class="table-user-sub">{{ $teacher->roleLabel() }}</div>
                </div>
            </div>
        </div>
    </div>

    @if ($offerings->isEmpty())
        <div class="alert-custom alert-custom-info" role="status">
            <div class="alert-custom-content">
                Chưa có lớp học phần nào được phân công cho bạn.
            </div>
        </div>
    @else
        <x-data-table title="Lớp học phần của tôi ({{ $offerings->count() }})">
            <x-slot name="controls">
                <a class="btn btn-custom btn-custom-sm btn-custom-outline-primary"
                    href="{{ route('teacher.offerings.index') }}">Xem chi tiết</a>
            </x-slot>
            <thead>
                <tr>
                    <th scope="col">Môn học</th>
                    <th scope="col">Đã đăng ký / sĩ số</th>
                    <th scope="col">Trạng thái</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($offerings as $offering)
                    <tr>
                        <td class="table-product-name">{{ $offering->label() }}</td>
                        <td class="table-amount">
                            @if ($offering->max_students === 0)
                                {{ $offering->active_count }} / Không giới hạn
                            @else
                                {{ $offering->active_count }}/{{ $offering->max_students }}
                            @endif
                        </td>
                        <td>
                            <x-status-badge :tone="$offering->is_open ? 'success' : 'neutral'">
                                {{ $offering->is_open ? 'Mở' : 'Đóng' }}
                            </x-status-badge>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-data-table>
    @endif
@endsection
