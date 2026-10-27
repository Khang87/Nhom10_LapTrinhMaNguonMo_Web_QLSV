<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label" for="cohort_year">Khóa tuyển sinh <span class="text-danger">*</span></label>
        <input class="form-control" id="cohort_year" name="cohort_year" type="number" min="2000" max="2099"
            value="{{ old('cohort_year', $class->cohort_year ?? date('Y')) }}" required>
    </div>

    <div class="col-md-6">
        <label class="form-label" for="specialization_id">Chuyên ngành đào tạo <span class="text-danger">*</span></label>
        <select class="form-select" id="specialization_id" name="specialization_id" required>
            <option value="">-- Chọn chuyên ngành --</option>
            @foreach ($specializations as $spec)
                <option value="{{ $spec->id }}" {{ old('specialization_id', $class->specialization_id) == $spec->id ? 'selected' : '' }}>
                    [{{ $spec->code }}] {{ $spec->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-6">
        <label class="form-label" for="class_code">Mã lớp danh nghĩa <span class="text-danger">*</span></label>
        <input class="form-control font-monospace" id="class_code" name="class_code" maxlength="30"
            value="{{ old('class_code', $class->class_code) }}" required>
    </div>

    <div class="col-md-6">
        <label class="form-label" for="class_name">Tên lớp danh nghĩa <span class="text-danger">*</span></label>
        <input class="form-control" id="class_name" name="class_name" maxlength="100"
            value="{{ old('class_name', $class->class_name) }}" required>
    </div>

    <div class="col-md-6">
        <label class="form-label" for="lecturer_id">Giảng viên phụ trách (CVHT)</label>
        <select class="form-select" id="lecturer_id" name="lecturer_id">
            <option value="">-- Chưa phân công --</option>
            @foreach ($lecturers as $lec)
                <option value="{{ $lec->id }}" {{ old('lecturer_id', $class->lecturer_id) == $lec->id ? 'selected' : '' }}>
                    {{ $lec->full_name }} ({{ $lec->degree }})
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-6">
        <label class="form-label" for="room">Phòng học</label>
        <input class="form-control" id="room" name="room" maxlength="50"
            value="{{ old('room', $class->room) }}">
    </div>

    <div class="col-12">
        <label class="form-label" for="description">Ghi chú</label>
        <input class="form-control" id="description" name="description" maxlength="255"
            value="{{ old('description', $class->description) }}">
    </div>
</div>
