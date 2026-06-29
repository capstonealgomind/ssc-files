<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Verify your SSCEVS registration</title>
</head>
<body style="margin:0;padding:0;background-color:#ffffff;font-family:Arial,Helvetica,sans-serif;color:#121212;-webkit-text-size-adjust:100%;">

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;background-color:#ffffff;">
        <tr>
            <td align="center" style="padding:0;margin:0;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;max-width:640px;">
                    {{-- Header --}}
                    <tr>
                        <td style="background-color:#1a2744;border-bottom:3px solid #d4a017;padding:24px 32px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td align="left" style="vertical-align:middle;">
                                        <img
                                            src="https://i.ibb.co/V1BqD34/ssc.png"
                                            alt="Supreme Student Council"
                                            width="56"
                                            height="56"
                                            style="display:block;border:0;outline:none;text-decoration:none;height:56px;width:auto;max-width:120px;"
                                        />
                                    </td>
                                    <td align="right" style="vertical-align:middle;">
                                        <span style="font-size:20px;font-weight:700;color:#ffffff;letter-spacing:0.5px;">SSCEVS</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding:32px 32px 8px 32px;">
                            <p style="margin:0 0 12px 0;font-size:22px;font-weight:700;color:#121212;line-height:1.3;">
                                Hello, {{ $recipientName }}!
                            </p>
                            <p style="margin:0 0 28px 0;font-size:15px;line-height:1.7;color:#5f6368;">
                                Thank you for registering with the Student Supreme Court Electronic Voting System.
                                Verify your email to continue with your registration.
                            </p>

                            <p style="margin:0 0 8px 0;font-size:11px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:#5f6368;">
                                Your Voter ID
                            </p>
                            <p style="margin:0 0 8px 0;font-size:28px;font-weight:700;color:#1a2744;letter-spacing:0.02em;font-family:Consolas,Monaco,monospace;">
                                {{ $voterIdNumber }}
                            </p>
                            <p style="margin:0 0 32px 0;font-size:13px;line-height:1.6;color:#5f6368;">
                                Save this ID — you'll need it to check your registration status.
                            </p>

                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px 0;">
                                <tr>
                                    <td align="center" bgcolor="#1a2744" style="border-radius:8px;background-color:#1a2744;">
                                        <a
                                            href="{{ $verifyUrl }}"
                                            style="display:inline-block;padding:14px 32px;font-size:15px;font-weight:700;color:#ffffff !important;text-decoration:none;border-radius:8px;background-color:#1a2744;"
                                        >
                                            Verify email address
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0 0 8px 0;font-size:12px;line-height:1.6;color:#80868b;text-align:center;">
                                Or copy this link:
                            </p>
                            <p style="margin:0 0 32px 0;font-size:12px;line-height:1.6;color:#1a2744;word-break:break-all;text-align:center;">
                                <a href="{{ $verifyUrl }}" style="color:#2563eb;text-decoration:underline;">{{ $verifyUrl }}</a>
                            </p>
                        </td>
                    </tr>

                    {{-- OTP --}}
                    <tr>
                        <td style="padding:8px 32px 32px 32px;border-top:1px solid #e8eaed;">
                            <p style="margin:0 0 12px 0;font-size:11px;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:#5f6368;text-align:center;">
                                Your verification code
                            </p>
                            <p style="margin:0 0 8px 0;font-size:42px;font-weight:700;letter-spacing:0.25em;color:#121212;text-align:center;font-family:Consolas,Monaco,monospace;">
                                {{ $otp }}
                            </p>
                            <p style="margin:0 0 28px 0;font-size:12px;color:#80868b;text-align:center;">
                                Expires in 10 minutes
                            </p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 28px 0;">
                                <tr>
                                    <td style="padding:8px 0;font-size:14px;line-height:1.7;color:#5f6368;">
                                        1. Click the button above to open the verification page
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:8px 0;font-size:14px;line-height:1.7;color:#5f6368;">
                                        2. Enter the 6-digit code shown above
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:8px 0;font-size:14px;line-height:1.7;color:#5f6368;">
                                        3. Your school ID will be processed automatically
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:8px 0;font-size:14px;line-height:1.7;color:#5f6368;">
                                        4. You'll be notified once your account is approved
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0;font-size:13px;line-height:1.7;color:#5f6368;">
                                You won't be able to log in until your email is verified and your account is approved.
                                Never share your verification code with anyone.
                            </p>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding:24px 32px;background-color:#f8f9fa;border-top:1px solid #e8eaed;text-align:center;">
                            <p style="margin:0 0 6px 0;font-size:12px;color:#80868b;">
                                Student Supreme Court Election &amp; Voting System (SSCEVS)
                            </p>
                            <p style="margin:0;font-size:11px;color:#9aa0a6;">
                                Sent from {{ config('mail.from.address') }}
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
