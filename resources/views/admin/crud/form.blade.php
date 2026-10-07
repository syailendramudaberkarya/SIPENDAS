@extends('layouts.app')
@section('title', ($record->exists ? 'Edit ' : 'Tambah ').$title)
@section('content')
    <p class="eyebrow">Administrasi sekolah</p>
    <h1>{{ $record->exists ? 'Edit' : 'Tambah' }} {{ $title }}</h1>
    <p class="muted">Lengkapi data di bawah, kemudian simpan perubahan.</p>
    <section class="panel form-panel">
        <form method="POST" action="{{ $record->exists ? route('administrator.'.$resource.'.update', $record) : route('administrator.'.$resource.'.store') }}" class="school-form" data-account-form data-validated-form novalidate>
            @csrf
            @if ($record->exists) @method('PUT') @endif
            @foreach ($fields as $key => $field)
                @php
                    $type = $field['type'] ?? 'text';
                    $value = array_key_exists('value', $field) ? $field['value'] : ($record->getAttribute($key) ?? $field['default'] ?? '');
                    if ($value instanceof \DateTimeInterface) { $value = $value->format('Y-m-d'); }
                    $value = $type === 'password' ? '' : old($key, $value);
                    $value = is_scalar($value) || $value === null ? $value : '';
                @endphp
                @if ($type === 'hidden')
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @else
                    <div @isset($field['group']) data-profile-role="{{ $field['group'] }}" @endisset>
                        @if ($type === 'checkbox')
                            <input type="hidden" name="{{ $key }}" value="0">
                            <label class="checkbox-label"><input id="{{ $key }}" name="{{ $key }}" type="checkbox" value="1" @checked((bool) $value)> {{ $field['label'] }}</label>
                        @else
                            <label for="{{ $key }}">{{ $field['label'] }}</label>
                            @if ($type === 'select')
                                <select id="{{ $key }}" name="{{ $key }}" @required($field['required'] ?? false) aria-describedby="{{ $key }}-error" @error($key) aria-invalid="true" @enderror>
                                    <option value="">Pilih {{ mb_strtolower($field['label']) }}</option>
                                    @foreach ($field['options'] as $optionValue => $optionLabel)
                                        <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>{{ $optionLabel }}</option>
                                    @endforeach
                                </select>
                            @elseif ($type === 'textarea')
                                <textarea id="{{ $key }}" name="{{ $key }}" rows="4" @required($field['required'] ?? false) aria-describedby="{{ $key }}-error" @error($key) aria-invalid="true" @enderror>{{ $value }}</textarea>
                            @else
                                @if ($type === 'password')<div class="password-field">@endif
                                <input id="{{ $key }}" name="{{ $key }}" type="{{ $type }}" value="{{ $value }}" @required($field['required'] ?? false) aria-describedby="{{ $key }}-error" @isset($field['inputmode']) inputmode="{{ $field['inputmode'] }}" @endisset @isset($field['pattern']) pattern="{{ $field['pattern'] }}" @endisset @if ($field['digits_only'] ?? false) data-digits-only @endif @if ($type === 'password') autocomplete="new-password" @endif @error($key) aria-invalid="true" @enderror>
                                @if ($type === 'password')
                                    <button class="password-toggle" type="button" data-password-toggle data-password-label="{{ mb_strtolower($field['label']) }}" aria-controls="{{ $key }}" aria-pressed="false" aria-label="Tampilkan {{ mb_strtolower($field['label']) }}" title="Tampilkan {{ mb_strtolower($field['label']) }}">
                                        <svg data-password-show aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                                        <svg data-password-hide aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" hidden><path d="m3 3 18 18M10.6 10.6a2 2 0 0 0 2.8 2.8M9.9 4.2A10.8 10.8 0 0 1 12 4c6.5 0 10 8 10 8a18 18 0 0 1-2.1 3.2M6.6 6.6C3.6 8.5 2 12 2 12s3.5 8 10 8a9.8 9.8 0 0 0 4.1-.9"/></svg>
                                    </button>
                                </div>
                                @endif
                            @endif
                        @endif
                        @isset($field['help'])<p class="field-help">{{ $field['help'] }}</p>@endisset
                        @if ($type !== 'checkbox')
                            <p class="field-error" id="{{ $key }}-error" role="alert" @if (! $errors->has($key)) hidden @endif>{{ $errors->first($key) }}</p>
                        @endif
                    </div>
                @endif
            @endforeach
            <div class="form-actions"><button class="button-primary" type="submit">Simpan</button><a href="{{ route('administrator.'.$resource.'.index') }}" class="button-secondary">Batal</a></div>
        </form>
    </section>
@endsection
