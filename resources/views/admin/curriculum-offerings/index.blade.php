@extends('layouts.spark')

@section('title', 'Mở lớp theo khung chương trình')

@section('content')
    <x-page-header title="Mở lớp theo khung chương trình"
        subtitle="Chọn một học kỳ đào tạo của khung và một học kỳ thật: mọi môn chưa có lớp ở học kỳ đó sẽ thành lớp học phần mới trong một lần mở." />

    <div class="card">
        <form class="row g-2 align-items-end" method="GET"
            action="{{ route('admin.course-offerings.open-by-plan') }}" aria-label="Chọn khung và học kỳ mở lớp">
            <div class="col-md-6 col-lg-3">
                <label class="form-label-custom" for="major_id">Chuyên ngành</label>
                <select class="form-select form-select-custom" id="major_id" name="major_id">
                    <option value="">Chọn chuyên ngành</option>
                    @foreach ($majors as $option)
                        <option value="{{ $option->id }}" @selected($major?->id === $option->id)>
                            {{ $option->name }} ({{ $option->code }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-lg-3">
                <label class="form-label-custom" for="semester_id">Học kỳ mở lớp</label>
                <select class="form-select form-select-custom" id="semester_id" name="semester_id">
                    @foreach ($semesters as $option)
                        <option value="{{ $option->id }}" @selected($semester?->id === $option->id)>
                            {{ $option->label }}{{ $option->is_active ? ' (hiện hành)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3 col-lg-2">
                <label class="form-label-custom" for="plan_semester">Học kỳ trong khung</label>
                <select class="form-select form-select-custom" id="plan_semester" name="plan_semester">
                    @for($i = 1; $i <= 8; $i++)
                        <option value="{{ $i }}" @selected($planSemester === $i)>Kỳ {{ $i }}</option>
                    @endfor
                </select>
            </div>
            <div class="col-6 col-md-3 col-lg-2">
                <label class="form-label-custom" for="group_code">Nhóm</label>
                <input class="form-control form-control-custom" id="group_code" name="group_code" maxlength="20"
                    placeholder="TN01" value="{{ $groupCode }}" required>
            </div>
            <div class="col-auto">
                <button class="btn btn-custom btn-custom-primary" type="submit">Xem khung</button>
            </div>
        </form>
        @if ($majors->isEmpty())
            <div class="alert-custom alert-custom-warning mt-3" role="alert">
                <i class="bi bi-exclamation-triangle alert-custom-icon" aria-hidden="true"></i>
                <span class="alert-custom-content">Chưa có chuyên ngành nào. Khai báo khung chương trình trước khi mở lớp hàng loạt.</span>
            </div>
        @endif
    </div>

    @if ($major === null || $semester === null)
        <div class="alert-custom alert-custom-info mt-3" role="alert">
            <i class="bi bi-info-circle alert-custom-icon" aria-hidden="true"></i>
            <span class="alert-custom-content">Chọn chuyên ngành rồi bấm "Xem khung" để liệt kê môn của học kỳ đào tạo.</span>
        </div>
    @elseif ($rows->isEmpty())
        <div class="alert-custom alert-custom-info mt-3" role="alert">
            <i class="bi bi-info-circle alert-custom-icon" aria-hidden="true"></i>
            <span class="alert-custom-content">
                Khung của {{ $major->name }} chưa có môn nào ở kỳ {{ $planSemester }}.
                Khai báo ở trang "Khung chương trình đào tạo".
            </span>
        </div>
    @else
        <form method="POST" action="{{ route('admin.course-offerings.open-by-plan.store') }}">
            @csrf
            <input type="hidden" name="semester_id" value="{{ $semester->id }}">
            <input type="hidden" name="major_id" value="{{ $major->id }}">
            <input type="hidden" name="plan_semester" value="{{ $planSemester }}">
            <input type="hidden" name="group_code" value="{{ $groupCode }}">

            <x-data-table :title="$major->name.' ('.$major->code.') · kỳ '.$planSemester">
                <x-slot name="controls">
                    <span class="table-pagination-info">
                        {{ $rows->count() }} môn · {{ $credits }} TC · mở thêm được {{ $openable->count() }} lớp.
                    </span>
                </x-slot>
                <thead>
                    <tr>
                        <th scope="col">Chọn</th>
                        <th scope="col">Môn học</th>
                        <th scope="col">Tín chỉ</th>
                        <th scope="col">Loại</th>
                        <th scope="col">Nhóm dự kiến</th>
                        <th scope="col">Đã có lớp kỳ này</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            <td>
                                <label class="visually-hidden" for="subject-{{ $row['plan']->subject_id }}">
                                    Mở lớp môn {{ $row['plan']->subject?->code }}
                                </label>
                                <input class="form-check-input form-check-input-custom" type="checkbox"
                                    id="subject-{{ $row['plan']->subject_id }}" name="subjects[]"
                                    value="{{ $row['plan']->subject_id }}"
                                    @checked(! $row['group_taken']) @disabled($row['group_taken'])>
                            </td>
                            <td class="table-product-name">{{ $row['plan']->subject?->label() ?? '—' }}</td>
                            <td class="table-amount">{{ $row['plan']->subject?->credits }}</td>
                            <td>{{ $row['plan']->kindLabel() }}</td>
                            <td>{{ $row['group_taken'] ? '—' : $row['group_code'] }}</td>
                            <td>
                                @if ($row['other_groups'] === [])
                                    <span class="text-muted-green">Chưa mở</span>
                                @else
                                    <x-status-badge tone="neutral">{{ implode(', ', $row['other_groups']) }}</x-status-badge>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-data-table>

            <div class="row g-3 mt-1">
                <div class="col-md-4">
                    <label class="form-label-custom" for="batch_max_students">Sĩ số tối đa</label>
                    <input class="form-control form-control-custom" id="batch_max_students" name="max_students"
                        type="number" min="0" max="65535" value="{{ old('max_students', $maxStudents) }}">
                    <div class="fs-xs text-muted-green mt-1">Nhập 0 nếu lớp không giới hạn sĩ số.</div>
                </div>
                <div class="col-md-8">
                    <div class="form-check form-check-custom">
                        <input class="form-check-input form-check-input-custom" type="checkbox" id="is_open"
                            name="is_open" value="1" @checked(old('is_open', true))>
                        <label class="form-check-label" for="is_open">Mở cho sinh viên đăng ký</label>
                    </div>
                    <div class="fs-xs text-muted-green">
                        Cổng đăng ký còn cần học kỳ đang trong khoảng thời gian nhận đăng ký.
                    </div>
                </div>
            </div>

            <button class="btn btn-custom btn-custom-primary mt-3" type="submit" @disabled($openable->isEmpty())>
                <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Mở lớp đã chọn
            </button>
        </form>
    @endif
@endsection
