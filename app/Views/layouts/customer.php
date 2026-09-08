<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Rental Universal' ?></title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f5f6fa;
            color: #1f2937;
        }
        .auth-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .auth-card {
            background: #fff;
            width: 100%;
            max-width: 400px;
            padding: 32px 28px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        .auth-title { margin: 0 0 4px; font-size: 22px; }
        .auth-subtitle { margin: 0 0 20px; font-size: 14px; color: #6b7280; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; margin-bottom: 6px; font-size: 14px; font-weight: 600; }
        .phone-input-wrapper { display: flex; align-items: center; border: 1px solid #d1d5db; border-radius: 8px; overflow: hidden; }
        .phone-prefix { padding: 10px 12px; background: #f3f4f6; font-size: 14px; color: #6b7280; }
        .form-control { flex: 1; border: none; padding: 10px 12px; font-size: 14px; outline: none; }
        .form-help { margin: 6px 0 0; font-size: 12px; color: #9ca3af; }
        .btn { display: inline-block; padding: 12px 16px; border-radius: 8px; border: none; font-size: 15px; font-weight: 600; cursor: pointer; text-decoration: none; }
        .btn-primary { background: #2563eb; color: #fff; }
        .btn-block { width: 100%; text-align: center; }
        .auth-links { display: flex; justify-content: space-between; margin-top: 18px; }
        .link-secondary { font-size: 13px; color: #6b7280; text-decoration: none; }
        .alert { padding: 10px 12px; border-radius: 8px; font-size: 13px; margin-bottom: 16px; }
        .alert-info { background: #eff6ff; color: #1d4ed8; }
        .alert-error { background: #fef2f2; color: #b91c1c; }
        .alert-error ul { margin: 0; padding-left: 18px; }
        .otp-input { letter-spacing: 6px; font-size: 20px; text-align: center; width: 100%; }
        .resend-form { margin-top: 4px; text-align: center; }
        .btn-link { background: none; border: none; color: #2563eb; font-size: 13px; cursor: pointer; padding: 6px; }
        .btn-link:disabled { color: #9ca3af; cursor: not-allowed; }
    </style>
</head>
<body>
    <?= $this->renderSection('content') ?>
</body>
</html>