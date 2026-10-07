<?php

use App\Models\Assignment;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Services\AssignmentService;
use Database\Seeders\DemoAssignmentSeeder;
use Database\Seeders\DemoSchoolSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DemoSchoolSeeder::class);
    Storage::fake('learning');
    $this->teacher = User::where('email', 'teacher@sipendas.example.test')->firstOrFail();
    $this->student = User::where('email', 'student@sipendas.example.test')->firstOrFail();
    $this->teaching = TeachingAssignment::where('teacher_id', $this->teacher->teacher->id)->where('classroom_id', $this->student->student->classroom_id)->firstOrFail();
    $this->payload = ['teaching_assignment_id' => $this->teaching->id, 'title' => 'Belajar pecahan', 'description' => '<script>alert(1)</script>', 'status' => 'published'];
    $this->actingAs($this->teacher);
});

test('teacher completes assignment CRUD and escaped content is safe', function () {
    $this->get(route('teacher.assignments.create'))->assertOk()->assertSee('multipart/form-data', false)->assertSee('name="_token"', false);
    $this->post(route('teacher.assignments.store'), [...$this->payload, 'attachment_disk' => 'public'])->assertRedirect()->assertSessionHasNoErrors();
    $assignment = Assignment::firstOrFail();
    expect($assignment->attachment_disk)->toBeNull()->and($assignment->published_at)->not->toBeNull();
    $this->get(route('teacher.assignments.show', $assignment))->assertOk()->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
    $this->get(route('teacher.assignments.edit', $assignment))->assertOk();
    $this->put(route('teacher.assignments.update', $assignment), [...$this->payload, 'status' => 'draft'])->assertRedirect()->assertSessionHasNoErrors();
    expect($assignment->fresh()->published_at)->toBeNull();
    $this->delete(route('teacher.assignments.destroy', $assignment))->assertRedirect();
    $this->assertSoftDeleted($assignment);
    $this->get(route('teacher.assignments.show', $assignment))->assertNotFound();
});

test('teacher cannot read edit delete or reassign another teachers assignment', function () {
    $other = TeachingAssignment::where('teacher_id', '!=', $this->teacher->teacher->id)->firstOrFail();
    $assignment = $other->assignments()->create(['title' => 'Rahasia', 'description' => 'Isi', 'status' => 'draft']);
    $this->get(route('teacher.assignments.show', $assignment))->assertForbidden();
    $this->get(route('teacher.assignments.edit', $assignment))->assertForbidden();
    $this->get(route('teacher.assignments.download', $assignment))->assertForbidden();
    $this->put(route('teacher.assignments.update', $assignment), $this->payload)->assertForbidden();
    $this->delete(route('teacher.assignments.destroy', $assignment))->assertForbidden();
    $this->post(route('teacher.assignments.store'), [...$this->payload, 'teaching_assignment_id' => $other->id])->assertForbidden();
    $this->get(route('teacher.assignments.index'))->assertDontSee('Rahasia');
});

test('student only reads current published assignments in their classroom', function (string $kind) {
    $data = $this->payload;
    $data['published_at'] = now()->subMinute();
    if ($kind === 'draft') {
        $data['status'] = 'draft';
    }
    if ($kind === 'future') {
        $data['published_at'] = now()->addDay();
    }
    if ($kind === 'other-class') {
        $data['teaching_assignment_id'] = TeachingAssignment::where('classroom_id', '!=', $this->student->student->classroom_id)->firstOrFail()->id;
    }
    $assignment = Assignment::create($data);
    $this->actingAs($this->student);
    $this->get(route('student.assignments.show', $assignment))->assertStatus($kind === 'published' ? 200 : 403);
    if ($kind !== 'published') {
        $this->get(route('student.assignments.download', $assignment))->assertForbidden();
        $this->get(route('student.assignments.index'))->assertDontSee('Belajar pecahan');
    }
    $this->post(route('teacher.assignments.store'), $this->payload)->assertForbidden();
})->with(['published', 'draft', 'future', 'other-class']);

test('assignment fields are validated server side', function (array $override, string $field) {
    $this->post(route('teacher.assignments.store'), [...$this->payload, ...$override])->assertSessionHasErrors($field);
    expect(Assignment::count())->toBe(0);
})->with([
    [['title' => ''], 'title'], [['title' => ['invalid']], 'title'], [['description' => ''], 'description'],
    [['status' => 'invalid'], 'status'], [['teaching_assignment_id' => 99999], 'teaching_assignment_id'],
    [['published_at' => 'invalid'], 'published_at'], [['remove_attachment' => 'invalid'], 'remove_attachment'],
]);

test('unsafe uploads are rejected', function (string $kind) {
    $file = match ($kind) {
        'php' => UploadedFile::fake()->createWithContent('payload.php', '<?php echo 1;'),
        'disguised' => UploadedFile::fake()->createWithContent('payload.pdf', '<?php echo 1;'),
        'double-extension' => UploadedFile::fake()->createWithContent('payload.php.pdf', '%PDF-1.4 dummy'),
        'large' => UploadedFile::fake()->createWithContent('large.pdf', '%PDF-1.4 dummy')->size(5121),
        default => UploadedFile::fake()->createWithContent('invalid.png', '%PDF-1.4 dummy'),
    };
    $this->post(route('teacher.assignments.store'), [...$this->payload, 'attachment' => $file])->assertSessionHasErrors('attachment');
    expect(Assignment::count())->toBe(0)->and(Storage::disk('learning')->allFiles())->toBe([]);
})->with(['php', 'disguised', 'double-extension', 'large', 'mismatch']);

test('private attachment can be downloaded replaced removed and archived', function () {
    $pdf = UploadedFile::fake()->createWithContent('pecahan.pdf', '%PDF-1.4 dummy');
    $this->post(route('teacher.assignments.store'), [...$this->payload, 'attachment' => $pdf])->assertSessionHasNoErrors();
    $assignment = Assignment::firstOrFail();
    $old = $assignment->attachment_path;
    expect(AssignmentService::safePath($old))->toBeTrue()->and($assignment->attachment_disk)->toBe('learning');
    Storage::disk('learning')->assertExists($old);
    $this->actingAs($this->student)->get(route('student.assignments.download', $assignment))->assertOk()->assertDownload('pecahan.pdf')->assertHeader('X-Content-Type-Options', 'nosniff');
    $this->actingAs($this->teacher)->put(route('teacher.assignments.update', $assignment), [...$this->payload, 'attachment' => UploadedFile::fake()->image('gambar.png')])->assertSessionHasNoErrors();
    Storage::disk('learning')->assertMissing($old);
    $replacement = $assignment->fresh()->attachment_path;
    $this->put(route('teacher.assignments.update', $assignment), [...$this->payload, 'remove_attachment' => '1'])->assertSessionHasNoErrors();
    Storage::disk('learning')->assertMissing($replacement);
    expect($assignment->fresh()->attachment_path)->toBeNull();
    $this->get(route('teacher.assignments.download', $assignment))->assertNotFound();
});

test('download rejects path traversal and absent files', function () {
    $assignment = Assignment::create([...$this->payload, 'published_at' => now(), 'attachment_disk' => 'learning', 'attachment_path' => '../secret.pdf']);
    $this->get(route('teacher.assignments.download', $assignment))->assertNotFound();
    $assignment->update(['attachment_path' => 'assignments/'.str_repeat('a', 40).'.pdf']);
    $this->get(route('teacher.assignments.download', $assignment))->assertNotFound();
});

test('assignment index has validated search and server pagination', function () {
    for ($i = 0; $i < 17; $i++) {
        Assignment::create([...$this->payload, 'title' => 'Tugas '.$i]);
    }
    $this->get(route('teacher.assignments.index'))->assertOk()->assertViewHas('assignments', fn ($items) => $items->count() === 15 && $items->total() === 17);
    $this->get(route('teacher.assignments.index', ['q' => ['invalid']]))->assertSessionHasErrors('q');
});

test('new attachment is removed when database transaction fails', function () {
    Assignment::creating(fn () => throw new RuntimeException('Simulated failure'));
    try {
        app(AssignmentService::class)->save($this->teacher, [...$this->payload, 'attachment' => UploadedFile::fake()->createWithContent('demo.pdf', '%PDF-1.4 dummy')]);
        $this->fail('Expected database failure');
    } catch (RuntimeException $exception) {
        expect($exception->getMessage())->toBe('Simulated failure');
    } finally {
        Assignment::flushEventListeners();
    }
    expect(Assignment::count())->toBe(0)->and(Storage::disk('learning')->allFiles())->toBe([]);
});

test('assignment mutation requires CSRF and administrator cannot mutate teacher routes', function () {
    $admin = User::where('email', 'administrator@sipendas.example.test')->firstOrFail();
    $this->actingAs($admin)->post(route('teacher.assignments.store'), $this->payload)->assertForbidden();
    $this->actingAs($this->teacher);
    $this->app['env'] = 'local';
    $this->post(route('teacher.assignments.store'), $this->payload)->assertStatus(419);
});

test('demo assignments are idempotent and forbidden in production', function () {
    $this->seed(DemoAssignmentSeeder::class);
    $this->seed(DemoAssignmentSeeder::class);
    expect(Assignment::count())->toBe(18);
    $this->app['env'] = 'production';
    expect(fn () => $this->seed(DemoAssignmentSeeder::class))->toThrow(LogicException::class);
});

test('assignment deadline must follow effective publication', function (string $kind) {
    $publication = now()->addDay()->startOfMinute();
    $payload = [...$this->payload, 'published_at' => $publication->format('Y-m-d\TH:i')];
    $payload['deadline_at'] = match ($kind) {
        'same' => $publication->format('Y-m-d\TH:i'),
        'before' => $publication->copy()->subMinute()->format('Y-m-d\TH:i'),
        'invalid' => '2026-02-30T08:00',
        'implicit' => now()->subDay()->format('Y-m-d\TH:i'),
        default => 'invalid',
    };
    if ($kind === 'implicit') {
        unset($payload['published_at']);
    }
    $this->post(route('teacher.assignments.store'), $payload)->assertSessionHasErrors('deadline_at');
    expect(Assignment::count())->toBe(0);
})->with(['same', 'before', 'invalid', 'implicit', 'bad-format']);

test('deadline is optional editable and does not hide expired published assignments', function () {
    $this->post(route('teacher.assignments.store'), [...$this->payload,
        'published_at' => now()->subDays(2)->format('Y-m-d\TH:i'),
        'deadline_at' => now()->subDay()->format('Y-m-d\TH:i'),
    ])->assertSessionHasNoErrors();
    $assignment = Assignment::firstOrFail();
    expect($assignment->deadline_at->isPast())->toBeTrue();
    $this->actingAs($this->student)->get(route('student.assignments.show', $assignment))->assertOk()->assertSee('Batas waktu:');
    $this->get(route('student.assignments.index'))->assertSee('Belajar pecahan');
    $this->actingAs($this->teacher)->put(route('teacher.assignments.update', $assignment), $this->payload)->assertSessionHasNoErrors();
    expect($assignment->fresh()->deadline_at)->toBeNull();
});

test('archiving preserves the attachment but forbids subsequent downloads', function () {
    $this->post(route('teacher.assignments.store'), [...$this->payload, 'attachment' => UploadedFile::fake()->image('contoh.jpg')])->assertSessionHasNoErrors();
    $assignment = Assignment::firstOrFail();
    $path = $assignment->attachment_path;
    $this->delete(route('teacher.assignments.destroy', $assignment))->assertRedirect();
    Storage::disk('learning')->assertExists($path);
    $this->get(route('teacher.assignments.download', $assignment))->assertNotFound();
    $this->actingAs($this->student)->get(route('student.assignments.download', $assignment))->assertNotFound();
});

test('inactive profiles and masters remove assignments from list and direct access', function (string $target) {
    $assignment = Assignment::create([...$this->payload, 'published_at' => now()->subMinute()]);
    match ($target) {
        'student' => $this->student->student->update(['status' => 'inactive']),
        'classroom' => $this->teaching->classroom->update(['is_active' => false]),
        'year' => $this->teaching->classroom->academicYear->update(['is_active' => false]),
        'subject' => $this->teaching->subject->update(['is_active' => false]),
    };
    $this->actingAs($this->student->fresh())->get(route('student.assignments.show', $assignment))->assertForbidden();
    $this->get(route('student.assignments.index'))->assertDontSee('Belajar pecahan');
})->with(['student', 'classroom', 'year', 'subject']);
