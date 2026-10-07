@extends('layouts.app')
@section('title', $information->title)
@section('content')
    <p class="eyebrow">Informasi pembelajaran</p><h1>{{ $information->title }}</h1>
    <section class="panel">
        <p>{{ $information->teachingAssignment->classroom->name }} · {{ $information->teachingAssignment->subject->name }}</p>
        <p class="muted">{{ $information->status->label() }} · {{ $information->published_at?->format('d/m/Y H:i') ?? 'Belum diterbitkan' }}</p>
        <div style="white-space: pre-wrap; overflow-wrap: anywhere">{{ $information->content }}</div>
        @can('update', $information)<p><a href="{{ route(auth()->user()->role->value.'.information.edit', $information) }}">Edit informasi</a></p>@endcan
        @can('delete', $information)<form method="POST" action="{{ route(auth()->user()->role->value.'.information.destroy', $information) }}" data-confirm="Arsipkan informasi ini? Siswa tidak dapat membukanya lagi.">@csrf @method('DELETE')<button class="button-secondary">Arsipkan informasi</button></form>@endcan
    </section>
    <a href="{{ route(auth()->user()->role->value.'.information.index') }}">Kembali ke daftar</a>
@endsection
