<div class="row g-3">
    <div class="col-md-8">
        <label class="form-label-custom" for="name">Họ và tên</label>
        <input class="form-control form-control-custom" id="name" name="name" maxlength="100"
            value="{{ old('name', $student->name) }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label-custom" for="student_code">Mã sinh viên</label>
        <input class="form-control form-control-custom" id="student_code" name="student_code" maxlength="20"
            value="{{ old('student_code', $student->student_code) }}" placeholder="20240001">
    </div>
    <div class="col-md-6">
        <label class="form-label-custom" for="email">Email</label>
        <input class="form-control form-control-custom" id="email" name="email" type="email" maxlength="255"
            value="{{ old('email', $student->email) }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label-custom" for="phone">Số điện thoại</label>
        <input class="form-control form-control-custom" id="phone" name="phone" maxlength="20" inputmode="numeric"
            value="{{ old('phone', $student->phone) }}" placeholder="0912345678">
    </div>
    <div class="col-md-6">
        <label class="form-label-custom" for="password">Mật khẩu {{ $editing ? '(để trống nếu không đổi)' : '' }}</label>
        <input class="form-control form-control-custom" id="password" name="password" type="password" minlength="8"
            {{ $editing ? '' : 'required' }}>
    </div>
    <div class="col-md-6">
        <label class="form-label-custom" for="dob">Ngày sinh</label>
        <input class="form-control form-control-custom" id="dob" name="dob" type="date"
            value="{{ old('dob', $student->dob?->format('Y-m-d')) }}">
    </div>
    <div class="col-md-3">
        <label class="form-label-custom" for="gender">Giới tính</label>
        <select class="form-select form-select-custom" id="gender" name="gender">
            <option value="">Chưa cập nhật</option>
            @foreach (App\Support\PersonLabels::GENDERS as $code => $label)
                <option value="{{ $code }}" @selected(old('gender', $student->gender) === $code)>
                    {{ App\Support\PersonLabels::option($code, App\Support\PersonLabels::GENDERS) }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label-custom" for="academic_status">Tình trạng học tập</label>
        <select class="form-select form-select-custom" id="academic_status" name="academic_status">
            @foreach (App\Support\PersonLabels::ACADEMIC_STATUSES as $code => $label)
                <option value="{{ $code }}" @selected(old('academic_status', $student->academic_status ?? 'STUDYING') === $code)>
                    {{ App\Support\PersonLabels::option($code, App\Support\PersonLabels::ACADEMIC_STATUSES) }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label-custom" for="hometown">Quê quán</label>
        <input class="form-control form-control-custom" id="hometown" name="hometown" maxlength="200"
            value="{{ old('hometown', $student->hometown) }}">
    </div>
    <div class="col-md-6">
        <label class="form-label-custom" for="class_id">Lớp hành chính</label>
        <select class="form-select form-select-custom" id="class_id" name="class_id">
            <option value="">Chưa xếp lớp</option>
            @foreach ($classes as $class)
                <option value="{{ $class->id }}" @selected(old('class_id', $student->class_id) == $class->id)>
                    {{ $class->label() }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-12">
        <label class="form-label-custom" for="avatar">Ảnh đại diện</label>
        <div class="d-flex align-items-center gap-3">
            <img class="table-user-avatar table-user-avatar-lg" width="64" height="64" alt=""
                id="avatar-preview" src="{{ e($student->avatarUrl()) }}">
            <div class="flex-grow-1">
                <input class="form-control form-control-custom" id="avatar" name="avatar" type="file"
                    accept=".jpg,.jpeg,.png,.webp">
                <div class="fs-xs text-muted-green mt-1">Chấp nhận JPG, PNG hoặc WebP, tối đa 2 MB.</div>
            </div>
        </div>
    </div>
</div>
