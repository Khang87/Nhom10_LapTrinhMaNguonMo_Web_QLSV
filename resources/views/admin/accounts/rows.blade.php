@forelse ($accounts as $account)
    <tr>
        <td>
            <x-person-cell :name="$account->name" :avatar="$account->avatarUrl()" />
        </td>
        <td>{{ $account->email }}</td>
        <td>{{ $account->roleLabel() }} <span class="fs-xs text-muted-green">({{ $account->role }})</span></td>
        <td>{{ $account->role === 'student' ? ($account->studentClass?->class_code ?? 'Chưa xếp lớp') : '—' }}</td>
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
        <td colspan="5" class="text-center py-5">
            <div class="empty-state-icon bi bi-inbox" aria-hidden="true"></div>
            <div class="table-user-sub">Chưa có tài khoản nào khớp.</div>
        </td>
    </tr>
@endforelse
