@extends('layouts.spark')

@section('title', 'Tra cứu công nợ & Thanh toán học phí - HUIT')

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h4 fw-bold text-dark mb-1">Tra cứu công nợ & Thanh toán học phí</h1>
            <p class="text-secondary small mb-0">
                Sinh viên: <strong class="text-dark">{{ $student->full_name ?? auth()->user()->name }}</strong> (MSSV: {{ $student->student_code ?? auth()->user()->username }})
                • Lớp: <strong class="text-dark">{{ $student->class_code ?? '14DHTH04' }}</strong>
                • Chuyên ngành: <strong class="text-dark">{{ $student->specialization?->name ?? ($student->specialization_name ?: 'Công nghệ phần mềm') }}</strong>
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('student.subjects.index') }}" class="btn btn-outline-dark btn-sm d-inline-flex align-items-center gap-1.5">
                <i class="bi bi-journal-plus"></i>
                <span>Đăng ký học phần</span>
            </a>
            <a href="{{ route('student.schedule.index') }}" class="btn btn-outline-dark btn-sm d-inline-flex align-items-center gap-1.5">
                <i class="bi bi-calendar-week"></i>
                <span>Xem lịch học</span>
            </a>
        </div>
    </div>

    {{-- Alert Messages --}}
    @if (session('success'))
        <div class="alert alert-light border d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="bi bi-check2-circle text-dark"></i>
            <div class="text-dark small">{{ session('success') }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-light border d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="bi bi-exclamation-circle text-dark"></i>
            <div class="text-dark small">{{ session('error') }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- 3 THẺ KPI TỔNG CÔNG NỢ & TỔNG HỌC PHÍ --}}
    <div class="row g-3 mb-4">
        <!-- KPI 1: TỔNG HỌC PHÍ -->
        <div class="col-12 col-md-4">
            <div class="p-3.5 rounded-3 bg-light border h-100">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="text-secondary small fw-medium">Tổng học phí đã đăng ký</span>
                    <i class="bi bi-receipt-cutoff text-secondary"></i>
                </div>
                <div class="fs-3 fw-bold text-dark">
                    {{ number_format($totalTuitionAmount, 0, ',', '.') }} <span class="fs-6 fw-normal text-secondary">VNĐ</span>
                </div>
                <div class="text-secondary small mt-1" style="font-size: 0.8rem;">
                    Áp dụng cho toàn bộ {{ $registeredCourses->count() }} môn học đã đăng ký
                </div>
            </div>
        </div>

        <!-- KPI 2: ĐÃ THANH TOÁN -->
        <div class="col-12 col-md-4">
            <div class="p-3.5 rounded-3 bg-light border h-100">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="text-secondary small fw-medium">Số tiền đã thanh toán</span>
                    <i class="bi bi-check2-all text-success"></i>
                </div>
                <div class="fs-3 fw-bold text-success">
                    {{ number_format($totalPaidAmount, 0, ',', '.') }} <span class="fs-6 fw-normal text-secondary">VNĐ</span>
                </div>
                <div class="text-secondary small mt-1" style="font-size: 0.8rem;">
                    Đã thanh toán cho {{ $registeredCourses->where('is_paid', true)->count() }} học phần
                </div>
            </div>
        </div>

        <!-- KPI 3: TỔNG CÔNG NỢ CÒN LẠI -->
        <div class="col-12 col-md-4">
            <div class="p-3.5 rounded-3 bg-light border h-100">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="text-secondary small fw-medium">Tổng công nợ còn phải nộp</span>
                    <i class="bi bi-exclamation-triangle text-danger"></i>
                </div>
                <div class="fs-3 fw-bold text-danger">
                    {{ number_format($totalDebtAmount, 0, ',', '.') }} <span class="fs-6 fw-normal text-secondary">VNĐ</span>
                </div>
                <div class="text-secondary small mt-1" style="font-size: 0.8rem;">
                    @if ($totalDebtAmount > 0)
                        Còn {{ $registeredCourses->where('is_paid', false)->count() }} học phần chưa thanh toán
                    @else
                        <span class="text-success fw-medium">Đã thanh toán đủ toàn bộ học phí</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- CỘT BÊN TRÁI (8 COL): DANH SÁCH TẤT CẢ CÁC HỌC PHẦN ĐÃ ĐĂNG KÝ --}}
        <div class="col-lg-8">
            <div class="card border shadow-sm rounded-3 bg-white overflow-hidden mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <strong class="text-dark fs-6">Danh sách tất cả các học phần đã đăng ký</strong>
                        <div class="text-secondary small mt-0.5">
                            Chi tiết tình trạng thanh toán và đơn giá theo từng môn học của sinh viên
                        </div>
                    </div>
                    <span class="badge bg-light text-dark border font-monospace">
                        Tổng cộng: {{ $registeredCourses->count() }} học phần
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-secondary small">
                                <tr>
                                    <th class="ps-3 text-center" style="width: 45px;">#</th>
                                    <th style="width: 120px;">Mã LHP</th>
                                    <th>Học phần & Tín chỉ</th>
                                    <th style="width: 140px;">Học kỳ</th>
                                    <th class="text-end" style="width: 130px;">Học phí</th>
                                    <th class="text-center pe-3" style="width: 150px;">Tình trạng thanh toán</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($registeredCourses as $idx => $item)
                                    <tr>
                                        <td class="ps-3 text-center text-muted small">{{ $idx + 1 }}</td>
                                        <td>
                                            <span class="badge bg-light text-dark border font-monospace">
                                                {{ $item->section?->section_code ?? 'LHP-' . $item->course?->course_code }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark">{{ $item->course?->course_name }}</div>
                                            <div class="d-flex align-items-center gap-1.5 text-secondary small">
                                                <span class="font-monospace">{{ $item->course?->course_code }}</span>
                                                <span>•</span>
                                                <span>{{ $item->credits }} TC</span>
                                                <span>•</span>
                                                <span>{{ number_format($item->unit_price, 0, ',', '.') }} đ/TC</span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border small">
                                                {{ $item->semester_code }}
                                            </span>
                                        </td>
                                        <td class="text-end fw-semibold text-dark small">
                                            @if ($item->is_paid)
                                                <span class="text-secondary text-decoration-line-through">
                                                    {{ number_format($item->fee, 0, ',', '.') }} đ
                                                </span>
                                            @else
                                                <span class="text-danger fw-bold">
                                                    {{ number_format($item->fee, 0, ',', '.') }} đ
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-center pe-3">
                                            @if ($item->is_paid)
                                                <span class="badge bg-light text-success border border-success-subtle px-2.5 py-1.5 small">
                                                    <i class="bi bi-check2-circle me-1"></i>Đã thanh toán
                                                </span>
                                            @else
                                                <div class="d-flex flex-column align-items-center">
                                                    <span class="badge bg-light text-danger border border-danger-subtle px-2 py-1 small">
                                                        <i class="bi bi-clock me-1"></i>Chưa thanh toán
                                                    </span>
                                                    <span class="text-danger fw-bold mt-0.5" style="font-size: 0.75rem;">
                                                        {{ number_format($item->fee, 0, ',', '.') }} đ
                                                    </span>
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-secondary small">
                                            <i class="bi bi-inbox fs-3 d-block mb-2 text-secondary opacity-50"></i>
                                            Chưa có học phần nào được đăng ký trong hệ thống.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if ($registeredCourses->isNotEmpty())
                                <tfoot class="table-light">
                                    <tr class="fw-bold text-dark small">
                                        <td colspan="4" class="ps-3 text-end">TỔNG CỘNG:</td>
                                        <td class="text-end text-dark fs-6">{{ number_format($totalTuitionAmount, 0, ',', '.') }} đ</td>
                                        <td class="text-center pe-3">
                                            @if ($totalDebtAmount > 0)
                                                <span class="badge bg-light text-danger border border-danger-subtle">
                                                    Còn nợ: {{ number_format($totalDebtAmount, 0, ',', '.') }} đ
                                                </span>
                                            @else
                                                <span class="badge bg-light text-success border border-success-subtle">
                                                    Đã hoàn thành 100%
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- CỘT BÊN PHẢI (4 COL): THANH TOÁN TRỰC TUYẾN & VIETQR --}}
        <div class="col-lg-4">
            <div class="card border shadow-sm rounded-3 bg-white mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <strong class="text-dark fs-6">
                        <i class="bi bi-qr-code-scan me-1 text-dark"></i> Thanh toán học phí trực tuyến
                    </strong>
                    <div class="text-secondary small mt-0.5">
                        Thanh toán qua quét mã VietQR tự động hoặc chuyển khoản
                    </div>
                </div>
                <div class="card-body p-3 p-md-4 text-center">
                    @if ($totalDebtAmount > 0)
                        <!-- Khi còn công nợ -->
                        <div class="alert alert-light border border-danger-subtle text-start p-3 rounded-3 mb-3">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <span class="text-secondary small">Tổng công nợ cần nộp:</span>
                                <span class="badge bg-danger text-white">Chưa thanh toán</span>
                            </div>
                            <div class="fs-4 fw-bold text-danger">
                                {{ number_format($totalDebtAmount, 0, ',', '.') }} VNĐ
                            </div>
                            @if ($currentInvoice)
                                <div class="small text-secondary mt-1">
                                    Mã giao dịch: <code class="fw-bold text-dark">{{ $currentInvoice->payment_code }}</code>
                                </div>
                            @endif
                        </div>

                        <!-- Mã QR VietQR động -->
                        @if ($currentInvoice && $currentInvoice->viet_qr_url)
                            <div class="p-2 border rounded-3 bg-white d-inline-block shadow-sm mb-3">
                                <img src="{{ $currentInvoice->viet_qr_url }}" 
                                     alt="Mã QR thanh toán học phí HUIT" 
                                     class="img-fluid rounded" style="max-width: 220px;">
                            </div>
                        @else
                            <div class="p-3 border rounded-3 bg-light text-secondary small mb-3">
                                <i class="bi bi-bank fs-2 d-block mb-1 text-dark"></i>
                                Quét mã qua App Ngân hàng hoặc chuyển khoản theo mã SV: <strong>{{ $student->student_code }}</strong>
                            </div>
                        @endif

                        <div class="small text-secondary mb-3 text-start">
                            <i class="bi bi-info-circle me-1"></i> Sử dụng App Ngân hàng (Vietcombank, MB, Techcombank...) hoặc MoMo để quét mã VietQR. Hệ thống sẽ tự động cập nhật công nợ sau khi giao dịch thành công.
                        </div>

                        <!-- Nút thanh toán mô phỏng -->
                        @if ($currentInvoice)
                            <form method="POST" action="{{ route('student.tuition.simulate', $currentInvoice) }}" class="mb-2">
                                @csrf
                                <button type="submit" class="btn btn-dark btn-sm w-100 py-2 d-inline-flex align-items-center justify-content-center gap-1.5">
                                    <i class="bi bi-lightning-charge-fill text-warning"></i>
                                    <span>Thanh toán kỳ này (Mô phỏng)</span>
                                </button>
                            </form>
                        @endif

                        <form method="POST" action="{{ route('student.tuition.payAll') }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-dark btn-sm w-100 py-2 d-inline-flex align-items-center justify-content-center gap-1.5">
                                <i class="bi bi-check2-all"></i>
                                <span>Thanh toán toàn bộ công nợ</span>
                            </button>
                        </form>
                    @else
                        <!-- Khi đã hoàn thành toàn bộ công nợ -->
                        <div class="py-4 text-center">
                            <div class="rounded-circle bg-light border d-inline-flex align-items-center justify-content-center mb-3 text-success" 
                                 style="width: 72px; height: 72px;">
                                <i class="bi bi-shield-check fs-1"></i>
                            </div>
                            <h3 class="h6 fw-bold text-dark mb-1">Đã hoàn thành công nợ</h3>
                            <p class="text-secondary small mb-3">
                                Cảm ơn bạn! Tất cả các học phần đã đăng ký đều đã được thanh toán đầy đủ.
                            </p>
                            <span class="badge bg-light text-success border border-success-subtle px-3 py-2">
                                <i class="bi bi-check-circle me-1"></i>Không còn công nợ phải đóng
                            </span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Khung Lịch sử Hóa đơn theo Kỳ -->
            @if ($invoices->isNotEmpty())
                <div class="card border shadow-sm rounded-3 bg-white overflow-hidden">
                    <div class="card-header bg-white py-2.5 px-3 border-bottom">
                        <strong class="text-dark small">Lịch sử hóa đơn học phí các kỳ</strong>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush small">
                            @foreach ($invoices as $inv)
                                <div class="list-group-item px-3 py-2.5 d-flex align-items-center justify-content-between">
                                    <div>
                                        <div class="fw-semibold text-dark">{{ $inv->semester_code }}</div>
                                        <div class="text-secondary" style="font-size: 0.75rem;">
                                            {{ $inv->total_credits }} TC • {{ number_format($inv->total_amount, 0, ',', '.') }} đ
                                        </div>
                                    </div>
                                    <div>
                                        @if ($inv->status === 'PAID')
                                            <span class="badge bg-light text-success border border-success-subtle">
                                                Đã nộp
                                            </span>
                                        @else
                                            <span class="badge bg-light text-danger border border-danger-subtle">
                                                Nợ: {{ number_format($inv->debt_amount, 0, ',', '.') }} đ
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
