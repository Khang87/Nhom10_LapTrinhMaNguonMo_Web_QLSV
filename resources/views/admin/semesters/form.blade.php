<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label-custom" for="academic_year">Năm học</label>
        <input class="form-control form-control-custom" id="academic_year" name="academic_year" maxlength="20"
            placeholder="2025-2026" value="{{ old('academic_year', $semester->academic_year) }}" required>
    </div>
    <div class="col-md-2">
        <label class="form-label-custom" for="term">Học kỳ</label>
        <select class="form-select form-select-custom" id="term" name="term" required>
            @foreach ([1, 2, 3] as $term)
                <option value="{{ $term }}" @selected((int) old('term', $semester->term ?? 1) === $term)>
                    Học kỳ {{ $term }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label-custom" for="starts_at">Ngày bắt đầu</label>
        <input class="form-control form-control-custom" id="starts_at" name="starts_at" type="date"
            value="{{ old('starts_at', $semester->starts_at?->format('Y-m-d')) }}" required>
    </div>
    <div class="col-md-3">
        <label class="form-label-custom" for="ends_at">Ngày kết thúc</label>
        <input class="form-control form-control-custom" id="ends_at" name="ends_at" type="date"
            value="{{ old('ends_at', $semester->ends_at?->format('Y-m-d')) }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label-custom" for="registration_starts_at">Cổng đăng ký mở từ</label>
        <input class="form-control form-control-custom" id="registration_starts_at" name="registration_starts_at"
            type="datetime-local"
            value="{{ old('registration_starts_at', $semester->registration_starts_at?->format('Y-m-d\TH:i')) }}">
    </div>
    <div class="col-md-6">
        <label class="form-label-custom" for="registration_ends_at">Cổng đăng ký đóng lúc</label>
        <input class="form-control form-control-custom" id="registration_ends_at" name="registration_ends_at"
            type="datetime-local"
            value="{{ old('registration_ends_at', $semester->registration_ends_at?->format('Y-m-d\TH:i')) }}">
    </div>
</div>
<p class="fs-xs text-muted-green mt-3 mb-0">
    Để trống cả hai mốc cổng nếu học kỳ chưa cho sinh viên tự đăng ký. Sinh viên chỉ đăng ký được trong khoảng thời
    gian này và lớp học phần phải đang mở.
</p>
