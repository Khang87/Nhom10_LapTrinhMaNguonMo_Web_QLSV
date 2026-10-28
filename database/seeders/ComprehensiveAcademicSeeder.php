<?php

namespace Database\Seeders;

use App\Models\Cohort;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\CurriculumFramework;
use App\Models\Enrollment;
use App\Models\Faculty;
use App\Models\Grade;
use App\Models\Lecturer;
use App\Models\Semester;
use App\Models\Specialization;
use App\Models\Student;
use App\Models\StudentClass;
use App\Models\Subject;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ComprehensiveAcademicSeeder extends Seeder
{
    public function run(): void
    {
        // ==========================================
        // 1. TẠO CÁC KHOA ĐÀO TẠO (FACULTIES) ĐA DẠNG
        // ==========================================
        $facultiesData = [
            [
                'code' => 'CNTT',
                'name' => 'Khoa Công nghệ Thông tin',
                'phone' => '028.38161673',
                'email' => 'cntt@huit.edu.vn',
                'office_location' => 'Phòng B.302, Tòa nhà B, HUIT',
                'description' => 'Đào tạo kỹ sư CNTT, Công nghệ phần mềm, Hệ thống thông tin, Khoa học dữ liệu & AI, An toàn thông tin và Mạng máy tính.',
            ],
            [
                'code' => 'QTKD',
                'name' => 'Khoa Quản trị Kinh doanh',
                'phone' => '028.38161674',
                'email' => 'qtkd@huit.edu.vn',
                'office_location' => 'Phòng C.201, Tòa nhà C, HUIT',
                'description' => 'Đào tạo cử nhân Quản trị kinh doanh, Marketing số, Thương mại điện tử và Logistics & Quản lý chuỗi cung ứng toàn cầu.',
            ],
            [
                'code' => 'TCNH',
                'name' => 'Khoa Tài chính - Ngân hàng',
                'phone' => '028.38161675',
                'email' => 'tcnh@huit.edu.vn',
                'office_location' => 'Phòng C.305, Tòa nhà C, HUIT',
                'description' => 'Đào tạo cử nhân Tài chính doanh nghiệp, Ngân hàng số & Fintech, Kế toán kiểm toán và Phân tích đầu tư tài chính.',
            ],
            [
                'code' => 'NN',
                'name' => 'Khoa Ngoại ngữ',
                'phone' => '028.38161676',
                'email' => 'nn@huit.edu.vn',
                'office_location' => 'Phòng D.102, Tòa nhà D, HUIT',
                'description' => 'Đào tạo cử nhân Ngôn ngữ Anh thương mại, Tiếng Anh Biên phiên dịch, Phương pháp giảng dạy tiếng Anh (TESOL).',
            ],
            [
                'code' => 'CNTP',
                'name' => 'Khoa Công nghệ Thực phẩm',
                'phone' => '028.38161677',
                'email' => 'cntp@huit.edu.vn',
                'office_location' => 'Phòng E.204, Tòa nhà E, HUIT',
                'description' => 'Đào tạo kỹ sư Công nghệ thực phẩm, Đảm bảo chất lượng & ATTP, Phát triển sản phẩm dinh dưỡng và Đồ uống chế biến.',
            ],
            [
                'code' => 'DDT',
                'name' => 'Khoa Kỹ thuật Điện - Điện tử',
                'phone' => '028.38161678',
                'email' => 'ddt@huit.edu.vn',
                'office_location' => 'Phòng F.101, Tòa nhà F, HUIT',
                'description' => 'Đào tạo kỹ sư Kỹ thuật điều khiển & Tự động hóa, Robot thông minh, Điện tử viễn thông và Hệ thống nhúng IoT.',
            ],
        ];

        $faculties = [];
        foreach ($facultiesData as $f) {
            $fac = Faculty::updateOrCreate(['code' => $f['code']], $f);
            $faculties[$f['code']] = $fac;
        }

        // Xóa bản ghi rác nếu có (code = 'a')
        Faculty::where('code', 'a')->delete();

        // ==========================================
        // 2. TẠO CÁC KHÓA TUYỂN SINH (COHORTS)
        // ==========================================
        $cohortsData = [
            ['cohort_year' => '2021', 'name' => 'Khóa 2021 (Niên khóa K12: 2021 - 2025)'],
            ['cohort_year' => '2022', 'name' => 'Khóa 2022 (Niên khóa K13: 2022 - 2026)'],
            ['cohort_year' => '2023', 'name' => 'Khóa 2023 (Niên khóa K14: 2023 - 2027)'],
            ['cohort_year' => '2024', 'name' => 'Khóa 2024 (Niên khóa K15: 2024 - 2028)'],
        ];

        foreach ($cohortsData as $c) {
            Cohort::updateOrCreate(['cohort_year' => $c['cohort_year']], $c);
        }

        // ==========================================
        // 3. TẠO CHUYÊN NGÀNH (SPECIALIZATIONS) GẮN VÀO CÁC KHOA
        // ==========================================
        $specsData = [
            // Khoa Công nghệ Thông tin
            [
                'code' => 'CNPM',
                'name' => 'Công nghệ phần mềm',
                'faculty_id' => $faculties['CNTT']->id,
                'faculty_name' => $faculties['CNTT']->name,
                'description' => 'Kỹ thuật phần mềm Web/Mobile, kiến trúc Microservices, DevOps và kiểm thử tự động.',
            ],
            [
                'code' => 'HTTT',
                'name' => 'Hệ thống thông tin',
                'faculty_id' => $faculties['CNTT']->id,
                'faculty_name' => $faculties['CNTT']->name,
                'description' => 'Phân tích thiết kế hệ thống, cơ sở dữ liệu lớn (Big Data), ERP và chuyển đổi số.',
            ],
            [
                'code' => 'KHMT',
                'name' => 'Khoa học máy tính & Trí tuệ nhân tạo',
                'faculty_id' => $faculties['CNTT']->id,
                'faculty_name' => $faculties['CNTT']->name,
                'description' => 'Thuật toán nâng cao, Trí tuệ nhân tạo (AI), Học máy (Machine Learning) và Xử lý dữ liệu lớn.',
            ],
            [
                'code' => 'ATTT',
                'name' => 'An toàn thông tin',
                'faculty_id' => $faculties['CNTT']->id,
                'faculty_name' => $faculties['CNTT']->name,
                'description' => 'Bảo mật mạng, Mật mã học ứng dụng, Đánh giá lỗ hổng và Điều tra tội phạm mạng (Digital Forensics).',
            ],
            [
                'code' => 'MMT',
                'name' => 'Mạng máy tính & Điện toán đám mây',
                'faculty_id' => $faculties['CNTT']->id,
                'faculty_name' => $faculties['CNTT']->name,
                'description' => 'Quản trị hạ tầng mạng doanh nghiệp, Điện toán đám mây (Cloud Computing - AWS/Azure) và giải pháp viễn thông.',
            ],

            // Khoa Quản trị Kinh doanh
            [
                'code' => 'QTKD',
                'name' => 'Quản trị kinh doanh tổng hợp',
                'faculty_id' => $faculties['QTKD']->id,
                'faculty_name' => $faculties['QTKD']->name,
                'description' => 'Quản trị chiến lược, quản trị nhân sự, khởi nghiệp đổi mới sáng tạo và điều hành doanh nghiệp.',
            ],
            [
                'code' => 'MKT',
                'name' => 'Marketing số & Thương mại điện tử',
                'faculty_id' => $faculties['QTKD']->id,
                'faculty_name' => $faculties['QTKD']->name,
                'description' => 'Digital Marketing, quản lý sàn TMĐT, phân tích hành vi khách hàng và tối ưu hóa chiến dịch truyền thông.',
            ],
            [
                'code' => 'LOG',
                'name' => 'Logistics & Quản lý chuỗi cung ứng',
                'faculty_id' => $faculties['QTKD']->id,
                'faculty_name' => $faculties['QTKD']->name,
                'description' => 'Vận tải quốc tế, quản trị kho bãi, xuất nhập khẩu hải quan và tối ưu hóa chuỗi cung ứng.',
            ],

            // Khoa Tài chính - Ngân hàng
            [
                'code' => 'TCDN',
                'name' => 'Tài chính doanh nghiệp',
                'faculty_id' => $faculties['TCDN']->id ?? $faculties['TCNH']->id,
                'faculty_name' => $faculties['TCNH']->name,
                'description' => 'Quản trị tài chính công ty, thẩm định dự án đầu tư, thị trường chứng khoán và định giá tài sản.',
            ],
            [
                'code' => 'NHTS',
                'name' => 'Ngân hàng số & Công nghệ tài chính (Fintech)',
                'faculty_id' => $faculties['TCNH']->id,
                'faculty_name' => $faculties['TCNH']->name,
                'description' => 'Nghiệp vụ ngân hàng hiện đại, thanh toán điện tử, công nghệ chuỗi khối Blockchain và tín dụng số.',
            ],
            [
                'code' => 'KTDN',
                'name' => 'Kế toán - Kiểm toán doanh nghiệp',
                'faculty_id' => $faculties['TCNH']->id,
                'faculty_name' => $faculties['TCNH']->name,
                'description' => 'Chuẩn mực kế toán quốc tế IFRS, kiểm toán tài chính, kế toán quản trị và lập báo cáo thuế.',
            ],

            // Khoa Ngoại ngữ
            [
                'code' => 'NNA',
                'name' => 'Ngôn ngữ Anh thương mại',
                'faculty_id' => $faculties['NN']->id,
                'faculty_name' => $faculties['NN']->name,
                'description' => 'Tiếng Anh đàm phán thương mại, giao tiếp kinh doanh quốc tế và quan hệ công chúng toàn cầu.',
            ],
            [
                'code' => 'NNT',
                'name' => 'Tiếng Anh Biên phiên dịch',
                'faculty_id' => $faculties['NN']->id,
                'faculty_name' => $faculties['NN']->name,
                'description' => 'Dịch thuật cabin, biên dịch tài liệu chuyên ngành khoa học, văn hóa và kinh tế.',
            ],

            // Khoa Công nghệ Thực phẩm
            [
                'code' => 'CNTP',
                'name' => 'Công nghệ chế biến thực phẩm',
                'faculty_id' => $faculties['CNTP']->id,
                'faculty_name' => $faculties['CNTP']->name,
                'description' => 'Quy trình công nghệ bảo quản chế biến nông sản, đồ uống, công nghệ lên men và bao bì sinh học.',
            ],
            [
                'code' => 'DBCL',
                'name' => 'Đảm bảo chất lượng & An toàn thực phẩm',
                'faculty_id' => $faculties['CNTP']->id,
                'faculty_name' => $faculties['CNTP']->name,
                'description' => 'Hệ thống quản lý an toàn thực phẩm ISO 22000, HACCP, đánh giá cảm quan và phân tích độc chất.',
            ],

            // Khoa Kỹ thuật Điện - Điện tử
            [
                'code' => 'TDD',
                'name' => 'Kỹ thuật Điều khiển & Tự động hóa',
                'faculty_id' => $faculties['DDT']->id,
                'faculty_name' => $faculties['DDT']->name,
                'description' => 'Lập trình PLC, SCADA, robot công nghiệp, cảm biến thông minh và cánh tay robot tự hành.',
            ],
            [
                'code' => 'IOT',
                'name' => 'Kỹ thuật Điện tử & IoT thông minh',
                'faculty_id' => $faculties['DDT']->id,
                'faculty_name' => $faculties['DDT']->name,
                'description' => 'Thiết kế vi mạch, hệ thống nhúng thông minh, thiết bị đeo IoT và mạng cảm biến không dây.',
            ],
        ];

        $specs = [];
        foreach ($specsData as $s) {
            $spec = Specialization::updateOrCreate(['code' => $s['code']], $s);
            $specs[$s['code']] = $spec;
        }

        // ==========================================
        // 4. BỔ NHIỆM TRƯỞNG KHOA CHO TỪNG KHOA
        // ==========================================
        $deanAssignments = [
            'CNTT' => 'GV002',      // TS. Lê Hoàng Nam
            'QTKD' => 'GV_QTR01',   // TS. Trần Đình Thắng
            'TCNH' => 'GV_TC01',    // PGS.TS Vũ Đình Khoa
            'NN'   => 'GV_NN03',    // TS. Nguyễn Văn Hậu
            'CNTP' => 'GV_TP01',    // TS. Nguyễn Văn Thịnh
            'DDT'  => 'GV_DT01',    // TS. Huỳnh Thanh Tùng
        ];

        foreach ($deanAssignments as $facCode => $lecCode) {
            $lec = Lecturer::where('lecturer_code', $lecCode)->first();
            if ($lec && isset($faculties[$facCode])) {
                $faculties[$facCode]->update(['dean_id' => $lec->id]);
                $lec->update(['faculty_id' => $faculties[$facCode]->id]);
            }
        }

        // Cập nhật faculty_id cho các giảng viên theo khoa
        $lecturerFacultyMap = [
            'GV001' => 'CNTT', 'GV002' => 'CNTT', 'GV003' => 'CNTT', 'GV004' => 'CNTT', 'GV005' => 'CNTT',
            'GV006' => 'CNTT', 'GV007' => 'CNTT', 'GV008' => 'CNTT', 'GV009' => 'CNTT', 'GV010' => 'CNTT',
            'GV_QTR01' => 'QTKD', 'GV_QTR02' => 'QTKD', 'GV_QTR03' => 'QTKD',
            'GV_NN01' => 'NN', 'GV_NN02' => 'NN', 'GV_NN03' => 'NN',
            'GV_TC01' => 'TCNH', 'GV_TC02' => 'TCNH',
            'GV_TP01' => 'CNTP', 'GV_TP02' => 'CNTP',
            'GV_DT01' => 'DDT', 'GV_DT02' => 'DDT',
        ];

        foreach ($lecturerFacultyMap as $lCode => $fCode) {
            $lec = Lecturer::where('lecturer_code', $lCode)->first();
            if ($lec && isset($faculties[$fCode])) {
                $lec->update(['faculty_id' => $faculties[$fCode]->id]);
            }
        }

        // ==========================================
        // 5. TẠO CÁC LỚP HỌC DANH NGHĨA (CLASSES) PHONG PHÚ
        // ==========================================
        // Tạo các lớp phân bổ theo Khoa, Khóa, Chuyên ngành và phân công Giảng viên phụ trách (CVHT)
        $classesConfig = [
            // Khoa CNTT - Khóa 2023 (K14)
            [
                'class_code' => '14DHTH04',
                'class_name' => 'Đại học Tin học 04 (K14)',
                'cohort_year' => '2023',
                'specialization_code' => 'CNPM',
                'lecturer_code' => 'GV001', // ThS. Nguyễn Văn A
                'room' => 'A101 - Khu A',
                'description' => 'Lớp sinh viên chuyên ngành Công nghệ phần mềm Khóa 14.',
            ],
            [
                'class_code' => '14DHTH08',
                'class_name' => 'Đại học Tin học 08 (K14)',
                'cohort_year' => '2023',
                'specialization_code' => 'CNPM',
                'lecturer_code' => 'GV002', // TS. Lê Hoàng Nam
                'room' => 'A102 - Khu A',
                'description' => 'Lớp sinh viên chuyên ngành Công nghệ phần mềm Khóa 14 - Nhóm 2.',
            ],
            [
                'class_code' => '14DHTT01',
                'class_name' => 'Đại học Hệ thống thông tin 01 (K14)',
                'cohort_year' => '2023',
                'specialization_code' => 'HTTT',
                'lecturer_code' => 'GV003', // ThS. Trần Thị Bích
                'room' => 'B203 - Khu B',
                'description' => 'Lớp sinh viên chuyên ngành Hệ thống thông tin Khóa 14.',
            ],
            [
                'class_code' => '14DHAT01',
                'class_name' => 'Đại học An toàn thông tin 01 (K14)',
                'cohort_year' => '2023',
                'specialization_code' => 'ATTT',
                'lecturer_code' => 'GV007', // ThS. Nguyễn Hữu Trọng
                'room' => 'B301 - Khu B',
                'description' => 'Lớp sinh viên chuyên ngành An toàn thông tin Khóa 14.',
            ],

            // Khoa CNTT - Khóa 2024 (K15)
            [
                'class_code' => '15DHTH01',
                'class_name' => 'Đại học Công nghệ Phần mềm K15.01',
                'cohort_year' => '2024',
                'specialization_code' => 'CNPM',
                'lecturer_code' => 'GV009', // TS. Bùi Anh Tuấn
                'room' => 'A201 - Khu A',
                'description' => 'Lớp tân sinh viên Khóa 15 ngành Công nghệ phần mềm.',
            ],
            [
                'class_code' => '15DHKH01',
                'class_name' => 'Đại học Khoa học Máy tính & AI K15',
                'cohort_year' => '2024',
                'specialization_code' => 'KHMT',
                'lecturer_code' => 'GV005', // ThS. Đỗ Thị Mai
                'room' => 'A205 - Khu A',
                'description' => 'Lớp kỹ sư chuyên sâu Trí tuệ nhân tạo Khóa 15.',
            ],

            // Khoa CNTT - Khóa 2022 (K13)
            [
                'class_code' => '13DHMM01',
                'class_name' => 'Đại học Mạng máy tính K13',
                'cohort_year' => '2022',
                'specialization_code' => 'MMT',
                'lecturer_code' => 'GV004', // PGS.TS Phạm Minh Tuấn
                'room' => 'B104 - Khu B',
                'description' => 'Lớp chuyên ngành Mạng máy tính & Điện toán đám mây Khóa 13.',
            ],

            // Khoa Quản trị Kinh doanh - Khóa 2023 (K14)
            [
                'class_code' => '14DHQT01',
                'class_name' => 'Đại học Quản trị Kinh doanh 01 (K14)',
                'cohort_year' => '2023',
                'specialization_code' => 'QTKD',
                'lecturer_code' => 'GV_QTR01', // TS. Trần Đình Thắng
                'room' => 'C101 - Khu C',
                'description' => 'Lớp Quản trị kinh doanh tổng hợp Khóa 14.',
            ],
            [
                'class_code' => '14DHMK01',
                'class_name' => 'Đại học Marketing & TMĐT 01 (K14)',
                'cohort_year' => '2023',
                'specialization_code' => 'MKT',
                'lecturer_code' => 'GV_QTR02', // ThS. Nguyễn Thị Thu Trang
                'room' => 'C104 - Khu C',
                'description' => 'Lớp Marketing số Khóa 14.',
            ],

            // Khoa Quản trị Kinh doanh - Khóa 2024 (K15)
            [
                'class_code' => '15DHLG01',
                'class_name' => 'Đại học Logistics & Chuỗi cung ứng K15',
                'cohort_year' => '2024',
                'specialization_code' => 'LOG',
                'lecturer_code' => 'GV_QTR03', // TS. Lê Minh Tuấn
                'room' => 'C203 - Khu C',
                'description' => 'Lớp Logistics và Quản trị chuỗi cung ứng Khóa 15.',
            ],

            // Khoa Tài chính - Ngân hàng - Khóa 2023 (K14)
            [
                'class_code' => '14DHTC01',
                'class_name' => 'Đại học Tài chính Doanh nghiệp 01 (K14)',
                'cohort_year' => '2023',
                'specialization_code' => 'TCDN',
                'lecturer_code' => 'GV_TC01', // PGS.TS Vũ Đình Khoa
                'room' => 'C302 - Khu C',
                'description' => 'Lớp Tài chính doanh nghiệp Khóa 14.',
            ],
            // Khoa Tài chính - Ngân hàng - Khóa 2024 (K15)
            [
                'class_code' => '15DHNH01',
                'class_name' => 'Đại học Ngân hàng số & Fintech K15',
                'cohort_year' => '2024',
                'specialization_code' => 'NHTS',
                'lecturer_code' => 'GV_TC02', // TS. Trần Mỹ Duyên
                'room' => 'C306 - Khu C',
                'description' => 'Lớp Fintech và Ngân hàng số Khóa 15.',
            ],

            // Khoa Ngoại ngữ - Khóa 2023 (K14)
            [
                'class_code' => '14DHNA01',
                'class_name' => 'Đại học Ngôn ngữ Anh Thương mại (K14)',
                'cohort_year' => '2023',
                'specialization_code' => 'NNA',
                'lecturer_code' => 'GV_NN01', // ThS. Jennifer Phạm
                'room' => 'D201 - Khu D',
                'description' => 'Lớp Ngôn ngữ Anh thương mại Khóa 14.',
            ],

            // Khoa Công nghệ Thực phẩm - Khóa 2023 (K14)
            [
                'class_code' => '14DHTP01',
                'class_name' => 'Đại học Công nghệ Thực phẩm 01 (K14)',
                'cohort_year' => '2023',
                'specialization_code' => 'CNTP',
                'lecturer_code' => 'GV_TP01', // TS. Nguyễn Văn Thịnh
                'room' => 'E103 - Khu E',
                'description' => 'Lớp Công nghệ chế biến thực phẩm Khóa 14.',
            ],

            // Khoa Kỹ thuật Điện - Điện tử - Khóa 2023 (K14)
            [
                'class_code' => '14DHTD01',
                'class_name' => 'Đại học Điều khiển & Tự động hóa (K14)',
                'cohort_year' => '2023',
                'specialization_code' => 'TDD',
                'lecturer_code' => 'GV_DT01', // TS. Huỳnh Thanh Tùng
                'room' => 'F202 - Khu F',
                'description' => 'Lớp Kỹ thuật điều khiển và tự động hóa Khóa 14.',
            ],
        ];

        $studentClasses = [];
        foreach ($classesConfig as $cfg) {
            $specObj = $specs[$cfg['specialization_code']] ?? null;
            $lecObj = Lecturer::where('lecturer_code', $cfg['lecturer_code'])->first();

            $cls = StudentClass::updateOrCreate(
                ['class_code' => $cfg['class_code']],
                [
                    'class_name' => $cfg['class_name'],
                    'cohort_year' => $cfg['cohort_year'],
                    'specialization_id' => $specObj?->id,
                    'lecturer_id' => $lecObj?->id,
                    'room' => $cfg['room'],
                    'description' => $cfg['description'],
                ]
            );
            $studentClasses[$cfg['class_code']] = $cls;
        }

        // ==========================================
        // 6. THÊM CÁC MÔN HỌC (COURSES) ĐA NGÀNH
        // ==========================================
        $multiCourses = [
            // Quản trị kinh doanh & Marketing
            ['course_code' => 'QTKD101', 'course_name' => 'Quản trị học đại cương', 'credits' => 3, 'tuition_fee_per_credit' => 550000],
            ['course_code' => 'MKT102', 'course_name' => 'Marketing căn bản & Hành vi người tiêu dùng', 'credits' => 3, 'tuition_fee_per_credit' => 550000],
            ['course_code' => 'LOG101', 'course_name' => 'Nhập môn Logistics và Quản lý chuỗi cung ứng', 'credits' => 3, 'tuition_fee_per_credit' => 550000],

            // Tài chính - Ngân hàng
            ['course_code' => 'TC101', 'course_name' => 'Tài chính - Tiền tệ', 'credits' => 3, 'tuition_fee_per_credit' => 550000],
            ['course_code' => 'TC102', 'course_name' => 'Tài chính doanh nghiệp căn bản', 'credits' => 3, 'tuition_fee_per_credit' => 550000],
            ['course_code' => 'FIN101', 'course_name' => 'Nhập môn Công nghệ tài chính (Fintech)', 'credits' => 3, 'tuition_fee_per_credit' => 550000],

            // Ngoại ngữ
            ['course_code' => 'ENG201', 'course_name' => 'Tiếng Anh thương mại tổng quát', 'credits' => 3, 'tuition_fee_per_credit' => 550000],
            ['course_code' => 'ENG202', 'course_name' => 'Kỹ năng thuyết trình & Đàm phán tiếng Anh', 'credits' => 3, 'tuition_fee_per_credit' => 550000],

            // Công nghệ Thực phẩm
            ['course_code' => 'TP101', 'course_name' => 'Hóa học và Vi sinh vật thực phẩm', 'credits' => 3, 'tuition_fee_per_credit' => 550000],
            ['course_code' => 'TP102', 'course_name' => 'Nguyên lý chế biến và bảo quản thực phẩm', 'credits' => 3, 'tuition_fee_per_credit' => 550000],

            // Kỹ thuật Điện - Điện tử & Tự động hóa
            ['course_code' => 'TD101', 'course_name' => 'Kỹ thuật mạch điện tử và Cảm biến', 'credits' => 3, 'tuition_fee_per_credit' => 550000],
            ['course_code' => 'TD102', 'course_name' => 'Lập trình điều khiển PLC & Vi điều khiển', 'credits' => 4, 'tuition_fee_per_credit' => 550000],
        ];

        foreach ($multiCourses as $mc) {
            Course::updateOrCreate(['course_code' => $mc['course_code']], $mc);
            Subject::updateOrCreate(['code' => $mc['course_code']], ['name' => $mc['course_name'], 'credits' => $mc['credits']]);
        }

        // ==========================================
        // 7. THIẾT LẬP KHUNG CHƯƠNG TRÌNH CHO CÁC NGÀNH MỚI
        // ==========================================
        $allCoursesMap = Course::all()->keyBy('course_code');

        $extraCurriculums = [
            'QTKD' => [
                1 => ['MATH101' => true, 'ENG101' => true, 'QTKD101' => true, 'LAW101' => false],
                2 => ['MKT101' => true, 'MKT102' => true, 'LOG101' => false],
                3 => ['TC101' => true, 'TC102' => true],
            ],
            'MKT' => [
                1 => ['MATH101' => true, 'ENG101' => true, 'MKT101' => true, 'LAW101' => false],
                2 => ['MKT102' => true, 'WEB101' => false],
                3 => ['LOG101' => true, 'QTKD101' => true],
            ],
            'LOG' => [
                1 => ['MATH101' => true, 'ENG101' => true, 'QTKD101' => true, 'LAW101' => false],
                2 => ['LOG101' => true, 'MKT101' => true],
                3 => ['TC101' => true],
            ],
            'TCDN' => [
                1 => ['MATH101' => true, 'ENG101' => true, 'LAW101' => true, 'QTKD101' => false],
                2 => ['TC101' => true, 'TC102' => true],
                3 => ['FIN101' => true, 'DB101' => false],
            ],
            'NHTS' => [
                1 => ['MATH101' => true, 'ENG101' => true, 'LAW101' => true],
                2 => ['TC101' => true, 'FIN101' => true, 'DB101' => false],
                3 => ['TC102' => true, 'SEC101' => true],
            ],
            'NNA' => [
                1 => ['ENG101' => true, 'ENG201' => true, 'LAW101' => false],
                2 => ['ENG202' => true, 'QTKD101' => true],
                3 => ['MKT101' => true],
            ],
            'CNTP' => [
                1 => ['MATH101' => true, 'ENG101' => true, 'TP101' => true, 'LAW101' => false],
                2 => ['TP102' => true],
            ],
            'TDD' => [
                1 => ['MATH101' => true, 'ENG101' => true, 'TD101' => true, 'LAW101' => false],
                2 => ['TD102' => true, 'IOT101' => true],
            ],
        ];

        foreach ($extraCurriculums as $sCode => $semesters) {
            if (isset($specs[$sCode])) {
                $sp = $specs[$sCode];
                foreach ($semesters as $semOrder => $coursesList) {
                    foreach ($coursesList as $cCode => $isMandatory) {
                        if (isset($allCoursesMap[$cCode])) {
                            CurriculumFramework::updateOrCreate(
                                [
                                    'specialization_id' => $sp->id,
                                    'semester_order' => $semOrder,
                                    'course_id' => $allCoursesMap[$cCode]->id,
                                ],
                                [
                                    'specialization_name' => $sp->name,
                                    'is_mandatory' => $isMandatory,
                                ]
                            );
                        }
                    }
                }
            }
        }

        // ==========================================
        // 8. TẠO SINH VIÊN VÀ ĐIỂM SỐ ĐA DẠNG CHO CÁC LỚP
        // ==========================================
        // Danh sách sinh viên tiêu biểu cho từng lớp
        $sampleStudents = [
            // Lớp 14DHTT01 (Hệ thống thông tin - K14)
            [
                'code' => '2001230101', 'name' => 'Trần Văn Hoàng Long', 'gender' => 'Nam', 'dob' => '2005-02-14',
                'class_code' => '14DHTT01', 'cohort' => '2023', 'spec_code' => 'HTTT', 'fac_code' => 'CNTT',
                'email' => '2001230101@huit.edu.vn', 'phone' => '0938123401',
            ],
            [
                'code' => '2001230102', 'name' => 'Nguyễn Thị Minh Thư', 'gender' => 'Nữ', 'dob' => '2005-07-22',
                'class_code' => '14DHTT01', 'cohort' => '2023', 'spec_code' => 'HTTT', 'fac_code' => 'CNTT',
                'email' => '2001230102@huit.edu.vn', 'phone' => '0938123402',
            ],

            // Lớp 14DHAT01 (An toàn thông tin - K14)
            [
                'code' => '2001230201', 'name' => 'Lê Quốc Bảo', 'gender' => 'Nam', 'dob' => '2005-09-18',
                'class_code' => '14DHAT01', 'cohort' => '2023', 'spec_code' => 'ATTT', 'fac_code' => 'CNTT',
                'email' => '2001230201@huit.edu.vn', 'phone' => '0938123403',
            ],
            [
                'code' => '2001230202', 'name' => 'Phạm Hoàng Khang', 'gender' => 'Nam', 'dob' => '2005-11-05',
                'class_code' => '14DHAT01', 'cohort' => '2023', 'spec_code' => 'ATTT', 'fac_code' => 'CNTT',
                'email' => '2001230202@huit.edu.vn', 'phone' => '0938123404',
            ],

            // Lớp 15DHTH01 (Công nghệ phần mềm - K15)
            [
                'code' => '2001240001', 'name' => 'Đỗ Gia Huy', 'gender' => 'Nam', 'dob' => '2006-01-12',
                'class_code' => '15DHTH01', 'cohort' => '2024', 'spec_code' => 'CNPM', 'fac_code' => 'CNTT',
                'email' => '2001240001@huit.edu.vn', 'phone' => '0938123405',
            ],
            [
                'code' => '2001240002', 'name' => 'Võ Thị Thanh Thảo', 'gender' => 'Nữ', 'dob' => '2006-08-30',
                'class_code' => '15DHTH01', 'cohort' => '2024', 'spec_code' => 'CNPM', 'fac_code' => 'CNTT',
                'email' => '2001240002@huit.edu.vn', 'phone' => '0938123406',
            ],

            // Lớp 15DHKH01 (Khoa học máy tính & AI - K15)
            [
                'code' => '2001240101', 'name' => 'Hoàng Minh Quân', 'gender' => 'Nam', 'dob' => '2006-03-25',
                'class_code' => '15DHKH01', 'cohort' => '2024', 'spec_code' => 'KHMT', 'fac_code' => 'CNTT',
                'email' => '2001240101@huit.edu.vn', 'phone' => '0938123407',
            ],

            // Lớp 13DHMM01 (Mạng máy tính - K13)
            [
                'code' => '2001220001', 'name' => 'Bùi Tuấn Anh', 'gender' => 'Nam', 'dob' => '2004-05-19',
                'class_code' => '13DHMM01', 'cohort' => '2022', 'spec_code' => 'MMT', 'fac_code' => 'CNTT',
                'email' => '2001220001@huit.edu.vn', 'phone' => '0938123408',
            ],

            // Lớp 14DHQT01 (Quản trị kinh doanh - K14)
            [
                'code' => '2001230301', 'name' => 'Nguyễn Phương Thảo', 'gender' => 'Nữ', 'dob' => '2005-04-10',
                'class_code' => '14DHQT01', 'cohort' => '2023', 'spec_code' => 'QTKD', 'fac_code' => 'QTKD',
                'email' => '2001230301@huit.edu.vn', 'phone' => '0938123409',
            ],
            [
                'code' => '2001230302', 'name' => 'Trương Đình Trọng', 'gender' => 'Nam', 'dob' => '2005-12-01',
                'class_code' => '14DHQT01', 'cohort' => '2023', 'spec_code' => 'QTKD', 'fac_code' => 'QTKD',
                'email' => '2001230302@huit.edu.vn', 'phone' => '0938123410',
            ],

            // Lớp 14DHMK01 (Marketing số - K14)
            [
                'code' => '2001230401', 'name' => 'Lâm Yến Nhi', 'gender' => 'Nữ', 'dob' => '2005-06-15',
                'class_code' => '14DHMK01', 'cohort' => '2023', 'spec_code' => 'MKT', 'fac_code' => 'QTKD',
                'email' => '2001230401@huit.edu.vn', 'phone' => '0938123411',
            ],

            // Lớp 15DHLG01 (Logistics - K15)
            [
                'code' => '2001240201', 'name' => 'Huỳnh Hải Đăng', 'gender' => 'Nam', 'dob' => '2006-09-09',
                'class_code' => '15DHLG01', 'cohort' => '2024', 'spec_code' => 'LOG', 'fac_code' => 'QTKD',
                'email' => '2001240201@huit.edu.vn', 'phone' => '0938123412',
            ],

            // Lớp 14DHTC01 (Tài chính doanh nghiệp - K14)
            [
                'code' => '2001230501', 'name' => 'Trần Kim Ngân', 'gender' => 'Nữ', 'dob' => '2005-10-20',
                'class_code' => '14DHTC01', 'cohort' => '2023', 'spec_code' => 'TCDN', 'fac_code' => 'TCNH',
                'email' => '2001230501@huit.edu.vn', 'phone' => '0938123413',
            ],

            // Lớp 15DHNH01 (Ngân hàng số - K15)
            [
                'code' => '2001240301', 'name' => 'Đặng Thiên Phúc', 'gender' => 'Nam', 'dob' => '2006-04-18',
                'class_code' => '15DHNH01', 'cohort' => '2024', 'spec_code' => 'NHTS', 'fac_code' => 'TCNH',
                'email' => '2001240301@huit.edu.vn', 'phone' => '0938123414',
            ],

            // Lớp 14DHNA01 (Ngôn ngữ Anh - K14)
            [
                'code' => '2001230601', 'name' => 'Mai Tuyết Anh', 'gender' => 'Nữ', 'dob' => '2005-08-08',
                'class_code' => '14DHNA01', 'cohort' => '2023', 'spec_code' => 'NNA', 'fac_code' => 'NN',
                'email' => '2001230601@huit.edu.vn', 'phone' => '0938123415',
            ],

            // Lớp 14DHTP01 (Công nghệ Thực phẩm - K14)
            [
                'code' => '2001230701', 'name' => 'Lý Gia Hân', 'gender' => 'Nữ', 'dob' => '2005-05-11',
                'class_code' => '14DHTP01', 'cohort' => '2023', 'spec_code' => 'CNTP', 'fac_code' => 'CNTP',
                'email' => '2001230701@huit.edu.vn', 'phone' => '0938123416',
            ],

            // Lớp 14DHTD01 (Tự động hóa - K14)
            [
                'code' => '2001230801', 'name' => 'Vũ Tiến Đạt', 'gender' => 'Nam', 'dob' => '2005-03-31',
                'class_code' => '14DHTD01', 'cohort' => '2023', 'spec_code' => 'TDD', 'fac_code' => 'DDT',
                'email' => '2001230801@huit.edu.vn', 'phone' => '0938123417',
            ],
        ];

        // Lấy 1 học kỳ mẫu để tạo lớp học phần nếu cần
        $defaultSemester = Semester::first();

        foreach ($sampleStudents as $sData) {
            $clsModel = $studentClasses[$sData['class_code']] ?? null;
            $specModel = $specs[$sData['spec_code']] ?? null;
            $facModel = $faculties[$sData['fac_code']] ?? null;

            // Tạo tài khoản User (đăng nhập bằng mã sinh viên hoặc email, mật khẩu 12345678)
            $user = User::updateOrCreate(
                ['email' => $sData['email']],
                [
                    'username' => $sData['code'],
                    'name' => $sData['name'],
                    'password' => Hash::make('12345678'),
                    'role' => 'student',
                    'class_id' => $clsModel?->id,
                    'is_active' => true,
                ]
            );

            // Tạo hồ sơ sinh viên
            $student = Student::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'student_code' => $sData['code'],
                    'full_name' => $sData['name'],
                    'gender' => $sData['gender'],
                    'dob' => $sData['dob'],
                    'study_status' => 'Đang học',
                    'class_code' => $sData['class_code'],
                    'cohort_year' => (int) $sData['cohort'],
                    'specialization_id' => $specModel?->id,
                    'specialization_name' => $specModel?->name,
                    'faculty_name' => $facModel?->name ?? 'Khoa Công nghệ Thông tin',
                    'major_name' => $specModel?->name,
                    'campus' => 'ĐHCT TP.HCM',
                    'education_level' => 'Đại học',
                    'training_type' => 'Chính quy',
                    'phone' => $sData['phone'],
                    'email' => $sData['email'],
                    'permanent_residence' => 'TP. Hồ Chí Minh',
                    'enrollment_date' => Carbon::parse($sData['cohort'] . '-08-25'),
                ]
            );

            // Tạo các Enrollment & Grade mẫu theo Khung CTĐT của chuyên ngành đó
            if ($specModel) {
                $frameworkCourses = CurriculumFramework::with('course')
                    ->where('specialization_id', $specModel->id)
                    ->whereIn('semester_order', [1, 2])
                    ->get();

                foreach ($frameworkCourses as $item) {
                    if (!$item->course) {
                        continue;
                    }

                    // Tìm hoặc tạo CourseSection mẫu cho môn này
                    $section = CourseSection::where('course_id', $item->course_id)->first();
                    if (!$section) {
                        $section = CourseSection::create([
                            'course_id' => $item->course_id,
                            'semester_code' => 'HK1_2026_2027',
                            'section_code' => $item->course->course_code . '_01',
                            'day_of_week' => 2,
                            'start_period' => 1,
                            'end_period' => 3,
                            'room_name' => 'A201 - Phòng học lý thuyết',
                            'max_capacity' => 60,
                        ]);
                    }

                    if ($section) {
                        // Tạo hoặc cập nhật enrollment
                        $midterm = mt_rand(65, 95) / 10;
                        $final = mt_rand(60, 95) / 10;
                        $process = mt_rand(70, 100) / 10;

                        $enrollment = Enrollment::firstOrCreate(
                            [
                                'student_id' => $student->id,
                                'section_id' => $section->id,
                            ],
                            [
                                'process_score' => $process,
                                'midterm_score' => $midterm,
                                'final_score' => $final,
                                'registration_type' => 'FIRST_TIME',
                            ]
                        );

                        $enrollment->process_score = $process;
                        $enrollment->midterm_score = $midterm;
                        $enrollment->final_score = $final;
                        $enrollment->calculateGrade();
                        $enrollment->save();
                    }
                }
            }
        }

        // ==========================================
        // 9. ĐỒNG BỘ LẠI TOÀN BỘ SINH VIÊN CŨ THEO LỚP & CHUYÊN NGÀNH
        // ==========================================
        $oldStudents = Student::all();
        foreach ($oldStudents as $stu) {
            if ($stu->class_code && !$stu->specialization_id) {
                $matchedCls = StudentClass::where('class_code', $stu->class_code)->first();
                if ($matchedCls && $matchedCls->specialization_id) {
                    $stu->update([
                        'specialization_id' => $matchedCls->specialization_id,
                        'cohort_year' => $matchedCls->cohort_year ?: $stu->cohort_year,
                    ]);
                }
            }
        }
    }
}
