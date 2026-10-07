@extends('layouts.app')
@section('title', $assignment->exists ? 'Edit Tugas' : 'Tambah Tugas')
@section('content')
    <h1>{{ $assignment->exists ? 'Edit tugas' : 'Tambah tugas' }}</h1>
    <section class="panel form-panel"><form method="POST" enctype="multipart/form-data" action="{{ $assignment->exists ? route(auth()->user()->role->value.'.assignments.update', $assignment) : route(auth()->user()->role->value.'.assignments.store') }}" class="school-form">
        @csrf @if($assignment->exists) @method('PUT') @endif
        @php($selectedTeaching = old('teaching_assignment_id', $assignment->teaching_assignment_id))
        <label for="teaching_assignment_id">Kelas dan mata pelajaran</label><select id="teaching_assignment_id" name="teaching_assignment_id" required><option value="">Pilih penugasan</option>@foreach($teachings as $teaching)<option value="{{ $teaching->id }}" @selected(is_scalar($selectedTeaching) && (string)$selectedTeaching === (string)$teaching->id)>@if(auth()->user()->role === \App\Enums\UserRole::Administrator){{ $teaching->teacher->user->name }} — @endif{{ $teaching->classroom->name }} — {{ $teaching->subject->name }}</option>@endforeach</select>
        <label for="title">Judul</label><input id="title" name="title" value="{{ is_scalar(old('title', $assignment->title)) ? old('title', $assignment->title) : '' }}" maxlength="200" required>
        <label for="description">Deskripsi</label><textarea id="description" name="description" rows="10" required>{{ is_scalar(old('description', $assignment->description)) ? old('description', $assignment->description) : '' }}</textarea>
        <label for="status">Status</label><select id="status" name="status">@foreach(\App\Enums\PublicationStatus::cases() as $status)<option value="{{ $status->value }}" @selected(old('status', $assignment->status->value) === $status->value)>{{ $status->label() }}</option>@endforeach</select>
        <label for="published_at">Tanggal publikasi (opsional)</label><input id="published_at" name="published_at" type="datetime-local" value="{{ is_scalar(old('published_at', $assignment->published_at?->format('Y-m-d\TH:i'))) ? old('published_at', $assignment->published_at?->format('Y-m-d\TH:i')) : '' }}">
        <p class="muted">Jika diterbitkan tanpa tanggal, waktu saat ini digunakan. Publikasi mendatang belum terlihat oleh siswa.</p>
        <label for="deadline_at">Batas waktu (opsional)</label><input id="deadline_at" name="deadline_at" type="datetime-local" value="{{ is_scalar(old('deadline_at', $assignment->deadline_at?->format('Y-m-d\TH:i'))) ? old('deadline_at', $assignment->deadline_at?->format('Y-m-d\TH:i')) : '' }}">
        <p class="muted">Batas waktu hanya informasi. Siswa tidak mengumpulkan jawaban melalui aplikasi.</p>
        <label for="attachment">Lampiran PDF, JPG/JPEG, atau PNG (maksimal 5 MB)</label><input id="attachment" name="attachment" type="file" accept=".pdf,.jpg,.jpeg,.png">
        @if($assignment->attachment_path)<p>Lampiran saat ini: {{ $assignment->attachment_original_name }}</p><label class="checkbox-label"><input type="checkbox" name="remove_attachment" value="1"> Hapus lampiran tanpa mengunggah pengganti</label>@endif
        <button class="button-primary" type="submit">Simpan tugas</button><a href="{{ route(auth()->user()->role->value.'.assignments.index') }}">Batal</a>
    </form></section>
@endsection
