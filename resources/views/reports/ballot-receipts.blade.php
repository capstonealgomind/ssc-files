@extends('reports.layout')

@section('content')
    <div class="section">
        <div class="section-title">Ballot Receipt Log ({{ $report['count'] }})</div>
        <table class="data">
            <thead>
                <tr>
                    <th>Receipt Number</th>
                    <th>Voter ID</th>
                    <th>Name</th>
                    <th>Department</th>
                    <th>Submitted At</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($report['rows'] as $row)
                    <tr>
                        <td>{{ $row['receipt_number'] }}</td>
                        <td>{{ $row['voter_id_number'] }}</td>
                        <td>{{ $row['name'] }}</td>
                        <td>{{ $row['department'] }}</td>
                        <td>{{ $row['submitted_at'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">No ballot receipts submitted yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
