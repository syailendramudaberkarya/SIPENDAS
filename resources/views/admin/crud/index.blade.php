@extends('layouts.app')
@section('title', $title)
@section('content')
    <p class="eyebrow">Administrasi sekolah</p>
    <div class="page-heading">
        <div><h1>{{ $title }}</h1><p class="muted">Kelola data {{ mb_strtolower($title) }} sekolah.</p></div>
        <a class="button-primary" href="{{ route('administrator.'.$resource.'.create') }}">Tambah data</a>
    </div>
    <section class="panel">
        <form method="GET" action="{{ route('administrator.'.$resource.'.index') }}" class="search-form">
            <label for="q">Cari {{ mb_strtolower($title) }}</label>
            <div><input id="q" name="q" value="{{ $q }}" maxlength="100" placeholder="Ketik kata pencarian"><button type="submit" class="button-secondary">Cari</button>
            @if ($q !== '')<a href="{{ route('administrator.'.$resource.'.index') }}">Reset</a>@endif</div>
        </form>
        <div class="table-scroll">
            <table>
                <thead><tr>@foreach ($columns as $label)<th>{{ $label }}</th>@endforeach<th>Aksi</th></tr></thead>
                <tbody>
                @forelse ($records as $record)
                    <tr>
                        @foreach ($columns as $key => $label)
                            @php
                                $value = data_get($record, $key);
                                if ($value instanceof \DateTimeInterface) {
                                    $value = $value->format('d/m/Y');
                                } elseif ($value instanceof \BackedEnum) {
                                    $value = method_exists($value, 'label') ? $value->label() : $value->value;
                                } elseif (is_bool($value)) {
                                    $value = $value ? 'Aktif' : 'Nonaktif';
                                }
                            @endphp
                            <td>{{ $value === null || $value === '' ? '—' : $value }}</td>
                        @endforeach
                        <td><div class="row-actions">
                            <a href="{{ route('administrator.'.$resource.'.edit', $record) }}">Edit</a>
                            @php
                                $accountIsActive = $deactivate && (bool) data_get($record, $resource === 'users' ? 'is_active' : 'user.is_active');
                            @endphp
                            @if ($deactivate && ! $accountIsActive)
                                <form method="POST" action="{{ route('administrator.'.$resource.'.activate', $record) }}" data-confirm="Aktifkan kembali akun ini? Akun akan dapat digunakan untuk masuk ke sistem.">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="activate-link">Aktifkan</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('administrator.'.$resource.'.destroy', $record) }}" data-confirm="{{ $deactivate ? 'Nonaktifkan akun ini? Data profil dan pembelajaran tetap disimpan.' : 'Hapus data ini? Data yang masih digunakan tidak dapat dihapus.' }}">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="danger-link">{{ $deactivate ? 'Nonaktifkan' : 'Hapus' }}</button>
                                </form>
                            @endif
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="{{ count($columns) + 1 }}"><p class="empty-state">{{ $q !== '' ? 'Tidak ada data yang sesuai dengan pencarian.' : 'Belum ada data. Gunakan tombol Tambah data untuk memulai.' }}</p></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $records->links() }}</div>
    </section>
@endsection
