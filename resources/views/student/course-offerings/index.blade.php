@extends('layouts.spark')

@section('title', 'Đăng ký học phần')

@section('content')
    <div class="mb-3">
        <h1 class="h3 mb-0">Đăng ký học phần</h1>
        <p class="text-muted mb-0 small">
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

    <h2 class="h5">Đăng ký của tôi</h2>
    <p class="small text-muted mb-2">
        Tín chỉ đang học: {{ $currentCredits }} TC / tối đa {{ $maxCredits }} TC
        · Tín chỉ đã tích luỹ: {{ $accumulatedCredits }} TC
    </p>
    <div class="table-responsive mb-4">
        <table class="table table-bordered table-hover bg-white align-middle">
            <thead class="table-light">
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
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $offering->subject?->label() ?? '—' }}</td>
                        <td>{{ $offering->group_code }}</td>
                        <td>{{ $offering->teacher?->name ?? 'Chưa phân công' }}</td>
                        <td>{{ $offering->room ?: '—' }}</td>
                        <td>{{ $offering->subject?->credits }}</td>
                        <td class="text-nowrap">
                            <form class="d-inline" method="POST" action="{{ route('student.enrollments.drop', $offering->pivot->id) }}"
                                data-confirm="Hủy đăng ký {{ $offering->label() }}?">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" type="submit">Hủy đăng ký</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center">Bạn chưa đăng ký học phần nào trong học kỳ hiện hành.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <h2 class="h5">Học phần đang mở</h2>
    <p class="small text-muted mb-2">
        Lớp chưa đủ điều kiện vẫn hiển thị kèm lý do, bạn không cần tìm lại ở trang khác.
    </p>
    <div class="table-responsive">
        <table class="table table-bordered table-hover bg-white align-middle">
            <thead class="table-light">
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
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $offering->subject?->label() ?? '—' }}</td>
                        <td>{{ $offering->group_code }}</td>
                        <td>{{ $offering->teacher?->name ?? 'Chưa phân công' }}</td>
                        <td>{{ $offering->room ?: '—' }}</td>
                        <td>{{ $offering->subject?->credits }}</td>
                        <td>{{ $offering->max_students === 0 ? 'Không giới hạn' : $offering->seatsLeft() }}</td>
                        <td class="text-nowrap">
                            @if ($reason = $blocked[$offering->id])
                                <button class="btn btn-sm btn-primary" type="button" disabled>Đăng ký</button>
                                <div class="small text-danger mt-1">{{ $reason }}</div>
                            @else
                                <form class="d-inline" method="POST" action="{{ route('student.course-offerings.enroll', $offering) }}">
                                    @csrf
                                    <button class="btn btn-sm btn-primary" type="submit">Đăng ký</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center">Học kỳ hiện hành chưa có lớp học phần nào.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
