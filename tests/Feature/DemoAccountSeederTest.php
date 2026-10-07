<?php

use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\DemoAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

test('demo seeding creates three roles with hashed passwords and is idempotent', function () {
    config(['demo.password' => 'Demo45678']);
    $this->seed(DemoAccountSeeder::class);
    $this->seed(DemoAccountSeeder::class);

    expect(User::count())->toBe(3)
        ->and(Teacher::count())->toBe(1)
        ->and(Student::count())->toBe(1);

    foreach (User::all() as $user) {
        expect(Hash::check('Demo45678', $user->password))->toBeTrue();
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'Demo45678'])->assertRedirect(route('dashboard'));
        $this->post(route('logout'))->assertRedirect(route('login'));
    }
});

test('demo seeding does not reset existing passwords or reactivate accounts', function () {
    $this->seed(DemoAccountSeeder::class);
    $user = User::where('email', 'administrator@sipendas.example.test')->firstOrFail();
    $user->password = 'Changed123';
    $user->is_active = false;
    $user->save();

    $this->seed(DemoAccountSeeder::class);

    expect(Hash::check('Changed123', $user->refresh()->password))->toBeTrue()
        ->and($user->is_active)->toBeFalse();
});

test('demo seeding is refused in production', function () {
    $this->app['env'] = 'production';
    $this->seed(DemoAccountSeeder::class);
})->throws(LogicException::class);

test('weak demo passwords are rejected', function () {
    config(['demo.password' => 'password']);
    $this->seed(DemoAccountSeeder::class);
})->throws(ValidationException::class);
