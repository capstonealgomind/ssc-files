<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Verify Your SSCEVS Registration</title>
    <style>
        body { margin: 0; padding: 0; font-family: ui-sans-serif, system-ui, -apple-system, sans-serif; background-color: #f4f4f5; }
        .wrapper { max-width: 520px; margin: 40px auto; padding: 0 16px; }
        .card { background: #ffffff; border: 1px solid #e4e4e7; border-radius: 12px; overflow: hidden; }
        .header { background-color: #18181b; padding: 24px 32px; }
        .header-logo { display: flex; align-items: center; gap: 10px; }
        .header-icon { width: 32px; height: 32px; background: #ffffff; border-radius: 8px; display: flex; align-items: center; justify-content: center; }
        .header-title { color: #ffffff; font-size: 16px; font-weight: 600; }
        .body { padding: 32px; }
        .greeting { font-size: 18px; font-weight: 600; color: #18181b; margin: 0 0 12px 0; }
        .message { font-size: 14px; color: #71717a; margin: 0 0 24px 0; line-height: 1.6; }
        .voter-box { background: #f4f4f5; border: 1px solid #e4e4e7; border-radius: 8px; padding: 16px 20px; margin: 0 0 24px 0; }
        .voter-label { font-size: 11px; font-weight: 600; letter-spacing: 0.08em; text-transform: uppercase; color: #71717a; margin: 0 0 6px 0; }
        .voter-id { font-size: 22px; font-weight: 700; color: #18181b; font-variant-numeric: tabular-nums; margin: 0; letter-spacing: 0.02em; }
        .voter-note { font-size: 12px; color: #71717a; margin: 8px 0 0 0; }
        .btn-wrap { text-align: center; margin: 0 0 24px 0; }
        .btn { display: inline-block; background-color: #18181b; color: #ffffff; font-size: 14px; font-weight: 500; text-decoration: none; padding: 12px 28px; border-radius: 8px; }
        .otp-box { background: #f4f4f5; border: 2px dashed #d4d4d8; border-radius: 12px; padding: 24px; text-align: center; margin: 0 0 24px 0; }
        .otp-label { font-size: 11px; font-weight: 600; letter-spacing: 0.1em; text-transform: uppercase; color: #71717a; margin: 0 0 10px 0; }
        .otp-code { font-size: 40px; font-weight: 700; letter-spacing: 0.2em; color: #18181b; font-variant-numeric: tabular-nums; margin: 0; }
        .otp-expiry { font-size: 12px; color: #a1a1aa; margin: 10px 0 0 0; }
        .steps { margin: 0 0 24px 0; padding: 0; list-style: none; }
        .steps li { font-size: 13px; color: #71717a; line-height: 1.6; padding: 6px 0 6px 24px; position: relative; }
        .steps li::before { content: ''; position: absolute; left: 0; top: 13px; width: 6px; height: 6px; border-radius: 50%; background: #d4d4d8; }
        .notice { font-size: 12px; color: #71717a; background: #fafafa; border: 1px solid #e4e4e7; border-radius: 8px; padding: 12px 16px; line-height: 1.6; margin: 0; }
        .divider { border: none; border-top: 1px solid #e4e4e7; margin: 24px 0; }
        .link-fallback { font-size: 11px; color: #a1a1aa; word-break: break-all; text-align: center; margin: 0; line-height: 1.5; }
        .link-fallback a { color: #71717a; }
        .footer { padding: 20px 32px; background: #fafafa; border-top: 1px solid #e4e4e7; text-align: center; }
        .footer-text { font-size: 12px; color: #a1a1aa; margin: 0; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="card">
            <div class="header">
                <div class="header-logo">
                    <div class="header-icon">
                        <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="#18181b" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                    <span class="header-title">SSCEVS</span>
                </div>
            </div>
            <div class="body">
                <p class="greeting">Hello, {{ $recipientName }}!</p>
                <p class="message">
                    Thank you for registering with the Student Supreme Court Electronic Voting System.
                    Verify your email to continue with your registration.
                </p>

                <div class="voter-box">
                    <p class="voter-label">Your Voter ID</p>
                    <p class="voter-id">{{ $voterIdNumber }}</p>
                    <p class="voter-note">Save this ID — you'll need it to check your registration status.</p>
                </div>

                <div class="btn-wrap">
                    <a href="{{ $verifyUrl }}" class="btn">Verify email address</a>
                </div>

                <p class="link-fallback">
                    Or copy this link:<br />
                    <a href="{{ $verifyUrl }}">{{ $verifyUrl }}</a>
                </p>

                <hr class="divider" />

                <div class="otp-box">
                    <p class="otp-label">Your verification code</p>
                    <p class="otp-code">{{ $otp }}</p>
                    <p class="otp-expiry">Expires in 10 minutes</p>
                </div>

                <ul class="steps">
                    <li>Click the button above to open the verification page</li>
                    <li>Enter the 6-digit code shown above</li>
                    <li>Your school ID will be processed automatically</li>
                    <li>You'll be notified once your account is approved</li>
                </ul>

                <p class="notice">
                    You won't be able to log in until your email is verified and your account is approved.
                    Never share your verification code with anyone.
                </p>
            </div>
            <div class="footer">
                <p class="footer-text">Student Supreme Court Election &amp; Voting System (SSCEVS)</p>
            </div>
        </div>
    </div>
</body>
</html>
