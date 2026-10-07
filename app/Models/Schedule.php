<?php

namespace App\Models;

use App\Enums\StudentStatus;
use App\Enums\TeacherStatus;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['teaching_assignment_id', 'day_of_week', 'starts_at', 'ends_at'])]
class Schedule extends Model
{
    public const DAYS = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];

    protected function casts(): array
    {
        return ['day_of_week' => 'integer'];
    }

    public function teachingAssignment(): BelongsTo
    {
        return $this->belongsTo(TeachingAssignment::class);
    }

    protected function dayName(): Attribute
    {
        return Attribute::get(fn () => self::DAYS[$this->day_of_week] ?? '');
    }

    protected function startTime(): Attribute
    {
        return Attribute::get(fn () => substr($this->starts_at, 0, 5));
    }

    protected function endTime(): Attribute
    {
        return Attribute::get(fn () => substr($this->ends_at, 0, 5));
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if (! $user->is_active) {
            return $query->whereKey([]);
        }
        if ($user->role === UserRole::Administrator) {
            return $query;
        }
        $query->whereHas('teachingAssignment', fn ($teaching) => $teaching->active());

        if ($user->role === UserRole::Teacher && $user->teacher?->status === TeacherStatus::Active) {
            return $query->whereHas('teachingAssignment', fn ($teaching) => $teaching->where('teacher_id', $user->teacher->id));
        }
        if ($user->role === UserRole::Student && $user->student?->status === StudentStatus::Active && $user->student->classroom_id) {
            return $query->whereHas('teachingAssignment', fn ($teaching) => $teaching->where('classroom_id', $user->student->classroom_id));
        }

        return $query->whereKey([]);
    }
}
