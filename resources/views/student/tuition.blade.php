@extends('layouts.spark')

@section('title', 'Học phí')

@section('content')
    <x-page-header title="Học phí của tôi"
        subtitle="Số phải nộp theo từng học kỳ, kèm mã QR và nội dung chuyển khoản để nộp qua ngân hàng." />

    @if ($current && ! $invoices->contains(fn ($invoice) => $invoice->semester_id === $current->id))
        <div class="alert-custom alert-custom-info mb-3" role="alert">
            <i class="bi bi-info-circle alert-custom-icon" aria-hidden="true"></i>
            <span class="alert-custom-content">
                Học kỳ hiện hành ({{ $current->label }}) chưa có thông báo học phí.
                @if ($perCredit > 0)
                    Bạn đang học {{ $pendingCredits }} TC, tạm tính {{ number_format($pendingCredits * $perCredit, 0, ',', '.') }} đ
                    — con số này chỉ chính thức sau khi nhà trường phát hành.
                @else
                    Nhà trường chưa công bố mức thu một tín chỉ.
                @endif
            </span>
        </div>
    @endif

    @forelse ($invoices as $invoice)
        <div class="card mb-3">
            <div class="card-header">
                <h2 class="card-title">{{ $invoice->semester?->label ?? 'Học kỳ đã xóa' }}</h2>
            </div>
            <div class="row g-3 card-body">
                <div class="col-md-4">
                    <div class="stat-label">Số phải nộp</div>
                    <div class="stat-value stat-value-amount">{{ $invoice->amountLabel() }}</div>
                    <div class="fs-xs text-muted-green">{{ $invoice->credits }} tín chỉ đã đăng ký trong kỳ.</div>
                    <div class="mt-2">
                        @if ($invoice->isPaid())
                            <x-status-badge tone="success">Đã thu {{ $invoice->paid_at->format('d/m/Y') }}</x-status-badge>
                        @else
                            <x-status-badge tone="pending">Chưa thu</x-status-badge>
                        @endif
                    </div>
                </div>
                <div class="col-md-4">
                    @if ($invoice->qr_url)
                        <img class="rounded border" width="180" height="180" src="{{ $invoice->qr_url }}"
                            alt="Mã QR chuyển khoản học kỳ {{ $invoice->semester?->term }}">
                        <div class="fs-xs text-muted-green mt-1">Mã QR do VietQR tạo từ số tiền và nội dung bên phải.</div>
                    @else
                        <div class="fs-xs text-muted-green">
                            Nhà trường chưa cấu hình tài khoản nhận nên chưa có mã QR. Chuyển khoản theo số tiền và
                            nội dung dưới đây.
                        </div>
                    @endif
                </div>
                <div class="col-md-4">
                    <div class="stat-label">Nội dung chuyển khoản</div>
                    <div class="table-product-name">{{ $invoice->content }}</div>
                    @if (filled($bank['account'] ?? null))
                        <div class="fs-xs text-muted-green mt-2">
                            Người nhận: tài khoản {{ $bank['account'] }}
                            @if (filled($bank['code'] ?? null)) (ngân hàng {{ $bank['code'] }}) @endif
                        </div>
                    @endif
                    @if ($invoice->note)
                        <div class="fs-xs text-muted-green mt-2">{{ $invoice->note }}</div>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="card">
            <div class="card-body text-center py-5">
                <div class="empty-state-icon mb-2"><i class="bi bi-wallet2" aria-hidden="true"></i></div>
                <div class="table-user-sub">Bạn chưa có thông báo học phí nào.</div>
            </div>
        </div>
    @endforelse
@endsection
