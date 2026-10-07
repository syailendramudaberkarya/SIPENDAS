<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class SiteSettingService
{
    public static function safeLogoPath(?string $path): bool
    {
        return $path !== null && preg_match('/\Alogos\/[a-zA-Z0-9]{40}\.(jpg|jpeg|png)\z/', $path) === 1;
    }

    public function update(array $data): SiteSetting
    {
        $newPath = null;
        if (($data['logo'] ?? null) instanceof UploadedFile) {
            $file = $data['logo'];
            $newPath = 'logos/'.Str::random(40).'.'.strtolower($file->getClientOriginalExtension());
            Storage::disk('branding')->putFileAs('logos', $file, basename($newPath));
        }

        try {
            return DB::transaction(function () use ($data, $newPath) {
                $setting = SiteSetting::query()->lockForUpdate()->first() ?? new SiteSetting;
                $oldPath = $setting->logo_path;
                $setting->site_title = trim($data['site_title']);
                if ($newPath !== null) {
                    $setting->logo_path = $newPath;
                } elseif ($data['remove_logo'] ?? false) {
                    $setting->logo_path = null;
                }
                $setting->save();

                if ($oldPath !== null && $oldPath !== $setting->logo_path) {
                    DB::afterCommit(fn () => $this->deleteLogo($oldPath));
                }

                return $setting;
            }, 3);
        } catch (Throwable $exception) {
            if ($newPath !== null) {
                $this->deleteLogo($newPath);
            }
            throw $exception;
        }
    }

    private function deleteLogo(string $path): void
    {
        if (self::safeLogoPath($path)) {
            Storage::disk('branding')->delete($path);
        }
    }
}
