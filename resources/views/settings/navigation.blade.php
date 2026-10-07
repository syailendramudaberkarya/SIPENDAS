<nav class="settings-navigation" aria-label="Menu pengaturan">
    <a href="{{ route('settings.index') }}" @if(request()->routeIs('settings.index', 'settings.profile')) aria-current="page" @endif>Profil</a>
    <a href="{{ route('settings.password.edit') }}" @if(request()->routeIs('settings.password*')) aria-current="page" @endif>Password</a>
    @if(auth()->user()->role === \App\Enums\UserRole::Administrator)<a href="{{ route('settings.branding.edit') }}" @if(request()->routeIs('settings.branding*')) aria-current="page" @endif>Identitas website</a>@endif
</nav>
