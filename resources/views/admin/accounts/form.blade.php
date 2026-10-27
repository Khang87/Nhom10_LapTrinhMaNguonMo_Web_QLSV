<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label-custom" for="name">Họ và tên</label>
        <input class="form-control form-control-custom" id="name" name="name" maxlength="100"
            value="{{ old('name', $account->name) }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label-custom" for="email">Email</label>
        <input class="form-control form-control-custom" id="email" name="email" type="email" maxlength="255"
            value="{{ old('email', $account->email) }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label-custom" for="role">Vai trò</label>
        <select class="form-select form-select-custom" id="role" name="role" required>
            @foreach (\App\Models\User::ROLE_LABELS as $value => $label)
                <option value="{{ $value }}" @selected(old('role', $account->role) === $value)>
                    {{ $label }} ({{ $value }})
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6" id="class-field">
        <label class="form-label-custom" for="class_id">Lớp</label>
        <select class="form-select form-select-custom" id="class_id" name="class_id">
            <option value="">Chưa xếp lớp</option>
            @foreach ($classes as $class)
                <option value="{{ $class->id }}" @selected(old('class_id', $account->class_id) == $class->id)>
                    {{ $class->class_code }} - {{ $class->class_name }}
                </option>
            @endforeach
        </select>
        <div class="fs-xs text-muted-green mt-1">Chỉ áp dụng cho sinh viên.</div>
    </div>
    <div class="col-md-6">
        <label class="form-label-custom" for="password">Mật khẩu {{ $editing ? '(để trống nếu không đổi)' : '' }}</label>
        <input class="form-control form-control-custom" id="password" name="password" type="password" minlength="8"
            {{ $editing ? '' : 'required' }}>
    </div>
</div>
