@extends('layouts.app')
@section('title', 'Profil')
@section('content')
    <div class="page-heading"><div><h1>Profil akun</h1><p class="muted">Kelola identitas akun yang sedang digunakan.</p></div></div>
    @include('settings.navigation')
    <section class="panel form-panel">
        <form method="POST" action="{{ route('settings.profile') }}" enctype="multipart/form-data" class="school-form" data-validated-form novalidate>
            @csrf @method('PUT')
            <div class="profile-photo-editor">@if($profilePhotoUrl)<img src="{{ $profilePhotoUrl }}" alt="Foto profil {{ auth()->user()->name }}">@else<span>{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>@endif</div>
            <div><label for="name">Nama</label><input id="name" name="name" value="{{ is_scalar(old('name', auth()->user()->name)) ? old('name', auth()->user()->name) : '' }}" maxlength="255" required aria-describedby="name-error" @error('name') aria-invalid="true" @enderror><p class="field-error" id="name-error" role="alert" @if(!$errors->has('name')) hidden @endif>{{ $errors->first('name') }}</p></div>
            <div><label for="email">Email</label><input id="email" name="email" type="email" value="{{ is_scalar(old('email', auth()->user()->email)) ? old('email', auth()->user()->email) : '' }}" maxlength="255" required aria-describedby="email-error" @error('email') aria-invalid="true" @enderror><p class="field-error" id="email-error" role="alert" @if(!$errors->has('email')) hidden @endif>{{ $errors->first('email') }}</p></div>
            <div><label for="avatar">Foto profil baru</label><input id="avatar" name="avatar" type="file" accept=".jpg,.jpeg,.png" aria-describedby="avatar-help avatar-error" @error('avatar') aria-invalid="true" @enderror><p class="field-help" id="avatar-help">JPG, JPEG, atau PNG; maksimal 2 MB dan 4096 × 4096 piksel.</p><p class="field-error" id="avatar-error" role="alert" @if(!$errors->has('avatar')) hidden @endif>{{ $errors->first('avatar') }}</p></div>
            @if($profilePhotoUrl)<label class="checkbox-label"><input type="checkbox" name="remove_avatar" value="1"> Hapus foto profil</label>@endif
            <button class="button-primary" type="submit">Simpan profil</button>
        </form>
    </section>
@endsection
