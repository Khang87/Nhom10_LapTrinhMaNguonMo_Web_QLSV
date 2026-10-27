@extends('layouts.spark')

@section('title', 'Lịch sử học tập')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h3 mb-0">Lịch sử học tập</h1>
            <p class="text-muted mb-0">Mọi học phần đã học, kể cả lớp đã hủy và môn học lại.</p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('student.grades.index') }}">Quay lại</a>
    </div>

    @include('student.grades.table', ['rows' => $rows, 'components' => $components, 'showStatus' => true])
@endsection
