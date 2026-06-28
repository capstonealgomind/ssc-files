<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Your SSCEVS Verification Code</title>
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
        .message { font-size: 14px; color: #71717a; margin: 0 0 28px 0; line-height: 1.6; }
        .otp-box { background: #f4f4f5; border: 2px dashed #d4d4d8; border-radius: 12px; padding: 24px; text-align: center; margin: 0 0 28px 0; }
        .otp-label { font-size: 11px; font-weight: 600; letter-spacing: 0.1em; text-transform: uppercase; color: #71717a; margin: 0 0 10px 0; }
        .otp-code { font-size: 40px; font-weight: 700; letter-spacing: 0.2em; color: #18181b; font-variant-numeric: tabular-nums; margin: 0; }
        .otp-expiry { font-size: 12px; color: #a1a1aa; margin: 10px 0 0 0; }
        .divider { border: none; border-top: 1px solid #e4e4e7; margin: 24px 0; }
        .warning { font-size: 12px; color: #a1a1aa; line-height: 1.6; margin: 0; }
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
                    To complete your voter registration, please enter the verification code below.
                    This code confirms your email address and helps us keep your account secure.
                </p>

                <div class="otp-box">
                    <p class="otp-label">Your verification code</p>
                    <p class="otp-code">{{ $code }}</p>
                    <p class="otp-expiry">Expires in {{ $expiryMinutes }} minutes</p>
                </div>

                <hr class="divider" />

                <p class="warning">
                    If you did not request this code, you can safely ignore this email.
                    Never share this code with anyone. SSCEVS staff will never ask for your OTP.
                </p>
            </div>
            <div class="footer">
                <p class="footer-text">Student Supreme Court Election &amp; Voting System (SSCEVS)</p>
            </div>
        </div>
    </div>
</body>
</html>
