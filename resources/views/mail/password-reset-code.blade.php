<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Your SSCEVS password reset code</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f4f5;font-family:Arial,Helvetica,sans-serif;color:#121212;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;background-color:#f4f4f5;">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;max-width:520px;background:#ffffff;border:1px solid #e4e4e7;border-radius:12px;">
                    <tr>
                        <td style="background-color:#1a2744;border-bottom:3px solid #d4a017;padding:20px 28px;">
                            <span style="font-size:18px;font-weight:700;color:#ffffff;">SSCEVS</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px;">
                            <p style="margin:0 0 12px 0;font-size:18px;font-weight:700;">Hello, {{ $recipientName }}</p>
                            <p style="margin:0 0 24px 0;font-size:14px;line-height:1.6;color:#52525b;">
                                Use this code to reset your SSCEVS password. It expires in {{ $expiryMinutes }} minutes.
                            </p>
                            <p style="margin:0 0 8px 0;font-size:11px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:#71717a;text-align:center;">Password reset code</p>
                            <p style="margin:0;font-size:36px;font-weight:700;letter-spacing:0.2em;text-align:center;">{{ $code }}</p>
                            <p style="margin:24px 0 0 0;font-size:12px;line-height:1.6;color:#a1a1aa;">
                                If you did not ask to reset your password, you can ignore this email.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
