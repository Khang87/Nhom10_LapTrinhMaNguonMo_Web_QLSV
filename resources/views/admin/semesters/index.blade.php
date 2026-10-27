@extends('layouts.spark')

@section('title', 'Quản lý Học kỳ & Đợt Đăng ký học phần')

@section('content')
<div class="container-fluid px-0">
    <!-- Header: Tối giản, đồng bộ -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h4 fw-bold text-dark mb-1">Quản lý Học kỳ & Đợt Đăng ký học phần</h1>
            <p class="text-secondary small mb-0">Cấu hình năm học, học kỳ và thời gian mở cổng đăng ký học phần cho sinh viên</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-dark btn-sm d-inline-flex align-items-center gap-1.5 px-3 py-2" 
                    data-bs-toggle="modal" data-bs-target="#addSemesterModal">
                <i class="bi bi-plus-lg"></i>
                <span>Thêm Học kỳ mới</span>
            </button>
            <a href="{{ route('admin.course-sections.index') }}" class="btn btn-light border btn-sm text-dark d-inline-flex align-items-center gap-1.5 px-3 py-2">
                <i class="bi bi-diagram-3"></i>
                <span>Quản lý Mở lớp học phần</span>
            </a>
        </div>
    </div>

    <!-- Alert Messages -->
    @if (session('success'))
        <div class="alert alert-light border d-flex align-items-center gap-2 mb-3" role="alert">
            <i class="bi bi-check2-circle text-dark"></i>
            <div class="text-dark small">{{ session('success') }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-light border d-flex align-items-center gap-2 mb-3" role="alert">
            <i class="bi bi-exclamation-circle text-dark"></i>
            <div class="text-dark small">{{ session('error') }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Bảng danh sách các học kỳ & đợt đăng ký -->
    <div class="card border shadow-sm rounded-3 bg-white overflow-hidden">
        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
            <strong class="text-dark">Danh sách Học kỳ & Cấu hình Đợt Đăng ký ({{ $semesters->count() }})</strong>
            <span class="text-secondary small">Sinh viên chỉ có thể tự chọn ca học khi đợt đăng ký đang MỞ</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-secondary small">
                    <tr>
                        <th class="ps-3 text-center" style="width: 50px;">#</th>
                        <th style="width: 150px;">Mã Học kỳ</th>
                        <th>Tên Học kỳ</th>
                        <th style="width: 120px;">Năm học</th>
                        <th>Thời gian học kỳ</th>
                        <th>Thời gian mở ĐKHP</th>
                        <th class="text-center" style="width: 140px;">Cổng ĐKHP</th>
                        <th class="text-center" style="width: 110px;">Lớp HP mở</th>
                        <th class="text-center pe-3" style="width: 180px;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($semesters as $index => $sem)
                        @php
                            $startSem = $sem->start_date ? \Carbon\Carbon::parse($sem->start_date)->format('d/m/Y') : '-';
                            $endSem = $sem->end_date ? \Carbon\Carbon::parse($sem->end_date)->format('d/m/Y') : '-';
                            $startReg = $sem->registration_start_date ? \Carbon\Carbon::parse($sem->registration_start_date)->format('d/m/Y H:i') : '-';
                            $endReg = $sem->registration_end_date ? \Carbon\Carbon::parse($sem->registration_end_date)->format('d/m/Y H:i') : '-';
                        @endphp
                        <tr>
                            <td class="ps-3 text-center text-muted small">{{ $index + 1 }}</td>
                            <td>
                                <span class="badge bg-light text-dark border font-monospace">{{ $sem->semester_code }}</span>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $sem->semester_name }}</div>
                                <span class="text-secondary small">
                                    {{ match($sem->term_type) { 'TERM_1' => 'Học kỳ 1', 'TERM_2' => 'Học kỳ 2', 'SUMMER' => 'Học kỳ Hè', default => $sem->term_type } }}
                                </span>
                            </td>
                            <td>
                                <span class="small text-dark">{{ $sem->academic_year }}</span>
                            </td>
                            <td>
                                <div class="small text-dark">{{ $startSem }} &rarr; {{ $endSem }}</div>
                            </td>
                            <td>
                                <div class="small text-dark">{{ $startReg }}</div>
                                <div class="small text-secondary">&rarr; {{ $endReg }}</div>
                            </td>
                            <td class="text-center">
                                @if ($sem->is_registration_open)
                                    <span class="badge bg-light text-success border">
                                        ● Đang mở ĐK
                                    </span>
                                @else
                                    <span class="badge bg-light text-secondary border">
                                        ○ Đã đóng
                                    </span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('admin.course-sections.index', ['semester' => $sem->semester_code]) }}" 
                                   class="badge bg-light text-dark border text-decoration-none">
                                    {{ $sem->course_sections_count }} lớp
                                </a>
                            </td>
                            <td class="text-center pe-3">
                                <div class="d-inline-flex gap-1">
                                    <!-- Nút 1-Click Toggle Đóng/Mở Đợt ĐK -->
                                    <form method="POST" action="{{ route('admin.semesters.toggle', $sem) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-light border text-dark" 
                                                title="{{ $sem->is_registration_open ? 'Tạm đóng đợt đăng ký' : 'Mở đợt đăng ký ngay' }}">
                                            <i class="bi {{ $sem->is_registration_open ? 'bi-lock' : 'bi-unlock' }}"></i>
                                            <span>{{ $sem->is_registration_open ? 'Đóng' : 'Mở' }}</span>
                                        </button>
                                    </form>

                                    <!-- Nút Sửa thời gian -->
                                    <button type="button" class="btn btn-sm btn-light border text-dark" 
                                            data-bs-toggle="modal" data-bs-target="#editSemesterModal-{{ $sem->id }}"
                                            title="Sửa thời gian học kỳ">
                                        <i class="bi bi-pencil"></i>
                                        <span>Sửa</span>
                                    </button>

                                    <!-- Modal Sửa thời gian học kỳ (Per-row) -->
                                    <div class="modal fade text-start" id="editSemesterModal-{{ $sem->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content border shadow-sm rounded-3">
                                                <form method="POST" action="{{ route('admin.semesters.update', $sem) }}">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="modal-header border-bottom py-3">
                                                        <h5 class="modal-title fw-bold text-dark fs-6">Sửa thời gian: {{ $sem->semester_name }}</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body p-4">
                                                        <div class="border-bottom pb-2 mb-3">
                                                            <strong class="text-dark small text-uppercase">1. Thời gian học kỳ</strong>
                                                        </div>
                                                        <div class="row g-3 mb-3">
                                                            <div class="col-md-6">
                                                                <label class="form-label small fw-semibold text-secondary">Ngày bắt đầu HK</label>
                                                                <input type="date" class="form-control form-control-sm" name="start_date" 
                                                                       value="{{ $sem->start_date ? \Carbon\Carbon::parse($sem->start_date)->format('Y-m-d') : '' }}" required>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label small fw-semibold text-secondary">Ngày kết thúc HK</label>
                                                                <input type="date" class="form-control form-control-sm" name="end_date" 
                                                                       value="{{ $sem->end_date ? \Carbon\Carbon::parse($sem->end_date)->format('Y-m-d') : '' }}" required>
                                                            </div>
                                                        </div>

                                                        <div class="border-bottom pb-2 mb-3">
                                                            <strong class="text-dark small text-uppercase">2. Thời gian đợt đăng ký học phần</strong>
                                                        </div>
                                                        <div class="row g-3">
                                                            <div class="col-md-6">
                                                                <label class="form-label small fw-semibold text-secondary">Bắt đầu mở ĐK</label>
                                                                <input type="date" class="form-control form-control-sm" name="registration_start_date" 
                                                                       value="{{ $sem->registration_start_date ? \Carbon\Carbon::parse($sem->registration_start_date)->format('Y-m-d') : '' }}" required>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label small fw-semibold text-secondary">Kết thúc ĐK</label>
                                                                <input type="date" class="form-control form-control-sm" name="registration_end_date" 
                                                                       value="{{ $sem->registration_end_date ? \Carbon\Carbon::parse($sem->registration_end_date)->format('Y-m-d') : '' }}" required>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer border-top py-2.5 bg-light">
                                                        <button type="button" class="btn btn-sm btn-light border text-dark" data-bs-dismiss="modal">Hủy</button>
                                                        <button type="submit" class="btn btn-sm btn-dark px-3">Lưu cập nhật</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted small">
                                <i class="bi bi-calendar-x fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                Chưa có học kỳ nào được cấu hình trong hệ thống.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Thêm Học Kỳ Mới (Thiết kế phẳng, tối giản) -->
<div class="modal fade" id="addSemesterModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border shadow-sm rounded-3">
            <form method="POST" action="{{ route('admin.semesters.store') }}">
                @csrf
                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-title fw-bold text-dark fs-6">Thêm Học kỳ & Cấu hình Đợt ĐKHP mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-light border p-3 rounded-3 mb-4">
                        <div class="small text-secondary">
                            <strong class="text-dark d-block mb-1"><i class="bi bi-info-circle me-1"></i>Quy tắc tự động tính ngày mở đợt ĐKHP:</strong>
                            Hệ thống sẽ <strong>tự động mở đợt đăng ký trước 2 tháng</strong> so với ngày bắt đầu học kỳ nếu bạn để trống thời gian đăng ký.
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Mã Học kỳ <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm font-monospace" name="semester_code" placeholder="VD: HK1_2026_2027" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold text-secondary">Tên Học kỳ hiển thị <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" name="semester_name" placeholder="VD: Học kỳ 1 Năm học 2026-2027" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold text-secondary">Năm học <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" name="academic_year" placeholder="VD: 2026-2027" value="{{ date('Y') . '-' . (date('Y') + 1) }}" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Loại kỳ học <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" name="term_type" required>
                                <option value="TERM_1">Học kỳ 1 (Thu - Đông)</option>
                                <option value="TERM_2">Học kỳ 2 (Xuân - Hè)</option>
                                <option value="SUMMER">Học kỳ Hè</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Ngày bắt đầu học kỳ <span class="text-danger">*</span></label>
                            <input type="date" class="form-control form-control-sm" name="start_date" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Ngày kết thúc học kỳ <span class="text-danger">*</span></label>
                            <input type="date" class="form-control form-control-sm" name="end_date" required>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Thời gian bắt đầu ĐKHP</label>
                            <input type="datetime-local" class="form-control form-control-sm" name="registration_start_date">
                            <div class="form-text small">Để trống để tự động tính trước 2 tháng.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Thời gian kết thúc ĐKHP</label>
                            <input type="datetime-local" class="form-control form-control-sm" name="registration_end_date">
                            <div class="form-text small">Để trống để tự động lấy trước 1 ngày học kỳ bắt đầu.</div>
                        </div>
                    </div>
                    <input type="hidden" name="order_index" value="{{ $semesters->count() + 1 }}">
                </div>
                <div class="modal-footer border-top py-2.5 bg-light">
                    <button type="button" class="btn btn-sm btn-light border text-dark" data-bs-dismiss="modal">Hủy bỏ</button>
                    <button type="submit" class="btn btn-sm btn-dark px-3">Tạo học kỳ mới</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
