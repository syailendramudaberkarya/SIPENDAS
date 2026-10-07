@extends('layouts.app')
@section('title', $assignment->title)
@section('content')
    <p class="eyebrow">Tugas pembelajaran</p><h1>{{ $assignment->title }}</h1>
    <section class="panel">
        <p>{{ $assignment->teachingAssignment->classroom->name }} · {{ $assignment->teachingAssignment->subject->name }}</p>
        <p class="muted">{{ $assignment->status->label() }} · {{ $assignment->published_at?->format('d/m/Y H:i') ?? 'Belum diterbitkan' }}</p>
        <p>Batas waktu: {{ $assignment->deadline_at?->format('d/m/Y H:i') ?? 'Tidak ditentukan' }}</p>
        <p class="muted">Tugas ditampilkan untuk dibaca. Tidak ada pengumpulan atau penilaian melalui aplikasi.</p>
        <div style="white-space: pre-wrap; overflow-wrap: anywhere">{{ $assignment->description }}</div>
        @if($assignment->attachment_path)<p><a class="button-secondary" href="{{ route(auth()->user()->role->value.'.assignments.download', $assignment) }}">Unduh {{ $assignment->attachment_original_name }}</a></p>@endif
        @can('update', $assignment)<p><a href="{{ route(auth()->user()->role->value.'.assignments.edit', $assignment) }}">Edit tugas</a></p>@endcan
        @can('delete', $assignment)<form method="POST" action="{{ route(auth()->user()->role->value.'.assignments.destroy', $assignment) }}" data-confirm="Arsipkan tugas ini? Siswa tidak dapat membukanya lagi.">@csrf @method('DELETE')<button class="button-secondary">Arsipkan tugas</button></form>@endcan
    </section>
    <a href="{{ route(auth()->user()->role->value.'.assignments.index') }}">Kembali ke daftar</a>
@endsection
