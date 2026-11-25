@extends('reports.layout')

@section('content')
    <div class="section">
        <div class="section-title">Official Students Not Registered or Voted</div>
        <p style="font-size: 9px; color: #555; margin-bottom: 8px;">
            Students in the active official list who do not have a voter account. No ballot can be recorded without registration.
            Total: <strong>{{ $report['count'] }}</strong>
        </p>
        <table class="data" style="table-layout: fixed; font-size: 8px;">
            <colgroup>
                <col style="width: 13%;"><col style="width: 22%;"><col style="width: 12%;">
                <col style="width: 18%;"><col style="width: 10%;"><col style="width: 8%;">
                <col style="width: 9%;"><col style="width: 8%;">
            </colgroup>
            <thead>
                <tr>
                    <th>Student ID</th>
                    <th>Full Name</th>
                    <th>Department</th>
                    <th>Course</th>
                    <th>Year Level</th>
                    <th>Section</th>
                    <th>Registration</th>
                    <th>Voting</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($report['rows'] as $row)
                    <tr>
                        <td>{{ $row['student_id'] }}</td>
                        <td>{{ $row['name'] }}</td>
                        <td>{{ $row['department'] }}</td>
                        <td>{{ $row['course'] }}</td>
                        <td>{{ $row['year_level'] }}</td>
                        <td>{{ $row['section'] }}</td>
                        <td>{{ $row['registration_status'] }}</td>
                        <td>{{ $row['voting_status'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="muted">All active official-list students have registered.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
