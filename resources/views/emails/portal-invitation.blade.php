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
                <svg height="32" viewBox="0 0 100 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M14.706 19.802v-7.849L23.543 0H16.26l-4.489 6.06L7.286 0H0l8.84 11.953v7.849h5.866zM25.049.017H31v13.016c0 .576.464 1.04 1.04 1.04h9.578c.577 0 1.041-.468 1.041-1.04V.017h5.949v14.996a4.79 4.79 0 0 1-4.789 4.789H29.841a4.79 4.79 0 0 1-4.793-4.789V.017zm25.565 0h18.771a4.79 4.79 0 0 1 4.79 4.793v10.203a4.79 4.79 0 0 1-4.79 4.789H50.614V.017zm5.896 5.9v7.989h10.773c.576 0 1.044-.468 1.044-1.041V6.958a1.04 1.04 0 0 0-1.044-1.04H56.51zM76.386 4.81A4.791 4.791 0 0 1 81.178.017h13.983a4.79 4.79 0 0 1 4.788 4.793v10.203a4.79 4.79 0 0 1-4.788 4.789H81.178a4.79 4.79 0 0 1-4.792-4.789V4.81zm5.878 2.2v5.806c0 .573.465 1.04 1.038 1.04h9.724c.577 0 1.041-.467 1.041-1.04V7.01a1.04 1.04 0 0 0-1.04-1.041h-9.725c-.573 0-1.038.464-1.038 1.04z" fill="#DC0043"/>
                </svg>
                <p style="margin: 10px 0 0 0; font-size: 14px; color: #64748b;">Müşteri Portalı</p>
            </div>

            <p class="welcome">Portal Davetiyesi</p>

            <p class="message">
                Merhaba {{ $invitation->first_name }},<br><br>
                YUDO Müşteri Portalı'na davet edildiniz. Bu portal üzerinden tasarım taleplerinizi oluşturabilir,
                iş durumlarınızı takip edebilir ve tekliflerinizi görüntüleyebilirsiniz.
            </p>

            <div class="button-container">
                <a href="{{ $inviteUrl }}" class="button">Daveti Kabul Et</a>
            </div>

            <div class="info">
                <strong>Davetinizi kabul ettiğinizde:</strong>
                <ul style="margin: 8px 0 0 0; padding-left: 20px;">
                    <li>Şifrenizi belirleyeceksiniz</li>
                    <li>Hesabınız hemen aktif olacak</li>
                    <li>Portala giriş yapabileceksiniz</li>
                </ul>
            </div>

            <div class="warning">
                <strong>Önemli:</strong> Bu davetiye {{ $invitation->expires_at->format('d.m.Y H:i') }} tarihine kadar geçerlidir.
            </div>

            <p class="message">
                Eğer bu daveti siz talep etmediyseniz, bu e-postayı göz ardı edebilirsiniz.
            </p>

            <p class="link-text">
                Buton çalışmıyorsa, aşağıdaki linki tarayıcınıza kopyalayabilirsiniz:<br>
                {{ $inviteUrl }}
            </p>

            <div class="footer">
                <p>&copy; {{ date('Y') }} Yudo Sıcak Yolluk Sistemleri</p>
                <p>Bu otomatik bir e-postadır, lütfen yanıtlamayın.</p>
            </div>
        </div>
    </div>
</body>
</html>
