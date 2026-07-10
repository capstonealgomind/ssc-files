@extends('reports.layout')

@section('content')
    <div class="section">
        <div class="section-title">Registration Summary</div>
        <table class="data">
            <thead>
                <tr>
                    <th>Total Voters</th>
                    <th>Verified</th>
                    <th>Pending</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $report['summary']['total_voters'] }}</td>
                    <td>{{ $report['summary']['verified'] }}</td>
                    <td>{{ $report['summary']['pending'] }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">By Department</div>
        <table class="data">
            <thead>
                <tr>
                    <th>Department</th>
                    <th class="right">Total</th>
                    <th class="right">Verified</th>
                    <th class="right">Pending</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($report['by_department'] as $row)
                    <tr>
                        <td>{{ $row['label'] }}</td>
                        <td class="right">{{ $row['total'] }}</td>
                        <td class="right">{{ $row['verified'] }}</td>
                        <td class="right">{{ $row['pending'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="muted">No voter registration data by department.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
