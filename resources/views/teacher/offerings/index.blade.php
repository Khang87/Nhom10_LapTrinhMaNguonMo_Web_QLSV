@extends('layouts.spark')

@section('title', 'Lớp học phần của tôi')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h3 mb-0">Lớp học phần của tôi</h1>
            <p class="text-muted mb-0 small">
                {{ $offerings->count() }} lớp học phần được phân công cho bạn trong hệ thống.
            </p>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-hover bg-white align-middle">
            <thead class="table-light">
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
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $offering->subject?->label() ?? '—' }}</td>
                        <td>{{ $offering->semester?->label ?? '—' }}</td>
                        <td>{{ $offering->group_code }}</td>
                        <td>{{ $offering->room ?: '—' }}</td>
                        <td>
                            @if ($offering->max_students === 0)
                                {{ $offering->active_count }} / Không giới hạn
                            @else
                                {{ $offering->active_count }}/{{ $offering->max_students }}
                            @endif
                        </td>
                        <td>
                            <span class="badge text-bg-{{ $offering->is_open ? 'success' : 'secondary' }}">
                                {{ $offering->is_open ? 'Mở' : 'Đóng' }}
                            </span>
                        </td>
                        <td class="text-nowrap">
                            <a class="btn btn-sm btn-outline-primary"
                                href="{{ route('teacher.course-offerings.grades', $offering) }}">Nhập điểm</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center">
                            Chưa có lớp học phần nào được phân công cho bạn.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
