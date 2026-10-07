<?php

namespace App\Policies;

use App\Enums\PublicationStatus;
use App\Enums\StudentStatus;
use App\Enums\TeacherStatus;
use App\Enums\UserRole;
use App\Models\Assignment;
use App\Models\LearningInformation;
use App\Models\Material;
use App\Models\TeachingAssignment;
use App\Models\User;

abstract class LearningContentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, Material|Assignment|LearningInformation $content): bool
    {
        if (! $user->is_active || $content->trashed()) {
            return false;
        }

        if ($user->role === UserRole::Administrator) {
            return true;
        }

        $teaching = $content->teachingAssignment;

        if ($user->role === UserRole::Teacher) {
            return $this->ownsTeaching($user, $teaching);
        }

        $student = $user->student;

        return $student !== null
            && $student->status === StudentStatus::Active
            && $student->classroom_id === $teaching->classroom_id
            && $teaching->classroom->is_active
            && $teaching->classroom->academicYear->is_active
            && $teaching->subject->is_active
            && $content->status === PublicationStatus::Published
            && $content->published_at !== null
            && $content->published_at->lte(now());
    }

    public function create(User $user): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($user->role === UserRole::Administrator) {
            return TeachingAssignment::query()->active()->exists();
        }

        return $user->role === UserRole::Teacher
            && $user->teacher?->status === TeacherStatus::Active
            && $user->teacher->teachingAssignments()->active()->exists();
    }

    public function update(User $user, Material|Assignment|LearningInformation $content): bool
    {
        return $user->is_active && ! $content->trashed()
            && ($user->role === UserRole::Administrator || $this->ownsTeaching($user, $content->teachingAssignment));
    }

    public function delete(User $user, Material|Assignment|LearningInformation $content): bool
    {
        return $this->update($user, $content);
    }

    public function restore(User $user, Material|Assignment|LearningInformation $content): bool
    {
        return $user->is_active && $content->trashed()
            && ($user->role === UserRole::Administrator || $this->ownsTeaching($user, $content->teachingAssignment));
    }

    public function download(User $user, Material|Assignment|LearningInformation $content): bool
    {
        return $this->view($user, $content);
    }

    private function ownsTeaching(User $user, TeachingAssignment $teaching): bool
    {
        return $user->role === UserRole::Teacher
            && $user->teacher?->status === TeacherStatus::Active
            && $user->teacher->id === $teaching->teacher_id
            && $teaching->classroom->is_active
            && $teaching->classroom->academicYear->is_active
            && $teaching->subject->is_active;
    }
}
