<?php

use App\Models\User;
use App\Services\AccountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

test('administrator password reset revokes an existing session on its next request', function (bool $visited) {
    $user = User::factory()->administrator()->create();
    $admin = User::factory()->administrator()->create();
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])->assertRedirect();
    if ($visited) {
        $this->withCookie(config('session.cookie'), session()->getId())->get(route('administrator.dashboard'))->assertOk();
    }
    app(AccountService::class)->save(['name' => $user->name, 'email' => $user->email, 'role' => 'administrator', 'is_active' => true, 'password' => 'Changed12345'], $admin, $user);
    $this->actingAs($user->fresh())->withCookie(config('session.cookie'), session()->getId())->get(route('administrator.dashboard'))->assertRedirect(route('login'));
    $this->assertGuest();
    expect(session()->has('password_hash_web'))->toBeFalse();
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'Changed12345'])->assertRedirect();
    $this->assertAuthenticatedAs($user->fresh());
})->with([true, false]);

test('several previous sessions are rejected independently after a password change', function () {
    $user = User::factory()->administrator()->create();
    $hash = Auth::guard('web')->hashPasswordForCookie($user->password);
    $user->update(['password' => 'Changed12345']);
    for ($i = 0; $i < 2; $i++) {
        $this->actingAs($user->fresh())->withSession(['password_hash_web' => $hash, 'private_value' => 'old-session'])
            ->get(route('administrator.dashboard'))->assertRedirect(route('login'))->assertSessionMissing('private_value');
        $this->assertGuest();
    }
});

test('ordinary account edits preserve sessions when password is unchanged', function () {
    $user = User::factory()->administrator()->create();
    $admin = User::factory()->administrator()->create();
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])->assertRedirect();
    app(AccountService::class)->save(['name' => 'Nama Baru', 'email' => $user->email, 'role' => 'administrator', 'is_active' => true, 'password' => ''], $admin, $user);
    $this->actingAs($user->fresh())->withCookie(config('session.cookie'), session()->getId())->get(route('administrator.dashboard'))->assertOk();
});
