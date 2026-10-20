@extends('layouts.spark')

@section('title', 'Trang sinh viên')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Trang sinh viên</h1>
            <p class="page-subtitle">Xin chào {{ $student->name }}. Theo dõi thông tin và hoạt động học tập của bạn.</p>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-7">
            <section class="card h-100 student-profile-card" aria-labelledby="student-profile-title">
                <div class="card-header">
                    <h2 class="card-title" id="student-profile-title">Thông tin sinh viên</h2>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-column flex-sm-row align-items-center align-items-sm-start gap-4">
                        <div class="student-profile-avatar" aria-hidden="true">
                            <i class="bi bi-person-fill"></i>
                        </div>
                        <dl class="row mb-0 flex-grow-1">
                            <dt class="col-sm-4">Họ tên</dt>
                            <dd class="col-sm-8">{{ $student->name }}</dd>
                            <dt class="col-sm-4">Email</dt>
                            <dd class="col-sm-8">{{ $student->email }}</dd>
                            <dt class="col-sm-4">Mã lớp</dt>
                            <dd class="col-sm-8">{{ $student->studentClass?->class_code ?? 'Chưa xếp lớp' }}</dd>
                            <dt class="col-sm-4">Tên lớp</dt>
                            <dd class="col-sm-8">{{ $student->studentClass?->class_name ?? 'Chưa xếp lớp' }}</dd>
                            <dt class="col-sm-4">Phòng học</dt>
                            <dd class="col-sm-8 mb-0">{{ $student->studentClass?->room ?: 'Chưa cập nhật' }}</dd>
                        </dl>
                    </div>
                </div>
            </section>
        </div>

        <div class="col-xl-5">
            <section class="row row-cols-1 row-cols-sm-2 g-3 h-100" aria-label="Tổng quan học tập">
                <div class="col">
                    <div class="card student-stat-card h-100">
                        <div class="card-body">
                            <p class="stat-label">Môn đã đăng ký</p>
                            <p class="student-stat-value">{{ $student->subjects_count }}</p>
                            <a href="{{ route('student.subjects.index') }}">Xem học phần</a>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card student-stat-card h-100">
                        <div class="card-body">
                            <p class="stat-label">Điểm đã công bố</p>
                            <p class="student-stat-value">{{ $student->grades_count }}</p>
                            <a href="{{ route('student.grades.index') }}">Xem kết quả</a>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card student-stat-card h-100">
                        <div class="card-body">
                            <p class="stat-label">Có mặt</p>
                            <p class="student-stat-value">{{ $presentCount }}</p>
                            <span class="text-muted">Lượt điểm danh</span>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card student-stat-card h-100">
                        <div class="card-body">
                            <p class="stat-label">Vắng</p>
                            <p class="student-stat-value">{{ $absentCount }}</p>
                            <span class="text-muted">Lượt điểm danh</span>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <section class="mb-4" aria-labelledby="student-shortcuts-title">
        <h2 class="visually-hidden" id="student-shortcuts-title">Truy cập nhanh</h2>
        <div class="row row-cols-1 row-cols-sm-2 g-3">
            <div class="col">
                <a class="card student-action-card h-100" href="{{ route('student.subjects.index') }}">
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

    <section class="card" aria-labelledby="recent-grades-title">
        <div class="card-header">
            <h2 class="card-title" id="recent-grades-title">Kết quả học tập gần đây</h2>
            <a href="{{ route('student.grades.index') }}">Xem tất cả</a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th scope="col">Mã môn</th>
                        <th scope="col">Tên môn học</th>
                        <th scope="col">Điểm</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentGrades as $grade)
                        <tr>
                            <td>{{ $grade->subject->code }}</td>
                            <td>{{ $grade->subject->name }}</td>
                            <td>{{ number_format((float) $grade->score, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center py-4">
                                Chưa có điểm được cập nhật.
                                <a href="{{ route('student.subjects.index') }}">Xem học phần</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
