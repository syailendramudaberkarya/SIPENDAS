<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AccountService
{
    public function save(array $data, User $actor, ?User $account = null): User
    {
        return DB::transaction(function () use ($data, $actor, $account) {
            $administrators = User::where('role', UserRole::Administrator->value)->lockForUpdate()->get();
            $user = $account ? User::whereKey($account->id)->lockForUpdate()->firstOrFail() : new User;
            $role = UserRole::from($data['role']);

            if ($user->exists && $user->role !== $role) {
                throw ValidationException::withMessages(['role' => 'Role akun existing tidak boleh diubah.']);
            }
            if ($user->exists && ! (bool) $data['is_active']) {
                $this->ensureMayDeactivate($user, $actor, $administrators);
            }

            $user->fill(['name' => $data['name'], 'email' => $data['email']]);
            if (! empty($data['password'])) {
                $user->password = $data['password'];
            }
            $user->role = $role;
            $user->is_active = (bool) $data['is_active'];
            $user->save();

            if ($role === UserRole::Teacher) {
                Teacher::updateOrCreate(['user_id' => $user->id], [
                    'employee_number' => $data['employee_number'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'status' => $data['profile_status'],
                ]);
            } elseif ($role === UserRole::Student) {
                Student::updateOrCreate(['user_id' => $user->id], [
                    'nis' => $data['nis'], 'classroom_id' => $data['classroom_id'] ?? null,
                    'status' => $data['profile_status'],
                ]);
            }

            return $user;
        }, 3);
    }

    public function deactivate(User $account, User $actor): void
    {
        DB::transaction(function () use ($account, $actor) {
            $administrators = User::where('role', UserRole::Administrator->value)->lockForUpdate()->get();
            $user = User::whereKey($account->id)->lockForUpdate()->firstOrFail();
            $this->ensureMayDeactivate($user, $actor, $administrators);
            $user->is_active = false;
            $user->save();
            $user->teacher()->update(['status' => 'inactive']);
            $user->student()->where('status', 'active')->update(['status' => 'inactive']);
        }, 3);
    }

    public function activate(User $account): void
    {
        DB::transaction(function () use ($account) {
            $user = User::whereKey($account->id)->lockForUpdate()->firstOrFail();
            $user->is_active = true;
            $user->save();
            $user->teacher()->update(['status' => 'active']);
            $user->student()->update(['status' => 'active']);
        }, 3);
    }

    private function ensureMayDeactivate(User $user, User $actor, $administrators): void
    {
        if ($user->is($actor)) {
            throw ValidationException::withMessages(['is_active' => 'Anda tidak boleh menonaktifkan akun sendiri.']);
        }
        if ($user->role === UserRole::Administrator && ! $administrators->contains(fn ($admin) => $admin->id !== $user->id && $admin->is_active)) {
            throw ValidationException::withMessages(['is_active' => 'Administrator aktif terakhir tidak boleh dinonaktifkan.']);
        }
    }
}
