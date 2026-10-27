@extends('layouts.spark')

@section('title', 'Trang sinh viên')

@section('content')
    <x-page-header title="Trang sinh viên"
        subtitle="Xin chào {{ $student->name }}. Theo dõi thông tin và hoạt động học tập của bạn." />

    <div class="row g-4 mb-4">
        <div class="col-xl-7">
            <section class="card h-100" aria-labelledby="student-profile-title">
                <div class="card-header">
                    <h2 class="card-title" id="student-profile-title">Thông tin sinh viên</h2>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-column flex-sm-row align-items-center align-items-sm-start gap-4">
                        <img class="table-user-avatar table-user-avatar-lg" width="58" height="58"
                            alt="Ảnh đại diện của {{ $student->name }}" src="{{ e($student->avatarUrl()) }}">
                        <dl class="row mb-0 flex-grow-1">
                            <dt class="col-sm-4 fs-xs text-muted-green">Họ tên</dt>
                            <dd class="col-sm-8">{{ $student->name }}</dd>
                            <dt class="col-sm-4 fs-xs text-muted-green">Email</dt>
                            <dd class="col-sm-8">{{ $student->email }}</dd>
                            <dt class="col-sm-4 fs-xs text-muted-green">Mã lớp</dt>
                            <dd class="col-sm-8">{{ $student->studentClass?->class_code ?? 'Chưa xếp lớp' }}</dd>
                            <dt class="col-sm-4 fs-xs text-muted-green">Tên lớp</dt>
                            <dd class="col-sm-8">{{ $student->studentClass?->class_name ?? 'Chưa xếp lớp' }}</dd>
                            <dt class="col-sm-4 fs-xs text-muted-green">Khóa</dt>
                            <dd class="col-sm-8">{{ $student->studentClass?->intake_year ?: 'Chưa cập nhật' }}</dd>
                            <dt class="col-sm-4 fs-xs text-muted-green">GVCN</dt>
                            <dd class="col-sm-8 mb-0">{{ $student->studentClass?->adviser?->name ?? 'Chưa phân công' }}</dd>
                        </dl>
                    </div>
                </div>
            </section>
        </div>

        <div class="col-xl-5">
            <section class="row row-cols-1 row-cols-sm-2 g-3 h-100" aria-label="Tổng quan học tập">
                <div class="col">
                    <x-stat-card label="Học phần đã đăng ký" :value="$enrolledCount">
                        <a href="{{ route('student.course-offerings.index') }}">Xem học phần</a>
                    </x-stat-card>
                </div>
                <div class="col">
                    <x-stat-card label="Điểm đã công bố" :value="$gradesCount">
                        <a href="{{ route('student.grades.index') }}">Xem kết quả</a>
                    </x-stat-card>
                </div>
                <div class="col">
                    <x-stat-card label="Có mặt" :value="$presentCount" hint="Lượt điểm danh" />
                </div>
                <div class="col">
                    <x-stat-card label="Vắng" :value="$absentCount" hint="Lượt điểm danh" />
                </div>
            </section>
        </div>
    </div>

    <section class="mb-4" aria-labelledby="student-shortcuts-title">
        <h2 class="visually-hidden" id="student-shortcuts-title">Truy cập nhanh</h2>
        <div class="row row-cols-1 row-cols-sm-2 g-3">
            <div class="col">
                <a class="card student-action-card h-100" href="{{ route('student.course-offerings.index') }}">
                    <div class="card-body d-flex align-items-center gap-3">
                        <i class="bi bi-journal-plus" aria-hidden="true"></i>
                        <span>Đăng ký học phần</span>
                        <i class="bi bi-arrow-right ms-auto" aria-hidden="true"></i>
                    </div>
                </a>
            </div>
            <div class="col">
                <a class="card student-action-card h-100" href="{{ route('student.grades.index') }}">
                    <div class="card-body d-flex align-items-center gap-3">
                        <i class="bi bi-bar-chart-line" aria-hidden="true"></i>
                        <span>Kết quả học tập</span>
                        <i class="bi bi-arrow-right ms-auto" aria-hidden="true"></i>
                    </div>
                </a>
            </div>
        </div>
    </section>

    <x-data-table title="Kết quả học tập gần đây">
        <x-slot name="controls">
            <a href="{{ route('student.grades.index') }}">Xem tất cả</a>
        </x-slot>
        @include('student.grades.table', [
            'rows' => $recentGrades,
            'components' => $components,
            'emptyNote' => '<a href="'.route('student.course-offerings.index').'">Xem học phần</a>',
        ])
    </x-data-table>
@endsection
