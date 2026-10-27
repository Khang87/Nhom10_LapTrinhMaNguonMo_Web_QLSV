@extends('layouts.spark')

@section('title', 'Chương trình khung đào tạo - HUIT')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h4 mb-1 fw-bold text-dark">Chương trình khung đào tạo</h1>
            <p class="text-secondary small mb-0">Chuyên ngành: <strong class="text-primary">{{ $specialization }}</strong> (Từ Học kỳ 1 đến Học kỳ 8)</p>
        </div>
        <a href="{{ route('student.subjects.index') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-journal-plus me-1"></i> Đăng ký học phần mở
        </a>
    </div>

    @foreach ($items as $semesterOrder => $coursesInSem)
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-light border-0 py-3 d-flex justify-content-between align-items-center">
                <h2 class="h6 mb-0 fw-bold text-primary">
                    <i class="bi bi-bookmark-fill me-2"></i>HỌC KỲ {{ $semesterOrder }}
                </h2>
                <span class="badge bg-secondary">
                    {{ $coursesInSem->count() }} môn học | {{ $coursesInSem->sum(fn($i) => $i->course->credits) }} tín chỉ
                </span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 60px;" class="text-center">STT</th>
                                <th style="width: 140px;">Mã môn học</th>
                                <th>Tên môn học</th>
                                <th style="width: 100px;" class="text-center">Số tín chỉ</th>
                                <th style="width: 160px;" class="text-center">Loại học phần</th>
                                <th style="width: 180px;" class="text-end pe-3">Đơn giá tín chỉ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($coursesInSem as $index => $item)
                                <tr>
                                    <td class="text-center text-muted">{{ $index + 1 }}</td>
                                    <td><code class="fw-bold">{{ $item->course->course_code }}</code></td>
                                    <td class="fw-semibold text-dark">{{ $item->course->course_name }}</td>
                                    <td class="text-center fw-bold">{{ $item->course->credits }}</td>
                                    <td class="text-center">
                                        @if ($item->is_mandatory)
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                                Bắt buộc
                                            </span>
                                        @else
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                Tự chọn
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-end pe-3 text-secondary">
                                        {{ number_format($item->course->tuition_fee_per_credit, 0, ',', '.') }} VNĐ
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection
