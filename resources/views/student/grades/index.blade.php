@extends('layouts.spark')

@section('title', 'Kết quả học tập')

@section('content')
    <h1 class="h3 mb-3">Kết quả học tập</h1>
    <div class="table-responsive">
        <table class="table table-bordered table-hover bg-white align-middle">
            <thead class="table-light">
                <tr><th>Mã môn</th><th>Tên môn</th><th>Điểm</th></tr>
            </thead>
            <tbody>
                @forelse ($grades as $grade)
                    <tr>
                        <td>{{ $grade->subject->code }}</td>
                        <td>{{ $grade->subject->name }}</td>
                        <td>{{ $grade->score }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center">Chưa có điểm được cập nhật.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $grades->links() }}
@endsection
