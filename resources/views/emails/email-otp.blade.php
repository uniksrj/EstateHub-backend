<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estate Hub Email Verification</title>
</head>
<body style="margin:0;padding:0;background-color:#f8fafc;font-family:Arial,sans-serif;color:#0f172a;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding:24px 0;background-color:#f8fafc;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;background:#ffffff;border-radius:12px;overflow:hidden;">
                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 12px;font-size:14px;color:#475569;">Estate Hub</p>
                            <h1 style="margin:0 0 16px;font-size:24px;line-height:1.3;color:#0f172a;">Verify your email address</h1>
                            <p style="margin:0 0 20px;font-size:15px;line-height:1.6;color:#475569;">
                                Use the verification code below to complete your registration. This code is valid for 10 minutes.
                            </p>
                            <div style="margin:0 0 20px;padding:16px 20px;border-radius:10px;background:#eff6ff;border:1px solid #bfdbfe;text-align:center;">
                                <span style="display:block;font-size:30px;letter-spacing:6px;font-weight:700;color:#2563eb;">{{ $otp }}</span>
                            </div>
                            <p style="margin:0;font-size:13px;line-height:1.6;color:#64748b;">
                                This verification code was requested for {{ $email }}. If this was not you, you can safely ignore this email.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
