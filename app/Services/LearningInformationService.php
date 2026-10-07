<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\LearningInformation;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class LearningInformationService
{
    public function save(User $user, array $data, ?LearningInformation $information = null): LearningInformation
    {
        Gate::forUser($user)->authorize($information ? 'update' : 'create', $information ?? LearningInformation::class);

        return DB::transaction(function () use ($user, $data, $information) {
            $actor = $user->fresh();
            $teachingQuery = TeachingAssignment::query()->active();
            if ($actor->role !== UserRole::Administrator) {
                $teachingQuery->where('teacher_id', $actor->teacher?->id);
            }
            $teaching = $teachingQuery->lockForUpdate()->find($data['teaching_assignment_id']);
            abort_unless($teaching, 403);
            $record = $information ? LearningInformation::query()->lockForUpdate()->findOrFail($information->id) : new LearningInformation;
            Gate::forUser($actor)->authorize($information ? 'update' : 'create', $information ? $record : LearningInformation::class);
            $values = Arr::only($data, ['title', 'content', 'status']);
            $values['teaching_assignment_id'] = $teaching->id;
            $values['published_at'] = $data['status'] === 'published' ? ($data['published_at'] ?? now()) : null;
            $record->fill($values)->save();

            return $record;
        }, 3);
    }

    public function archive(User $user, LearningInformation $information): void
    {
        DB::transaction(function () use ($user, $information) {
            $record = LearningInformation::query()->lockForUpdate()->findOrFail($information->id);
            Gate::forUser($user->fresh())->authorize('delete', $record);
            $record->delete();
        });
    }
}
