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
    <div class="col-md-4">
        <label class="form-label-custom" for="phone">Số điện thoại phòng</label>
        <input class="form-control form-control-custom" id="phone" name="phone" maxlength="20"
            value="{{ old('phone', $department->phone) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label-custom" for="email">Email liên hệ</label>
        <input class="form-control form-control-custom" id="email" name="email" type="email" maxlength="100"
            value="{{ old('email', $department->email) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label-custom" for="office">Nơi làm việc</label>
        <input class="form-control form-control-custom" id="office" name="office" maxlength="150"
            value="{{ old('office', $department->office) }}">
    </div>
    <div class="col-12">
        <label class="form-label-custom" for="description">Mô tả</label>
        <textarea class="form-control form-control-custom" id="description" name="description" rows="3"
            maxlength="2000">{{ old('description', $department->description) }}</textarea>
    </div>
</div>
