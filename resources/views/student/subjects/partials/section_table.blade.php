@php
    $items = isset($courses) && $courses->isNotEmpty() 
        ? $courses 
        : (isset($sections) && $sections->isNotEmpty() 
            ? $sections->groupBy('course_id')->map(fn($s) => (object)['course' => $s->first()->course, 'sections' => $s->take(3)])->values() 
            : collect());
@endphp

<div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light text-secondary small">
            <tr>
                <th class="ps-3 text-center" style="width: 50px;">#</th>
                <th style="width: 250px;">Học phần & Tín chỉ</th>
                <th>Các Ca học / Lớp học phần mở (Tối đa 3 Ca học)</th>
                <th class="text-end" style="width: 130px;">Học phí</th>
                <th class="text-center pe-3" style="width: 130px;">Thao tác</th>
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
                    <td class="ps-3 text-center text-muted small align-top pt-3">{{ $index + 1 }}</td>
                    <td class="align-top pt-3">
                        <div class="fw-semibold text-dark subject-name">{{ $course->course_name }}</div>
                        <div class="d-flex flex-wrap align-items-center gap-1.5 mt-1">
                            <span class="badge bg-light text-dark border font-monospace">{{ $course->course_code }}</span>
                            <span class="badge bg-light text-dark border">{{ $course->credits }} TC</span>
                            @if (isset($curriculumOrderMap[$course->id]))
                                <span class="badge bg-light text-primary border border-primary-subtle" title="Môn thuộc học kỳ theo chương trình khung đào tạo">
                                    Kỳ {{ $curriculumOrderMap[$course->id] }} khung
                                </span>
                            @endif
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
                                    <label class="border rounded-3 p-2.5 d-flex flex-wrap align-items-center justify-content-between mb-0 transition-all {{ $optFull ? 'bg-light text-muted opacity-75' : 'bg-white' }}" 
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
                                                <span class="badge bg-light text-dark border me-1 font-monospace">
                                                    {{ $opt->section_code }}
                                                </span>
                                                <strong class="text-dark small">
                                                    <i class="bi bi-calendar-event me-1"></i>{{ $optDay }} (Tiết {{ $opt->start_period }} - {{ $opt->end_period }})
                                                </strong>
                                                <span class="text-secondary small ms-2">
                                                    <i class="bi bi-geo-alt me-0.5"></i>{{ $opt->room_name }}
                                                </span>
                                                <div class="text-secondary small mt-0.5" style="font-size: 11px;">
                                                    <i class="bi bi-calendar-range me-1"></i>Thời gian: {{ $opt->getDateRangeText() }}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center gap-2 mt-1 mt-md-0">
                                            <span class="small text-secondary">
                                                <i class="bi bi-person-badge me-0.5"></i>{{ $opt->lecturer->full_name ?? 'Chưa phân công' }}
                                            </span>
                                            <span class="badge bg-light text-dark border small">
                                                {{ $opt->enrollments_count ?? 0 }} / {{ $opt->max_capacity }}
                                            </span>
                                            @if ($optFull)
                                                <span class="badge bg-light text-danger border border-danger-subtle small">Hết chỗ</span>
                                            @endif
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                    </td>
                    <td class="text-end fw-semibold text-dark small align-top pt-3">
                        {{ number_format($fee, 0, ',', '.') }} đ
                    </td>
                    <td class="text-center pe-3 align-top pt-3">
                        @if (!($isRegistrationActive ?? true))
                            <button type="button" class="btn btn-sm btn-light border text-secondary w-100 py-1.5" disabled title="Đợt đăng ký cho học kỳ này hiện đang đóng">
                                <i class="bi bi-lock me-1"></i>Đã đóng
                            </button>
                        @elseif ($allFull)
                            <button type="button" class="btn btn-sm btn-light border text-danger w-100 py-1.5" disabled>Hết chỗ</button>
                        @else
                            <button type="submit" class="btn btn-sm btn-dark w-100 py-1.5 d-inline-flex align-items-center justify-content-center gap-1">
                                <i class="bi bi-check-lg"></i>
                                <span>Đăng ký</span>
                            </button>
                        @endif
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center py-4 text-secondary small">
                        <i class="bi bi-inbox fs-3 d-block mb-2 text-secondary opacity-50"></i>
                        {{ $emptyMessage ?? 'Hiện không có lớp học phần nào để đăng ký.' }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
