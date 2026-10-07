<?php

use App\Models\Assignment;
use App\Models\LearningInformation;
use App\Models\Material;
use App\Models\TeachingAssignment;
use App\Models\User;
use Database\Seeders\DemoSchoolSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DemoSchoolSeeder::class);
    $this->teacher = User::where('email', 'teacher@sipendas.example.test')->firstOrFail();
    $this->student = User::where('email', 'student@sipendas.example.test')->firstOrFail();
    $this->teaching = TeachingAssignment::where('teacher_id', $this->teacher->teacher->id)->where('classroom_id', $this->student->student->classroom_id)->firstOrFail();
});

test('administrator dashboard uses database counts and excludes archived content', function () {
    $material = $this->teaching->materials()->create(['title' => 'Arsip', 'content' => 'Isi', 'status' => 'draft']);
    $material->delete();
    $this->teaching->materials()->create(['title' => 'Aktif', 'content' => 'Isi', 'status' => 'draft']);
    $this->teaching->assignments()->create(['title' => 'Tugas', 'description' => 'Isi', 'status' => 'draft']);
    $admin = User::where('email', 'administrator@sipendas.example.test')->firstOrFail();
    $this->actingAs($admin)->get(route('administrator.dashboard'))->assertOk()
        ->assertViewHas('statistics', [
            'Siswa' => 6, 'Guru' => 3, 'Kelas' => 3, 'Mata pelajaran' => 3, 'Materi' => 1, 'Tugas' => 1,
        ])->assertSee(route('administrator.students.index'), false)
        ->assertSee(route('administrator.teachers.index'), false)
        ->assertSee(route('administrator.classrooms.index'), false)
        ->assertSee(route('administrator.subjects.index'), false)
        ->assertSee(route('administrator.materials.index'), false)
        ->assertSee(route('administrator.assignments.index'), false)
        ->assertDontSee('Pengelolaan pembelajaran')->assertDontSee('Data pembelajaran dan pengguna');
});

test('teacher summary excludes inactive teaching relationships', function (string $target) {
    match ($target) {
        'teacher' => $this->teacher->teacher->update(['status' => 'inactive']),
        'classroom' => $this->teaching->classroom->update(['is_active' => false]),
        'subject' => $this->teaching->subject->update(['is_active' => false]),
        'year' => $this->teaching->classroom->academicYear->update(['is_active' => false]),
    };
    $this->actingAs($this->teacher->fresh())->get(route('teacher.dashboard'))->assertOk()
        ->assertViewHas('teachingAssignments', fn ($items) => $items->every(fn ($teaching) => $teaching->id !== $this->teaching->id));
})->with(['teacher', 'classroom', 'subject', 'year']);

test('student subject list is restricted to their active class and deduplicated', function () {
    $this->actingAs($this->student)->get(route('student.dashboard'))->assertOk()
        ->assertViewHas('subjects', fn ($items) => $items->total() === 3)
        ->assertViewHas('hasActiveClass', true);
    $this->student->student->update(['status' => 'inactive']);
    $this->actingAs($this->student->fresh())->get(route('student.dashboard'))->assertOk()
        ->assertViewHas('subjects', fn ($items) => $items->total() === 0)
        ->assertViewHas('hasActiveClass', false);
});

test('active assignment counts exclude drafts future publications expired and other class tasks', function () {
    $base = ['title' => 'Tugas', 'description' => 'Isi', 'status' => 'published', 'published_at' => now()->subDay()];
    $this->teaching->assignments()->create($base);
    $this->teaching->assignments()->create([...$base, 'deadline_at' => now()->addDay()]);
    $this->teaching->assignments()->create([...$base, 'deadline_at' => now()->subMinute()]);
    $this->teaching->assignments()->create([...$base, 'status' => 'draft']);
    $this->teaching->assignments()->create([...$base, 'published_at' => now()->addDay()]);
    $other = TeachingAssignment::where('teacher_id', '!=', $this->teacher->teacher->id)->where('classroom_id', '!=', $this->student->student->classroom_id)->firstOrFail();
    $other->assignments()->create($base);
    $this->actingAs($this->teacher)->get(route('teacher.dashboard'))->assertOk()
        ->assertViewHas('statistics', fn ($statistics) => $statistics['Tugas terbit aktif'] === 2);
    $this->actingAs($this->student)->get(route('student.dashboard'))->assertOk()->assertViewHas('activeAssignmentCount', 2);
});

test('dashboard recent content is limited and scoped for every content model', function (string $model, string $key, string $body) {
    for ($i = 0; $i < 7; $i++) {
        $model::create(['teaching_assignment_id' => $this->teaching->id, 'title' => 'Terbit '.$i, $body => 'Isi', 'status' => 'published', 'published_at' => now()->subMinute()]);
    }
    $other = TeachingAssignment::where('teacher_id', '!=', $this->teacher->teacher->id)->where('classroom_id', '!=', $this->student->student->classroom_id)->firstOrFail();
    $model::create(['teaching_assignment_id' => $other->id, 'title' => 'Tidak berwenang', $body => 'Isi', 'status' => 'published', 'published_at' => now()->subMinute()]);
    $this->actingAs($this->teacher)->get(route('teacher.dashboard'))->assertOk()->assertDontSee('Tidak berwenang')
        ->assertViewHas($key, fn ($items) => $items->count() === 5);
    $this->actingAs($this->student)->get(route('student.dashboard'))->assertOk()->assertDontSee('Tidak berwenang')
        ->assertViewHas($key, fn ($items) => $items->count() === 5);
})->with([[Material::class, 'latestMaterials', 'content'], [Assignment::class, 'latestAssignments', 'description'], [LearningInformation::class, 'latestInformation', 'content']]);

test('dashboard pagination validates query input', function (string $role, string $parameter) {
    $this->actingAs($role === 'teacher' ? $this->teacher : $this->student)
        ->get(route($role.'.dashboard', [$parameter => ['invalid']]))->assertSessionHasErrors($parameter);
})->with([['teacher', 'page'], ['student', 'subjects_page']]);

test('dashboard denies access to another role', function () {
    $this->actingAs($this->student)->get(route('teacher.dashboard'))->assertForbidden();
    $this->get(route('administrator.dashboard'))->assertForbidden();
    $this->actingAs($this->teacher)->get(route('student.dashboard'))->assertForbidden();
});
