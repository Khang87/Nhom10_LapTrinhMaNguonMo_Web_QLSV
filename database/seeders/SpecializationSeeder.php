<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\CurriculumFramework;
use App\Models\Lecturer;
use App\Models\Specialization;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Database\Seeder;

class SpecializationSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Tạo các Chuyên ngành chính
        $specializationsData = [
            [
                'code' => 'CNPM',
                'name' => 'Công nghệ phần mềm',
                'faculty_name' => 'Khoa Công nghệ Thông tin',
                'description' => 'Đào tạo kỹ sư phần mềm, kiến trúc hệ thống, phát triển ứng dụng Web/Mobile và quy trình kiểm thử hiện đại.',
            ],
            [
                'code' => 'HTTT',
                'name' => 'Hệ thống thông tin',
                'faculty_name' => 'Khoa Công nghệ Thông tin',
                'description' => 'Đào tạo phân tích thiết kế hệ thống, cơ sở dữ liệu lớn (Big Data), ERP và giải pháp số doanh nghiệp.',
            ],
            [
                'code' => 'KHMT',
                'name' => 'Khoa học máy tính',
                'faculty_name' => 'Khoa Công nghệ Thông tin',
                'description' => 'Đào tạo chuyên sâu về thuật toán, Trí tuệ nhân tạo (AI), Học máy (Machine Learning) và Xử lý dữ liệu lớn.',
            ],
            [
                'code' => 'ATTT',
                'name' => 'An toàn thông tin',
                'faculty_name' => 'Khoa Công nghệ Thông tin',
                'description' => 'Đào tạo an ninh mạng, mật mã học ứng dụng, kiểm thử xâm nhập (PenTest) và phòng thủ hệ thống mạng.',
            ],
            [
                'code' => 'MMT',
                'name' => 'Mạng máy tính & Truyền thông',
                'faculty_name' => 'Khoa Công nghệ Thông tin',
                'description' => 'Đào tạo quản trị hạ tầng mạng, Điện toán đám mây (Cloud Computing - AWS/Azure/GCP) và thiết bị viễn thông.',
            ],
        ];

        $specs = [];
        foreach ($specializationsData as $s) {
            $spec = Specialization::updateOrCreate(['code' => $s['code']], $s);
            $specs[$s['code']] = $spec;
        }

        // 2. Thêm các Môn học bổ sung phục vụ chuyên sâu các ngành
        $extraCourses = [
            ['course_code' => 'CNPM102', 'course_name' => 'Kiểm thử phần mềm và Đảm bảo chất lượng', 'credits' => 3, 'tuition_fee_per_credit' => 550000],
            ['course_code' => 'CNPM103', 'course_name' => 'Kiến trúc phần mềm và Thiết kế mẫu', 'credits' => 3, 'tuition_fee_per_credit' => 550000],
            ['course_code' => 'HTTT101', 'course_name' => 'Phân tích & Thiết kế hệ thống thông tin', 'credits' => 3, 'tuition_fee_per_credit' => 550000],
            ['course_code' => 'HTTT102', 'course_name' => 'Quản trị hệ cơ sở dữ liệu doanh nghiệp', 'credits' => 3, 'tuition_fee_per_credit' => 550000],
            ['course_code' => 'KHMT101', 'course_name' => 'Học máy ứng dụng (Applied Machine Learning)', 'credits' => 4, 'tuition_fee_per_credit' => 550000],
            ['course_code' => 'KHMT102', 'course_name' => 'Xử lý ngôn ngữ tự nhiên và Thị giác máy tính', 'credits' => 3, 'tuition_fee_per_credit' => 550000],
            ['course_code' => 'ATTT101', 'course_name' => 'Mật mã học cơ sở và Ứng dụng bảo mật', 'credits' => 3, 'tuition_fee_per_credit' => 550000],
            ['course_code' => 'ATTT102', 'course_name' => 'Kỹ thuật tấn công và Phòng thủ mạng', 'credits' => 3, 'tuition_fee_per_credit' => 550000],
            ['course_code' => 'MMT101', 'course_name' => 'Quản trị hệ thống Linux & Windows Server', 'credits' => 3, 'tuition_fee_per_credit' => 550000],
            ['course_code' => 'MMT102', 'course_name' => 'Điện toán đám mây và DevOps', 'credits' => 3, 'tuition_fee_per_credit' => 550000],
        ];

        foreach ($extraCourses as $c) {
            Course::updateOrCreate(['course_code' => $c['course_code']], $c);
            Subject::updateOrCreate(
                ['code' => $c['course_code']],
                ['name' => $c['course_name'], 'credits' => $c['credits']]
            );
        }

        // 3. Gán specialization_id cho Giảng viên theo chuyên môn
        $lecturerMap = [
            'GV001' => 'CNPM', // ThS. Nguyễn Văn A
            'GV002' => 'CNPM', // TS. Lê Hoàng Nam
            'GV009' => 'CNPM', // TS. Bùi Anh Tuấn
            'GV010' => 'CNPM', // ThS. Phan Bích Ngọc
            'GV003' => 'HTTT', // ThS. Trần Thị Bích
            'GV006' => 'HTTT', // TS. Vũ Quốc Huy
            'GV005' => 'KHMT', // ThS. Đỗ Thị Mai
            'GV008' => 'KHMT', // TS. Lê Thu Hà
            'GV007' => 'ATTT', // ThS. Nguyễn Hữu Trọng
            'GV004' => 'MMT',  // PGS.TS Phạm Minh Tuấn
        ];

        foreach ($lecturerMap as $code => $specCode) {
            $lec = Lecturer::where('lecturer_code', $code)->first();
            if ($lec && isset($specs[$specCode])) {
                $lec->update(['specialization_id' => $specs[$specCode]->id]);
            }
        }

        // Với các giảng viên còn lại chưa có specialization, phân bổ luân phiên
        $allLecturers = Lecturer::whereNull('specialization_id')->get();
        $specKeys = array_keys($specs);
        $i = 0;
        foreach ($allLecturers as $lec) {
            $chosenKey = $specKeys[$i % count($specKeys)];
            $lec->update(['specialization_id' => $specs[$chosenKey]->id]);
            $i++;
        }

        // 4. Cập nhật CurriculumFramework hiện có và thêm cho các chuyên ngành khác
        $courses = Course::all()->keyBy('course_code');

        // Mapping chương trình khung cho từng chuyên ngành
        $curriculumsBySpec = [
            'CNPM' => [
                1 => ['DS101' => true, 'SE101' => true, 'MATH101' => true, 'ENG101' => false],
                2 => ['DB101' => true, 'NET101' => true, 'LAW101' => false],
                3 => ['WEB101' => true, 'CNPM102' => true, 'NOSQL101' => false, 'MKT101' => false],
                4 => ['OPEN101' => true, 'CNPM103' => true, 'IOT101' => false],
                5 => ['AI101' => true, 'MOB101' => false],
                6 => ['SEC101' => true],
                7 => [],
                8 => ['GRAD101' => true],
            ],
            'HTTT' => [
                1 => ['DS101' => true, 'MATH101' => true, 'ENG101' => true, 'LAW101' => false],
                2 => ['DB101' => true, 'NET101' => true, 'MKT101' => false],
                3 => ['HTTT101' => true, 'NOSQL101' => true, 'WEB101' => false],
                4 => ['HTTT102' => true, 'OPEN101' => false],
                5 => ['AI101' => true, 'IOT101' => false],
                6 => ['SEC101' => true],
                7 => [],
                8 => ['GRAD101' => true],
            ],
            'KHMT' => [
                1 => ['DS101' => true, 'MATH101' => true, 'ENG101' => true, 'LAW101' => false],
                2 => ['DB101' => true, 'AI101' => true, 'NET101' => false],
                3 => ['KHMT101' => true, 'NOSQL101' => true, 'WEB101' => false],
                4 => ['KHMT102' => true, 'OPEN101' => false],
                5 => ['IOT101' => true, 'MOB101' => false],
                6 => ['SEC101' => true],
                7 => [],
                8 => ['GRAD101' => true],
            ],
            'ATTT' => [
                1 => ['DS101' => true, 'MATH101' => true, 'ENG101' => true, 'LAW101' => false],
                2 => ['NET101' => true, 'DB101' => true],
                3 => ['ATTT101' => true, 'SEC101' => true, 'WEB101' => false],
                4 => ['ATTT102' => true, 'MMT101' => true, 'OPEN101' => false],
                5 => ['AI101' => true, 'NOSQL101' => false],
                6 => ['MOB101' => false],
                7 => [],
                8 => ['GRAD101' => true],
            ],
            'MMT' => [
                1 => ['DS101' => true, 'MATH101' => true, 'ENG101' => true, 'LAW101' => false],
                2 => ['NET101' => true, 'DB101' => true],
                3 => ['MMT101' => true, 'WEB101' => true, 'IOT101' => false],
                4 => ['MMT102' => true, 'SEC101' => true, 'OPEN101' => false],
                5 => ['AI101' => true, 'NOSQL101' => false],
                6 => ['MOB101' => false],
                7 => [],
                8 => ['GRAD101' => true],
            ],
        ];

        foreach ($curriculumsBySpec as $specCode => $semesters) {
            $spec = $specs[$specCode];
            foreach ($semesters as $sem => $subList) {
                foreach ($subList as $code => $isMandatory) {
                    if (isset($courses[$code])) {
                        CurriculumFramework::updateOrCreate(
                            [
                                'specialization_id' => $spec->id,
                                'semester_order' => $sem,
                                'course_id' => $courses[$code]->id,
                            ],
                            [
                                'specialization_name' => $spec->name,
                                'is_mandatory' => $isMandatory,
                            ]
                        );
                    }
                }
            }
        }

        // Đồng bộ các record CurriculumFramework cũ nếu chưa có specialization_id (tránh tạo bản ghi trùng)
        $oldItems = CurriculumFramework::whereNull('specialization_id')->get();
        foreach ($oldItems as $item) {
            $foundSpec = Specialization::where('name', $item->specialization_name)->first() ?? $specs['CNPM'];
            $alreadyExists = CurriculumFramework::where('specialization_id', $foundSpec->id)
                ->where('semester_order', $item->semester_order)
                ->where('course_id', $item->course_id)
                ->where('id', '!=', $item->id)
                ->exists();
            if ($alreadyExists) {
                $item->delete();
            } else {
                $item->update(['specialization_id' => $foundSpec->id]);
            }
        }

        // 5. Cập nhật sinh viên: gán specialization_id
        $students = Student::all();
        foreach ($students as $stu) {
            $foundSpec = Specialization::where('name', $stu->specialization_name)->first() ?? $specs['CNPM'];
            $stu->update([
                'specialization_id' => $foundSpec->id,
                'specialization_name' => $foundSpec->name,
            ]);
        }

        // 6. Điền điểm quá trình ngẫu nhiên cho enrollments cũ nếu chưa có
        $enrollments = \App\Models\Enrollment::all();
        foreach ($enrollments as $enr) {
            if ($enr->process_score === null) {
                $base = $enr->midterm_score ?? 7.5;
                $processScore = min(10.0, max(4.0, round($base + (mt_rand(-10, 10) / 10), 1)));
                $enr->process_score = $processScore;
                $enr->calculateGrade();
                $enr->save();
            }
        }
    }
}
