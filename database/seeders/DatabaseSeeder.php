<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\ClassSchedule;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\CurriculumFramework;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Lecturer;
use App\Models\Semester;
use App\Models\SemesterEvaluation;
use App\Models\Student;
use App\Models\StudentClass;
use App\Models\Subject;
use App\Models\TuitionInvoice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with 100% real SQLite data.
     */
    public function run(): void
    {
        // 1. Lớp học danh nghĩa
        $class14DHTH04 = StudentClass::updateOrCreate(
            ['class_code' => '14DHTH04'],
            ['class_name' => 'Đại học Tin học 04', 'room' => 'A101']
        );
        $class14DHTH08 = StudentClass::updateOrCreate(
            ['class_code' => '14DHTH08'],
            ['class_name' => 'Đại học Tin học 08', 'room' => 'A102']
        );

        // 2. Tài khoản Admin
        User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'username' => 'admin',
                'name' => 'Quản trị viên HUIT',
                'password' => Hash::make('12345678'),
                'role' => 'admin',
                'class_id' => null,
                'is_active' => true,
            ]
        );

        // 3. Danh sách Giảng viên phong phú từ nhiều Khoa / Ngành khác nhau tại HUIT (22 Giảng viên)
        $lecturersData = [
            // Khoa Công nghệ Thông tin
            ['code' => 'GV001', 'name' => 'ThS. Nguyễn Văn A', 'email' => 'giangvien@gmail.com', 'degree' => 'Thạc sĩ Khoa học Máy tính', 'faculty' => 'Khoa Công nghệ Thông tin', 'phone' => '0912345671'],
            ['code' => 'GV002', 'name' => 'TS. Lê Hoàng Nam', 'email' => 'namlh@huit.edu.vn', 'degree' => 'Tiến sĩ Kỹ thuật Phần mềm', 'faculty' => 'Khoa Công nghệ Thông tin', 'phone' => '0912345672'],
            ['code' => 'GV003', 'name' => 'ThS. Trần Thị Bích', 'email' => 'bichttt@huit.edu.vn', 'degree' => 'Thạc sĩ Hệ thống Thông tin', 'faculty' => 'Khoa Công nghệ Thông tin', 'phone' => '0912345673'],
            ['code' => 'GV004', 'name' => 'PGS.TS Phạm Minh Tuấn', 'email' => 'tuanpm@huit.edu.vn', 'degree' => 'Phó Giáo sư Mạng máy tính', 'faculty' => 'Khoa Công nghệ Thông tin', 'phone' => '0912345674'],
            ['code' => 'GV005', 'name' => 'ThS. Đỗ Thị Mai', 'email' => 'maidt@huit.edu.vn', 'degree' => 'Thạc sĩ Trí tuệ Nhân tạo', 'faculty' => 'Khoa Công nghệ Thông tin', 'phone' => '0912345675'],
            ['code' => 'GV006', 'name' => 'TS. Vũ Quốc Huy', 'email' => 'huyvq@huit.edu.vn', 'degree' => 'Tiến sĩ Cơ sở Dữ liệu', 'faculty' => 'Khoa Công nghệ Thông tin', 'phone' => '0912345676'],
            ['code' => 'GV007', 'name' => 'ThS. Nguyễn Hữu Trọng', 'email' => 'trongnh@huit.edu.vn', 'degree' => 'Thạc sĩ An toàn Thông tin', 'faculty' => 'Khoa Công nghệ Thông tin', 'phone' => '0912345677'],
            ['code' => 'GV008', 'name' => 'TS. Lê Thu Hà', 'email' => 'halt@huit.edu.vn', 'degree' => 'Tiến sĩ Khoa học Dữ liệu', 'faculty' => 'Khoa Công nghệ Thông tin', 'phone' => '0912345678'],
            ['code' => 'GV009', 'name' => 'TS. Bùi Anh Tuấn', 'email' => 'tuanba@huit.edu.vn', 'degree' => 'Tiến sĩ Kiến trúc Phần mềm', 'faculty' => 'Khoa Công nghệ Thông tin', 'phone' => '0912345679'],
            ['code' => 'GV010', 'name' => 'ThS. Phan Bích Ngọc', 'email' => 'ngocpb@huit.edu.vn', 'degree' => 'Thạc sĩ Giao diện & Trải nghiệm UI/UX', 'faculty' => 'Khoa Công nghệ Thông tin', 'phone' => '0912345680'],

            // Khoa Quản trị Kinh doanh
            ['code' => 'GV_QTR01', 'name' => 'TS. Trần Đình Thắng', 'email' => 'thangtd@huit.edu.vn', 'degree' => 'Tiến sĩ Quản trị Chiến lược', 'faculty' => 'Khoa Quản trị Kinh doanh', 'phone' => '0912345681'],
            ['code' => 'GV_QTR02', 'name' => 'ThS. Nguyễn Thị Thu Trang', 'email' => 'trangntt@huit.edu.vn', 'degree' => 'Thạc sĩ Marketing Số', 'faculty' => 'Khoa Quản trị Kinh doanh', 'phone' => '0912345682'],
            ['code' => 'GV_QTR03', 'name' => 'TS. Lê Minh Tuấn', 'email' => 'tuanlm@huit.edu.vn', 'degree' => 'Tiến sĩ Thương mại Điện tử', 'faculty' => 'Khoa Quản trị Kinh doanh', 'phone' => '0912345683'],

            // Khoa Ngoại ngữ
            ['code' => 'GV_NN01', 'name' => 'ThS. Jennifer Phạm (Phạm Thị Kim Loan)', 'email' => 'loanpk@huit.edu.vn', 'degree' => 'Thạc sĩ Ngôn ngữ Anh (TESOL)', 'faculty' => 'Khoa Ngoại ngữ', 'phone' => '0912345684'],
            ['code' => 'GV_NN02', 'name' => 'ThS. David Trần (Trần Hoàng Kha)', 'email' => 'khatran@huit.edu.vn', 'degree' => 'Thạc sĩ Tiếng Anh Chuyên ngành', 'faculty' => 'Khoa Ngoại ngữ', 'phone' => '0912345685'],
            ['code' => 'GV_NN03', 'name' => 'TS. Nguyễn Văn Hậu', 'email' => 'haunv@huit.edu.vn', 'degree' => 'Tiến sĩ Ngôn ngữ học Ứng dụng', 'faculty' => 'Khoa Ngoại ngữ', 'phone' => '0912345686'],

            // Khoa Tài chính - Ngân hàng
            ['code' => 'GV_TC01', 'name' => 'PGS.TS Vũ Đình Khoa', 'email' => 'khoavd@huit.edu.vn', 'degree' => 'Phó Giáo sư Tài chính Doanh nghiệp', 'faculty' => 'Khoa Tài chính - Ngân hàng', 'phone' => '0912345687'],
            ['code' => 'GV_TC02', 'name' => 'TS. Trần Mỹ Duyên', 'email' => 'duyentm@huit.edu.vn', 'degree' => 'Tiến sĩ Fintech & Ngân hàng số', 'faculty' => 'Khoa Tài chính - Ngân hàng', 'phone' => '0912345688'],

            // Khoa Công nghệ Thực phẩm
            ['code' => 'GV_TP01', 'name' => 'TS. Nguyễn Văn Thịnh', 'email' => 'thinhnv@huit.edu.vn', 'degree' => 'Tiến sĩ Công nghệ Sinh học Thực phẩm', 'faculty' => 'Khoa Công nghệ Thực phẩm', 'phone' => '0912345689'],
            ['code' => 'GV_TP02', 'name' => 'ThS. Lê Kim Ngân', 'email' => 'nganlk@huit.edu.vn', 'degree' => 'Thạc sĩ Đảm bảo Chất lượng & ATTP', 'faculty' => 'Khoa Công nghệ Thực phẩm', 'phone' => '0912345690'],

            // Khoa Kỹ thuật Điện - Điện tử
            ['code' => 'GV_DT01', 'name' => 'TS. Huỳnh Thanh Tùng', 'email' => 'tunght@huit.edu.vn', 'degree' => 'Tiến sĩ Kỹ thuật Viễn thông & IoT', 'faculty' => 'Khoa Kỹ thuật Điện - Điện tử', 'phone' => '0912345691'],
            ['code' => 'GV_DT02', 'name' => 'ThS. Phan Đình Trọng', 'email' => 'trongpd@huit.edu.vn', 'degree' => 'Thạc sĩ Hệ thống Nhúng & Robot', 'faculty' => 'Khoa Kỹ thuật Điện - Điện tử', 'phone' => '0912345692'],

            // Khoa Luật
            ['code' => 'GV_LUAT01', 'name' => 'TS. LS. Trịnh Công Minh', 'email' => 'minhtc@huit.edu.vn', 'degree' => 'Tiến sĩ Luật Kinh tế & Sở hữu Trí tuệ', 'faculty' => 'Khoa Luật', 'phone' => '0912345693'],

            // Khoa Khoa học Ứng dụng
            ['code' => 'GV_KH01', 'name' => 'TS. Dương Ngọc Mai', 'email' => 'maidn@huit.edu.vn', 'degree' => 'Tiến sĩ Toán Tin Ứng dụng & Giải tích', 'faculty' => 'Khoa Khoa học Ứng dụng', 'phone' => '0912345694'],
            ['code' => 'GV_KH02', 'name' => 'ThS. Hoàng Văn Cường', 'email' => 'cuonghv@huit.edu.vn', 'degree' => 'Thạc sĩ Vật lý Kỹ thuật & Bán dẫn', 'faculty' => 'Khoa Khoa học Ứng dụng', 'phone' => '0912345695'],
        ];

        $lecturerModels = [];
        foreach ($lecturersData as $lData) {
            $u = User::updateOrCreate(
                ['email' => $lData['email']],
                [
                    'username' => $lData['code'],
                    'name' => $lData['name'],
                    'password' => Hash::make('12345678'),
                    'role' => 'lecturer',
                    'class_id' => null,
                    'is_active' => true,
                ]
            );

            $lecturerModels[$lData['code']] = Lecturer::updateOrCreate(
                ['user_id' => $u->id],
                [
                    'lecturer_code' => $lData['code'],
                    'full_name' => $lData['name'],
                    'degree' => $lData['degree'],
                    'faculty_name' => $lData['faculty'] ?? 'Khoa Công nghệ Thông tin',
                    'phone' => $lData['phone'],
                    'email' => $lData['email'],
                ]
            );
        }
        $lecturer = $lecturerModels['GV001'];

        // 4. Sinh viên cốt lõi: Bùi Quốc Hùng - 2001230283
        $hungUser = User::updateOrCreate(
            ['email' => 'buiquochung0942@gmail.com'],
            [
                'username' => '2001230283',
                'name' => 'Bùi Quốc Hùng',
                'password' => Hash::make('12345678'),
                'role' => 'student',
                'class_id' => $class14DHTH04->id,
                'is_active' => true,
            ]
        );

        $hungStudent = Student::updateOrCreate(
            ['user_id' => $hungUser->id],
            [
                'student_code' => '2001230283',
                'full_name' => 'Bùi Quốc Hùng',
                'gender' => 'Nam',
                'avatar_url' => null,
                'study_status' => 'Đang học',
                'profile_code' => '2001230283',
                'enrollment_date' => '2023-08-25',
                'class_code' => '14DHTH04',
                'campus' => 'ĐHCT TP.HCM',
                'education_level' => 'Đại học',
                'training_type' => 'Chính quy',
                'faculty_name' => 'Khoa Công nghệ Thông tin',
                'major_name' => 'Công nghệ thông tin',
                'specialization_name' => 'Công nghệ phần mềm',
                'cohort_year' => 2023,
                'dob' => '2005-03-04',
                'ethnicity' => 'Kinh',
                'religion' => 'Không',
                'nationality' => 'Việt Nam',
                'region' => 'Khu vực 2',
                'cccd_number' => '095205003219',
                'cccd_issue_date' => '2021-08-27',
                'cccd_issue_place' => 'Cà Mau',
                'priority_object' => 'Không',
                'policy_area' => 'Không',
                'youth_union_date' => '2020-03-26',
                'party_date' => null,
                'phone' => '0942367010',
                'email' => 'buiquochung0942@gmail.com',
                'contact_address' => '140 Lê Trọng Tấn, P. Tây Thạnh, Q. Tân Phú, TP.HCM',
                'birth_place' => 'Cà Mau',
                'permanent_residence' => 'ấp 14 xóm lung, Xã Vĩnh Mỹ, Cà Mau',
            ]
        );

        // 5. Thêm các sinh viên phụ (sinhvien1 đến sinhvien5)
        $extraStudents = [
            ['name' => 'Nguyễn Minh Anh', 'email' => 'sinhvien1@gmail.com', 'code' => '2001230001', 'gender' => 'Nữ', 'drl' => 95, 'gpa' => 8.9],
            ['name' => 'Trần Hoàng Nam', 'email' => 'sinhvien2@gmail.com', 'code' => '2001230002', 'gender' => 'Nam', 'drl' => 85, 'gpa' => 8.2],
            ['name' => 'Lê Thu Hà', 'email' => 'sinhvien3@gmail.com', 'code' => '2001230003', 'gender' => 'Nữ', 'drl' => 75, 'gpa' => 7.4],
            ['name' => 'Phạm Quốc Bảo', 'email' => 'sinhvien4@gmail.com', 'code' => '2001230004', 'gender' => 'Nam', 'drl' => 65, 'gpa' => 7.8], // Trượt do ĐRL < 70
            ['name' => 'Võ Ngọc Linh', 'email' => 'sinhvien5@gmail.com', 'code' => '2001230005', 'gender' => 'Nữ', 'drl' => 88, 'gpa' => 6.2], // Trượt do GPA < 7.0
        ];

        $studentModels = [$hungStudent];

        foreach ($extraStudents as $idx => $extra) {
            $u = User::updateOrCreate(
                ['email' => $extra['email']],
                [
                    'username' => $extra['code'],
                    'name' => $extra['name'],
                    'password' => Hash::make('12345678'),
                    'role' => 'student',
                    'class_id' => $idx % 2 === 0 ? $class14DHTH04->id : $class14DHTH08->id,
                    'is_active' => true,
                ]
            );

            $s = Student::updateOrCreate(
                ['user_id' => $u->id],
                [
                    'student_code' => $extra['code'],
                    'full_name' => $extra['name'],
                    'gender' => $extra['gender'],
                    'study_status' => 'Đang học',
                    'class_code' => $idx % 2 === 0 ? '14DHTH04' : '14DHTH08',
                    'faculty_name' => 'Khoa Công nghệ Thông tin',
                    'major_name' => 'Công nghệ thông tin',
                    'specialization_name' => 'Công nghệ phần mềm',
                    'cohort_year' => 2023,
                    'phone' => '090123456' . $idx,
                    'email' => $extra['email'],
                    'permanent_residence' => 'TP. Hồ Chí Minh',
                ]
            );
            $studentModels[] = $s;
        }

        // 5.1. Danh mục Học kỳ (Semesters: 3 kỳ / năm, mở đăng ký trước 2 tháng)
        $semestersData = [
            [
                'semester_code' => 'HK1_2025_2026',
                'semester_name' => 'Học kỳ 1 (2025 - 2026)',
                'academic_year' => '2025-2026',
                'term_type' => 'TERM_1',
                'order_index' => 1,
                'start_date' => '2025-11-01',
                'end_date' => '2026-02-28',
                'registration_start_date' => '2025-09-01 00:00:00',
                'registration_end_date' => '2025-10-31 23:59:59',
                'is_registration_open' => false,
            ],
            [
                'semester_code' => 'HK2_2025_2026',
                'semester_name' => 'Học kỳ 2 (2025 - 2026)',
                'academic_year' => '2025-2026',
                'term_type' => 'TERM_2',
                'order_index' => 2,
                'start_date' => '2026-03-01',
                'end_date' => '2026-06-30',
                'registration_start_date' => '2026-01-01 00:00:00',
                'registration_end_date' => '2026-02-28 23:59:59',
                'is_registration_open' => false,
            ],
            [
                'semester_code' => 'HK_HE_2025_2026',
                'semester_name' => 'Học kỳ hè (2025 - 2026)',
                'academic_year' => '2025-2026',
                'term_type' => 'SUMMER',
                'order_index' => 3,
                'start_date' => '2026-07-01',
                'end_date' => '2026-08-31',
                'registration_start_date' => '2026-05-01 00:00:00',
                'registration_end_date' => '2026-06-30 23:59:59',
                'is_registration_open' => false,
            ],
            [
                'semester_code' => 'HK1_2026_2027',
                'semester_name' => 'Học kỳ 1 (2026 - 2027)',
                'academic_year' => '2026-2027',
                'term_type' => 'TERM_1',
                'order_index' => 4,
                'start_date' => '2026-11-01',
                'end_date' => '2027-02-28',
                'registration_start_date' => '2026-09-01 00:00:00', // Trước 2 tháng
                'registration_end_date' => '2026-10-31 23:59:59',
                'is_registration_open' => true, // Đang mở
            ],
            [
                'semester_code' => 'HK2_2026_2027',
                'semester_name' => 'Học kỳ 2 (2026 - 2027)',
                'academic_year' => '2026-2027',
                'term_type' => 'TERM_2',
                'order_index' => 5,
                'start_date' => '2027-03-01',
                'end_date' => '2027-06-30',
                'registration_start_date' => '2027-01-01 00:00:00',
                'registration_end_date' => '2027-02-28 23:59:59',
                'is_registration_open' => true,
            ],
            [
                'semester_code' => 'HK_HE_2026_2027',
                'semester_name' => 'Học kỳ hè (2026 - 2027)',
                'academic_year' => '2026-2027',
                'term_type' => 'SUMMER',
                'order_index' => 6,
                'start_date' => '2027-07-01',
                'end_date' => '2027-08-31',
                'registration_start_date' => '2027-05-01 00:00:00',
                'registration_end_date' => '2027-06-30 23:59:59',
                'is_registration_open' => true,
            ],
        ];

        foreach ($semestersData as $sem) {
            Semester::updateOrCreate(['semester_code' => $sem['semester_code']], $sem);
        }

        // 6. Danh mục môn học chi tiết (Courses & Subjects)
        $coursesData = [
            ['course_code' => 'OPEN101', 'course_name' => 'Lập trình mã nguồn mở', 'credits' => 3, 'tuition_fee_per_credit' => 550000],
            ['course_code' => 'IOT101', 'course_name' => 'Internet of Things', 'credits' => 3, 'tuition_fee_per_credit' => 550000],
            ['course_code' => 'NOSQL101', 'course_name' => 'Dữ liệu NoSQL', 'credits' => 3, 'tuition_fee_per_credit' => 550000],
            ['course_code' => 'WEB101', 'course_name' => 'Lập trình Web', 'credits' => 3, 'tuition_fee_per_credit' => 550000],
            ['course_code' => 'DB101', 'course_name' => 'Cơ sở dữ liệu', 'credits' => 3, 'tuition_fee_per_credit' => 550000],
            ['course_code' => 'NET101', 'course_name' => 'Mạng máy tính', 'credits' => 2, 'tuition_fee_per_credit' => 550000],
            ['course_code' => 'DS101', 'course_name' => 'Cấu trúc dữ liệu và giải thuật', 'credits' => 4, 'tuition_fee_per_credit' => 550000],
            ['course_code' => 'SE101', 'course_name' => 'Nhập môn Công nghệ phần mềm', 'credits' => 3, 'tuition_fee_per_credit' => 550000],
            ['course_code' => 'AI101', 'course_name' => 'Trí tuệ nhân tạo', 'credits' => 3, 'tuition_fee_per_credit' => 550000],
            ['course_code' => 'MOB101', 'course_name' => 'Lập trình thiết bị di động', 'credits' => 3, 'tuition_fee_per_credit' => 550000],
            ['course_code' => 'SEC101', 'course_name' => 'An toàn thông tin', 'credits' => 3, 'tuition_fee_per_credit' => 550000],
            ['course_code' => 'GRAD101', 'course_name' => 'Khóa luận tốt nghiệp', 'credits' => 10, 'tuition_fee_per_credit' => 550000],
            ['course_code' => 'ENG101', 'course_name' => 'Tiếng Anh chuyên ngành CNTT', 'credits' => 3, 'tuition_fee_per_credit' => 550000],
            ['course_code' => 'LAW101', 'course_name' => 'Pháp luật đại cương & Luật CNTT', 'credits' => 2, 'tuition_fee_per_credit' => 550000],
            ['course_code' => 'MKT101', 'course_name' => 'Marketing căn bản & Digital Marketing', 'credits' => 3, 'tuition_fee_per_credit' => 550000],
            ['course_code' => 'MATH101', 'course_name' => 'Toán rời rạc & Đại số tuyến tính', 'credits' => 3, 'tuition_fee_per_credit' => 550000],
        ];

        $courses = collect();
        foreach ($coursesData as $c) {
            $course = Course::updateOrCreate(['course_code' => $c['course_code']], $c);
            $courses->push($course);

            // Đồng bộ sang bảng subjects để giữ tương thích với test cũ
            Subject::updateOrCreate(
                ['code' => $c['course_code']],
                ['name' => $c['course_name'], 'credits' => $c['credits']]
            );
        }

        // 7. Chương trình khung (Curriculum Framework: Kỳ 1 -> Kỳ 8)
        $curriculumMap = [
            1 => ['DS101' => true, 'SE101' => true, 'MATH101' => true, 'ENG101' => false],
            2 => ['DB101' => true, 'NET101' => true, 'LAW101' => false],
            3 => ['WEB101' => true, 'NOSQL101' => false, 'MKT101' => false],
            4 => ['OPEN101' => true, 'IOT101' => false, 'ENG101' => true],
            5 => ['AI101' => true, 'MOB101' => false],
            6 => ['SEC101' => true],
            7 => [],
            8 => ['GRAD101' => true],
        ];

        foreach ($curriculumMap as $sem => $subMap) {
            foreach ($subMap as $code => $isMandatory) {
                $c = $courses->firstWhere('course_code', $code);
                $cnpmSpec = \App\Models\Specialization::where('name', 'Công nghệ phần mềm')->first();
                $specId = $cnpmSpec?->id ?? 1;
                if ($c) {
                    CurriculumFramework::updateOrCreate(
                        ['specialization_id' => $specId, 'semester_order' => $sem, 'course_id' => $c->id],
                        ['specialization_name' => 'Công nghệ phần mềm', 'is_mandatory' => $isMandatory]
                    );
                }
            }
        }

        // 8. Lớp học phần mở trong kỳ HK1_2026_2027 và các kỳ quá khứ (Mỗi môn có tối đa 3 option thời gian)
        $sectionsData = [
            // OPEN101 - 3 Option thời gian
            [
                'section_code' => '010110197801',
                'course_code' => 'OPEN101',
                'lecturer_code' => 'GV001',
                'semester_code' => 'HK1_2026_2027',
                'specialization_name' => 'Công nghệ phần mềm',
                'day_of_week' => 2, // Thứ 2
                'start_period' => 1,
                'end_period' => 3,
                'room_name' => 'B201 - Cơ sở 140 Lê Trọng Tấn',
                'exam_date' => '2026-11-20 07:30:00',
                'exam_room' => 'B201',
            ],
            [
                'section_code' => '010110197811',
                'course_code' => 'OPEN101',
                'lecturer_code' => 'GV002',
                'semester_code' => 'HK1_2026_2027',
                'specialization_name' => 'Công nghệ phần mềm',
                'day_of_week' => 4, // Thứ 4
                'start_period' => 7,
                'end_period' => 9,
                'room_name' => 'A110 - Phòng máy tính',
                'exam_date' => '2026-11-20 13:30:00',
                'exam_room' => 'A110',
            ],
            [
                'section_code' => '010110197821',
                'course_code' => 'OPEN101',
                'lecturer_code' => 'GV010',
                'semester_code' => 'HK1_2026_2027',
                'specialization_name' => 'Công nghệ phần mềm',
                'day_of_week' => 6, // Thứ 6
                'start_period' => 4,
                'end_period' => 6,
                'room_name' => 'B202 - Cơ sở 140 Lê Trọng Tấn',
                'exam_date' => '2026-11-20 13:30:00',
                'exam_room' => 'B202',
            ],

            // IOT101 - 3 Option thời gian
            [
                'section_code' => '010110197802',
                'course_code' => 'IOT101',
                'lecturer_code' => 'GV003',
                'semester_code' => 'HK1_2026_2027',
                'specialization_name' => 'Công nghệ phần mềm',
                'day_of_week' => 3, // Thứ 3
                'start_period' => 7,
                'end_period' => 11,
                'room_name' => 'A110 - Phòng máy tính - 140 Lê Trọng Tấn',
                'exam_date' => '2026-11-21 13:30:00',
                'exam_room' => 'A110',
            ],
            [
                'section_code' => '010110197812',
                'course_code' => 'IOT101',
                'lecturer_code' => 'GV004',
                'semester_code' => 'HK1_2026_2027',
                'specialization_name' => 'Công nghệ phần mềm',
                'day_of_week' => 5, // Thứ 5
                'start_period' => 1,
                'end_period' => 5,
                'room_name' => 'Lab 2 - Cơ sở 140 Lê Trọng Tấn',
                'exam_date' => '2026-11-21 07:30:00',
                'exam_room' => 'Lab 2',
            ],
            [
                'section_code' => '010110197822',
                'course_code' => 'IOT101',
                'lecturer_code' => 'GV009',
                'semester_code' => 'HK1_2026_2027',
                'specialization_name' => 'Công nghệ phần mềm',
                'day_of_week' => 7, // Thứ 7
                'start_period' => 1,
                'end_period' => 5,
                'room_name' => 'Lab 1 - Cơ sở 140 Lê Trọng Tấn',
                'exam_date' => '2026-11-21 07:30:00',
                'exam_room' => 'Lab 1',
            ],

            // NOSQL101 - 3 Option thời gian
            [
                'section_code' => '010110197803',
                'course_code' => 'NOSQL101',
                'lecturer_code' => 'GV006',
                'semester_code' => 'HK1_2026_2027',
                'specialization_name' => 'Công nghệ phần mềm',
                'day_of_week' => 4, // Thứ 4
                'start_period' => 1,
                'end_period' => 3,
                'room_name' => 'Trực tuyến (Google Meet)',
                'exam_date' => '2026-11-22 07:30:00',
                'exam_room' => 'Online',
            ],
            [
                'section_code' => '010110197813',
                'course_code' => 'NOSQL101',
                'lecturer_code' => 'GV005',
                'semester_code' => 'HK1_2026_2027',
                'specialization_name' => 'Công nghệ phần mềm',
                'day_of_week' => 6, // Thứ 6
                'start_period' => 7,
                'end_period' => 9,
                'room_name' => 'B202 - Cơ sở 140 Lê Trọng Tấn',
                'exam_date' => '2026-11-22 13:30:00',
                'exam_room' => 'B202',
            ],
            [
                'section_code' => '010110197823',
                'course_code' => 'NOSQL101',
                'lecturer_code' => 'GV007',
                'semester_code' => 'HK1_2026_2027',
                'specialization_name' => 'Công nghệ phần mềm',
                'day_of_week' => 2, // Thứ 2
                'start_period' => 7,
                'end_period' => 9,
                'room_name' => 'B204 - Cơ sở 140 Lê Trọng Tấn',
                'exam_date' => '2026-11-22 13:30:00',
                'exam_room' => 'B204',
            ],

            // WEB101 - 3 Option thời gian
            [
                'section_code' => '010110197804',
                'course_code' => 'WEB101',
                'lecturer_code' => 'GV001',
                'semester_code' => 'HK1_2026_2027',
                'specialization_name' => 'Công nghệ phần mềm',
                'day_of_week' => 4, // Thứ 4
                'start_period' => 4,
                'end_period' => 6,
                'room_name' => 'A203 - Cơ sở 140 Lê Trọng Tấn',
                'exam_date' => '2026-11-23 07:30:00',
                'exam_room' => 'A203',
            ],
            [
                'section_code' => '010110197814',
                'course_code' => 'WEB101',
                'lecturer_code' => 'GV002',
                'semester_code' => 'HK1_2026_2027',
                'specialization_name' => 'Công nghệ phần mềm',
                'day_of_week' => 3, // Thứ 3
                'start_period' => 1,
                'end_period' => 3,
                'room_name' => 'B101 - Cơ sở 140 Lê Trọng Tấn',
                'exam_date' => '2026-11-23 07:30:00',
                'exam_room' => 'B101',
            ],
            [
                'section_code' => '010110197824',
                'course_code' => 'WEB101',
                'lecturer_code' => 'GV008',
                'semester_code' => 'HK1_2026_2027',
                'specialization_name' => 'Công nghệ phần mềm',
                'day_of_week' => 5, // Thứ 5
                'start_period' => 4,
                'end_period' => 6,
                'room_name' => 'C201 - Cơ sở 140 Lê Trọng Tấn',
                'exam_date' => '2026-11-23 13:30:00',
                'exam_room' => 'C201',
            ],

            // NET101 - 3 Option thời gian (Học cải thiện)
            [
                'section_code' => '010110197805',
                'course_code' => 'NET101',
                'lecturer_code' => 'GV002',
                'semester_code' => 'HK1_2026_2027',
                'specialization_name' => 'Công nghệ phần mềm',
                'day_of_week' => 5, // Thứ 5
                'start_period' => 7,
                'end_period' => 9,
                'room_name' => 'B102 - Cơ sở 140 Lê Trọng Tấn',
                'exam_date' => '2026-11-24 13:30:00',
                'exam_room' => 'B102',
            ],
            [
                'section_code' => '010110197815',
                'course_code' => 'NET101',
                'lecturer_code' => 'GV007',
                'semester_code' => 'HK1_2026_2027',
                'specialization_name' => 'Công nghệ phần mềm',
                'day_of_week' => 2, // Thứ 2
                'start_period' => 4,
                'end_period' => 6,
                'room_name' => 'B104 - Cơ sở 140 Lê Trọng Tấn',
                'exam_date' => '2026-11-24 13:30:00',
                'exam_room' => 'B104',
            ],
            [
                'section_code' => '010110197825',
                'course_code' => 'NET101',
                'lecturer_code' => 'GV008',
                'semester_code' => 'HK1_2026_2027',
                'specialization_name' => 'Công nghệ phần mềm',
                'day_of_week' => 6, // Thứ 6
                'start_period' => 4,
                'end_period' => 6,
                'room_name' => 'C103 - Cơ sở 140 Lê Trọng Tấn',
                'exam_date' => '2026-11-24 07:30:00',
                'exam_room' => 'C103',
            ],

            // DB101
            [
                'section_code' => '010110197806',
                'course_code' => 'DB101',
                'lecturer_code' => 'GV006',
                'semester_code' => 'HK1_2026_2027',
                'specialization_name' => 'Công nghệ phần mềm',
                'day_of_week' => 7, // Thứ 7
                'start_period' => 7,
                'end_period' => 9,
                'room_name' => 'C302 - Cơ sở 140 Lê Trọng Tấn',
                'exam_date' => '2026-11-25 13:30:00',
                'exam_room' => 'C302',
            ],

            // DS101 - 3 Option thời gian (Học lại)
            [
                'section_code' => '010110197807',
                'course_code' => 'DS101',
                'lecturer_code' => 'GV004',
                'semester_code' => 'HK1_2026_2027',
                'specialization_name' => 'Công nghệ phần mềm',
                'day_of_week' => 6, // Thứ 6
                'start_period' => 1,
                'end_period' => 4,
                'room_name' => 'C201 - Cơ sở 140 Lê Trọng Tấn',
                'exam_date' => '2026-11-26 07:30:00',
                'exam_room' => 'C201',
            ],
            [
                'section_code' => '010110197817',
                'course_code' => 'DS101',
                'lecturer_code' => 'GV006',
                'semester_code' => 'HK1_2026_2027',
                'specialization_name' => 'Công nghệ phần mềm',
                'day_of_week' => 3, // Thứ 3
                'start_period' => 7,
                'end_period' => 10,
                'room_name' => 'B203 - Cơ sở 140 Lê Trọng Tấn',
                'exam_date' => '2026-11-26 13:30:00',
                'exam_room' => 'B203',
            ],
            [
                'section_code' => '010110197827',
                'course_code' => 'DS101',
                'lecturer_code' => 'GV005',
                'semester_code' => 'HK1_2026_2027',
                'specialization_name' => 'Công nghệ phần mềm',
                'day_of_week' => 7, // Thứ 7
                'start_period' => 1,
                'end_period' => 4,
                'room_name' => 'A102 - Cơ sở 140 Lê Trọng Tấn',
                'exam_date' => '2026-11-26 07:30:00',
                'exam_room' => 'A102',
            ],

            // AI101 - 3 Option thời gian (Học mới)
            [
                'section_code' => '010110197808',
                'course_code' => 'AI101',
                'lecturer_code' => 'GV005',
                'semester_code' => 'HK1_2026_2027',
                'specialization_name' => 'Công nghệ phần mềm',
                'day_of_week' => 6, // Thứ 6
                'start_period' => 7,
                'end_period' => 9,
                'room_name' => 'B203 - Cơ sở 140 Lê Trọng Tấn',
                'exam_date' => '2026-11-27 13:30:00',
                'exam_room' => 'B203',
            ],
            [
                'section_code' => '010110197818',
                'course_code' => 'AI101',
                'lecturer_code' => 'GV006',
                'semester_code' => 'HK1_2026_2027',
                'specialization_name' => 'Công nghệ phần mềm',
                'day_of_week' => 3, // Thứ 3
                'start_period' => 4,
                'end_period' => 6,
                'room_name' => 'A202 - Cơ sở 140 Lê Trọng Tấn',
                'exam_date' => '2026-11-27 07:30:00',
                'exam_room' => 'A202',
            ],
            [
                'section_code' => '010110197828',
                'course_code' => 'AI101',
                'lecturer_code' => 'GV001',
                'semester_code' => 'HK1_2026_2027',
                'specialization_name' => 'Công nghệ phần mềm',
                'day_of_week' => 7, // Thứ 7
                'start_period' => 4,
                'end_period' => 6,
                'room_name' => 'B205 - Cơ sở 140 Lê Trọng Tấn',
                'exam_date' => '2026-11-27 13:30:00',
                'exam_room' => 'B205',
            ],

            // ENG101 - 3 Option thời gian trong HK1_2026_2027 (Khoa Ngoại ngữ)
            [
                'section_code' => '010110197809',
                'course_code' => 'ENG101',
                'lecturer_code' => 'GV_NN01',
                'semester_code' => 'HK1_2026_2027',
                'specialization_name' => 'Công nghệ phần mềm',
                'day_of_week' => 2, // Thứ 2
                'start_period' => 7,
                'end_period' => 9,
                'room_name' => 'D201 - Cơ sở 140 Lê Trọng Tấn',
                'exam_date' => '2026-11-28 13:30:00',
                'exam_room' => 'D201',
            ],
            [
                'section_code' => '010110197819',
                'course_code' => 'ENG101',
                'lecturer_code' => 'GV_NN02',
                'semester_code' => 'HK1_2026_2027',
                'specialization_name' => 'Công nghệ phần mềm',
                'day_of_week' => 4, // Thứ 4
                'start_period' => 1,
                'end_period' => 3,
                'room_name' => 'D202 - Cơ sở 140 Lê Trọng Tấn',
                'exam_date' => '2026-11-28 07:30:00',
                'exam_room' => 'D202',
            ],
            [
                'section_code' => '010110197829',
                'course_code' => 'ENG101',
                'lecturer_code' => 'GV_NN03',
                'semester_code' => 'HK1_2026_2027',
                'specialization_name' => 'Công nghệ phần mềm',
                'day_of_week' => 6, // Thứ 6
                'start_period' => 4,
                'end_period' => 6,
                'room_name' => 'D203 - Cơ sở 140 Lê Trọng Tấn',
                'exam_date' => '2026-11-28 13:30:00',
                'exam_room' => 'D203',
            ],

            // LAW101 - 3 Option thời gian trong HK1_2026_2027 (Khoa Luật & Quản trị)
            [
                'section_code' => '010110197810',
                'course_code' => 'LAW101',
                'lecturer_code' => 'GV_LUAT01',
                'semester_code' => 'HK1_2026_2027',
                'specialization_name' => 'Công nghệ phần mềm',
                'day_of_week' => 3, // Thứ 3
                'start_period' => 7,
                'end_period' => 9,
                'room_name' => 'A301 - Cơ sở 140 Lê Trọng Tấn',
                'exam_date' => '2026-11-29 13:30:00',
                'exam_room' => 'A301',
            ],
            [
                'section_code' => '010110197820',
                'course_code' => 'LAW101',
                'lecturer_code' => 'GV_QTR01',
                'semester_code' => 'HK1_2026_2027',
                'specialization_name' => 'Công nghệ phần mềm',
                'day_of_week' => 5, // Thứ 5
                'start_period' => 1,
                'end_period' => 3,
                'room_name' => 'A302 - Cơ sở 140 Lê Trọng Tấn',
                'exam_date' => '2026-11-29 07:30:00',
                'exam_room' => 'A302',
            ],
            [
                'section_code' => '010110197830',
                'course_code' => 'LAW101',
                'lecturer_code' => 'GV_QTR03',
                'semester_code' => 'HK1_2026_2027',
                'specialization_name' => 'Công nghệ phần mềm',
                'day_of_week' => 7, // Thứ 7
                'start_period' => 7,
                'end_period' => 9,
                'room_name' => 'A303 - Cơ sở 140 Lê Trọng Tấn',
                'exam_date' => '2026-11-29 13:30:00',
                'exam_room' => 'A303',
            ],

            // Lớp quá khứ HK1_2025_2026 (Nhiều môn & 3 Option thời gian)
            // DS101
            [
                'section_code' => '010110192501',
                'course_code' => 'DS101',
                'lecturer_code' => 'GV004',
                'semester_code' => 'HK1_2025_2026',
                'specialization_name' => 'Công nghệ phần mềm',
                'day_of_week' => 2,
                'start_period' => 1,
                'end_period' => 4,
                'room_name' => 'A101',
                'exam_date' => '2026-01-15 07:30:00',
                'exam_room' => 'A101',
            ],
            [
                'section_code' => '010110192511',
                'course_code' => 'DS101',
                'lecturer_code' => 'GV_KH01',
                'semester_code' => 'HK1_2025_2026',
                'specialization_name' => 'Công nghệ phần mềm',
                'day_of_week' => 4,
                'start_period' => 7,
                'end_period' => 10,
                'room_name' => 'A202',
                'exam_date' => '2026-01-15 13:30:00',
                'exam_room' => 'A202',
            ],
            [
                'section_code' => '010110192521',
                'course_code' => 'DS101',
                'lecturer_code' => 'GV005',
                'semester_code' => 'HK1_2025_2026',
                'specialization_name' => 'Công nghệ phần mềm',
                'day_of_week' => 6,
                'start_period' => 1,
                'end_period' => 4,
                'room_name' => 'B103',
                'exam_date' => '2026-01-15 07:30:00',
                'exam_room' => 'B103',
            ],

            // SE101
            [
                'section_code' => '010110192502',
                'course_code' => 'SE101',
                'lecturer_code' => 'GV009',
                'semester_code' => 'HK1_2025_2026',
                'specialization_name' => 'Công nghệ phần mềm',
                'day_of_week' => 3,
                'start_period' => 1,
                'end_period' => 3,
                'room_name' => 'B201',
                'exam_date' => '2026-01-16 07:30:00',
                'exam_room' => 'B201',
            ],
            [
                'section_code' => '010110192512',
                'course_code' => 'SE101',
                'lecturer_code' => 'GV002',
                'semester_code' => 'HK1_2025_2026',
                'specialization_name' => 'Công nghệ phần mềm',
                'day_of_week' => 5,
                'start_period' => 4,
                'end_period' => 6,
                'room_name' => 'B204',
                'exam_date' => '2026-01-16 13:30:00',
                'exam_room' => 'B204',
            ],
            [
                'section_code' => '010110192522',
                'course_code' => 'SE101',
                'lecturer_code' => 'GV010',
                'semester_code' => 'HK1_2025_2026',
                'specialization_name' => 'Công nghệ phần mềm',
                'day_of_week' => 7,
                'start_period' => 7,
                'end_period' => 9,
                'room_name' => 'B101',
                'exam_date' => '2026-01-16 13:30:00',
                'exam_room' => 'B101',
            ],

            // MATH101
            [
                'section_code' => '010110192506',
                'course_code' => 'MATH101',
                'lecturer_code' => 'GV_KH01',
                'semester_code' => 'HK1_2025_2026',
                'specialization_name' => 'Công nghệ phần mềm',
                'day_of_week' => 2,
                'start_period' => 7,
                'end_period' => 9,
                'room_name' => 'C101',
                'exam_date' => '2026-01-17 13:30:00',
                'exam_room' => 'C101',
            ],
            [
                'section_code' => '010110192516',
                'course_code' => 'MATH101',
                'lecturer_code' => 'GV_KH02',
                'semester_code' => 'HK1_2025_2026',
                'specialization_name' => 'Công nghệ phần mềm',
                'day_of_week' => 4,
                'start_period' => 1,
                'end_period' => 3,
                'room_name' => 'C102',
                'exam_date' => '2026-01-17 07:30:00',
                'exam_room' => 'C102',
            ],
            [
                'section_code' => '010110192526',
                'course_code' => 'MATH101',
                'lecturer_code' => 'GV004',
                'semester_code' => 'HK1_2025_2026',
                'specialization_name' => 'Công nghệ phần mềm',
                'day_of_week' => 6,
                'start_period' => 7,
                'end_period' => 9,
                'room_name' => 'C103',
                'exam_date' => '2026-01-17 13:30:00',
                'exam_room' => 'C103',
            ],

            // ENG101
            [
                'section_code' => '010110192507',
                'course_code' => 'ENG101',
                'lecturer_code' => 'GV_NN01',
                'semester_code' => 'HK1_2025_2026',
                'specialization_name' => 'Công nghệ phần mềm',
                'day_of_week' => 3,
                'start_period' => 7,
                'end_period' => 9,
                'room_name' => 'D201',
                'exam_date' => '2026-01-18 13:30:00',
                'exam_room' => 'D201',
            ],
            [
                'section_code' => '010110192517',
                'course_code' => 'ENG101',
                'lecturer_code' => 'GV_NN02',
                'semester_code' => 'HK1_2025_2026',
                'specialization_name' => 'Công nghệ phần mềm',
                'day_of_week' => 5,
                'start_period' => 1,
                'end_period' => 3,
                'room_name' => 'D202',
                'exam_date' => '2026-01-18 07:30:00',
                'exam_room' => 'D202',
            ],
            [
                'section_code' => '010110192527',
                'course_code' => 'ENG101',
                'lecturer_code' => 'GV_NN03',
                'semester_code' => 'HK1_2025_2026',
                'specialization_name' => 'Công nghệ phần mềm',
                'day_of_week' => 7,
                'start_period' => 1,
                'end_period' => 3,
                'room_name' => 'D203',
                'exam_date' => '2026-01-18 07:30:00',
                'exam_room' => 'D203',
            ],

            // Lớp quá khứ HK2_2025_2026 (Nhiều môn & 3 Option thời gian)
            // NET101
            [
                'section_code' => '010110192503',
                'course_code' => 'NET101',
                'lecturer_code' => 'GV004',
                'semester_code' => 'HK2_2025_2026',
                'specialization_name' => 'Công nghệ phần mềm',
                'day_of_week' => 4,
                'start_period' => 7,
                'end_period' => 9,
                'room_name' => 'C301',
                'exam_date' => '2026-06-15 13:30:00',
                'exam_room' => 'C301',
            ],
            [
                'section_code' => '010110192513',
                'course_code' => 'NET101',
                'lecturer_code' => 'GV_DT01',
                'semester_code' => 'HK2_2025_2026',
                'specialization_name' => 'Công nghệ phần mềm',
                'day_of_week' => 2,
                'start_period' => 1,
                'end_period' => 3,
                'room_name' => 'C302',
                'exam_date' => '2026-06-15 07:30:00',
                'exam_room' => 'C302',
            ],
            [
                'section_code' => '010110192523',
                'course_code' => 'NET101',
                'lecturer_code' => 'GV_DT02',
                'semester_code' => 'HK2_2025_2026',
                'specialization_name' => 'Công nghệ phần mềm',
                'day_of_week' => 6,
                'start_period' => 4,
                'end_period' => 6,
                'room_name' => 'C303',
                'exam_date' => '2026-06-15 13:30:00',
                'exam_room' => 'C303',
            ],

            // DB101
            [
                'section_code' => '010110192504',
                'course_code' => 'DB101',
                'lecturer_code' => 'GV006',
                'semester_code' => 'HK2_2025_2026',
                'specialization_name' => 'Công nghệ phần mềm',
                'day_of_week' => 5,
                'start_period' => 1,
                'end_period' => 3,
                'room_name' => 'B101',
                'exam_date' => '2026-06-16 07:30:00',
                'exam_room' => 'B101',
            ],
            [
                'section_code' => '010110192514',
                'course_code' => 'DB101',
                'lecturer_code' => 'GV005',
                'semester_code' => 'HK2_2025_2026',
                'specialization_name' => 'Công nghệ phần mềm',
                'day_of_week' => 3,
                'start_period' => 7,
                'end_period' => 9,
                'room_name' => 'B102',
                'exam_date' => '2026-06-16 13:30:00',
                'exam_room' => 'B102',
            ],
            [
                'section_code' => '010110192524',
                'course_code' => 'DB101',
                'lecturer_code' => 'GV003',
                'semester_code' => 'HK2_2025_2026',
                'specialization_name' => 'Công nghệ phần mềm',
                'day_of_week' => 7,
                'start_period' => 4,
                'end_period' => 6,
                'room_name' => 'B103',
                'exam_date' => '2026-06-16 13:30:00',
                'exam_room' => 'B103',
            ],

            // LAW101
            [
                'section_code' => '010110192508',
                'course_code' => 'LAW101',
                'lecturer_code' => 'GV_LUAT01',
                'semester_code' => 'HK2_2025_2026',
                'specialization_name' => 'Công nghệ phần mềm',
                'day_of_week' => 2,
                'start_period' => 4,
                'end_period' => 6,
                'room_name' => 'A301',
                'exam_date' => '2026-06-17 13:30:00',
                'exam_room' => 'A301',
            ],
            [
                'section_code' => '010110192518',
                'course_code' => 'LAW101',
                'lecturer_code' => 'GV_QTR01',
                'semester_code' => 'HK2_2025_2026',
                'specialization_name' => 'Công nghệ phần mềm',
                'day_of_week' => 4,
                'start_period' => 1,
                'end_period' => 3,
                'room_name' => 'A302',
                'exam_date' => '2026-06-17 07:30:00',
                'exam_room' => 'A302',
            ],
            [
                'section_code' => '010110192528',
                'course_code' => 'LAW101',
                'lecturer_code' => 'GV_QTR03',
                'semester_code' => 'HK2_2025_2026',
                'specialization_name' => 'Công nghệ phần mềm',
                'day_of_week' => 6,
                'start_period' => 7,
                'end_period' => 9,
                'room_name' => 'A303',
                'exam_date' => '2026-06-17 13:30:00',
                'exam_room' => 'A303',
            ],

            // Lớp quá khứ HK_HE_2025_2026
            // MOB101
            [
                'section_code' => '010110192505',
                'course_code' => 'MOB101',
                'lecturer_code' => 'GV010',
                'semester_code' => 'HK_HE_2025_2026',
                'specialization_name' => 'Công nghệ phần mềm',
                'day_of_week' => 6,
                'start_period' => 1,
                'end_period' => 3,
                'room_name' => 'A110',
                'exam_date' => '2026-08-15 07:30:00',
                'exam_room' => 'A110',
            ],
            [
                'section_code' => '010110192515',
                'course_code' => 'MOB101',
                'lecturer_code' => 'GV001',
                'semester_code' => 'HK_HE_2025_2026',
                'specialization_name' => 'Công nghệ phần mềm',
                'day_of_week' => 3,
                'start_period' => 7,
                'end_period' => 9,
                'room_name' => 'A111',
                'exam_date' => '2026-08-15 13:30:00',
                'exam_room' => 'A111',
            ],
            [
                'section_code' => '010110192525',
                'course_code' => 'MOB101',
                'lecturer_code' => 'GV002',
                'semester_code' => 'HK_HE_2025_2026',
                'specialization_name' => 'Công nghệ phần mềm',
                'day_of_week' => 5,
                'start_period' => 1,
                'end_period' => 3,
                'room_name' => 'A112',
                'exam_date' => '2026-08-15 07:30:00',
                'exam_room' => 'A112',
            ],

            // MKT101
            [
                'section_code' => '010110192509',
                'course_code' => 'MKT101',
                'lecturer_code' => 'GV_QTR02',
                'semester_code' => 'HK_HE_2025_2026',
                'specialization_name' => 'Công nghệ phần mềm',
                'day_of_week' => 2,
                'start_period' => 7,
                'end_period' => 9,
                'room_name' => 'B301',
                'exam_date' => '2026-08-16 13:30:00',
                'exam_room' => 'B301',
            ],
            [
                'section_code' => '010110192519',
                'course_code' => 'MKT101',
                'lecturer_code' => 'GV_QTR03',
                'semester_code' => 'HK_HE_2025_2026',
                'specialization_name' => 'Công nghệ phần mềm',
                'day_of_week' => 4,
                'start_period' => 4,
                'end_period' => 6,
                'room_name' => 'B302',
                'exam_date' => '2026-08-16 13:30:00',
                'exam_room' => 'B302',
            ],
            [
                'section_code' => '010110192529',
                'course_code' => 'MKT101',
                'lecturer_code' => 'GV_TC02',
                'semester_code' => 'HK_HE_2025_2026',
                'specialization_name' => 'Công nghệ phần mềm',
                'day_of_week' => 6,
                'start_period' => 7,
                'end_period' => 9,
                'room_name' => 'B303',
                'exam_date' => '2026-08-16 13:30:00',
                'exam_room' => 'B303',
            ],
        ];

        $sections = collect();
        foreach ($sectionsData as $sec) {
            $course = $courses->firstWhere('course_code', $sec['course_code']);
            $lecId = isset($sec['lecturer_code']) && isset($lecturerModels[$sec['lecturer_code']])
                ? $lecturerModels[$sec['lecturer_code']]->id
                : $lecturer->id;

            $s = CourseSection::updateOrCreate(
                ['section_code' => $sec['section_code']],
                [
                    'course_id' => $course->id,
                    'lecturer_id' => $lecId,
                    'semester_code' => $sec['semester_code'],
                    'specialization_name' => $sec['specialization_name'] ?? 'Công nghệ phần mềm',
                    'max_capacity' => 60,
                    'day_of_week' => $sec['day_of_week'],
                    'start_period' => $sec['start_period'],
                    'end_period' => $sec['end_period'],
                    'room_name' => $sec['room_name'],
                    'exam_date' => $sec['exam_date'],
                    'exam_room' => $sec['exam_room'],
                ]
            );
            $sections->push($s);
        }

        // 9. Lập lịch học tuần chi tiết (Class Schedules) cho tuần hiện tại và các tuần lân cận
        // Tính toán thứ Hai của tuần hiện tại (2026-10-19 đến 2026-10-25 hoặc tuần hiện tại)
        $baseMonday = Carbon::now()->startOfWeek(Carbon::MONDAY);

        // Sinh lịch cho tuần hiện tại (-1 tuần, tuần này, +1 tuần)
        for ($weekOffset = -1; $weekOffset <= 1; $weekOffset++) {
            $monday = (clone $baseMonday)->addWeeks($weekOffset);

            // Thứ 2: Lý thuyết (THEORY)
            ClassSchedule::updateOrCreate(
                ['section_code' => '010110197801', 'schedule_date' => (clone $monday)->toDateString()],
                [
                    'section_id' => $sections[0]->id,
                    'course_id' => $sections[0]->course_id,
                    'lecturer_id' => $lecturer->id,
                    'class_group_name' => '14DHTH04',
                    'subject_name' => 'Lập trình mã nguồn mở',
                    'lecturer_name' => 'ThS. Nguyễn Văn A',
                    'shift' => 'MORNING',
                    'start_period' => 1,
                    'end_period' => 3,
                    'room_info' => 'B201 - Cơ sở 140 Lê Trọng Tấn',
                    'schedule_type' => 'THEORY',
                    'note' => null,
                ]
            );

            // Thứ 3: Thực hành (PRACTICE)
            ClassSchedule::updateOrCreate(
                ['section_code' => '010110197802', 'schedule_date' => (clone $monday)->addDays(1)->toDateString()],
                [
                    'section_id' => $sections[1]->id,
                    'course_id' => $sections[1]->course_id,
                    'lecturer_id' => $lecturer->id,
                    'class_group_name' => '14DHTH04',
                    'subject_name' => 'Internet of Things',
                    'lecturer_name' => 'ThS. Trần Thị B',
                    'shift' => 'AFTERNOON',
                    'start_period' => 7,
                    'end_period' => 11,
                    'room_info' => 'A110 - Phòng máy tính - 140 Lê Trọng Tấn',
                    'schedule_type' => 'PRACTICE',
                    'note' => null,
                ]
            );

            // Thứ 4 (Ca Sáng - Môn 1): Trực tuyến (ONLINE)
            ClassSchedule::updateOrCreate(
                ['section_code' => '010110197803', 'schedule_date' => (clone $monday)->addDays(2)->toDateString()],
                [
                    'section_id' => $sections[2]->id,
                    'course_id' => $sections[2]->course_id,
                    'lecturer_id' => $lecturer->id,
                    'class_group_name' => '14DHTH04',
                    'subject_name' => 'Dữ liệu NoSQL',
                    'lecturer_name' => 'ThS. Lê Văn C',
                    'shift' => 'MORNING',
                    'start_period' => 1,
                    'end_period' => 3,
                    'room_info' => 'Trực tuyến (Google Meet)',
                    'meeting_link' => 'https://meet.google.com/huit-cntt-online',
                    'schedule_type' => 'ONLINE',
                    'note' => 'Học trực tuyến qua Google Meet',
                ]
            );

            // Thứ 4 (Ca Sáng - Môn 2): Xếp chồng trong cùng ca sáng!
            ClassSchedule::updateOrCreate(
                ['section_code' => '010110197804', 'schedule_date' => (clone $monday)->addDays(2)->toDateString()],
                [
                    'section_id' => $sections[3]->id,
                    'course_id' => $sections[3]->course_id,
                    'lecturer_id' => $lecturer->id,
                    'class_group_name' => '14DHTH04',
                    'subject_name' => 'Lập trình Web',
                    'lecturer_name' => 'ThS. Nguyễn Văn A',
                    'shift' => 'MORNING',
                    'start_period' => 4,
                    'end_period' => 6,
                    'room_info' => 'A203 - Cơ sở 140 Lê Trọng Tấn',
                    'schedule_type' => 'THEORY',
                    'note' => null,
                ]
            );

            // Thứ 5: Tạm ngưng (SUSPENDED)
            ClassSchedule::updateOrCreate(
                ['section_code' => '010110197805', 'schedule_date' => (clone $monday)->addDays(3)->toDateString()],
                [
                    'section_id' => $sections[4]->id,
                    'course_id' => $sections[4]->course_id,
                    'lecturer_id' => $lecturer->id,
                    'class_group_name' => '14DHTH04',
                    'subject_name' => 'Mạng máy tính',
                    'lecturer_name' => 'TS. Phạm Hoàng Nam',
                    'shift' => 'AFTERNOON',
                    'start_period' => 7,
                    'end_period' => 9,
                    'room_info' => 'B102 - Cơ sở 140 Lê Trọng Tấn',
                    'schedule_type' => 'SUSPENDED',
                    'note' => 'GV bận công tác đột xuất, sẽ xếp lịch học bù sau.',
                ]
            );

            // Thứ 7: Lịch thi (EXAM)
            ClassSchedule::updateOrCreate(
                ['section_code' => '010110197806', 'schedule_date' => (clone $monday)->addDays(5)->toDateString()],
                [
                    'section_id' => $sections[5]->id,
                    'course_id' => $sections[5]->course_id,
                    'lecturer_id' => null,
                    'class_group_name' => '14DHTH04',
                    'subject_name' => 'Thi kết thúc môn: Cơ sở dữ liệu',
                    'lecturer_name' => 'Ban Khảo thí HUIT',
                    'shift' => 'AFTERNOON',
                    'start_period' => 7,
                    'end_period' => 9,
                    'room_info' => 'C302 - Cơ sở 140 Lê Trọng Tấn',
                    'schedule_type' => 'EXAM',
                    'note' => 'Sinh viên mang theo thẻ sinh viên và CCCD gốc.',
                ]
            );
        }

        // 10. Đăng ký môn & Điểm cho Bùi Quốc Hùng (Bao gồm lịch sử Năm 1 và Kỳ hiện tại)
        $hungGrades = [
            // Lịch sử Năm 1 - HK1 (2025 - 2026)
            ['section_code' => '010110192501', 'mid' => 3.0, 'fin' => 3.5, 'type' => 'FIRST_TIME'], // DS101 (Cấu trúc dữ liệu) -> 3.35 (Điểm F - RỚT để test Học lại)
            ['section_code' => '010110192502', 'mid' => 8.0, 'fin' => 8.0, 'type' => 'FIRST_TIME'], // SE101 (Nhập môn CNPM) -> 8.00 (B+ - ĐẠT)
            ['section_code' => '010110192507', 'mid' => 8.5, 'fin' => 9.0, 'type' => 'FIRST_TIME'], // ENG101 (Tiếng Anh chuyên ngành) -> 8.85 (A - ĐẠT)

            // Lịch sử Năm 1 - HK2 (2025 - 2026)
            ['section_code' => '010110192503', 'mid' => 5.5, 'fin' => 5.5, 'type' => 'FIRST_TIME'], // NET101 (Mạng máy tính) -> 5.50 (Điểm C - ĐẠT để test Học cải thiện)
            ['section_code' => '010110192504', 'mid' => 7.5, 'fin' => 7.5, 'type' => 'FIRST_TIME'], // DB101 (Cơ sở dữ liệu) -> 7.50 (Điểm B - ĐẠT)
            ['section_code' => '010110192508', 'mid' => 8.0, 'fin' => 8.0, 'type' => 'FIRST_TIME'], // LAW101 (Pháp luật đại cương) -> 8.00 (B+ - ĐẠT)

            // Lịch sử Năm 1 - Học kỳ hè (2025 - 2026)
            ['section_code' => '010110192505', 'mid' => 8.0, 'fin' => 8.0, 'type' => 'FIRST_TIME'], // MOB101 -> 8.00 (Điểm B+ - ĐẠT)

            // Kỳ hiện tại HK1_2026_2027 (Đã đăng ký 2 môn ban đầu, còn lại mở cho sinh viên đăng ký thêm)
            ['section_code' => '010110197801', 'mid' => 8.5, 'fin' => 9.0, 'type' => 'FIRST_TIME'], // Open Source -> 8.85 (A)
            ['section_code' => '010110197802', 'mid' => 8.0, 'fin' => 8.5, 'type' => 'FIRST_TIME'], // IoT -> 8.35 (B+)
        ];

        foreach ($hungGrades as $g) {
            $sec = $sections->firstWhere('section_code', $g['section_code']);
            if (!$sec) continue;

            $total10 = round($g['mid'] * 0.3 + $g['fin'] * 0.7, 2);
            $total4 = match (true) {
                $total10 >= 8.5 => 4.0,
                $total10 >= 8.0 => 3.5,
                $total10 >= 7.0 => 3.0,
                $total10 >= 6.5 => 2.5,
                $total10 >= 5.5 => 2.0,
                $total10 >= 5.0 => 1.5,
                $total10 >= 4.0 => 1.0,
                default => 0.0,
            };
            $letter = match (true) {
                $total10 >= 8.5 => 'A',
                $total10 >= 8.0 => 'B+',
                $total10 >= 7.0 => 'B',
                $total10 >= 6.5 => 'C+',
                $total10 >= 5.5 => 'C',
                $total10 >= 5.0 => 'D+',
                $total10 >= 4.0 => 'D',
                default => 'F',
            };

            Enrollment::updateOrCreate(
                ['student_id' => $hungStudent->id, 'section_id' => $sec->id],
                [
                    'registration_type' => $g['type'] ?? 'FIRST_TIME',
                    'midterm_score' => $g['mid'],
                    'final_score' => $g['fin'],
                    'total_score_10' => $total10,
                    'total_score_4' => $total4,
                    'letter_grade' => $letter,
                ]
            );

            // Đồng bộ sang bảng grades và student_subject cho các test cũ
            $subj = Subject::where('code', $sec->course->course_code)->first();
            if ($subj) {
                $hungUser->subjects()->syncWithoutDetaching([$subj->id]);
                Grade::updateOrCreate(
                    ['user_id' => $hungUser->id, 'subject_id' => $subj->id],
                    ['score' => $total10]
                );
            }
        }

        // 11. Đánh giá học kỳ & Xét Học bổng cho Bùi Quốc Hùng
        SemesterEvaluation::updateOrCreate(
            ['student_id' => $hungStudent->id, 'semester_code' => 'HK1_2026_2027'],
            [
                'total_credits' => 15,
                'min_course_score' => 8.00,
                'training_score' => 92,
                'semester_gpa_10' => 8.57,
                'semester_gpa_4' => 3.75,
                'is_eligible' => true,
                'ineligible_reason' => null,
                'scholarship_type' => '80%',
                'scholarship_score' => 87.59,
                'scholarship_status' => 'PUBLISHED',
            ]
        );

        // 12. Hóa đơn học phí cho các học kỳ của Bùi Quốc Hùng
        TuitionInvoice::updateOrCreate(
            ['student_id' => $hungStudent->id, 'semester_code' => 'HK1_2025_2026'],
            [
                'total_credits' => 10,
                'total_amount' => 5500000,
                'paid_amount' => 5500000,
                'status' => 'PAID',
                'payment_code' => 'HUIT 2001230283 HK12526',
                'paid_at' => Carbon::parse('2025-10-15 10:00:00'),
            ]
        );

        TuitionInvoice::updateOrCreate(
            ['student_id' => $hungStudent->id, 'semester_code' => 'HK2_2025_2026'],
            [
                'total_credits' => 7,
                'total_amount' => 3850000,
                'paid_amount' => 3850000,
                'status' => 'PAID',
                'payment_code' => 'HUIT 2001230283 HK22526',
                'paid_at' => Carbon::parse('2026-02-15 10:00:00'),
            ]
        );

        TuitionInvoice::updateOrCreate(
            ['student_id' => $hungStudent->id, 'semester_code' => 'HK_HE_2025_2026'],
            [
                'total_credits' => 3,
                'total_amount' => 1650000,
                'paid_amount' => 1650000,
                'status' => 'PAID',
                'payment_code' => 'HUIT 2001230283 HKHE2526',
                'paid_at' => Carbon::parse('2026-06-15 10:00:00'),
            ]
        );

        TuitionInvoice::updateOrCreate(
            ['student_id' => $hungStudent->id, 'semester_code' => 'HK1_2026_2027'],
            [
                'total_credits' => 6,
                'total_amount' => 3300000,
                'paid_amount' => 0,
                'status' => 'UNPAID',
                'payment_code' => 'HUIT 2001230283 HK12627',
                'paid_at' => null,
            ]
        );

        // 13. Đăng ký môn và đánh giá học kỳ cho các sinh viên còn lại
        foreach ($studentModels as $index => $stu) {
            if ($stu->id === $hungStudent->id) continue;

            // Phân bổ lớp học phần cho sinh viên phụ qua tất cả các học kỳ
            $extraSecCodes = [
                // HK1_2025_2026
                '010110192501', '010110192502', '010110192507',
                // HK2_2025_2026
                '010110192503', '010110192504', '010110192508',
                // HK_HE_2025_2026
                '010110192505',
                // HK1_2026_2027
                '010110197801', '010110197802',
            ];

            $assignedSec = $sections->whereIn('section_code', $extraSecCodes);
            $scores = [];
            $sem1Credits = 0;

            foreach ($assignedSec as $sec) {
                if ($sec->semester_code === 'HK1_2026_2027') {
                    $sem1Credits += $sec->course->credits;
                }

                $score10 = match ($index) {
                    1 => 8.8,
                    2 => 8.2,
                    3 => ($sec->course->course_code === 'DS101' ? 3.5 : 7.6), // sinhvien3 rớt DS101 để test Học lại
                    4 => 7.8,
                    default => ($sec->course->course_code === 'NET101' ? 5.5 : 7.2), // sinhvien5 điểm C môn NET101 để test Học cải thiện
                };
                $scores[] = $score10;

                $letter = match (true) {
                    $score10 >= 8.5 => 'A',
                    $score10 >= 8.0 => 'B+',
                    $score10 >= 7.0 => 'B',
                    $score10 >= 6.5 => 'C+',
                    $score10 >= 5.5 => 'C',
                    $score10 >= 5.0 => 'D+',
                    $score10 >= 4.0 => 'D',
                    default => 'F',
                };

                Enrollment::updateOrCreate(
                    ['student_id' => $stu->id, 'section_id' => $sec->id],
                    [
                        'registration_type' => 'FIRST_TIME',
                        'midterm_score' => $score10,
                        'final_score' => $score10,
                        'total_score_10' => $score10,
                        'total_score_4' => $score10 >= 8.5 ? 4.0 : ($score10 >= 7.0 ? 3.0 : ($score10 >= 5.5 ? 2.0 : 0.0)),
                        'letter_grade' => $letter,
                    ]
                );

                $subj = Subject::where('code', $sec->course->course_code)->first();
                if ($subj) {
                    $stu->user->subjects()->syncWithoutDetaching([$subj->id]);
                    Grade::updateOrCreate(
                        ['user_id' => $stu->user_id, 'subject_id' => $subj->id],
                        ['score' => $score10]
                    );
                }
            }

            $minScore = min($scores);
            $gpa10 = round(array_sum($scores) / count($scores), 2);
            $drl = match ($index) {
                1 => 95,
                2 => 85,
                3 => 75,
                4 => 65, // ĐRL thấp
                default => 88,
            };

            $totalAllCredits = $assignedSec->sum(fn($s) => $s->course->credits);
            $isEligible = ($totalAllCredits >= 15 && $minScore >= 6.5 && $gpa10 >= 7.0 && $drl >= 70);
            $ineligibleReason = null;
            if (!$isEligible) {
                if ($minScore < 6.5) $ineligibleReason = "Có môn đạt điểm {$minScore} (< 6.5)";
                elseif ($drl < 70) $ineligibleReason = "Điểm rèn luyện đạt {$drl} (< 70)";
                elseif ($gpa10 < 7.0) $ineligibleReason = "GPA đạt {$gpa10} (< 7.0)";
                elseif ($totalAllCredits < 15) $ineligibleReason = "Tổng tín chỉ {$totalAllCredits} (< 15)";
            }

            $scholarshipType = 'NONE';
            if ($isEligible) {
                if ($gpa10 >= 8.5 && $drl >= 90) $scholarshipType = '80%';
                elseif ($gpa10 >= 8.0 && $drl >= 80) $scholarshipType = '60%';
                elseif ($gpa10 >= 7.0 && $drl >= 70) $scholarshipType = '40%';
            }

            $scholarshipScore = $isEligible ? round(($gpa10 * 10) * 0.7 + $drl * 0.3, 2) : 0;

            SemesterEvaluation::updateOrCreate(
                ['student_id' => $stu->id, 'semester_code' => 'HK1_2026_2027'],
                [
                    'total_credits' => $totalAllCredits,
                    'min_course_score' => $minScore,
                    'training_score' => $drl,
                    'semester_gpa_10' => $gpa10,
                    'semester_gpa_4' => 3.2,
                    'is_eligible' => $isEligible,
                    'ineligible_reason' => $ineligibleReason,
                    'scholarship_type' => $scholarshipType,
                    'scholarship_score' => $scholarshipScore,
                    'scholarship_status' => 'PUBLISHED',
                ]
            );

            // Hóa đơn học phí cho sinh viên phụ
            TuitionInvoice::updateOrCreate(
                ['student_id' => $stu->id, 'semester_code' => 'HK1_2025_2026'],
                [
                    'total_credits' => 10,
                    'total_amount' => 5500000,
                    'paid_amount' => 5500000,
                    'status' => 'PAID',
                    'payment_code' => 'HUIT ' . $stu->student_code . ' HK12526',
                    'paid_at' => Carbon::parse('2025-10-15 10:00:00'),
                ]
            );

            TuitionInvoice::updateOrCreate(
                ['student_id' => $stu->id, 'semester_code' => 'HK2_2025_2026'],
                [
                    'total_credits' => 7,
                    'total_amount' => 3850000,
                    'paid_amount' => 3850000,
                    'status' => 'PAID',
                    'payment_code' => 'HUIT ' . $stu->student_code . ' HK22526',
                    'paid_at' => Carbon::parse('2026-02-15 10:00:00'),
                ]
            );

            TuitionInvoice::updateOrCreate(
                ['student_id' => $stu->id, 'semester_code' => 'HK_HE_2025_2026'],
                [
                    'total_credits' => 3,
                    'total_amount' => 1650000,
                    'paid_amount' => 1650000,
                    'status' => 'PAID',
                    'payment_code' => 'HUIT ' . $stu->student_code . ' HKHE2526',
                    'paid_at' => Carbon::parse('2026-06-15 10:00:00'),
                ]
            );

            TuitionInvoice::updateOrCreate(
                ['student_id' => $stu->id, 'semester_code' => 'HK1_2026_2027'],
                [
                    'total_credits' => $sem1Credits,
                    'total_amount' => $sem1Credits * 550000,
                    'paid_amount' => $index === 1 ? $sem1Credits * 550000 : 0,
                    'status' => $index === 1 ? 'PAID' : 'UNPAID',
                    'payment_code' => 'HUIT ' . $stu->student_code . ' HK12627',
                    'paid_at' => $index === 1 ? now() : null,
                ]
            );
        }

        require_once __DIR__ . '/SpecializationSeeder.php';
        $this->call(SpecializationSeeder::class);
    }
}
