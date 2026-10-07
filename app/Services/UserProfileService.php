<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class UserProfileService
{
    public static function safeAvatarPath(?string $path): bool
    {
        return $path !== null && preg_match('/\Aavatars\/[a-zA-Z0-9]{40}\.(jpg|jpeg|png)\z/', $path) === 1;
    }

    public function update(User $user, array $data): User
    {
        $newPath = null;
        if (($data['avatar'] ?? null) instanceof UploadedFile) {
            $file = $data['avatar'];
            $newPath = 'avatars/'.Str::random(40).'.'.strtolower($file->getClientOriginalExtension());
            Storage::disk('profiles')->putFileAs('avatars', $file, basename($newPath));
        }

        try {
            return DB::transaction(function () use ($user, $data, $newPath) {
                $record = User::query()->lockForUpdate()->findOrFail($user->id);
                $oldPath = $record->avatar_path;
                $record->name = trim($data['name']);
                $record->email = mb_strtolower(trim($data['email']));
                if ($newPath !== null) {
                    $record->avatar_path = $newPath;
                } elseif ($data['remove_avatar'] ?? false) {
                    $record->avatar_path = null;
                }
                $record->save();
                if ($oldPath !== null && $oldPath !== $record->avatar_path) {
                    DB::afterCommit(fn () => $this->deleteAvatar($oldPath));
                }

                return $record;
            }, 3);
        } catch (Throwable $exception) {
            if ($newPath !== null) {
                $this->deleteAvatar($newPath);
            }
            throw $exception;
        }
    }

    private function deleteAvatar(string $path): void
    {
        if (self::safeAvatarPath($path)) {
            Storage::disk('profiles')->delete($path);
        }
    }
}
