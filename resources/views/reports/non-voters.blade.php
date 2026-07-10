@extends('reports.layout')

@section('content')
    <div class="section">
        <div class="section-title">Non-Voters ({{ $report['count'] }})</div>
        <p class="muted" style="margin-bottom: 8px;">Verified voters who have not cast a ballot for this election.</p>
        <table class="data">
            <thead>
                <tr>
                    <th>Voter ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Department</th>
                    <th>Course</th>
                    <th>Year Level</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($report['rows'] as $row)
                    <tr>
                        <td>{{ $row['voter_id_number'] }}</td>
                        <td>{{ $row['name'] }}</td>
                        <td>{{ $row['email'] }}</td>
                        <td>{{ $row['department'] }}</td>
                        <td>{{ $row['course'] }}</td>
                        <td>{{ $row['year_level'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="muted">All verified voters have cast ballots, or no verified voters exist.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
