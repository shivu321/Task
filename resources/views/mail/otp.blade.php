<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your OTP Code</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea, #764ba2);
            font-family: 'Segoe UI', sans-serif;
            color: #fff;
            height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .otp-box {
            background: #fff;
            color: #333;
            padding: 2.5rem 2rem;
            border-radius: 20px;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.2);
            text-align: center;
            max-width: 380px;
            width: 100%;
            animation: popIn 0.5s ease-out;
        }

        .otp-box h2 {
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .otp-box .otp {
            font-size: 2.5rem;
            font-weight: bold;
            letter-spacing: 10px;
            color: #007bff;
            margin: 1.2rem 0;
        }

        .otp-box p {
            font-size: 1rem;
            margin-top: 1rem;
            color: #555;
        }

        @keyframes popIn {
            0% {
                transform: scale(0.95);
                opacity: 0;
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }
    </style>
</head>
<body>

    <div class="otp-box">
        <h2>Hello, {{ $user->user_name ?? 'User' }}!</h2>
        <p>Your One-Time Password (OTP) is:</p>
        <div class="otp">
            {{ $user->otp ?? '------' }}
        </div>
        <p>Please use this OTP to proceed.</p>
        <strong>Note : OTP is valid for 10 minutes.</strong>
    </div>

</body>
</html>
