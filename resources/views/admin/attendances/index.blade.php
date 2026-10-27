@extends('layouts.spark')
@section('title', 'Điểm danh và báo cáo')
@section('content')
    <x-page-header title="Điểm danh và báo cáo"
        subtitle="Giảng viên điểm danh trên lớp của mình; trang này xem lại và xuất báo cáo theo ngày.">
        <x-slot name="actions">
            <x-export-buttons route="admin.attendances.export" :query="['date' => $date]" />
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

    <x-data-table title="Điểm danh trong ngày ({{ $sessions->count() }} buổi)">
        <thead>
            <tr>
                <th>Sinh viên</th>
                <th>Lớp học phần</th>
                <th>Ngày</th>
                <th>Người điểm danh</th>
                <th>Trạng thái</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($sessions as $session)
                @foreach ($session->records as $record)
                    <tr>
                        <td>
                            <x-person-cell :name="$record->enrollment?->user?->name ?? '(đã xóa)'"
                                :sub="$record->enrollment?->user?->person_code"
                                :avatar="$record->enrollment?->user?->avatarUrl()" />
                        </td>
                        <td class="table-product-name">{{ $session->offering?->label() ?? 'Lớp đã xóa' }}</td>
                        <td class="table-order-id">{{ $session->session_date->format('d/m/Y') }}</td>
                        <td>{{ $session->recorder?->name ?? 'Chưa ghi nhận' }}</td>
                        <td>
                            <x-status-badge :tone="$record->isPresent() ? 'success' : 'failed'">
                                {{ $record->statusLabel() }}
                            </x-status-badge>
                        </td>
                    </tr>
                @endforeach
            @empty
                <tr>
                    <td colspan="5" class="text-center py-5">
                        <div class="empty-state-icon bi bi-inbox" aria-hidden="true"></div>
                        <div class="table-user-sub">Chưa có dữ liệu điểm danh trong ngày này.</div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </x-data-table>
@endsection
