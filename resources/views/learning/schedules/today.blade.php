<section class="panel">
    <div class="page-heading"><h2>Jadwal hari ini</h2><a href="{{ route(auth()->user()->role->value.'.schedules.index') }}">Lihat semua jadwal</a></div>
    <p class="field-help">{{ now()->format('d/m/Y') }} · Zona waktu {{ config('app.timezone') }}</p>
    @forelse($todaySchedules as $schedule)
        <p><strong>{{ $schedule->start_time }}–{{ $schedule->end_time }}</strong> · {{ $schedule->teachingAssignment->subject->name }} · {{ $schedule->teachingAssignment->classroom->name }}</p>
    @empty
        <p class="empty-state">Tidak ada jadwal pembelajaran hari ini.</p>
    @endforelse
</section>
