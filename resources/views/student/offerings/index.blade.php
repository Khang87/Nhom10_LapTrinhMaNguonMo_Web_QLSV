@extends('layouts.spark')

@section('title', 'Đăng ký học phần')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="h3 mb-0">Đăng ký học phần</h1>
        <span class="text-muted">Tổng số tín chỉ đã đăng ký: <strong>{{ $totalCredits }}</strong></span>
    </div>

    <h2 class="h5">Học phần đã đăng ký</h2>
    <div class="table-responsive mb-4">
        <table class="table table-bordered table-hover bg-white align-middle">
            <thead class="table-light">
                <tr>
                    <th>Mã môn</th><th>Tên môn</th><th>Nhóm</th><th>Học kỳ</th>
                    <th>Giảng viên</th><th>Tín chỉ</th><th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($registered as $offering)
                    <tr>
                        <td>{{ $offering->subject?->code }}</td>
                        <td>{{ $offering->subject?->name }}</td>
                        <td>{{ $offering->group_code }}</td>
                        <td>{{ $offering->semester?->label }}</td>
                        <td>{{ $offering->teacher?->name ?? 'Chưa phân công' }}</td>
                        <td>{{ $offering->subject?->credits }}</td>
                        <td>
                            <form method="POST" action="{{ route('student.offerings.unregister', $offering) }}">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" type="submit">Hủy đăng ký</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center">Bạn chưa đăng ký học phần nào.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <h2 class="h5">Học phần đang mở</h2>
    <div class="table-responsive">
        <table class="table table-bordered table-hover bg-white align-middle">
            <thead class="table-light">
                <tr>
                    <th>Mã môn</th><th>Tên môn</th><th>Nhóm</th><th>Học kỳ</th>
                    <th>Giảng viên</th><th>Tín chỉ</th><th>Sĩ số</th><th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($available as $offering)
                    <tr>
                        <td>{{ $offering->subject?->code }}</td>
                        <td>{{ $offering->subject?->name }}</td>
                        <td>{{ $offering->group_code }}</td>
                        <td>{{ $offering->semester?->label }}</td>
                        <td>{{ $offering->teacher?->name ?? 'Chưa phân công' }}</td>
                        <td>{{ $offering->subject?->credits }}</td>
                        <td>{{ $offering->max_students === 0 ? 'Không giới hạn' : $offering->active_count.'/'.$offering->max_students }}</td>
                        <td>
                            <form method="POST" action="{{ route('student.offerings.register', $offering) }}">
                                @csrf
                                <button class="btn btn-sm btn-primary" type="submit">Đăng ký</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center">Hiện không còn học phần để đăng ký.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
