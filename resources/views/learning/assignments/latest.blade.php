<section class="panel">
    <h2>Tugas terbaru</h2>
    @forelse($latestAssignments as $assignment)
        <p><a href="{{ route(auth()->user()->role->value.'.assignments.show', $assignment) }}">{{ $assignment->title }}</a></p>
        <p class="field-help">{{ $assignment->status->label() }} · Batas waktu {{ $assignment->deadline_at?->format('d/m/Y H:i') ?? 'tidak ditentukan' }}</p>
    @empty
        <p class="empty-state">Belum ada tugas yang dapat ditampilkan.</p>
    @endforelse
    <a href="{{ route(auth()->user()->role->value.'.assignments.index') }}">Lihat semua tugas</a>
</section>
