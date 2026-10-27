@extends('layouts.spark')

@section('title', 'Lịch học, lịch thi theo tuần - HUIT')

@section('content')
<div class="timetable-container">
    {{-- Header Toolbar --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                {{-- Tiêu đề bên trái chữ xanh đậm HUIT --}}
                <div class="d-flex align-items-center">
                    <i class="bi bi-calendar3 me-2 fs-4 text-primary"></i>
                    <h1 class="h4 mb-0 fw-bold" style="color: #0f2942;">Lịch học, lịch thi theo tuần</h1>
                </div>

                {{-- Bộ lọc Radio buttons --}}
                <form id="filterForm" method="GET" action="{{ url()->current() }}" class="d-flex flex-wrap align-items-center gap-3">
                    <input type="hidden" name="date" value="{{ $selectedDate->toDateString() }}">
                    
                    <div class="btn-group" role="group" aria-label="Bộ lọc lịch">
                        <input type="radio" class="btn-check" name="filter" id="filter_all" value="all" 
                            {{ $filter === 'all' ? 'checked' : '' }} onchange="this.form.submit()">
                        <label class="btn btn-outline-primary btn-sm" for="filter_all">Tất cả</label>

                        <input type="radio" class="btn-check" name="filter" id="filter_study" value="study" 
                            {{ $filter === 'study' ? 'checked' : '' }} onchange="this.form.submit()">
                        <label class="btn btn-outline-primary btn-sm" for="filter_study">Lịch học</label>

                        <input type="radio" class="btn-check" name="filter" id="filter_exam" value="exam" 
                            {{ $filter === 'exam' ? 'checked' : '' }} onchange="this.form.submit()">
                        <label class="btn btn-outline-primary btn-sm" for="filter_exam">Lịch thi</label>
                    </div>

                    {{-- Bộ chọn ngày Datepicker --}}
                    <div class="input-group input-group-sm" style="width: 170px;">
                        <input type="date" name="date" class="form-control" value="{{ $selectedDate->toDateString() }}" 
                            onchange="this.form.submit()" title="Chọn ngày để nhảy tới tuần bất kỳ">
                    </div>

                    {{-- Các nút điều hướng xanh cyan (#1da1f2) & in lịch --}}
                    <div class="d-flex align-items-center gap-1">
                        <a href="{{ url()->current() }}?date={{ $currentWeekDate }}&filter={{ $filter }}" 
                            class="btn btn-huit-action btn-sm">
                            <i class="bi bi-clock-history"></i> Hiện tại
                        </a>
                        <a href="{{ url()->current() }}?date={{ $prevWeekDate }}&filter={{ $filter }}" 
                            class="btn btn-huit-action btn-sm">
                            <i class="bi bi-chevron-left"></i> Trở về
                        </a>
                        <a href="{{ url()->current() }}?date={{ $nextWeekDate }}&filter={{ $filter }}" 
                            class="btn btn-huit-action btn-sm">
                            Tiếp <i class="bi bi-chevron-right"></i>
                        </a>
                        <button type="button" class="btn btn-huit-action btn-sm" onclick="window.print()">
                            <i class="bi bi-printer"></i> In lịch
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Thông báo tuần --}}
    <div class="d-flex justify-content-between align-items-center mb-2 px-1">
        <span class="text-secondary small fw-medium">
            <i class="bi bi-info-circle"></i> Tuần từ: 
            <strong class="text-dark">{{ $startOfWeek->format('d/m/Y') }}</strong> đến 
            <strong class="text-dark">{{ $endOfWeek->format('d/m/Y') }}</strong>
        </span>
        @if (auth()->user()->isLecturer() || auth()->user()->isAdmin())
            <span class="badge bg-info text-dark">
                <i class="bi bi-pencil-square"></i> Nhấp vào thẻ môn học để Báo nghỉ / Đổi học Online
            </span>
        @endif
    </div>

    {{-- Bảng Lịch (Grid Table: 8 cột x 3 ca học) --}}
    <div class="table-responsive card border-0 shadow-sm print-area">
        <table class="table table-bordered mb-0 timetable-grid text-center">
            <thead>
                <tr class="header-row">
                    {{-- Cột 1: Ca học (nền vàng nhạt #ffffd4) --}}
                    <th scope="col" class="col-shift-header" style="background-color: #ffffd4; width: 110px; color: #0f2942;">
                        Ca học
                    </th>
                    {{-- 7 Cột tiếp theo: Thứ 2 đến Chủ nhật --}}
                    @foreach ($weekDays as $wDay)
                        <th scope="col" class="col-day-header {{ $wDay['is_today'] ? 'today-highlight' : '' }}" style="min-width: 145px;">
                            <div class="day-name fw-bold">{{ $wDay['day_name'] }}</div>
                            <div class="day-date small text-secondary">{{ \Carbon\Carbon::parse($wDay['date'])->format('d/m/Y') }}</div>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($shifts as $shiftKey => $shiftInfo)
                    <tr>
                        {{-- Cột ca học --}}
                        <td class="col-shift-cell align-middle" style="background-color: #ffffd4;">
                            <div class="fw-bold fs-6 text-dark">{{ $shiftInfo['name'] }}</div>
                            <div class="text-muted small">({{ $shiftInfo['periods'] }})</div>
                        </td>

                        {{-- 7 ô cho 7 ngày trong ca này --}}
                        @foreach ($weekDays as $wDay)
                            @php
                                $cellDate = $wDay['date'];
                                $items = $matrix[$shiftKey][$cellDate] ?? collect();
                            @endphp
                            <td class="timetable-cell align-top text-start {{ $wDay['is_today'] ? 'today-cell' : '' }}">
                                @if ($items->isEmpty())
                                    <div class="empty-cell-pattern"></div>
                                @else
                                    <div class="d-flex flex-column gap-2 p-1">
                                        @foreach ($items as $item)
                                            @php $style = $item->getCardStyle(); @endphp
                                            <div class="schedule-card" 
                                                style="background-color: {{ $style['bg'] }}; border-color: {{ $style['border'] }}; color: {{ $style['color'] }};"
                                                @if (auth()->user()->isLecturer() || auth()->user()->isAdmin())
                                                    onclick="openScheduleModal({{ $item->id }}, '{{ addslashes($item->subject_name) }}', '{{ $item->schedule_type }}', '{{ addslashes($item->meeting_link ?? '') }}', '{{ addslashes($item->note ?? '') }}')"
                                                    title="Bấm để chỉnh sửa trạng thái buổi dạy"
                                                    style="cursor: pointer;"
                                                @endif
                                            >
                                                {{-- Dòng 1: Tên môn học --}}
                                                <div class="card-subject fw-bold">
                                                    {{ $item->subject_name }}
                                                </div>

                                                {{-- Dòng 2: [Mã lớp danh nghĩa] - [Mã lớp học phần] --}}
                                                <div class="card-class-code small">
                                                    {{ $item->class_group_name }} - {{ $item->section_code ?? 'HP-HUIT' }}
                                                </div>

                                                {{-- Dòng 3: Tiết: [Tiết bắt đầu] - [Tiết kết thúc] --}}
                                                <div class="card-period small">
                                                    <i class="bi bi-clock"></i> Tiết: {{ $item->start_period }} - {{ $item->end_period }}
                                                </div>

                                                {{-- Dòng 4: Phòng: [Tên phòng và cơ sở] --}}
                                                <div class="card-room small">
                                                    <i class="bi bi-geo-alt"></i> {{ $item->room_info }}
                                                </div>

                                                {{-- Dòng 5: GV: [Họ tên giảng viên] --}}
                                                <div class="card-lecturer small">
                                                    <i class="bi bi-person"></i> GV: {{ $item->lecturer_name }}
                                                </div>

                                                {{-- Bổ sung: Nút link Online hoặc Lý do tạm ngưng --}}
                                                @if ($item->schedule_type === 'ONLINE')
                                                    <div class="mt-2 pt-1 border-top border-primary-subtle">
                                                        <a href="{{ $item->meeting_link ?? '#' }}" target="_blank" 
                                                            class="btn btn-sm btn-primary w-100 py-0 text-white fw-medium" style="font-size: 11px;">
                                                            <i class="bi bi-camera-video-fill"></i> Vào lớp Online
                                                        </a>
                                                    </div>
                                                @elseif ($item->schedule_type === 'SUSPENDED')
                                                    <div class="mt-1 pt-1 border-top border-white-50 small fw-bold text-white">
                                                        <i class="bi bi-exclamation-triangle-fill"></i> TẠM NGƯNG
                                                        @if ($item->note)
                                                            <div class="fw-normal" style="font-size: 11px;">{{ $item->note }}</div>
                                                        @endif
                                                    </div>
                                                @elseif ($item->schedule_type === 'EXAM')
                                                    <div class="mt-1 badge bg-warning text-dark border border-dark-subtle">
                                                        <i class="bi bi-award-fill"></i> LỊCH THI HỌC KỲ
                                                    </div>
                                                @endif

                                                @if (auth()->user()->isLecturer() && ($item->section_id || $item->section_code))
                                                    @php
                                                        $targetSectionId = $item->section_id;
                                                        if (!$targetSectionId && $item->section_code) {
                                                            $targetSectionId = \App\Models\CourseSection::where('section_code', $item->section_code)->value('id');
                                                        }
                                                    @endphp
                                                    @if ($targetSectionId)
                                                        <div class="mt-2 pt-1 border-top border-secondary-subtle">
                                                            <a href="{{ route('lecturer.attendances.take', ['section' => $targetSectionId, 'date' => $item->schedule_date]) }}" 
                                                               class="btn btn-sm btn-primary w-100 py-1 text-white fw-bold d-inline-flex align-items-center justify-content-center gap-1 shadow-sm"
                                                               style="font-size: 11px;"
                                                               onclick="event.stopPropagation();">
                                                                <i class="bi bi-person-check-fill"></i> Điểm danh buổi này
                                                            </a>
                                                        </div>
                                                    @endif
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Thanh chú thích 5 màu sắc (Legend Bar) ở đáy bảng --}}
    <div class="card border-0 shadow-sm mt-3 p-3">
        <div class="d-flex flex-wrap align-items-center justify-content-center gap-4 small fw-semibold">
            <div class="d-flex align-items-center gap-2">
                <span class="legend-box" style="background-color: #e8ecef; border: 1px solid #adb5bd;"></span>
                <span style="color: #0f2942;">Lịch học lý thuyết</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="legend-box" style="background-color: #68c136; border: 1px solid #4b9422;"></span>
                <span style="color: #0b2239;">Lịch học thực hành</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="legend-box" style="background-color: #74c0fc; border: 1px solid #228be6;"></span>
                <span style="color: #08203e;">Lịch học trực tuyến</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="legend-box" style="background-color: #fff3a1; border: 1px solid #f59f00;"></span>
                <span style="color: #4a3500;">Lịch thi</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="legend-box" style="background-color: #e0533c; border: 1px solid #b02a17;"></span>
                <span style="color: #b02a17;">Lịch tạm ngưng</span>
            </div>
        </div>
    </div>
</div>

{{-- Modal Popup cho Giảng viên / Admin thay đổi trạng thái buổi học --}}
@if (auth()->user()->isLecturer() || auth()->user()->isAdmin())
<div class="modal fade" id="scheduleModal" tabindex="-1" aria-labelledby="scheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="scheduleStatusForm" method="POST" action="">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold" id="scheduleModalLabel">Điều chỉnh buổi học</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label text-muted small">Môn học</label>
                        <input type="text" id="modalSubjectName" class="form-control fw-bold" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Chọn trạng thái buổi học</label>
                        <select name="schedule_type" id="modalScheduleType" class="form-select" onchange="toggleModalFields()">
                            <option value="THEORY">Lịch học lý thuyết (Trắng xám)</option>
                            <option value="PRACTICE">Lịch học thực hành (Xanh lá)</option>
                            <option value="ONLINE">Chuyển học Online (Xanh dương)</option>
                            <option value="SUSPENDED">Báo tạm ngưng / Nghỉ (Màu đỏ)</option>
                            <option value="EXAM">Lịch thi (Màu vàng)</option>
                        </select>
                    </div>

                    <div class="mb-3" id="modalOnlineLinkGroup" style="display: none;">
                        <label class="form-label fw-bold">Link học trực tuyến (Google Meet / Zoom)</label>
                        <input type="url" name="meeting_link" id="modalMeetingLink" class="form-control" 
                            placeholder="https://meet.google.com/abc-defg-hij">
                    </div>

                    <div class="mb-3" id="modalNoteGroup">
                        <label class="form-label fw-bold">Ghi chú / Lý do (nếu tạm ngưng)</label>
                        <textarea name="note" id="modalNote" class="form-control" rows="3" 
                            placeholder="Nhập lý do nghỉ hoặc dặn dò sinh viên..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary">Lưu cập nhật</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endif

{{-- CSS chuyên biệt cho bảng lịch HUIT và in ấn --}}
<style>
    .btn-huit-action {
        background-color: #1da1f2;
        color: #ffffff;
        border: none;
        font-weight: 500;
        transition: background-color 0.2s;
    }
    .btn-huit-action:hover {
        background-color: #0d8ddb;
        color: #ffffff;
    }
    .timetable-grid {
        table-layout: fixed;
        border-collapse: separate;
        border-spacing: 0;
    }
    .timetable-grid th, .timetable-grid td {
        border: 1px solid #e2e8f0;
    }
    .col-shift-header {
        font-size: 14px;
        vertical-align: middle;
    }
    .col-day-header {
        background-color: #f8fafc;
        padding: 10px 6px;
    }
    .col-day-header.today-highlight {
        background-color: #e0f2fe;
        border-bottom: 2px solid #0284c7;
    }
    .timetable-cell {
        min-height: 120px;
        height: 120px;
        position: relative;
        padding: 4px;
        background-color: #ffffff;
    }
    .timetable-cell.today-cell {
        background-color: #f0f9ff;
    }
    .empty-cell-pattern {
        width: 100%;
        height: 100%;
        min-height: 100px;
        background-image: radial-gradient(#e2e8f0 1.2px, transparent 1.2px);
        background-size: 10px 10px;
        opacity: 0.6;
    }
    .schedule-card {
        border-radius: 6px;
        border-width: 1px;
        border-style: solid;
        border-left-width: 4px;
        padding: 8px;
        font-size: 12px;
        line-height: 1.4;
        box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .schedule-card:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
    }
    .legend-box {
        display: inline-block;
        width: 18px;
        height: 18px;
        border-radius: 3px;
    }

    /* Print CSS chuẩn A4 ngang */
    @media print {
        @page {
            size: A4 landscape;
            margin: 8mm;
        }
        body {
            background-color: #ffffff !important;
        }
        .sidebar-wrapper, .navbar-custom, .footer-custom, #filterForm, .btn-huit-action, .modal {
            display: none !important;
        }
        .main-wrapper {
            margin-left: 0 !important;
            padding: 0 !important;
        }
        .timetable-container {
            width: 100% !important;
        }
        .card {
            box-shadow: none !important;
            border: none !important;
        }
        .timetable-grid {
            font-size: 11px;
        }
        .schedule-card {
            box-shadow: none !important;
            border-width: 1px !important;
            page-break-inside: avoid;
        }
    }
</style>

{{-- Script điều khiển Modal popup --}}
<script>
    function openScheduleModal(id, subjectName, scheduleType, meetingLink, note) {
        const form = document.getElementById('scheduleStatusForm');
        form.action = `/schedules/${id}/update-status`;
        document.getElementById('modalSubjectName').value = subjectName;
        document.getElementById('modalScheduleType').value = scheduleType;
        document.getElementById('modalMeetingLink').value = meetingLink || '';
        document.getElementById('modalNote').value = note || '';

        toggleModalFields();
        const modal = new bootstrap.Modal(document.getElementById('scheduleModal'));
        modal.show();
    }

    function toggleModalFields() {
        const type = document.getElementById('modalScheduleType').value;
        const onlineGroup = document.getElementById('modalOnlineLinkGroup');
        onlineGroup.style.display = (type === 'ONLINE') ? 'block' : 'none';
    }
</script>
@endsection
