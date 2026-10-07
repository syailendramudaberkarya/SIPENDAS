@extends('layouts.app')
@section('title', 'Detail Jadwal')
@section('content')
    <p class="eyebrow">Jadwal pembelajaran</p>
    <h1>{{ $schedule->teachingAssignment->subject->name }}</h1>
    <section class="panel form-panel">
        <dl class="schedule-details">
            <dt>Hari</dt><dd>{{ $schedule->day_name }}</dd>
            <dt>Jam</dt><dd>{{ $schedule->start_time }}–{{ $schedule->end_time }}</dd>
            <dt>Kelas</dt><dd>{{ $schedule->teachingAssignment->classroom->name }}</dd>
            <dt>Guru</dt><dd>{{ $schedule->teachingAssignment->teacher->user->name }}</dd>
            <dt>Tahun ajaran</dt><dd>{{ $schedule->teachingAssignment->classroom->academicYear->name }}</dd>
        </dl>
        <a class="button-secondary" href="{{ route(auth()->user()->role->value.'.schedules.index') }}">Kembali ke jadwal</a>
    </section>
@endsection
