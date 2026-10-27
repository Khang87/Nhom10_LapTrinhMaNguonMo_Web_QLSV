@extends('layouts.spark')

@section('title', 'Học phí')

@section('content')
    <x-page-header title="Học phí theo học kỳ"
        subtitle="Phát hành thông báo một lần cho cả kỳ, sinh viên chuyển khoản theo QR, kế toán ghi nhận thu tại đây.">
        <x-slot name="actions">
            @if ($semester)
                <form method="POST" action="{{ route('admin.tuition.declare') }}">
                    @csrf
                    <input type="hidden" name="semester_id" value="{{ $semester->id }}">
                    <button class="btn btn-custom btn-custom-primary" type="submit" @disabled($perCredit === 0)>
                        <i class="bi bi-megaphone me-1" aria-hidden="true"></i>Phát hành học phí kỳ này
                    </button>
                </form>
            @endif
        </x-slot>
    </x-page-header>

    @if ($perCredit === 0)
        <div class="alert-custom alert-custom-warning mb-3" role="alert">
            <i class="bi bi-exclamation-triangle alert-custom-icon" aria-hidden="true"></i>
            <span class="alert-custom-content">
                Chưa cấu hình mức thu một tín chỉ (ACADEMIC_TUITION_PER_CREDIT) nên hệ thống chưa tính được
                số tiền. Nhập mức thu vào biến môi trường rồi phát hành.
            </span>
        </div>
    @endif

    <div class="card">
        <form class="row g-2 align-items-end" method="GET" action="{{ route('admin.tuition.index') }}"
            aria-label="Lọc học phí theo học kỳ">
            <div class="col-sm-8 col-md-5">
                <label class="visually-hidden" for="semester_id">Học kỳ</label>
                <select class="form-select form-select-custom" id="semester_id" name="semester_id">
                    @foreach ($semesters as $option)
                        <option value="{{ $option->id }}" @selected($semester?->id === $option->id)>
                            {{ $option->label }}{{ $option->is_active ? ' (hiện hành)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 col-lg-3">
                <span class="form-label-custom">Mức thu một tín chỉ</span>
                <div class="stat-value stat-value-amount">{{ number_format($perCredit, 0, ',', '.') }} đ</div>
            </div>
            <div class="col-auto"><button class="btn btn-custom btn-custom-primary" type="submit">Xem</button></div>
        </form>
    </div>

    <div class="row g-3 mt-0">
        <div class="col-6 col-lg-3">
            <x-stat-card label="Thông báo đã phát hành" :value="$invoices->total()" />
        </div>
        <div class="col-6 col-lg-3">
            <x-stat-card label="Đã thu"
                :value="$collected['amount'] === 0 ? '0 đ' : number_format($collected['amount'], 0, ',', '.').' đ'"
                :hint="$collected['students'].' sinh viên'" />
        </div>
        <div class="col-6 col-lg-3">
            <x-stat-card label="Còn chưa thu"
                :value="$outstanding['amount'] === 0 ? '0 đ' : number_format($outstanding['amount'], 0, ',', '.').' đ'"
                :hint="$outstanding['students'].' sinh viên'" />
        </div>
    </div>

    <x-data-table>
        <thead>
            <tr>
                <th scope="col">STT</th>
                <th scope="col">Sinh viên</th>
                <th scope="col">Tín chỉ</th>
                <th scope="col">Số tiền</th>
                <th scope="col">Nội dung chuyển khoản</th>
                <th scope="col">Trạng thái</th>
                <th scope="col">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($invoices as $invoice)
                <tr>
                    <td class="table-order-id">{{ $invoices->firstItem() + $loop->iteration - 1 }}</td>
                    <td>
                        <x-person-cell :name="$invoice->student?->name ?? '—'"
                            :sub="$invoice->student?->student_code" :avatar="$invoice->student?->avatarUrl()" />
                    </td>
                    <td class="table-amount">{{ $invoice->credits }}</td>
                    <td class="table-amount">{{ $invoice->amountLabel() }}</td>
                    <td>{{ $invoice->content }}</td>
                    <td>
                        @if ($invoice->isPaid())
                            <x-status-badge tone="success">Đã thu</x-status-badge>
                            <div class="fs-xs text-muted-green mt-1">
                                {{ $invoice->paid_at->format('d/m/Y H:i') }}
                                @if ($invoice->collector)
                                    · {{ $invoice->collector->name }}
                                @endif
                            </div>
                        @else
                            <x-status-badge tone="pending">Chưa thu</x-status-badge>
                        @endif
                        @if ($invoice->note)
                            <div class="fs-xs text-muted-green">{{ $invoice->note }}</div>
                        @endif
                    </td>
                    <td class="text-nowrap">
                        @if ($invoice->isPaid())
                            <form method="POST" action="{{ route('admin.tuition.unpaid', $invoice) }}"
                                data-confirm="Hủy ghi nhận thu của {{ $invoice->student?->name }}?">
                                @csrf
                                <button class="btn btn-custom btn-custom-sm btn-custom-outline-secondary" type="submit">
                                    Hủy ghi nhận thu
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('admin.tuition.paid', $invoice) }}">
                                @csrf
                                <button class="btn btn-custom btn-custom-sm btn-custom-primary" type="submit">
                                    Đánh dấu đã thu
                                </button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center py-5">
                        <div class="empty-state-icon mb-2"><i class="bi bi-wallet2" aria-hidden="true"></i></div>
                        <div class="table-user-sub">
                            Học kỳ này chưa có thông báo học phí nào. Bấm "Phát hành học phí kỳ này" để tạo.
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
        <x-slot name="footer">
            <div id="tuition-pagination">{{ $invoices->links() }}</div>
        </x-slot>
    </x-data-table>
@endsection
