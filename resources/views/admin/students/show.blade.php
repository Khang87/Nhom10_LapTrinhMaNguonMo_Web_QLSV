@extends('layouts.spark')
@section('title', 'Hồ sơ sinh viên')
@section('content')
    @php
        $totalSessions = $student->attendances->count();
        $attendanceRate = $review['attendance_rate'];
        $code = fn (mixed $value): string => $value ? (string) $value : 'Chưa cập nhật';
        $date = fn (?Illuminate\Support\Carbon $value): string => $value?->format('d/m/Y') ?? 'Chưa cập nhật';
    @endphp

    <x-page-header title="Hồ sơ sinh viên">
        <x-slot name="actions">
            <a class="btn btn-custom btn-custom-primary" href="{{ route('admin.students.edit', $student) }}">
                <i class="bi bi-pencil" aria-hidden="true"></i>Sửa
            </a>
            <a class="btn btn-custom btn-custom-light" href="{{ route('admin.students.index') }}">Quay lại</a>
        </x-slot>
    </x-page-header>

    <div class="card">
        <div class="table-user-cell mb-0">
            <img class="table-user-avatar table-user-avatar-lg" alt="Ảnh đại diện của {{ $student->name }}"
                src="{{ e($student->avatarUrl()) }}">
            <div>
                <div class="table-user-name">{{ $student->name }}</div>
                <div class="table-user-sub">
                    {{ $code($student->student_code) }}
                    @if ($student->studentClass)
                        · {{ $student->studentClass->label() }}
                    @else
                        · chưa xếp lớp
                    @endif
                    @if ($student->phone)
                        · {{ $student->phone }}
                    @endif
                </div>
                <x-status-badge tone="{{ $student->academic_status === 'STUDYING' ? 'info' : ($student->academic_status === 'GRADUATED' ? 'success' : 'pending') }}">
                    {{ $student->academic_status_label }} ({{ $student->academic_status }})
                </x-status-badge>
                @if ($student->academic_status_updated_at)
                    <span class="fs-xs text-muted-green">· xét duyệt ngày
                        {{ $student->academic_status_updated_at->format('d/m/Y H:i') }}</span>
                @endif
            </div>
        </div>
    </div>

    <div class="row g-3 mt-0">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <h2 class="card-title">Hồ sơ đào tạo</h2>
                </div>
                <dl class="row mb-0">
                    <dt class="col-5 fs-xs text-muted-green">Mã sinh viên</dt>
                    <dd class="col-7 mb-2">{{ $code($student->student_code) }}</dd>
                    <dt class="col-5 fs-xs text-muted-green">Lớp hành chính</dt>
                    <dd class="col-7 mb-2">{{ $student->studentClass?->class_code ?? 'Chưa xếp lớp' }}</dd>
                    <dt class="col-5 fs-xs text-muted-green">Tên lớp</dt>
                    <dd class="col-7 mb-2">{{ $student->studentClass?->class_name ?: 'Chưa cập nhật' }}</dd>
                    <dt class="col-5 fs-xs text-muted-green">Khóa</dt>
                    <dd class="col-7 mb-2">
                        {{ $student->studentClass?->intake_year ? 'Khóa '.$student->studentClass->intake_year : '—' }}
                    </dd>
                    <dt class="col-5 fs-xs text-muted-green">Khoa</dt>
                    <dd class="col-7 mb-2">
                        @if ($student->studentClass?->department)
                            {{ $student->studentClass->department->name }}
                            ({{ $student->studentClass->department->code }})
                        @else
                            Chưa phân loại
                        @endif
                    </dd>
                    <dt class="col-5 fs-xs text-muted-green">Chuyên ngành</dt>
                    <dd class="col-7 mb-2">
                        @if ($student->studentClass?->major)
                            {{ $student->studentClass->major->name }}
                            ({{ $student->studentClass->major->code }})
                        @else
                            Chưa phân loại
                        @endif
                    </dd>
                    <dt class="col-5 fs-xs text-muted-green">Bậc đào tạo</dt>
                    <dd class="col-7 mb-2">{{ $code($student->education_level) }}</dd>
                    <dt class="col-5 fs-xs text-muted-green">Loại hình</dt>
                    <dd class="col-7 mb-2">{{ $code($student->training_type) }}</dd>
                    <dt class="col-5 fs-xs text-muted-green">Ngày nhập học</dt>
                    <dd class="col-7 mb-2">{{ $date($student->enrolled_on) }}</dd>
                    <dt class="col-5 fs-xs text-muted-green">Tình trạng</dt>
                    <dd class="col-7 mb-0">
                        {{ $student->academic_status_label }}
                        <span class="fs-xs text-muted-green">({{ $student->academic_status }})</span>
                    </dd>
                </dl>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <h2 class="card-title">Cá nhân &amp; liên hệ</h2>
                </div>
                <dl class="row mb-0">
                    <dt class="col-5 fs-xs text-muted-green">Họ và tên</dt>
                    <dd class="col-7 mb-2">{{ $student->name }}</dd>
                    <dt class="col-5 fs-xs text-muted-green">Ngày sinh</dt>
                    <dd class="col-7 mb-2">{{ $date($student->dob) }}</dd>
                    <dt class="col-5 fs-xs text-muted-green">Giới tính</dt>
                    <dd class="col-7 mb-2">
                        {{ $student->gender ? $student->gender_label.' ('.$student->gender.')' : 'Chưa cập nhật' }}
                    </dd>
                    <dt class="col-5 fs-xs text-muted-green">Dân tộc</dt>
                    <dd class="col-7 mb-2">{{ $code($student->ethnicity) }}</dd>
                    <dt class="col-5 fs-xs text-muted-green">Tôn giáo</dt>
                    <dd class="col-7 mb-2">{{ $code($student->religion) }}</dd>
                    <dt class="col-5 fs-xs text-muted-green">Quốc tịch</dt>
                    <dd class="col-7 mb-2">{{ $code($student->nationality) }}</dd>
                    <dt class="col-5 fs-xs text-muted-green">Email</dt>
                    <dd class="col-7 mb-2">
                        <a href="mailto:{{ $student->email }}">{{ $student->email }}</a>
                    </dd>
                    <dt class="col-5 fs-xs text-muted-green">Số điện thoại</dt>
                    <dd class="col-7 mb-2">{{ $code($student->phone) }}</dd>
                    <dt class="col-12 fs-xs text-muted-green mt-1">Nơi sinh</dt>
                    <dd class="col-12 mb-2">{{ $code($student->birth_place) }}</dd>
                    <dt class="col-12 fs-xs text-muted-green">Quê quán</dt>
                    <dd class="col-12 mb-2">{{ $code($student->hometown) }}</dd>
                    <dt class="col-12 fs-xs text-muted-green">Hộ khẩu thường trú</dt>
                    <dd class="col-12 mb-2">{{ $code($student->permanent_residence) }}</dd>
                    <dt class="col-12 fs-xs text-muted-green">Địa chỉ liên hệ</dt>
                    <dd class="col-12 mb-0">{{ $code($student->contact_address) }}</dd>
                </dl>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <h2 class="card-title">Giấy tờ &amp; diện ưu tiên</h2>
                </div>
                <dl class="row mb-0">
                    <dt class="col-6 fs-xs text-muted-green">Số CCCD / định danh</dt>
                    <dd class="col-6 mb-2">{{ $code($student->id_card_number) }}</dd>
                    <dt class="col-6 fs-xs text-muted-green">Ngày cấp</dt>
                    <dd class="col-6 mb-2">{{ $date($student->id_card_issued_at) }}</dd>
                    <dt class="col-6 fs-xs text-muted-green">Nơi cấp</dt>
                    <dd class="col-6 mb-2">{{ $code($student->id_card_issued_place) }}</dd>
                    <dt class="col-6 fs-xs text-muted-green">Khu vực tuyển sinh</dt>
                    <dd class="col-6 mb-2">{{ $code($student->admission_region) }}</dd>
                    <dt class="col-6 fs-xs text-muted-green">Diện ưu tiên</dt>
                    <dd class="col-6 mb-0">{{ $code($student->priority_category) }}</dd>
                </dl>
                <p class="fs-xs text-muted-green mb-0 mt-2">
                    Hồ sơ in từ trang này đủ các mục của phiếu lý lịch sinh viên; ô trống nghĩa là
                    chưa thu thập, không phải dữ liệu sai.
                </p>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-2">
        <div class="col-6 col-lg-3">
            <x-stat-card label="GPA hệ 4" :value="$review['gpa'] === null ? '—' : number_format($review['gpa'], 2)"
                :hint="$review['classification']" />
        </div>
        <div class="col-6 col-lg-3">
            <x-stat-card label="Điểm TB hệ 10"
                :value="$review['average_ten'] === null ? '—' : number_format($review['average_ten'], 2)" :hint="$review['failing_subjects'].' môn F'" />
        </div>
        <div class="col-6 col-lg-3">
            <x-stat-card label="Tín chỉ tích lũy"
                :value="$review['credits_accumulated'].' / '.($review['credits_required'] ?? '—').' TC'" />
        </div>
        <div class="col-6 col-lg-3">
            <x-stat-card label="Tỷ lệ chuyên cần" :value="$attendanceRate === null ? '—' : $attendanceRate.'%'"
                :hint="$attendanceRate === null ? null : 'Có mặt '.$presentCount.'/'.$totalSessions.' buổi'" />
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <x-data-table title="Lớp học phần đã đăng ký">
                <thead>
                    <tr>
                        <th scope="col">Môn học</th>
                        <th scope="col">Nhóm</th>
                        <th scope="col">Học kỳ</th>
                        <th scope="col">Tín chỉ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($student->offerings
                        ->filter(fn ($offering) => $offering->pivot->status === 'ACTIVE')
                        ->sortBy(fn ($offering) => [$offering->subject?->code, $offering->group_code]) as $offering)
                        <tr>
                            <td class="table-product-name">{{ $offering->subject?->label() }}</td>
                            <td>{{ $offering->group_code }}</td>
                            <td>{{ $offering->semester?->slug ?? '—' }}</td>
                            <td>{{ $offering->subject?->credits }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-5">
                                <div class="empty-state-icon bi bi-inbox" aria-hidden="true"></div>
                                <div class="table-user-sub">Chưa đăng ký học phần nào.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </x-data-table>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <h2 class="card-title">Tỷ lệ chuyên cần</h2>
                </div>
                @if ($attendanceRate === null)
                    <p class="mb-0 text-muted-green">Chưa có dữ liệu điểm danh.</p>
                @else
                    <p class="mb-2">
                        Có mặt {{ $presentCount }}/{{ $totalSessions }} buổi =
                        <strong>{{ $attendanceRate }}%</strong>.
                    </p>
                    <div class="progress" role="progressbar" aria-label="Tỷ lệ chuyên cần"
                        aria-valuenow="{{ $attendanceRate }}" aria-valuemin="0" aria-valuemax="100" style="height:12px">
                        <div class="progress-bar {{ \App\Support\AttendanceRate::isBelowThreshold($attendanceRate) ? 'bg-warning' : 'bg-success' }}"
                            style="width: {{ $attendanceRate }}%"></div>
                    </div>
                    <div class="fs-xs text-muted-green mt-2">Tỷ lệ = số buổi có mặt / tổng số buổi ghi nhận.</div>
                @endif
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Xét duyệt học vụ</h2>
                </div>
                <p class="mb-2">
                    GPA hệ 4 = {{ $review['gpa'] === null ? '—' : number_format($review['gpa'], 2) }},
                    hệ 10 = {{ $review['average_ten'] === null ? '—' : number_format($review['average_ten'], 2) }},
                    tín chỉ tích lũy = {{ $review['credits_accumulated'] }}/{{ $review['credits_required'] ?? '—' }} TC,
                    môn F = {{ $review['failing_subjects'] }}.
                </p>
                <p class="mb-2">
                    <x-status-badge tone="info">
                        Gợi ý: {{ App\Support\PersonLabels::option($review['suggestion']['status'], App\Support\PersonLabels::ACADEMIC_STATUSES) }}
                    </x-status-badge>
                </p>
                <ul class="mb-3">
                    @foreach ($review['suggestion']['reasons'] as $reason)
                        <li>{{ $reason }}</li>
                    @endforeach
                </ul>
                <form class="row g-2 align-items-end" method="POST"
                    action="{{ route('admin.students.academic-status.update', $student) }}">
                    @csrf
                    <div class="col-sm-6 col-md-4">
                        <label class="form-label-custom" for="academic_status">Tình trạng sau xét duyệt</label>
                        <select class="form-select form-select-custom" id="academic_status" name="academic_status" required>
                            @foreach (App\Support\PersonLabels::ACADEMIC_STATUSES as $code => $label)
                                <option value="{{ $code }}"
                                    @selected(old('academic_status', $student->academic_status) === $code)>
                                    {{ App\Support\PersonLabels::option($code, App\Support\PersonLabels::ACADEMIC_STATUSES) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-custom btn-custom-primary" type="submit">
                            <i class="bi bi-check-lg" aria-hidden="true"></i>Xét duyệt
                        </button>
                    </div>
                    @error('academic_status')
                        <div class="col-12"><div class="form-feedback-custom invalid-custom">{{ $message }}</div></div>
                    @enderror
                </form>
                <div class="fs-xs text-muted-green mt-2">
                    Hệ thống chỉ gợi ý theo bảng điểm và điểm danh; quyết định lên lớp hay tốt nghiệp thuộc hội
                    đồng đào tạo, nên nút này ghi đúng tình trạng mà quản trị viên chọn.
                </div>
            </div>
        </div>

        <div class="col-12">
            <x-data-table title="Kết quả học tập">
                <thead>
                    <tr>
                        <th scope="col">Môn học</th>
                        <th scope="col">Nhóm</th>
                        @foreach (App\Support\GradingScale::components() as $component)
                            <th scope="col">{{ $component['label'] }} ({{ $component['weight'] }}%)</th>
                        @endforeach
                        <th scope="col">Tổng kết</th>
                        <th scope="col">Chữ</th>
                        <th scope="col">Hệ 4</th>
                        <th scope="col">Trạng thái lớp</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $letterTone = fn (string $letter): string => match ($letter) {
                            'A' => 'success',
                            'B' => 'info',
                            'C' => 'pending',
                            'D' => 'neutral',
                            default => 'failed',
                        };
                    @endphp
                    @forelse ($student->grades->sortBy(fn ($grade) => $grade->subject?->code) as $grade)
                        <tr>
                            <td class="table-product-name">{{ $grade->subject?->code }} - {{ $grade->subject?->name }}</td>
                            <td>{{ $grade->offering?->group_code }}</td>
                            @foreach (App\Support\GradingScale::components() as $component)
                                <td>{{ $grade->{$component['key']} !== null ? number_format((float) $grade->{$component['key']}, 2) : '—' }}</td>
                            @endforeach
                            <td class="table-amount">{{ $grade->subjectScore() === null ? '—' : number_format($grade->subjectScore(), 2) }}</td>
                            <td>
                                @if ($grade->subjectScore() === null)
                                    —
                                @else
                                    <x-status-badge :tone="$letterTone($grade->letter())">{{ $grade->letter() }}</x-status-badge>
                                @endif
                            </td>
                            <td>{{ $grade->subjectScore() === null ? '—' : number_format($grade->fourScore(), 2) }}</td>
                            <td>
                                <x-status-badge :tone="$grade->is_published ? 'success' : 'pending'">
                                    {{ $grade->is_published ? 'Đã công bố' : 'Chưa công bố' }}
                                </x-status-badge>
                                @if ($grade->enrollment)
                                    <span class="fs-xs text-muted-green">{{ $grade->enrollment->statusLabel() }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-5">
                                <div class="empty-state-icon bi bi-inbox" aria-hidden="true"></div>
                                <div class="table-user-sub">Chưa có điểm.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </x-data-table>
        </div>
    </div>
@endsection
