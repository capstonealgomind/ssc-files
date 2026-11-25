<!doctype html>
<html lang="en">
<body style="margin:0;padding:24px;background:#eef3fb;font-family:Arial,sans-serif;color:#172640">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr><td align="center">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;background:#fff;border:1px solid #d8e2f2;border-radius:14px">
                <tr><td style="padding:20px 28px;background:#173765;color:#fff;font-weight:bold;border-radius:14px 14px 0 0">BCC SSC E-Voting System</td></tr>
                <tr><td style="padding:28px">
                    <h1 style="margin:0 0 16px;font-size:24px">Your voter account is approved</h1>
                    <p>Hello, {{ $recipientName }}.</p>
                    <div style="margin:24px 0;padding:18px;background:#eef5ff;border:1px solid #cbdcf6;border-radius:10px;text-align:center">
                        <div style="font-size:14px">Your Voter ID</div>
                        <div style="margin-top:8px;font: bold 24px Consolas,monospace">{{ $voterIdNumber }}</div>
                    </div>
                    <p>Save your Voter ID for your records. You can now sign in to SSCEVS.</p>
                    <p style="margin:24px 0 4px"><a href="{{ route('login') }}" style="background:#1557af;color:#fff;text-decoration:none;padding:12px 18px;border-radius:8px;display:inline-block">Sign in to SSCEVS</a></p>
                </td></tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
