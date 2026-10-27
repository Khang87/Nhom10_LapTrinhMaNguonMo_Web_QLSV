@extends('layouts.spark')

@section('title', 'Chương trình khung: ' . $specialization->name)

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-primary px-3 py-1 fs-6">{{ $specialization->code }}</span>
                <h1 class="h3 fw-bold text-dark mb-0">{{ $specialization->name }}</h1>
            </div>
            <p class="text-secondary mb-0">Cấu hình chi tiết khung chương trình đào tạo theo 8 học kỳ (Bắt buộc & Tự chọn)</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#addCourseModal">
                <i class="bi bi-plus-circle-fill"></i>
                <span>Thêm học phần vào kỳ</span>
            </button>
            <a href="{{ route('admin.specializations.index') }}" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2">
                <i class="bi bi-arrow-left"></i>
                <span>DS Chuyên ngành</span>
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="bi bi-check-circle-fill fs-5"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-5"></i>
            <div>{{ session('error') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Tabs hoặc Accordion 8 Học kỳ --}}
    <div class="row g-4">
        @for ($sem = 1; $sem <= 8; $sem++)
            @php
                $items = $curriculumBySemester[$sem] ?? collect();
                $totalCredits = $items->sum(fn($i) => $i->course?->credits ?? 0);
                $mandatoryCount = $items->where('is_mandatory', true)->count();
                $electiveCount = $items->where('is_mandatory', false)->count();
            @endphp
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-3 h-100">
                    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                                {{ $sem }}
                            </span>
                            <span class="fw-bold text-dark fs-6">Học kỳ {{ $sem }}</span>
                            @if ($sem === 1)
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle ms-1" title="Kỳ 1 sẽ tự động xếp cho sinh viên mới">
                                    <i class="bi bi-magic"></i> Auto Kỳ 1
                                </span>
                            @endif
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-light text-dark border">
                                {{ $totalCredits }} Tín chỉ
                            </span>
                            <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2"
                                    onclick="openAddModalForSemester({{ $sem }})" title="Thêm môn vào kỳ {{ $sem }}">
                                <i class="bi bi-plus-lg"></i> Thêm
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 small">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3" style="width: 90px;">Mã HP</th>
                                        <th>Tên học phần</th>
                                        <th class="text-center" style="width: 50px;">TC</th>
                                        <th class="text-center" style="width: 100px;">Loại</th>
                                        <th class="text-end pe-3" style="width: 80px;">Xử lý</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($items as $cItem)
                                        <tr>
                                            <td class="ps-3 fw-bold text-primary font-monospace">
                                                {{ $cItem->course?->course_code }}
                                            </td>
                                            <td>
                                                <div class="fw-semibold text-dark">{{ $cItem->course?->course_name }}</div>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-secondary-subtle text-secondary">{{ $cItem->course?->credits }}</span>
                                            </td>
                                            <td class="text-center">
                                                @if ($cItem->is_mandatory)
                                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                                        Bắt buộc
                                                    </span>
                                                @else
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                        Tự chọn
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="text-end pe-3">
                                                <div class="d-inline-flex gap-1">
                                                    <button type="button" class="btn btn-sm btn-light border p-1 text-warning" 
                                                            onclick="openEditModal({{ $cItem->id }}, {{ $cItem->semester_order }}, {{ $cItem->is_mandatory ? 1 : 0 }}, '{{ addslashes($cItem->course?->course_name) }}')"
                                                            title="Sửa thuộc tính">
                                                        <i class="bi bi-pencil-fill"></i>
                                                    </button>
                                                    <form method="POST" action="{{ route('admin.specializations.curriculum.remove', $cItem) }}" class="d-inline"
                                                          onsubmit="return confirm('Gỡ học phần [{{ $cItem->course?->course_name }}] khỏi Kỳ {{ $sem }}?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-light border p-1 text-danger" title="Xóa môn">
                                                            <i class="bi bi-trash-fill"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted">
                                                Chưa có môn học trong Kỳ {{ $sem }}.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @endfor
    </div>
</div>

{{-- Modal Thêm Môn Vào Kỳ --}}
<div class="modal fade" id="addCourseModal" tabindex="-1" aria-labelledby="addCourseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="{{ route('admin.specializations.curriculum.add', $specialization) }}">
                @csrf
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold" id="addCourseModalLabel">
                        <i class="bi bi-plus-circle text-primary me-2"></i>Thêm Học phần vào Chuyên ngành
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="modal_course_id" class="form-label fw-bold">Chọn Học phần <span class="text-danger">*</span></label>
                        <select class="form-select" id="modal_course_id" name="course_id" required>
                            <option value="">-- Chọn môn học từ danh mục --</option>
                            @foreach ($allCourses as $c)
                                <option value="{{ $c->id }}">
                                    [{{ $c->course_code }}] {{ $c->course_name }} ({{ $c->credits }} tín chỉ)
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="modal_semester_order" class="form-label fw-bold">Xếp vào Học kỳ <span class="text-danger">*</span></label>
                            <select class="form-select" id="modal_semester_order" name="semester_order" required>
                                @for ($i = 1; $i <= 8; $i++)
                                    <option value="{{ $i }}">Học kỳ {{ $i }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="modal_is_mandatory" class="form-label fw-bold">Tính chất môn <span class="text-danger">*</span></label>
                            <select class="form-select" id="modal_is_mandatory" name="is_mandatory" required>
                                <option value="1">Bắt buộc</option>
                                <option value="0">Tự chọn</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-1">
                        <i class="bi bi-plus-lg"></i> Thêm vào khung
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Sửa Học Phần Trong Khung --}}
<div class="modal fade" id="editItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="editItemForm" method="POST" action="">
                @csrf
                @method('PUT')
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-pencil-square text-warning me-2"></i>Chỉnh sửa học phần trong khung
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-light border mb-3">
                        <span class="text-secondary small d-block">Học phần:</span>
                        <strong class="text-dark fs-6" id="editCourseName"></strong>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="edit_semester_order" class="form-label fw-bold">Chuyển sang Học kỳ</label>
                            <select class="form-select" id="edit_semester_order" name="semester_order" required>
                                @for ($i = 1; $i <= 8; $i++)
                                    <option value="{{ $i }}">Học kỳ {{ $i }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_is_mandatory" class="form-label fw-bold">Tính chất học phần</label>
                            <select class="form-select" id="edit_is_mandatory" name="is_mandatory" required>
                                <option value="1">Bắt buộc</option>
                                <option value="0">Tự chọn</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-warning text-dark fw-bold">
                        Lưu thay đổi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openAddModalForSemester(sem) {
    document.getElementById('modal_semester_order').value = sem;
    const modal = new bootstrap.Modal(document.getElementById('addCourseModal'));
    modal.show();
}

function openEditModal(itemId, semesterOrder, isMandatory, courseName) {
    document.getElementById('editCourseName').innerText = courseName;
    document.getElementById('edit_semester_order').value = semesterOrder;
    document.getElementById('edit_is_mandatory').value = isMandatory;
    document.getElementById('editItemForm').action = '/admin/specializations/curriculum/' + itemId;
    const modal = new bootstrap.Modal(document.getElementById('editItemModal'));
    modal.show();
}
</script>
@endsection
