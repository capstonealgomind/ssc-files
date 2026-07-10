<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Your SSCEVS Account Credentials</title>
</head>
<body style="margin:0;padding:0;background-color:#ffffff;font-family:Arial,Helvetica,sans-serif;color:#121212;-webkit-text-size-adjust:100%;">

    @php
        $isAdmin = ($accountType ?? 'committee') === 'admin';
        $roleLabel = $isAdmin ? 'administrator' : 'election committee';
        $stepThree = $isAdmin
            ? '3. Use the admin dashboard to manage elections, voters, and settings'
            : '3. Use the Candidate page to create election candidates';
    @endphp

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
                                An {{ $roleLabel }} account has been created for you on the Student Supreme Court Electronic Voting System.
                                Use the login credentials below to sign in.
                            </p>

                            <p style="margin:0 0 8px 0;font-size:11px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:#5f6368;">
                                Login email
                            </p>
                            <p style="margin:0 0 24px 0;font-size:18px;font-weight:700;color:#1a2744;letter-spacing:0.02em;font-family:Consolas,Monaco,monospace;word-break:break-all;">
                                {{ $loginEmail }}
                            </p>

                            <p style="margin:0 0 8px 0;font-size:11px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:#5f6368;">
                                Temporary password
                            </p>
                            <p style="margin:0 0 32px 0;font-size:28px;font-weight:700;color:#1a2744;letter-spacing:0.08em;font-family:Consolas,Monaco,monospace;">
                                {{ $plainPassword }}
                            </p>

                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px 0;">
                                <tr>
                                    <td align="center" bgcolor="#1a2744" style="border-radius:8px;background-color:#1a2744;">
                                        <a
                                            href="{{ $loginUrl }}"
                                            style="display:inline-block;padding:14px 32px;font-size:15px;font-weight:700;color:#ffffff !important;text-decoration:none;border-radius:8px;background-color:#1a2744;"
                                        >
                                            Sign in to SSCEVS
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0 0 8px 0;font-size:12px;line-height:1.6;color:#80868b;text-align:center;">
                                Or copy this link:
                            </p>
                            <p style="margin:0 0 32px 0;font-size:12px;line-height:1.6;color:#1a2744;word-break:break-all;text-align:center;">
                                <a href="{{ $loginUrl }}" style="color:#2563eb;text-decoration:underline;">{{ $loginUrl }}</a>
                            </p>
                        </td>
                    </tr>

                    {{-- Security note --}}
                    <tr>
                        <td style="padding:8px 32px 32px 32px;border-top:1px solid #e8eaed;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 28px 0;">
                                <tr>
                                    <td style="padding:8px 0;font-size:14px;line-height:1.7;color:#5f6368;">
                                        1. Sign in using the login email and temporary password above
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:8px 0;font-size:14px;line-height:1.7;color:#5f6368;">
                                        2. Change your password after your first sign-in
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:8px 0;font-size:14px;line-height:1.7;color:#5f6368;">
                                        {{ $stepThree }}
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0;font-size:13px;line-height:1.7;color:#5f6368;">
                                For your security, please change your password after signing in.
                                If you did not expect this email, contact your system administrator.
                                Never share your login credentials with anyone.
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
