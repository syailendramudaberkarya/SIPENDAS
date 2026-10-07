<?php

use App\Models\User;
use Database\Seeders\DemoSchoolSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

test('public and authenticated pages carry safe response headers', function () {
    $this->get(route('login'))->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    $this->actingAs(User::factory()->administrator()->create())->get(route('administrator.dashboard'))->assertOk()
        ->assertHeader('Cache-Control', 'no-store, private');
});

test('production error responses do not expose sensitive exception details', function () {
    config(['app.debug' => false]);
    Route::middleware('web')->get('/security-review-error', fn () => throw new RuntimeException('SQL secret-password /private/server/path'));
    $this->get('/security-review-error')->assertStatus(500)->assertSee('Terjadi gangguan')
        ->assertDontSee('secret-password')->assertDontSee('/private/server/path')->assertDontSee('RuntimeException');
    $this->get('/security-review-missing')->assertNotFound()->assertSee('Halaman tidak ditemukan');
});

test('oversized requests have a friendly error without server details', function () {
    Route::middleware('web')->get('/security-review-large', fn () => abort(413));
    $this->get('/security-review-large')->assertStatus(413)->assertSee('Unggahan terlalu besar');
});

test('session configuration uses framework security defaults', function () {
    expect(config('session.http_only'))->toBeTrue()
        ->and(config('session.same_site'))->toBe('lax')
        ->and(config('session.lifetime'))->toBeGreaterThan(0)->toBeLessThanOrEqual(120)
        ->and(config('filesystems.disks.learning.serve'))->toBeFalse()
        ->and(config('filesystems.disks.learning.visibility'))->toBe('private');
});

test('learning forms recover safely from malicious array input', function (string $resource, string $body) {
    $this->seed(DemoSchoolSeeder::class);
    $teacher = User::where('email', 'teacher@sipendas.example.test')->firstOrFail();
    $this->actingAs($teacher)->from(route('teacher.'.$resource.'.create'))->post(route('teacher.'.$resource.'.store'), [
        'teaching_assignment_id' => ['invalid'], 'title' => '', $body => 'Isi', 'status' => 'draft',
    ])->assertSessionHasErrors(['teaching_assignment_id', 'title']);
    $this->withCookie(config('session.cookie'), session()->getId())->get(route('teacher.'.$resource.'.create'))->assertOk()->assertSee('Judul wajib diisi.');
})->with([['materials', 'content'], ['assignments', 'description']]);
