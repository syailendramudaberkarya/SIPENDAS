<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use LogicException;

class DemoAccountSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new LogicException('Akun demo hanya boleh dibuat pada environment local atau testing.');
        }

        $password = config('demo.password') ?: 'Demo12345';
        Validator::make(['password' => $password], ['password' => ['required', 'string', Password::defaults()]])->validate();

        DB::transaction(function () use ($password) {
            foreach (UserRole::cases() as $role) {
                $user = User::firstOrNew(['email' => $role->value.'@sipendas.example.test']);

                if (! $user->exists) {
                    $user->forceFill([
                        'name' => $role->label().' Demo',
                        'password' => Hash::make($password),
                        'role' => $role,
                        'is_active' => true,
                    ])->save();
                } elseif ($user->role !== $role) {
                    throw new LogicException('Role akun demo existing berbeda. Periksa data sebelum menjalankan seeder.');
                }

                if ($role === UserRole::Teacher) {
                    Teacher::firstOrCreate(['user_id' => $user->id]);
                } elseif ($role === UserRole::Student) {
                    Student::firstOrCreate(['user_id' => $user->id], ['nis' => 'DEMO0001']);
                }
            }
        });
    }
}
