<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

uses(RefreshDatabase::class);

test('login form is available and includes a csrf token', function () {
    $this->get(route('login'))->assertOk()
        ->assertSee('name="_token"', false)
        ->assertSee('data-login-form', false)
        ->assertSee('data-password-toggle', false)
        ->assertSee('images/sipendas-logo.png', false)
        ->assertSee('Masuk ke akun Anda')
        ->assertDontSee('Lupa password atau belum memiliki akun?');
});

test('valid credentials authenticate and regenerate the session', function () {
    $user = User::factory()->administrator()->create();
    $this->get(route('login'));
    $oldId = session()->getId();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
    expect(session()->getId())->not->toBe($oldId);
});

test('invalid credentials and inactive accounts have the same generic error', function (bool $active) {
    $user = User::factory()->create(['is_active' => $active]);

    $this->post(route('login.store'), ['email' => $user->email, 'password' => $active ? 'incorrect' : 'password'])
        ->assertSessionHasErrors(['email' => 'Email atau password salah.'])
        ->assertSessionMissing('_old_input.password');

    $this->assertGuest();
})->with([true, false]);

test('login input is validated on the server in Indonesian', function (array $input, string $field, string $message) {
    $this->post(route('login.store'), $input)->assertSessionHasErrors([$field => $message]);
    $this->assertGuest();
})->with([
    'required email' => [[], 'email', 'Email wajib diisi.'],
    'invalid email' => [['email' => 'invalid', 'password' => 'password'], 'email', 'Format email tidak valid.'],
    'required password' => [['email' => 'demo@example.test'], 'password', 'Password wajib diisi.'],
    'array password' => [['email' => 'demo@example.test', 'password' => ['malicious']], 'password', 'Password harus berupa teks.'],
]);

test('the sixth attempt is blocked for five minutes even with correct credentials', function () {
    $user = User::factory()->create(['email' => 'demo@example.test']);

    for ($i = 0; $i < 5; $i++) {
        $this->post(route('login.store'), ['email' => ' DEMO@example.test ', 'password' => 'incorrect'])
            ->assertSessionHasErrors(['email' => 'Email atau password salah.']);
    }

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertSessionHasErrors('email');
    expect(session('errors')->first('email'))->toContain('Terlalu banyak percobaan login');
    $this->assertGuest();

    $this->get(route('login'))->assertOk()
        ->assertSee('data-login-cooldown', false)
        ->assertSee('data-login-progress', false)
        ->assertSee('role="progressbar"', false)
        ->assertSee('data-login-submit disabled', false)
        ->assertDontSee('Coba lagi dalam 300 detik.');

    $this->travel(301)->seconds();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($user);
});

test('successful login clears failed attempt counters', function () {
    $user = User::factory()->create();
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong']);
    $key = hash('sha256', $user->email.'|127.0.0.1');
    expect(RateLimiter::attempts($key))->toBe(1);

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])->assertRedirect();
    expect(RateLimiter::attempts($key))->toBe(0);
});

test('logout invalidates session data and regenerates the csrf token', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->withSession(['private_value' => 'private'])->get(route('student.dashboard'));
    $oldId = session()->getId();
    $oldToken = session()->token();

    $this->post(route('logout'))->assertRedirect(route('login'))->assertSessionMissing('private_value');

    $this->assertGuest();
    expect(session()->getId())->not->toBe($oldId)
        ->and(session()->token())->not->toBe($oldToken);
});

test('logout does not accept a GET request', function () {
    $this->actingAs(User::factory()->create())->get(route('logout'))->assertStatus(405);
});

test('role dashboards reject guests', function (string $route) {
    $this->get(route($route))->assertRedirect(route('login'));
})->with(['dashboard', 'administrator.dashboard', 'teacher.dashboard', 'student.dashboard']);

test('dashboard entry redirects each role to its own area', function (UserRole $role) {
    $user = User::factory()->create(['role' => $role]);
    $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route($role->value.'.dashboard'));
    $this->get(route($role->value.'.dashboard'))->assertOk();
})->with([UserRole::Administrator, UserRole::Teacher, UserRole::Student]);

test('users are forbidden from opening another role URL', function (UserRole $role, string $route) {
    $this->actingAs(User::factory()->create(['role' => $role]))->get(route($route))->assertForbidden();
})->with([
    [UserRole::Teacher, 'administrator.dashboard'],
    [UserRole::Student, 'administrator.dashboard'],
    [UserRole::Student, 'teacher.dashboard'],
    [UserRole::Teacher, 'student.dashboard'],
    [UserRole::Administrator, 'teacher.dashboard'],
    [UserRole::Administrator, 'student.dashboard'],
]);

test('disabling an account invalidates an already authenticated session', function () {
    $user = User::factory()->administrator()->create();
    $this->actingAs($user);
    $user->is_active = false;
    $user->save();

    $this->get(route('administrator.dashboard'))->assertRedirect(route('login'))->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('dashboard escapes user supplied names', function () {
    $user = User::factory()->create(['name' => '<script>alert(1)</script>']);
    $this->actingAs($user)->get(route('student.dashboard'))
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
        ->assertDontSee('<script>alert(1)</script>', false);
});

test('csrf middleware rejects login and logout without a token', function (string $route) {
    $this->app['env'] = 'local';
    if ($route === 'logout') {
        $this->actingAs(User::factory()->create());
    }

    $this->post(route($route), ['email' => 'demo@example.test', 'password' => 'password'])
        ->assertStatus(419)->assertSee('Sesi berakhir');
})->with(['login.store', 'logout']);

test('new account password defaults require eight characters letters and numbers', function (string $password, bool $valid) {
    $validator = Validator::make(['password' => $password], ['password' => [Password::defaults()]]);
    expect($validator->passes())->toBe($valid);
})->with([
    ['Abc12345', true], ['Abc1234', false], ['abcdefgh', false], ['12345678', false],
]);

test('login and logout accept a valid csrf token with protection enabled', function () {
    $this->app['env'] = 'local';
    $user = User::factory()->create();
    $this->get(route('login'))->assertOk();

    $this->post(route('login.store'), [
        '_token' => session()->token(), 'email' => $user->email, 'password' => 'password',
    ])->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($user);

    $this->post(route('logout'), ['_token' => session()->token()])->assertRedirect(route('login'));
    $this->assertGuest();
});

test('https session cookies honor secure httponly and samesite configuration', function () {
    config(['session.secure' => true]);
    $response = $this->get(route('login'))->assertOk();
    $cookie = collect($response->headers->getCookies())->first(fn ($cookie) => $cookie->getName() === config('session.cookie'));

    expect($cookie)->not->toBeNull()
        ->and($cookie->isSecure())->toBeTrue()
        ->and($cookie->isHttpOnly())->toBeTrue()
        ->and($cookie->getSameSite())->toBe('lax');
});

test('login validation still renders when an email is submitted as an array', function () {
    $this->from(route('login'))->post(route('login.store'), ['email' => ['bad'], 'password' => 'password'])
        ->assertRedirect(route('login'))->assertSessionHasErrors('email');
    $this->withCookie(config('session.cookie'), session()->getId())
        ->get(route('login'))->assertOk()->assertSee('Email harus berupa teks.');
});
