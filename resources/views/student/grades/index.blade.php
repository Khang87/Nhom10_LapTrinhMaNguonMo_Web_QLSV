@extends('layouts.spark')

@section('title', 'Kết quả học tập')

@section('content')
    <x-page-header title="Kết quả học tập"
        subtitle="Điểm các học phần đang học, tính theo trọng số chuyên cần / giữa kỳ / cuối kỳ.">
        <x-slot name="actions">
            <a class="btn btn-custom btn-custom-light" href="{{ route('student.grades.history') }}">Lịch sử học tập</a>
        </x-slot>
    </x-page-header>

    <div class="card">
        <div class="card-body">
            @if ($summary['gpa'] === null)
                <p class="mb-0 text-muted-green">Chưa có điểm đã công bố nên chưa tính được trung bình tích lũy.</p>
            @else
                <p class="mb-1">Trung bình tích lũy: hệ 4 = {{ number_format($summary['gpa'], 2) }}, hệ 10 = {{ number_format($summary['average_ten'], 2) }}, tín chỉ đã tích lũy = {{ $summary['credits_accumulated'] }} TC, xếp loại = {{ $summary['classification'] }}</p>
                @if ($summary['failing_subjects'] > 0)
                    <div class="form-text">Đang có {{ $summary['failing_subjects'] }} môn F chưa học lại.</div>
                @endif
            @endif
        </div>
    </div>

    <x-data-table>
        @include('student.grades.table', ['rows' => $rows, 'components' => $components])
    </x-data-table>
@endsection
