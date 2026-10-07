@extends('layouts.app')
@section('title', 'Jadwal Pembelajaran')
@section('content')
    <p class="eyebrow">Pembelajaran</p>
    <h1>Jadwal pembelajaran</h1>
    <p class="muted">Jadwal kelas dan mata pelajaran yang dapat Anda akses.</p>
    <section class="panel">
        <form method="GET" action="{{ route(auth()->user()->role->value.'.schedules.index') }}" class="search-form">
            <label for="q">Cari kelas atau mata pelajaran</label>
            <div>
                <input id="q" name="q" value="{{ $q }}" maxlength="100" placeholder="Kata pencarian">
                <label for="day_of_week">Hari</label>
                <select id="day_of_week" name="day_of_week"><option value="">Semua hari</option>@foreach(\App\Models\Schedule::DAYS as $value => $label)<option value="{{ $value }}" @selected((string)$day === (string)$value)>{{ $label }}</option>@endforeach</select>
                <button class="button-secondary" type="submit">Tampilkan</button>
            </div>
        </form>
        <div class="table-scroll"><table>
            <thead><tr><th>Hari</th><th>Jam</th><th>Kelas</th><th>Mata pelajaran</th><th>Guru</th><th>Detail</th></tr></thead>
            <tbody>@forelse($schedules as $schedule)
                <tr><td>{{ $schedule->day_name }}</td><td>{{ $schedule->start_time }}–{{ $schedule->end_time }}</td><td>{{ $schedule->teachingAssignment->classroom->name }}</td><td>{{ $schedule->teachingAssignment->subject->name }}</td><td>{{ $schedule->teachingAssignment->teacher->user->name }}</td><td><a href="{{ route(auth()->user()->role->value.'.schedules.show', $schedule) }}">Lihat</a></td></tr>
            @empty
                <tr><td colspan="6"><p class="empty-state">Belum ada jadwal yang sesuai. Hubungi administrator jika jadwal kelas belum tersedia.</p></td></tr>
            @endforelse</tbody>
        </table></div>
        <div class="pagination">{{ $schedules->links() }}</div>
    </section>
@endsection
