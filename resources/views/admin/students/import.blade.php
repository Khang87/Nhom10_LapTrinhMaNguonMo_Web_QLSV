@extends('layouts.spark')
@section('title', 'Import danh sách sinh viên')
@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="h3 mb-0">Import danh sách sinh viên</h1>
        <a class="btn btn-outline-secondary" href="{{ route('admin.students.index') }}">Quay lại</a>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.students.import.store') }}" enctype="multipart/form-data"
                class="row g-2 align-items-end">
                @csrf
                <div class="col-md-6">
                    <label class="form-label" for="file">Chọn file .xlsx</label>
                    <input class="form-control" id="file" name="file" type="file" accept=".xlsx" required>
                </div>
                <div class="col-auto">
                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-upload me-1" aria-hidden="true"></i>Nhập danh sách
                    </button>
                </div>
                <div class="col-12">
                    <a class="btn btn-outline-primary mt-2" href="{{ route('admin.students.import.template') }}">
                        <i class="bi bi-download me-1" aria-hidden="true"></i>Tải mẫu Excel
                    </a>
                </div>
            </form>
            <ul class="text-muted small mb-0 mt-3">
                <li>Cột bắt buộc theo mẫu: Mã SV, Họ tên, Email, Ngày sinh, Giới tính, Quê quán, SĐT, Lớp, Tình trạng.</li>
                <li>Trùng mã sinh viên sẽ cập nhật hồ sơ và giữ nguyên mật khẩu đang dùng.</li>
                <li>Sinh viên mới tạo có mật khẩu khởi điểm là <code>{{ App\Support\PersonLabels::IMPORT_DEFAULT_PASSWORD }}</code>.</li>
                <li>Dòng thiếu họ tên/email, lớp không tồn tại hoặc ngày sinh sai định dạng sẽ được bỏ qua kèm lý do.</li>
                <li>Mỗi lần nhập tối đa {{ App\Support\StudentImport::MAX_ROWS }} dòng, file .xlsx không quá 25 MB.</li>
            </ul>
        </div>
    </div>

    @if ($result)
        <div class="card">
            <div class="card-header">Kết quả lần nhập gần nhất</div>
            <div class="card-body">
                <p class="mb-3">{{ $result['summary'] }}</p>
                @if (count($result['skipped']) > 0)
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm bg-white align-middle mb-0">
                            <thead class="table-light"><tr><th style="width:120px">Dòng</th><th>Lý do bỏ qua</th></tr></thead>
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
                    <p class="text-muted mb-0">Không có dòng nào bị bỏ qua.</p>
                @endif
            </div>
        </div>
    @endif
@endsection
