<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Schedule;
use App\Models\User;

class SchedulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, Schedule $schedule): bool
    {
        return Schedule::visibleTo($user)->whereKey($schedule->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->role === UserRole::Administrator;
    }

    public function update(User $user, Schedule $schedule): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, Schedule $schedule): bool
    {
        return $this->create($user);
    }
}
