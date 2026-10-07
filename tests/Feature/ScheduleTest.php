<?php

use App\Enums\UserRole;
use App\Models\AcademicYear;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Services\ScheduleService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->administrator()->create();
    $this->actingAs($this->admin);
    $this->year = AcademicYear::create(['name' => '2026/2027', 'starts_at' => '2026-07-01', 'ends_at' => '2027-06-30', 'is_active' => true]);
    $this->classroom = $this->year->classrooms()->create(['name' => 'Kelas 5A', 'grade' => 5, 'section' => 'A']);
    $this->otherClassroom = $this->year->classrooms()->create(['name' => 'Kelas 6A', 'grade' => 6, 'section' => 'A']);
    $this->teacherUser = User::factory()->teacher()->create(['name' => 'Guru Satu']);
    $this->otherTeacherUser = User::factory()->teacher()->create(['name' => 'Guru Dua']);
    $this->teacher = Teacher::create(['user_id' => $this->teacherUser->id]);
    $this->otherTeacher = Teacher::create(['user_id' => $this->otherTeacherUser->id]);
    $this->subject = Subject::create(['name' => 'Matematika']);
    $this->otherSubject = Subject::create(['name' => 'IPA']);
    $this->teaching = TeachingAssignment::create(['teacher_id' => $this->teacher->id, 'classroom_id' => $this->classroom->id, 'subject_id' => $this->subject->id]);
    $this->sameTeacher = TeachingAssignment::create(['teacher_id' => $this->teacher->id, 'classroom_id' => $this->otherClassroom->id, 'subject_id' => $this->otherSubject->id]);
    $this->sameClassroom = TeachingAssignment::create(['teacher_id' => $this->otherTeacher->id, 'classroom_id' => $this->classroom->id, 'subject_id' => $this->otherSubject->id]);
    $this->unrelated = TeachingAssignment::create(['teacher_id' => $this->otherTeacher->id, 'classroom_id' => $this->otherClassroom->id, 'subject_id' => $this->subject->id]);
    $this->studentUser = User::factory()->create();
    Student::create(['user_id' => $this->studentUser->id, 'classroom_id' => $this->classroom->id, 'nis' => 'NIS0001']);
    $this->schedule = $this->teaching->schedules()->create(['day_of_week' => 1, 'starts_at' => '08:00:00', 'ends_at' => '09:00:00']);
    $this->payload = ['teaching_assignment_id' => $this->teaching->id, 'day_of_week' => 1, 'starts_at' => '09:00', 'ends_at' => '10:00'];
});

test('administrator can view schedule forms and complete CRUD', function () {
    $this->get(route('administrator.schedules.index'))->assertOk()->assertSee('Senin')->assertSee('08:00');
    $this->get(route('administrator.schedules.create'))->assertOk()->assertSee('name="_token"', false);
    $this->get(route('administrator.schedules.edit', $this->schedule))->assertOk()->assertSee('value="08:00"', false);
    $this->post(route('administrator.schedules.store'), [...$this->payload, 'teacher_id' => $this->otherTeacher->id])
        ->assertRedirect(route('administrator.schedules.index'))->assertSessionHasNoErrors();
    $record = Schedule::where('starts_at', '09:00:00')->firstOrFail();
    expect($record->teachingAssignment->teacher_id)->toBe($this->teacher->id);
    $this->put(route('administrator.schedules.update', $record), [...$this->payload, 'ends_at' => '10:30'])
        ->assertRedirect()->assertSessionHasNoErrors();
    expect($record->fresh()->ends_at)->toBe('10:30:00');
    $this->delete(route('administrator.schedules.destroy', $record))->assertRedirect()->assertSessionHasNoErrors();
    expect(Schedule::find($record->id))->toBeNull();
});

test('overlapping intervals are rejected for the same teacher or classroom', function (string $relation, string $start, string $end) {
    $teaching = match ($relation) {
        'teacher' => $this->sameTeacher,
        'classroom' => $this->sameClassroom,
        default => $this->teaching,
    };
    $this->post(route('administrator.schedules.store'), [
        ...$this->payload, 'teaching_assignment_id' => $teaching->id, 'starts_at' => $start, 'ends_at' => $end,
    ])->assertSessionHasErrors(['starts_at' => 'Jadwal bentrok dengan jadwal kelas atau guru pada hari dan rentang waktu yang sama.']);
    expect(Schedule::count())->toBe(1);
})->with([
    ['teacher', '08:30', '09:30'], ['classroom', '08:30', '09:30'],
    ['same', '08:00', '09:00'], ['teacher', '07:30', '08:30'],
    ['teacher', '08:15', '08:45'], ['classroom', '07:30', '09:30'],
]);

test('adjacent intervals independent resources and different days are allowed', function (string $kind) {
    $payload = match ($kind) {
        'after' => $this->payload,
        'before' => [...$this->payload, 'starts_at' => '07:00', 'ends_at' => '08:00'],
        'independent' => [...$this->payload, 'teaching_assignment_id' => $this->unrelated->id, 'starts_at' => '08:00', 'ends_at' => '09:00'],
        'another-day' => [...$this->payload, 'day_of_week' => 2, 'starts_at' => '08:00', 'ends_at' => '09:00'],
    };
    $this->post(route('administrator.schedules.store'), $payload)->assertRedirect()->assertSessionHasNoErrors();
    expect(Schedule::count())->toBe(2);
})->with(['after', 'before', 'independent', 'another-day']);

test('updates ignore their own interval but reject conflicts without changing data', function () {
    $original = [...$this->payload, 'starts_at' => '08:00', 'ends_at' => '09:00'];
    $this->put(route('administrator.schedules.update', $this->schedule), $original)->assertRedirect()->assertSessionHasNoErrors();
    $record = app(ScheduleService::class)->save($this->payload);
    $this->put(route('administrator.schedules.update', $record), [...$this->payload, 'starts_at' => '08:30'])
        ->assertSessionHasErrors('starts_at');
    expect($record->fresh()->starts_at)->toBe('09:00:00');
});

test('schedule input has Indonesian server validation', function (array $overrides, string $field) {
    $this->post(route('administrator.schedules.store'), [...$this->payload, ...$overrides])->assertSessionHasErrors($field);
    expect(Schedule::count())->toBe(1);
})->with([
    [['teaching_assignment_id' => null], 'teaching_assignment_id'],
    [['teaching_assignment_id' => 999999], 'teaching_assignment_id'],
    [['teaching_assignment_id' => ['bad']], 'teaching_assignment_id'],
    [['day_of_week' => 0], 'day_of_week'], [['day_of_week' => 8], 'day_of_week'], [['day_of_week' => ['bad']], 'day_of_week'],
    [['starts_at' => '25:00'], 'starts_at'], [['starts_at' => '09:00:00'], 'starts_at'], [['starts_at' => ['bad']], 'starts_at'],
    [['ends_at' => '09:00'], 'ends_at'], [['ends_at' => '08:00'], 'ends_at'], [['ends_at' => null], 'ends_at'],
]);

test('inactive teaching relationships cannot receive schedules', function (string $type) {
    match ($type) {
        'teacher' => $this->teacher->update(['status' => 'inactive']),
        'account' => $this->teacherUser->forceFill(['is_active' => false])->save(),
        'classroom' => $this->classroom->update(['is_active' => false]),
        'subject' => $this->subject->update(['is_active' => false]),
        'year' => $this->year->update(['is_active' => false]),
    };
    $this->post(route('administrator.schedules.store'), $this->payload)->assertSessionHasErrors('teaching_assignment_id');
})->with(['teacher', 'account', 'classroom', 'subject', 'year']);

test('schedule service rechecks active teaching data when called directly', function () {
    $this->year->update(['is_active' => false]);
    app(ScheduleService::class)->save($this->payload);
})->throws(ValidationException::class);

test('historical schedules in another academic year do not block current schedules', function () {
    $year = AcademicYear::create(['name' => '2025/2026', 'starts_at' => '2025-07-01', 'ends_at' => '2026-06-30']);
    $classroom = $year->classrooms()->create(['name' => 'Kelas 5A', 'grade' => 5, 'section' => 'A']);
    $teaching = TeachingAssignment::create(['teacher_id' => $this->teacher->id, 'classroom_id' => $classroom->id, 'subject_id' => $this->subject->id]);
    $teaching->schedules()->create(['day_of_week' => 1, 'starts_at' => '09:00:00', 'ends_at' => '10:00:00']);
    $this->post(route('administrator.schedules.store'), $this->payload)->assertRedirect()->assertSessionHasNoErrors();
});

test('teachers and students cannot mutate administrative schedules', function (UserRole $role) {
    $this->actingAs(User::factory()->create(['role' => $role]));
    foreach (['index', 'create'] as $action) {
        $this->get(route('administrator.schedules.'.$action))->assertForbidden();
    }
    $this->get(route('administrator.schedules.edit', $this->schedule))->assertForbidden();
    $this->post(route('administrator.schedules.store'), $this->payload)->assertForbidden();
    $this->put(route('administrator.schedules.update', $this->schedule), $this->payload)->assertForbidden();
    $this->delete(route('administrator.schedules.destroy', $this->schedule))->assertForbidden();
    expect(Gate::forUser($this->teacherUser)->allows('update', $this->schedule))->toBeFalse()
        ->and(Gate::forUser($this->studentUser)->allows('delete', $this->schedule))->toBeFalse();
})->with([UserRole::Teacher, UserRole::Student]);

test('teacher lists and details contain only schedules assigned to that teacher', function () {
    $other = $this->unrelated->schedules()->create(['day_of_week' => 1, 'starts_at' => '08:00:00', 'ends_at' => '09:00:00']);
    $this->actingAs($this->teacherUser)->get(route('teacher.schedules.index'))->assertOk()
        ->assertViewHas('schedules', fn ($records) => $records->total() === 1 && $records[0]->id === $this->schedule->id);
    $this->get(route('teacher.schedules.show', $this->schedule))->assertOk()->assertSee('Matematika');
    $this->get(route('teacher.schedules.show', $other))->assertForbidden();
});

test('student schedules include all teachers in their class and exclude other classrooms', function () {
    $sameClass = $this->sameClassroom->schedules()->create(['day_of_week' => 2, 'starts_at' => '08:00:00', 'ends_at' => '09:00:00']);
    $otherClass = $this->unrelated->schedules()->create(['day_of_week' => 1, 'starts_at' => '08:00:00', 'ends_at' => '09:00:00']);
    $this->actingAs($this->studentUser)->get(route('student.schedules.index'))->assertOk()
        ->assertViewHas('schedules', fn ($records) => $records->total() === 2);
    $this->get(route('student.schedules.show', $sameClass))->assertOk()->assertSee('Guru Dua');
    $this->get(route('student.schedules.show', $otherClass))->assertForbidden();
    $this->get(route('student.schedules.index', ['q' => 'Kelas 6A']))->assertOk()
        ->assertViewHas('schedules', fn ($records) => $records->total() === 0);
});

test('accounts without eligible profiles receive empty listings and cannot open details', function (UserRole $role) {
    $user = User::factory()->create(['role' => $role]);
    $this->actingAs($user)->get(route($role->value.'.schedules.index'))->assertOk()
        ->assertViewHas('schedules', fn ($records) => $records->total() === 0);
    $this->get(route($role->value.'.schedules.show', $this->schedule))->assertForbidden();
})->with([UserRole::Teacher, UserRole::Student]);

test('students cannot see inactive or historical teaching data', function (string $type) {
    match ($type) {
        'teacher' => $this->teacher->update(['status' => 'inactive']),
        'classroom' => $this->classroom->update(['is_active' => false]),
        'subject' => $this->subject->update(['is_active' => false]),
        'year' => $this->year->update(['is_active' => false]),
        'student' => $this->studentUser->student->update(['status' => 'transferred']),
    };
    $this->actingAs($this->studentUser->fresh())->get(route('student.schedules.index'))->assertOk()
        ->assertViewHas('schedules', fn ($records) => $records->total() === 0);
    $this->get(route('student.schedules.show', $this->schedule))->assertForbidden();
})->with(['teacher', 'classroom', 'subject', 'year', 'student']);

test('schedule filters are validated and applied before pagination', function () {
    $this->actingAs($this->studentUser)->get(route('student.schedules.index', ['day_of_week' => 1, 'q' => 'Matematika']))
        ->assertOk()->assertViewHas('schedules', fn ($records) => $records->total() === 1);
    $this->get(route('student.schedules.index', ['day_of_week' => 2]))->assertOk()
        ->assertViewHas('schedules', fn ($records) => $records->total() === 0);
    $this->get(route('student.schedules.index', ['day_of_week' => 8]))->assertSessionHasErrors('day_of_week');
    $this->get(route('student.schedules.index', ['q' => ['bad']]))->assertSessionHasErrors('q');
    $this->get(route('student.schedules.index', ['page' => 0]))->assertSessionHasErrors('page');
});

test('today schedules use school timezone and remain scoped to the user', function () {
    $this->travelTo(Carbon::parse('2026-10-04 17:30:00', 'UTC'));
    expect(now()->isoWeekday())->toBe(1);
    $this->actingAs($this->studentUser)->get(route('student.dashboard'))->assertOk()
        ->assertViewHas('todaySchedules', fn ($records) => $records->count() === 1 && $records[0]->id === $this->schedule->id);
    $this->actingAs($this->otherTeacherUser)->get(route('teacher.dashboard'))->assertOk()
        ->assertViewHas('todaySchedules', fn ($records) => $records->isEmpty());
});

test('schedule text is escaped and CSRF protects mutations', function () {
    $this->subject->update(['name' => '<script>alert(1)</script>']);
    $this->actingAs($this->studentUser)->get(route('student.schedules.show', $this->schedule))->assertOk()
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
    $this->actingAs($this->admin);
    $this->app['env'] = 'local';
    $this->post(route('administrator.schedules.store'), $this->payload)->assertStatus(419);
    $this->delete(route('administrator.schedules.destroy', $this->schedule))->assertStatus(419);
});

test('guests cannot read schedule lists or details', function () {
    Auth::logout();
    $this->get(route('teacher.schedules.index'))->assertRedirect(route('login'));
    $this->get(route('student.schedules.show', $this->schedule))->assertRedirect(route('login'));
});

test('schedule lists paginate results on the server', function () {
    for ($i = 0; $i < 20; $i++) {
        $start = sprintf('%02d:%02d:00', 10 + intdiv($i, 6), ($i % 6) * 10);
        $end = sprintf('%02d:%02d:00', 10 + intdiv($i, 6), ($i % 6) * 10 + 5);
        $this->teaching->schedules()->create(['day_of_week' => 1, 'starts_at' => $start, 'ends_at' => $end]);
    }
    $this->actingAs($this->studentUser)->get(route('student.schedules.index', ['q' => 'Matematika', 'day_of_week' => 1]))
        ->assertOk()->assertViewHas('schedules', fn ($records) => $records->count() === 15 && $records->total() === 21);
    $this->get(route('student.schedules.index', ['q' => 'Matematika', 'day_of_week' => 1, 'page' => 2]))
        ->assertOk()->assertViewHas('schedules', fn ($records) => $records->count() === 6);
});

test('reader role URLs cannot be crossed manually', function () {
    $this->actingAs($this->teacherUser)->get(route('student.schedules.show', $this->schedule))->assertForbidden();
    $this->actingAs($this->studentUser)->get(route('teacher.schedules.index'))->assertForbidden();
});

test('disabled accounts cannot access schedules even through the policy', function () {
    $this->teacherUser->is_active = false;
    $this->teacherUser->save();
    expect(Gate::forUser($this->teacherUser)->allows('view', $this->schedule))->toBeFalse();
    $this->actingAs($this->teacherUser)->get(route('teacher.schedules.index'))->assertRedirect(route('login'));
});
