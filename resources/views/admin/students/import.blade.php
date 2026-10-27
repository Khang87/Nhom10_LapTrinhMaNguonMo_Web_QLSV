@extends('layouts.spark')
@section('title', 'Import danh sách sinh viên')
@section('content')
    <x-page-header title="Import danh sách sinh viên">
        <x-slot name="actions">
            <a class="btn btn-custom btn-custom-light" href="{{ route('admin.students.index') }}">Quay lại</a>
        </x-slot>
    </x-page-header>

    <div class="card">
        <form class="row g-2 align-items-end" method="POST" action="{{ route('admin.students.import.store') }}"
            enctype="multipart/form-data">
            @csrf
            <div class="col-md-6">
                <label class="form-label-custom" for="file">Chọn file .xlsx</label>
                <input class="form-control form-control-custom" id="file" name="file" type="file" accept=".xlsx" required>
            </div>
            <div class="col-auto">
                <button class="btn btn-custom btn-custom-primary" type="submit">
                    <i class="bi bi-upload me-1" aria-hidden="true"></i>Nhập danh sách
                </button>
            </div>
            <div class="col-12">
                <a class="btn btn-custom btn-custom-outline-primary mt-2" href="{{ route('admin.students.import.template') }}">
                    <i class="bi bi-download me-1" aria-hidden="true"></i>Tải mẫu Excel
                </a>
            </div>
        </form>
        <ul class="fs-xs text-muted-green mb-0 mt-3">
            <li>Cột bắt buộc theo mẫu: Mã SV, Họ tên, Email, Ngày sinh, Giới tính, Quê quán, SĐT, Lớp, Tình trạng.</li>
            <li>Trùng mã sinh viên sẽ cập nhật hồ sơ và giữ nguyên mật khẩu đang dùng.</li>
            <li>Sinh viên mới tạo có mật khẩu khởi điểm là <code>{{ App\Support\PersonLabels::IMPORT_DEFAULT_PASSWORD }}</code>.</li>
            <li>Dòng thiếu họ tên/email, lớp không tồn tại hoặc ngày sinh sai định dạng sẽ được bỏ qua kèm lý do.</li>
            <li>Mỗi lần nhập tối đa {{ App\Support\StudentImport::MAX_ROWS }} dòng, file .xlsx không quá 25 MB.</li>
        </ul>
    </div>

    @if ($result)
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Kết quả lần nhập gần nhất</h2>
            </div>
            <p class="mb-3">{{ $result['summary'] }}</p>
            @if (count($result['skipped']) > 0)
                <div class="table-responsive">
                    <table class="table-custom">
                        <thead><tr><th scope="col" style="width:120px">Dòng</th><th scope="col">Lý do bỏ qua</th></tr></thead>
                        <tbody>
                            @foreach ($result['skipped'] as $row)
                                <tr>
                                    <td>Dòng {{ $row['row'] }}</td>
                                    <td>{{ $row['reason'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-muted-green mb-0">Không có dòng nào bị bỏ qua.</p>
            @endif
        </div>
    @endif
@endsection
