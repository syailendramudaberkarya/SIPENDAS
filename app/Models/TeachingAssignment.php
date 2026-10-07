<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['teacher_id', 'classroom_id', 'subject_id'])]
class TeachingAssignment extends Model
{
    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->whereHas('teacher', fn ($teachers) => $teachers->where('status', 'active')
                ->whereHas('user', fn ($users) => $users->where('role', 'teacher')->where('is_active', true)))
            ->whereHas('classroom', fn ($classes) => $classes->where('is_active', true)
                ->whereHas('academicYear', fn ($years) => $years->where('is_active', true)))
            ->whereHas('subject', fn ($subjects) => $subjects->where('is_active', true));
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function materials(): HasMany
    {
        return $this->hasMany(Material::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    public function learningInformation(): HasMany
    {
        return $this->hasMany(LearningInformation::class);
    }
}
