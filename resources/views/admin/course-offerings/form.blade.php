<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label-custom" for="subject_id">Môn học</label>
        <select class="form-select form-select-custom" id="subject_id" name="subject_id" required>
            <option value="">Chọn môn học</option>
            @foreach ($subjects as $subject)
                <option value="{{ $subject->id }}" @selected(old('subject_id', $offering->subject_id) == $subject->id)>
                    {{ $subject->label() }} ({{ $subject->credits }} TC)
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label-custom" for="semester_id">Học kỳ</label>
        <select class="form-select form-select-custom" id="semester_id" name="semester_id" required>
            <option value="">Chọn học kỳ</option>
            @foreach ($semesters as $semester)
                <option value="{{ $semester->id }}" @selected(old('semester_id', $offering->semester_id) == $semester->id)>
                    {{ $semester->label }}@if($semester->is_active) (hiện hành) @endif
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label-custom" for="group_code">Nhóm</label>
        <input class="form-control form-control-custom" id="group_code" name="group_code" maxlength="20"
            placeholder="TN01" value="{{ old('group_code', $offering->group_code) }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label-custom" for="teacher_id">Giảng viên phụ trách</label>
        <select class="form-select form-select-custom" id="teacher_id" name="teacher_id">
            <option value="">Chưa phân công</option>
            @foreach ($teachers as $teacher)
                <option value="{{ $teacher->id }}" @selected(old('teacher_id', $offering->teacher_id) == $teacher->id)>
                    {{ $teacher->name }}@if($teacher->person_code) ({{ $teacher->person_code }}) @endif
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label-custom" for="room">Phòng học</label>
        <input class="form-control form-control-custom" id="room" name="room" maxlength="50"
            value="{{ old('room', $offering->room) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label-custom" for="max_students">Sĩ số tối đa</label>
        <input class="form-control form-control-custom" id="max_students" name="max_students" type="number" min="0"
            max="65535" value="{{ old('max_students', $offering->max_students ?? 0) }}" required>
        <div class="fs-xs text-muted-green mt-1">Nhập 0 nếu lớp không giới hạn sĩ số.</div>
    </div>
    <div class="col-md-8">
        <div class="form-check form-check-custom mt-4">
            <input class="form-check-input form-check-input-custom" type="checkbox" id="is_open" name="is_open"
                value="1" @checked(old('is_open', $offering->is_open ?? true))>
            <label class="form-check-label" for="is_open">Mở cho sinh viên đăng ký</label>
        </div>
        <div class="fs-xs text-muted-green">
            Cổng đăng ký còn cần học kỳ đang trong khoảng thời gian nhận đăng ký.
        </div>
    </div>
</div>
