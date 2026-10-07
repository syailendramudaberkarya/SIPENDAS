<?php

use App\Enums\PublicationStatus;
use App\Models\AcademicYear;
use App\Models\Assignment;
use App\Models\LearningInformation;
use App\Models\Material;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

beforeEach(function () {
    $year = AcademicYear::create(['name' => '2026/2027', 'starts_at' => '2026-07-01', 'ends_at' => '2027-06-30', 'is_active' => true]);
    $classroom = $year->classrooms()->create(['name' => 'Kelas 5A', 'grade' => 5, 'section' => 'A']);
    $otherClassroom = $year->classrooms()->create(['name' => 'Kelas 6A', 'grade' => 6, 'section' => 'A']);
    $this->teacher = User::factory()->teacher()->create();
    $this->otherTeacher = User::factory()->teacher()->create();
    $profile = Teacher::create(['user_id' => $this->teacher->id]);
    Teacher::create(['user_id' => $this->otherTeacher->id]);
    $this->student = User::factory()->create();
    $this->otherStudent = User::factory()->create();
    Student::create(['user_id' => $this->student->id, 'classroom_id' => $classroom->id, 'nis' => '20260001']);
    Student::create(['user_id' => $this->otherStudent->id, 'classroom_id' => $otherClassroom->id, 'nis' => '20260002']);
    $this->teaching = TeachingAssignment::create([
        'teacher_id' => $profile->id, 'classroom_id' => $classroom->id,
        'subject_id' => Subject::create(['name' => 'Matematika'])->id,
    ]);
});

test('all content policies enforce teacher ownership and student classroom access', function (string $model) {
    $content = $model::create([
        'teaching_assignment_id' => $this->teaching->id, 'title' => 'Belajar pecahan',
        $model === Assignment::class ? 'description' : 'content' => 'Materi contoh',
        'status' => PublicationStatus::Published, 'published_at' => now()->subMinute(),
    ]);

    expect(Gate::forUser($this->teacher)->allows('create', $model))->toBeTrue()
        ->and(Gate::forUser($this->teacher)->allows('update', $content))->toBeTrue()
        ->and(Gate::forUser($this->teacher)->allows('delete', $content))->toBeTrue()
        ->and(Gate::forUser($this->otherTeacher)->allows('view', $content))->toBeFalse()
        ->and(Gate::forUser($this->otherTeacher)->allows('update', $content))->toBeFalse()
        ->and(Gate::forUser($this->otherTeacher)->allows('delete', $content))->toBeFalse()
        ->and(Gate::forUser($this->otherTeacher)->allows('create', $model))->toBeFalse()
        ->and(Gate::forUser($this->student)->allows('view', $content))->toBeTrue()
        ->and(Gate::forUser($this->student)->allows('download', $content))->toBeTrue()
        ->and(Gate::forUser($this->student)->allows('create', $model))->toBeFalse()
        ->and(Gate::forUser($this->student)->allows('update', $content))->toBeFalse()
        ->and(Gate::forUser($this->otherStudent)->allows('view', $content))->toBeFalse()
        ->and(Gate::forUser($this->otherStudent)->allows('download', $content))->toBeFalse();
})->with([Material::class, Assignment::class, LearningInformation::class]);

test('students cannot view drafts future publications or content without a publication date', function (string $status, ?string $publication) {
    $content = $this->teaching->materials()->create([
        'title' => 'Pecahan', 'content' => 'Materi', 'status' => $status,
        'published_at' => $publication === 'future' ? now()->addDay() : ($publication === 'past' ? now()->subDay() : null),
    ]);

    expect(Gate::forUser($this->student)->allows('view', $content))->toBeFalse()
        ->and(Gate::forUser($this->teacher)->allows('view', $content))->toBeTrue();
})->with([['draft', 'past'], ['published', 'future'], ['published', null]]);

test('inactive users and profiles cannot access learning content', function (string $type) {
    $content = $this->teaching->materials()->create(['title' => 'Pecahan', 'content' => 'Isi', 'status' => 'published', 'published_at' => now()]);
    $user = $type === 'teacher' ? $this->teacher : $this->student;
    if ($type === 'account') {
        $user->is_active = false;
        $user->save();
    } else {
        $user->{$type}->update(['status' => 'inactive']);
        $user->refresh();
    }

    expect(Gate::forUser($user)->allows('view', $content))->toBeFalse();
})->with(['account', 'teacher', 'student']);

test('students and teachers cannot access content in inactive classes years or subjects', function (string $type) {
    $content = $this->teaching->materials()->create(['title' => 'Pecahan', 'content' => 'Isi', 'status' => 'published', 'published_at' => now()]);
    $record = match ($type) {
        'year' => $this->teaching->classroom->academicYear,
        'classroom' => $this->teaching->classroom,
        'subject' => $this->teaching->subject,
    };
    $record->update(['is_active' => false]);

    expect(Gate::forUser($this->student)->allows('view', $content))->toBeFalse()
        ->and(Gate::forUser($this->teacher)->allows('update', $content))->toBeFalse();
})->with(['year', 'classroom', 'subject']);

test('soft deleted content is inaccessible even to administrators', function () {
    $content = $this->teaching->materials()->create(['title' => 'Pecahan', 'content' => 'Isi']);
    $content->delete();

    expect(Gate::forUser($this->teacher)->allows('view', $content))->toBeFalse()
        ->and(Gate::forUser(User::factory()->administrator()->create())->allows('view', $content))->toBeFalse();
});

test('administrator may manage learning content', function () {
    $content = $this->teaching->materials()->create(['title' => 'Pecahan', 'content' => 'Isi']);
    $admin = User::factory()->administrator()->create();

    expect(Gate::forUser($admin)->allows('view', $content))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('update', $content))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('delete', $content))->toBeTrue();
});

test('accounts without a profile are denied content access', function () {
    $content = $this->teaching->materials()->create(['title' => 'Pecahan', 'content' => 'Isi', 'status' => 'published', 'published_at' => now()]);
    expect(Gate::forUser(User::factory()->create())->allows('view', $content))->toBeFalse()
        ->and(Gate::forUser(User::factory()->teacher()->create())->allows('update', $content))->toBeFalse();
});
