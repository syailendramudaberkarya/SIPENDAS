@extends('layouts.app')
@section('title', 'Dashboard Guru')
@section('content')
    <p class="eyebrow">Guru</p>
    <h1>Ringkasan pembelajaran</h1>
    <p class="muted">Kelas dan mata pelajaran yang menjadi tanggung jawab Anda.</p>
    <div class="statistics">@foreach($statistics as $label => $count)<div class="statistic"><span>{{ $label }}</span><strong>{{ $count }}</strong></div>@endforeach</div>
    <section class="panel">
        <h2>Penugasan mengajar aktif</h2>
        @if ($teachingAssignments && $teachingAssignments->isNotEmpty())
            <div class="table-scroll"><table>
                <thead><tr><th>Kelas</th><th>Mata pelajaran</th><th>Tahun ajaran</th></tr></thead>
                <tbody>@foreach ($teachingAssignments as $teaching)
                    <tr><td>{{ $teaching->classroom->name }}</td><td>{{ $teaching->subject->name }}</td><td>{{ $teaching->classroom->academicYear->name }}</td></tr>
                @endforeach</tbody>
            </table></div>
            {{ $teachingAssignments->links() }}
        @else
            <p class="empty-state">Belum ada penugasan mengajar. Hubungi administrator untuk melengkapi data.</p>
        @endif
    </section>
    @include('learning.schedules.today')
    @include('learning.materials.latest')
    @include('learning.assignments.latest')
    @include('learning.information.latest')
@endsection
