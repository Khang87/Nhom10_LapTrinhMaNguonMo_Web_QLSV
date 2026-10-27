@extends('layouts.spark')

@section('title', 'Quản lý đăng ký học phần')

@section('content')
    <x-page-header title="Quản lý đăng ký học phần"
        subtitle="Mỗi đăng ký đều ghi lại người thực hiện. Hủy hộ không xóa điểm hay điểm danh đã có.">
        <x-slot name="actions">
            <a class="btn btn-custom btn-custom-outline-primary" href="{{ route('admin.enrollments.export') }}">
                <i class="bi bi-download me-1" aria-hidden="true"></i>Tải CSV
            </a>
        </x-slot>
    </x-page-header>

    <div class="card">
        <form class="row g-3 align-items-end" method="GET" action="{{ route('admin.enrollments.index') }}">
            <div class="col-sm-8 col-md-4">
                <label class="visually-hidden" for="filter_semester_id">Lọc theo học kỳ</label>
                <select class="form-select form-select-custom" id="filter_semester_id" name="semester_id">
                    <option value="">Mọi học kỳ</option>
                    @foreach ($semesters as $semester)
                        <option value="{{ $semester->id }}" @selected($semesterId === $semester->id)>
                            {{ $semester->label }}{{ $semester->is_active ? ' (hiện hành)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-8 col-md-4">
                <label class="visually-hidden" for="filter_user_id">Lọc theo sinh viên</label>
                <select class="form-select form-select-custom" id="filter_user_id" name="user_id">
                    <option value="">Mọi sinh viên</option>
                    @foreach ($students as $student)
                        <option value="{{ $student->id }}" @selected(request('user_id') == $student->id)>
                            {{ $student->name }} ({{ $student->person_code }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto"><button class="btn btn-custom btn-custom-primary" type="submit">Tìm kiếm</button></div>
        </form>
    </div>

    <div class="card">
        <form class="row g-3 align-items-end" method="POST" action="{{ route('admin.enrollments.store') }}">
            @csrf
            <div class="col-md-4">
                <label class="form-label-custom" for="enroll_user_id">Sinh viên</label>
                <select class="form-select form-select-custom" id="enroll_user_id" name="user_id" required>
                    <option value="">Chọn sinh viên</option>
                    @foreach ($students as $student)
                        <option value="{{ $student->id }}" @selected(old('user_id') == $student->id)>
                            {{ $student->name }} ({{ $student->person_code }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label-custom" for="enroll_course_offering_id">Lớp học phần</label>
                <select class="form-select form-select-custom" id="enroll_course_offering_id" name="course_offering_id" required>
                    <option value="">Chọn lớp học phần</option>
                    @foreach ($offerings as $offering)
                        <option value="{{ $offering->id }}" @selected(old('course_offering_id') == $offering->id)>
                            {{ $offering->label() }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-custom btn-custom-primary w-100" type="submit">
                    <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Đăng ký hộ
                </button>
            </div>
        </form>
    </div>

    <x-data-table>
        <thead>
            <tr>
                <th scope="col">STT</th>
                <th scope="col">Sinh viên</th>
                <th scope="col">Môn học</th>
                <th scope="col">Nhóm</th>
                <th scope="col">Học kỳ</th>
                <th scope="col">Trạng thái</th>
                <th scope="col">Người thực hiện</th>
                <th scope="col">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($enrollments as $enrollment)
                <tr>
                    <td>{{ $enrollments->firstItem() + $loop->index }}</td>
                    <td>
                        <x-person-cell :name="$enrollment->user->name" :sub="$enrollment->user->person_code" :avatar="$enrollment->user->avatarUrl()" />
                    </td>
                    <td class="table-product-name">{{ $enrollment->offering->subject?->label() ?? '—' }}</td>
                    <td>{{ $enrollment->offering->group_code }}</td>
                    <td>{{ $enrollment->offering->semester?->slug ?? '—' }}</td>
                    <td>
                        <x-status-badge :tone="$enrollment->isActive() ? 'success' : 'neutral'">
                            {{ $enrollment->statusLabel() }}
                        </x-status-badge>
                        @if (! $enrollment->isActive() && $enrollment->dropped_at)
                            <div class="fs-xs text-muted-green">{{ $enrollment->dropped_at->format('d/m/Y H:i') }}</div>
                        @endif
                    </td>
                    <td>{{ $enrollment->registeredByLabel() }}</td>
                    <td class="text-nowrap">
                        @if ($enrollment->isActive())
                            <form class="d-inline" method="POST"
                                action="{{ route('admin.enrollments.drop', $enrollment) }}"
                                data-confirm="Hủy đăng ký {{ $enrollment->offering->label() }} của {{ $enrollment->user->name }}?">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-custom btn-custom-sm btn-custom-outline-danger" type="submit">Hủy đăng ký</button>
                            </form>
                        @else
                            <span class="text-muted-green">—</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center py-5">
                        <div class="empty-state-icon bi bi-inbox" aria-hidden="true"></div>
                        <div class="table-user-sub">Chưa có đăng ký học phần nào.</div>
                    </td>
                </tr>
            @endforelse
        </tbody>
        <x-slot name="footer">
            {{ $enrollments->links() }}
        </x-slot>
    </x-data-table>
@endsection
