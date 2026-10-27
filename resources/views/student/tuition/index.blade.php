@extends('layouts.spark')

@section('title', 'Học phí & Thanh toán VietQR - HUIT')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h4 mb-1 fw-bold text-dark">Tra cứu công nợ & Thanh toán học phí</h1>
            <p class="text-secondary small mb-0">Thanh toán trực tuyến tự động qua chuẩn VietQR</p>
        </div>
        <a href="{{ route('student.schedule.index') }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-calendar-week me-1"></i> Xem lịch học
        </a>
    </div>

    @if ($currentInvoice)
        <div class="row g-4">
            {{-- Cột bên trái: Chi tiết công nợ --}}
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-light border-0 py-3">
                        <h2 class="h6 mb-0 fw-bold text-primary">
                            <i class="bi bi-receipt me-2"></i>THÔNG TIN HỌC PHÍ HỌC KỲ ({{ $currentInvoice->semester_code }})
                        </h2>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3 mb-4">
                            <div class="col-sm-6">
                                <div class="p-3 bg-light rounded">
                                    <div class="text-secondary small">Tổng số tín chỉ đăng ký:</div>
                                    <div class="fs-4 fw-bold text-dark">{{ $currentInvoice->total_credits }} tín chỉ</div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="p-3 bg-light rounded">
                                    <div class="text-secondary small">Trạng thái thanh toán:</div>
                                    <div class="fs-5 fw-bold mt-1">
                                        @if ($currentInvoice->status === 'PAID')
                                            <span class="badge bg-success"><i class="bi bi-check-circle"></i> Đã hoàn thành</span>
                                        @else
                                            <span class="badge bg-danger"><i class="bi bi-exclamation-circle"></i> Chưa thanh toán</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <table class="table table-bordered align-middle">
                            <tbody>
                                <tr>
                                    <th class="bg-light text-secondary" style="width: 45%;">Tổng học phí phát sinh:</th>
                                    <td class="fw-bold fs-5 text-dark">{{ number_format($currentInvoice->total_amount, 0, ',', '.') }} VNĐ</td>
                                </tr>
                                <tr>
                                    <th class="bg-light text-secondary">Số tiền đã nộp:</th>
                                    <td class="text-success fw-bold">{{ number_format($currentInvoice->paid_amount, 0, ',', '.') }} VNĐ</td>
                                </tr>
                                <tr class="table-warning">
                                    <th class="fw-bold text-danger">Học phí còn nợ:</th>
                                    <td class="fw-bold fs-4 text-danger">{{ number_format($currentInvoice->debt_amount, 0, ',', '.') }} VNĐ</td>
                                </tr>
                                <tr>
                                    <th class="bg-light text-secondary">Mã chuyển khoản (Nội dung):</th>
                                    <td>
                                        <code class="fs-6 fw-bold text-primary px-2 py-1 bg-primary-subtle rounded">
                                            {{ $currentInvoice->payment_code }}
                                        </code>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        @if ($currentInvoice->status !== 'PAID')
                            <div class="alert alert-info border-0 d-flex align-items-center gap-3 mt-3">
                                <i class="bi bi-info-circle-fill fs-3 text-primary"></i>
                                <div class="small">
                                    Sinh viên dùng ứng dụng Ngân hàng (App Banking) hoặc MoMo quét mã QR bên phải. 
                                    Hệ thống sẽ tự động điền sẵn số tiền và mã sinh viên.
                                </div>
                            </div>
                        @else
                            <div class="alert alert-success border-0 d-flex align-items-center gap-3 mt-3">
                                <i class="bi bi-check-circle-fill fs-3 text-success"></i>
                                <div>
                                    <strong>Đã thanh toán học phí thành công!</strong> 
                                    <div class="small text-muted">Thời gian ghi nhận: {{ $currentInvoice->paid_at ? $currentInvoice->paid_at->format('H:i d/m/Y') : 'Vừa xong' }}</div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Cột bên phải: Mã VietQR động --}}
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-header bg-light border-0 py-3">
                        <h2 class="h6 mb-0 fw-bold text-primary">
                            <i class="bi bi-qr-code-scan me-2"></i>MÃ THANH TOÁN VIETQR
                        </h2>
                    </div>
                    <div class="card-body p-4">
                        @if ($currentInvoice->debt_amount > 0)
                            <div class="qr-box p-3 border rounded-3 bg-white d-inline-block shadow-sm mb-3">
                                <img src="{{ $currentInvoice->viet_qr_url }}" 
                                    alt="Mã QR thanh toán học phí HUIT" 
                                    class="img-fluid" style="max-width: 280px;">
                            </div>
                            <div class="small text-muted mb-3">
                                Số tiền: <strong class="text-danger fs-5">{{ number_format($currentInvoice->debt_amount, 0, ',', '.') }} VNĐ</strong>
                            </div>
                            
                            {{-- Nút giả lập chuyển khoản (Dành cho đồ án) --}}
                            <form method="POST" action="{{ route('student.tuition.simulate', $currentInvoice) }}">
                                @csrf
                                <button type="submit" class="btn btn-outline-success btn-sm w-100 py-2">
                                    <i class="bi bi-lightning-charge-fill me-1"></i> Giả lập đã chuyển khoản thành công
                                </button>
                            </form>
                        @else
                            <div class="py-5">
                                <i class="bi bi-shield-check text-success display-1"></i>
                                <h3 class="h5 mt-3 fw-bold text-success">Đã hoàn thành công nợ</h3>
                                <p class="text-muted small">Cảm ơn bạn! Bạn không còn học phí nợ trong học kỳ này.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="alert alert-secondary">
            Chưa có thông tin học phí cho sinh viên trong học kỳ này.
        </div>
    @endif
</div>
@endsection
