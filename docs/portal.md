# Portal UI - API Entegrasyon Rehberi

Bu dokuman, YUDO Customer Portal UI uygulamasinin Portal API ile entegrasyonu icin gerekli bilgileri icerir.

---

## API Baglanti Bilgileri

```
Base URL: https://api.yudo.com.tr/api
```

### Gerekli Header'lar

Her istekte asagidaki header'lar gonderilmelidir:

```
X-Portal-Api-Key: {API_KEY}
Content-Type: application/json
Accept: application/json
```

Kimlik dogrulamasi gerektiren endpoint'ler icin ek olarak:

```
Authorization: Bearer {JWT_TOKEN}
```

---

## Authentication (Kimlik Dogrulama)

### Login

```
POST /api/auth/login
```

**Request Body:**
```json
{
  "email": "kullanici@firma.com",
  "password": "sifre123"
}
```

**Basarili Response (200):**
```json
{
  "success": true,
  "data": {
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "token_type": "bearer",
    "expires_in": 86400,
    "user": {
      "id": 1,
      "email": "kullanici@firma.com",
      "contact": {
        "id": 5,
        "name": "Ahmet Yilmaz"
      },
      "company": {
        "id": 10,
        "name": "ABC Kalip Ltd.",
        "code": "ABC001"
      }
    }
  }
}
```

> **ONEMLI:** `expires_in` degeri **saniye** cinsindendir (86400 saniye = 24 saat).
> Token yenileme islemini bu sureye gore planlayiniz.

**Basarisiz Response (401):**
```json
{
  "success": false,
  "message": "Gecersiz kimlik bilgileri."
}
```

**Pasif Hesap (403):**
```json
{
  "success": false,
  "message": "Hesabiniz pasif durumdadir. Lutfen destek ile iletisime gecin."
}
```

---

### Logout

```
POST /api/auth/logout
```

**Response (200):**
```json
{
  "success": true,
  "message": "Basariyla cikis yapildi."
}
```

---

### Token Yenileme

```
POST /api/auth/refresh
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "token_type": "bearer",
    "expires_in": 86400,
    "user": { ... }
  }
}
```

**Token Suresi Dolmus (401):**
```json
{
  "success": false,
  "message": "Token yenilenemedi. Lutfen tekrar giris yapin."
}
```

---

### Mevcut Kullanici Bilgisi

```
GET /api/auth/me
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "email": "kullanici@firma.com",
    "is_active": true,
    "last_login_at": "2025-01-15T10:30:00Z",
    "contact": {
      "id": 5,
      "name": "Ahmet Yilmaz",
      "email": "ahmet@firma.com",
      "phone": "+90 532 xxx xx xx"
    },
    "company": {
      "id": 10,
      "name": "ABC Kalip Ltd.",
      "code": "ABC001",
      "address": "Istanbul, Turkiye"
    }
  }
}
```

---

## Davetiye Islemleri

### Davetiye Detayi (Public)

```
GET /api/invitations/{token}
```

**Gecerli Davetiye (200):**
```json
{
  "success": true,
  "data": {
    "token": "abc123...",
    "email": "davet@firma.com",
    "company": {
      "id": 10,
      "name": "ABC Kalip Ltd."
    },
    "contact": {
      "id": 5,
      "name": "Mehmet Demir"
    },
    "expires_at": "2025-01-22T23:59:59Z",
    "is_expired": false,
    "is_accepted": false
  }
}
```

**Gecersiz/Suresi Dolmus (404):**
```json
{
  "success": false,
  "message": "Davetiye bulunamadi veya suresi dolmus."
}
```

---

### Daveti Kabul Et ve Kayit Ol (Public)

```
POST /api/invitations/{token}/accept
```

**Request Body:**
```json
{
  "password": "yeniSifre123",
  "password_confirmation": "yeniSifre123"
}
```

**Basarili Response (201):**
```json
{
  "success": true,
  "message": "Kayit basariyla tamamlandi.",
  "data": {
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "token_type": "bearer",
    "expires_in": 86400,
    "user": { ... }
  }
}
```

---

## Lookup Data (Form Dropdown'lari)

### Tum Lookup Verileri (Tek Istek)

```
GET /api/lookups/all
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "request_states": [
      { "id": 1, "name": "Talep Alindi", "color_class": "blue" },
      { "id": 2, "name": "Inceleniyor", "color_class": "yellow" },
      { "id": 3, "name": "Calisiliyor", "color_class": "orange" },
      { "id": 4, "name": "Revizyon Bekliyor", "color_class": "purple" },
      { "id": 5, "name": "Tamamlandi", "color_class": "green" },
      { "id": 6, "name": "Iptal Edildi", "color_class": "red" }
    ],
    "request_types": [
      { "value": 1, "label": "Tasarim Talebi" },
      { "value": 2, "label": "Teklif Talebi" }
    ],
    "priorities": [
      { "value": 1, "label": "Dusuk" },
      { "value": 2, "label": "Normal" },
      { "value": 3, "label": "Yuksek" },
      { "value": 4, "label": "Acil" }
    ],
    "materials": [
      { "value": "ABS", "label": "ABS" },
      { "value": "PP", "label": "PP (Polipropilen)" },
      { "value": "PS", "label": "PS (Polistiren)" },
      { "value": "PA", "label": "PA (Naylon)" },
      { "value": "PC", "label": "PC (Polikarbonat)" },
      { "value": "POM", "label": "POM (Asetal)" },
      { "value": "PE", "label": "PE (Polietilen)" },
      { "value": "PET", "label": "PET" },
      { "value": "PMMA", "label": "PMMA (Akrilik)" }
    ],
    "additives": [
      { "value": "glass_fiber", "label": "Cam Elyaf" },
      { "value": "talc", "label": "Talc" },
      { "value": "mineral", "label": "Mineral" },
      { "value": "carbon_fiber", "label": "Karbon Elyaf" }
    ],
    "nozzle_types": [
      { "value": "parca", "label": "Parcaya (Direct Gate)" },
      { "value": "yolluk", "label": "Yolluga (Runner)" }
    ]
  }
}
```

### Ayri Ayri Lookup Endpoint'leri

```
GET /api/lookups/request-states
GET /api/lookups/request-types
GET /api/lookups/priorities
GET /api/lookups/materials
GET /api/lookups/additives
GET /api/lookups/nozzle-types
```

---

## Firma Bilgileri

### Kendi Firma Bilgisi

```
GET /api/company
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 10,
    "name": "ABC Kalip Ltd.",
    "code": "ABC001",
    "address": "Organize Sanayi Bolgesi, Istanbul",
    "phone": "+90 212 xxx xx xx",
    "email": "info@abckalip.com",
    "sales_person": {
      "id": 3,
      "name": "Ali Satisci"
    }
  }
}
```

---

## Is (Job) Islemleri

### Firmaya Ait Isler

```
GET /api/jobs
```

**Query Parameters:**
- `page` (optional): Sayfa numarasi (default: 1)
- `per_page` (optional): Sayfa basina kayit (default: 15)

**Response (200):**
```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 100,
        "job_no": "YT25-1001",
        "mold_maker_ref_no": "ABC-REF-001",
        "status": "Aktif",
        "created_at": "2025-01-10T14:30:00Z"
      }
    ],
    "meta": {
      "current_page": 1,
      "last_page": 5,
      "per_page": 15,
      "total": 72
    }
  }
}
```

### Is Detayi

```
GET /api/jobs/{job_no}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 100,
    "job_no": "YT25-1001",
    "mold_maker_ref_no": "ABC-REF-001",
    "aciklama": "Portal uzerinden olusturuldu. Talep No: PR-2025-0001",
    "technical_data": {
      "parca_agirligi": 150.5,
      "et_kalinligi": 2.5,
      "malzeme": "ABS",
      "kalip_x": 300,
      "kalip_y": 400,
      "kalip_d": 100,
      "kalip_e": 50,
      "kalip_parca_sayisi": 4,
      "meme_sayisi": 8
    },
    "files": [
      {
        "id": 50,
        "dosya_adi": "kalip-cizim.pdf",
        "extension": "pdf",
        "dosya_boyut": 1048576
      }
    ]
  }
}
```

---

## Talep (Request) Islemleri

### Talep Listesi

```
GET /api/requests
```

**Query Parameters:**
- `page` (optional): Sayfa numarasi
- `status` (optional): Durum filtreleme (state id)

**Response (200):**
```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 1,
        "request_no": "PR-2025-0001",
        "request_type": 1,
        "request_type_label": "Tasarim Talebi",
        "priority": 2,
        "priority_label": "Normal",
        "current_state": {
          "id": 2,
          "name": "Inceleniyor",
          "color_class": "yellow"
        },
        "job": {
          "id": 100,
          "job_no": "YT25-1001"
        },
        "created_at": "2025-01-15T10:00:00Z"
      }
    ],
    "meta": {
      "current_page": 1,
      "last_page": 3,
      "per_page": 15,
      "total": 42
    }
  }
}
```

---

### Yeni Talep Olustur

```
POST /api/requests
```

**Request Body:**
```json
{
  "request_type": 1,
  "customer_reference_code": "ABC-REF-001",
  "customer_mold_code": "KALIP-001",
  "customer_notes": "Acil uretim gerekiyor.",
  "expected_delivery_date": "2025-02-15",
  "priority": 3,

  "parca_agirligi": 150.5,
  "et_kalinligi": 2.5,
  "malzeme": "ABS",
  "katki_var_mi": true,
  "katki_turu": "glass_fiber",
  "katki_orani": 30,

  "kalip_x": 300,
  "kalip_y": 400,
  "kalip_z": 250,
  "kalip_d": 100,
  "kalip_l": 50,

  "goz_sayisi": 4,
  "meme_sayisi": 8,
  "meme_tipi": "parca"
}
```

**Alan Aciklamalari:**
| Alan | Tip | Zorunlu | Aciklama |
|------|-----|---------|----------|
| request_type | int | Evet | 1: Tasarim, 2: Teklif |
| customer_reference_code | string | Hayir | Musterinin kendi referans kodu |
| customer_mold_code | string | Hayir | Musterinin kalip kodu |
| customer_notes | string | Hayir | Ek notlar |
| expected_delivery_date | date | Hayir | Beklenen teslim tarihi |
| priority | int | Hayir | 1-4 arasi (default: 2) |
| parca_agirligi | decimal | Hayir | Parca agirligi (gram) |
| et_kalinligi | decimal | Hayir | Et kalinligi (mm) |
| malzeme | string | Hayir | Malzeme kodu |
| katki_var_mi | boolean | Hayir | Katki maddesi var mi |
| katki_turu | string | Hayir | Katki turu kodu |
| katki_orani | int | Hayir | Katki orani (%) |
| kalip_x | decimal | Hayir | X olcusu (mm) |
| kalip_y | decimal | Hayir | Y olcusu (mm) |
| kalip_z | decimal | Hayir | Z olcusu (mm) - Portal'a ozel |
| kalip_d | decimal | Hayir | D olcusu (mm) |
| kalip_l | decimal | Hayir | L olcusu (mm) |
| goz_sayisi | int | Hayir | Goz sayisi |
| meme_sayisi | int | Hayir | Meme sayisi |
| meme_tipi | string | Hayir | "parca" veya "yolluk" |

**Basarili Response (201):**
```json
{
  "success": true,
  "message": "Talep basariyla olusturuldu.",
  "data": {
    "id": 1,
    "request_no": "PR-2025-0001",
    "job": {
      "id": 100,
      "job_no": "YT25-1001"
    },
    "current_state": {
      "id": 1,
      "name": "Talep Alindi"
    }
  }
}
```

---

### Talep Detayi

```
GET /api/requests/{id}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "request_no": "PR-2025-0001",
    "request_type": 1,
    "request_type_label": "Tasarim Talebi",
    "customer_reference_code": "ABC-REF-001",
    "customer_mold_code": "KALIP-001",
    "customer_notes": "Acil uretim gerekiyor.",
    "expected_delivery_date": "2025-02-15",
    "priority": 3,
    "priority_label": "Yuksek",
    "kalip_z": 250,
    "current_state": {
      "id": 2,
      "name": "Inceleniyor",
      "color_class": "yellow"
    },
    "job": {
      "id": 100,
      "job_no": "YT25-1001",
      "technical_data": {
        "parca_agirligi": 150.5,
        "et_kalinligi": 2.5,
        "malzeme": "ABS",
        "malzeme_katki": "glass_fiber",
        "malzeme_katki_yuzdesi": 30,
        "kalip_x": 300,
        "kalip_y": 400,
        "kalip_d": 100,
        "kalip_e": 50,
        "kalip_parca_sayisi": 4,
        "meme_sayisi": 8,
        "tip_sekli": "parca"
      },
      "files": [
        {
          "id": 50,
          "dosya_adi": "kalip-cizim.pdf",
          "extension": "pdf",
          "dosya_boyut": 1048576,
          "download_url": "/api/files/50/download"
        }
      ]
    },
    "state_logs": [
      {
        "id": 2,
        "state": { "id": 2, "name": "Inceleniyor" },
        "aciklama": "Talep incelemeye alindi.",
        "created_at": "2025-01-15T14:00:00Z"
      },
      {
        "id": 1,
        "state": { "id": 1, "name": "Talep Alindi" },
        "aciklama": "Talep olusturuldu.",
        "created_at": "2025-01-15T10:00:00Z"
      }
    ],
    "is_editable": false,
    "is_cancellable": true,
    "created_at": "2025-01-15T10:00:00Z",
    "updated_at": "2025-01-15T14:00:00Z"
  }
}
```

---

### Talep Guncelle

```
PUT /api/requests/{id}
```

> **NOT:** Sadece "Talep Alindi" durumundaki talepler guncellenebilir.

**Request Body:** (Yeni talep ile ayni alanlar)

**Duzenlenemez Talep (403):**
```json
{
  "success": false,
  "message": "Bu talep artik duzenlenemez."
}
```

---

### Talep Iptal Et

```
POST /api/requests/{id}/cancel
```

> **NOT:** Bu endpoint request body almaz. Iptal sebebi API tarafindan otomatik olarak "Talep musteri tarafindan iptal edildi." olarak kaydedilir.

**Basarili Response (200):**
```json
{
  "success": true,
  "message": "Talep basariyla iptal edildi.",
  "data": {
    "id": 1,
    "request_no": "PR-2025-0001",
    "current_state": {
      "id": 6,
      "name": "Iptal Edildi",
      "color_class": "red"
    }
  }
}
```

**Iptal Edilemez Talep (403):**
```json
{
  "success": false,
  "message": "Bu talep iptal edilemez."
}
```

---

### Durum Gecmisi

```
GET /api/requests/{id}/history
```

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 2,
      "state": {
        "id": 2,
        "name": "Inceleniyor",
        "color_class": "yellow"
      },
      "aciklama": "Talep incelemeye alindi.",
      "changed_by": "Ali Satisci",
      "created_at": "2025-01-15T14:00:00Z"
    },
    {
      "id": 1,
      "state": {
        "id": 1,
        "name": "Talep Alindi",
        "color_class": "blue"
      },
      "aciklama": "Talep olusturuldu.",
      "changed_by": "Ahmet Yilmaz (Portal)",
      "created_at": "2025-01-15T10:00:00Z"
    }
  ]
}
```

---

## Dosya Islemleri

### Yukleme Kurallarini Getir (Public)

```
GET /api/files/upload-rules
```

> **NOT:** Bu endpoint JWT gerektirmez, sadece API Key yeterlidir.

**Response (200):**
```json
{
  "success": true,
  "data": {
    "allowed_extensions": ["pdf", "jpg", "jpeg", "png", "dwg", "step", "stp", "iges", "igs", "ai", "psd", "zip", "rar"],
    "max_size": 52428800,
    "max_size_formatted": "50.00 MB"
  }
}
```

---

### Talebe Dosya Yukle

```
POST /api/requests/{requestId}/files
Content-Type: multipart/form-data
```

**Form Data:**
- `files[]` - Dosya(lar) (birden fazla dosya gonderilebilir)
- `description` (optional) - Dosya aciklamasi

> **ONEMLI:** Dosya input alani `files[]` olarak isimlendirilmelidir (array formati).

**Ornek JavaScript/Fetch:**
```javascript
const formData = new FormData();

// Tek dosya
formData.append('files[]', file1);

// Birden fazla dosya
formData.append('files[]', file1);
formData.append('files[]', file2);

// Opsiyonel aciklama
formData.append('description', 'Kalip cizimleri');

const response = await fetch(`/api/requests/${requestId}/files`, {
  method: 'POST',
  headers: {
    'X-Portal-Api-Key': API_KEY,
    'Authorization': `Bearer ${token}`,
    // Content-Type header'i GONDERMEYIN - browser otomatik multipart/form-data ekler
  },
  body: formData
});
```

**Basarili Response (201):**
```json
{
  "success": true,
  "message": "2 dosya basariyla yuklendi.",
  "data": [
    {
      "id": 51,
      "dosya_adi": "kalip-cizim-v2.pdf",
      "extension": "pdf",
      "dosya_boyut": 2097152,
      "download_url": "/api/files/51/download"
    },
    {
      "id": 52,
      "dosya_adi": "teknik-resim.dwg",
      "extension": "dwg",
      "dosya_boyut": 5242880,
      "download_url": "/api/files/52/download"
    }
  ]
}
```

**Gecersiz Dosya (400):**
```json
{
  "success": false,
  "message": "Gecersiz dosya uzantisi: exe. Izin verilen: pdf, jpg, jpeg, png, dwg, step, stp, iges, igs, ai, psd, zip, rar"
}
```

---

### Talebe Ait Dosyalari Listele

```
GET /api/requests/{requestId}/files
```

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 50,
      "dosya_adi": "kalip-cizim.pdf",
      "extension": "pdf",
      "dosya_boyut": 1048576,
      "dosya_boyut_formatted": "1.00 MB",
      "download_url": "/api/files/50/download",
      "created_at": "2025-01-15T10:30:00Z"
    }
  ]
}
```

---

### Dosya Indir

```
GET /api/files/{id}/download
```

**Basarili Response:** Binary dosya (Content-Disposition: attachment)

**Dosya Bulunamadi (404):**
```json
{
  "success": false,
  "message": "Dosya bulunamadi."
}
```

**Erisim Yetkisi Yok (403):**
```json
{
  "success": false,
  "message": "Bu dosyaya erisim yetkiniz yok."
}
```

---

### Dosya Sil

```
DELETE /api/files/{id}
```

> **NOT:** Sadece portal uzerinden yuklenen ve talep henuz isleme alinmamis dosyalar silinebilir.

**Basarili Response (200):**
```json
{
  "success": true,
  "message": "Dosya basariyla silindi."
}
```

**Silinemez Dosya (403):**
```json
{
  "success": false,
  "message": "Talep isleme alindiktan sonra dosya silinemez."
}
```

---

## Health Check Endpoint'leri (Public)

Bu endpoint'ler API Key gerektirmez.

### API Durumu

```
GET /api/health
```

**Response (200):**
```json
{
  "success": true,
  "status": "ok",
  "timestamp": "2025-01-15T10:00:00.000000Z",
  "version": "1.0.0",
  "environment": "production"
}
```

### Veritabani Baglantisi

```
GET /api/health/db
```

**Response (200):**
```json
{
  "success": true,
  "database": "connected",
  "driver": "sqlsrv"
}
```

---

## Hata Kodlari

| HTTP Kodu | Anlami |
|-----------|--------|
| 200 | Basarili |
| 201 | Kayit olusturuldu |
| 400 | Gecersiz istek (validation hatasi) |
| 401 | Kimlik dogrulama hatasi |
| 403 | Yetkilendirme hatasi |
| 404 | Kayit bulunamadi |
| 422 | Validation hatasi (detayli) |
| 429 | Rate limit asildi |
| 500 | Sunucu hatasi |

### Validation Hatasi Ornegi (422)

```json
{
  "success": false,
  "message": "Verilen veri gecersiz.",
  "errors": {
    "email": ["Email alani zorunludur."],
    "password": ["Sifre en az 8 karakter olmalidir."]
  }
}
```

---

## Rate Limiting

- **Genel API:** 10 istek/saniye
- **Login Endpoint:** 5 istek/dakika

Rate limit asildiginda HTTP 429 kodu doner:

```json
{
  "success": false,
  "message": "Too Many Attempts."
}
```

---

## Ornek Frontend Entegrasyonu (TypeScript)

```typescript
// api-client.ts

const API_BASE_URL = 'https://api.yudo.com.tr/api';
const API_KEY = process.env.NEXT_PUBLIC_PORTAL_API_KEY;

interface ApiResponse<T> {
  success: boolean;
  message?: string;
  data?: T;
  errors?: Record<string, string[]>;
}

class PortalApiClient {
  private token: string | null = null;
  private tokenExpiresAt: number | null = null;

  setToken(token: string, expiresIn: number) {
    this.token = token;
    // expiresIn saniye cinsinden, milisaniyeye cevirip biraz erken yenileyelim
    this.tokenExpiresAt = Date.now() + (expiresIn * 1000) - 60000; // 1 dakika erken
  }

  clearToken() {
    this.token = null;
    this.tokenExpiresAt = null;
  }

  isTokenExpired(): boolean {
    if (!this.tokenExpiresAt) return true;
    return Date.now() >= this.tokenExpiresAt;
  }

  private async request<T>(
    endpoint: string,
    options: RequestInit = {}
  ): Promise<ApiResponse<T>> {
    const headers: HeadersInit = {
      'X-Portal-Api-Key': API_KEY!,
      'Accept': 'application/json',
      ...options.headers,
    };

    // Content-Type sadece JSON istekleri icin ekle (file upload haric)
    if (!(options.body instanceof FormData)) {
      headers['Content-Type'] = 'application/json';
    }

    // Auth gerektiren endpoint'ler icin token ekle
    if (this.token) {
      headers['Authorization'] = `Bearer ${this.token}`;
    }

    const response = await fetch(`${API_BASE_URL}${endpoint}`, {
      ...options,
      headers,
    });

    if (!response.ok && response.status === 401) {
      // Token gecersiz, cikis yap
      this.clearToken();
      window.location.href = '/login';
    }

    return response.json();
  }

  // Auth
  async login(email: string, password: string) {
    const response = await this.request<{
      access_token: string;
      expires_in: number;
      user: any;
    }>('/auth/login', {
      method: 'POST',
      body: JSON.stringify({ email, password }),
    });

    if (response.success && response.data) {
      this.setToken(response.data.access_token, response.data.expires_in);
    }

    return response;
  }

  async logout() {
    const response = await this.request('/auth/logout', { method: 'POST' });
    this.clearToken();
    return response;
  }

  async refreshToken() {
    const response = await this.request<{
      access_token: string;
      expires_in: number;
    }>('/auth/refresh', { method: 'POST' });

    if (response.success && response.data) {
      this.setToken(response.data.access_token, response.data.expires_in);
    }

    return response;
  }

  async me() {
    return this.request('/auth/me');
  }

  // Lookups
  async getLookups() {
    return this.request('/lookups/all');
  }

  async getUploadRules() {
    return this.request('/files/upload-rules');
  }

  // Requests
  async getRequests(page = 1) {
    return this.request(`/requests?page=${page}`);
  }

  async getRequest(id: number) {
    return this.request(`/requests/${id}`);
  }

  async createRequest(data: any) {
    return this.request('/requests', {
      method: 'POST',
      body: JSON.stringify(data),
    });
  }

  async updateRequest(id: number, data: any) {
    return this.request(`/requests/${id}`, {
      method: 'PUT',
      body: JSON.stringify(data),
    });
  }

  async cancelRequest(id: number) {
    return this.request(`/requests/${id}/cancel`, { method: 'POST' });
  }

  async getRequestHistory(id: number) {
    return this.request(`/requests/${id}/history`);
  }

  // Files
  async getRequestFiles(requestId: number) {
    return this.request(`/requests/${requestId}/files`);
  }

  async uploadFiles(requestId: number, files: File[], description?: string) {
    const formData = new FormData();
    files.forEach(file => formData.append('files[]', file));
    if (description) formData.append('description', description);

    return this.request(`/requests/${requestId}/files`, {
      method: 'POST',
      body: formData,
      // Content-Type header'i GONDERME - browser otomatik ayarlar
    });
  }

  async deleteFile(id: number) {
    return this.request(`/files/${id}`, { method: 'DELETE' });
  }

  getFileDownloadUrl(id: number): string {
    return `${API_BASE_URL}/files/${id}/download`;
  }

  // Jobs
  async getJobs(page = 1) {
    return this.request(`/jobs?page=${page}`);
  }

  async getJob(jobNo: string) {
    return this.request(`/jobs/${jobNo}`);
  }

  // Company
  async getCompany() {
    return this.request('/company');
  }
}

export const apiClient = new PortalApiClient();
```

---

## Notlar ve Dikkat Edilmesi Gerekenler

1. **Token Suresi:** `expires_in` degeri **saniye** cinsindendir. Frontend'de token yenileme mekanizmasi kurulurken bu dikkate alinmalidir.

2. **Dosya Yukleme:** Dosya yuklerken `files[]` array formati kullanilmalidir. `Content-Type` header'i manuel olarak gonderilmemelidir.

3. **Talep Durumu:** Talepler sadece "Talep Alindi" durumundayken guncellenebilir veya iptal edilebilir.

4. **Dosya Silme:** Sadece portal uzerinden yuklenen dosyalar silinebilir ve talep isleme alinmadan once yapilmalidir.

5. **Rate Limiting:** Login endpoint'i dakikada 5 istekle sinirlidir. Kullanici arayuzunde bu goz onunde bulundurulmalidir.

6. **API Key:** Tum isteklerde `X-Portal-Api-Key` header'i zorunludur. Frontend uygulamasinda bu key environment variable olarak tutulmalidir.
