<?php

namespace App\Support;

use App\Models\CourseOffering;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Bộ lọc dùng chung cho cả bốn báo cáo: một bộ lọc, bốn con số, không phải bốn màn hình bốn
 * cách lọc. Giá trị thừa hoặc sai kiểu bị bỏ qua thay vì báo lỗi vì thanh lọc luôn có ô trống.
 *
 * $offeringIds là phạm vi của giảng viên: họ chỉ thấy số liệu lớp mình phụ trách, không có
 * quyền chọn khoa/khóa.
 */
final class ReportFilter
{
    /** @param list<int> $offeringIds */
    public function __construct(
        public readonly ?int $semesterId = null,
        public readonly ?int $departmentId = null,
        public readonly ?int $majorId = null,
        public readonly ?int $classId = null,
        public readonly ?int $intakeYear = null,
        public readonly ?string $gender = null,
        public readonly ?string $academicStatus = null,
        public readonly array $offeringIds = [],
    ) {}

    public static function fromRequest(Request $request, ?int $semesterId = null): self
    {
        $gender = $request->input('gender');
        $status = $request->input('academic_status');

        return new self(
            semesterId: self::int($request->input('semester_id')) ?? $semesterId ?? Semester::current()?->id,
            departmentId: self::int($request->input('department_id')),
            majorId: self::int($request->input('major_id')),
            classId: self::int($request->input('class_id')),
            intakeYear: self::int($request->input('intake_year')),
            gender: in_array($gender, PersonLabels::genderCodes(), true) ? $gender : null,
            academicStatus: in_array($status, PersonLabels::statusCodes(), true) ? $status : null,
        );
    }

    /**
     * Thanh lọc gửi lên chuỗi rỗng khi chưa chọn ô nào; coi mọi giá trị không phải số nguyên
     * dương là "không lọc" để một tham số bẩn không biến báo cáo thành trang trống.
     */
    private static function int(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value > 0 ? $value : null;
        }

        return ctype_digit((string) $value) && (int) $value > 0 ? (int) $value : null;
    }

    /**
     * Báo cáo của giảng viên dựng từ chính các lớp họ đứng tên, không tin mã lớp gửi lên form.
     *
     * @param  list<int>  $offeringIds
     */
    public function forOfferings(array $offeringIds): self
    {
        return new self(
            semesterId: $this->semesterId,
            offeringIds: array_values(array_map('intval', $offeringIds)),
        );
    }

    public function semester(): ?Semester
    {
        return $this->semesterId === null ? null : Semester::find($this->semesterId);
    }

    /** @return Builder<User> */
    public function students(): Builder
    {
        return $this->constrainUsers(User::query());
    }

    /**
     * Điểm của sinh viên khớp lọc trong học kỳ đang lọc; chưa chọn kỳ thì là mọi kỳ.
     *
     * @return Builder<Grade>
     */
    public function grades(): Builder
    {
        return $this->inSemester(
            Grade::query()
                ->when($this->offeringIds !== [], fn (Builder $q) => $q->whereIn('course_offering_id', $this->offeringIds))
                ->whereHas('user', fn (Builder $u) => $this->constrainUsers($u))
        );
    }

    /**
     * Đăng ký còn hiệu lực của kỳ đang lọc — nguồn duy nhất cho các con số chuyên cần.
     *
     * @return Builder<Enrollment>
     */
    public function enrollments(): Builder
    {
        return $this->inSemester(
            Enrollment::query()
                ->where('status', 'ACTIVE')
                ->when($this->offeringIds !== [], fn (Builder $q) => $q->whereIn('course_offering_id', $this->offeringIds))
                ->whereHas('user', fn (Builder $u) => $this->constrainUsers($u))
        );
    }

    /** @return Builder<CourseOffering> */
    public function offerings(): Builder
    {
        return CourseOffering::query()
            ->when($this->semesterId, fn (Builder $q) => $q->where('semester_id', $this->semesterId))
            ->when($this->offeringIds !== [], fn (Builder $q) => $q->whereIn('id', $this->offeringIds));
    }

    /**
     * Áp bộ lọc phía người học vào một query users: dùng trực tiếp cho danh sách sinh viên và
     * dùng lại nguyên văn khi đi qua quan hệ user của bảng điểm/đăng ký, để bốn báo cáo không
     * tự viết lại điều kiện theo bốn cách khác nhau.
     *
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    private function constrainUsers(Builder $query): Builder
    {
        return $query
            ->where('role', 'student')
            ->when($this->offeringIds !== [], fn (Builder $q) => $q
                ->whereHas('enrollments', fn (Builder $e) => $e->whereIn('course_offering_id', $this->offeringIds)))
            ->when($this->gender, fn (Builder $q) => $q->where('gender', $this->gender))
            ->when($this->academicStatus, fn (Builder $q) => $q->where('academic_status', $this->academicStatus))
            ->when($this->classId, fn (Builder $q) => $q->where('class_id', $this->classId))
            ->when($this->majorId || $this->departmentId || $this->intakeYear, fn (Builder $q) => $q
                ->whereHas('studentClass', fn (Builder $c) => $c
                    ->when($this->majorId, fn (Builder $x) => $x->where('major_id', $this->majorId))
                    ->when($this->departmentId, fn (Builder $x) => $x->where('department_id', $this->departmentId))
                    ->when($this->intakeYear, fn (Builder $x) => $x->where('intake_year', $this->intakeYear))));
    }

    /** @return Builder<Grade>|Builder<Enrollment> */
    private function inSemester(Builder $query): Builder
    {
        return $query->when($this->semesterId, fn (Builder $q) => $q
            ->whereHas('offering', fn (Builder $o) => $o->where('semester_id', $this->semesterId)));
    }

    /**
     * Giá trị của bộ lọc cho form: mọi ô chọn lại được sau khi chuyển trang hoặc đổi kỳ.
     *
     * @return array<string, int|string|null>
     */
    public function values(): array
    {
        return [
            'semester_id' => $this->semesterId,
            'department_id' => $this->departmentId,
            'major_id' => $this->majorId,
            'class_id' => $this->classId,
            'intake_year' => $this->intakeYear,
            'gender' => $this->gender,
            'academic_status' => $this->academicStatus,
        ];
    }
}
