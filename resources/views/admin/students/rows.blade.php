@php
    $statusTone = fn (?string $code): string => match ($code) {
        'GRADUATED' => 'success',
        'AT_RISK' => 'pending',
        'DROPPED' => 'failed',
        'SUSPENDED' => 'neutral',
        default => 'info',
    };
@endphp
@forelse ($students as $student)
    <tr>
        <td class="table-order-id">{{ $students->firstItem() + $loop->iteration - 1 }}</td>
        <td class="table-order-id">
            <a class="table-product-name" href="{{ route('admin.students.show', $student) }}">{{ $student->student_code ?: '—' }}</a>
        </td>
        <td>
            <x-person-cell :name="$student->name" :avatar="$student->avatarUrl()"
                :sub="$student->phone ?: 'Chưa có số điện thoại'" />
        </td>
        <td>{{ $student->dob?->format('d/m/Y') ?? '—' }}</td>
        <td>{{ $student->gender ? App\Support\PersonLabels::gender($student->gender) : '—' }}</td>
        <td>{{ $student->studentClass?->class_code ?? 'Chưa xếp lớp' }}</td>
        <td>{{ $student->studentClass?->intake_year ?? '—' }}</td>
        <td class="table-user-sub">{{ $student->studentClass?->major?->name ?? '—' }}</td>
        <td class="table-user-sub">{{ $student->email }}</td>
        <td><x-status-badge :tone="$statusTone($student->academic_status)">{{ $student->academic_status_label }}</x-status-badge></td>
        <td class="text-nowrap">
            <a class="btn btn-custom btn-custom-sm btn-custom-light" href="{{ route('admin.students.show', $student) }}">Xem</a>
            <a class="btn btn-custom btn-custom-sm btn-custom-outline-primary" href="{{ route('admin.students.edit', $student) }}">Sửa</a>
            <form class="d-inline" method="POST" action="{{ route('admin.students.destroy', $student) }}"
                data-confirm="Bạn có chắc muốn xóa sinh viên này?">
                @csrf @method('DELETE')
                <button class="btn btn-custom btn-custom-sm btn-custom-outline-danger" type="submit">Xóa</button>
            </form>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="11" class="text-center py-5">
            <div class="empty-state-icon bi bi-inbox" aria-hidden="true"></div>
            <div class="table-user-sub">Chưa có sinh viên nào khớp.</div>
        </td>
    </tr>
@endforelse
