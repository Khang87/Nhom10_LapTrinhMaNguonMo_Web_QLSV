@extends('layouts.spark')

@section('title', 'Quản lý học kỳ')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h3 mb-0">Quản lý học kỳ</h1>
            <p class="text-muted mb-0 small">
                Học kỳ hiện hành:
                <strong>{{ $current?->label ?? 'chưa đặt' }}</strong>
                @if ($current)
                    · Cổng đăng ký {{ $current->isRegistrationOpen() ? 'đang mở' : 'đang đóng' }}
                @endif
            </p>
        </div>
        <a class="btn btn-primary" href="{{ route('admin.semesters.create') }}">
            <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Thêm học kỳ
        </a>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-hover bg-white align-middle">
            <thead class="table-light">
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
                        <td>{{ $semester->slug }}</td>
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
                                <span class="badge text-bg-success">Hiện hành</span>
                            @else
                                <span class="badge text-bg-secondary">Không hiện hành</span>
                            @endif
                        </td>
                        <td class="text-nowrap">
                            @unless ($semester->is_active)
                                <form class="d-inline" method="POST"
                                    action="{{ route('admin.semesters.activate', $semester) }}"
                                    data-confirm="Đặt {{ $semester->label }} làm học kỳ hiện hành?">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-primary" type="submit">Đặt hiện hành</button>
                                </form>
                            @endunless
                            <a class="btn btn-sm btn-outline-primary"
                                href="{{ route('admin.semesters.edit', $semester) }}">Sửa</a>
                            <form class="d-inline" method="POST"
                                action="{{ route('admin.semesters.destroy', $semester) }}"
                                data-confirm="Bạn có chắc muốn xóa {{ $semester->label }}? Học kỳ còn lớp học phần sẽ không xóa được.">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" type="submit">Xóa</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center">Chưa có học kỳ nào.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $semesters->links() }}
@endsection
