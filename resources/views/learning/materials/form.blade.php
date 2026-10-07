@extends('layouts.app')
@section('title', $material->exists ? 'Edit Materi' : 'Tambah Materi')
@section('content')
    <h1>{{ $material->exists ? 'Edit materi' : 'Tambah materi' }}</h1>
    <section class="panel form-panel"><form method="POST" enctype="multipart/form-data" action="{{ $material->exists ? route(auth()->user()->role->value.'.materials.update', $material) : route(auth()->user()->role->value.'.materials.store') }}" class="school-form">
        @csrf @if($material->exists) @method('PUT') @endif
        @php($selectedTeaching = old('teaching_assignment_id', $material->teaching_assignment_id))
        <label for="teaching_assignment_id">Kelas dan mata pelajaran</label><select id="teaching_assignment_id" name="teaching_assignment_id" required><option value="">Pilih penugasan</option>@foreach($teachings as $teaching)<option value="{{ $teaching->id }}" @selected(is_scalar($selectedTeaching) && (string)$selectedTeaching === (string)$teaching->id)>@if(auth()->user()->role === \App\Enums\UserRole::Administrator){{ $teaching->teacher->user->name }} — @endif{{ $teaching->classroom->name }} — {{ $teaching->subject->name }}</option>@endforeach</select>
        <label for="title">Judul</label><input id="title" name="title" value="{{ is_scalar(old('title', $material->title)) ? old('title', $material->title) : '' }}" maxlength="200" required>
        <label for="content">Konten</label><textarea id="content" name="content" rows="10" required>{{ is_scalar(old('content', $material->content)) ? old('content', $material->content) : '' }}</textarea>
        <label for="status">Status</label><select id="status" name="status">@foreach(\App\Enums\PublicationStatus::cases() as $status)<option value="{{ $status->value }}" @selected(old('status', $material->status->value) === $status->value)>{{ $status->label() }}</option>@endforeach</select>
        <label for="published_at">Tanggal publikasi (opsional)</label><input id="published_at" name="published_at" type="datetime-local" value="{{ is_scalar(old('published_at', $material->published_at?->format('Y-m-d\TH:i'))) ? old('published_at', $material->published_at?->format('Y-m-d\TH:i')) : '' }}">
        <p class="muted">Jika diterbitkan tanpa tanggal, waktu saat ini digunakan. Publikasi mendatang belum terlihat oleh siswa.</p>
        <label for="attachment">Lampiran PDF, JPG/JPEG, atau PNG (maksimal 5 MB)</label><input id="attachment" name="attachment" type="file" accept=".pdf,.jpg,.jpeg,.png">
        @if($material->attachment_path)<p>Lampiran saat ini: {{ $material->attachment_original_name }}</p><label class="checkbox-label"><input type="checkbox" name="remove_attachment" value="1"> Hapus lampiran tanpa mengunggah pengganti</label>@endif
        <button class="button-primary" type="submit">Simpan materi</button><a href="{{ route(auth()->user()->role->value.'.materials.index') }}">Batal</a>
    </form></section>
@endsection
