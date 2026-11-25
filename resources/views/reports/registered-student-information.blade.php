@extends('reports.layout')

@section('content')
    <div class="section">
        <div class="section-title">Registered Student Information</div>
        <table class="data" style="table-layout: fixed; font-size: 6px;">
            <colgroup>
                <col style="width: 8%;"><col style="width: 11%;"><col style="width: 20%;">
                <col style="width: 7%;"><col style="width: 10%;"><col style="width: 6%;">
                <col style="width: 5%;"><col style="width: 8%;"><col style="width: 9%;">
                <col style="width: 7%;"><col style="width: 9%;">
            </colgroup>
            <thead>
                <tr>
                    <th>Student ID</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Department</th>
                    <th>Course</th>
                    <th>Year</th>
                    <th>Section</th>
                    <th>Email Status</th>
                    <th>Official Match</th>
                    <th>Account Status</th>
                    <th>Registered At</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($report['rows'] as $row)
                    <tr>
                        <td>{{ $row['student_id'] }}</td>
                        <td>{{ $row['name'] }}</td>
                        <td style="font-size: 6px; word-break: break-all; overflow-wrap: anywhere;">{!! nl2br(e(wordwrap($row['email'], 10, "\n", true))) !!}</td>
                        <td>{{ $row['department'] }}</td>
                        <td>{{ $row['course'] }}</td>
                        <td>{{ $row['year_level'] }}</td>
                        <td>{{ $row['section'] }}</td>
                        <td>{{ $row['email_verification'] }}</td>
                        <td>{{ $row['official_record_match'] }}</td>
                        <td>{{ $row['account_status'] }}</td>
                        <td>{{ $row['registered_at'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="11" class="muted">No registered student records match the selected date range.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
