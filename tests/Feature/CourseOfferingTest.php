<?php

namespace Tests\Feature;

use App\Models\CourseOffering;
use App\Models\Department;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Major;
use App\Models\Semester;
use App\Models\StudentClass;
use App\Models\User;
use App\Support\OfferingAssignment;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\Feature\Concerns\OfferingFixtures;
use Tests\TestCase;

class CourseOfferingTest extends TestCase
{
    use OfferingFixtures, RefreshDatabase;

    public function test_activating_a_semester_keeps_exactly_one_active(): void
    {
        $first = $this->semester('2025-2026', 1);
        $second = $this->semester('2025-2026', 2);

        $this->assertSame($first->id, Semester::current()?->id);

        Semester::activate($second->id);

        $this->assertSame($second->id, Semester::current()?->id);
        $this->assertSame(1, Semester::where('is_active', true)->count());
        $this->assertTrue(Semester::find($second->id)->is_active);
        $this->assertFalse(Semester::find($first->id)->is_active);
    }

    public function test_semester_slug_is_generated_from_year_and_term(): void
    {
        $semester = $this->semester('2025-2026', 2);

        $this->assertSame('HK2-2025-2026', $semester->slug);
        $this->assertSame('Học kỳ 2 năm học 2025-2026', $semester->label);
    }

    public function test_one_subject_can_run_two_groups_in_the_same_semester(): void
    {
        $subject = $this->subject('WEB101');
        $semester = $this->semester('2025-2026', 1);

        $nhom1 = $this->offering($subject, $semester, 'TN01');
        $nhom2 = $this->offering($subject, $semester, 'TN02');

        $this->assertNotSame($nhom1->id, $nhom2->id);
        $this->assertSame(2, CourseOffering::where('subject_id', $subject->id)->count());
    }

    public function test_duplicate_group_in_the_same_subject_and_semester_is_rejected(): void
    {
        $subject = $this->subject('WEB101');
        $semester = $this->semester('2025-2026', 1);
        $this->offering($subject, $semester, 'TN01');

        try {
            CourseOffering::create([
                'subject_id' => $subject->id,
                'semester_id' => $semester->id,
                'group_code' => 'TN01',
            ]);
            $this->fail('Trùng (môn, kỳ, nhóm) phải bị chặn bởi unique index.');
        } catch (UniqueConstraintViolationException $e) {
            $this->assertSame(1, CourseOffering::count());
        }
    }

    public function test_seats_left_counts_only_active_enrollments_and_zero_is_unlimited(): void
    {
        $semester = $this->semester('2025-2026', 1);
        $capped = $this->offering($this->subject('WEB101'), $semester, 'TN01', 3);
        $open = $this->offering($this->subject('DB101'), $semester, 'TN01');

        $students = User::factory()->count(3)->create(['role' => 'student']);
        foreach ($students as $student) {
            Enrollment::create(['course_offering_id' => $capped->id, 'user_id' => $student->id]);
        }

        $this->assertSame(0, $capped->fresh()->seatsLeft());
        $this->assertSame(3, $capped->fresh()->enrolledCount());

        $dropped = $capped->enrollments()->where('user_id', $students->first()->id)->sole();
        $dropped->update(['status' => 'DROPPED', 'dropped_at' => now()]);

        $this->assertSame(1, $capped->fresh()->seatsLeft());
        $this->assertSame(2, $capped->fresh()->enrolledCount());
        $this->assertSame(\PHP_INT_MAX, $open->seatsLeft(), 'Sĩ số 0 nghĩa là không giới hạn.');
    }

    public function test_registration_gate_needs_both_the_window_and_the_offering_flag(): void
    {
        $semester = $this->semester('2025-2026', 1, [
            'registration_starts_at' => '2026-01-05 00:00:00',
            'registration_ends_at' => '2026-01-19 23:59:59',
        ]);
        $offering = $this->offering($this->subject('WEB101'), $semester, 'TN01');

        $this->travelTo('2026-01-10 09:00:00');
        $this->assertTrue($offering->fresh()->isRegistrationOpen());

        $this->travelTo('2026-01-25 09:00:00');
        $this->assertFalse($offering->fresh()->isRegistrationOpen(), 'Cổng kỳ đã đóng.');

        $this->travelTo('2026-01-10 09:00:00');
        $offering->update(['is_open' => false]);
        $this->assertFalse($offering->fresh()->isRegistrationOpen(), 'Lớp tự đóng thì cổng kỳ cũng vô nghĩa.');

        $this->travelBack();
    }

    public function test_semester_without_registration_window_is_closed(): void
    {
        $semester = $this->semester('2025-2026', 1);
        $offering = $this->offering($this->subject('WEB101'), $semester, 'TN01');

        $this->assertFalse($offering->isRegistrationOpen());
    }

    public function test_assigning_a_non_teacher_is_rejected_with_a_friendly_reason(): void
    {
        $semester = $this->semester('2025-2026', 1);
        $offering = $this->offering($this->subject('WEB101'), $semester, 'TN01');

        $this->expectException(ValidationException::class);
        OfferingAssignment::assertTeacher(User::factory()->create(['role' => 'admin']));
    }

    public function test_assigning_a_teacher_stores_the_link(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher', 'name' => 'Nguyễn Văn Đạt']);
        $offering = $this->offering($this->subject('WEB101'), $this->semester('2025-2026', 1), 'TN01');

        $offering->update(['teacher_id' => $teacher->id]);

        $this->assertSame('Nguyễn Văn Đạt', $offering->fresh()->teacher->name);
    }

    public function test_deleting_a_semester_that_still_has_offerings_is_blocked(): void
    {
        $semester = $this->semester('2025-2026', 1);
        $this->offering($this->subject('WEB101'), $semester, 'TN01');

        $this->expectException(QueryException::class);
        $semester->delete();
    }

    public function test_enrollments_are_unique_per_student_and_offering(): void
    {
        $offering = $this->offering($this->subject('WEB101'), $this->semester('2025-2026', 1), 'TN01');
        $student = User::factory()->create(['role' => 'student']);
        Enrollment::create(['course_offering_id' => $offering->id, 'user_id' => $student->id]);

        $this->expectException(UniqueConstraintViolationException::class);
        Enrollment::create(['course_offering_id' => $offering->id, 'user_id' => $student->id]);
    }

    public function test_class_row_has_adviser_and_semester_and_no_longer_a_room(): void
    {
        $this->assertFalse(Schema::hasColumn('classes', 'room'), 'Phòng học thuộc về lớp học phần, không thuộc về lớp hành chính.');
        $this->assertTrue(Schema::hasColumn('classes', 'adviser_id'));
        $this->assertTrue(Schema::hasColumn('classes', 'semester_id'));
    }

    public function test_legacy_registration_table_and_score_columns_are_gone(): void
    {
        $this->assertFalse(Schema::hasTable('student_subject'), 'Đăng ký môn học chỉ còn đường enrollments.');
        $this->assertFalse(Schema::hasColumn('grades', 'subject_id'));
        $this->assertFalse(Schema::hasColumn('grades', 'score'));
        $this->assertTrue(Schema::hasColumn('grades', 'course_offering_id'));
        $this->assertTrue(Schema::hasColumn('grades', 'final_score'));
    }

    public function test_one_student_holds_exactly_one_grade_per_offering(): void
    {
        $offering = $this->singleOffering('WEB101');
        $student = User::factory()->create(['role' => 'student']);
        $this->enroll($student, $offering);
        Grade::create(['user_id' => $student->id, 'course_offering_id' => $offering->id, 'final_score' => 8]);

        try {
            Grade::create(['user_id' => $student->id, 'course_offering_id' => $offering->id, 'final_score' => 9]);
            $this->fail('Unique (course_offering_id, user_id) phải chặn hai điểm cùng lớp học phần.');
        } catch (UniqueConstraintViolationException $e) {
            $this->assertSame(1, Grade::count());
        }
    }

    public function test_admin_assigns_a_homeroom_teacher_from_the_class_form(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $teacher = User::factory()->create(['role' => 'teacher', 'name' => 'Nguyễn Văn Đạt', 'teacher_code' => 'GV01']);

        $this->actingAs($admin)->post('/admin/classes', [
            'class_code' => 'CTK46A',
            'class_name' => 'Công nghệ thông tin 46A',
            'intake_year' => 2026,
            'adviser_id' => $teacher->id,
        ])->assertRedirect('/admin/classes')->assertSessionHas('success');

        $class = StudentClass::firstWhere('class_code', 'CTK46A');
        $this->assertSame(2026, $class->intake_year);
        $this->assertSame($teacher->id, $class->adviser_id);
        $this->assertSame('Nguyễn Văn Đạt (GV01)', $class->adviser_label);
    }

    public function test_class_form_offers_only_teachers_and_blocks_a_non_teacher_adviser(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $teacher = User::factory()->create(['role' => 'teacher', 'name' => 'Trần Thị Kim Oanh']);
        $student = User::factory()->create(['role' => 'student', 'name' => 'Sinh viên A']);

        $page = $this->actingAs($admin)->get('/admin/classes/create')->getContent();
        $this->assertStringContainsString('Giảng viên chủ nhiệm', $page);
        $this->assertStringNotContainsString($student->name, $page);

        $this->actingAs($admin)->post('/admin/classes', [
            'class_code' => 'CTK46B',
            'class_name' => 'Công nghệ thông tin 46B',
            'adviser_id' => $student->id,
        ])->assertSessionHasErrors('adviser_id');

        $this->assertDatabaseMissing('classes', ['class_code' => 'CTK46B']);
        $this->assertNotNull($teacher->id);
    }

    public function test_class_major_has_to_sit_inside_the_selected_department(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $cntt = Department::create(['code' => 'CNTT', 'name' => 'Khoa Công nghệ thông tin']);
        $khtn = Department::create(['code' => 'KHTN', 'name' => 'Khoa Khoa học cơ bản']);
        $ktpm = Major::create(['department_id' => $cntt->id, 'code' => 'KTPM', 'name' => 'Kỹ thuật phần mềm']);

        $this->actingAs($admin)->post('/admin/classes', [
            'class_code' => 'CTK46C',
            'class_name' => 'Công nghệ thông tin 46C',
            'department_id' => $khtn->id,
            'major_id' => $ktpm->id,
        ])->assertSessionHasErrors('major_id');

        $this->actingAs($admin)->post('/admin/classes', [
            'class_code' => 'CTK46C',
            'class_name' => 'Công nghệ thông tin 46C',
            'department_id' => $cntt->id,
            'major_id' => $ktpm->id,
        ])->assertSessionHasNoErrors();

        $this->assertSame($cntt->id, StudentClass::firstWhere('class_code', 'CTK46C')->department_id);
    }

    public function test_class_list_shows_intake_department_adviser_and_size(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $khoaCntt = Department::create(['code' => 'CNTT', 'name' => 'Khoa Công nghệ thông tin']);
        $adviser = User::factory()->create(['role' => 'teacher', 'name' => 'Lê Thị Vàng']);
        $class = StudentClass::create([
            'class_code' => 'CTK44A',
            'class_name' => 'Công nghệ thông tin 44A',
            'intake_year' => 2024,
            'department_id' => $khoaCntt->id,
            'adviser_id' => $adviser->id,
        ]);
        User::factory()->count(3)->create(['role' => 'student', 'class_id' => $class->id]);

        $page = $this->actingAs($admin)->get('/admin/classes')->getContent();

        $this->assertStringContainsString('Khóa', $page);
        $this->assertStringContainsString('Khoa Công nghệ thông tin', $page);
        $this->assertStringContainsString('Lê Thị Vàng', $page);
        $this->assertStringContainsString('2024', $page);
        $this->assertStringNotContainsString('Phòng', $page, 'Cột Phòng đã bỏ khỏi lớp hành chính.');
    }

    public function test_admin_creates_a_semester_with_a_generated_slug_and_a_gate(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post('/admin/semesters', [
            'academic_year' => '2025-2026',
            'term' => 2,
            'starts_at' => '2026-01-01',
            'ends_at' => '2026-05-31',
            'registration_starts_at' => '2025-12-15T08:00',
            'registration_ends_at' => '2026-01-10T17:00',
        ])->assertRedirect('/admin/semesters')->assertSessionHas('success');

        $semester = Semester::sole();
        $this->assertSame('HK2-2025-2026', $semester->slug);
        $this->assertTrue($semester->is_active, 'Học kỳ đầu tiên của hệ thống mặc nhiên đang chạy.');
        $this->assertSame('2025-12-15 08:00', $semester->registration_starts_at->format('Y-m-d H:i'));
        $this->assertSame('2026-01-10 17:00', $semester->registration_ends_at->format('Y-m-d H:i'));
    }

    public function test_one_academic_year_cannot_hold_the_same_term_twice(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $payload = [
            'academic_year' => '2025-2026',
            'term' => 1,
            'starts_at' => '2025-09-01',
            'ends_at' => '2026-01-31',
        ];

        $this->actingAs($admin)->post('/admin/semesters', $payload)->assertSessionHasNoErrors();
        $this->actingAs($admin)->post('/admin/semesters', $payload)
            ->assertSessionHasErrors('term');

        $this->assertStringContainsString('đã tồn tại', (string) session('errors')->first('term'));
        $this->assertSame(1, Semester::count());
    }

    public function test_the_registration_gate_needs_both_ends_in_the_right_order(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post('/admin/semesters', [
            'academic_year' => '2025-2026',
            'term' => 1,
            'starts_at' => '2025-09-01',
            'ends_at' => '2026-01-31',
            'registration_starts_at' => '2025-08-15T08:00',
        ])->assertSessionHasErrors('registration_ends_at');

        $this->actingAs($admin)->post('/admin/semesters', [
            'academic_year' => '2025-2026',
            'term' => 1,
            'starts_at' => '2025-09-01',
            'ends_at' => '2026-01-31',
            'registration_starts_at' => '2025-08-20T08:00',
            'registration_ends_at' => '2025-08-19T08:00',
        ])->assertSessionHasErrors('registration_ends_at');

        $this->assertSame(0, Semester::count());
    }

    public function test_editing_the_year_and_term_rebuilds_the_slug(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $semester = $this->semester('2025-2026', 1);

        $this->actingAs($admin)->put('/admin/semesters/'.$semester->id, [
            'academic_year' => '2026-2027',
            'term' => 3,
            'starts_at' => '2027-06-01',
            'ends_at' => '2027-08-31',
        ])->assertRedirect('/admin/semesters')->assertSessionHas('success');

        $this->assertSame('HK3-2026-2027', $semester->fresh()->slug, 'Mã kỳ phải theo đúng năm học và kỳ mới.');
    }

    public function test_the_active_semester_is_not_deletable(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $current = $this->semester();

        $this->actingAs($admin)->delete('/admin/semesters/'.$current->id)
            ->assertRedirect('/admin/semesters')
            ->assertSessionHas('error');

        $this->assertDatabaseHas('semesters', ['id' => $current->id]);
        $this->assertStringContainsString('học kỳ hiện hành', (string) session('error'));
    }

    public function test_admin_moves_the_active_semester_from_the_list(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $first = $this->semester('2025-2026', 1);
        $second = $this->semester('2025-2026', 2);

        $this->actingAs($admin)->post('/admin/semesters/'.$second->id.'/activate')
            ->assertRedirect('/admin/semesters')
            ->assertSessionHas('success');

        $this->assertSame($second->id, Semester::current()->id);
        $this->assertSame(1, Semester::where('is_active', true)->count());
    }

    public function test_a_semester_still_used_by_an_offering_cannot_be_deleted(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $semester = $this->semester('2025-2026', 1);
        $this->offering($this->subject('WEB101'), $semester);
        Semester::activate($this->semester('2025-2026', 2)->id);

        $this->actingAs($admin)->delete('/admin/semesters/'.$semester->id)
            ->assertRedirect('/admin/semesters')
            ->assertSessionHas('error');

        $this->assertDatabaseHas('semesters', ['id' => $semester->id]);
        $this->assertStringContainsString('chưa xóa được', (string) session('error'));
    }

    public function test_admin_opens_two_groups_of_one_subject_for_the_same_semester(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $teacherA = User::factory()->create(['role' => 'teacher', 'name' => 'Nguyễn Văn Đạt', 'teacher_code' => 'GV01']);
        $teacherB = User::factory()->create(['role' => 'teacher', 'name' => 'Trần Thị Kim Oanh', 'teacher_code' => 'GV02']);
        $subject = $this->subject('WEB101');
        $semester = $this->semester();

        foreach ([['TN01', $teacherA, 'A101', 30], ['TN02', $teacherB, 'A102', 0]] as [$group, $teacher, $room, $max]) {
            $this->actingAs($admin)->post('/admin/course-offerings', [
                'subject_id' => $subject->id,
                'semester_id' => $semester->id,
                'group_code' => $group,
                'teacher_id' => $teacher->id,
                'room' => $room,
                'max_students' => $max,
                'is_open' => '1',
            ])->assertRedirect('/admin/course-offerings')->assertSessionHas('success');
        }

        $offerings = CourseOffering::with('teacher')->get()->keyBy('group_code');
        $this->assertSame($teacherA->id, $offerings['TN01']->teacher_id);
        $this->assertSame($teacherB->id, $offerings['TN02']->teacher_id);
        $this->assertSame('A101', $offerings['TN01']->room);
        $this->assertSame(0, $offerings['TN02']->max_students);
    }

    public function test_a_duplicate_group_is_rejected_with_a_message_not_a_sql_error(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $subject = $this->subject('WEB101');
        $semester = $this->semester();
        $this->offering($subject, $semester, 'TN01');

        $this->actingAs($admin)->post('/admin/course-offerings', [
            'subject_id' => $subject->id,
            'semester_id' => $semester->id,
            'group_code' => 'TN01',
            'max_students' => 30,
        ])->assertSessionHasErrors('group_code');

        $this->assertSame(1, CourseOffering::count());
        $this->assertStringContainsString(
            'đã được mở trong học kỳ này',
            (string) session('errors')->first('group_code'),
        );
    }

    public function test_only_a_teacher_can_be_put_in_charge_of_an_offering(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $subject = $this->subject('WEB101');
        $semester = $this->semester();
        $student = User::factory()->create(['role' => 'student', 'name' => 'Sinh viên A', 'student_code' => '20240099']);
        $teacher = User::factory()->create(['role' => 'teacher', 'name' => 'Nguyễn Văn Đạt', 'teacher_code' => 'GV01']);

        $page = $this->actingAs($admin)->get('/admin/course-offerings/create')->getContent();
        $this->assertStringContainsString('Nguyễn Văn Đạt (GV01)', $page);
        $this->assertStringNotContainsString('Sinh viên A', $page, 'Danh sách giảng viên phụ trách không lẫn sinh viên.');

        $this->actingAs($admin)->post('/admin/course-offerings', [
            'subject_id' => $subject->id,
            'semester_id' => $semester->id,
            'group_code' => 'TN01',
            'teacher_id' => $student->id,
            'max_students' => 0,
        ])->assertSessionHasErrors('teacher_id');

        $this->assertSame(0, CourseOffering::count());
        $this->assertStringContainsString('không phải giảng viên', (string) session('errors')->first('teacher_id'));
    }

    public function test_the_offering_list_shows_registered_over_capacity_and_the_open_state(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $semester = $this->semester();
        $capped = $this->offering($this->subject('WEB101'), $semester, 'TN01', 2);
        $this->enroll(User::factory()->create(['role' => 'student']), $capped);
        $this->offering($this->subject('DB101'), $semester, 'TN01', 0);
        $this->offering($this->subject('NET101'), $semester, 'TN01', 25, ['is_open' => false]);

        $page = $this->actingAs($admin)->get('/admin/course-offerings')->getContent();

        $this->assertStringContainsString('1/2', $page);
        $this->assertStringContainsString('Không giới hạn', $page, 'Sĩ số 0 phải đọc được là không giới hạn.');
        $this->assertStringContainsString('Đóng', $page);
        $this->assertStringContainsString('Mở', $page);
    }

    public function test_an_offering_that_still_holds_students_or_grades_cannot_be_deleted(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $semester = $this->semester();

        $withStudents = $this->offering($this->subject('WEB101'), $semester, 'TN01');
        $this->enroll(User::factory()->create(['role' => 'student']), $withStudents);

        $withGrades = $this->offering($this->subject('DB101'), $semester, 'TN01');
        $student = User::factory()->create(['role' => 'student']);
        Grade::create([
            'user_id' => $student->id,
            'course_offering_id' => $withGrades->id,
            'final_score' => 8,
        ]);

        foreach ([$withStudents, $withGrades] as $offering) {
            $this->actingAs($admin)->delete('/admin/course-offerings/'.$offering->id)
                ->assertRedirect('/admin/course-offerings')
                ->assertSessionHas('error');
            $this->assertStringContainsString('Đóng lớp', (string) session('error'));
        }

        $this->assertSame(2, CourseOffering::count());
    }

    public function test_an_offering_with_no_students_and_no_grades_is_deletable(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $offering = $this->offering($this->subject('WEB101'), $this->semester(), 'TN01');

        $this->actingAs($admin)->delete('/admin/course-offerings/'.$offering->id)
            ->assertRedirect('/admin/course-offerings')
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('course_offerings', ['id' => $offering->id]);
    }

    public function test_an_offering_left_with_only_dropped_students_and_no_grades_is_deletable(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $offering = $this->offering($this->subject('WEB101'), $this->semester(), 'TN01');
        $student = User::factory()->create(['role' => 'student']);
        $this->enroll($student, $offering)->update(['status' => 'DROPPED', 'dropped_at' => now()]);

        $this->actingAs($admin)->delete('/admin/course-offerings/'.$offering->id)
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('course_offerings', ['id' => $offering->id]);
        $this->assertSame(0, Enrollment::count(), 'Xóa lớp học phần kéo theo đăng ký đã hủy.');
    }

    public function test_a_teacher_lists_only_the_offerings_assigned_to_them(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher', 'name' => 'Nguyễn Văn Đạt', 'teacher_code' => 'GV01']);
        $other = User::factory()->create(['role' => 'teacher', 'name' => 'Trần Thị Kim Oanh', 'teacher_code' => 'GV02']);
        $semester = $this->semester();

        $mine = $this->offering($this->subject('WEB101'), $semester, 'TN01', 30, ['teacher_id' => $teacher->id]);
        $theirs = $this->offering($this->subject('DB101'), $semester, 'TN02', 20, ['teacher_id' => $other->id]);
        $unassigned = $this->offering($this->subject('NET101'), $semester, 'TN01');
        $this->enroll(User::factory()->create(['role' => 'student']), $mine);

        $page = $this->actingAs($teacher)->get('/teacher/my-offerings')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('WEB101', $page);
        $this->assertStringContainsString('1/30', $page);
        $this->assertStringNotContainsString('DB101', $page, 'Lớp của giảng viên khác không được xuất hiện.');
        $this->assertStringNotContainsString('NET101', $page, 'Lớp chưa phân công cũng không thuộc về giảng viên.');
        $this->assertNotSame($theirs->id, $mine->id);
        $this->assertNotSame($unassigned->id, $mine->id);
    }

    public function test_a_teacher_cannot_reach_the_offering_admin_screens(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);

        $this->actingAs($teacher)->get('/admin/course-offerings')->assertForbidden();
        $this->actingAs($teacher)->post('/admin/course-offerings', [
            'subject_id' => $this->subject('WEB101')->id,
            'semester_id' => $this->semester()->id,
            'group_code' => 'TN01',
        ])->assertForbidden();

        $this->assertSame(0, CourseOffering::where('group_code', 'TN01')->count());
    }

    public function test_the_admin_offering_form_is_the_place_where_a_group_is_edited(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $teacher = User::factory()->create(['role' => 'teacher', 'name' => 'Nguyễn Văn Đạt', 'teacher_code' => 'GV01']);
        $offering = $this->offering($this->subject('WEB101'), $this->semester(), 'TN01', 20);

        $this->actingAs($admin)->put('/admin/course-offerings/'.$offering->id, [
            'subject_id' => $offering->subject_id,
            'semester_id' => $offering->semester_id,
            'group_code' => 'TN02',
            'teacher_id' => $teacher->id,
            'room' => 'B201',
            'max_students' => 25,
            'is_open' => '0',
        ])->assertRedirect('/admin/course-offerings')->assertSessionHas('success');

        $offering->refresh();
        $this->assertSame('TN02', $offering->group_code);
        $this->assertSame($teacher->id, $offering->teacher_id);
        $this->assertSame('B201', $offering->room);
        $this->assertSame(25, $offering->max_students);
        $this->assertFalse($offering->is_open, 'Bỏ chọn "Mở đăng ký" phải đóng lớp.');
    }
}
