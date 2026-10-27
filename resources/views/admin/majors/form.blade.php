<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label-custom" for="department_id">Khoa</label>
        <select class="form-select form-select-custom" id="department_id" name="department_id" required>
            <option value="">Chọn khoa</option>
            @foreach ($departments as $department)
                <option value="{{ $department->id }}" @selected(old('department_id', $major->department_id) == $department->id)>
                    {{ $department->name }} ({{ $department->code }})
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label-custom" for="code">Mã chuyên ngành</label>
        <input class="form-control form-control-custom" id="code" name="code" maxlength="20"
            value="{{ old('code', $major->code) }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label-custom" for="name">Tên chuyên ngành</label>
        <input class="form-control form-control-custom" id="name" name="name" maxlength="150"
            value="{{ old('name', $major->name) }}" required>
    </div>
    <div class="col-12">
        <label class="form-label-custom" for="description">Mô tả chương trình</label>
        <textarea class="form-control form-control-custom" id="description" name="description" rows="3"
            maxlength="2000">{{ old('description', $major->description) }}</textarea>
    </div>
</div>
