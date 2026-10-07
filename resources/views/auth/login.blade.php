<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk — {{ $siteTitle }}</title>
    <link rel="icon" href="{{ $siteLogoUrl }}">
    @fonts('poppins')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="school-body">
    <main class="login-shell">
        <section class="login-intro">
            <a class="brand brand-login" href="{{ url('/') }}">
                <img class="brand-logo" src="{{ $siteLogoUrl }}" alt="Logo {{ $siteTitle }}">
                <span class="brand-copy"><strong>{{ $siteTitle }}</strong><span>Sistem pembelajaran sekolah dasar</span></span>
            </a>
            <h1>Pembelajaran terkelola.<br>Informasi tersampaikan.</h1>
            <p>Akses jadwal, materi, tugas, dan informasi pembelajaran sekolah dalam satu tempat.</p>
        </section>
        <section class="login-card" aria-labelledby="login-title">
            <h2 id="login-title">Masuk ke akun Anda</h2>
            <p class="muted">Gunakan akun yang diberikan administrator sekolah.</p>
            @if (session('login_locked_until'))
                @php($retrySeconds = max(1, session('login_locked_until') - now()->timestamp))
                <div class="login-cooldown" data-login-cooldown data-locked-until="{{ session('login_locked_until') }}" data-duration="300" role="status" aria-live="polite">
                    <div class="login-cooldown-heading">
                        <strong>Login sementara dibatasi</strong>
                        <span data-login-countdown>{{ sprintf('%02d:%02d', intdiv($retrySeconds, 60), $retrySeconds % 60) }}</span>
                    </div>
                    <div class="login-progress" role="progressbar" aria-label="Waktu tunggu login" aria-valuemin="0" aria-valuemax="300" aria-valuenow="{{ $retrySeconds }}">
                        <span data-login-progress style="width: {{ min(100, ($retrySeconds / 300) * 100) }}%"></span>
                    </div>
                    <p data-login-message>Terlalu banyak percobaan. Coba kembali setelah hitung mundur selesai.</p>
                </div>
            @endif
            @if (session('status'))
                <p data-swal-success hidden>{{ session('status') }}</p>
                <noscript><p class="notice" role="status">{{ session('status') }}</p></noscript>
            @endif
            <form method="POST" action="{{ route('login.store') }}" class="school-form" data-login-form novalidate>
                @csrf
                <div>
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" value="{{ is_string(old('email')) ? old('email') : '' }}" autocomplete="username" required autofocus aria-describedby="email-error" @error('email') aria-invalid="true" @enderror>
                    <p class="field-error" id="email-error" role="alert" @if (! $errors->has('email') || session('login_locked_until')) hidden @endif>{{ session('login_locked_until') ? '' : $errors->first('email') }}</p>
                </div>
                <div>
                    <label for="password">Password</label>
                    <div class="password-field">
                        <input id="password" name="password" type="password" autocomplete="current-password" required aria-describedby="password-error" @error('password') aria-invalid="true" @enderror>
                        <button class="password-toggle" type="button" data-password-toggle data-password-label="password" aria-controls="password" aria-pressed="false" aria-label="Tampilkan password" title="Tampilkan password">
                            <svg data-password-show aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg data-password-hide aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" hidden><path d="m3 3 18 18M10.6 10.6a2 2 0 0 0 2.8 2.8M9.9 4.2A10.8 10.8 0 0 1 12 4c6.5 0 10 8 10 8a18 18 0 0 1-2.1 3.2M6.6 6.6C3.6 8.5 2 12 2 12s3.5 8 10 8a9.8 9.8 0 0 0 4.1-.9"/></svg>
                        </button>
                    </div>
                    <p class="field-error" id="password-error" role="alert" @if (! $errors->has('password')) hidden @endif>{{ $errors->first('password') }}</p>
                </div>
                <button class="button-primary" type="submit" data-login-submit @disabled(session('login_locked_until'))>Masuk</button>
            </form>
        </section>
    </main>
</body>
</html>
