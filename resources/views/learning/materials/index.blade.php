@extends('layouts.app')
@section('title', 'Materi Pembelajaran')
@section('content')
    <div class="page-heading">
        <div><h1>Materi pembelajaran</h1><p class="muted">Materi sesuai kewenangan dan kelas Anda.</p></div>
        @can('create', \App\Models\Material::class)<a class="button-primary" href="{{ route(auth()->user()->role->value.'.materials.create') }}">Tambah materi</a>@endcan
    </div>
    <section class="panel">
        <form method="GET" class="search-form"><label for="q">Cari judul atau konten</label><div><input id="q" name="q" value="{{ $q }}" maxlength="100"><button class="button-secondary">Cari</button></div></form>
        <div class="table-scroll"><table><thead><tr><th>Judul</th><th>Kelas</th><th>Mata pelajaran</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
        @forelse($materials as $material)
            <tr><td>{{ $material->title }}</td><td>{{ $material->teachingAssignment->classroom->name }}</td><td>{{ $material->teachingAssignment->subject->name }}</td><td>{{ $material->trashed() ? 'Diarsipkan' : $material->status->label() }}</td><td>
                @if ($material->trashed())
                    <form method="POST" action="{{ route(auth()->user()->role->value.'.materials.restore', $material->id) }}" data-confirm="Aktifkan kembali materi ini? Materi akan kembali tersedia sesuai status publikasinya.">@csrf @method('PATCH')<button class="activate-link" type="submit">Aktifkan</button></form>
                @else
                    <a href="{{ route(auth()->user()->role->value.'.materials.show', $material) }}">Buka</a>
                @endif
            </td></tr>
        @empty<tr><td colspan="5"><p class="empty-state">Belum ada materi yang dapat ditampilkan.</p></td></tr>@endforelse
        </tbody></table></div><div class="pagination">{{ $materials->links() }}</div>
    </section>
@endsection
