@extends('layouts.spark')

@section('title', 'Thời khóa biểu')

@section('content')
    <x-page-header title="Thời khóa biểu"
        subtitle="Chọn một lớp học phần để xem lịch lên lớp trong tuần." />

    <div class="card">
        <form class="row g-3 align-items-end" method="GET" action="{{ route('admin.timetable.index') }}"
            aria-label="Chọn lớp học phần">
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
            <div class="col-sm-6 col-md-5">
                <label class="form-label-custom" for="filter_offering_id">Lớp học phần</label>
                <select class="form-select form-select-custom" id="filter_offering_id" name="offering_id">
                    @forelse ($offerings as $option)
                        <option value="{{ $option->id }}" @selected($offering?->id === $option->id)>
                            {{ $option->label() }}
                        </option>
                    @empty
                        <option value="">Chưa có lớp học phần nào</option>
                    @endforelse
                </select>
            </div>
            <div class="col-auto">
                <button class="btn btn-custom btn-custom-primary" type="submit">Xem lịch</button>
            </div>
        </form>
    </div>

    @if ($offerings->isEmpty())
        <div class="alert-custom alert-custom-info" role="status">
            <i class="bi bi-info-circle alert-custom-icon" aria-hidden="true"></i>
            <span class="alert-custom-content">Học kỳ này chưa có lớp học phần nào.</span>
        </div>
    @else
        <div class="table-header-control d-flex justify-content-end">
            <a class="btn btn-custom btn-custom-sm btn-custom-outline-primary"
                href="{{ route('admin.timetable.export', ['semester_id' => $semester?->id, 'offering_id' => $offering?->id]) }}">
                <i class="bi bi-download me-1" aria-hidden="true"></i>Tải CSV
            </a>
        </div>

        <x-timetable-grid :grid="$grid" :title="$offering?->label()" />
    @endif
@endsection
