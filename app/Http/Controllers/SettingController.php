<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateBrandingRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\SiteSetting;
use App\Services\SiteSettingService;
use App\Services\UserProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SettingController extends Controller
{
    public function index(): View
    {
        return view('settings.index');
    }

    public function updateProfile(UpdateProfileRequest $request, UserProfileService $service): RedirectResponse
    {
        $service->update($request->user(), $request->validated());

        return to_route('settings.index')->with('status', 'Profil berhasil diperbarui.');
    }

    public function password(): View
    {
        return view('settings.password');
    }

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $request->user()->update(['password' => $request->validated('password')]);
        $request->session()->put('password_hash_web', Auth::guard('web')->hashPasswordForCookie($request->user()->getAuthPassword()));

        return to_route('settings.password.edit')->with('status', 'Password berhasil diperbarui.');
    }

    public function updateBranding(UpdateBrandingRequest $request, SiteSettingService $service): RedirectResponse
    {
        $service->update($request->validated());

        return to_route('settings.branding.edit')->with('status', 'Identitas website berhasil diperbarui.');
    }

    public function branding(): View
    {
        return view('settings.branding', ['setting' => SiteSetting::current()]);
    }

    public function avatar(): BinaryFileResponse
    {
        $path = request()->user()->avatar_path;
        abort_unless(UserProfileService::safeAvatarPath($path) && Storage::disk('profiles')->exists($path), 404);

        return response()->file(Storage::disk('profiles')->path($path), [
            'Content-Type' => str_ends_with($path, '.png') ? 'image/png' : 'image/jpeg',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=86400',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
        ]);
    }

    public function logo(): BinaryFileResponse
    {
        $path = SiteSetting::current()->logo_path;
        abort_unless(SiteSettingService::safeLogoPath($path) && Storage::disk('branding')->exists($path), 404);

        return response()->file(Storage::disk('branding')->path($path), [
            'Content-Type' => str_ends_with($path, '.png') ? 'image/png' : 'image/jpeg',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'public, max-age=86400',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
        ]);
    }
}
