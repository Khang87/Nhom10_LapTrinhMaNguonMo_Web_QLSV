<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label-custom" for="code">Mã khoa</label>
        <input class="form-control form-control-custom" id="code" name="code" maxlength="20"
            value="{{ old('code', $department->code) }}" required>
    </div>
    <div class="col-md-8">
        <label class="form-label-custom" for="name">Tên khoa</label>
        <input class="form-control form-control-custom" id="name" name="name" maxlength="150"
            value="{{ old('name', $department->name) }}" required>
    </div>
</div>
