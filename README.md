# YUDO Musteri Portal API

YUDO Müşteri Portalı için RESTful API servisi. Müşterilerin tasarım/teklif taleplerini yönetmelerini sağlayan backend uygulaması.

## Teknolojiler

- **PHP 8.2** - Programlama dili
- **Laravel 11** - PHP Framework
- **SQL Server** - Veritabanı (ERP entegrasyonu)
- **Redis** - Cache ve session yönetimi
- **JWT** - Token tabanlı kimlik doğrulama
- **Docker** - Konteynerizasyon

## Gereksinimler

### Yerel Geliştirme
- PHP 8.2+
- Composer 2.x
- SQL Server ODBC Driver
- Redis

### Docker ile Çalıştırma
- Docker 24+
- Docker Compose 2.x

## Kurulum

### 1. Projeyi Klonlayın

```bash
git clone https://github.com/ademandic/avanox-yudo-musteri-portal-api.git
cd avanox-yudo-musteri-portal-api
```

### 2. Environment Dosyasını Hazırlayın

```bash
# Docker için
cp .env.docker.example .env

# Yerel geliştirme için
cp .env.example .env
```

### 3. Docker ile Başlatın

```bash
# Development ortamı
make dev

# Veya docker-compose ile
docker-compose up -d
```

### 4. Bağımlılıkları Yükleyin

```bash
make composer-install
```

### 5. Uygulama Anahtarlarını Oluşturun

```bash
make artisan cmd="key:generate"
make artisan cmd="jwt:secret"
```

### 6. Migration'ları Çalıştırın

```bash
make migrate
```

## API Endpoints

### Kimlik Doğrulama

| Method | Endpoint | Açıklama |
|--------|----------|----------|
| POST | `/api/auth/login` | Giriş yap |
| POST | `/api/auth/logout` | Çıkış yap |
| POST | `/api/auth/refresh` | Token yenile |
| GET | `/api/auth/me` | Kullanıcı bilgisi |

### Davetiyeler

| Method | Endpoint | Açıklama |
|--------|----------|----------|
| GET | `/api/invitations/{token}` | Davetiye detayı |
| POST | `/api/invitations/{token}/accept` | Davetiyeyi kabul et |

### Talepler

| Method | Endpoint | Açıklama |
|--------|----------|----------|
| GET | `/api/requests` | Talep listesi |
| POST | `/api/requests` | Yeni talep oluştur |
| GET | `/api/requests/{id}` | Talep detayı |
| PUT | `/api/requests/{id}` | Talep güncelle |
| POST | `/api/requests/{id}/cancel` | Talep iptal et |
| GET | `/api/requests/{id}/history` | Talep geçmişi |

### Dosyalar

| Method | Endpoint | Açıklama |
|--------|----------|----------|
| GET | `/api/requests/{id}/files` | Dosya listesi |
| POST | `/api/requests/{id}/files` | Dosya yükle |
| GET | `/api/files/{id}/download` | Dosya indir |
| DELETE | `/api/files/{id}` | Dosya sil |
| GET | `/api/files/upload-rules` | Yükleme kuralları |

### Firmalar

| Method | Endpoint | Açıklama |
|--------|----------|----------|
| GET | `/api/companies` | Firma listesi |
| GET | `/api/companies/{id}` | Firma detayı |

### İşler (Jobs)

| Method | Endpoint | Açıklama |
|--------|----------|----------|
| GET | `/api/jobs` | İş listesi |
| GET | `/api/jobs/{id}` | İş detayı |

### Lookup Verileri

| Method | Endpoint | Açıklama |
|--------|----------|----------|
| GET | `/api/lookups` | Tüm lookup verileri |
| GET | `/api/lookups/request-types` | Talep tipleri |
| GET | `/api/lookups/priorities` | Öncelikler |
| GET | `/api/lookups/materials` | Malzemeler |
| GET | `/api/lookups/additives` | Katkı maddeleri |
| GET | `/api/lookups/nozzle-types` | Nozul tipleri |
| GET | `/api/lookups/states` | Durum listesi |

### Sistem

| Method | Endpoint | Açıklama |
|--------|----------|----------|
| GET | `/api/health` | Sağlık kontrolü |

## Güvenlik

### API Key Doğrulama

Tüm API istekleri `X-Portal-Api-Key` header'ı gerektirir:

```bash
curl -H "X-Portal-Api-Key: your-api-key" \
     -H "Authorization: Bearer your-jwt-token" \
     https://api.example.com/api/requests
```

### JWT Token

Login sonrası alınan JWT token'ı `Authorization: Bearer` header'ı ile gönderilmelidir.

## Docker Komutları

```bash
# Development başlat
make dev

# Production başlat
make prod

# Durdur
make down

# Logları izle
make logs

# Shell erişimi
make shell

# Artisan komutu çalıştır
make artisan cmd="migrate:status"

# Cache temizle
make cache-clear

# Testleri çalıştır
make test
```

## Proje Yapısı

```
portal-api/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/    # API Controller'ları
│   │   ├── Middleware/         # Özel middleware'ler
│   │   ├── Requests/           # Form request validasyonları
│   │   └── Resources/          # API resource dönüşümleri
│   ├── Models/                 # Eloquent modelleri
│   └── Services/               # İş mantığı servisleri
├── config/
│   └── portal.php              # Portal konfigürasyonu
├── database/
│   └── migrations/             # Veritabanı migration'ları
├── docker/                     # Docker konfigürasyonları
│   ├── nginx/                  # Nginx ayarları
│   └── php/                    # PHP-FPM ayarları
├── routes/
│   └── api.php                 # API route tanımları
├── Dockerfile                  # Docker image tanımı
├── docker-compose.yml          # Docker compose (dev)
├── docker-compose.prod.yml     # Docker compose (prod)
└── Makefile                    # Yardımcı komutlar
```

## Veritabanı Şeması

### Portal Tabloları (Read/Write)

- `portal_users` - Portal kullanıcıları
- `portal_invitations` - Davetiyeler
- `portal_requests` - Talepler
- `portal_request_states` - Talep durumları
- `portal_request_state_logs` - Durum değişiklik logları

### ERP Tabloları (Readonly)

- `companies` - Firmalar
- `contacts` - İletişim kişileri
- `users` - ERP kullanıcıları

### ERP Tabloları (Read/Write)

- `jobs` - İşler
- `technical_datas` - Teknik veriler
- `files` - Dosyalar

## Numaralandırma Formatları

- **İş Numarası**: `YT{YY}-{SIRA}` (örn: YT25-1001)
- **Talep Numarası**: `PR-{YYYY}-{SIRA}` (örn: PR-2025-0001)

## Lisans

Bu proje YUDO Türkiye için Avanox tarafından geliştirilmiştir. Tüm hakları saklıdır.
