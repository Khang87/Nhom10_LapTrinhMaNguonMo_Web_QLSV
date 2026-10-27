@extends('layouts.spark')

@section('title', 'Trang quản trị')

@section('content')
    @php
        $modules = [
            ['route' => 'admin.classes.index', 'icon' => 'bi-building', 'label' => 'Lớp học',
                'note' => 'Quản lý danh sách lớp và phòng học.'],
            ['route' => 'admin.subjects.index', 'icon' => 'bi-book', 'label' => 'Môn học',
                'note' => 'Cập nhật môn học và số tín chỉ.'],
            ['route' => 'admin.departments.index', 'icon' => 'bi-diagram-3', 'label' => 'Khoa',
                'note' => 'Quản lý khoa và chuyên ngành trực thuộc.'],
            ['route' => 'admin.majors.index', 'icon' => 'bi-mortarboard', 'label' => 'Chuyên ngành',
                'note' => 'Chuyên ngành thuộc khoa và môn học của ngành.'],
            ['route' => 'admin.study-plans.index', 'icon' => 'bi-list-nested', 'label' => 'Khung CTĐT',
                'note' => 'Xếp môn theo học kỳ dự kiến cho từng chuyên ngành.'],
            ['route' => 'admin.students.index', 'icon' => 'bi-people', 'label' => 'Sinh viên',
                'note' => 'Quản lý hồ sơ và lớp của sinh viên.'],
            ['route' => 'admin.grades.index', 'icon' => 'bi-journal-check', 'label' => 'Điểm',
                'note' => 'Nhập và theo dõi kết quả học tập.'],
            ['route' => 'admin.attendances.index', 'icon' => 'bi-calendar-check', 'label' => 'Điểm danh',
                'note' => 'Ghi nhận và xem tình hình chuyên cần.'],
            ['route' => 'admin.accounts.index', 'icon' => 'bi-person-badge', 'label' => 'Tài khoản',
                'note' => 'Quản lý tài khoản quản trị, giảng viên, sinh viên.'],
        ];
    @endphp

    <x-page-header title="Trang quản trị" subtitle="Quản lý thông tin đào tạo và kết quả học tập." />

    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
        @foreach ($modules as $module)
            <div class="col">
                <a class="card h-100 text-decoration-none" href="{{ route($module['route']) }}">
                    <div class="d-flex align-items-center gap-3">
                        <i class="bi {{ $module['icon'] }} fs-2 text-muted-green" aria-hidden="true"></i>
                        <div>
                            <h2 class="card-title mb-1">{{ $module['label'] }}</h2>
                            <p class="page-subtitle mb-0">{{ $module['note'] }}</p>
                        </div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>
@endsection
