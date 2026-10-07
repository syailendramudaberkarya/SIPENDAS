<?php

namespace App\Models;

use App\Enums\PublicationStatus;
use App\Enums\StudentStatus;
use App\Enums\TeacherStatus;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['teaching_assignment_id', 'title', 'description', 'status', 'published_at', 'deadline_at', 'attachment_disk', 'attachment_path', 'attachment_original_name', 'attachment_mime', 'attachment_size'])]
class Assignment extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return ['status' => PublicationStatus::class, 'published_at' => 'datetime', 'deadline_at' => 'datetime', 'attachment_size' => 'integer'];
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if (! $user->is_active) {
            return $query->whereRaw('1 = 0');
        }
        if ($user->role === UserRole::Administrator) {
            return $query;
        }
        $query->whereHas('teachingAssignment', fn ($teaching) => $teaching
            ->whereHas('classroom', fn ($classroom) => $classroom->where('is_active', true)->whereHas('academicYear', fn ($year) => $year->where('is_active', true)))
            ->whereHas('subject', fn ($subject) => $subject->where('is_active', true)));
        if ($user->role === UserRole::Teacher && $user->teacher?->status === TeacherStatus::Active) {
            return $query->whereHas('teachingAssignment', fn ($teaching) => $teaching->where('teacher_id', $user->teacher->id));
        }
        if ($user->role === UserRole::Student && $user->student?->status === StudentStatus::Active) {
            return $query->where('status', PublicationStatus::Published)->whereNotNull('published_at')->where('published_at', '<=', now())
                ->whereHas('teachingAssignment', fn ($teaching) => $teaching->where('classroom_id', $user->student->classroom_id));
        }

        return $query->whereRaw('1 = 0');
    }

    public function teachingAssignment(): BelongsTo
    {
        return $this->belongsTo(TeachingAssignment::class);
    }
}
