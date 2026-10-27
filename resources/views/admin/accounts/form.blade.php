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

    <div class="col-12">
        <div class="fs-xs text-uppercase text-muted-green mt-2">Hồ sơ theo vai trò</div>
    </div>
    <div class="col-md-3">
        <label class="form-label-custom" for="student_code">Mã sinh viên</label>
        <input class="form-control form-control-custom" id="student_code" name="student_code" maxlength="20"
            value="{{ old('student_code', $account->student_code) }}" placeholder="20240001">
        <div class="fs-xs text-muted-green mt-1">Chỉ áp dụng cho sinh viên.</div>
    </div>
    <div class="col-md-3">
        <label class="form-label-custom" for="teacher_code">Mã giảng viên</label>
        <input class="form-control form-control-custom" id="teacher_code" name="teacher_code" maxlength="20"
            value="{{ old('teacher_code', $account->teacher_code) }}" placeholder="GV01">
        <div class="fs-xs text-muted-green mt-1">Chỉ áp dụng cho giảng viên.</div>
    </div>
    <div class="col-md-3">
        <label class="form-label-custom" for="degree">Học vị</label>
        <input class="form-control form-control-custom" id="degree" name="degree" maxlength="60"
            value="{{ old('degree', $account->degree) }}" placeholder="Tiến sĩ">
        <div class="fs-xs text-muted-green mt-1">Chỉ áp dụng cho giảng viên.</div>
    </div>
    <div class="col-md-3">
        <label class="form-label-custom" for="department_id">Khoa phụ trách</label>
        <select class="form-select form-select-custom" id="department_id" name="department_id">
            <option value="">Chưa phân khoa</option>
            @foreach ($departments as $department)
                <option value="{{ $department->id }}" @selected(old('department_id', $account->department_id) == $department->id)>
                    {{ $department->name }}
                </option>
            @endforeach
        </select>
        <div class="fs-xs text-muted-green mt-1">Chỉ áp dụng cho giảng viên.</div>
    </div>
    <div class="col-md-4">
        <label class="form-label-custom" for="account_phone">Số điện thoại</label>
        <input class="form-control form-control-custom" id="account_phone" name="phone" maxlength="20"
            inputmode="numeric" value="{{ old('phone', $account->phone) }}" placeholder="0912345678">
    </div>
    <div class="col-md-4">
        <label class="form-label-custom" for="account_dob">Ngày sinh</label>
        <input class="form-control form-control-custom" id="account_dob" name="dob" type="date"
            value="{{ old('dob', $account->dob?->format('Y-m-d')) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label-custom" for="account_gender">Giới tính</label>
        <select class="form-select form-select-custom" id="account_gender" name="gender">
            <option value="">Chưa cập nhật</option>
            @foreach (\App\Support\PersonLabels::GENDERS as $code => $label)
                <option value="{{ $code }}" @selected(old('gender', $account->gender) === $code)>
                    {{ \App\Support\PersonLabels::option($code, \App\Support\PersonLabels::GENDERS) }}
                </option>
            @endforeach
        </select>
    </div>
</div>
