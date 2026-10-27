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
        <div class="fs-xs text-uppercase text-muted-green mt-2">Thông tin đào tạo</div>
    </div>
    <div class="col-md-3">
        <label class="form-label-custom" for="enrolled_on">Ngày nhập học</label>
        <input class="form-control form-control-custom" id="enrolled_on" name="enrolled_on" type="date"
            value="{{ old('enrolled_on', $student->enrolled_on?->format('Y-m-d')) }}">
    </div>
    <div class="col-md-3">
        <label class="form-label-custom" for="education_level">Bậc đào tạo</label>
        <input class="form-control form-control-custom" id="education_level" name="education_level" maxlength="30"
            value="{{ old('education_level', $student->education_level) }}" placeholder="Đại học">
    </div>
    <div class="col-md-3">
        <label class="form-label-custom" for="training_type">Loại hình đào tạo</label>
        <input class="form-control form-control-custom" id="training_type" name="training_type" maxlength="30"
            value="{{ old('training_type', $student->training_type) }}" placeholder="Chính quy">
    </div>
    <div class="col-md-3">
        <label class="form-label-custom" for="admission_region">Khu vực tuyển sinh</label>
        <input class="form-control form-control-custom" id="admission_region" name="admission_region" maxlength="20"
            value="{{ old('admission_region', $student->admission_region) }}" placeholder="Khu vực 2">
    </div>
    <div class="col-md-3">
        <label class="form-label-custom" for="priority_category">Diện ưu tiên</label>
        <input class="form-control form-control-custom" id="priority_category" name="priority_category" maxlength="30"
            value="{{ old('priority_category', $student->priority_category) }}" placeholder="Không">
    </div>

    <div class="col-12">
        <div class="fs-xs text-uppercase text-muted-green mt-2">Giấy tờ tùy thân</div>
    </div>
    <div class="col-md-4">
        <label class="form-label-custom" for="id_card_number">Số CCCD / định danh</label>
        <input class="form-control form-control-custom" id="id_card_number" name="id_card_number" maxlength="20"
            inputmode="numeric" value="{{ old('id_card_number', $student->id_card_number) }}" placeholder="095205003219">
    </div>
    <div class="col-md-4">
        <label class="form-label-custom" for="id_card_issued_at">Ngày cấp</label>
        <input class="form-control form-control-custom" id="id_card_issued_at" name="id_card_issued_at" type="date"
            value="{{ old('id_card_issued_at', $student->id_card_issued_at?->format('Y-m-d')) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label-custom" for="id_card_issued_place">Nơi cấp</label>
        <input class="form-control form-control-custom" id="id_card_issued_place" name="id_card_issued_place"
            maxlength="100" value="{{ old('id_card_issued_place', $student->id_card_issued_place) }}" placeholder="Cục Cảnh sát QLHC về TTXH">
    </div>

    <div class="col-12">
        <div class="fs-xs text-uppercase text-muted-green mt-2">Thân nhân và cư trú</div>
    </div>
    <div class="col-md-3">
        <label class="form-label-custom" for="ethnicity">Dân tộc</label>
        <input class="form-control form-control-custom" id="ethnicity" name="ethnicity" maxlength="50"
            value="{{ old('ethnicity', $student->ethnicity) }}" placeholder="Kinh">
    </div>
    <div class="col-md-3">
        <label class="form-label-custom" for="religion">Tôn giáo</label>
        <input class="form-control form-control-custom" id="religion" name="religion" maxlength="50"
            value="{{ old('religion', $student->religion) }}" placeholder="Không">
    </div>
    <div class="col-md-3">
        <label class="form-label-custom" for="nationality">Quốc tịch</label>
        <input class="form-control form-control-custom" id="nationality" name="nationality" maxlength="50"
            value="{{ old('nationality', $student->nationality) }}" placeholder="Việt Nam">
    </div>
    <div class="col-md-3">
        <label class="form-label-custom" for="birth_place">Nơi sinh</label>
        <input class="form-control form-control-custom" id="birth_place" name="birth_place" maxlength="100"
            value="{{ old('birth_place', $student->birth_place) }}">
    </div>
    <div class="col-md-6">
        <label class="form-label-custom" for="permanent_residence">Hộ khẩu thường trú</label>
        <input class="form-control form-control-custom" id="permanent_residence" name="permanent_residence"
            maxlength="200" value="{{ old('permanent_residence', $student->permanent_residence) }}">
    </div>
    <div class="col-md-6">
        <label class="form-label-custom" for="contact_address">Địa chỉ liên hệ</label>
        <input class="form-control form-control-custom" id="contact_address" name="contact_address" maxlength="200"
            value="{{ old('contact_address', $student->contact_address) }}">
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
