<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label-custom" for="code">Mã môn</label>
        <input class="form-control form-control-custom" id="code" name="code" maxlength="20"
            value="{{ old('code', $subject->code) }}" required>
    </div>
    <div class="col-md-8">
        <label class="form-label-custom" for="name">Tên môn</label>
        <input class="form-control form-control-custom" id="name" name="name" maxlength="100"
            value="{{ old('name', $subject->name) }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label-custom" for="credits">Số tín chỉ</label>
        <input class="form-control form-control-custom" id="credits" name="credits" type="number" min="1" max="30"
            value="{{ old('credits', $subject->credits) }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label-custom" for="major_id">Chuyên ngành</label>
        <select class="form-select form-select-custom" id="major_id" name="major_id">
            <option value="">Chưa phân chuyên ngành</option>
            @foreach ($majors as $major)
                <option value="{{ $major->id }}" @selected(old('major_id', $subject->major_id) == $major->id)>
                    {{ $major->name }} ({{ $major->code }})
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label-custom" for="total_sessions">Số buổi dự kiến</label>
        <input class="form-control form-control-custom" id="total_sessions" name="total_sessions" type="number" min="1"
            max="255" value="{{ old('total_sessions', $subject->total_sessions) }}">
        <div class="fs-xs text-muted-green mt-1">Dùng cho thời khóa biểu; để trống nếu chưa xác định.</div>
    </div>
    <div class="col-12">
        <label class="form-label-custom" for="description">Mô tả</label>
        <textarea class="form-control form-control-custom" id="description" name="description" rows="3"
            maxlength="2000">{{ old('description', $subject->description) }}</textarea>
    </div>
</div>
