@extends('layouts.app')
@section('title', 'Tugas Pembelajaran')
@section('content')
    <div class="page-heading">
        <div><h1>Tugas pembelajaran</h1><p class="muted">Tugas sesuai kewenangan dan kelas Anda.</p></div>
        @can('create', \App\Models\Assignment::class)<a class="button-primary" href="{{ route(auth()->user()->role->value.'.assignments.create') }}">Tambah tugas</a>@endcan
    </div>
    <section class="panel">
        <form method="GET" class="search-form"><label for="q">Cari judul atau deskripsi</label><div><input id="q" name="q" value="{{ $q }}" maxlength="100"><button class="button-secondary">Cari</button></div></form>
        <div class="table-scroll"><table><thead><tr><th>Judul</th><th>Kelas</th><th>Mata pelajaran</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
        @forelse($assignments as $assignment)
            <tr><td>{{ $assignment->title }}</td><td>{{ $assignment->teachingAssignment->classroom->name }}</td><td>{{ $assignment->teachingAssignment->subject->name }}</td><td>{{ $assignment->status->label() }}</td><td><a href="{{ route(auth()->user()->role->value.'.assignments.show', $assignment) }}">Buka</a></td></tr>
        @empty<tr><td colspan="5"><p class="empty-state">Belum ada tugas yang dapat ditampilkan.</p></td></tr>@endforelse
        </tbody></table></div><div class="pagination">{{ $assignments->links() }}</div>
    </section>
@endsection
