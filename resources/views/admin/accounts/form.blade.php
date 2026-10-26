<div class="mb-3">
    <label class="form-label" for="name">Họ và tên</label>
    <input class="form-control" id="name" name="name" maxlength="100" value="{{ old('name', $account->name) }}" required>
</div>
<div class="mb-3">
    <label class="form-label" for="email">Email</label>
    <input class="form-control" id="email" name="email" type="email" maxlength="255"
        value="{{ old('email', $account->email) }}" required>
</div>
<div class="mb-3">
    <label class="form-label" for="role">Vai trò</label>
    <select class="form-select" id="role" name="role" required>
        @foreach (\App\Models\User::ROLE_LABELS as $value => $label)
            <option value="{{ $value }}" @selected(old('role', $account->role) === $value)>
                {{ $label }} ({{ $value }})
            </option>
        @endforeach
    </select>
</div>
<div class="mb-3" id="class-field">
    <label class="form-label" for="class_id">Lớp</label>
    <select class="form-select" id="class_id" name="class_id">
        <option value="">Chưa xếp lớp</option>
        @foreach ($classes as $class)
            <option value="{{ $class->id }}" @selected(old('class_id', $account->class_id) == $class->id)>
                {{ $class->class_code }} - {{ $class->class_name }}
            </option>
        @endforeach
    </select>
    <div class="form-text">Chỉ áp dụng cho sinh viên.</div>
</div>
<div class="mb-3">
    <label class="form-label" for="password">Mật khẩu {{ $editing ? '(để trống nếu không đổi)' : '' }}</label>
    <input class="form-control" id="password" name="password" type="password" minlength="8"
        {{ $editing ? '' : 'required' }}>
</div>
