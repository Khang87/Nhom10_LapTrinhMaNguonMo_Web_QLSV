@extends('layouts.spark')

@section('title', 'Nhập Điểm Học Phần - Giảng Viên')

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1">Nhập Điểm Học Phần</h1>
            <p class="text-secondary small mb-0">
                Danh sách các lớp học phần được phân công giảng dạy. Điểm nhập sẽ được cập nhật trực tiếp cho sinh viên và đồng bộ sang Ban Quản trị.
            </p>
        </div>
        @if ($lecturer)
            <div class="d-flex align-items-center gap-2 bg-white border rounded-3 p-2 px-3 shadow-sm">
                <div class="rounded-circle bg-dark text-white d-flex align-items-center justify-content-center fw-bold" style="width: 38px; height: 38px;">
                    {{ mb_substr($lecturer->full_name, 0, 1) }}
                </div>
                <div>
                    <div class="fw-bold text-dark small">{{ $lecturer->full_name }}</div>
                    <div class="text-secondary small" style="font-size: 0.75rem;">Mã GV: {{ $lecturer->lecturer_code }}</div>
                </div>
            </div>
        @endif
    </div>

    @if (session('success'))
        <div class="alert alert-light border shadow-sm rounded-3 d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="bi bi-check-circle-fill text-success fs-5"></i>
            <div class="text-dark small">{{ session('success') }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Danh sách lớp học phần -->
    <div class="card border shadow-sm rounded-3 bg-white">
        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
            <strong class="text-dark fs-6">Lớp Học Phần Phụ Trách ({{ $sections->count() }} lớp)</strong>
            <span class="badge bg-light text-secondary border">Học kỳ 1 • 2026-2027</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-secondary small">
                    <tr>
                        <th class="ps-3" style="width: 60px;">#</th>
                        <th style="width: 150px;">Mã Lớp HP</th>
                        <th>Tên Môn Học</th>
                        <th style="width: 80px;" class="text-center">Số TC</th>
                        <th style="width: 140px;">Học kỳ</th>
                        <th style="width: 120px;" class="text-center">Số SV ĐK</th>
                        <th style="width: 180px;">Tiến độ nhập điểm</th>
                        <th class="text-end pe-3" style="width: 160px;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sections as $index => $sec)
                        @php
                            $totalEnr = $sec->enrollments_count ?? $sec->enrollments->count();
                            $gradedCount = $sec->enrollments->filter(fn($e) => $e->process_score !== null || $e->midterm_score !== null || $e->final_score !== null)->count();
                            $percent = $totalEnr > 0 ? round(($gradedCount / $totalEnr) * 100) : 0;
                        @endphp
                        <tr>
                            <td class="ps-3 text-muted small">{{ $index + 1 }}</td>
                            <td>
                                <span class="badge bg-light text-dark border font-monospace fw-bold">
                                    {{ $sec->section_code }}
                                </span>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $sec->course?->course_name }}</div>
                                <div class="text-secondary small font-monospace">{{ $sec->course?->course_code }} • {{ $sec->room_name ?? 'Phòng học' }}</div>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border">{{ $sec->course?->credits ?? 3 }}</span>
                            </td>
                            <td class="small text-secondary">{{ $sec->semester_code }}</td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border">{{ $totalEnr }} SV</span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height: 6px;">
                                        <div class="progress-bar bg-dark" role="progressbar" style="width: {{ $percent }}%" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <span class="small font-monospace text-secondary" style="font-size: 0.75rem;">{{ $gradedCount }}/{{ $totalEnr }}</span>
                                </div>
                            </td>
                            <td class="text-end pe-3">
                                <a href="{{ route('lecturer.grades.edit', $sec) }}" class="btn btn-sm btn-dark d-inline-flex align-items-center gap-1.5 px-3 py-1.5 shadow-sm">
                                    <i class="bi bi-pencil-square"></i>
                                    <span>Nhập điểm</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted small">
                                <i class="bi bi-journal-x fs-2 d-block mb-2 text-secondary opacity-50"></i>
                                Hiện tại chưa có lớp học phần nào được phân công cho bạn.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
