@extends('layouts.spark')

@section('title', 'Thông tin sinh viên - HUIT')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h4 mb-1 fw-bold text-dark">Thông tin sinh viên</h1>
            <p class="text-secondary small mb-0">Hồ sơ học vấn và lý lịch cá nhân chi tiết</p>
        </div>
        <span class="badge bg-success px-3 py-2 fs-6">
            <i class="bi bi-check-circle"></i> {{ $student->study_status ?? 'Đang học' }}
        </span>
    </div>

    {{-- Thẻ tóm tắt thông tin đầu trang --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="row align-items-center">
                <div class="col-auto">
                    <div class="avatar-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold fs-3" 
                        style="width: 72px; height: 72px; border-radius: 50%;">
                        {{ mb_substr($student->full_name ?? $user->name, 0, 1) }}
                    </div>
                </div>
                <div class="col">
                    <h2 class="h5 mb-1 fw-bold">{{ $student->full_name ?? $user->name }}</h2>
                    <div class="text-secondary small mb-1">
                        MSSV: <strong class="text-dark">{{ $student->student_code ?? '2001230283' }}</strong> 
                        | Lớp: <strong class="text-primary">{{ $student->class_code ?? '14DHTH04' }}</strong>
                        | Khóa: <strong class="text-dark">{{ $student->cohort_year ?? 2023 }}</strong>
                    </div>
                    <div class="text-muted small">
                        {{ $student->faculty_name ?? 'Khoa Công nghệ Thông tin' }} - Chuyên ngành {{ $student->specialization_name ?? 'Công nghệ phần mềm' }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Bố cục 2 cột (Học vấn & Cá nhân) --}}
    <div class="row g-4">
        {{-- Cột 1: Thông tin học vấn --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-light border-0 py-3">
                    <h3 class="h6 mb-0 fw-bold text-primary">
                        <i class="bi bi-mortarboard-fill me-2"></i>THÔNG TIN HỌC VẤN
                    </h3>
                </div>
                <div class="card-body p-4">
                    <table class="table table-sm table-borderless info-table mb-0">
                        <tbody>
                            <tr>
                                <th class="text-secondary" style="width: 40%;">Mã sinh viên:</th>
                                <td class="fw-bold">{{ $student->student_code ?? '2001230283' }}</td>
                            </tr>
                            <tr>
                                <th class="text-secondary">Mã hồ sơ:</th>
                                <td>{{ $student->profile_code ?? '2001230283' }}</td>
                            </tr>
                            <tr>
                                <th class="text-secondary">Ngày vào trường:</th>
                                <td>{{ $student->enrollment_date ? $student->enrollment_date->format('d/m/Y') : '25/08/2023' }}</td>
                            </tr>
                            <tr>
                                <th class="text-secondary">Lớp học:</th>
                                <td class="fw-bold text-primary">{{ $student->class_code ?? '14DHTH04' }}</td>
                            </tr>
                            <tr>
                                <th class="text-secondary">Cơ sở:</th>
                                <td>{{ $student->campus ?? 'ĐHCT TP.HCM' }}</td>
                            </tr>
                            <tr>
                                <th class="text-secondary">Bậc đào tạo:</th>
                                <td>{{ $student->education_level ?? 'Đại học' }}</td>
                            </tr>
                            <tr>
                                <th class="text-secondary">Loại hình đào tạo:</th>
                                <td>{{ $student->training_type ?? 'Chính quy' }}</td>
                            </tr>
                            <tr>
                                <th class="text-secondary">Khoa:</th>
                                <td class="fw-semibold">{{ $student->faculty_name ?? 'Khoa Công nghệ Thông tin' }}</td>
                            </tr>
                            <tr>
                                <th class="text-secondary">Ngành:</th>
                                <td>{{ $student->major_name ?? 'Công nghệ thông tin' }}</td>
                            </tr>
                            <tr>
                                <th class="text-secondary">Chuyên ngành:</th>
                                <td class="fw-semibold">{{ $student->specialization_name ?? 'Công nghệ phần mềm' }}</td>
                            </tr>
                            <tr>
                                <th class="text-secondary">Khóa học:</th>
                                <td>{{ $student->cohort_year ?? 2023 }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Cột 2: Thông tin cá nhân & Chỉnh sửa liên hệ --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-light border-0 py-3">
                    <h3 class="h6 mb-0 fw-bold text-primary">
                        <i class="bi bi-person-lines-fill me-2"></i>THÔNG TIN CÁ NHÂN
                    </h3>
                </div>
                <div class="card-body p-4">
                    <table class="table table-sm table-borderless info-table mb-4">
                        <tbody>
                            <tr>
                                <th class="text-secondary" style="width: 40%;">Ngày sinh:</th>
                                <td>{{ $student->dob ? $student->dob->format('d/m/Y') : '04/03/2005' }}</td>
                            </tr>
                            <tr>
                                <th class="text-secondary">Giới tính:</th>
                                <td>{{ $student->gender ?? 'Nam' }}</td>
                            </tr>
                            <tr>
                                <th class="text-secondary">Dân tộc:</th>
                                <td>{{ $student->ethnicity ?? 'Kinh' }}</td>
                            </tr>
                            <tr>
                                <th class="text-secondary">Tôn giáo:</th>
                                <td>{{ $student->religion ?? 'Không' }}</td>
                            </tr>
                            <tr>
                                <th class="text-secondary">Quốc tịch:</th>
                                <td>{{ $student->nationality ?? 'Việt Nam' }}</td>
                            </tr>
                            <tr>
                                <th class="text-secondary">Số CCCD:</th>
                                <td class="fw-bold">{{ $student->cccd_number ?? '095205003219' }}</td>
                            </tr>
                            <tr>
                                <th class="text-secondary">Ngày cấp:</th>
                                <td>{{ $student->cccd_issue_date ? $student->cccd_issue_date->format('d/m/Y') : '27/08/2021' }}</td>
                            </tr>
                            <tr>
                                <th class="text-secondary">Nơi cấp:</th>
                                <td>{{ $student->cccd_issue_place ?? 'Cà Mau' }}</td>
                            </tr>
                            <tr>
                                <th class="text-secondary">Nơi sinh:</th>
                                <td>{{ $student->birth_place ?? 'Cà Mau' }}</td>
                            </tr>
                            <tr>
                                <th class="text-secondary">Hộ khẩu thường trú:</th>
                                <td>{{ $student->permanent_residence ?? 'ấp 14 xóm lung, Xã Vĩnh Mỹ, Cà Mau' }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <hr class="my-3">

                    {{-- Form cập nhật liên hệ --}}
                    <h4 class="h6 fw-bold mb-3 text-dark">
                        <i class="bi bi-pencil-square text-primary"></i> Cập nhật thông tin liên hệ
                    </h4>
                    <form method="POST" action="{{ route('student.profile.updateContact') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label small fw-semibold" for="phone">Số điện thoại</label>
                            <input type="text" class="form-control form-control-sm" id="phone" name="phone" 
                                value="{{ old('phone', $student->phone ?? '0942367010') }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold" for="email">Địa chỉ Email</label>
                            <input type="email" class="form-control form-control-sm" id="email" name="email" 
                                value="{{ old('email', $student->email ?? $user->email) }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold" for="contact_address">Địa chỉ liên hệ hiện tại</label>
                            <textarea class="form-control form-control-sm" id="contact_address" name="contact_address" rows="2" required>{{ old('contact_address', $student->contact_address ?? '140 Lê Trọng Tấn, P. Tây Thạnh, Q. Tân Phú, TP.HCM') }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bi bi-save me-1"></i> Lưu thông tin liên hệ
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .info-table th {
        font-weight: 500;
        padding-top: 6px;
        padding-bottom: 6px;
    }
    .info-table td {
        padding-top: 6px;
        padding-bottom: 6px;
    }
</style>
@endsection
