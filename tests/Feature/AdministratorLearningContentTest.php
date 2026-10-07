<?php

use App\Models\Assignment;
use App\Models\LearningInformation;
use App\Models\Material;
use App\Models\TeachingAssignment;
use App\Models\User;
use Database\Seeders\DemoSchoolSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DemoSchoolSeeder::class);
    Storage::fake('learning');
    $this->admin = User::where('email', 'administrator@sipendas.example.test')->firstOrFail();
    $this->teaching = TeachingAssignment::query()->active()->with('teacher.user')->firstOrFail();
    $this->actingAs($this->admin);
});

test('administrator sees create actions for all learning content', function (string $resource, string $button) {
    $this->get(route("administrator.$resource.index"))
        ->assertOk()
        ->assertSee(route("administrator.$resource.create"), false)
        ->assertSee($button);

    $this->get(route("administrator.$resource.create"))
        ->assertOk()
        ->assertSee($this->teaching->teacher->user->name)
        ->assertSee('name="teaching_assignment_id"', false);
})->with([
    ['materials', 'Tambah materi'],
    ['assignments', 'Tambah tugas'],
    ['information', 'Tambah informasi'],
]);

test('administrator can create learning content for a teacher assignment', function () {
    $base = [
        'teaching_assignment_id' => $this->teaching->id,
        'status' => 'published',
    ];

    $this->post(route('administrator.materials.store'), [
        ...$base, 'title' => 'Materi dari Admin', 'content' => 'Isi materi',
    ])->assertRedirect()->assertSessionHasNoErrors();

    $this->post(route('administrator.assignments.store'), [
        ...$base, 'title' => 'Tugas dari Admin', 'description' => 'Isi tugas',
    ])->assertRedirect()->assertSessionHasNoErrors();

    $this->post(route('administrator.information.store'), [
        ...$base, 'title' => 'Informasi dari Admin', 'content' => 'Isi informasi',
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect(Material::where('title', 'Materi dari Admin')->exists())->toBeTrue()
        ->and(Assignment::where('title', 'Tugas dari Admin')->exists())->toBeTrue()
        ->and(LearningInformation::where('title', 'Informasi dari Admin')->exists())->toBeTrue();
});
