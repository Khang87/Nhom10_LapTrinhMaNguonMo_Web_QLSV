<div class="mb-3">
    <label class="form-label" for="name">Họ và tên</label>
    <input class="form-control" id="name" name="name" maxlength="100" value="{{ old('name', $student->name) }}" required>
</div>
<div class="mb-3">
    <label class="form-label" for="email">Email</label>
    <input class="form-control" id="email" name="email" type="email" maxlength="255"
        value="{{ old('email', $student->email) }}" required>
</div>
<div class="mb-3">
    <label class="form-label" for="password">Mật khẩu {{ $student->exists ? '(để trống nếu không đổi)' : '' }}</label>
    <input class="form-control" id="password" name="password" type="password" minlength="8"
        {{ $student->exists ? '' : 'required' }}>
</div>
<div class="mb-3">
    <label class="form-label" for="class_id">Lớp</label>
    <select class="form-select" id="class_id" name="class_id">
        <option value="">Chưa xếp lớp</option>
        @foreach ($classes as $class)
            <option value="{{ $class->id }}" @selected(old('class_id', $student->class_id) == $class->id)>
                {{ $class->class_code }} - {{ $class->class_name }}
            </option>
        @endforeach
    </select>
</div>
