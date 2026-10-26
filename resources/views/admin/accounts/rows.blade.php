@forelse ($accounts as $account)
    <tr>
        <td>
            <span class="d-inline-flex align-items-center gap-2">
                <img class="rounded-circle border" width="32" height="32" alt=""
                    src="{{ e($account->avatarUrl()) }}">
                <span>{{ $account->name }}</span>
            </span>
        </td>
        <td>{{ $account->email }}</td>
        <td>{{ $account->roleLabel() }} <span class="text-muted small">({{ $account->role }})</span></td>
        <td>{{ $account->role === 'student' ? ($account->studentClass?->class_code ?? 'Chưa xếp lớp') : '—' }}</td>
        <td class="text-nowrap">
            <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.accounts.edit', $account) }}">Sửa</a>
            @unless ($account->id === auth()->id())
                <form class="d-inline" method="POST" action="{{ route('admin.accounts.destroy', $account) }}"
                    data-confirm="Bạn có chắc muốn xóa tài khoản này?">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger" type="submit">Xóa</button>
                </form>
            @endunless
        </td>
    </tr>
@empty
    <tr><td colspan="5" class="text-center">Chưa có tài khoản nào khớp.</td></tr>
@endforelse
