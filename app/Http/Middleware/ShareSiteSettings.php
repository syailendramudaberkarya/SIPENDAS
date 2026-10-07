<?php

namespace App\Http\Middleware;

use App\Models\SiteSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ShareSiteSettings
{
    public function handle(Request $request, Closure $next): Response
    {
        $setting = Schema::hasTable('site_settings') ? SiteSetting::current() : new SiteSetting(['site_title' => 'SIPENDAS']);
        View::share('siteTitle', $setting->site_title ?: 'SIPENDAS');
        View::share('siteLogoUrl', $setting->logo_path
            ? route('settings.logo', ['v' => $setting->updated_at?->timestamp])
            : asset('images/sipendas-logo.png'));
        $user = $request->user();
        View::share('profilePhotoUrl', $user?->avatar_path
            ? route('settings.avatar', ['v' => $user->updated_at?->timestamp])
            : null);

        return $next($request);
    }
}
