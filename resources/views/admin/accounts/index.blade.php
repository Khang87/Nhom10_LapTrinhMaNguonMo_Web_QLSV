@extends('layouts.spark')
@section('title', 'Quản lý tài khoản')
@section('content')
    <x-page-header title="Quản lý tài khoản">
        <x-slot name="actions">
            <a class="btn btn-custom btn-custom-primary" href="{{ route('admin.accounts.create') }}">Thêm tài khoản</a>
        </x-slot>
    </x-page-header>

    <div class="row g-3 mb-3">
        @foreach ($stats as $stat)
            <div class="col-6 col-lg-3">
                <x-stat-card :label="$stat['label']" :value="$stat['value']" />
            </div>
        @endforeach
    </div>

    <form class="row g-2 mb-3" method="GET" action="{{ route('admin.accounts.index') }}" data-live-search
        data-target="#account-table-body" data-pagination="account-pagination" data-unit="tài khoản"
        aria-label="Tìm tài khoản">
        <div class="col-sm-8 col-md-4">
            <label class="visually-hidden" for="search">Tìm tài khoản</label>
            <input class="form-control form-control-custom" id="search" name="search" value="{{ $search }}"
                placeholder="Tên, email, MSSV/Mã GV hoặc số điện thoại" autocomplete="off">
        </div>
        <div class="col-sm-8 col-md-3">
            <label class="visually-hidden" for="role">Lọc theo vai trò</label>
            <select class="form-select form-select-custom" id="role" name="role">
                <option value="">Mọi vai trò</option>
                @foreach (\App\Models\User::ROLE_LABELS as $value => $label)
                    <option value="{{ $value }}" @selected($role === $value)>{{ $label }} ({{ $value }})</option>
                @endforeach
            </select>
        </div>
        <div class="col-auto"><button class="btn btn-custom btn-custom-outline-primary" type="submit">Tìm kiếm</button></div>
    </form>
    <p class="fs-xs text-muted-green mb-2" id="live-search-status" aria-live="polite">
        {{ $accounts->total() }} tài khoản.
    </p>
    <x-data-table>
        <thead>
            <tr>
                <th scope="col">#</th>
                <th scope="col">Mã</th>
                <th scope="col">Họ tên</th>
                <th scope="col">Vai trò</th>
                <th scope="col">Học vị</th>
                <th scope="col">Đơn vị</th>
                <th scope="col">Email</th>
                <th scope="col">Tình trạng</th>
                <th scope="col">Thao tác</th>
            </tr>
        </thead>
        <tbody id="account-table-body">
            @include('admin.accounts.rows')
        </tbody>
        <x-slot name="footer">
            <div id="account-pagination">{{ $accounts->links() }}</div>
        </x-slot>
    </x-data-table>
@endsection
