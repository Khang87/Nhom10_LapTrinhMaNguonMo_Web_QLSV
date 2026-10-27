@extends('layouts.spark')

@section('title', 'Kết quả học tập')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h3 mb-0">Kết quả học tập</h1>
            <p class="text-muted mb-0">Điểm các học phần đang học, tính theo trọng số chuyên cần / giữa kỳ / cuối kỳ.</p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('student.grades.history') }}">Lịch sử học tập</a>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            @if ($summary['gpa'] === null)
                <p class="mb-0 text-muted">Chưa có điểm đã công bố nên chưa tính được trung bình tích lũy.</p>
            @else
                <p class="mb-1">Trung bình tích lũy: hệ 4 = {{ number_format($summary['gpa'], 2) }}, hệ 10 = {{ number_format($summary['average_ten'], 2) }}, tín chỉ đã tích lũy = {{ $summary['credits_accumulated'] }} TC, xếp loại = {{ $summary['classification'] }}</p>
                @if ($summary['failing_subjects'] > 0)
                    <div class="form-text">Đang có {{ $summary['failing_subjects'] }} môn F chưa học lại.</div>
                @endif
            @endif
        </div>
    </div>

    @include('student.grades.table', ['rows' => $rows, 'components' => $components])
@endsection
