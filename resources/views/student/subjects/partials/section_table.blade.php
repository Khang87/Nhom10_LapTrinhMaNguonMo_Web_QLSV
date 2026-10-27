@php
    $items = isset($courses) && $courses->isNotEmpty() 
        ? $courses 
        : (isset($sections) && $sections->isNotEmpty() 
            ? $sections->groupBy('course_id')->map(fn($s) => (object)['course' => $s->first()->course, 'sections' => $s->take(3)])->values() 
            : collect());
@endphp

<div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
            <tr class="text-secondary small">
                <th class="text-center" style="width: 50px;">STT</th>
                <th style="width: 260px;">Môn học & Tín chỉ</th>
                <th>Các Option Thời gian & Giảng viên (Tối đa 3 Option)</th>
                <th class="text-end" style="width: 130px;">Học phí</th>
                <th class="text-center" style="width: 130px;">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $index => $item)
                @php
                    $course = $item->course;
                    $options = $item->sections;
                    $fee = ($course->credits ?? 0) * ($course->tuition_fee_per_credit ?? 550000);
                    $allFull = $options->every(fn($opt) => ($opt->enrollments_count ?? 0) >= $opt->max_capacity);
                @endphp
                <tr class="subject-row">
                    <td class="text-center text-muted fw-bold align-top pt-3">{{ $index + 1 }}</td>
                    <td class="align-top pt-3">
                        <div class="fw-bold text-dark subject-name fs-6">{{ $course->course_name }}</div>
                        <div class="d-flex align-items-center gap-2 mt-1">
                            <span class="badge bg-light text-primary border font-monospace">{{ $course->course_code }}</span>
                            <span class="badge bg-light text-dark border">{{ $course->credits }} tín chỉ</span>
                        </div>
                    </td>
                    <td>
                        <form method="POST" action="{{ route('student.subjects.register', [], false) }}" id="reg_form_{{ $course->id }}">
                            @csrf
                            <input type="hidden" name="semester" value="{{ $selectedSemesterCode ?? 'HK1_2026_2027' }}">
                            <input type="hidden" name="registration_type" value="{{ $regType }}">
                            
                            <div class="d-flex flex-column gap-2 py-1">
                                @foreach ($options as $optIdx => $opt)
                                    @php
                                        $optDay = match ((int)$opt->day_of_week) {
                                            2 => 'Thứ 2',
                                            3 => 'Thứ 3',
                                            4 => 'Thứ 4',
                                            5 => 'Thứ 5',
                                            6 => 'Thứ 6',
                                            7 => 'Thứ 7',
                                            default => 'Chủ nhật',
                                        };
                                        $optFull = ($opt->enrollments_count ?? 0) >= $opt->max_capacity;
                                    @endphp
                                    <label class="form-check border rounded p-2 d-flex flex-wrap align-items-center justify-content-between mb-0 transition-all {{ $optFull ? 'bg-light text-muted opacity-75' : 'bg-white hover-shadow' }}" 
                                           style="cursor: {{ $optFull ? 'not-allowed' : 'pointer' }};"
                                           for="opt_{{ $opt->id }}">
                                        <div class="d-flex align-items-center gap-2">
                                            <input class="form-check-input mt-0" 
                                                   type="radio" 
                                                   name="section_id" 
                                                   value="{{ $opt->id }}" 
                                                   id="opt_{{ $opt->id }}" 
                                                   {{ $loop->first && !$optFull ? 'checked' : '' }} 
                                                   {{ $optFull ? 'disabled' : '' }} 
                                                   required>
                                            <div>
                                                <span class="badge bg-light text-dark border me-1">
                                                    Option {{ $optIdx + 1 }} (Lớp {{ $opt->section_code }})
                                                </span>
                                                <strong class="text-dark">
                                                    <i class="bi bi-calendar-event text-primary me-1"></i>{{ $optDay }} (Tiết {{ $opt->start_period }} - {{ $opt->end_period }})
                                                </strong>
                                                <span class="text-secondary small ms-2">
                                                    <i class="bi bi-geo-alt me-1 text-danger"></i>{{ $opt->room_name }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center gap-2 mt-1 mt-md-0">
                                            <span class="small text-dark fw-medium">
                                                <i class="bi bi-person-badge text-success me-1"></i>{{ $opt->lecturer->full_name ?? 'Chưa phân công' }}
                                            </span>
                                            <span class="badge {{ $optFull ? 'bg-danger' : 'bg-light text-dark border' }}">
                                                Sĩ số: {{ $opt->enrollments_count ?? 0 }} / {{ $opt->max_capacity }}
                                            </span>
                                            @if ($optFull)
                                                <span class="badge bg-danger">Hết chỗ</span>
                                            @endif
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </td>
                        <td class="text-end fw-semibold text-secondary align-top pt-3">
                            {{ number_format($fee, 0, ',', '.') }} đ
                        </td>
                        <td class="text-center align-top pt-3">
                            @if (!($isRegistrationActive ?? true))
                                <button type="button" class="btn btn-sm btn-secondary w-100" disabled title="Đợt đăng ký cho học kỳ này hiện đang đóng">
                                    <i class="bi bi-lock me-1"></i>Đã đóng đợt
                                </button>
                            @elseif ($allFull)
                                <button type="button" class="btn btn-sm btn-secondary w-100" disabled>Hết chỗ</button>
                            @else
                                <button type="submit" class="btn btn-sm btn-dark w-100">
                                    <i class="bi bi-check-lg me-1"></i>Đăng ký
                                </button>
                            @endif
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center py-4 text-muted">
                        <i class="bi bi-check2-circle fs-3 d-block mb-2 text-secondary"></i>
                        {{ $emptyMessage ?? 'Hiện không có lớp học phần nào để đăng ký.' }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
