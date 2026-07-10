@extends('reports.layout')

@section('content')
    <div class="section">
        <div class="section-title">Candidate Roster ({{ $report['count'] }})</div>
        <table class="data">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Position</th>
                    <th>Partylist</th>
                    <th>Department</th>
                    <th>Course</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($report['rows'] as $row)
                    <tr>
                        <td>{{ $row['name'] }}</td>
                        <td>{{ $row['position'] }}</td>
                        <td>{{ $row['partylist'] }}</td>
                        <td>{{ $row['department'] }}</td>
                        <td>{{ $row['course'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">No candidates found for this election.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
