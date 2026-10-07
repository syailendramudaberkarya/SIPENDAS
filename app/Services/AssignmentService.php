<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Assignment;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class AssignmentService
{
    public static function safePath(?string $path): bool
    {
        return $path !== null && preg_match('/\Aassignments\/[a-zA-Z0-9]{40}\.(pdf|jpg|jpeg|png)\z/', $path) === 1;
    }

    public function save(User $user, array $data, ?Assignment $assignment = null): Assignment
    {
        Gate::forUser($user)->authorize($assignment ? 'update' : 'create', $assignment ?? Assignment::class);
        $this->ownedTeaching($user, $data['teaching_assignment_id']);
        $newPath = null;
        $metadata = [];
        try {
            if (($data['attachment'] ?? null) instanceof UploadedFile) {
                $file = $data['attachment'];
                try {
                    $newPath = Storage::disk('learning')->putFile('assignments', $file);
                    if (! $newPath || ! self::safePath($newPath)) {
                        throw new \RuntimeException('Invalid storage result');
                    }
                } catch (Throwable) {
                    throw ValidationException::withMessages(['attachment' => 'Lampiran gagal disimpan. Silakan coba kembali.']);
                }
                $metadata = ['attachment_disk' => 'learning', 'attachment_path' => $newPath, 'attachment_original_name' => $file->getClientOriginalName(), 'attachment_mime' => $file->getMimeType(), 'attachment_size' => $file->getSize()];
            } elseif ($data['remove_attachment'] ?? false) {
                $metadata = array_fill_keys(['attachment_disk', 'attachment_path', 'attachment_original_name', 'attachment_mime', 'attachment_size'], null);
            }

            return DB::transaction(function () use ($user, $data, $assignment, $metadata) {
                $teaching = $this->ownedTeaching($user, $data['teaching_assignment_id'], true);
                $record = $assignment ? Assignment::query()->lockForUpdate()->findOrFail($assignment->id) : new Assignment;
                Gate::forUser($user->fresh())->authorize($assignment ? 'update' : 'create', $assignment ? $record : Assignment::class);
                $oldPath = $record->attachment_disk === 'learning' ? $record->attachment_path : null;
                $values = Arr::only($data, ['title', 'description', 'status']);
                $values['teaching_assignment_id'] = $teaching->id;
                $values['published_at'] = $data['status'] === 'published' ? ($data['published_at'] ?? now()) : null;
                $values['deadline_at'] = $data['deadline_at'] ?? null;
                $record->fill([...$values, ...$metadata])->save();
                if ($metadata !== [] && $oldPath !== null && $oldPath !== $record->attachment_path) {
                    DB::afterCommit(fn () => $this->deleteFile($oldPath));
                }

                return $record;
            }, 3);
        } catch (Throwable $exception) {
            if ($newPath) {
                $this->deleteFile($newPath);
            }
            throw $exception;
        }
    }

    public function archive(User $user, Assignment $assignment): void
    {
        DB::transaction(function () use ($user, $assignment) {
            $record = Assignment::query()->lockForUpdate()->findOrFail($assignment->id);
            Gate::forUser($user->fresh())->authorize('delete', $record);
            $record->delete();
        });
    }

    private function ownedTeaching(User $user, int|string $id, bool $lock = false): TeachingAssignment
    {
        $query = TeachingAssignment::query()->active();
        if ($user->role !== UserRole::Administrator) {
            $query->where('teacher_id', $user->teacher?->id);
        }
        if ($lock) {
            $query->lockForUpdate();
        }
        $teaching = $query->find($id);
        abort_unless($teaching, 403);

        return $teaching;
    }

    private function deleteFile(string $path): void
    {
        if (! self::safePath($path)) {
            return;
        }
        try {
            if (! Storage::disk('learning')->delete($path)) {
                Log::warning('Pembersihan lampiran tugas tertunda.');
            }
        } catch (Throwable) {
            Log::warning('Pembersihan lampiran tugas tertunda.');
        }
    }
}
