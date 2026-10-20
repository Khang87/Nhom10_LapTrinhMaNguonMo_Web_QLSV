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
