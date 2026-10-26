<div class="mb-3">
    <label class="form-label" for="department_id">Khoa</label>
    <select class="form-select" id="department_id" name="department_id" required>
        <option value="">Chọn khoa</option>
        @foreach ($departments as $department)
            <option value="{{ $department->id }}" @selected(old('department_id', $major->department_id) == $department->id)>
                {{ $department->name }} ({{ $department->code }})
            </option>
        @endforeach
    </select>
</div>
<div class="mb-3">
    <label class="form-label" for="code">Mã chuyên ngành</label>
    <input class="form-control" id="code" name="code" maxlength="20" value="{{ old('code', $major->code) }}" required>
</div>
<div class="mb-3">
    <label class="form-label" for="name">Tên chuyên ngành</label>
    <input class="form-control" id="name" name="name" maxlength="150" value="{{ old('name', $major->name) }}" required>
</div>
