@extends('layouts.app')
@section('title', 'Dashboard Siswa')
@section('content')
    <p class="eyebrow">Siswa</p>
    <h1>Ruang belajar Anda</h1>
    <p class="muted">Informasi kelas yang tercatat untuk akun Anda.</p>
    <section class="panel">
        @if ($student?->classroom)
            <h2>{{ $student->classroom->name }}</h2>
            <p class="muted">Tahun ajaran {{ $student->classroom->academicYear->name }}</p>
            @if(! $hasActiveClass)<p class="notice">Profil atau kelas Anda belum aktif. Hubungi administrator sekolah.</p>@endif
        @else
            <p class="empty-state">Kelas Anda belum ditentukan. Hubungi administrator sekolah.</p>
        @endif
    </section>
    <section class="panel">
        <h2>Mata pelajaran kelas Anda</h2>
        @forelse($subjects as $subject)<p>{{ $subject->name }}</p>@empty<p class="empty-state">Belum ada mata pelajaran aktif yang dapat ditampilkan.</p>@endforelse
        <div class="pagination">{{ $subjects->links() }}</div>
        <p class="muted">Tugas terbit aktif: {{ $activeAssignmentCount }}. Batas waktu hanya informasi; tugas yang melewati batas tetap dapat dibaca pada daftar tugas.</p>
    </section>
    @include('learning.schedules.today')
    @include('learning.materials.latest')
    @include('learning.assignments.latest')
    @include('learning.information.latest')
@endsection
