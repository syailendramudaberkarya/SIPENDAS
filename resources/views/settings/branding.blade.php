@extends('layouts.app')
@section('title', 'Identitas Website')
@section('content')
    <div class="page-heading"><div><h1>Identitas website</h1><p class="muted">Kelola judul dan logo yang digunakan di seluruh aplikasi.</p></div></div>
    @include('settings.navigation')
    <section class="panel form-panel"><form method="POST" action="{{ route('settings.branding') }}" enctype="multipart/form-data" class="school-form" data-validated-form novalidate>
        @csrf @method('PUT')
        <div><label for="site_title">Judul website</label><input id="site_title" name="site_title" value="{{ is_scalar(old('site_title', $setting->site_title)) ? old('site_title', $setting->site_title) : '' }}" maxlength="80" required aria-describedby="site_title-error" @error('site_title') aria-invalid="true" @enderror><p class="field-error" id="site_title-error" role="alert" @if(!$errors->has('site_title')) hidden @endif>{{ $errors->first('site_title') }}</p></div>
        <div><label for="logo">Logo baru</label><div class="branding-preview"><img src="{{ $siteLogoUrl }}" alt="Logo {{ $siteTitle }}"></div><input id="logo" name="logo" type="file" accept=".jpg,.jpeg,.png" aria-describedby="logo-help logo-error" @error('logo') aria-invalid="true" @enderror><p class="field-help" id="logo-help">JPG, JPEG, atau PNG; maksimal 2 MB dan 4096 × 4096 piksel.</p><p class="field-error" id="logo-error" role="alert" @if(!$errors->has('logo')) hidden @endif>{{ $errors->first('logo') }}</p></div>
        @if($setting->logo_path)<label class="checkbox-label"><input type="checkbox" name="remove_logo" value="1"> Gunakan kembali logo bawaan</label>@endif
        <button class="button-primary" type="submit">Simpan identitas</button>
    </form></section>
@endsection
