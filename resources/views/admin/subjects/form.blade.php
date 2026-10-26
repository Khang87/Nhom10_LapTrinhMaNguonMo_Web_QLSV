<div class="mb-3">
    <label class="form-label" for="code">Mã môn</label>
    <input class="form-control" id="code" name="code" maxlength="20" value="{{ old('code', $subject->code) }}" required>
</div>
<div class="mb-3">
    <label class="form-label" for="name">Tên môn</label>
    <input class="form-control" id="name" name="name" maxlength="100" value="{{ old('name', $subject->name) }}" required>
</div>
<div class="mb-3">
    <label class="form-label" for="credits">Số tín chỉ</label>
    <input class="form-control" id="credits" name="credits" type="number" min="1" max="30"
        value="{{ old('credits', $subject->credits) }}" required>
</div>
<div class="mb-3">
    <label class="form-label" for="major_id">Chuyên ngành</label>
    <select class="form-select" id="major_id" name="major_id">
        <option value="">Chưa phân chuyên ngành</option>
        @foreach ($majors as $major)
            <option value="{{ $major->id }}" @selected(old('major_id', $subject->major_id) == $major->id)>
                {{ $major->name }} ({{ $major->code }})
            </option>
        @endforeach
    </select>
</div>
<div class="mb-3">
    <label class="form-label" for="total_sessions">Số buổi dự kiến</label>
    <input class="form-control" id="total_sessions" name="total_sessions" type="number" min="1" max="255"
        value="{{ old('total_sessions', $subject->total_sessions) }}">
    <div class="form-text">Dùng cho thời khóa biểu; để trống nếu chưa xác định.</div>
</div>
<div class="mb-3">
    <label class="form-label" for="description">Mô tả</label>
    <textarea class="form-control" id="description" name="description" rows="3"
        maxlength="2000">{{ old('description', $subject->description) }}</textarea>
</div>
