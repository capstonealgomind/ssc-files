<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #1e40af; color: white; padding: 20px; text-align: center; }
        .content { background: #f9fafb; padding: 30px; }
        .voter-id { background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin: 20px 0; }
        .otp-box { background: #fff; border: 2px dashed #1e40af; padding: 20px; text-align: center; margin: 20px 0; }
        .otp-code { font-size: 32px; font-weight: bold; letter-spacing: 8px; color: #1e40af; }
        .button { display: inline-block; background: #1e40af; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        .footer { text-align: center; padding: 20px; color: #6b7280; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>SSCEVS Registration</h1>
        </div>
        
        <div class="content">
            <h2>Hello, {{ $recipientName }}!</h2>
            
            <p>Thank you for registering with the SSC Electronic Voting System.</p>
            
            <div class="voter-id">
                <strong>📋 Your Voter Registration ID:</strong><br>
                <span style="font-size: 24px; font-weight: bold; color: #1e40af;">{{ $voterIdNumber }}</span><br>
                <small style="color: #dc2626;">⚠️ Please copy and save this ID number for your records!</small>
            </div>

            <p><strong>To complete your registration, please verify your email address:</strong></p>

            <div style="text-align: center;">
                <a href="{{ $verifyUrl }}" class="button">Verify Email Address</a>
            </div>

            <p style="text-align: center; margin-top: 10px;">
                <small>Or copy this link: <a href="{{ $verifyUrl }}">{{ $verifyUrl }}</a></small>
            </p>

            <div class="otp-box">
                <p style="margin: 0 0 10px 0;">Your verification code:</p>
                <div class="otp-code">{{ $otp }}</div>
            </div>

            <h3>What happens next?</h3>
            <ol>
                <li>Click the verification link above or use the OTP code</li>
                <li>Your School ID will be automatically processed (OCR)</li>
                <li>If verification score is ≥ 80%, your account will be auto-approved</li>
                <li>If score is &lt; 80%, an admin will review your registration</li>
                <li>You'll be able to login once your account is approved</li>
            </ol>

            <p style="color: #dc2626; font-weight: bold;">⚠️ Important: You will not be able to login until your email is verified and your account is approved.</p>
        </div>
        
        <div class="footer">
            <p>This is an automated email from SSCEVS. Please do not reply to this message.</p>
        </div>
    </div>
</body>
</html>
