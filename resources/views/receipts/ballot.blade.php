<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Ballot Receipt - {{ $receipt_number }}</title>
    <style>
        @page {
            margin: 10mm 12mm;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body {
            width: 100%;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #000;
            line-height: 1.25;
        }
        .sheet {
            page-break-inside: avoid;
            page-break-after: avoid;
        }
        .header {
            border-bottom: 1.5px solid #000;
            padding-bottom: 6px;
            margin-bottom: 8px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td {
            vertical-align: middle;
        }
        .header-logo {
            width: 22%;
            text-align: center;
        }
        .header-logo img {
            max-height: 48px;
            max-width: 90px;
        }
        .header-center {
            width: 56%;
            text-align: center;
            padding: 0 4px;
        }
        .header-org {
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            line-height: 1.25;
        }
        .header-title {
            font-size: 16px;
            font-weight: bold;
            margin: 2px 0 1px;
            letter-spacing: 1px;
        }
        .header-subtitle {
            font-size: 8px;
            margin-bottom: 3px;
        }
        .header-doc {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 4px;
            padding-top: 3px;
            border-top: 1px solid #000;
            text-decoration: underline;
        }
        .columns {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        .columns td {
            vertical-align: top;
            width: 50%;
        }
        .columns td + td {
            padding-left: 10px;
        }
        .section-title {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 4px;
            border-bottom: 1px solid #000;
            padding-bottom: 2px;
        }
        .info-grid {
            width: 100%;
            border-collapse: collapse;
        }
        .info-row td {
            padding: 1.5px 0;
            vertical-align: top;
            font-size: 9px;
        }
        .info-label {
            width: 92px;
        }
        .info-value {
            font-weight: bold;
        }
        .selections-wrap {
            page-break-inside: avoid;
        }
        .selections-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2px;
            page-break-inside: avoid;
        }
        .selections-table th,
        .selections-table td {
            text-align: left;
            padding: 3px 6px;
            border: 1px solid #000;
            font-size: 9px;
            line-height: 1.2;
        }
        .selections-table th {
            font-size: 8px;
            text-transform: uppercase;
            background: #f3f3f3;
        }
        .selections-table tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }
        .footer {
            margin-top: 8px;
            padding-top: 6px;
            border-top: 1px solid #000;
            font-size: 8px;
            text-align: center;
            line-height: 1.35;
        }
        .footer p { margin-bottom: 2px; }
    </style>
</head>
<body>
    <div class="sheet">
        <div class="header">
            <table class="header-table">
                <tr>
                    <td class="header-logo">
                        @if($bcc_logo)
                            <img src="{{ $bcc_logo }}" alt="Baao Community College">
                        @endif
                    </td>
                    <td class="header-center">
                        <div class="header-org">Baao Community College</div>
                        <div class="header-org">Supreme Student Council</div>
                        <div class="header-title">SSCEVS</div>
                        <div class="header-subtitle">Smart Student Council Electronic Voting System</div>
                        <div class="header-doc">Ballot Receipt</div>
                    </td>
                    <td class="header-logo">
                        @if($ssc_logo)
                            <img src="{{ $ssc_logo }}" alt="Supreme Student Council">
                        @endif
                    </td>
                </tr>
            </table>
        </div>

        <table class="columns">
            <tr>
                <td>
                    <div class="section-title">Receipt Details</div>
                    <table class="info-grid">
                        <tr class="info-row">
                            <td class="info-label">Receipt No.</td>
                            <td class="info-value">{{ $receipt_number }}</td>
                        </tr>
                        <tr class="info-row">
                            <td class="info-label">Date Submitted</td>
                            <td class="info-value">{{ $submitted_at }}</td>
                        </tr>
                        <tr class="info-row">
                            <td class="info-label">Election</td>
                            <td class="info-value">{{ $election['title'] }}</td>
                        </tr>
                        <tr class="info-row">
                            <td class="info-label">Voting Period</td>
                            <td class="info-value">{{ $election['voting_period'] }}</td>
                        </tr>
                    </table>
                </td>
                <td>
                    <div class="section-title">Voter Information</div>
                    <table class="info-grid">
                        <tr class="info-row">
                            <td class="info-label">Name</td>
                            <td class="info-value">{{ $voter['name'] }}</td>
                        </tr>
                        <tr class="info-row">
                            <td class="info-label">Voter ID</td>
                            <td class="info-value">{{ $voter['voter_id_number'] ?? '—' }}</td>
                        </tr>
                        @if($voter['department'])
                        <tr class="info-row">
                            <td class="info-label">Department</td>
                            <td class="info-value">{{ $voter['department'] }}</td>
                        </tr>
                        @endif
                        @if($voter['course'])
                        <tr class="info-row">
                            <td class="info-label">Course</td>
                            <td class="info-value">{{ $voter['course'] }}</td>
                        </tr>
                        @endif
                        @if($voter['year_level'])
                        <tr class="info-row">
                            <td class="info-label">Year Level</td>
                            <td class="info-value">{{ $voter['year_level'] }}</td>
                        </tr>
                        @endif
                    </table>
                </td>
            </tr>
        </table>

        <div class="selections-wrap">
            <div class="section-title">Your Selections</div>
            <table class="selections-table">
                <tr>
                    <th style="width: 40%;">Position</th>
                    <th>Candidate Voted</th>
                </tr>
                @foreach($selections as $selection)
                <tr>
                    <td>{{ $selection['position'] }}</td>
                    <td><strong>{{ $selection['candidate'] }}</strong></td>
                </tr>
                @endforeach
            </table>
        </div>

        <div class="footer">
            <p>This receipt confirms that your ballot was successfully submitted and recorded by the system.</p>
            <p>Keep this receipt for your records. Your vote is confidential and cannot be changed after submission.</p>
            <p style="margin-top: 4px;">Generated on {{ $generated_at }} · {{ $app_name }}</p>
        </div>
    </div>
</body>
</html>
