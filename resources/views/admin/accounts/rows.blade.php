@php
    $statusTone = fn (?string $code): string => match ($code) {
        'GRADUATED' => 'success',
        'AT_RISK' => 'pending',
        'DROPPED' => 'failed',
        'SUSPENDED' => 'neutral',
        default => 'info',
    };
@endphp
@forelse ($accounts as $account)
    <tr>
        <td class="table-order-id">{{ $accounts->firstItem() + $loop->iteration - 1 }}</td>
        <td class="table-order-id">
            <a class="table-product-name" href="{{ route('admin.accounts.edit', $account) }}">
                {{ $account->person_code ?: '—' }}
            </a>
        </td>
        <td>
            <x-person-cell :name="$account->name" :avatar="$account->avatarUrl()"
                :sub="$account->phone ?: 'Chưa có số điện thoại'" />
        </td>
        <td>{{ $account->roleLabel() }} <span class="fs-xs text-muted-green">({{ $account->role }})</span></td>
        <td>{{ $account->degree ?: '—' }}</td>
        <td>
            @switch($account->role)
                @case('student')
                    <div>{{ $account->studentClass?->class_code ?? 'Chưa xếp lớp' }}</div>
                    @if ($account->studentClass?->major)
                        <div class="table-user-sub">{{ $account->studentClass->major->name }}</div>
                    @endif
                    @break
                @case('teacher')
                    <div>{{ $account->department?->name ?? 'Chưa phân công khoa' }}</div>
                    @break
                @default
                    <span class="table-user-sub">Ban quản lý</span>
            @endswitch
        </td>
        <td class="table-user-sub">{{ $account->email }}</td>
        <td>
            @if ($account->role === 'student')
                <x-status-badge :tone="$statusTone($account->academic_status)">{{ $account->academic_status_label }}</x-status-badge>
            @else
                <span class="table-user-sub">—</span>
            @endif
        </td>
        <td class="text-nowrap">
            <a class="btn btn-custom btn-custom-sm btn-custom-outline-primary" href="{{ route('admin.accounts.edit', $account) }}">Sửa</a>
            @unless ($account->id === auth()->id())
                <form class="d-inline" method="POST" action="{{ route('admin.accounts.destroy', $account) }}"
                    data-confirm="Bạn có chắc muốn xóa tài khoản này?">
                    @csrf @method('DELETE')
                    <button class="btn btn-custom btn-custom-sm btn-custom-outline-danger" type="submit">Xóa</button>
                </form>
            @endunless
        </td>
    </tr>
@empty
    <tr>
        <td colspan="9" class="text-center py-5">
            <div class="empty-state-icon bi bi-inbox" aria-hidden="true"></div>
            <div class="table-user-sub">Chưa có tài khoản nào khớp.</div>
        </td>
    </tr>
@endforelse
