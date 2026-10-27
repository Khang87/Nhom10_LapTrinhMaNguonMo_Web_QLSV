@extends('layouts.spark')

@section('title', 'Thời khóa biểu')

@section('content')
    <x-page-header title="Thời khóa biểu"
        subtitle="Lịch học trong tuần của bạn, dựng từ lịch lên lớp của các lớp đang học." />

    <div class="card">
        <form class="row g-3 align-items-end" method="GET" action="{{ route('student.timetable') }}"
            aria-label="Chọn học kỳ">
            <div class="col-sm-6 col-md-3">
                <label class="form-label-custom" for="filter_semester_id">Học kỳ</label>
                <select class="form-select form-select-custom" id="filter_semester_id" name="semester_id">
                    @foreach ($semesters as $term)
                        <option value="{{ $term->id }}" @selected($semester?->id === $term->id)>
                            {{ $term->label }}{{ $term->is_active ? ' (hiện hành)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button class="btn btn-custom btn-custom-primary" type="submit">Xem lịch</button>
            </div>
        </form>
    </div>

    @if ($conflicts !== [])
        <div class="alert-custom alert-custom-warning" role="alert">
            <i class="bi bi-exclamation-triangle alert-custom-icon" aria-hidden="true"></i>
            <div class="alert-custom-content">
                Lịch của bạn có {{ count($conflicts) }} cặp trùng giờ:
                <ul class="mb-0">
                    @foreach ($conflicts as $conflict)
                        <li>
                            {{ $conflict['first']->label() }} — {{ $conflict['first']->offering?->label() }}
                            trùng {{ $conflict['second']->label() }} — {{ $conflict['second']->offering?->label() }}
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <x-timetable-grid :grid="$grid" />
@endsection
