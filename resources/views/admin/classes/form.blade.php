<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label-custom" for="class_code">Mã lớp</label>
        <input class="form-control form-control-custom" id="class_code" name="class_code" maxlength="20"
            value="{{ old('class_code', $class->class_code) }}" required>
    </div>
    <div class="col-md-8">
        <label class="form-label-custom" for="class_name">Tên lớp</label>
        <input class="form-control form-control-custom" id="class_name" name="class_name" maxlength="100"
            value="{{ old('class_name', $class->class_name) }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label-custom" for="intake_year">Khóa tuyển sinh</label>
        <input class="form-control form-control-custom" id="intake_year" name="intake_year" type="number" min="1990"
            max="{{ now()->year + 10 }}" value="{{ old('intake_year', $class->intake_year) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label-custom" for="department_id">Khoa</label>
        <select class="form-select form-select-custom" id="department_id" name="department_id">
            <option value="">Chưa phân loại</option>
            @foreach ($departments as $department)
                <option value="{{ $department->id }}" @selected(old('department_id', $class->department_id) == $department->id)>
                    {{ $department->name }} ({{ $department->code }})
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label-custom" for="major_id">Chuyên ngành</label>
        <select class="form-select form-select-custom" id="major_id" name="major_id">
            <option value="">Chưa phân loại</option>
            @foreach ($majors as $major)
                <option value="{{ $major->id }}" @selected(old('major_id', $class->major_id) == $major->id)>
                    {{ $major->name }} ({{ $major->code }})
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label-custom" for="adviser_id">Giảng viên chủ nhiệm</label>
        <select class="form-select form-select-custom" id="adviser_id" name="adviser_id">
            <option value="">Chưa phân công</option>
            @foreach ($advisers as $adviser)
                <option value="{{ $adviser->id }}" @selected(old('adviser_id', $class->adviser_id) == $adviser->id)>
                    {{ $adviser->name }}@if($adviser->teacher_code) ({{ $adviser->teacher_code }}) @endif
                </option>
            @endforeach
        </select>
    </div>
</div>
