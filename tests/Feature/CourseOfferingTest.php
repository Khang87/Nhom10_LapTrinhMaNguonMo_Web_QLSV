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
}
