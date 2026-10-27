<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\CourseOffering;
use App\Models\Department;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\LectureSlot;
use App\Models\Major;
use App\Models\Semester;
use App\Models\StudentClass;
use App\Models\StudyPlan;
use App\Models\Subject;
use App\Models\TuitionInvoice;
use App\Models\User;
use App\Support\Tuition;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Dữ liệu demo phủ mọi màn hình của hệ thống, dựng từ danh mục thật của hệ thống đào tạo cùng
 * trường (xem database/seeders/data/catalog.php): 8 khoa kèm liên hệ, 5 chuyên ngành, 26 môn với
 * tín chỉ 2..10, khung chương trình 8 kỳ, 25 giảng viên kèm học vị, 6 lớp hành chính và 28 sinh
 * viên có hồ sơ lý lịch đầy đủ (dân tộc, tôn giáo, giấy tờ tùy thân, khu vực tuyển sinh).
 *
 * Lịch đào tạo trải trên 6 học kỳ của hai năm học: một kỳ đã đóng kèm điểm tổng kết, một kỳ đang
 * mở cổng với lớp học phần, lịch lên lớp, đăng ký, điểm thành phần và điểm danh theo buổi.
 *
 * Mọi dòng dùng updateOrCreate theo mã duy nhất nên chạy lại nhiều lần không tạo bản sao.
 * Trình tự tạo dữ liệu có chủ đích: danh mục -> người -> khung -> học kỳ -> lớp -> lịch -> đăng ký
 * -> điểm/điểm danh -> học phí, vì mỗi bước đọc mã của bước trước.
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Lịch lên lớp chính tay seeder tạo ra trong lần chạy này, khóa theo id lớp học phần.
     * Một lớp được phép có nhiều lịch, nên điểm danh phải bám vào cái mình vừa dựng chứ không
     * giả định mỗi lớp chỉ còn một dòng trong bảng.
     */
    private array $slotsByOffering = [];

    /** Mật khẩu chung của mọi tài khoản demo. */
    public const PASSWORD = '12345678';

    /** Khóa {P} = năm trước năm hiện hành, {Y} = năm hiện hành, {N} = năm sau. */
    private const SEMESTER_PLAN = [
        ['{P}-{Y}', 1, '{P}-09-01', '{Y}-01-31', '{P}-08-01', '{P}-08-25'],
        ['{P}-{Y}', 2, '{Y}-02-01', '{Y}-06-30', '{Y}-01-01', '{Y}-01-25'],
        ['{P}-{Y}', 3, '{Y}-07-01', '{Y}-08-31', '{Y}-05-01', '{Y}-06-15'],
        // Kỳ hiện hành: cổng mở quanh ngày chạy seeder để màn hình đăng ký còn dùng được.
        ['{Y}-{N}', 1, '{Y}-09-01', '{N}-01-31', null, null],
        ['{Y}-{N}', 2, '{N}-02-01', '{N}-06-30', '{N}-01-01', '{N}-01-25'],
        ['{Y}-{N}', 3, '{N}-07-01', '{N}-08-31', '{N}-05-01', '{N}-06-15'],
    ];

    /** Lớp hành chính: [mã lớp, tên lớp, chuyên ngành, mã giảng viên chủ nhiệm]. */
    private const CLASSES = [
        ['CN24A', 'Công nghệ phần mềm 24A', 'CNPM', 'GV002'],
        ['CN24B', 'Công nghệ phần mềm 24B', 'CNPM', 'GV009'],
        ['HS24A', 'Hệ thống thông tin 24A', 'HTTT', 'GV003'],
        ['HC24A', 'Khoa học máy tính 24A', 'KHMT', 'GV008'],
        ['AT24A', 'An toàn thông tin 24A', 'ATTT', 'GV007'],
        ['MM24A', 'Mạng máy tính & Truyền thông 24A', 'MMT', 'GV004'],
    ];

    /** Năm nhập học của toàn bộ sinh viên demo — khớp đầu mã số và mã lớp. */
    private const INTAKE_YEAR = 2024;

    private const EDUCATION_LEVEL = 'Đại học';

    private const DEFAULT_TRAINING = 'Chính quy';

    /**
     * Môn đã học ở kỳ trước theo chuyên ngành: [mã môn, nhóm]. Mọi môn nằm trong học kỳ 1-2 của
     * khung và bao đủ các môn tiên quyết mà kỳ này cần, trừ đúng một trường hợp trượt có chủ đích.
     */
    private const PAST_COURSES = [
        'CNPM' => [['DS101', 'TN01'], ['SE101', 'TN01'], ['MATH101', 'TN01'], ['DB101', 'TN01']],
        'HTTT' => [['DS101', 'TN01'], ['MATH101', 'TN01'], ['DB101', 'TN01'], ['NET101', 'TN01']],
        'KHMT' => [['DS101', 'TN02'], ['MATH101', 'TN01'], ['DB101', 'TN01'], ['NET101', 'TN01']],
        'ATTT' => [['DS101', 'TN01'], ['MATH101', 'TN02'], ['DB101', 'TN01'], ['NET101', 'TN01']],
        'MMT' => [['DS101', 'TN01'], ['MATH101', 'TN02'], ['DB101', 'TN01'], ['NET101', 'TN01']],
    ];

    /**
     * Lớp sẽ đăng ký ở học kỳ hiện hành, theo chuyên ngành: [mã môn, nhóm].
     * Độ dài tín chỉ luôn dưới ngưỡng 14 của config('academic.max_credits_per_term').
     */
    private const CURRENT_COURSES = [
        'CNPM' => [['WEB101', 'TN01'], ['CNPM102', 'TN01'], ['NOSQL101', 'TN01']],
        'HTTT' => [['HTTT101', 'TN01'], ['NOSQL101', 'TN01'], ['MKT101', 'TN01']],
        'KHMT' => [['KHMT101', 'TN01'], ['OPEN101', 'TN01'], ['IOT101', 'TN01']],
        'ATTT' => [['ATTT101', 'TN01'], ['SEC101', 'TN01'], ['MMT101', 'TN01']],
        'MMT' => [['MMT101', 'TN01'], ['SEC101', 'TN01'], ['IOT101', 'TN01']],
    ];

    /**
     * Sinh viên này trượt DS101 học kỳ trước nên không được đăng ký môn có tiên quyết là DS101
     * (WEB101); CNPM102 cũng bị chặn vì phải học song hành với WEB101. Danh sách riêng để dữ
     * liệu khớp với EnrollmentPolicy.
     */
    private const COURSES_BY_STUDENT_CODE = [
        '20240007' => [['NOSQL101', 'TN01'], ['MKT101', 'TN01']],
    ];

    /** [mã môn, chuyên ngành] của em trượt tiên quyết — điểm ba cột được ghi thấp có chủ đích. */
    private const FAILING_STUDENT = ['code' => '20240007', 'subject' => 'DS101', 'scores' => [6.0, 4.0, 3.0]];

    /** Lớp của kỳ trước không còn mở ở kỳ này, chỉ dùng để dựng lịch sử học tập. */
    private const PAST_OFFERINGS = [
        ['DS101', 'TN01', 'GV005', 'B.101', 60],
        ['DS101', 'TN02', 'GV005', 'B.102', 55],
        ['SE101', 'TN01', 'GV009', 'B.201', 45],
        ['MATH101', 'TN01', 'GV_KH01', 'C.301', 70],
        ['MATH101', 'TN02', 'GV_KH01', 'C.302', 65],
        ['DB101', 'TN01', 'GV006', 'B.103', 60],
        ['NET101', 'TN01', 'GV004', 'A.201', 50],
    ];

    /**
     * Lớp kỳ hiện hành: [mã môn, nhóm, mã giảng viên|null, phòng, sĩ số, mở cổng].
     * Bốn dòng cuối không có sinh viên nào đăng ký và chỉ tồn tại để màn hình chọn lớp còn đất
     * hiển thị các trạng thái ngoại lệ: GRAD101 chưa mở cổng, WEB101 nhóm 2 hết chỗ, KHMT102
     * không ai đủ tiên quyết, MMT102 chưa phân công giảng viên.
     */
    private const CURRENT_OFFERINGS = [
        ['WEB101', 'TN01', 'GV010', 'B.101', 40, true],
        ['CNPM102', 'TN01', 'GV002', 'B.102', 40, true],
        ['NOSQL101', 'TN01', 'GV006', 'B.103', 50, true],
        ['HTTT101', 'TN01', 'GV003', 'A.201', 40, true],
        ['MKT101', 'TN01', 'GV_QTR02', 'A.202', 60, true],
        ['KHMT101', 'TN01', 'GV008', 'B.201', 40, true],
        ['OPEN101', 'TN01', 'GV001', 'B.202', 45, true],
        ['IOT101', 'TN01', 'GV_DT02', 'C.101', 45, true],
        ['ATTT101', 'TN01', 'GV007', 'D.301', 40, true],
        ['SEC101', 'TN01', 'GV007', 'D.302', 40, true],
        ['MMT101', 'TN01', 'GV004', 'A.301', 50, true],
        ['ENG101', 'TN01', 'GV_NN01', 'E.101', 80, true],
        ['LAW101', 'TN01', 'GV_LUAT01', 'E.202', 70, true],
        ['GRAD101', 'TN01', 'GV009', 'F.401', 20, false],
        ['WEB101', 'TN02', 'GV005', 'B.104', 0, true],
        ['KHMT102', 'TN01', 'GV008', 'B.203', 30, true],
        ['MMT102', 'TN01', null, 'A.302', 35, true],
    ];

    /**
     * Mỗi lớp một slot duy nhất trong tuần. (ngày, ca) của ngày = (i % 5) + 2 và
     * ca = intdiv(i, 5) nên không hai lớp nào trùng nhau, lịch của mọi người dùng đều sạch
     * và không sinh viên nào bị "Trùng giờ" khi đăng ký theo danh sách ở trên.
     */
    private const TIME_BANDS = [
        ['07:00', '09:30'],
        ['09:40', '12:00'],
        ['13:00', '15:30'],
        ['15:40', '18:00'],
    ];

    /**
     * Điểm ba cột thành phần theo trọng số 10/20/70 của GradingScale.
     * [điểm chuyên cần, điểm giữa kỳ, điểm cuối kỳ, công bố?]
     */
    private const SCORE_SETS = [
        [9.0, 8.5, 8.0, true],
        [8.5, 7.0, 6.5, true],
        [9.5, 9.0, 8.5, true],
        [7.0, 6.5, 5.5, true],
        [8.0, 7.5, 7.0, true],
        [10.0, 9.0, 9.5, true],
        [7.5, 8.0, 7.0, true],
        [6.5, 5.0, 4.5, true],
        [9.0, 8.0, 7.5, false],
        [8.5, 9.5, 9.0, true],
    ];

    /** Môn kỳ trước giữ trạng thái chưa công bố, để bảng điểm còn hai nhánh hiển thị. */
    private const UNPUBLISHED_SUBJECT = 'MATH101';

    /** MSSV có chữ số cuối chia hết cho 3 được giả lập đã chuyển khoản kỳ này. */
    private const PAID_EVERY = 3;

    public function run(): void
    {
        $catalog = require __DIR__.'/data/catalog.php';

        // Một lần băm duy nhất: 50+ tài khoản demo mà băm riêng thì `db:seed` mất hàng chục giây.
        $password = Hash::make(self::PASSWORD);

        $this->admins($password);
        $departments = $this->departments($catalog['departments']);
        $majors = $this->majors($catalog['majors'], $departments);
        $subjects = $this->subjects($catalog['subjects'], $majors);
        $teachers = $this->teachers($catalog['lecturers'], $departments, $password);
        $classes = $this->classes($majors, $teachers);
        $students = $this->students($classes, $password);

        $this->studyPlans($catalog['study_plans'], $majors, $subjects);
        $this->subjectRelations($catalog, $subjects);

        [$past, $current] = $this->semesters();
        $pastOfferings = $this->offerings($subjects, $teachers, $past, self::PAST_OFFERINGS);
        $currentOfferings = $this->offerings($subjects, $teachers, $current, self::CURRENT_OFFERINGS);

        $this->history($students, $pastOfferings);
        $this->register($students, $currentOfferings);
        $this->tuition($past, $current);
    }

    /** Tài khoản quản trị dùng chung cho môi trường demo. */
    private function admins(string $password): void
    {
        User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Quản trị viên',
                'password' => $password,
                'role' => 'admin',
                'student_code' => null,
                'teacher_code' => null,
                'class_id' => null,
            ],
        );
    }

    /**
     * Khoa của danh mục, kèm số điện thoại, email và địa điểm làm việc thật để bảng khoa không
     * còn trơ hai cột mã tên.
     *
     * @param  list<array<string, string>>  $rows
     * @return Collection<string, Department>
     */
    private function departments(array $rows): Collection
    {
        return collect($rows)->mapWithKeys(fn (array $data) => [
            $data['code'] => Department::updateOrCreate(['code' => $data['code']], [
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $data['email'],
                'office' => $data['office'],
                'description' => $data['description'],
            ]),
        ]);
    }

    /**
     * @param  list<array<string, string>>  $rows
     * @param  Collection<string, Department>  $departments
     * @return Collection<string, Major>
     */
    private function majors(array $rows, Collection $departments): Collection
    {
        return collect($rows)->mapWithKeys(fn (array $data) => [
            $data['code'] => Major::updateOrCreate(
                ['code' => $data['code']],
                [
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'department_id' => $departments[$data['department']]->id,
                ],
            ),
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  Collection<string, Major>  $majors
     * @return Collection<string, Subject>
     */
    private function subjects(array $rows, Collection $majors): Collection
    {
        return collect($rows)->mapWithKeys(fn (array $data) => [
            $data['code'] => Subject::updateOrCreate(
                ['code' => $data['code']],
                [
                    'name' => $data['name'],
                    'credits' => $data['credits'],
                    'major_id' => $data['major'] === null ? null : $majors[$data['major']]->id,
                    'total_sessions' => $data['sessions'],
                    'description' => $data['description'],
                ],
            ),
        ]);
    }

    /**
     * Giảng viên theo đúng danh sách bộ môn: học vị, khoa quản lý và email công tác — email này
     * cũng là tài khoản đăng nhập của họ.
     *
     * @param  list<array<string, string>>  $rows
     * @param  Collection<string, Department>  $departments
     * @return Collection<string, User>
     */
    private function teachers(array $rows, Collection $departments, string $password): Collection
    {
        $hometowns = ['TP. Hồ Chí Minh', 'Hà Nội', 'Nghệ An', 'Đà Nẵng', 'Cần Thơ', 'Huế', 'Nha Trang'];

        return collect($rows)->values()->mapWithKeys(function (array $data, int $index) use ($departments, $hometowns, $password) {
            $teacher = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'teacher_code' => $data['code'],
                    'student_code' => null,
                    'password' => $password,
                    'role' => 'teacher',
                    'class_id' => null,
                    'degree' => $data['degree'],
                    'department_id' => $departments[$data['department']]->id,
                    'gender' => $data['gender'],
                    'dob' => sprintf('%d-%02d-%02d', 1978 + ($index % 17), ($index % 12) + 1, ($index % 27) + 1),
                    'hometown' => $hometowns[$index % count($hometowns)],
                    'phone' => $data['phone'],
                ],
            );

            return [$data['code'] => $teacher];
        });
    }

    /**
     * @param  Collection<string, Major>  $majors
     * @param  Collection<string, User>  $teachers
     * @return Collection<string, StudentClass>
     */
    private function classes(Collection $majors, Collection $teachers): Collection
    {
        return collect(self::CLASSES)->mapWithKeys(fn (array $row) => [
            $row[0] => StudentClass::updateOrCreate(
                ['class_code' => $row[0]],
                [
                    'class_name' => $row[1],
                    'intake_year' => self::INTAKE_YEAR,
                    'department_id' => $majors[$row[2]]->department_id,
                    'major_id' => $majors[$row[2]]->id,
                    'adviser_id' => $teachers[$row[3]]->id,
                ],
            ),
        ]);
    }

    /**
     * Hồ sơ sinh viên lấy từ dữ liệu MaNguon: mỗi dòng là một bộ trường lý lịch trích ngang, đủ
     * để trang hồ sơ và phiếu in không còn chỗ trống ngoài ý muốn — trừ ba dòng cuối để trống có
     * chủ đích.
     *
     * @param  Collection<string, StudentClass>  $classes
     * @return Collection<string, ?string> mã sinh viên => mã lớp hành chính (null: chưa xếp lớp)
     */
    private function students(Collection $classes, string $password): Collection
    {
        $rows = require __DIR__.'/data/students.php';

        return collect($rows)->values()->mapWithKeys(function (array $row, int $index) use ($classes, $password) {
            $code = self::INTAKE_YEAR.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT);
            User::updateOrCreate(
                ['email' => 'sinhvien'.($index + 1).'@gmail.com'],
                [
                    'name' => $row[0],
                    'student_code' => $code,
                    'teacher_code' => null,
                    'password' => $password,
                    'role' => 'student',
                    'class_id' => $row[4] === null ? null : $classes[$row[4]]->id,
                    'dob' => $row[2],
                    'gender' => $row[1],
                    'hometown' => $row[3],
                    'phone' => '09'.(20000000 + $index * 173311),
                    'enrolled_on' => self::INTAKE_YEAR.'-09-15',
                    'education_level' => self::EDUCATION_LEVEL,
                    'training_type' => $row[16] ?? self::DEFAULT_TRAINING,
                    'admission_region' => $row[8],
                    'priority_category' => $row[9],
                    'ethnicity' => $row[5],
                    'religion' => $row[6],
                    'nationality' => $row[7],
                    'birth_place' => $row[13],
                    'permanent_residence' => $row[14],
                    'contact_address' => $row[15],
                    'id_card_number' => $row[10],
                    'id_card_issued_at' => $row[11],
                    'id_card_issued_place' => $row[12],
                    'academic_status' => $this->statusFor($index),
                    'academic_status_updated_at' => now()->subDays($index),
                ],
            );

            return [$code => $row[4]];
        });
    }

    /**
     * Phân bố tình trạng học tập để mọi nhãn trong PersonLabels::ACADEMIC_STATUSES đều có dữ
     * liệu; các chỉ số khác ở trạng thái STUDYING.
     */
    private function statusFor(int $index): string
    {
        return match (true) {
            $index === 4 => 'AT_RISK',
            $index === 9 => 'SUSPENDED',
            $index === 23 => 'DROPPED',
            $index === 24 => 'GRADUATED',
            default => 'STUDYING',
        };
    }

    /**
     * @param  list<array{0: string, 1: int, 2: string}>  $rows
     * @param  Collection<string, Major>  $majors
     * @param  Collection<string, Subject>  $subjects
     */
    private function studyPlans(array $rows, Collection $majors, Collection $subjects): void
    {
        foreach ($rows as [$majorCode, $semester, $codes]) {
            [$required, $elective] = array_pad(explode('|', $codes), 2, '');

            foreach (['REQUIRED' => $required, 'ELECTIVE' => $elective] as $kind => $list) {
                foreach (array_filter(array_map('trim', explode(',', $list))) as $subjectCode) {
                    StudyPlan::updateOrCreate(
                        ['major_id' => $majors[$majorCode]->id, 'subject_id' => $subjects[$subjectCode]->id],
                        ['plan_semester' => $semester, 'kind' => $kind],
                    );
                }
            }
        }
    }

    /**
     * Quan hệ tiên quyết và song hành của danh mục. Đồng bộ nguyên danh sách nên chạy lại seeder
     * cũng xóa những cạnh người dùng thêm thử trên màn hình admin.
     *
     * @param  array<string, array<string, list<string>>>  $catalog
     * @param  Collection<string, Subject>  $subjects
     */
    private function subjectRelations(array $catalog, Collection $subjects): void
    {
        foreach (['prerequisites' => 'prerequisites', 'concurrents' => 'concurrents'] as $key => $relation) {
            foreach ($catalog[$key] as $subjectCode => $partners) {
                $subjects[$subjectCode]->{$relation}()->sync(
                    collect($partners)->map(fn (string $code) => $subjects[$code]->id)->all()
                );
            }
        }
    }

    /** @return array{0: Semester, 1: Semester} [học kỳ đã có điểm, học kỳ hiện hành] */
    private function semesters(): array
    {
        $year = now()->month >= 8 ? (int) now()->format('Y') : (int) now()->format('Y') - 1;
        $tokens = ['{P}' => $year - 1, '{Y}' => $year, '{N}' => $year + 1];

        $past = null;
        $current = null;

        foreach (self::SEMESTER_PLAN as [$academicYear, $term, $starts, $ends, $regFrom, $regTo]) {
            $semester = Semester::updateOrCreate(
                ['academic_year' => self::expand($academicYear, $tokens), 'term' => $term],
                [
                    'starts_at' => self::expand($starts, $tokens),
                    'ends_at' => self::expand($ends, $tokens),
                    'registration_starts_at' => $regFrom === null ? now()->subDays(14) : self::expand($regFrom, $tokens),
                    'registration_ends_at' => $regTo === null ? now()->addDays(14) : self::expand($regTo, $tokens),
                ],
            );

            if ($academicYear === '{P}-{Y}' && $term === 1) {
                $past = $semester;
            }
            if ($academicYear === '{Y}-{N}' && $term === 1) {
                $current = $semester;
            }
        }

        // booted() tự bật is_active cho kỳ tạo đầu tiên, nên chốt kỳ hiện hành ở bước cuối.
        Semester::activate($current->id);

        return [$past, $current];
    }

    /** @param  array<string, int>  $tokens */
    private static function expand(string $value, array $tokens): string
    {
        return strtr($value, $tokens);
    }

    /**
     * @param  Collection<string, Subject>  $subjects
     * @param  Collection<string, User>  $teachers
     * @return Collection<string, CourseOffering> khóa "MONHOM" để tra theo mã môn + nhóm
     */
    private function offerings(Collection $subjects, Collection $teachers, Semester $semester, array $rows): Collection
    {
        return collect($rows)->mapWithKeys(function (array $row, int $index) use ($subjects, $teachers, $semester) {
            [$subjectCode, $group, $teacherCode, $room, $max, $isOpen] = array_pad($row, 6, true);

            $offering = CourseOffering::updateOrCreate(
                [
                    'subject_id' => $subjects[$subjectCode]->id,
                    'semester_id' => $semester->id,
                    'group_code' => $group,
                ],
                [
                    'teacher_id' => $teacherCode === null ? null : $teachers[$teacherCode]->id,
                    'room' => $room,
                    'max_students' => $max,
                    'is_open' => $isOpen,
                ],
            );

            $this->slot($offering, $semester, $index);

            return [$subjectCode.$group => $offering];
        });
    }

    /**
     * Lịch lên lớp định kỳ của một lớp học phần. (ngày, ca) suy ra từ vị trí dòng trong danh
     * sách lớp nên mỗi lớp một chỗ đứng riêng trên thời khóa biểu.
     */
    private function slot(CourseOffering $offering, Semester $semester, int $index): void
    {
        [$start, $end] = self::TIME_BANDS[intdiv($index, 5) % count(self::TIME_BANDS)];

        $this->slotsByOffering[$offering->id] = LectureSlot::updateOrCreate(
            [
                'course_offering_id' => $offering->id,
                'week_day' => ($index % 5) + 2,
                'start_time' => $start,
            ],
            [
                'end_time' => $end,
                'room' => $offering->room,
                'starts_on' => $semester->starts_at,
                'ends_on' => $semester->ends_at,
            ],
        );
    }

    /**
     * Kỳ trước: ai còn có lớp đều để lại bốn môn đã học kèm điểm tổng kết (MATH101 giữ trạng
     * thái chưa công bố), đủ để GPA, tín chỉ tích lũy và "Lịch sử học tập" hiển thị.
     *
     * @param  Collection<string, ?string>  $students  mã sinh viên => mã lớp hành chính
     * @param  Collection<string, CourseOffering>  $offerings
     */
    private function history(Collection $students, Collection $offerings): void
    {
        foreach ($students as $code => $classCode) {
            if ($classCode === null) {
                continue;
            }

            $majorCode = $this->majorOf($classCode);
            $student = User::where('student_code', $code)->sole();

            foreach (self::PAST_COURSES[$majorCode] as $offset => [$subjectCode, $group]) {
                $offering = $offerings[$subjectCode.$group];
                $enrollment = Enrollment::updateOrCreate(
                    ['course_offering_id' => $offering->id, 'user_id' => $student->id],
                    ['status' => 'ACTIVE', 'dropped_at' => null],
                );

                [$attendance, $midterm, $final, $published] = self::scoreSet($code, $offset);

                // Trượt DS101 để môn có tiên quyết là DS101 còn đường hiển thị cảnh báo.
                if ($code === self::FAILING_STUDENT['code'] && $subjectCode === self::FAILING_STUDENT['subject']) {
                    [$attendance, $midterm, $final] = self::FAILING_STUDENT['scores'];
                    $published = true;
                }

                Grade::updateOrCreate(
                    ['course_offering_id' => $offering->id, 'user_id' => $student->id],
                    [
                        'enrollment_id' => $enrollment->id,
                        'attendance_score' => $attendance,
                        'midterm_score' => $midterm,
                        'final_score' => $final,
                        'is_published' => $subjectCode === self::UNPUBLISHED_SUBJECT ? false : $published,
                    ],
                );
            }
        }
    }

    /**
     * Kỳ hiện hành: đăng ký theo khung, lịch điểm danh của các buổi đã qua và điểm thành
     * phần — một nửa lớp đã công bố, nửa còn lại chờ thi cuối kỳ. Sinh viên bị đình chỉ hoặc
     * nghỉ học vẫn có đăng ký nhưng ở trạng thái DROPPED và không có điểm.
     *
     * @param  Collection<string, ?string>  $students
     * @param  Collection<string, CourseOffering>  $offerings
     */
    private function register(Collection $students, Collection $offerings): void
    {
        foreach ($students as $code => $classCode) {
            $student = User::where('student_code', $code)->sole();

            if ($classCode === null || $student->academic_status === 'GRADUATED') {
                continue;
            }

            $inactive = in_array($student->academic_status, ['SUSPENDED', 'DROPPED'], true);
            $courses = self::COURSES_BY_STUDENT_CODE[$code] ?? self::CURRENT_COURSES[$this->majorOf($classCode)];

            foreach ($courses as $offset => [$subjectCode, $group]) {
                $offering = $offerings[$subjectCode.$group];
                $enrollment = Enrollment::updateOrCreate(
                    ['course_offering_id' => $offering->id, 'user_id' => $student->id],
                    [
                        'status' => $inactive ? 'DROPPED' : 'ACTIVE',
                        'dropped_at' => $inactive ? now()->subWeek() : null,
                    ],
                );

                if ($inactive) {
                    continue;
                }

                $this->sessions($enrollment, $offering, $code);
                $this->midtermGrade($enrollment, $offering, $student, $offset);
            }
        }
    }

    /**
     * Bốn buổi đã diễn ra của lớp, lấy từ slot định kỳ của nó. Ba mã dưới đây vắng mặt có chủ
     * đích: 20240005 nghỉ hai buổi gần nhất nên chuyên cần còn 50%, 20240003 và 20240007 nghỉ
     * một buổi nên còn 75% - tất cả đều dưới ngưỡng config('academic.min_attendance_rate') để
     * màn hình học vụ còn đất gợi ý; những sinh viên khác đi học đầy đủ.
     */
    private function sessions(Enrollment $enrollment, CourseOffering $offering, string $code): void
    {
        $slot = $this->slotsByOffering[$offering->id];

        for ($week = 0; $week < 4; $week++) {
            $date = $this->lectureDate($slot->week_day, $offering->semester->starts_at, $week);

            if ($date->greaterThan(now())) {
                break;
            }

            $session = AttendanceSession::updateOrCreate(
                ['course_offering_id' => $offering->id, 'session_date' => $date->toDateString()],
                ['lecture_slot_id' => $slot->id, 'recorded_by' => $offering->teacher_id],
            );

            $absent = match ($code) {
                '20240005' => $week >= 2,
                '20240003', '20240007' => $week === 1,
                default => false,
            };

            Attendance::updateOrCreate(
                ['attendance_session_id' => $session->id, 'enrollment_id' => $enrollment->id],
                ['course_offering_id' => $offering->id, 'status' => $absent ? Attendance::ABSENT : Attendance::PRESENT],
            );
        }
    }

    /**
     * Điểm học kỳ hiện hành: đã có chuyên cần và giữa kỳ, cuối kỳ chỉ có với một nửa lớp.
     */
    private function midtermGrade(Enrollment $enrollment, CourseOffering $offering, User $student, int $offset): void
    {
        [$attendance, $midterm, $final, $published] = self::scoreSet($student->student_code, $offset + 3);

        Grade::updateOrCreate(
            ['course_offering_id' => $offering->id, 'user_id' => $student->id],
            [
                'enrollment_id' => $enrollment->id,
                'attendance_score' => $attendance,
                'midterm_score' => $midterm,
                'final_score' => $offset % 2 === 0 ? $final : null,
                'is_published' => $offset % 2 === 0 && $published,
            ],
        );
    }

    /**
     * Điểm của một sinh viên trong một lớp: chọn theo mã số để mỗi người một mặt bằng khác nhau
     * nhưng chạy lại seeder thì dữ liệu không đổi.
     *
     * @return array{0: float, 1: float, 2: float, 3: bool}
     */
    private function scoreSet(string $code, int $offset): array
    {
        $seed = ((int) substr($code, -3)) + $offset;

        return self::SCORE_SETS[$seed % count(self::SCORE_SETS)];
    }

    /**
     * Ngày của buổi thứ $week trong lớp: từ đầu học kỳ, tìm ngày gần nhất đúng thứ của slot
     * rồi cộng dồn theo tuần. week_day theo lịch trường (2 = Thứ 2) nên Carbon dayOfWeek
     * (0 = Chủ nhật) là week_day - 1.
     */
    private function lectureDate(int $weekDay, Carbon $startsAt, int $week): Carbon
    {
        $target = $weekDay - 1;
        $first = (clone $startsAt)->addDays((7 + $target - (int) $startsAt->dayOfWeek) % 7);

        return $first->addWeeks($week);
    }

    /**
     * Thông báo học phí hai kỳ có dữ liệu: kỳ đã đóng thu xong toàn bộ, kỳ hiện hành thu một
     * phần để màn hình còn cả trạng thái chờ. Đơn giá do nhà trường cấu hình, mức 0 nghĩa là
     * chưa áp học phí thì seeder không tự bịa ra thông báo.
     */
    private function tuition(Semester $past, Semester $current): void
    {
        if (Tuition::perCredit() === 0) {
            return;
        }

        Tuition::declare($past);
        Tuition::declare($current);

        $collector = User::where('role', 'admin')->sole()->id;

        TuitionInvoice::where('semester_id', $past->id)->whereNull('paid_at')->update([
            'paid_at' => $past->ends_at->copy()->subWeek()->format('Y-m-d H:i:s'),
            'collected_by' => $collector,
        ]);

        TuitionInvoice::with('student')
            ->where('semester_id', $current->id)
            ->whereNull('paid_at')
            ->get()
            ->filter(fn (TuitionInvoice $invoice) => $invoice->student !== null
                && (int) substr((string) $invoice->student->student_code, -1) % self::PAID_EVERY === 0)
            ->each(fn (TuitionInvoice $invoice) => $invoice->update([
                'paid_at' => $current->starts_at->copy()->addWeeks(6)->format('Y-m-d H:i:s'),
                'collected_by' => $collector,
            ]));
    }

    /** Suy ra mã chuyên ngành từ mã lớp hành chính. */
    private function majorOf(string $classCode): string
    {
        foreach (self::CLASSES as [$code, , $majorCode]) {
            if ($code === $classCode) {
                return $majorCode;
            }
        }

        throw new RuntimeException("Lớp {$classCode} không có trong danh sách lớp demo.");
    }
}
