@extends('reports.layout')

@section('content')
    <div class="section">
        <div class="section-title">Partylist Summary</div>
        <table class="data">
            <thead>
                <tr>
                    <th>Partylist</th>
                    <th class="right">Total Votes</th>
                    <th class="right">Candidates</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($report['groups'] as $group)
                    <tr>
                        <td>{{ $group['label'] }}</td>
                        <td class="right">{{ $group['total_votes'] }}</td>
                        <td class="right">{{ $group['candidate_count'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="muted">No partylist data available.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Candidates by Partylist</div>
        @forelse ($report['groups'] as $group)
            <p style="margin: 10px 0 4px; font-weight: bold;">{{ $group['label'] }}</p>
            <table class="data">
                <thead>
                    <tr>
                        <th>Candidate</th>
                        <th>Position</th>
                        <th class="right">Votes</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($group['candidates'] as $candidate)
                        <tr>
                            <td>{{ $candidate['name'] }}</td>
                            <td>{{ $candidate['position'] }}</td>
                            <td class="right">{{ $candidate['votes'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @empty
            <p class="muted">No candidates found.</p>
        @endforelse
    </div>
@endsection
