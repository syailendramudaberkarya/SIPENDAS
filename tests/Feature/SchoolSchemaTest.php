<?php

use App\Enums\PublicationStatus;
use App\Enums\UserRole;
use App\Models\AcademicYear;
use App\Models\Assignment;
use App\Models\Classroom;
use App\Models\LearningInformation;
use App\Models\Material;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->year = AcademicYear::create([
        'name' => '2026/2027',
        'starts_at' => '2026-07-01',
        'ends_at' => '2027-06-30',
        'is_active' => true,
    ]);
    $this->classroom = $this->year->classrooms()->create([
        'name' => 'Kelas 5A', 'grade' => 5, 'section' => 'A',
    ]);
    $this->teacher = Teacher::create(['user_id' => User::factory()->teacher()->create()->id]);
    $this->subject = Subject::create(['name' => 'Matematika', 'code' => 'MTK']);
    $this->teaching = TeachingAssignment::create([
        'teacher_id' => $this->teacher->id,
        'classroom_id' => $this->classroom->id,
        'subject_id' => $this->subject->id,
    ]);
});

test('school relationships connect accounts classrooms and teaching resources', function () {
    $user = User::factory()->create();
    $student = Student::create([
        'user_id' => $user->id, 'classroom_id' => $this->classroom->id, 'nis' => '20260001',
    ]);
    $schedule = $this->teaching->schedules()->create([
        'day_of_week' => 1, 'starts_at' => '08:00:00', 'ends_at' => '09:00:00',
    ]);
    $material = $this->teaching->materials()->create(['title' => 'Pecahan', 'content' => 'Materi pecahan.']);
    $assignment = $this->teaching->assignments()->create(['title' => 'Latihan pecahan', 'description' => 'Kerjakan latihan.']);
    $information = $this->teaching->learningInformation()->create(['title' => 'Persiapan belajar', 'content' => 'Bawa buku.']);

    expect($student->user->is($user))->toBeTrue()
        ->and($user->student->is($student))->toBeTrue()
        ->and($this->teacher->user->teacher->is($this->teacher))->toBeTrue()
        ->and($student->classroom->academicYear->is($this->year))->toBeTrue()
        ->and($this->classroom->students()->count())->toBe(1)
        ->and($this->teacher->teachingAssignments()->count())->toBe(1)
        ->and($this->subject->teachingAssignments()->count())->toBe(1)
        ->and($this->classroom->teachingAssignments()->count())->toBe(1)
        ->and($material->teachingAssignment->teacher->is($this->teacher))->toBeTrue()
        ->and($assignment->teachingAssignment->subject->is($this->subject))->toBeTrue()
        ->and($information->teachingAssignment->classroom->is($this->classroom))->toBeTrue()
        ->and($schedule->teachingAssignment->is($this->teaching))->toBeTrue();
});

test('duplicate teaching assignments are rejected by the database', function () {
    TeachingAssignment::create($this->teaching->only(['teacher_id', 'classroom_id', 'subject_id']));
})->throws(QueryException::class);

test('classrooms without sections still have a unique grade per academic year', function () {
    $this->year->classrooms()->create(['name' => 'Kelas 6', 'grade' => 6]);
    $this->year->classrooms()->create(['name' => 'Kelas Enam', 'grade' => 6]);
})->throws(QueryException::class);

test('a classroom name can be reused in another academic year', function () {
    $year = AcademicYear::create(['name' => '2027/2028', 'starts_at' => '2027-07-01', 'ends_at' => '2028-06-30']);
    $year->classrooms()->create(['name' => 'Kelas 5A', 'grade' => 5, 'section' => 'A']);

    expect(Classroom::where('name', 'Kelas 5A')->count())->toBe(2);
});

test('referenced master data cannot be permanently deleted', function (string $model) {
    $record = match ($model) {
        'teacher' => $this->teacher,
        'classroom' => $this->classroom,
        'subject' => $this->subject,
        'year' => $this->year,
        'user' => $this->teacher->user,
    };

    $record->delete();
})->with(['teacher', 'classroom', 'subject', 'year', 'user'])->throws(QueryException::class);

test('foreign keys reject a nonexistent teaching assignment', function () {
    Material::create(['teaching_assignment_id' => 999999, 'title' => 'Tidak valid', 'content' => 'Konten']);
})->throws(QueryException::class);

test('student identifiers are unique', function () {
    Student::create(['user_id' => User::factory()->create()->id, 'nis' => '20260001']);
    Student::create(['user_id' => User::factory()->create()->id, 'nis' => '20260001']);
})->throws(QueryException::class);

test('each account has at most one teacher profile', function () {
    Teacher::create(['user_id' => $this->teacher->user_id]);
})->throws(QueryException::class);

test('learning content uses drafts by default and soft deletion preserves records', function (string $model) {
    $record = $model::create([
        'teaching_assignment_id' => $this->teaching->id,
        'title' => 'Contoh', 'content' => 'Isi materi', 'description' => 'Isi tugas',
    ]);
    $record->refresh();

    expect($record->status)->toBe(PublicationStatus::Draft)
        ->and($record->published_at)->toBeNull();

    $record->delete();

    expect($model::find($record->id))->toBeNull()
        ->and($model::withTrashed()->find($record->id))->not->toBeNull();
})->with([Material::class, Assignment::class, LearningInformation::class]);

test('a teaching assignment with content cannot be deleted', function () {
    $this->teaching->materials()->create(['title' => 'Materi', 'content' => 'Isi']);
    $this->teaching->delete();
})->throws(QueryException::class);

test('duplicate schedule slots are rejected', function () {
    $slot = ['teaching_assignment_id' => $this->teaching->id, 'day_of_week' => 1, 'starts_at' => '08:00:00', 'ends_at' => '09:00:00'];
    Schedule::create($slot);
    Schedule::create($slot);
})->throws(QueryException::class);

test('account access fields cannot be escalated through mass assignment', function () {
    $user = User::factory()->create();
    $user->fill(['name' => 'Nama baru', 'role' => 'administrator', 'is_active' => false])->save();

    expect($user->refresh()->role)->toBe(UserRole::Student)
        ->and($user->is_active)->toBeTrue()
        ->and($user->name)->toBe('Nama baru')
        ->and(Hash::check('password', $user->password))->toBeTrue();
});
