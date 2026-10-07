<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') — {{ $siteTitle }}</title>
    <link rel="icon" href="{{ $siteLogoUrl }}">
    @fonts('poppins')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="school-body">
    <a class="skip-link" href="#main-content">Lewati navigasi</a>
    <div class="app-shell">
        <aside class="sidebar">
            <a class="brand brand-sidebar" href="{{ route('dashboard') }}">
                <img class="brand-logo" src="{{ $siteLogoUrl }}" alt="Logo {{ $siteTitle }}">
                <strong>{{ $siteTitle }}</strong>
            </a>
            <nav aria-label="Navigasi utama">
                <a class="nav-link" href="{{ route('dashboard') }}" @if(request()->routeIs('*.dashboard')) aria-current="page" @endif>Dashboard</a>
                @if (auth()->user()->role === \App\Enums\UserRole::Administrator)
                    @foreach (['users' => 'Admin', 'teachers' => 'Guru', 'students' => 'Siswa', 'academic-years' => 'Tahun ajaran', 'classrooms' => 'Kelas / rombel', 'subjects' => 'Mata pelajaran', 'teaching-assignments' => 'Penugasan mengajar', 'schedules' => 'Jadwal pembelajaran'] as $menu => $label)
                        <a class="nav-link" href="{{ route('administrator.'.$menu.'.index') }}" @if(request()->routeIs('administrator.'.$menu.'.*')) aria-current="page" @endif>{{ $label }}</a>
                    @endforeach
                @else
                    <a class="nav-link" href="{{ route(auth()->user()->role->value.'.schedules.index') }}" @if(request()->routeIs('*.schedules.*')) aria-current="page" @endif>Jadwal pembelajaran</a>
                @endif
            @foreach(['materials' => 'Materi pembelajaran', 'assignments' => 'Tugas pembelajaran', 'information' => 'Informasi pembelajaran'] as $menu => $label)
                <a class="nav-link" href="{{ route(auth()->user()->role->value.'.'.$menu.'.index') }}" @if(request()->routeIs('*.'.$menu.'.*')) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
                <a class="nav-link" href="{{ route('settings.index') }}" @if(request()->routeIs('settings.*')) aria-current="page" @endif>Pengaturan</a>
            </nav>
        </aside>
        <div class="app-content">
            <header class="topbar">
                <div class="account-summary">
                    <span class="account-avatar">@if($profilePhotoUrl)<img src="{{ $profilePhotoUrl }}" alt="">@else{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}@endif</span>
                    <span class="account-copy"><strong>{{ auth()->user()->name }}</strong><span>{{ auth()->user()->role->label() }}</span></span>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="button-danger" type="submit">Keluar</button>
                </form>
            </header>
            <main class="main-content" id="main-content" tabindex="-1">
                @if (session('status'))
                    <p data-swal-success hidden>{{ session('status') }}</p>
                    <noscript><p class="notice" role="status">{{ session('status') }}</p></noscript>
                @endif
                @if ($errors->any())
                    <div class="error-summary" role="alert"><p>Periksa data berikut:</p><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
