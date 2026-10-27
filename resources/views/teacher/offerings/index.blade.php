@extends('layouts.spark')

@section('title', 'Lớp học phần của tôi')

@section('content')
    <x-page-header title="Lớp học phần của tôi"
        subtitle="{{ $offerings->count() }} lớp học phần được phân công cho bạn trong hệ thống." />

    <x-data-table>
        <thead>
            <tr>
                <th scope="col">STT</th>
                <th scope="col">Môn học</th>
                <th scope="col">Học kỳ</th>
                <th scope="col">Nhóm</th>
                <th scope="col">Phòng</th>
                <th scope="col">Đã đăng ký / sĩ số</th>
                <th scope="col">Trạng thái</th>
                <th scope="col">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($offerings as $offering)
                <tr>
                    <td class="table-order-id">{{ $loop->iteration }}</td>
                    <td class="table-product-name">{{ $offering->subject?->label() ?? '—' }}</td>
                    <td>{{ $offering->semester?->label ?? '—' }}</td>
                    <td>{{ $offering->group_code }}</td>
                    <td>{{ $offering->room ?: '—' }}</td>
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
                    <td class="text-nowrap">
                        <a class="btn btn-custom btn-custom-sm btn-custom-outline-primary"
                            href="{{ route('teacher.course-offerings.grades', $offering) }}">Nhập điểm</a>
                        <a class="btn btn-custom btn-custom-sm btn-custom-outline-secondary"
                            href="{{ route('teacher.course-offerings.attendance', $offering) }}">Điểm danh</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center py-5">
                        <div class="empty-state-icon mb-2"><i class="bi bi-inbox" aria-hidden="true"></i></div>
                        <div class="table-user-sub">Chưa có lớp học phần nào được phân công cho bạn.</div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </x-data-table>
@endsection
