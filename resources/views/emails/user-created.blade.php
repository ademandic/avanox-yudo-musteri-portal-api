<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hesabınız Oluşturuldu</title>
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
        .welcome {
            text-align: center;
            font-size: 24px;
            color: #1a56db;
            margin-bottom: 20px;
        }
        .message {
            color: #666;
            margin-bottom: 20px;
        }
        .credentials {
            background-color: #f0f7ff;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
        }
        .credentials h3 {
            margin: 0 0 15px 0;
            color: #1a56db;
            font-size: 16px;
        }
        .credential-item {
            display: flex;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e0e7ff;
        }
        .credential-item:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        .credential-label {
            font-weight: 600;
            color: #374151;
            width: 100px;
        }
        .credential-value {
            color: #1f2937;
            font-family: 'Courier New', Courier, monospace;
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .button {
            display: inline-block;
            background-color: #1a56db;
            color: #ffffff !important;
            padding: 14px 32px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
        }
        .button:hover {
            background-color: #1e40af;
        }
        .warning {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 12px 16px;
            margin: 20px 0;
            border-radius: 0 4px 4px 0;
            font-size: 14px;
            color: #92400e;
        }
        .info {
            background-color: #e0f2fe;
            border-left: 4px solid #0284c7;
            padding: 12px 16px;
            margin: 20px 0;
            border-radius: 0 4px 4px 0;
            font-size: 14px;
            color: #0369a1;
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

            <p class="welcome">Hoş Geldiniz!</p>

            <p class="message">
                Merhaba {{ $user->first_name }},<br><br>
                YUDO Müşteri Portalı'na hesabınız oluşturuldu. Aşağıdaki bilgilerle giriş yapabilirsiniz:
            </p>

            <div class="credentials">
                <h3>Giriş Bilgileriniz</h3>
                <div class="credential-item">
                    <span class="credential-label">E-posta:</span>
                    <span class="credential-value">{{ $user->email }}</span>
                </div>
                <div class="credential-item">
                    <span class="credential-label">Şifre:</span>
                    <span class="credential-value">{{ $password }}</span>
                </div>
            </div>

            <div class="button-container">
                <a href="{{ $loginUrl }}" class="button">Portala Giriş Yap</a>
            </div>

            <div class="warning">
                <strong>Önemli:</strong> İlk girişinizde şifrenizi değiştirmeniz istenecektir. Lütfen güçlü bir şifre belirleyin.
            </div>

            <div class="info">
                <strong>Şifre Gereksinimleri:</strong>
                <ul style="margin: 8px 0 0 0; padding-left: 20px;">
                    <li>En az 8 karakter</li>
                    <li>Büyük ve küçük harf</li>
                    <li>En az 1 rakam</li>
                    <li>En az 1 özel karakter (!@#$%^&*)</li>
                </ul>
            </div>

            <p class="message">
                Herhangi bir sorunuz varsa, lütfen bizimle iletişime geçin.
            </p>

            <div class="footer">
                <p>&copy; {{ date('Y') }} Yudo Sıcak Yolluk Sistemleri</p>
                <p>Bu otomatik bir e-postadır, lütfen yanıtlamayın.</p>
            </div>
        </div>
    </div>
</body>
</html>
