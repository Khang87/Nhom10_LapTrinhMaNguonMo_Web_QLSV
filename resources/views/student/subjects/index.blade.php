@extends('layouts.spark')

@section('title', 'Đăng ký môn học')

@section('content')
    <h1 class="h3 mb-3">Đăng ký môn học</h1>

    <h2 class="h5">Môn đã đăng ký</h2>
    <div class="table-responsive mb-4">
        <table class="table table-bordered table-hover bg-white align-middle">
            <thead class="table-light">
                <tr><th>Mã môn</th><th>Tên môn</th><th>Số tín chỉ</th><th>Thao tác</th></tr>
            </thead>
            <tbody>
                @forelse ($registeredSubjects as $subject)
                    <tr>
                        <td>{{ $subject->code }}</td>
                        <td>{{ $subject->name }}</td>
                        <td>{{ $subject->credits }}</td>
                        <td>
                            <form method="POST" action="{{ route('student.subjects.unregister', $subject) }}">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" type="submit">Hủy đăng ký</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center">Bạn chưa đăng ký môn học nào.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <h2 class="h5">Môn học đang mở</h2>
    <div class="table-responsive">
        <table class="table table-bordered table-hover bg-white align-middle">
            <thead class="table-light">
                <tr><th>Mã môn</th><th>Tên môn</th><th>Số tín chỉ</th><th>Thao tác</th></tr>
            </thead>
            <tbody>
                @forelse ($openSubjects as $subject)
                    <tr>
                        <td>{{ $subject->code }}</td>
                        <td>{{ $subject->name }}</td>
                        <td>{{ $subject->credits }}</td>
                        <td>
                            <form method="POST" action="{{ route('student.subjects.register', $subject) }}">
                                @csrf
                                <button class="btn btn-sm btn-primary" type="submit">Đăng ký</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center">Hiện không còn môn học để đăng ký.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
