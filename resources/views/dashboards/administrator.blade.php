@extends('layouts.app')
@section('title', 'Dashboard Administrator')
@section('content')
    <p class="eyebrow">Administrator</p>
    <h1>Ringkasan sekolah</h1>
    <div class="statistics">
        @foreach ($statistics as $label => $count)
            @php
                $route = match ($label) {
                    'Siswa' => 'administrator.students.index',
                    'Guru' => 'administrator.teachers.index',
                    'Kelas' => 'administrator.classrooms.index',
                    'Mata pelajaran' => 'administrator.subjects.index',
                    'Materi' => 'administrator.materials.index',
                    'Tugas' => 'administrator.assignments.index',
                };
            @endphp
            <a class="statistic" href="{{ route($route) }}" aria-label="Lihat {{ mb_strtolower($label) }}: {{ $count }}">
                <span>{{ $label }}</span><strong>{{ $count }}</strong>
            </a>
        @endforeach
    </div>
@endsection
