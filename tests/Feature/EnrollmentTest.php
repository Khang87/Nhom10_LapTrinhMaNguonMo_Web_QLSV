<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\OfferingFixtures;
use Tests\Feature\Concerns\RendersHtml;
use Tests\TestCase;

/**
 * Cổng đăng ký học phần (nhóm yêu cầu 5): mọi từ chối phải kèm lý do đọc được,
 * không được để hai sinh viên cùng chiếm một chỗ.
 */
class EnrollmentTest extends TestCase
{
    use OfferingFixtures, RefreshDatabase, RendersHtml;

    public function test_a_student_registers_while_the_gate_is_open(): void
    {
        $gate = $this->gate();
        $offering = $this->offering($this->subject('WEB101'), $gate);
        $student = $this->student();

        $this->actingAs($student)->post(route('student.course-offerings.enroll', $offering))
            ->assertRedirect(route('student.course-offerings.index'))
            ->assertSessionHas('success');

        $enrollment = Enrollment::where('course_offering_id', $offering->id)
            ->where('user_id', $student->id)
            ->sole();

        $this->assertSame('ACTIVE', $enrollment->status);
        $this->assertSame($student->id, $enrollment->enrolled_by, 'Sinh viên tự đăng ký thì vết thuộc về chính họ.');
    }

    public function test_a_post_before_the_gate_opens_is_refused_with_422(): void
    {
        $gate = $this->gate([
            'registration_starts_at' => now()->addDay(),
            'registration_ends_at' => now()->addWeek(),
        ]);
        $offering = $this->offering($this->subject('WEB101'), $gate);

        $this->actingAs($this->student())
            ->post(route('student.course-offerings.enroll', $offering))
            ->assertStatus(422)
            ->assertSee('Cổng đăng ký đã đóng');

        $this->assertSame(0, Enrollment::count());
    }

    public function test_a_post_after_the_gate_closes_is_refused_with_422(): void
    {
        $gate = $this->gate();
        $offering = $this->offering($this->subject('WEB101'), $gate);

        $this->travelTo(now()->addDays(3));

        $this->actingAs($this->student())
            ->post(route('student.course-offerings.enroll', $offering))
            ->assertStatus(422)
            ->assertSee('Cổng đăng ký đã đóng');

        $this->assertSame(0, Enrollment::count());
    }

    public function test_a_closed_offering_is_refused_even_inside_the_gate(): void
    {
        $gate = $this->gate();
        $offering = $this->offering($this->subject('WEB101'), $gate, 'TN01', 0, ['is_open' => false]);

        $this->actingAs($this->student())
            ->post(route('student.course-offerings.enroll', $offering))
            ->assertStatus(422)
            ->assertSee('Cổng đăng ký đã đóng');

        $this->assertSame(0, Enrollment::count());
    }

    public function test_an_unmet_prerequisite_is_refused_and_names_the_subject_code(): void
    {
        $gate = $this->gate();
        $db = $this->subject('DB101');
        $web = $this->subject('WEB101');
        $web->prerequisites()->attach($db->id);

        $student = $this->student();
        $this->enroll($student, $this->offering($db, $gate, 'TN01'));
        $this->grade($student, $db->offerings()->sole(), 4.9);

        $this->actingAs($student)
            ->post(route('student.course-offerings.enroll', $this->offering($web, $gate, 'TN02')))
            ->assertStatus(422)
            ->assertSee('Chưa học môn tiên quyết: DB101');

        $this->assertSame(1, Enrollment::count(), 'Điểm 4.9 chưa đạt nên WEB101 phải bị chặn.');
    }

    public function test_a_passed_prerequisite_unlocks_the_subject(): void
    {
        $gate = $this->gate();
        $db = $this->subject('DB101');
        $web = $this->subject('WEB101');
        $web->prerequisites()->attach($db->id);

        $dbOffering = $this->offering($db, $gate, 'TN01');
        $student = $this->student();
        $this->enroll($student, $dbOffering);
        $this->grade($student, $dbOffering, 8.5);

        $this->actingAs($student)
            ->post(route('student.course-offerings.enroll', $this->offering($web, $gate, 'TN02')))
            ->assertSessionMissing('errors');

        $this->assertSame(2, Enrollment::where('status', 'ACTIVE')->count());
    }

    public function test_a_concurrent_subject_needs_a_same_term_enrollment_or_a_pass(): void
    {
        $gate = $this->gate();
        $db = $this->subject('DB101');
        $sec = $this->subject('SEC201');
        $sec->concurrents()->attach($db->id);
        $student = $this->student();

        $this->actingAs($student)
            ->post(route('student.course-offerings.enroll', $this->offering($sec, $gate, 'TN01')))
            ->assertStatus(422)
            ->assertSee('Phải đăng ký song hành với môn: DB101');

        $this->enroll($student, $this->offering($db, $gate, 'TN01'));

        $this->actingAs($student)
            ->post(route('student.course-offerings.enroll', $sec->offerings()->sole()))
            ->assertSessionMissing('errors');

        $this->assertSame(2, Enrollment::where('status', 'ACTIVE')->count());
    }

    public function test_the_third_student_is_told_the_class_is_full(): void
    {
        $gate = $this->gate();
        $offering = $this->offering($this->subject('WEB101'), $gate, 'TN01', 2);
        $students = User::factory()->count(3)->create(['role' => 'student']);

        $this->actingAs($students[0])->post(route('student.course-offerings.enroll', $offering))->assertSessionMissing('errors');
        $this->actingAs($students[1])->post(route('student.course-offerings.enroll', $offering))->assertSessionMissing('errors');

        $this->assertSame(0, $offering->fresh()->seatsLeft());

        $this->actingAs($students[2])
            ->post(route('student.course-offerings.enroll', $offering))
            ->assertStatus(422)
            ->assertSee('Lớp đã đủ sĩ số');

        $this->assertSame(2, Enrollment::where('course_offering_id', $offering->id)->count());
    }

    public function test_the_subject_that_overflows_the_credit_cap_is_blocked(): void
    {
        $slots = intdiv((int) config('academic.max_credits_per_term'), 3);
        $gate = $this->gate();
        $student = $this->student();

        for ($i = 1; $i <= $slots; $i++) {
            $offering = $this->offering($this->subject('MA'.sprintf('%03d', $i)), $gate, 'TN01');
            $this->actingAs($student)->post(route('student.course-offerings.enroll', $offering));
        }

        $overflow = $this->offering($this->subject('MA999'), $gate, 'TN01');

        $this->actingAs($student)
            ->post(route('student.course-offerings.enroll', $overflow))
            ->assertStatus(422)
            ->assertSee('Vượt quá số tín chỉ tối đa trong học kỳ');

        $this->assertSame($slots, Enrollment::where('user_id', $student->id)->count());
    }

    public function test_a_second_request_never_creates_two_active_enrollments(): void
    {
        $gate = $this->gate();
        $offering = $this->offering($this->subject('WEB101'), $gate);
        $student = $this->student();

        $this->actingAs($student)->post(route('student.course-offerings.enroll', $offering))->assertSessionMissing('errors');

        $this->actingAs($student)
            ->post(route('student.course-offerings.enroll', $offering))
            ->assertStatus(422)
            ->assertSee('Bạn đã đăng ký lớp này');

        $this->assertSame(1, Enrollment::where('course_offering_id', $offering->id)->count());
    }

    public function test_the_registration_page_keeps_blocked_classes_visible_with_their_reason(): void
    {
        $gate = $this->gate();
        $full = $this->offering($this->subject('WEB101'), $gate, 'TN01', 1);
        $this->offering($this->subject('DB101'), $gate, 'TN01', 0, ['is_open' => false]);
        $this->enroll(User::factory()->create(['role' => 'student']), $full);

        $page = $this->actingAs($this->student())->get('/student/course-offerings');

        $page->assertOk()
            ->assertSee('WEB101')
            ->assertSee('DB101')
            ->assertSee('Lớp đã đủ sĩ số')
            ->assertSee('Cổng đăng ký đã đóng');

        $this->assertSame(2, substr_count($page->getContent(), 'btn btn-sm btn-primary" type="button" disabled'),
            'Mỗi lớp không đủ điều kiện phải hiện nút Đăng ký đã vô hiệu.');
    }

    public function test_the_registration_page_summarises_current_and_accumulated_credits(): void
    {
        $gate = $this->gate();
        $student = $this->student();

        $this->enroll($student, $this->offering($this->subject('WEB101'), $gate, 'TN01'));
        $this->enroll($student, $this->offering($this->subject('DB101'), $gate, 'TN01'));

        $old = Subject::create(['code' => 'OLD101', 'name' => 'Môn cũ', 'credits' => 5]);
        $finished = $this->offering($old, $this->semester('2024-2025', 2));
        $this->grade($student, $finished, 7.0);

        $this->actingAs($student)->get('/student/course-offerings')
            ->assertOk()
            ->assertSee('Tín chỉ đang học: 6 TC')
            ->assertSee('Tín chỉ đã tích luỹ: 5 TC');
    }

    public function test_a_student_drops_a_class_and_the_seat_is_returned_immediately(): void
    {
        $gate = $this->gate();
        $offering = $this->offering($this->subject('WEB101'), $gate, 'TN01', 1);
        $enrollment = $this->enroll($this->student(), $offering);

        $this->assertSame(0, $offering->fresh()->seatsLeft());

        $this->actingAs($enrollment->user)
            ->delete(route('student.enrollments.drop', $enrollment))
            ->assertRedirect(route('student.course-offerings.index'))
            ->assertSessionHas('success');

        $enrollment->refresh();
        $this->assertSame('DROPPED', $enrollment->status);
        $this->assertNotNull($enrollment->dropped_at);
        $this->assertSame(1, $offering->fresh()->seatsLeft());
    }

    public function test_a_student_can_register_again_after_dropping_inside_the_gate(): void
    {
        $gate = $this->gate();
        $offering = $this->offering($this->subject('WEB101'), $gate);
        $student = $this->student();
        $enrollment = $this->enroll($student, $offering);

        $this->actingAs($student)->delete(route('student.enrollments.drop', $enrollment));

        $this->actingAs($student)
            ->post(route('student.course-offerings.enroll', $offering))
            ->assertSessionMissing('errors');

        $this->assertSame('ACTIVE', $enrollment->fresh()->status);
        $this->assertNull($enrollment->fresh()->dropped_at);
    }

    public function test_a_drop_is_refused_once_the_class_has_data_and_the_data_survives(): void
    {
        $gate = $this->gate();
        $subject = $this->subject('WEB101');
        $offering = $this->offering($subject, $gate);
        $student = $this->student();
        $enrollment = $this->enroll($student, $offering);
        Attendance::create(['user_id' => $student->id, 'subject_id' => $subject->id, 'date' => '2026-01-05', 'status' => 'present']);
        $this->grade($student, $offering, 9.0);

        $this->actingAs($student)
            ->delete(route('student.enrollments.drop', $enrollment))
            ->assertSessionHas('error', 'Đã có dữ liệu điểm danh/điểm, liên hệ giảng viên');

        $this->assertSame('ACTIVE', $enrollment->fresh()->status);
        $this->assertSame(1, Grade::count());
        $this->assertSame(1, Attendance::count());
    }

    public function test_a_drop_is_refused_after_the_gate_closes(): void
    {
        $gate = $this->gate();
        $offering = $this->offering($this->subject('WEB101'), $gate);
        $student = $this->student();
        $enrollment = $this->enroll($student, $offering);

        $this->travelTo(now()->addDays(3));

        $this->actingAs($student)
            ->delete(route('student.enrollments.drop', $enrollment))
            ->assertSessionHas('error', 'Cổng đăng ký đã đóng');

        $this->assertSame('ACTIVE', $enrollment->fresh()->status);
    }

    public function test_a_student_cannot_touch_another_students_enrollment(): void
    {
        $gate = $this->gate();
        $enrollment = $this->enroll($this->student(), $this->offering($this->subject('WEB101'), $gate));

        $this->actingAs($this->student('Sinh viên B'))
            ->delete(route('student.enrollments.drop', $enrollment))
            ->assertForbidden();

        $this->assertSame('ACTIVE', $enrollment->fresh()->status);
    }

    public function test_an_admin_registers_on_behalf_across_a_closed_gate_and_leaves_a_trace(): void
    {
        $gate = $this->gate();
        $offering = $this->offering($this->subject('WEB101'), $gate, 'TN01', 1);
        $student = $this->student();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->travelTo(now()->addDays(3));

        $this->actingAs($admin)->post(route('admin.enrollments.store'), [
            'user_id' => $student->id,
            'course_offering_id' => $offering->id,
        ])->assertSessionMissing('errors');

        $enrollment = Enrollment::where('course_offering_id', $offering->id)->where('user_id', $student->id)->sole();
        $this->assertSame('ACTIVE', $enrollment->status);
        $this->assertSame($admin->id, $enrollment->enrolled_by);
    }

    public function test_registering_on_behalf_still_respects_capacity_and_prerequisites(): void
    {
        $gate = $this->gate();
        $admin = User::factory()->create(['role' => 'admin']);
        $taken = $this->offering($this->subject('WEB101'), $gate, 'TN01', 1);
        $this->enroll($this->student(), $taken);

        $this->actingAs($admin)->post(route('admin.enrollments.store'), [
            'user_id' => $this->student('Sinh viên B')->id,
            'course_offering_id' => $taken->id,
        ])->assertStatus(422)->assertSee('Lớp đã đủ sĩ số');

        $db = $this->subject('DB101');
        $sec = $this->subject('SEC201');
        $sec->prerequisites()->attach($db->id);
        $blockedStudent = $this->student('Sinh viên C');

        $this->actingAs($admin)
            ->post(route('admin.enrollments.store'), [
                'user_id' => $blockedStudent->id,
                'course_offering_id' => $this->offering($sec, $gate, 'TN01')->id,
            ])
            ->assertStatus(422)
            ->assertSee('Chưa học môn tiên quyết: DB101');

        $this->assertSame(1, Enrollment::count(), 'Đăng ký hộ chỉ ghi được lớp qua đủ mọi quy tắc.');
        $this->assertSame(0, Enrollment::where('user_id', $blockedStudent->id)->count());
    }

    public function test_an_admin_can_drop_on_behalf_and_the_data_is_kept(): void
    {
        $gate = $this->gate();
        $offering = $this->offering($this->subject('WEB101'), $gate);
        $student = $this->student();
        $enrollment = $this->enroll($student, $offering);
        $this->grade($student, $offering, 9.0);
        $this->travelTo(now()->addDays(3));

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->delete(route('admin.enrollments.drop', $enrollment))
            ->assertSessionHas('success');

        $this->assertSame('DROPPED', $enrollment->fresh()->status);
        $this->assertSame(1, Grade::count(), 'Hủy hộ không được xóa lịch sử điểm.');
    }

    public function test_the_admin_enrollment_list_is_reachable_and_scopes_by_semester(): void
    {
        $current = $this->gate();
        $past = $this->semester('2024-2025', 2);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->enroll($this->student(), $this->offering($this->subject('WEB101'), $current));
        $this->enroll($this->student('Sinh viên B'), $this->offering($this->subject('DB101'), $past));

        $this->actingAs($admin)->get('/admin/enrollments')
            ->assertOk()
            ->assertSee('WEB101')
            ->assertSee('DB101');

        $scoped = $this->actingAs($admin)->get('/admin/enrollments?semester_id='.$past->id)->getContent();
        $rows = $this->rowsOnly($scoped);

        $this->assertStringContainsString('DB101', $rows);
        $this->assertStringNotContainsString('WEB101', $rows, 'Lọc theo học kỳ phải bỏ lớp của kỳ khác khỏi bảng.');
    }

    public function test_a_student_cannot_reach_the_admin_enrollment_screen(): void
    {
        $this->actingAs($this->student())->get('/admin/enrollments')->assertForbidden();
    }
}
