@extends('reports.layout')

@section('content')
    <div class="section">
        <div class="section-title">Vote Tally by Position</div>
        <table class="data">
            <thead>
                <tr>
                    <th>Position</th>
                    <th>Candidate</th>
                    <th>Partylist</th>
                    <th class="right">Votes</th>
                    <th class="right">Share</th>
                    <th>Leader</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($report['rows'] as $row)
                    <tr>
                        <td>{{ $row['position'] }}</td>
                        <td>{{ $row['candidate'] }}</td>
                        <td>{{ $row['partylist'] }}</td>
                        <td class="right">{{ $row['votes'] }}</td>
                        <td class="right">{{ $row['percentage'] }}%</td>
                        <td>{{ $row['is_leader'] ? 'Yes' : 'No' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="muted">No vote tally data available.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
