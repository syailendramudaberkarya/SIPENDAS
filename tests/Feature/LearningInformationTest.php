<?php

use App\Models\LearningInformation;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Services\LearningInformationService;
use Database\Seeders\DemoLearningInformationSeeder;
use Database\Seeders\DemoSchoolSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DemoSchoolSeeder::class);
    $this->teacher = User::where('email', 'teacher@sipendas.example.test')->firstOrFail();
    $this->student = User::where('email', 'student@sipendas.example.test')->firstOrFail();
    $this->teaching = TeachingAssignment::where('teacher_id', $this->teacher->teacher->id)->where('classroom_id', $this->student->student->classroom_id)->firstOrFail();
    $this->payload = ['teaching_assignment_id' => $this->teaching->id, 'title' => 'Persiapan pembelajaran', 'content' => '<script>alert(1)</script>', 'status' => 'published'];
    $this->actingAs($this->teacher);
});

test('teacher completes information CRUD and output is escaped', function () {
    $this->get(route('teacher.information.create'))->assertOk()->assertSee('name="_token"', false);
    $this->post(route('teacher.information.store'), [...$this->payload, 'teacher_id' => 9999])->assertRedirect()->assertSessionHasNoErrors();
    $information = LearningInformation::firstOrFail();
    expect($information->published_at)->not->toBeNull()->and($information->teachingAssignment->teacher_id)->toBe($this->teacher->teacher->id);
    $this->get(route('teacher.information.show', $information))->assertOk()->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
    $this->get(route('teacher.information.edit', $information))->assertOk();
    $this->put(route('teacher.information.update', $information), [...$this->payload, 'status' => 'draft'])->assertSessionHasNoErrors();
    expect($information->fresh()->published_at)->toBeNull();
    $this->delete(route('teacher.information.destroy', $information))->assertRedirect();
    $this->assertSoftDeleted($information);
    $this->get(route('teacher.information.show', $information))->assertNotFound();
});

test('teacher cannot access or mutate information owned by another teacher', function () {
    $other = TeachingAssignment::where('teacher_id', '!=', $this->teacher->teacher->id)->firstOrFail();
    $information = $other->learningInformation()->create(['title' => 'Informasi rahasia', 'content' => 'Isi', 'status' => 'draft']);
    $this->get(route('teacher.information.show', $information))->assertForbidden();
    $this->get(route('teacher.information.edit', $information))->assertForbidden();
    $this->put(route('teacher.information.update', $information), $this->payload)->assertForbidden();
    $this->delete(route('teacher.information.destroy', $information))->assertForbidden();
    $this->post(route('teacher.information.store'), [...$this->payload, 'teaching_assignment_id' => $other->id])->assertForbidden();
    $owned = LearningInformation::create($this->payload);
    $this->put(route('teacher.information.update', $owned), [...$this->payload, 'teaching_assignment_id' => $other->id])->assertForbidden();
    expect($owned->fresh()->teaching_assignment_id)->toBe($this->teaching->id);
    $this->get(route('teacher.information.index', ['q' => 'rahasia']))->assertDontSee('Informasi rahasia');
});

test('student only sees published information for their classroom', function (string $kind) {
    $data = [...$this->payload, 'published_at' => now()->subMinute()];
    if ($kind === 'draft') {
        $data['status'] = 'draft';
    }
    if ($kind === 'future') {
        $data['published_at'] = now()->addDay();
    }
    if ($kind === 'missing-date') {
        $data['published_at'] = null;
    }
    if ($kind === 'other-class') {
        $data['teaching_assignment_id'] = TeachingAssignment::where('classroom_id', '!=', $this->student->student->classroom_id)->firstOrFail()->id;
    }
    $information = LearningInformation::create($data);
    $this->actingAs($this->student);
    $this->get(route('student.information.show', $information))->assertStatus($kind === 'published' ? 200 : 403);
    $response = $this->get(route('student.information.index'));
    $kind === 'published' ? $response->assertSee('Persiapan pembelajaran') : $response->assertDontSee('Persiapan pembelajaran');
    $this->post(route('teacher.information.store'), $this->payload)->assertForbidden();
    $this->put(route('teacher.information.update', $information), $this->payload)->assertForbidden();
    $this->delete(route('teacher.information.destroy', $information))->assertForbidden();
})->with(['published', 'draft', 'future', 'missing-date', 'other-class']);

test('information fields use server validation in Indonesian', function (array $override, string $field) {
    $this->post(route('teacher.information.store'), [...$this->payload, ...$override])->assertSessionHasErrors($field);
    expect(LearningInformation::count())->toBe(0);
})->with([
    [['title' => ''], 'title'], [['title' => ['invalid']], 'title'], [['title' => str_repeat('a', 201)], 'title'],
    [['content' => ''], 'content'], [['content' => ['invalid']], 'content'],
    [['status' => 'invalid'], 'status'], [['published_at' => 'invalid'], 'published_at'],
    [['published_at' => '2026-02-30T08:00'], 'published_at'],
    [['teaching_assignment_id' => 99999], 'teaching_assignment_id'], [['teaching_assignment_id' => ['invalid']], 'teaching_assignment_id'],
]);

test('inactive profiles and masters deny information access', function (string $target) {
    $information = LearningInformation::create([...$this->payload, 'published_at' => now()->subMinute()]);
    match ($target) {
        'student' => $this->student->student->update(['status' => 'inactive']),
        'classroom' => $this->teaching->classroom->update(['is_active' => false]),
        'year' => $this->teaching->classroom->academicYear->update(['is_active' => false]),
        'subject' => $this->teaching->subject->update(['is_active' => false]),
    };
    $this->actingAs($this->student->fresh())->get(route('student.information.show', $information))->assertForbidden();
    $this->get(route('student.information.index'))->assertDontSee('Persiapan pembelajaran');
})->with(['student', 'classroom', 'year', 'subject']);

test('information has validated search pagination and role scoped dashboard', function () {
    for ($i = 0; $i < 17; $i++) {
        LearningInformation::create([...$this->payload, 'title' => 'Informasi '.$i, 'published_at' => now()->subMinute()]);
    }
    $this->get(route('teacher.information.index'))->assertOk()->assertViewHas('informationItems', fn ($items) => $items->count() === 15 && $items->total() === 17);
    $this->get(route('teacher.information.index', ['q' => ['invalid']]))->assertSessionHasErrors('q');
    $this->get(route('teacher.information.index', ['page' => 0]))->assertSessionHasErrors('page');
    $this->get(route('teacher.dashboard'))->assertOk()->assertViewHas('latestInformation', fn ($items) => $items->count() === 5);
    $this->actingAs($this->student)->get(route('student.dashboard'))->assertOk()->assertViewHas('latestInformation', fn ($items) => $items->count() === 5);
});

test('administrator reads information but cannot mutate teacher routes', function () {
    $information = LearningInformation::create($this->payload);
    $admin = User::where('email', 'administrator@sipendas.example.test')->firstOrFail();
    $this->actingAs($admin)->get(route('administrator.information.index'))->assertOk();
    $this->get(route('administrator.information.show', $information))->assertOk();
    $this->post(route('teacher.information.store'), $this->payload)->assertForbidden();
    $this->put(route('teacher.information.update', $information), $this->payload)->assertForbidden();
    $this->delete(route('teacher.information.destroy', $information))->assertForbidden();
});

test('information mutation requires CSRF and guest authentication', function () {
    $this->app['env'] = 'local';
    $this->post(route('teacher.information.store'), $this->payload)->assertStatus(419);
    auth()->logout();
    $this->get(route('teacher.information.index'))->assertRedirect(route('login'));
});

test('information write failure rolls back without partial records', function () {
    LearningInformation::creating(fn () => throw new RuntimeException('Simulated failure'));
    try {
        app(LearningInformationService::class)->save($this->teacher, $this->payload);
        $this->fail('Expected failure');
    } catch (RuntimeException $exception) {
        expect($exception->getMessage())->toBe('Simulated failure');
    } finally {
        LearningInformation::flushEventListeners();
    }
    expect(LearningInformation::count())->toBe(0);
});

test('demo information is idempotent and rejects production', function () {
    $this->seed(DemoLearningInformationSeeder::class);
    $this->seed(DemoLearningInformationSeeder::class);
    expect(LearningInformation::count())->toBe(18);
    $this->app['env'] = 'production';
    expect(fn () => $this->seed(DemoLearningInformationSeeder::class))->toThrow(LogicException::class);
});

test('invalid array input returns a usable form with Indonesian feedback', function () {
    $this->from(route('teacher.information.create'))->post(route('teacher.information.store'), [...$this->payload,
        'title' => '', 'teaching_assignment_id' => ['invalid'],
    ])->assertSessionHasErrors(['title' => 'Judul wajib diisi.', 'teaching_assignment_id']);
    $this->withCookie(config('session.cookie'), session()->getId())->get(route('teacher.information.create'))->assertOk()->assertSee('Judul wajib diisi.');
});
