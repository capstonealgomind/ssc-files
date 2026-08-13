<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Ballot Receipt - {{ $receipt_number }}</title>
    <style>
        /* DomPDF often ignores @page margins; .sheet padding is the real inset. */
        @page { margin: 0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { width: 100%; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #111;
            line-height: 1.45;
            padding: 0;
        }
        .brand-bar {
            width: 100%;
            border-collapse: collapse;
        }
        .brand-bar td { font-size: 0; line-height: 0; padding: 0; }
        .brand-bar .navy { height: 6px; background: #1e3a5f; }
        .brand-bar .gold { height: 3px; background: #c9a227; }

        .sheet {
            padding: 28px 60px 72px 60px;
        }
        .header { margin-bottom: 4px; }
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td { vertical-align: middle; }
        .header-logo {
            width: 18%;
            text-align: center;
            padding: 4px 6px;
        }
        .header-logo img {
            max-height: 78px;
            max-width: 82px;
        }
        .header-center {
            width: 64%;
            text-align: center;
            padding: 2px 12px;
        }
        .header-org {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1.4px;
            color: #1e3a5f;
            line-height: 1.4;
        }
        .header-org-sub {
            font-size: 8.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1.1px;
            color: #334155;
            margin-top: 2px;
        }
        .header-title {
            font-size: 20px;
            font-weight: bold;
            margin: 7px 0 3px;
            letter-spacing: 2.5px;
            color: #0f172a;
        }
        .header-subtitle {
            font-size: 8px;
            color: #475569;
            letter-spacing: 0.2px;
            line-height: 1.35;
        }

        .doc-title {
            width: 100%;
            border-collapse: collapse;
            margin: 14px 0 18px;
        }
        .doc-title td { vertical-align: middle; padding: 0; }
        .doc-title-text {
            width: 1%;
            white-space: nowrap;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2.2px;
            color: #1e3a5f;
            padding: 0 14px;
            text-align: center;
        }
        .doc-title-line .rule {
            border-top: 1.5px solid #1e3a5f;
            border-bottom: 2px solid #c9a227;
            height: 5px;
        }

        .columns {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }
        .columns .card {
            vertical-align: top;
            width: 48.5%;
            border: 1px solid #cbd5e1;
            border-top: 3px solid #1e3a5f;
            background: #f8fafc;
            padding: 12px 16px 14px;
        }
        .columns .gutter { width: 3%; }

        .section-title {
            font-size: 8.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #1e3a5f;
            margin-bottom: 8px;
            padding-bottom: 5px;
            border-bottom: 1px solid #c9a227;
        }

        .info-grid {
            width: 100%;
            border-collapse: collapse;
        }
        .info-row td {
            padding: 4px 0;
            vertical-align: top;
            font-size: 10px;
        }
        .info-label {
            width: 112px;
            color: #475569;
            padding-right: 8px;
        }
        .info-value {
            font-weight: bold;
            color: #0f172a;
        }

        .selections-wrap { margin-bottom: 4px; }
        .selections-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        .selections-table th,
        .selections-table td {
            text-align: left;
            padding: 8px 14px;
            border: 1px solid #94a3b8;
            font-size: 10px;
            line-height: 1.35;
        }
        .selections-table th {
            font-size: 8.5px;
            text-transform: uppercase;
            letter-spacing: 0.7px;
            background: #e8eef5;
            color: #1e3a5f;
        }
        .selections-table tr {
            page-break-inside: avoid;
        }

        .notice {
            margin-top: 20px;
            padding: 12px 16px 0;
            border-top: 1px solid #cbd5e1;
            font-size: 9px;
            text-align: center;
            color: #334155;
            line-height: 1.5;
        }
        .notice p { margin-bottom: 4px; }

        .page-footer {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            padding: 10px 60px 24px;
            text-align: center;
            font-size: 8px;
            color: #64748b;
            letter-spacing: 0.2px;
        }
        .page-footer .line {
            border-top: 1.5px solid #1e3a5f;
            border-bottom: 2px solid #c9a227;
            height: 5px;
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <table class="brand-bar">
        <tr><td class="navy"></td></tr>
        <tr><td class="gold"></td></tr>
    </table>

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
                    <div class="header-org-sub">Supreme Student Council</div>
                    <div class="header-title">SSCEVS</div>
                    <div class="header-subtitle">Smart Student Council Electronic Voting System</div>
                </td>
                <td class="header-logo">
                    @if($ssc_logo)
                        <img src="{{ $ssc_logo }}" alt="Supreme Student Council">
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <table class="doc-title">
        <tr>
            <td class="doc-title-line"><div class="rule"></div></td>
            <td class="doc-title-text">Ballot Receipt</td>
            <td class="doc-title-line"><div class="rule"></div></td>
        </tr>
    </table>

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

    <div class="notice">
        <p>This receipt confirms that your ballot was successfully submitted and recorded by the system.</p>
        <p>Keep this receipt for your records. Your vote is confidential and cannot be changed after submission.</p>
    </div>
    </div>

    <div class="page-footer">
        <div class="line"></div>
        Generated on {{ $generated_at }} &nbsp;&middot;&nbsp; {{ $app_name }} &nbsp;&middot;&nbsp; Official election record
    </div>
</body>
</html>
