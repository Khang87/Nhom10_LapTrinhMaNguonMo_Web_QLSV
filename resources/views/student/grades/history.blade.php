@extends('layouts.spark')

@section('title', 'Lịch sử học tập')

@section('content')
    <x-page-header title="Lịch sử học tập"
        subtitle="Mọi học phần đã học, kể cả lớp đã hủy và môn học lại.">
        <x-slot name="actions">
            <a class="btn btn-custom btn-custom-light" href="{{ route('student.grades.index') }}">Quay lại</a>
        </x-slot>
    </x-page-header>

    <x-data-table>
        @include('student.grades.table', [
            'rows' => $rows,
            'components' => $components,
            'showStatus' => true,
        ])
    </x-data-table>
@endsection
