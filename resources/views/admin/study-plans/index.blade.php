@extends('layouts.spark')

@section('title', 'Khung chương trình đào tạo')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h3 mb-0">Khung chương trình đào tạo</h1>
            <p class="text-muted mb-0">Môn nào thuộc chuyên ngành nào, dự kiến bố trí ở học kỳ thứ mấy.</p>
        </div>
    </div>

    @if ($majors->isEmpty())
        <div class="alert alert-warning">Chưa có chuyên ngành nào. Tạo chuyên ngành trước khi khai báo khung chương trình.</div>
        <a class="btn btn-outline-primary" href="{{ route('admin.majors.create') }}">Thêm chuyên ngành</a>
    @else
        <form class="row g-2 mb-3" method="GET" action="{{ route('admin.study-plans.index') }}"
            aria-label="Chọn chuyên ngành">
            <div class="col-sm-8 col-md-5">
                <label class="visually-hidden" for="major_id">Chuyên ngành</label>
                <select class="form-select" id="major_id" name="major_id">
                    @foreach ($majors as $option)
                        <option value="{{ $option->id }}" @selected($major->id === $option->id)>
                            {{ $option->name }} ({{ $option->code }}) — {{ $option->department?->name ?? 'Chưa có khoa' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto"><button class="btn btn-outline-primary" type="submit">Tìm kiếm</button></div>
        </form>

        <h2 class="h5 mb-2">{{ $major->name }} ({{ $major->code }})</h2>
        <p class="text-muted mb-3">
            Tổng tín chỉ: {{ $plans->sum(fn ($plan) => $plan->subject->credits) }} · {{ $plans->count() }} môn.
        </p>

        <form method="POST" action="{{ route('admin.study-plans.sync') }}">
            @csrf @method('PUT')
            <input type="hidden" name="major_id" value="{{ $major->id }}">
            <div class="table-responsive">
                <table class="table table-bordered bg-white align-middle">
                    <thead class="table-light">
                        <tr><th>Môn học</th><th>Tín chỉ</th><th>Học kỳ dự kiến</th><th>Loại</th><th>Thao tác</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($plans as $plan)
                            <tr>
                                <td>{{ $plan->subject->code }} - {{ $plan->subject->name }}</td>
                                <td>{{ $plan->subject->credits }}</td>
                                <td>
                                    <label class="visually-hidden" for="plan-{{ $plan->id }}-semester">
                                        Học kỳ của môn {{ $plan->subject->code }}
                                    </label>
                                    <input class="form-control form-control-sm" type="number" min="1" max="8"
                                        id="plan-{{ $plan->id }}-semester" name="entries[{{ $plan->id }}][plan_semester]"
                                        value="{{ old('entries.'.$plan->id.'.plan_semester', $plan->plan_semester) }}">
                                </td>
                                <td>
                                    <label class="visually-hidden" for="plan-{{ $plan->id }}-kind">
                                        Loại học phần của môn {{ $plan->subject->code }}
                                    </label>
                                    <select class="form-select form-select-sm" id="plan-{{ $plan->id }}-kind"
                                        name="entries[{{ $plan->id }}][kind]">
                                        @foreach (\App\Models\StudyPlan::KIND_LABELS as $value => $label)
                                            <option value="{{ $value }}" @selected($plan->kind === $value)>
                                                {{ $label }} ({{ $value }})
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="text-nowrap">
                                    <button class="btn btn-sm btn-outline-danger" type="submit"
                                        form="study-plan-delete-{{ $plan->id }}">Xóa</button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center">Chưa có môn nào trong khung.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($plans->isNotEmpty())
                <button class="btn btn-primary" type="submit">
                    <i class="bi bi-check-lg me-1" aria-hidden="true"></i>Cập nhật toàn khung
                </button>
            @endif
        </form>

        @foreach ($plans as $plan)
            <form id="study-plan-delete-{{ $plan->id }}" class="d-none" method="POST"
                action="{{ route('admin.study-plans.destroy', $plan) }}"
                data-confirm="Bạn có chắc muốn xóa môn {{ $plan->subject->code }} khỏi khung chương trình?">
                @csrf @method('DELETE')
            </form>
        @endforeach

        <h2 class="h5 mt-4 mb-2">Thêm môn vào khung</h2>
        @if ($availableSubjects->isEmpty())
            <p class="text-muted mb-0">Toàn bộ môn học đã có trong khung này.</p>
        @else
            <form class="row g-2 align-items-end" method="POST" action="{{ route('admin.study-plans.store') }}">
                @csrf
                <input type="hidden" name="major_id" value="{{ $major->id }}">
                <div class="col-sm-8 col-md-5">
                    <label class="form-label" for="subject_id">Môn học</label>
                    <select class="form-select" id="subject_id" name="subject_id" required>
                        @foreach ($availableSubjects as $option)
                            <option value="{{ $option->id }}">
                                {{ $option->code }} - {{ $option->name }} ({{ $option->credits }} TC)
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-4 col-md-2">
                    <label class="form-label" for="plan_semester">Học kỳ (1–8)</label>
                    <input class="form-control" id="plan_semester" name="plan_semester" type="number" min="1" max="8"
                        value="1" required>
                </div>
                <div class="col-sm-4 col-md-2">
                    <label class="form-label" for="kind">Loại</label>
                    <select class="form-select" id="kind" name="kind" required>
                        @foreach (\App\Models\StudyPlan::KIND_LABELS as $value => $label)
                            <option value="{{ $value }}">{{ $label }} ({{ $value }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Thêm môn
                    </button>
                </div>
            </form>
        @endif
    @endif
@endsection
