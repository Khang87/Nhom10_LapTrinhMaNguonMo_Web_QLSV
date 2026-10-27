@extends('layouts.spark')
@section('title', 'Điểm danh và báo cáo')
@section('content')
    <x-page-header title="Điểm danh và báo cáo">
        <x-slot name="actions">
            <a class="btn btn-custom btn-custom-outline-primary" href="{{ route('admin.attendances.export', ['date' => $date]) }}">
                <i class="bi bi-download me-1" aria-hidden="true"></i>Tải CSV
            </a>
        </x-slot>
    </x-page-header>

    <div class="card">
        <form class="row g-2 align-items-end" method="GET" action="{{ route('admin.attendances.index') }}">
            <div class="col-auto">
                <label class="form-label-custom" for="date">Ngày điểm danh</label>
                <input class="form-control form-control-custom" id="date" name="date" type="date" value="{{ $date }}" required>
            </div>
            <div class="col-auto"><button class="btn btn-custom btn-custom-primary" type="submit">Xem báo cáo</button></div>
        </form>
    </div>

    <p class="mb-3">
        Ngày {{ \Illuminate\Support\Carbon::parse($date)->format('d/m/Y') }}:
        <x-status-badge tone="success">Có mặt: {{ $presentCount }}</x-status-badge>
        <x-status-badge tone="failed">Vắng: {{ $absentCount }}</x-status-badge>
    </p>

    <form method="POST" action="{{ route('admin.attendances.store') }}">
        @csrf
        <input type="hidden" name="date" value="{{ $date }}">
        <x-data-table>
            <thead><tr><th>Sinh viên</th><th>Môn đã đăng ký</th><th>Trạng thái</th></tr></thead>
            <tbody>
                @forelse ($students as $student)
                    @php $enrolledSubjects = $student->enrolledSubjects(); @endphp
                    @continue ($enrolledSubjects->isEmpty())
                    @foreach ($enrolledSubjects as $subject)
                        @php
                            $record = $records->first(fn ($item) => $item->user_id === $student->id && $item->subject_id === $subject->id);
                        @endphp
                        <tr>
                            <td>
                                <x-person-cell :name="$student->name" :avatar="$student->avatarUrl()" />
                            </td>
                            <td class="table-product-name">{{ $subject->code }} - {{ $subject->name }}</td>
                            <td>
                                <label class="visually-hidden" for="attendance-{{ $student->id }}-{{ $subject->id }}">
                                    Trạng thái điểm danh của {{ $student->name }} - {{ $subject->name }}
                                </label>
                                <select class="form-select form-select-custom" id="attendance-{{ $student->id }}-{{ $subject->id }}"
                                    name="attendances[{{ $student->id }}][{{ $subject->id }}]">
                                    <option value="">Chưa điểm danh</option>
                                    <option value="present" @selected($record?->status === 'present')>Có mặt</option>
                                    <option value="absent" @selected($record?->status === 'absent')>Vắng</option>
                                </select>
                            </td>
                        </tr>
                    @endforeach
                @empty
                    <tr>
                        <td colspan="3" class="text-center py-5">
                            <div class="empty-state-icon bi bi-inbox" aria-hidden="true"></div>
                            <div class="table-user-sub">Chưa có sinh viên.</div>
                        </td>
                    </tr>
                @endforelse
                @if ($students->isNotEmpty() && $students->every(fn ($student) => $student->enrolledSubjects()->isEmpty()))
                    <tr>
                        <td colspan="3" class="text-center py-5">
                            <div class="empty-state-icon bi bi-inbox" aria-hidden="true"></div>
                            <div class="table-user-sub">Chưa có sinh viên đăng ký môn học.</div>
                        </td>
                    </tr>
                @endif
            </tbody>
        </x-data-table>
        <button class="btn btn-custom btn-custom-primary mt-3" type="submit">Lưu điểm danh</button>
    </form>

    <x-data-table title="Bảng điểm danh trong ngày">
        <thead><tr><th>Sinh viên</th><th>Môn học</th><th>Trạng thái</th></tr></thead>
        <tbody>
            @forelse ($records as $record)
                <tr>
                    <td>
                        <x-person-cell :name="$record->user->name" :avatar="$record->user->avatarUrl()" />
                    </td>
                    <td class="table-product-name">{{ $record->subject->name }}</td>
                    <td>
                        <x-status-badge :tone="$record->status === 'present' ? 'success' : 'failed'">
                            {{ $record->status === 'present' ? 'Có mặt' : 'Vắng' }}
                        </x-status-badge>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-center py-5">
                        <div class="empty-state-icon bi bi-inbox" aria-hidden="true"></div>
                        <div class="table-user-sub">Chưa có dữ liệu điểm danh trong ngày này.</div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </x-data-table>
@endsection
