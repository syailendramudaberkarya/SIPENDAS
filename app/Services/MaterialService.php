<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Material;
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

class MaterialService
{
    public static function safePath(?string $path): bool
    {
        return $path !== null && preg_match('/\Amaterials\/[a-zA-Z0-9]{40}\.(pdf|jpg|jpeg|png)\z/', $path) === 1;
    }

    public function save(User $user, array $data, ?Material $material = null): Material
    {
        Gate::forUser($user)->authorize($material ? 'update' : 'create', $material ?? Material::class);
        $this->ownedTeaching($user, $data['teaching_assignment_id']);
        $newPath = null;
        $metadata = [];
        try {
            if (($data['attachment'] ?? null) instanceof UploadedFile) {
                $file = $data['attachment'];
                try {
                    $newPath = Storage::disk('learning')->putFile('materials', $file);
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

            return DB::transaction(function () use ($user, $data, $material, $metadata) {
                $teaching = $this->ownedTeaching($user, $data['teaching_assignment_id'], true);
                $record = $material ? Material::query()->lockForUpdate()->findOrFail($material->id) : new Material;
                Gate::forUser($user->fresh())->authorize($material ? 'update' : 'create', $material ? $record : Material::class);
                $oldPath = $record->attachment_disk === 'learning' ? $record->attachment_path : null;
                $values = Arr::only($data, ['title', 'content', 'status']);
                $values['teaching_assignment_id'] = $teaching->id;
                $values['published_at'] = $data['status'] === 'published' ? ($data['published_at'] ?? now()) : null;
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

    public function archive(User $user, Material $material): void
    {
        DB::transaction(function () use ($user, $material) {
            $record = Material::query()->lockForUpdate()->findOrFail($material->id);
            Gate::forUser($user->fresh())->authorize('delete', $record);
            $record->delete();
        });
    }

    public function restore(User $user, int $materialId): void
    {
        DB::transaction(function () use ($user, $materialId) {
            $record = Material::withTrashed()->lockForUpdate()->findOrFail($materialId);
            Gate::forUser($user->fresh())->authorize('restore', $record);
            $record->restore();
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
                Log::warning('Pembersihan lampiran materi tertunda.');
            }
        } catch (Throwable) {
            Log::warning('Pembersihan lampiran materi tertunda.');
        }
    }
}
