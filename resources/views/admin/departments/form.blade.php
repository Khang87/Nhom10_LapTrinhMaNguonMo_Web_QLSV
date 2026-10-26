<div class="mb-3">
    <label class="form-label" for="code">Mã khoa</label>
    <input class="form-control" id="code" name="code" maxlength="20" value="{{ old('code', $department->code) }}" required>
</div>
<div class="mb-3">
    <label class="form-label" for="name">Tên khoa</label>
    <input class="form-control" id="name" name="name" maxlength="150" value="{{ old('name', $department->name) }}" required>
</div>
