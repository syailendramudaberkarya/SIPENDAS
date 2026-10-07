<section class="panel">
    <h2>Materi terbaru</h2>
    @forelse($latestMaterials as $material)
        <p><a href="{{ route(auth()->user()->role->value.'.materials.show', $material) }}">{{ $material->title }}</a></p>
    @empty
        <p class="empty-state">Belum ada materi yang dapat ditampilkan.</p>
    @endforelse
    <a href="{{ route(auth()->user()->role->value.'.materials.index') }}">Lihat semua materi</a>
</section>
