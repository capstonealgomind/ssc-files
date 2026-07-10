@extends('reports.layout')

@section('content')
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
        <div class="section-title">Turnout by Department</div>
        <table class="data">
            <thead>
                <tr>
                    <th>Department</th>
                    <th class="right">Voted</th>
                    <th class="right">Registered</th>
                    <th class="right">Turnout</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($report['by_department'] as $row)
                    <tr>
                        <td>{{ $row['label'] }}</td>
                        <td class="right">{{ $row['voted'] }}</td>
                        <td class="right">{{ $row['total_registered'] }}</td>
                        <td class="right">{{ $row['turnout_rate'] }}%</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="muted">No department turnout data.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Turnout by Year Level</div>
        <table class="data">
            <thead>
                <tr>
                    <th>Year Level</th>
                    <th class="right">Voted</th>
                    <th class="right">Registered</th>
                    <th class="right">Turnout</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($report['by_year_level'] as $row)
                    <tr>
                        <td>{{ $row['label'] }}</td>
                        <td class="right">{{ $row['voted'] }}</td>
                        <td class="right">{{ $row['total_registered'] }}</td>
                        <td class="right">{{ $row['turnout_rate'] }}%</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="muted">No year level turnout data.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
