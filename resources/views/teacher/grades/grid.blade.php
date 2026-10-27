@extends('layouts.spark')

@section('title', 'Nhập điểm · '.$offering->label())

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h3 mb-0">Nhập điểm</h1>
            <p class="text-muted mb-0">
                {{ $offering->subject?->label() ?? '—' }} · nhóm {{ $offering->group_code }} ·
                {{ $offering->semester?->label ?? '—' }}
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge text-bg-{{ $isPublished ? 'success' : 'warning' }}">
                {{ $isPublished ? 'Đã công bố' : 'Chưa công bố' }}
            </span>
            <a class="btn btn-outline-secondary" href="{{ route('teacher.offerings.index') }}">Quay lại</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body d-flex flex-wrap gap-4 align-items-center">
            <div class="small">
                Sĩ số: {{ $size }}@if ($capacity > 0) / {{ $capacity }}@endif
            </div>
            <div class="small">
                Trung bình lớp: {{ $classAverage === null ? '—' : number_format($classAverage, 2) }}
            </div>
            <div class="small">
                Môn F: {{ $failingCount }}
            </div>
            <form class="ms-auto" method="POST" action="{{ route('teacher.course-offerings.grades.publish', $offering) }}">
                @csrf
                <button class="btn btn-outline-success" type="submit" @if ($size === 0) disabled @endif>
                    <i class="bi bi-megaphone me-1" aria-hidden="true"></i>Công bố điểm
                </button>
            </form>
        </div>
    </div>

    <form method="POST" action="{{ route('teacher.course-offerings.grades.update', $offering) }}">
        @csrf
        <div class="table-responsive">
            <table class="table table-bordered table-hover bg-white align-middle">
                <thead class="table-light">
                    <tr>
                        <th scope="col">STT</th>
                        <th scope="col">Sinh viên</th>
                        @foreach ($components as $component)
                            <th scope="col">{{ $component['label'] }} ({{ $component['weight'] }}%)</th>
                        @endforeach
                        <th scope="col">Tổng kết</th>
                        <th scope="col">Chữ</th>
                        <th scope="col">Hệ 4</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        @php($grade = $row['grade'])
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $row['student']->name }} ({{ $row['student']->person_code ?: 'chưa có mã' }})</td>
                            @foreach ($components as $component)
                                <td>
                                    <label class="visually-hidden" for="{{ $component['key'] }}-{{ $row['student']->id }}">
                                        {{ $row['student']->name }} — {{ $component['label'] }}
                                    </label>
                                    <input class="form-control form-control-sm{{ $errors->has($component['key'].'.'.$row['student']->id) ? ' is-invalid' : '' }}"
                                        id="{{ $component['key'] }}-{{ $row['student']->id }}"
                                        name="{{ $component['key'] }}[{{ $row['student']->id }}]" type="number"
                                        min="0" max="10" step="0.01" value="{{ $grade?->{$component['key']} }}">
                                    @error($component['key'].'.'.$row['student']->id)
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </td>
                            @endforeach
                            <td>{{ $grade?->subjectScore() === null ? '—' : number_format($grade->subjectScore(), 2) }}</td>
                            <td>{{ $grade?->letter() ?? '—' }}</td>
                            <td>{{ $grade?->subjectScore() === null ? '—' : number_format($grade->fourScore(), 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 5 + count($components) }}" class="text-center">
                                Chưa có sinh viên nào đang học ở lớp này.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <button class="btn btn-primary" type="submit" @if ($size === 0) disabled @endif>
            <i class="bi bi-check-lg me-1" aria-hidden="true"></i>Lưu tất cả
        </button>
        <div class="form-text">
            Ô để trống có nghĩa là chưa có điểm, nên lưu ô trống sẽ xóa điểm đã nhập nhầm. Điểm tổng kết,
            chữ và hệ 4 tính lại từ ba cột nhập, không phải ô thứ tư để gõ.
        </div>
    </form>
@endsection
