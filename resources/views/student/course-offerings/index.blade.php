@extends('layouts.spark')

@section('title', 'Đăng ký học phần')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Đăng ký học phần</h1>
            <p class="page-subtitle">
                Học kỳ hiện hành: <strong>{{ $semester?->label ?? 'chưa đặt' }}</strong>
                @if ($semester)
                    @if ($gateOpen)
                        · Cổng đăng ký đang mở tới {{ $semester->registration_ends_at?->format('d/m/Y H:i') }}
                    @elseif ($semester->registration_starts_at && $semester->registration_ends_at)
                        · Cổng đăng ký đang đóng ({{ $semester->registration_starts_at->format('d/m/Y H:i') }}
                        → {{ $semester->registration_ends_at->format('d/m/Y H:i') }})
                    @else
                        · Học kỳ chưa mở cổng đăng ký
                    @endif
                @endif
            </p>
        </div>
    </div>

    <x-data-table title="Đăng ký của tôi">
        <x-slot name="controls">
            <span class="table-pagination-info">
                Tín chỉ đang học: {{ $currentCredits }} TC / tối đa {{ $maxCredits }} TC
                · Tín chỉ đã tích luỹ: {{ $accumulatedCredits }} TC
            </span>
        </x-slot>
        <thead>
            <tr>
                <th scope="col">STT</th>
                <th scope="col">Môn học</th>
                <th scope="col">Nhóm</th>
                <th scope="col">Giảng viên</th>
                <th scope="col">Phòng</th>
                <th scope="col">Tín chỉ</th>
                <th scope="col">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($registered as $offering)
                <tr>
                    <td class="table-order-id">{{ $loop->iteration }}</td>
                    <td class="table-product-name">{{ $offering->subject?->label() ?? '—' }}</td>
                    <td>{{ $offering->group_code }}</td>
                    <td>{{ $offering->teacher?->name ?? 'Chưa phân công' }}</td>
                    <td>{{ $offering->room ?: '—' }}</td>
                    <td class="table-amount">{{ $offering->subject?->credits }}</td>
                    <td class="text-nowrap">
                        <form class="d-inline" method="POST"
                            action="{{ route('student.enrollments.drop', $offering->pivot->id) }}"
                            data-confirm="Hủy đăng ký {{ $offering->label() }}?">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-custom btn-custom-sm btn-custom-outline-danger"
                                type="submit">Hủy đăng ký</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center py-5">
                        <div class="empty-state-icon mb-2"><i class="bi bi-inbox" aria-hidden="true"></i></div>
                        <div class="table-user-sub">Bạn chưa đăng ký học phần nào trong học kỳ hiện hành.</div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </x-data-table>

    <x-data-table title="Học phần đang mở">
        <x-slot name="controls">
            <span class="table-pagination-info">
                Lớp chưa đủ điều kiện vẫn hiển thị kèm lý do, bạn không cần tìm lại ở trang khác.
            </span>
        </x-slot>
        <thead>
            <tr>
                <th scope="col">STT</th>
                <th scope="col">Môn học</th>
                <th scope="col">Nhóm</th>
                <th scope="col">Giảng viên</th>
                <th scope="col">Phòng</th>
                <th scope="col">Tín chỉ</th>
                <th scope="col">Còn lại</th>
                <th scope="col">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($available as $offering)
                <tr>
                    <td class="table-order-id">{{ $loop->iteration }}</td>
                    <td class="table-product-name">{{ $offering->subject?->label() ?? '—' }}</td>
                    <td>{{ $offering->group_code }}</td>
                    <td>{{ $offering->teacher?->name ?? 'Chưa phân công' }}</td>
                    <td>{{ $offering->room ?: '—' }}</td>
                    <td class="table-amount">{{ $offering->subject?->credits }}</td>
                    <td>{{ $offering->max_students === 0 ? 'Không giới hạn' : $offering->seatsLeft() }}</td>
                    <td class="text-nowrap">
                        @if ($reason = $blocked[$offering->id])
                            <button class="btn btn-custom btn-custom-sm btn-custom-primary" type="button" disabled>
                                Đăng ký
                            </button>
                            <div class="form-feedback-custom invalid-custom mt-1">{{ $reason }}</div>
                        @else
                            <form class="d-inline" method="POST"
                                action="{{ route('student.course-offerings.enroll', $offering) }}">
                                @csrf
                                <button class="btn btn-custom btn-custom-sm btn-custom-primary" type="submit">
                                    Đăng ký
                                </button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center py-5">
                        <div class="empty-state-icon mb-2"><i class="bi bi-inbox" aria-hidden="true"></i></div>
                        <div class="table-user-sub">Học kỳ hiện hành chưa có lớp học phần nào.</div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </x-data-table>
@endsection
