<!DOCTYPE html>
<html>
<head>
    <title>Your Account Password</title>
</head>
<body>
    <h2>Hello {{ $user->name }},</h2>
    <p>Your account has been created. Here is your temporary password:</p>
    <p><strong>{{ $password }}</strong></p>
    <p>Please <a href="{{ url('/password/reset') }}">reset your password</a> after logging in.</p>
    <p>Thank you!</p>
</body>
</html>
