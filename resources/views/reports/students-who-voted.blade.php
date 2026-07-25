@extends('reports.layout')

@section('content')
    <style>
        .compact-voters .section-title { font-size: 9px; margin-bottom: 4px; padding-bottom: 2px; }
        .compact-voters .intro { font-size: 7.5px; margin-bottom: 6px; line-height: 1.3; }
        .compact-voters table.data { font-size: 7px; line-height: 1.25; }
        .compact-voters table.data th,
        .compact-voters table.data td {
            padding: 2px 3px;
            vertical-align: middle;
            white-space: nowrap;
        }
        .compact-voters table.data th { font-size: 6.5px; letter-spacing: 0.2px; }
        .compact-voters table.data td.wrap,
        .compact-voters table.data th.wrap {
            white-space: normal;
            word-wrap: break-word;
            overflow-wrap: anywhere;
        }
        .compact-voters .col-id { width: 11%; }
        .compact-voters .col-name { width: 18%; }
        .compact-voters .col-email { width: 22%; }
        .compact-voters .col-dept { width: 7%; }
        .compact-voters .col-course { width: 22%; }
        .compact-voters .col-year { width: 8%; }
        .compact-voters .col-voted { width: 12%; }
    </style>

    <div class="section compact-voters">
        <div class="section-title">Students Who Voted ({{ $report['count'] }})</div>
        <p class="muted intro">
            Students who cast a ballot for this election.
            @if (!empty($report['filters']['department']) || !empty($report['filters']['year_level']) || !empty($report['filters']['course']))
                Filtered by
                @php
                    $parts = array_filter([
                        !empty($report['filters']['department']) ? 'Department: '.$report['filters']['department'] : null,
                        !empty($report['filters']['year_level']) ? 'Year Level: '.$report['filters']['year_level'] : null,
                        !empty($report['filters']['course']) ? 'Course/Section: '.$report['filters']['course'] : null,
                    ]);
                @endphp
                {{ implode(' · ', $parts) }}.
            @endif
        </p>
        <table class="data">
            <thead>
                <tr>
                    <th class="col-id">Voter ID</th>
                    <th class="col-name wrap">Name</th>
                    <th class="col-email wrap">Email</th>
                    <th class="col-dept">Dept</th>
                    <th class="col-course wrap">Course</th>
                    <th class="col-year">Year</th>
                    <th class="col-voted">Voted At</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($report['rows'] as $row)
                    <tr>
                        <td class="col-id">{{ $row['voter_id_number'] }}</td>
                        <td class="col-name wrap">{{ $row['name'] }}</td>
                        <td class="col-email wrap">{{ $row['email'] }}</td>
                        <td class="col-dept">{{ $row['department'] }}</td>
                        <td class="col-course wrap">{{ $row['course'] }}</td>
                        <td class="col-year">{{ $row['year_level'] }}</td>
                        <td class="col-voted">{{ $row['voted_at'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="muted">No students matched the selected filters for this election.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
