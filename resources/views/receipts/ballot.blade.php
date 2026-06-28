<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Ballot Receipt - {{ $receipt_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #000;
            line-height: 1.5;
            padding: 36px;
        }
        .header {
            border-bottom: 2px solid #000;
            padding-bottom: 14px;
            margin-bottom: 22px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td {
            vertical-align: middle;
        }
        .header-logo {
            width: 28%;
            text-align: center;
        }
        .header-logo img {
            max-height: 78px;
            max-width: 130px;
        }
        .header-center {
            width: 44%;
            text-align: center;
            padding: 0 6px;
        }
        .header-org {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            line-height: 1.4;
        }
        .header-title {
            font-size: 20px;
            font-weight: bold;
            margin: 6px 0 2px;
            letter-spacing: 1px;
        }
        .header-subtitle {
            font-size: 10px;
            margin-bottom: 6px;
        }
        .header-doc {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            margin-top: 8px;
            padding-top: 6px;
            border-top: 1px solid #000;
        }
        .section {
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 8px;
            border-bottom: 1px solid #000;
            padding-bottom: 4px;
        }
        .info-grid {
            width: 100%;
        }
        .info-row td {
            padding: 4px 0;
            vertical-align: top;
        }
        .info-label {
            width: 130px;
        }
        .info-value {
            font-weight: bold;
        }
        .selections-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }
        .selections-table th,
        .selections-table td {
            text-align: left;
            padding: 8px 10px;
            border: 1px solid #000;
        }
        .selections-table th {
            font-size: 10px;
            text-transform: uppercase;
        }
        .footer {
            margin-top: 28px;
            padding-top: 16px;
            border-top: 1px solid #000;
            font-size: 10px;
            text-align: center;
        }
        .footer p { margin-bottom: 4px; }
    </style>
</head>
<body>
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

    <div class="section">
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
    </div>

    <div class="section">
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
    </div>

    <div class="section">
        <div class="section-title">Your Selections</div>
        <table class="selections-table">
            <thead>
                <tr>
                    <th style="width: 40%;">Position</th>
                    <th>Candidate Voted</th>
                </tr>
            </thead>
            <tbody>
                @foreach($selections as $selection)
                <tr>
                    <td>{{ $selection['position'] }}</td>
                    <td><strong>{{ $selection['candidate'] }}</strong></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="footer">
        <p>This receipt confirms that your ballot was successfully submitted and recorded by the system.</p>
        <p>Keep this receipt for your records. Your vote is confidential and cannot be changed after submission.</p>
        <p style="margin-top: 8px;">Generated on {{ $generated_at }} · {{ $app_name }}</p>
    </div>
</body>
</html>
