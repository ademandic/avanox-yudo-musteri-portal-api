<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doğrulama Kodu</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .card {
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 40px;
            margin: 20px 0;
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo h1 {
            color: #1a56db;
            font-size: 28px;
            font-weight: 700;
            margin: 0;
        }
        .code-container {
            text-align: center;
            margin: 30px 0;
            padding: 20px;
            background-color: #f0f7ff;
            border-radius: 8px;
        }
        .code {
            font-size: 36px;
            font-weight: 700;
            letter-spacing: 8px;
            color: #1a56db;
            font-family: 'Courier New', Courier, monospace;
        }
        .message {
            text-align: center;
            color: #666;
            margin-bottom: 20px;
        }
        .warning {
            background-color: #fff8e6;
            border-left: 4px solid #f59e0b;
            padding: 12px 16px;
            margin: 20px 0;
            border-radius: 0 4px 4px 0;
            font-size: 14px;
            color: #92400e;
        }
        .footer {
            text-align: center;
            color: #999;
            font-size: 12px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .footer a {
            color: #1a56db;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="logo">
                <h1>YUDO Portal</h1>
            </div>

            <p class="message">
                Merhaba,<br>
                Hesabınıza giriş yapmak için aşağıdaki doğrulama kodunu kullanın:
            </p>

            <div class="code-container">
                <div class="code">{{ $code }}</div>
            </div>

            <p class="message">
                Bu kod <strong>{{ $validityMinutes }} dakika</strong> boyunca geçerlidir.
            </p>

            <div class="warning">
                <strong>Güvenlik Uyarısı:</strong> Bu kodu kimseyle paylaşmayın. Yudo ekibi sizden asla doğrulama kodu istemez.
            </div>

            <p class="message">
                Bu giriş denemesini siz yapmadıysanız, lütfen bu e-postayı dikkate almayın veya şifrenizi değiştirmek için bizimle iletişime geçin.
            </p>

            <div class="footer">
                <p>&copy; {{ date('Y') }} Yudo Sıcak Yolluk Sistemleri</p>
                <p>Bu otomatik bir e-postadır, lütfen yanıtlamayın.</p>
            </div>
        </div>
    </div>
</body>
</html>
