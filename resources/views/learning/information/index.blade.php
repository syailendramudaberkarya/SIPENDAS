@extends('layouts.app')
@section('title', 'Informasi Pembelajaran')
@section('content')
    <div class="page-heading">
        <div><h1>Informasi pembelajaran</h1><p class="muted">Informasi sesuai kewenangan dan kelas Anda.</p></div>
        @can('create', \App\Models\LearningInformation::class)<a class="button-primary" href="{{ route(auth()->user()->role->value.'.information.create') }}">Tambah informasi</a>@endcan
    </div>
    <section class="panel">
        <form method="GET" class="search-form"><label for="q">Cari judul atau konten</label><div><input id="q" name="q" value="{{ $q }}" maxlength="100"><button class="button-secondary">Cari</button></div></form>
        <div class="table-scroll"><table><thead><tr><th>Judul</th><th>Kelas</th><th>Mata pelajaran</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
        @forelse($informationItems as $information)
            <tr><td>{{ $information->title }}</td><td>{{ $information->teachingAssignment->classroom->name }}</td><td>{{ $information->teachingAssignment->subject->name }}</td><td>{{ $information->status->label() }}</td><td><a href="{{ route(auth()->user()->role->value.'.information.show', $information) }}">Buka</a></td></tr>
        @empty<tr><td colspan="5"><p class="empty-state">Belum ada informasi yang dapat ditampilkan.</p></td></tr>@endforelse
        </tbody></table></div><div class="pagination">{{ $informationItems->links() }}</div>
    </section>
@endsection
