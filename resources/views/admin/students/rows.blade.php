@forelse ($students as $student)
    <tr>
        <td>{{ $student->student_code ?: '—' }}</td>
        <td>
            <span class="d-inline-flex align-items-center gap-2">
                <img class="rounded-circle border" width="32" height="32" alt=""
                    src="{{ e($student->avatarUrl()) }}">
                <span>{{ $student->name }}</span>
            </span>
        </td>
        <td>{{ $student->email }}</td>
        <td>{{ $student->studentClass?->class_code ?? 'Chưa xếp lớp' }}</td>
        <td>{{ $student->studentClass?->intake_year ?? '—' }}</td>
        <td>{{ $student->academic_status_label }}</td>
        <td class="text-nowrap">
            <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.students.show', $student) }}">Xem</a>
            <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.students.edit', $student) }}">Sửa</a>
            <form class="d-inline" method="POST" action="{{ route('admin.students.destroy', $student) }}"
                data-confirm="Bạn có chắc muốn xóa sinh viên này?">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-outline-danger" type="submit">Xóa</button>
            </form>
        </td>
    </tr>
@empty
    <tr><td colspan="7" class="text-center">Chưa có sinh viên nào khớp.</td></tr>
@endforelse
