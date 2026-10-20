<div class="mb-3">
    <label class="form-label" for="class_code">Mã lớp</label>
    <input class="form-control" id="class_code" name="class_code" maxlength="20"
        value="{{ old('class_code', $class->class_code) }}" required>
</div>
<div class="mb-3">
    <label class="form-label" for="class_name">Tên lớp</label>
    <input class="form-control" id="class_name" name="class_name" maxlength="100"
        value="{{ old('class_name', $class->class_name) }}" required>
</div>
<div class="mb-3">
    <label class="form-label" for="room">Phòng</label>
    <input class="form-control" id="room" name="room" maxlength="50"
        value="{{ old('room', $class->room) }}">
</div>
