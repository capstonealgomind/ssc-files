<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Ballot Receipt - {{ $receipt_number }}</title>
    <style>
        @page {
            margin: 16mm 18mm;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { width: 100%; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #111;
            line-height: 1.4;
        }
        .sheet {
            page-break-inside: avoid;
            page-break-after: avoid;
        }
        .header {
            border-bottom: 2px solid #1e3a5f;
            padding-bottom: 12px;
            margin-bottom: 14px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td { vertical-align: middle; }
        .header-logo {
            width: 20%;
            text-align: center;
        }
        .header-logo img {
            max-height: 62px;
            max-width: 105px;
        }
        .header-center {
            width: 60%;
            text-align: center;
            padding: 0 10px;
        }
        .header-org {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #1e3a5f;
            line-height: 1.35;
        }
        .header-title {
            font-size: 22px;
            font-weight: bold;
            margin: 4px 0 2px;
            letter-spacing: 1.5px;
            color: #0f172a;
        }
        .header-subtitle {
            font-size: 9px;
            color: #334155;
            margin-bottom: 6px;
        }
        .header-doc {
            display: inline-block;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            margin-top: 4px;
            padding: 3px 14px 2px;
            border: 1px solid #1e3a5f;
            color: #1e3a5f;
        }
        .status {
            text-align: center;
            border: 1px solid #86efac;
            background: #f0fdf4;
            padding: 8px 12px;
            margin-bottom: 14px;
        }
        .status-label {
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #166534;
        }
        .status-value {
            font-size: 12px;
            font-weight: bold;
            color: #14532d;
            margin-top: 2px;
        }
        .receipt-no {
            font-size: 10px;
            font-weight: bold;
            color: #1e3a5f;
            margin-top: 3px;
        }
        .columns {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        .columns .card {
            vertical-align: top;
            width: 49%;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            padding: 10px 12px;
        }
        .columns .gutter {
            width: 2%;
        }
        .section-title {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.7px;
            color: #1e3a5f;
            margin-bottom: 8px;
            border-bottom: 1px solid #94a3b8;
            padding-bottom: 4px;
        }
        .info-grid {
            width: 100%;
            border-collapse: collapse;
        }
        .info-row td {
            padding: 3px 0;
            vertical-align: top;
            font-size: 10px;
        }
        .info-label {
            width: 108px;
            color: #475569;
        }
        .info-value {
            font-weight: bold;
            color: #0f172a;
        }
        .selections-wrap {
            page-break-inside: avoid;
        }
        .selections-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            page-break-inside: avoid;
        }
        .selections-table th,
        .selections-table td {
            text-align: left;
            padding: 6px 10px;
            border: 1px solid #64748b;
            font-size: 10px;
            line-height: 1.3;
        }
        .selections-table th {
            font-size: 8.5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: #e2e8f0;
            color: #1e3a5f;
        }
        .selections-table tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }
        .footer {
            margin-top: 16px;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            font-size: 9px;
            text-align: center;
            color: #334155;
            line-height: 1.45;
        }
        .footer p { margin-bottom: 3px; }
        .footer-meta {
            margin-top: 6px;
            font-size: 8px;
            color: #64748b;
        }
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

        <div class="status">
            <div class="status-label">Ballot Status</div>
            <div class="status-value">Successfully Recorded</div>
            <div class="receipt-no">{{ $receipt_number }}</div>
        </div>

        <table class="columns">
            <tr>
                <td class="card">
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
                <td class="gutter"></td>
                <td class="card">
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
            <p class="footer-meta">Generated on {{ $generated_at }} · {{ $app_name }}</p>
        </div>
    </div>
</body>
</html>
