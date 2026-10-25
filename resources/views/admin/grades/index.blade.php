@extends('layouts.spark')
@section('title', 'Quản lý điểm')
@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="h3 mb-0">Quản lý điểm</h1>
        <a class="btn btn-outline-primary" href="{{ route('admin.grades.export') }}">
            <i class="bi bi-download me-1" aria-hidden="true"></i>Tải CSV
        </a>
    </div>
    <form class="row g-3 align-items-end mb-4" method="POST" action="{{ route('admin.grades.store') }}">
        @csrf
        <div class="col-md-4">
            <label class="form-label" for="user_id">Sinh viên</label>
            <select class="form-select" id="user_id" name="user_id" required>
                <option value="">Chọn sinh viên</option>
                @foreach ($students as $student)
                    <option value="{{ $student->id }}" @selected(old('user_id') == $student->id)>
                        {{ $student->name }} ({{ $student->email }})
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="subject_id">Môn học</label>
            <select class="form-select" id="subject_id" name="subject_id" required>
                <option value="">Chọn môn học đã đăng ký</option>
                @foreach ($subjects as $subject)
                    <option value="{{ $subject->id }}" @selected(old('subject_id') == $subject->id)>
                        {{ $subject->code }} - {{ $subject->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label" for="score">Điểm (0-10)</label>
            <input class="form-control" id="score" name="score" type="number" min="0" max="10"
                step="0.01" value="{{ old('score') }}" required>
        </div>
        <div class="col-md-2"><button class="btn btn-primary" type="submit">Lưu điểm</button></div>
    </form>

    <div class="table-responsive">
        <table class="table table-bordered table-hover bg-white align-middle">
            <thead class="table-light"><tr><th>Sinh viên</th><th>Mã môn</th><th>Tên môn</th><th>Điểm</th><th>Thao tác</th></tr></thead>
            <tbody>
                @forelse ($grades as $grade)
                    <tr>
                        <td>{{ $grade->user->name }}</td>
                        <td>{{ $grade->subject->code }}</td>
                        <td>{{ $grade->subject->name }}</td>
                        <td>{{ $grade->score }}</td>
                        <td>
                            <form method="POST" action="{{ route('admin.grades.destroy', $grade) }}"
                                data-confirm="Bạn có chắc muốn xóa điểm này?">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" type="submit">Xóa</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center">Chưa có điểm.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $grades->links() }}
@endsection
