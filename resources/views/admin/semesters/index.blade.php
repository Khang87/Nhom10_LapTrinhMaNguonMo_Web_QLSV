@extends('layouts.spark')

@section('title', 'Quản lý học kỳ')

@section('content')
    @php
        $pageSubtitle = 'Học kỳ hiện hành: '.($current?->label ?? 'chưa đặt');
        if ($current) {
            $pageSubtitle .= ' · Cổng đăng ký '.($current->isRegistrationOpen() ? 'đang mở' : 'đang đóng');
        }
    @endphp

    <x-page-header title="Quản lý học kỳ" :subtitle="$pageSubtitle">
        <x-slot name="actions">
            <a class="btn btn-custom btn-custom-primary" href="{{ route('admin.semesters.create') }}">
                <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Thêm học kỳ
            </a>
        </x-slot>
    </x-page-header>

    <x-data-table>
        <thead>
            <tr>
                <th scope="col">STT</th>
                <th scope="col">Năm học</th>
                <th scope="col">Học kỳ</th>
                <th scope="col">Mã kỳ</th>
                <th scope="col">Diễn ra</th>
                <th scope="col">Cổng đăng ký</th>
                <th scope="col">Lớp học phần</th>
                <th scope="col">Trạng thái</th>
                <th scope="col">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($semesters as $semester)
                <tr>
                    <td>{{ $semesters->firstItem() + $loop->index }}</td>
                    <td>{{ $semester->academic_year }}</td>
                    <td>{{ $semester->term }}</td>
                    <td class="table-order-id">{{ $semester->slug }}</td>
                    <td>{{ $semester->starts_at->format('d/m/Y') }} – {{ $semester->ends_at->format('d/m/Y') }}</td>
                    <td>
                        @if ($semester->registration_starts_at && $semester->registration_ends_at)
                            Từ {{ $semester->registration_starts_at->format('d/m/Y H:i') }}<br>
                            đến {{ $semester->registration_ends_at->format('d/m/Y H:i') }}
                        @else
                            Chưa mở cổng
                        @endif
                    </td>
                    <td>{{ $semester->offerings_count }}</td>
                    <td>
                        @if ($semester->is_active)
                            <x-status-badge tone="success">Hiện hành</x-status-badge>
                        @else
                            <x-status-badge tone="pending">Không hiện hành</x-status-badge>
                        @endif
                    </td>
                    <td class="text-nowrap">
                        @unless ($semester->is_active)
                            <form class="d-inline" method="POST"
                                action="{{ route('admin.semesters.activate', $semester) }}"
                                data-confirm="Đặt {{ $semester->label }} làm học kỳ hiện hành?">
                                @csrf
                                <button class="btn btn-custom btn-custom-sm btn-custom-outline-primary" type="submit">Đặt hiện hành</button>
                            </form>
                        @endunless
                        <a class="btn btn-custom btn-custom-sm btn-custom-outline-primary"
                            href="{{ route('admin.semesters.edit', $semester) }}">Sửa</a>
                        <form class="d-inline" method="POST"
                            action="{{ route('admin.semesters.destroy', $semester) }}"
                            data-confirm="Bạn có chắc muốn xóa {{ $semester->label }}? Học kỳ còn lớp học phần sẽ không xóa được.">
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
                        <div class="table-user-sub">Chưa có học kỳ nào.</div>
                    </td>
                </tr>
            @endforelse
        </tbody>
        <x-slot name="footer">
            {{ $semesters->links() }}
        </x-slot>
    </x-data-table>
@endsection
