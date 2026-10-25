@extends('layouts.spark')
@section('title', 'Điểm danh và báo cáo')
@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="h3 mb-0">Điểm danh và báo cáo</h1>
        <a class="btn btn-outline-primary" href="{{ route('admin.attendances.export', ['date' => $date]) }}">
            <i class="bi bi-download me-1" aria-hidden="true"></i>Tải CSV
        </a>
    </div>
    <form class="row g-2 align-items-end mb-3" method="GET" action="{{ route('admin.attendances.index') }}">
        <div class="col-auto">
            <label class="form-label" for="date">Ngày điểm danh</label>
            <input class="form-control" id="date" name="date" type="date" value="{{ $date }}" required>
        </div>
        <div class="col-auto"><button class="btn btn-outline-primary" type="submit">Xem báo cáo</button></div>
    </form>

    <p class="mb-3">
        Ngày {{ \Illuminate\Support\Carbon::parse($date)->format('d/m/Y') }}:
        <span class="badge text-bg-success">Có mặt: {{ $presentCount }}</span>
        <span class="badge text-bg-danger">Vắng: {{ $absentCount }}</span>
    </p>

    <form method="POST" action="{{ route('admin.attendances.store') }}">
        @csrf
        <input type="hidden" name="date" value="{{ $date }}">
        <div class="table-responsive">
            <table class="table table-bordered table-hover bg-white align-middle">
                <thead class="table-light"><tr><th>Sinh viên</th><th>Môn đã đăng ký</th><th>Trạng thái</th></tr></thead>
                <tbody>
                    @forelse ($students as $student)
                        @forelse ($student->subjects as $subject)
                            @php
                                $record = $records->first(fn ($item) => $item->user_id === $student->id && $item->subject_id === $subject->id);
                            @endphp
                            <tr>
                                <td>{{ $student->name }}</td>
                                <td>{{ $subject->code }} - {{ $subject->name }}</td>
                                <td>
                                    <label class="visually-hidden" for="attendance-{{ $student->id }}-{{ $subject->id }}">
                                        Trạng thái điểm danh của {{ $student->name }} - {{ $subject->name }}
                                    </label>
                                    <select class="form-select" id="attendance-{{ $student->id }}-{{ $subject->id }}"
                                        name="attendances[{{ $student->id }}][{{ $subject->id }}]">
                                        <option value="">Chưa điểm danh</option>
                                        <option value="present" @selected($record?->status === 'present')>Có mặt</option>
                                        <option value="absent" @selected($record?->status === 'absent')>Vắng</option>
                                    </select>
                                </td>
                            </tr>
                        @empty
                            @continue
                        @endforelse
                    @empty
                        <tr><td colspan="3" class="text-center">Chưa có sinh viên.</td></tr>
                    @endforelse
                    @if ($students->isNotEmpty() && $students->every(fn ($student) => $student->subjects->isEmpty()))
                        <tr><td colspan="3" class="text-center">Chưa có sinh viên đăng ký môn học.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
        <button class="btn btn-primary" type="submit">Lưu điểm danh</button>
    </form>

    <h2 class="h5 mt-4">Bảng điểm danh trong ngày</h2>
    <div class="table-responsive">
        <table class="table table-bordered bg-white">
            <thead class="table-light"><tr><th>Sinh viên</th><th>Môn học</th><th>Trạng thái</th></tr></thead>
            <tbody>
                @forelse ($records as $record)
                    <tr>
                        <td>{{ $record->user->name }}</td>
                        <td>{{ $record->subject->name }}</td>
                        <td>{{ $record->status === 'present' ? 'Có mặt' : 'Vắng' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center">Chưa có dữ liệu điểm danh trong ngày này.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
