@extends('layouts.spark')

@section('title', 'Nhập điểm · '.$offering->label())

@section('content')
    <x-page-header title="Nhập điểm"
        subtitle="{{ $offering->subject?->label() ?? '—' }} · nhóm {{ $offering->group_code }} · {{ $offering->semester?->label ?? '—' }}">
        <x-slot name="actions">
            <x-status-badge :tone="$isPublished ? 'success' : 'pending'">
                {{ $isPublished ? 'Đã công bố' : 'Chưa công bố' }}
            </x-status-badge>
            <a class="btn btn-custom btn-custom-light" href="{{ route('teacher.offerings.index') }}">Quay lại</a>
        </x-slot>
    </x-page-header>

    <div class="card mb-3">
        <div class="card-body d-flex flex-wrap gap-4 align-items-center">
            <div class="fs-xs text-muted-green">
                Sĩ số: {{ $size }}@if ($capacity > 0) / {{ $capacity }}@endif
            </div>
            <div class="fs-xs text-muted-green">
                Trung bình lớp: {{ $classAverage === null ? '—' : number_format($classAverage, 2) }}
            </div>
            <div class="fs-xs text-muted-green">
                Môn F: {{ $failingCount }}
            </div>
            <form class="ms-auto" method="POST"
                action="{{ route('teacher.course-offerings.grades.publish', $offering) }}">
                @csrf
                <button class="btn btn-custom btn-custom-outline-primary" type="submit"
                    @if ($size === 0) disabled @endif>
                    <i class="bi bi-megaphone me-1" aria-hidden="true"></i>Công bố điểm
                </button>
            </form>
        </div>
    </div>

    <form method="POST" action="{{ route('teacher.course-offerings.grades.update', $offering) }}">
        @csrf
        <x-data-table>
            <thead>
                <tr>
                    <th scope="col">STT</th>
                    <th scope="col">Sinh viên</th>
                    @foreach ($components as $component)
                        <th scope="col">{{ $component['label'] }} ({{ $component['weight'] }}%)</th>
                    @endforeach
                    <th scope="col">Tổng kết</th>
                    <th scope="col">Chữ</th>
                    <th scope="col">Hệ 4</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    @php($grade = $row['grade'])
                    <tr>
                        <td class="table-order-id">{{ $loop->iteration }}</td>
                        <td>
                            <x-person-cell :name="$row['student']->name"
                                :sub="$row['student']->person_code ?: 'chưa có mã'" />
                        </td>
                        @foreach ($components as $component)
                            <td>
                                <label class="visually-hidden"
                                    for="{{ $component['key'] }}-{{ $row['student']->id }}">
                                    {{ $row['student']->name }} — {{ $component['label'] }}
                                </label>
                                <input class="form-control-custom form-control-custom-sm{{ $errors->has($component['key'].'.'.$row['student']->id) ? ' is-invalid-custom' : '' }}"
                                    id="{{ $component['key'] }}-{{ $row['student']->id }}"
                                    name="{{ $component['key'] }}[{{ $row['student']->id }}]" type="number"
                                    min="0" max="10" step="0.01" value="{{ $grade?->{$component['key']} }}">
                                @error($component['key'].'.'.$row['student']->id)
                                    <div class="form-feedback-custom invalid-custom">{{ $message }}</div>
                                @enderror
                            </td>
                        @endforeach
                        <td class="table-amount">
                            {{ $grade?->subjectScore() === null ? '—' : number_format($grade->subjectScore(), 2) }}
                        </td>
                        <td>{{ $grade?->letter() ?? '—' }}</td>
                        <td class="table-amount">
                            {{ $grade?->subjectScore() === null ? '—' : number_format($grade->fourScore(), 2) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ 5 + count($components) }}" class="text-center py-5">
                            <div class="empty-state-icon mb-2"><i class="bi bi-inbox" aria-hidden="true"></i></div>
                            <div class="table-user-sub">Chưa có sinh viên nào đang học ở lớp này.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-data-table>

        <div class="d-flex flex-wrap align-items-center gap-3 mt-3">
            <button class="btn btn-custom btn-custom-primary" type="submit"
                @if ($size === 0) disabled @endif>
                <i class="bi bi-check-lg me-1" aria-hidden="true"></i>Lưu tất cả
            </button>
            <div class="form-text mb-0">
                Ô để trống có nghĩa là chưa có điểm, nên lưu ô trống sẽ xóa điểm đã nhập nhầm. Điểm tổng kết,
                chữ và hệ 4 tính lại từ ba cột nhập, không phải ô thứ tư để gõ.
            </div>
        </div>
    </form>
@endsection
