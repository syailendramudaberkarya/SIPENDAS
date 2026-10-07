@extends('layouts.app')
@section('title', $material->title)
@section('content')
    <h1>{{ $material->title }}</h1>
    <section class="panel content-detail">
        <div class="content-meta">
            <strong>{{ $material->teachingAssignment->classroom->name }} · {{ $material->teachingAssignment->subject->name }}</strong>
            <span>{{ $material->status->label() }} · {{ $material->published_at?->format('d/m/Y H:i') ?? 'Belum diterbitkan' }}</span>
        </div>
        <div class="content-body">{{ $material->content }}</div>
        <div class="content-actions">
            @if($material->attachment_path)<a class="button-secondary" href="{{ route(auth()->user()->role->value.'.materials.download', $material) }}">Unduh {{ $material->attachment_original_name }}</a>@endif
            @can('update', $material)<a class="button-secondary" href="{{ route(auth()->user()->role->value.'.materials.edit', $material) }}">Edit materi</a>@endcan
            @can('delete', $material)<form method="POST" action="{{ route(auth()->user()->role->value.'.materials.destroy', $material) }}" data-confirm="Arsipkan materi ini? Siswa tidak dapat membukanya lagi.">@csrf @method('DELETE')<button class="button-secondary">Arsipkan materi</button></form>@endcan
        </div>
    </section>
    <a class="back-link" href="{{ route(auth()->user()->role->value.'.materials.index') }}">Kembali ke daftar</a>
@endsection
