@extends('layouts.spark')

@section('title', 'Điểm danh buổi '.$date)

@section('content')
    <x-page-header title="Điểm danh buổi {{ \Illuminate\Support\Carbon::parse($date)->format('d/m/Y') }}"
        :subtitle="$offering->label().($slot ? ' · '.$slot->label() : '')">
        <x-slot name="actions">
            <a class="btn btn-custom btn-custom-light" href="{{ route('teacher.course-offerings.attendance', $offering) }}">
                <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Quay lại
            </a>
        </x-slot>
    </x-page-header>

    <div class="card">
        <form class="row g-2 align-items-end" method="GET"
            action="{{ route('teacher.course-offerings.attendance.session.create', $offering) }}"
            aria-label="Chọn buổi điểm danh">
            <input type="hidden" name="slot" value="{{ $slot?->id }}">
            <div class="col-auto">
                <label class="form-label-custom" for="date">Ngày vào học</label>
                <input class="form-control form-control-custom" id="date" name="date" type="date" value="{{ $date }}" required>
            </div>
            <div class="col-auto"><button class="btn btn-custom btn-custom-primary" type="submit">Xem buổi học</button></div>
            @if ($session)
                <div class="col-12">
                    <span class="fs-xs text-muted-green">
                        Buổi này đã điểm danh {{ $session->records->count() }}/{{ $rows->count() }} sinh viên
                        — lưu lại sẽ cập nhật, không tạo thêm dòng mới.
                    </span>
                </div>
            @endif
        </form>
    </div>

    <form method="POST" action="{{ route('teacher.course-offerings.attendance.session.store', $offering) }}">
        @csrf
        <input type="hidden" name="session_date" value="{{ $date }}">
        @if ($slot)
            <input type="hidden" name="lecture_slot_id" value="{{ $slot->id }}">
        @endif

        <x-data-table title="Danh sách lớp ({{ $rows->count() }})">
            <thead>
                <tr>
                    <th scope="col">STT</th>
                    <th scope="col">Sinh viên</th>
                    <th scope="col">Vắng</th>
                    <th scope="col">Tỷ lệ chuyên cần</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td class="table-order-id">{{ $loop->iteration }}</td>
                        <td>
                            <x-person-cell :name="$row['student']->name" :sub="$row['student']->person_code ?: 'chưa có mã'"
                                :avatar="$row['student']->avatarUrl()" />
                        </td>
                        <td>
                            <input type="hidden" name="statuses[{{ $row['enrollment']->id }}]" value="PRESENT">
                            <div class="form-check form-check-custom">
                                <input class="form-check-input form-check-input-custom" type="checkbox"
                                    id="absent-{{ $row['enrollment']->id }}"
                                    name="statuses[{{ $row['enrollment']->id }}]" value="ABSENT"
                                    @checked($row['record']?->status === \App\Models\Attendance::ABSENT)>
                                <label class="form-check-label" for="absent-{{ $row['enrollment']->id }}">
                                    Vắng buổi này
                                </label>
                            </div>
                        </td>
                        <td>
                            @if ($row['rate']['rate'] === null)
                                <span class="fs-xs text-muted-green">Chưa có dữ liệu</span>
                            @else
                                <span class="table-amount">{{ $row['rate']['rate'] }}%</span>
                                <div class="fs-xs text-muted-green">
                                    Có mặt {{ $row['rate']['present'] }}/{{ $row['rate']['sessions'] }} buổi
                                    @if ($row['rate']['pending'] > 0)
                                        · chưa điểm danh ({{ $row['rate']['pending'] }})
                                    @endif
                                </div>
                                @if (\App\Support\AttendanceRate::isBelowThreshold($row['rate']['rate']))
                                    <x-status-badge tone="failed">
                                        Dưới ngưỡng {{ $threshold }}%
                                    </x-status-badge>
                                @endif
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center py-5">
                            <div class="empty-state-icon bi bi-inbox" aria-hidden="true"></div>
                            <div class="table-user-sub">Lớp chưa có sinh viên đang học.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-data-table>

        <div class="card mt-3">
            <label class="form-label-custom" for="note">Ghi chú buổi học</label>
            <textarea class="form-control form-control-custom" id="note" name="note" rows="2"
                maxlength="2000">{{ $session?->note }}</textarea>
        </div>

        <button class="btn btn-custom btn-custom-primary mt-3" type="submit" @disabled($rows->isEmpty())>Lưu tất cả</button>
    </form>
@endsection
