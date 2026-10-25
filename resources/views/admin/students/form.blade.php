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
    <label class="form-label" for="password">Mật khẩu {{ $editing ? '(để trống nếu không đổi)' : '' }}</label>
    <input class="form-control" id="password" name="password" type="password" minlength="8"
        {{ $editing ? '' : 'required' }}>
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
<div class="mb-3">
    <label class="form-label" for="avatar">Ảnh đại diện</label>
    <div class="d-flex align-items-center gap-3">
        <img class="rounded-circle border" width="64" height="64" alt=""
            id="avatar-preview" src="{{ e($student->avatarUrl()) }}">
        <div class="flex-grow-1">
            <input class="form-control" id="avatar" name="avatar" type="file" accept=".jpg,.jpeg,.png,.webp">
            <div class="form-text">Chấp nhận JPG, PNG hoặc WebP, tối đa 2 MB.</div>
        </div>
    </div>
</div>
<script>
    (() => {
        const input = document.getElementById('avatar');
        const preview = document.getElementById('avatar-preview');
        const fallback = preview.getAttribute('src');
        input.addEventListener('change', () => {
            const file = input.files && input.files[0];
            if (file) {
                preview.src = URL.createObjectURL(file);
            } else {
                preview.src = fallback;
            }
        });
    })();
</script>
