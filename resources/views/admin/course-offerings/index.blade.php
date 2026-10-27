@extends('layouts.spark')

@section('title', 'Quản lý lớp học phần')

@section('content')
    <x-page-header title="Quản lý lớp học phần"
        subtitle="Một môn có thể mở nhiều nhóm trong cùng học kỳ. Sĩ số 0 nghĩa là không giới hạn.">
        <x-slot name="actions">
            <a class="btn btn-custom btn-custom-outline-primary" href="{{ route('admin.course-offerings.open-by-plan') }}">
                <i class="bi bi-list-check me-1" aria-hidden="true"></i>Mở lớp theo khung
            </a>
            <a class="btn btn-custom btn-custom-primary" href="{{ route('admin.course-offerings.create') }}">
                <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Thêm lớp học phần
            </a>
        </x-slot>
    </x-page-header>

    <div class="card">
        <form class="row g-2 align-items-end" method="GET" action="{{ route('admin.course-offerings.index') }}"
            aria-label="Lọc lớp học phần theo học kỳ">
            <div class="col-sm-8 col-md-5">
                <label class="visually-hidden" for="semester_id">Lọc theo học kỳ</label>
                <select class="form-select form-select-custom" id="semester_id" name="semester_id">
                    <option value="">Mọi học kỳ</option>
                    @foreach ($semesters as $semester)
                        <option value="{{ $semester->id }}" @selected($semesterId === $semester->id)>
                            {{ $semester->label }}{{ $semester->is_active ? ' (hiện hành)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto"><button class="btn btn-custom btn-custom-primary" type="submit">Tìm kiếm</button></div>
        </form>
    </div>

    <x-data-table>
        <thead>
            <tr>
                <th scope="col">STT</th>
                <th scope="col">Môn học</th>
                <th scope="col">Học kỳ</th>
                <th scope="col">Nhóm</th>
                <th scope="col">Giảng viên</th>
                <th scope="col">Phòng</th>
                <th scope="col">Đã đăng ký / sĩ số</th>
                <th scope="col">Trạng thái</th>
                <th scope="col">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($offerings as $offering)
                <tr>
                    <td>{{ $offerings->firstItem() + $loop->index }}</td>
                    <td class="table-product-name">{{ $offering->subject?->label() ?? '—' }}</td>
                    <td>{{ $offering->semester?->slug ?? '—' }}</td>
                    <td>{{ $offering->group_code }}</td>
                    <td>
                        @if ($offering->teacher)
                            <x-person-cell :name="$offering->teacher->name" :avatar="$offering->teacher->avatarUrl()" />
                        @else
                            Chưa phân công
                        @endif
                    </td>
                    <td>{{ $offering->room ?: '—' }}</td>
                    <td>
                        @if ($offering->max_students === 0)
                            <span class="text-muted-green">{{ $offering->active_count }} / Không giới hạn</span>
                        @else
                            <div class="fs-xs">{{ $offering->active_count }}/{{ $offering->max_students }}</div>
                            <div class="progress" role="progressbar" aria-label="Tỷ lệ lấp đầy sĩ số"
                                aria-valuenow="{{ $offering->active_count }}" aria-valuemin="0"
                                aria-valuemax="{{ $offering->max_students }}" style="height: 6px">
                                <div class="progress-bar {{ $offering->active_count >= $offering->max_students ? 'bg-danger' : '' }}"
                                    style="width: {{ min(100, (int) round($offering->active_count / $offering->max_students * 100)) }}%"></div>
                            </div>
                        @endif
                    </td>
                    <td>
                        <x-status-badge :tone="$offering->is_open ? 'success' : 'neutral'">
                            {{ $offering->is_open ? 'Mở' : 'Đóng' }}
                        </x-status-badge>
                    </td>
                    <td class="text-nowrap">
                        <a class="btn btn-custom btn-custom-sm btn-custom-outline-primary"
                            href="{{ route('admin.course-offerings.edit', $offering) }}">Sửa</a>
                        <form class="d-inline" method="POST"
                            action="{{ route('admin.course-offerings.destroy', $offering) }}"
                            data-confirm="Bạn có chắc muốn xóa lớp {{ $offering->label() }}? Lớp đã có sinh viên hoặc điểm sẽ không xóa được.">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-custom btn-custom-sm btn-custom-outline-danger" type="submit">Xóa</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center py-5">
                        <div class="empty-state-icon bi bi-inbox" aria-hidden="true"></div>
                        <div class="table-user-sub">Chưa có lớp học phần nào.</div>
                    </td>
                </tr>
            @endforelse
        </tbody>
        <x-slot name="footer">
            {{ $offerings->links() }}
        </x-slot>
    </x-data-table>
@endsection
