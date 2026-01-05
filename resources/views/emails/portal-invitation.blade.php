<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Davetiyesi</title>
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
        .info {
            background-color: #e0f2fe;
            border-left: 4px solid #0284c7;
            padding: 12px 16px;
            margin: 20px 0;
            border-radius: 0 4px 4px 0;
            font-size: 14px;
            color: #0369a1;
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
        .link-text {
            word-break: break-all;
            font-size: 12px;
            color: #666;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="logo">
                <h1>YUDO Portal</h1>
            </div>

            <p class="welcome">Portal Davetiyesi</p>

            <p class="message">
                Merhaba {{ $invitation->first_name }},<br><br>
                YUDO Musteri Portali'na davet edildiniz. Bu portal uzerinden tasarim taleplerinizi olusturabilir,
                is durumlarinizi takip edebilir ve tekliflerinizi goruntuleyebilirsiniz.
            </p>

            <div class="button-container">
                <a href="{{ $inviteUrl }}" class="button">Daveti Kabul Et</a>
            </div>

            <div class="info">
                <strong>Davetinizi kabul ettiginizde:</strong>
                <ul style="margin: 8px 0 0 0; padding-left: 20px;">
                    <li>Sifrenizi belirleyeceksiniz</li>
                    <li>Hesabiniz hemen aktif olacak</li>
                    <li>Portala giris yapabileceksiniz</li>
                </ul>
            </div>

            <div class="warning">
                <strong>Onemli:</strong> Bu davetiye {{ $invitation->expires_at->format('d.m.Y H:i') }} tarihine kadar gecerlidir.
            </div>

            <p class="message">
                Eger bu daveti siz talep etmediyseniz, bu emaili goz ardi edebilirsiniz.
            </p>

            <p class="link-text">
                Buton calismiyorsa, asagidaki linki tarayiciniza kopyalayabilirsiniz:<br>
                {{ $inviteUrl }}
            </p>

            <div class="footer">
                <p>&copy; {{ date('Y') }} Yudo Sicak Yolluk Sistemleri</p>
                <p>Bu otomatik bir e-postadir, lutfen yanitlamayin.</p>
            </div>
        </div>
    </div>
</body>
</html>
