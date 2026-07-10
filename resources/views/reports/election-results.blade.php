@extends('reports.layout')

@section('content')
    @if (!($report['voting_closed'] ?? false))
        <div class="note">
            Official winners are shown only after the voting phase ends. The full tally below is still available for monitoring.
        </div>
    @endif

    <div class="section">
        <div class="section-title">Participation Summary</div>
        <table class="data">
            <thead>
                <tr>
                    <th>Eligible Voters</th>
                    <th>Ballots Cast</th>
                    <th>Pending</th>
                    <th>Turnout</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $report['participation']['eligible_voters'] }}</td>
                    <td>{{ $report['participation']['ballots_cast'] }}</td>
                    <td>{{ $report['participation']['pending_voters'] }}</td>
                    <td>{{ $report['participation']['turnout_rate'] }}%</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Winners per Position</div>
        @if (empty($report['winners']))
            <p class="muted">No official winners to display yet.</p>
        @else
            <table class="data">
                <thead>
                    <tr>
                        <th>Position</th>
                        <th>Winner</th>
                        <th>Partylist</th>
                        <th class="right">Votes</th>
                        <th class="right">Share</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($report['winners'] as $winner)
                        <tr>
                            <td>{{ $winner['position'] }}</td>
                            <td>{{ $winner['candidate'] }}</td>
                            <td>{{ $winner['partylist'] }}</td>
                            <td class="right">{{ $winner['votes'] }}</td>
                            <td class="right">{{ $winner['percentage'] }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="section">
        <div class="section-title">Full Vote Tally</div>
        @forelse ($report['positions'] as $position)
            <p style="margin: 10px 0 4px; font-weight: bold;">{{ $position['name'] }} · {{ $position['total_votes'] }} votes</p>
            <table class="data">
                <thead>
                    <tr>
                        <th>Candidate</th>
                        <th>Partylist</th>
                        <th class="right">Votes</th>
                        <th class="right">Share</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($position['candidates'] as $candidate)
                        <tr>
                            <td>{{ $candidate['name'] }}@if($candidate['show_trophy']) ★@endif</td>
                            <td>{{ $candidate['partylist_label'] }}</td>
                            <td class="right">{{ $candidate['votes'] }}</td>
                            <td class="right">{{ $candidate['percentage'] }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @empty
            <p class="muted">No candidates found for this election.</p>
        @endforelse
    </div>
@endsection
