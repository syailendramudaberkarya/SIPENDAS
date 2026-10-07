<?php

use App\Models\Material;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Services\MaterialService;
use Database\Seeders\DemoMaterialSeeder;
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
    $this->payload = ['teaching_assignment_id' => $this->teaching->id, 'title' => 'Belajar pecahan', 'content' => '<script>alert(1)</script>', 'status' => 'published'];
    $this->actingAs($this->teacher);
});

test('teacher completes material CRUD and escaped content is safe', function () {
    $this->get(route('teacher.materials.create'))->assertOk()->assertSee('multipart/form-data', false)->assertSee('name="_token"', false);
    $this->post(route('teacher.materials.store'), [...$this->payload, 'attachment_disk' => 'public'])->assertRedirect()->assertSessionHasNoErrors();
    $material = Material::firstOrFail();
    expect($material->attachment_disk)->toBeNull()->and($material->published_at)->not->toBeNull();
    $this->get(route('teacher.materials.show', $material))->assertOk()->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
    $this->get(route('teacher.materials.edit', $material))->assertOk();
    $this->put(route('teacher.materials.update', $material), [...$this->payload, 'status' => 'draft'])->assertRedirect()->assertSessionHasNoErrors();
    expect($material->fresh()->published_at)->toBeNull();
    $this->delete(route('teacher.materials.destroy', $material))->assertRedirect();
    $this->assertSoftDeleted($material);
    $this->get(route('teacher.materials.show', $material))->assertNotFound();
});

test('archived material remains listed and can be restored', function () {
    $material = $this->teaching->materials()->create($this->payload);
    $this->delete(route('teacher.materials.destroy', $material))->assertRedirect();

    $this->get(route('teacher.materials.index'))->assertOk()
        ->assertSee('Diarsipkan')
        ->assertSee(route('teacher.materials.restore', $material->id), false)
        ->assertSee('>Aktifkan<', false);

    $this->patch(route('teacher.materials.restore', $material->id))
        ->assertRedirect(route('teacher.materials.index'))->assertSessionHasNoErrors();
    expect($material->fresh())->not->toBeNull();
});

test('teacher cannot read edit delete or reassign another teachers material', function () {
    $other = TeachingAssignment::where('teacher_id', '!=', $this->teacher->teacher->id)->firstOrFail();
    $material = $other->materials()->create(['title' => 'Rahasia', 'content' => 'Isi', 'status' => 'draft']);
    $this->get(route('teacher.materials.show', $material))->assertForbidden();
    $this->get(route('teacher.materials.edit', $material))->assertForbidden();
    $this->get(route('teacher.materials.download', $material))->assertForbidden();
    $this->put(route('teacher.materials.update', $material), $this->payload)->assertForbidden();
    $this->delete(route('teacher.materials.destroy', $material))->assertForbidden();
    $this->post(route('teacher.materials.store'), [...$this->payload, 'teaching_assignment_id' => $other->id])->assertForbidden();
    $this->get(route('teacher.materials.index'))->assertDontSee('Rahasia');
});

test('student only reads current published materials in their classroom', function (string $kind) {
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
    $material = Material::create($data);
    $this->actingAs($this->student);
    $this->get(route('student.materials.show', $material))->assertStatus($kind === 'published' ? 200 : 403);
    if ($kind !== 'published') {
        $this->get(route('student.materials.download', $material))->assertForbidden();
        $this->get(route('student.materials.index'))->assertDontSee('Belajar pecahan');
    }
    $this->post(route('teacher.materials.store'), $this->payload)->assertForbidden();
})->with(['published', 'draft', 'future', 'other-class']);

test('material fields are validated server side', function (array $override, string $field) {
    $this->post(route('teacher.materials.store'), [...$this->payload, ...$override])->assertSessionHasErrors($field);
    expect(Material::count())->toBe(0);
})->with([
    [['title' => ''], 'title'], [['title' => ['invalid']], 'title'], [['content' => ''], 'content'],
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
    $this->post(route('teacher.materials.store'), [...$this->payload, 'attachment' => $file])->assertSessionHasErrors('attachment');
    expect(Material::count())->toBe(0)->and(Storage::disk('learning')->allFiles())->toBe([]);
})->with(['php', 'disguised', 'double-extension', 'large', 'mismatch']);

test('private attachment can be downloaded replaced removed and archived', function () {
    $pdf = UploadedFile::fake()->createWithContent('pecahan.pdf', '%PDF-1.4 dummy');
    $this->post(route('teacher.materials.store'), [...$this->payload, 'attachment' => $pdf])->assertSessionHasNoErrors();
    $material = Material::firstOrFail();
    $old = $material->attachment_path;
    expect(MaterialService::safePath($old))->toBeTrue()->and($material->attachment_disk)->toBe('learning');
    Storage::disk('learning')->assertExists($old);
    $this->actingAs($this->student)->get(route('student.materials.download', $material))->assertOk()->assertDownload('pecahan.pdf')->assertHeader('X-Content-Type-Options', 'nosniff');
    $this->actingAs($this->teacher)->put(route('teacher.materials.update', $material), [...$this->payload, 'attachment' => UploadedFile::fake()->image('gambar.png')])->assertSessionHasNoErrors();
    Storage::disk('learning')->assertMissing($old);
    $replacement = $material->fresh()->attachment_path;
    $this->put(route('teacher.materials.update', $material), [...$this->payload, 'remove_attachment' => '1'])->assertSessionHasNoErrors();
    Storage::disk('learning')->assertMissing($replacement);
    expect($material->fresh()->attachment_path)->toBeNull();
    $this->get(route('teacher.materials.download', $material))->assertNotFound();
});

test('download rejects path traversal and absent files', function () {
    $material = Material::create([...$this->payload, 'published_at' => now(), 'attachment_disk' => 'learning', 'attachment_path' => '../secret.pdf']);
    $this->get(route('teacher.materials.download', $material))->assertNotFound();
    $material->update(['attachment_path' => 'materials/'.str_repeat('a', 40).'.pdf']);
    $this->get(route('teacher.materials.download', $material))->assertNotFound();
});

test('material index has validated search and server pagination', function () {
    for ($i = 0; $i < 17; $i++) {
        Material::create([...$this->payload, 'title' => 'Materi '.$i]);
    }
    $this->get(route('teacher.materials.index'))->assertOk()->assertViewHas('materials', fn ($items) => $items->count() === 15 && $items->total() === 17);
    $this->get(route('teacher.materials.index', ['q' => ['invalid']]))->assertSessionHasErrors('q');
});

test('new attachment is removed when database transaction fails', function () {
    Material::creating(fn () => throw new RuntimeException('Simulated failure'));
    try {
        app(MaterialService::class)->save($this->teacher, [...$this->payload, 'attachment' => UploadedFile::fake()->createWithContent('demo.pdf', '%PDF-1.4 dummy')]);
        $this->fail('Expected database failure');
    } catch (RuntimeException $exception) {
        expect($exception->getMessage())->toBe('Simulated failure');
    } finally {
        Material::flushEventListeners();
    }
    expect(Material::count())->toBe(0)->and(Storage::disk('learning')->allFiles())->toBe([]);
});

test('material mutation requires CSRF and administrator cannot mutate teacher routes', function () {
    $admin = User::where('email', 'administrator@sipendas.example.test')->firstOrFail();
    $this->actingAs($admin)->post(route('teacher.materials.store'), $this->payload)->assertForbidden();
    $this->actingAs($this->teacher);
    $this->app['env'] = 'local';
    $this->post(route('teacher.materials.store'), $this->payload)->assertStatus(419);
});

test('demo materials are idempotent and forbidden in production', function () {
    $this->seed(DemoMaterialSeeder::class);
    $this->seed(DemoMaterialSeeder::class);
    expect(Material::count())->toBe(18);
    $this->app['env'] = 'production';
    expect(fn () => $this->seed(DemoMaterialSeeder::class))->toThrow(LogicException::class);
});
