SSCEVS — Verify your registration

Hello, {{ $recipientName }}!

Thank you for registering with the Student Supreme Court Electronic Voting System.

@if ($voterIdNumber)
Your Voter ID: {{ $voterIdNumber }}
Save this ID. You can use it once your voter registration is approved.
@endif

Verify your email by opening this link:
{{ $verifyUrl }}

Your verification code: {{ $otp }}
This code expires in 10 minutes.

Steps:
1. Open the verification link above
2. Enter the 6-digit code
3. View your registration decision

If you did not register for SSCEVS, you can ignore this email.
Never share your verification code with anyone.

—
Student Supreme Court Election & Voting System (SSCEVS)
Sent from {{ config('mail.from.address') }}
