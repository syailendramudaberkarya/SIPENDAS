<?php

use App\Enums\UserRole;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('branding');
    Storage::fake('profiles');
});

test('every active role can open settings and change its own password', function (UserRole $role) {
    $user = User::factory()->create(['role' => $role]);
    $this->actingAs($user)->get(route('settings.password.edit'))
        ->assertOk()->assertSee('Ubah password')->assertSee('data-password-toggle', false);

    $this->put(route('settings.password'), [
        'current_password' => 'password',
        'password' => 'PasswordBaru123',
        'password_confirmation' => 'PasswordBaru123',
    ])->assertRedirect(route('settings.password.edit'))->assertSessionHasNoErrors();

    expect(Hash::check('PasswordBaru123', $user->fresh()->password))->toBeTrue();
})->with([UserRole::Administrator, UserRole::Teacher, UserRole::Student]);

test('password change validates current password strength and confirmation', function (array $data, string $field) {
    $user = User::factory()->create();
    $this->actingAs($user)->put(route('settings.password'), $data)->assertSessionHasErrors($field);
    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
})->with([
    'wrong current password' => [[
        'current_password' => 'wrong', 'password' => 'PasswordBaru123', 'password_confirmation' => 'PasswordBaru123',
    ], 'current_password'],
    'weak password' => [[
        'current_password' => 'password', 'password' => 'abcdefgh', 'password_confirmation' => 'abcdefgh',
    ], 'password'],
    'unconfirmed password' => [[
        'current_password' => 'password', 'password' => 'PasswordBaru123', 'password_confirmation' => 'berbeda',
    ], 'password'],
]);

test('administrator can update website title and logo everywhere', function () {
    $admin = User::factory()->administrator()->create();
    $logo = UploadedFile::fake()->image('logo-sekolah.png', 600, 600);

    $this->actingAs($admin)->put(route('settings.branding'), [
        'site_title' => 'Sekolah Harapan', 'logo' => $logo,
    ])->assertRedirect(route('settings.branding.edit'))->assertSessionHasNoErrors();

    $setting = SiteSetting::firstOrFail();
    Storage::disk('branding')->assertExists($setting->logo_path);

    $this->get(route('settings.index'))->assertOk()
        ->assertSee('Sekolah Harapan')
        ->assertSee(route('settings.logo', ['v' => $setting->updated_at->timestamp]), false);
    $this->post(route('logout'));
    $this->get(route('login'))->assertOk()
        ->assertSee('Masuk — Sekolah Harapan')
        ->assertSee(route('settings.logo', ['v' => $setting->updated_at->timestamp]), false);
    $this->get(route('settings.logo'))->assertOk()->assertHeader('Content-Type', 'image/png');
});

test('every active role can update name email and profile photo', function (UserRole $role) {
    $user = User::factory()->create(['role' => $role]);
    $photo = UploadedFile::fake()->image('avatar.png', 500, 500);

    $this->actingAs($user)->put(route('settings.profile'), [
        'name' => 'Nama Baru', 'email' => 'baru-'.$user->id.'@example.test', 'avatar' => $photo,
    ])->assertRedirect(route('settings.index'))->assertSessionHasNoErrors();

    $user->refresh();
    expect($user->name)->toBe('Nama Baru')->and($user->avatar_path)->not->toBeNull();
    Storage::disk('profiles')->assertExists($user->avatar_path);
    $this->get(route('settings.avatar'))->assertOk()->assertHeader('Content-Type', 'image/png');
})->with([UserRole::Administrator, UserRole::Teacher, UserRole::Student]);

test('nonadministrators cannot change website identity', function (UserRole $role) {
    $user = User::factory()->create(['role' => $role]);
    $this->actingAs($user)->get(route('settings.index'))->assertOk()->assertDontSee('Identitas website');
    $this->put(route('settings.branding'), ['site_title' => 'Palsu'])->assertForbidden();
})->with([UserRole::Teacher, UserRole::Student]);

test('website logo upload rejects disguised and oversized files', function () {
    $admin = User::factory()->administrator()->create();
    $fake = UploadedFile::fake()->createWithContent('logo.png', '<?php echo "bad";');

    $this->actingAs($admin)->put(route('settings.branding'), [
        'site_title' => 'SIPENDAS', 'logo' => $fake,
    ])->assertSessionHasErrors('logo');

    expect(SiteSetting::count())->toBe(0)
        ->and(Storage::disk('branding')->allFiles())->toBe([]);
});
