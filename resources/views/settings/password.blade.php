@extends('layouts.app')
@section('title', 'Ubah Password')
@section('content')
    <div class="page-heading"><div><h1>Ubah password</h1><p class="muted">Perbarui password akun secara terpisah dan aman.</p></div></div>
    @include('settings.navigation')
    <section class="panel form-panel"><form method="POST" action="{{ route('settings.password') }}" class="school-form" data-validated-form novalidate>
        @csrf @method('PUT')
        @foreach (['current_password' => ['Password saat ini', 'current-password'], 'password' => ['Password baru', 'new-password'], 'password_confirmation' => ['Konfirmasi password baru', 'new-password']] as $field => [$label, $autocomplete])
            <div><label for="{{ $field }}">{{ $label }}</label><div class="password-field"><input id="{{ $field }}" name="{{ $field }}" type="password" autocomplete="{{ $autocomplete }}" required aria-describedby="{{ $field }}-error" @error($field) aria-invalid="true" @enderror><button class="password-toggle" type="button" data-password-toggle data-password-label="{{ mb_strtolower($label) }}" aria-controls="{{ $field }}" aria-pressed="false" aria-label="Tampilkan {{ mb_strtolower($label) }}" title="Tampilkan {{ mb_strtolower($label) }}"><svg data-password-show aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg><svg data-password-hide aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" hidden><path d="m3 3 18 18M10.6 10.6a2 2 0 0 0 2.8 2.8M9.9 4.2A10.8 10.8 0 0 1 12 4c6.5 0 10 8 10 8a18 18 0 0 1-2.1 3.2M6.6 6.6C3.6 8.5 2 12 2 12s3.5 8 10 8a9.8 9.8 0 0 0 4.1-.9"/></svg></button></div><p class="field-error" id="{{ $field }}-error" role="alert" @if(!$errors->has($field)) hidden @endif>{{ $errors->first($field) }}</p></div>
        @endforeach
        <p class="field-help">Minimal 8 karakter serta mengandung huruf dan angka.</p><button class="button-primary" type="submit">Simpan password</button>
    </form></section>
@endsection
