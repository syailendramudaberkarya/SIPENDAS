<section class="panel">
    <h2>Informasi terbaru</h2>
    @forelse($latestInformation as $information)
        <p><a href="{{ route(auth()->user()->role->value.'.information.show', $information) }}">{{ $information->title }}</a></p>
    @empty
        <p class="empty-state">Belum ada informasi yang dapat ditampilkan.</p>
    @endforelse
    <a href="{{ route(auth()->user()->role->value.'.information.index') }}">Lihat semua informasi</a>
</section>
