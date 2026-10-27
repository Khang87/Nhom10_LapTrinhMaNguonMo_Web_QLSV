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
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

/**
 * Dữ liệu demo phủ mọi màn hình của hệ thống: một tài khoản quản trị, 3 khoa, 4 chuyên ngành,
 * 16 môn kèm khung chương trình và quan hệ tiên quyết - song hành, 25 giảng viên, 5 lớp hành
 * chính, 24 sinh viên, hai học kỳ (một đã đóng, một đang mở cổng đăng ký) với lớp học phần,
 * lịch lên lớp, đăng ký, điểm thành phần và điểm danh theo buổi.
 *
 * Mọi dòng dùng updateOrCreate theo mã duy nhất nên chạy lại nhiều lần không tạo bản sao.
 * Trình tự tạo dữ liệu có chủ đích: danh mục -> người -> học kỳ -> lớp -> lịch -> đăng ký
 * -> điểm/điểm danh, vì mỗi bước đọc mã của bước trước.
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

    /** Khung chương trình: [mã môn, học kỳ đào tạo, loại]. */
    private const STUDY_PLANS = [
        'KTPM' => [
            ['OOP101', 1, 'REQUIRED'], ['GTA101', 1, 'REQUIRED'], ['WEB101', 2, 'REQUIRED'],
            ['DB101', 2, 'REQUIRED'], ['PROB101', 2, 'REQUIRED'], ['ADT101', 3, 'REQUIRED'],
            ['SE201', 3, 'REQUIRED'], ['MOB201', 4, 'REQUIRED'], ['CLO201', 4, 'ELECTIVE'],
            ['SEC201', 5, 'ELECTIVE'],
        ],
        'HTTT' => [
            ['GTA101', 1, 'REQUIRED'], ['OOP101', 1, 'REQUIRED'], ['NET101', 2, 'REQUIRED'],
            ['DB101', 2, 'REQUIRED'], ['PROB101', 3, 'REQUIRED'], ['SEC201', 3, 'REQUIRED'],
            ['BIG201', 4, 'REQUIRED'], ['CLO201', 4, 'REQUIRED'], ['AI101', 5, 'ELECTIVE'],
        ],
        'KHMT' => [
            ['GEB101', 1, 'REQUIRED'], ['OOP101', 1, 'REQUIRED'], ['ADT101', 2, 'REQUIRED'],
            ['OS101', 2, 'REQUIRED'], ['DB101', 3, 'REQUIRED'], ['AI101', 3, 'REQUIRED'],
            ['NET101', 4, 'ELECTIVE'], ['SE201', 4, 'ELECTIVE'],
        ],
        'DKT' => [
            ['GTA101', 1, 'REQUIRED'], ['GEB101', 1, 'REQUIRED'], ['EMB101', 2, 'REQUIRED'],
            ['OOP101', 2, 'REQUIRED'], ['ADT101', 3, 'REQUIRED'], ['OS101', 3, 'REQUIRED'],
        ],
    ];

    /** Môn đã học ở học kỳ trước, theo chuyên ngành: [mã môn, nhóm]. */
    private const PAST_COURSES = [
        'KTPM' => [['OOP101', 'TN01'], ['GTA101', 'TN01'], ['PROB101', 'TN01']],
        'HTTT' => [['OOP101', 'TN01'], ['GTA101', 'TN01'], ['PROB101', 'TN01']],
        'KHMT' => [['OOP101', 'TN02'], ['GEB101', 'TN01'], ['PROB101', 'TN01']],
        'DKT' => [['GEB101', 'TN01'], ['OOP101', 'TN02'], ['PROB101', 'TN01']],
    ];

    /**
     * Lớp sẽ đăng ký ở học kỳ hiện hành, theo chuyên ngành: [mã môn, nhóm].
     * Độ dài tín chỉ luôn dưới ngưỡng 14 của config('academic.max_credits_per_term').
     */
    private const CURRENT_COURSES = [
        'KTPM' => [['WEB101', 'TN01'], ['DB101', 'TN01'], ['ADT101', 'TN01']],
        'HTTT' => [['NET101', 'TN01'], ['SEC201', 'TN01'], ['BIG201', 'TN01'], ['CLO201', 'TN01']],
        'KHMT' => [['ADT101', 'TN01'], ['OS101', 'TN01'], ['SE201', 'TN01']],
        'DKT' => [['WEB101', 'TN02'], ['MOB201', 'TN01']],
    ];

    /**
     * Sinh viên này trượt OOP101 học kỳ trước nên không được đăng ký môn có tiên quyết là
     * OOP101 (WEB101, DB101); danh sách riêng để dữ liệu khớp với EnrollmentPolicy.
     */
    private const COURSES_BY_STUDENT_CODE = [
        '20240007' => [['ADT101', 'TN01'], ['MOB201', 'TN01']],
    ];

    /** Lớp của học kỳ trước không còn mở ở kỳ này, chỉ dùng để dựng lịch sử học tập. */
    private const PAST_OFFERINGS = [
        ['OOP101', 'TN01', 'GV01', 'A101', 40],
        ['OOP101', 'TN02', 'GV02', 'A102', 35],
        ['GTA101', 'TN01', 'GV13', 'C201', 60],
        ['GEB101', 'TN01', 'GV14', 'C202', 60],
        ['PROB101', 'TN01', 'GV15', 'C203', 50],
    ];

    /**
     * Lớp học kỳ hiện hành: [mã môn, nhóm, mã giảng viên|null, phòng, sĩ số, mở cổng].
     * Ba dòng cuối không có sinh viên nào đăng ký và chỉ tồn tại để màn hình chọn lớp còn đất
     * hiển thị các trạng thái ngoại lệ: AI101 thiếu tiên quyết với mọi người, DB101 nhóm 2 hết
     * chỗ, CLO201 nhóm 2 chưa mở cổng; BIG201 chưa phân công giảng viên.
     */
    private const CURRENT_OFFERINGS = [
        ['WEB101', 'TN01', 'GV01', 'A101', 30, true],
        ['WEB101', 'TN02', 'GV02', 'A102', 25, true],
        ['DB101', 'TN01', 'GV03', 'A103', 35, true],
        ['ADT101', 'TN01', 'GV04', 'B201', 30, true],
        ['OS101', 'TN01', 'GV05', 'B202', 30, true],
        ['NET101', 'TN01', 'GV06', 'B203', 40, true],
        ['SEC201', 'TN01', 'GV07', 'B204', 20, true],
        ['SE201', 'TN01', 'GV08', 'A204', 30, true],
        ['AI101', 'TN01', 'GV09', 'C301', 25, true],
        ['MOB201', 'TN01', 'GV10', 'C302', 25, true],
        ['CLO201', 'TN01', 'GV11', 'C303', 25, true],
        ['BIG201', 'TN01', null, 'D401', 30, true],
        ['DB101', 'TN02', 'GV12', 'A104', 0, true],
        ['CLO201', 'TN02', 'GV17', 'C304', 25, false],
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

    public function run(): void
    {
        // Một lần băm duy nhất: 50+ tài khoản demo mà băm riêng thì `db:seed` mất hàng chục giây.
        $password = Hash::make(self::PASSWORD);

        $this->admins($password);
        $departments = $this->departments();
        $majors = $this->majors($departments);
        $subjects = $this->subjects($majors);
        $teachers = $this->teachers($password);
        $classes = $this->classes($majors, $teachers);
        $students = $this->students($classes, $password);

        $this->studyPlans($majors, $subjects);
        $this->subjectRelations($subjects);

        [$past, $current] = $this->semesters();
        $pastOfferings = $this->offerings($subjects, $teachers, $past, self::PAST_OFFERINGS);
        $currentOfferings = $this->offerings($subjects, $teachers, $current, self::CURRENT_OFFERINGS);

        $this->history($students, $pastOfferings);
        $this->register($students, $currentOfferings);
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

    /** @return Collection<string, Department> */
    private function departments(): Collection
    {
        return collect([
            ['code' => 'CNTT', 'name' => 'Khoa Công nghệ thông tin'],
            ['code' => 'KHTN', 'name' => 'Khoa Khoa học cơ bản'],
            ['code' => 'KTDT', 'name' => 'Khoa Điện — Điện tử'],
        ])->mapWithKeys(fn (array $data) => [
            $data['code'] => Department::updateOrCreate(['code' => $data['code']], ['name' => $data['name']]),
        ]);
    }

    /** @return Collection<string, Major> */
    private function majors(Collection $departments): Collection
    {
        return collect([
            ['code' => 'KTPM', 'name' => 'Kỹ thuật phần mềm', 'department' => 'CNTT'],
            ['code' => 'HTTT', 'name' => 'Hệ thống thông tin', 'department' => 'CNTT'],
            ['code' => 'KHMT', 'name' => 'Khoa học máy tính', 'department' => 'CNTT'],
            ['code' => 'DKT', 'name' => 'Điều khiển và tự động hóa', 'department' => 'KTDT'],
        ])->mapWithKeys(fn (array $data) => [
            $data['code'] => Major::updateOrCreate(
                ['code' => $data['code']],
                ['name' => $data['name'], 'department_id' => $departments[$data['department']]->id],
            ),
        ]);
    }

    /**
     * @param  Collection<string, Major>  $majors
     * @return Collection<string, Subject>
     */
    private function subjects(Collection $majors): Collection
    {
        $ktpm = $majors['KTPM']->id;
        $httt = $majors['HTTT']->id;
        $khmt = $majors['KHMT']->id;
        $dkt = $majors['DKT']->id;

        $rows = [
            ['GTA101', 'Giải tích A', 3, null, 45, 'Giới hạn, đạo hàm và tích hàm của một biến.'],
            ['GEB101', 'Giải tích B', 3, null, 45, 'Chuỗi số, tích phân nhiều lớp và phương trình vi phân.'],
            ['PROB101', 'Xác suất thống kê', 3, null, 45, 'Môn chung của toàn trường, dùng cho mọi chuyên ngành.'],
            ['OOP101', 'Lập trình hướng đối tượng', 4, $ktpm, 75, 'Kế thừa, đa hình và thiết kế lớp với Java.'],
            ['WEB101', 'Lập trình Web', 3, $ktpm, 60, 'Xây dựng ứng dụng web với Laravel và Bootstrap.'],
            ['DB101', 'Cơ sở dữ liệu', 3, $ktpm, 60, 'Thiết kế, chuẩn hóa và truy vấn cơ sở dữ liệu quan hệ.'],
            ['ADT101', 'Cấu trúc dữ liệu và giải thuật', 4, $khmt, 75, 'Danh sách, cây, bảng băm và độ phức tạp.'],
            ['OS101', 'Hệ điều hành', 3, $khmt, 60, 'Tiến trình, bộ nhớ ảo và đồng bộ hóa.'],
            ['NET101', 'Mạng máy tính', 2, $httt, 45, 'Mô hình TCP/IP, định tuyến và chuyển mạch.'],
            ['SEC201', 'An toàn thông tin', 3, $httt, 60, 'Mật mã, an toàn mạng và ứng cứu sự cố.'],
            ['AI101', 'Trí tuệ nhân tạo', 3, $khmt, 60, 'Tìm kiếm, biểu diễn tri thức và học máy cơ bản.'],
            ['SE201', 'Kỹ nghệ phần mềm', 3, $ktpm, 60, 'Quy trình, đặc tả và kiểm thử phần mềm.'],
            ['MOB201', 'Lập trình di động', 3, $ktpm, 60, 'Ứng dụng Android và iOS theo mô hình MVC.'],
            ['CLO201', 'Điện toán đám mây', 2, $httt, 45, 'Ảo hóa, containers và dịch vụ cloud.'],
            ['BIG201', 'Khai phá dữ liệu', 3, $httt, 60, 'Tiền xử lý, lớp hồi quy và phân cụm dữ liệu lớn.'],
            ['EMB101', 'Hệ thống nhúng', 3, $dkt, 60, 'Vi điều khiển, RTOS và giao tiếp cảm biến.'],
        ];

        return collect($rows)->mapWithKeys(fn (array $row) => [
            $row[0] => Subject::updateOrCreate(
                ['code' => $row[0]],
                ['name' => $row[1], 'credits' => $row[2], 'major_id' => $row[3],
                    'total_sessions' => $row[4], 'description' => $row[5]],
            ),
        ]);
    }

    /** @return Collection<string, User>  theo mã giảng viên */
    private function teachers(string $password): Collection
    {
        $names = [
            'Nguyễn Văn Đạt', 'Trần Thị Kim Oanh', 'Lê Minh Hùng', 'Phạm Quốc Trung',
            'Hoàng Thanh Tùng', 'Võ Thị Lệ Xuân', 'Đặng Tiến Dũng', 'Bùi Hải Triều',
            'Đỗ Quang Lâm', 'Hồ Hoài Nam', 'Ngô Thị Thu Trang', 'Dương Trường Giang',
            'Lý Hoàng Long', 'Trịnh Thị Hồng Hạnh', 'Mai Tiến Dụng', 'Phan Đức Thắng',
            'Trương Ái Nhung', 'Lê Viết Lam', 'Nguyễn Thị Bích Trâm', 'Trần Nhật Hào',
            'Phạm Thị Yến My', 'Hoàng Lê Phi Phượng', 'Vũ Đình Khoa', 'Đoàn Minh Khôi',
            'Nguyễn Thị Kim Ngân',
        ];
        $hometowns = ['Hà Nội', 'Nghệ An', 'Đà Nẵng', 'Hải Phòng', 'Cần Thơ', 'Huế', 'Nha Trang'];

        return collect($names)->values()->mapWithKeys(function (string $name, int $index) use ($hometowns, $password) {
            $code = 'GV'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
            $teacher = User::updateOrCreate(
                ['email' => 'giangvien'.($index + 1).'@gmail.com'],
                [
                    'name' => $name,
                    'teacher_code' => $code,
                    'student_code' => null,
                    'password' => $password,
                    'role' => 'teacher',
                    'class_id' => null,
                    'dob' => sprintf('%d-%02d-%02d', 1979 + ($index % 15), ($index % 12) + 1, ($index % 27) + 1),
                    'gender' => $index % 3 === 1 ? 'FEMALE' : 'MALE',
                    'hometown' => $hometowns[$index % count($hometowns)],
                    'phone' => '09'.(10000000 + $index * 137333),
                ],
            );

            return [$code => $teacher];
        });
    }

    /**
     * @param  Collection<string, Major>  $majors
     * @param  Collection<string, User>  $teachers
     * @return Collection<string, StudentClass>
     */
    private function classes(Collection $majors, Collection $teachers): Collection
    {
        $rows = [
            ['CTK44A', 'Công nghệ thông tin 44A', 2024, 'KTPM', 'GV01'],
            ['CTK44B', 'Công nghệ thông tin 44B', 2024, 'KTPM', 'GV02'],
            ['CTT44A', 'Hệ thống thông tin 44A', 2024, 'HTTT', 'GV06'],
            ['KHT44A', 'Khoa học máy tính 44A', 2024, 'KHMT', 'GV04'],
            ['DKT45A', 'Điều khiển tự động 45A', 2024, 'DKT', 'GV19'],
        ];

        return collect($rows)->mapWithKeys(fn (array $row) => [
            $row[0] => StudentClass::updateOrCreate(
                ['class_code' => $row[0]],
                [
                    'class_name' => $row[1],
                    'intake_year' => $row[2],
                    'department_id' => $majors[$row[3]]->department_id,
                    'major_id' => $majors[$row[3]]->id,
                    'adviser_id' => $teachers[$row[4]]->id,
                ],
            ),
        ]);
    }

    /**
     * @param  Collection<string, StudentClass>  $classes
     * @return Collection<string, ?string> mã sinh viên => mã lớp hành chính (null: chưa xếp lớp)
     */
    private function students(Collection $classes, string $password): Collection
    {
        $rows = [
            ['Nguyễn Minh Anh', 'MALE', 'Hà Nội', 'CTK44A'],
            ['Trần Hoàng Nam', 'MALE', 'Đà Nẵng', 'CTK44A'],
            ['Lê Thu Hà', 'FEMALE', 'Hải Phòng', 'CTK44A'],
            ['Phạm Quốc Bảo', 'MALE', 'Cần Thơ', 'CTK44B'],
            ['Võ Ngọc Linh', 'FEMALE', 'Huế', 'CTK44B'],
            ['Đặng Tiến Dũng', 'MALE', 'Nha Trang', 'CTK44B'],
            ['Bùi Gia Huy', 'MALE', 'Hà Nội', 'CTK44A'],
            ['Đỗ Thị Mai', 'FEMALE', 'Nghệ An', 'CTK44A'],
            ['Hồ Khánh Vy', 'FEMALE', 'Đà Nẵng', 'CTK44B'],
            ['Ngô Văn Tú', 'MALE', 'Hải Phòng', 'CTK44A'],
            ['Dương Thị Yến', 'FEMALE', 'Cần Thơ', 'CTK44B'],
            ['Lý Thanh Tùng', 'MALE', 'Huế', 'CTK44A'],
            ['Trịnh Hoàng Phúc', 'MALE', 'Nha Trang', 'CTK44B'],
            ['Mai Thảo My', 'FEMALE', 'Hà Nội', 'CTK44A'],
            ['Phan Văn Đạt', 'MALE', 'Nghệ An', 'KHT44A'],
            ['Trương Thị Hoa', 'FEMALE', 'Đà Nẵng', 'KHT44A'],
            ['Lê Minh Khôi', 'MALE', 'Hải Phòng', 'KHT44A'],
            ['Nguyễn Nhật Lan', 'FEMALE', 'Cần Thơ', 'CTT44A'],
            ['Trần Quang Huy', 'MALE', 'Huế', 'CTT44A'],
            ['Phạm Thanh Tâm', 'FEMALE', 'Nha Trang', 'CTT44A'],
            ['Hoàng Bảo Ngọc', 'FEMALE', 'Hà Nội', 'DKT45A'],
            ['Vũ Tiến Lực', 'MALE', 'Nghệ An', 'DKT45A'],
            ['Đoàn Thị Mai Anh', 'FEMALE', 'Đà Nẵng', 'DKT45A'],
            // Tốt nghiệp nên không còn lớp hành chính: bộ lọc "Chưa xếp lớp" và hồ sơ trống lớp còn đất hiển thị.
            ['Ngô Thị Diễm My', 'FEMALE', 'Hải Phòng', null],
        ];

        return collect($rows)->values()->mapWithKeys(function (array $row, int $index) use ($classes, $password) {
            $code = '2024'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT);
            User::updateOrCreate(
                ['email' => 'sinhvien'.($index + 1).'@gmail.com'],
                [
                    'name' => $row[0],
                    'student_code' => $code,
                    'teacher_code' => null,
                    'password' => $password,
                    'role' => 'student',
                    'class_id' => $row[3] === null ? null : $classes[$row[3]]->id,
                    'dob' => sprintf('200%d-%02d-%02d', $index % 7, ($index % 12) + 1, ($index % 27) + 1),
                    'gender' => $row[1],
                    'hometown' => $row[2],
                    'phone' => '09'.(20000000 + $index * 173311),
                    'academic_status' => $this->statusFor($index),
                    'academic_status_updated_at' => now()->subDays($index),
                ],
            );

            return [$code => $row[3]];
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
            $index === 22 => 'DROPPED',
            $index === 23 => 'GRADUATED',
            default => 'STUDYING',
        };
    }

    /**
     * @param  Collection<string, Major>  $majors
     * @param  Collection<string, Subject>  $subjects
     */
    private function studyPlans(Collection $majors, Collection $subjects): void
    {
        foreach (self::STUDY_PLANS as $majorCode => $rows) {
            foreach ($rows as [$subjectCode, $semester, $kind]) {
                StudyPlan::updateOrCreate(
                    ['major_id' => $majors[$majorCode]->id, 'subject_id' => $subjects[$subjectCode]->id],
                    ['plan_semester' => $semester, 'kind' => $kind],
                );
            }
        }
    }

    /**
     * WEB101 và DB101 cần OOP101, AI101 cần ADT101; CLO201 học song hành với NET101.
     *
     * @param  Collection<string, Subject>  $subjects
     */
    private function subjectRelations(Collection $subjects): void
    {
        $subjects['WEB101']->prerequisites()->sync([$subjects['OOP101']->id]);
        $subjects['DB101']->prerequisites()->sync([$subjects['OOP101']->id]);
        $subjects['AI101']->prerequisites()->sync([$subjects['ADT101']->id]);
        $subjects['SEC201']->concurrents()->sync([$subjects['NET101']->id]);
    }

    /** @return array{0: Semester, 1: Semester} [học kỳ trước, học kỳ hiện hành] */
    private function semesters(): array
    {
        $startYear = now()->month >= 8 ? (int) now()->format('Y') : (int) now()->format('Y') - 1;

        $past = Semester::updateOrCreate(
            ['academic_year' => ($startYear - 1).'-'.$startYear, 'term' => 1],
            [
                'starts_at' => ($startYear - 1).'-09-01',
                'ends_at' => $startYear.'-01-31',
                'registration_starts_at' => ($startYear - 1).'-08-01',
                'registration_ends_at' => ($startYear - 1).'-08-25',
            ],
        );

        $current = Semester::updateOrCreate(
            ['academic_year' => $startYear.'-'.($startYear + 1), 'term' => 1],
            [
                'starts_at' => $startYear.'-09-01',
                'ends_at' => ($startYear + 1).'-01-31',
                'registration_starts_at' => now()->subDays(14),
                'registration_ends_at' => now()->addDays(14),
            ],
        );

        // booted() tự bật is_active cho kỳ tạo đầu tiên, nên chốt kỳ hiện hành ở bước cuối.
        Semester::activate($current->id);

        return [$past, $current];
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
     * Học kỳ trước: ai còn có lớp đều để lại ba môn đã học kèm điểm tổng kết (PROB101 giữ trạng
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

                // Trượt OOP101 để môn có tiên quyết là OOP101 còn đường hiển thị cảnh báo.
                $final = $code === '20240007' && $subjectCode === 'OOP101' ? 3.5 : $final;

                Grade::updateOrCreate(
                    ['course_offering_id' => $offering->id, 'user_id' => $student->id],
                    [
                        'enrollment_id' => $enrollment->id,
                        'attendance_score' => $attendance,
                        'midterm_score' => $midterm,
                        'final_score' => $final,
                        'is_published' => $subjectCode === 'PROB101' ? false : $published,
                    ],
                );
            }
        }
    }

    /**
     * Học kỳ hiện hành: đăng ký theo khung, lịch điểm danh của các buổi đã qua và điểm thành
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

    /** Suy ra mã chuyên ngành từ mã lớp hành chính. */
    private function majorOf(string $classCode): string
    {
        return match ($classCode) {
            'CTK44A', 'CTK44B' => 'KTPM',
            'CTT44A' => 'HTTT',
            'KHT44A' => 'KHMT',
            default => 'DKT',
        };
    }
}
