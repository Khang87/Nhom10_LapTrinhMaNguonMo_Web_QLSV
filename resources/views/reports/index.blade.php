@extends('layouts.spark')

@section('title', 'Thống kê & báo cáo')

@section('content')
    @php
        $first = $report['rows'][0] ?? [];
        $numeric = array_keys(array_filter($first, fn ($value) => is_int($value) || is_float($value)));
        $cell = fn (mixed $value): string => match (true) {
            $value === null || $value === '' => '—',
            is_float($value) => number_format($value, round($value, 1) === $value ? 1 : 2, ',', '.'),
            default => (string) $value,
        };
        $percent = fn (int $index): bool => str_contains($report['header'][$index], '(%)');
    @endphp

    <x-page-header title="Thống kê & báo cáo"
        subtitle="Một bộ lọc dùng chung cho bốn bảng số; mọi con số tính lại từ dữ liệu đào tạo, không qua bảng trung gian.">
        <x-slot name="actions">
            <x-export-buttons :route="$routeBase.'.export'" :query="array_merge(request()->query(), ['type' => $type])" />
        </x-slot>
    </x-page-header>

    <ul class="nav nav-pills mb-3">
        @foreach (App\Support\Reports::TYPES as $key => $label)
            <li class="nav-item" role="presentation">
                <a class="nav-link {{ $type === $key ? 'active' : '' }}"
                    @if ($type === $key) aria-current="page" @endif
                    href="{{ route("{$routeBase}.show", $key) }}">{{ $label }}</a>
            </li>
        @endforeach
    </ul>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Bộ lọc</h2>
        </div>
        <form class="row g-3 align-items-end" method="GET" action="{{ route("{$routeBase}.show", $type) }}">
            @foreach ($filterOptions as $name => $option)
                <div class="col-sm-6 col-md-3">
                    <label class="form-label-custom" for="filter_{{ $name }}">{{ $option['label'] }}</label>
                    <select class="form-select form-select-custom" id="filter_{{ $name }}" name="{{ $name }}">
                        <option value="">{{ $option['blank'] }}</option>
                        @foreach ($option['options'] as $value => $text)
                            <option value="{{ $value }}"
                                @selected((string) $filter->values()[$name] === (string) $value)>{{ $text }}</option>
                        @endforeach
                    </select>
                </div>
            @endforeach
            <div class="col-auto d-flex gap-2">
                <button class="btn btn-custom btn-custom-primary" type="submit">
                    <i class="bi bi-search" aria-hidden="true"></i>Tìm kiếm
                </button>
                @if (array_filter($filter->values(), fn ($value, $key) => $key !== 'semester_id' && $value !== null, ARRAY_FILTER_USE_BOTH))
                    <a class="btn btn-custom btn-custom-light" href="{{ route("{$routeBase}.show", $type) }}">
                        <i class="bi bi-arrow-clockwise" aria-hidden="true"></i>Đặt lại
                    </a>
                @endif
            </div>
        </form>
    </div>

    <x-data-table :title="$report['title']">
        <thead>
            <tr>
                @foreach ($report['header'] as $index => $head)
                    <th @if (in_array($index, $numeric, true)) class="text-end" @endif>{{ $head }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($report['rows'] as $row)
                <tr>
                    @foreach ($row as $index => $value)
                        <td @if (in_array($index, $numeric, true)) class="text-end" @endif>
                            {{ $cell($value) }}
                            @if ($percent($index) && is_float($value))
                                <div class="progress mt-1" style="height: 4px" role="progressbar"
                                    aria-label="Tỷ lệ {{ $cell($value) }} phần trăm" aria-valuenow="{{ round($value) }}"
                                    aria-valuemin="0" aria-valuemax="100">
                                    <div class="progress-bar" style="width: {{ min(100, round($value)) }}%"></div>
                                </div>
                            @endif
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($report['header']) }}">Bộ lọc hiện tại chưa có dữ liệu.</td>
                </tr>
            @endforelse
        </tbody>
        <x-slot name="footer">
            {{ count($report['rows']) }} dòng · kỳ đang xem: {{ $filter->semester()?->label ?? 'toàn bộ thời gian' }}
        </x-slot>
    </x-data-table>
@endsection
