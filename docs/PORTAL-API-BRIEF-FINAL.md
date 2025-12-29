# Sıcak Yolluk Portal API - Final Brief (V4)

## 🎯 Proje Özeti

Müşteri portalı için **bağımsız** Laravel API uygulaması. Mevcut ERP tabloları ile entegre çalışacak, portal'a özel takip tabloları da olacak.

---

## 🏗️ Sistem Mimarisi

```
┌──────────────────────────────────────────────────────────────────────────────┐
│                              SİSTEM MİMARİSİ                                  │
└──────────────────────────────────────────────────────────────────────────────┘

    ┌─────────────────┐                                                    
    │   MÜŞTERİLER    │                                                    
    │   (Browser)     │                                                    
    └────────┬────────┘                                                    
             │ HTTPS                                                       
             ▼                                                             
┌─────────────────────────┐         HTTPS          ┌─────────────────────────┐
│                         │ ◄────────────────────► │                         │
│   HETZNER VPS           │                        │   API SUNUCU            │
│   portal.yudo.com.tr    │                        │   api.yudo.com.tr       │
│                         │                        │                         │
│   • Laravel Portal UI   │                        │   • Laravel API         │
│   • Blade/Livewire      │                        │   • JWT Auth            │
│                         │                        │                         │
└─────────────────────────┘                        └───────────┬─────────────┘
                                                               │              
                                                               │ DMZ          
                                                               ▼              
                                                   ┌─────────────────────────┐
                                                   │   DATABASE SUNUCU       │
                                                   │   SQL Server (PRGERP)   │
                                                   ├─────────────────────────┤
                                                   │   Mevcut ERP Tabloları: │
                                                   │   • jobs (INSERT)       │
                                                   │   • technical_datas     │
                                                   │   • files               │
                                                   │   • companies (READ)    │
                                                   │   • contacts (READ)     │
                                                   ├─────────────────────────┤
                                                   │   Yeni Portal Tabloları:│
                                                   │   • portal_users        │
                                                   │   • portal_invitations  │
                                                   │   • portal_requests     │
                                                   │   • portal_request_...  │
                                                   └───────────┬─────────────┘
                                                               │              
                                                   ┌───────────┴─────────────┐
                                                   │   FILE SERVER           │
                                                   │   (Dosya Storage)       │
                                                   └─────────────────────────┘
```

---

## 📊 Tablo Kullanım Matrisi

| Tablo | READ | INSERT | UPDATE | Açıklama |
|-------|------|--------|--------|----------|
| **companies** | ✅ | ❌ | ❌ | Firma bilgileri (readonly) |
| **contacts** | ✅ | ❌ | ❌ | Yetkili bilgileri (readonly) |
| **users** | ✅ | ❌ | ❌ | ERP kullanıcıları - satışçı/tasarımcı (readonly) |
| **jobs** | ✅ | ✅ | ❌ | Yeni iş kaydı oluşturulacak |
| **technical_datas** | ✅ | ✅ | ❌ | Teknik bilgiler eklenecek |
| **files** | ✅ | ✅ | ❌ | Dosyalar eklenecek |
| **job_states** | ✅ | ❌ | ❌ | İş durumları (readonly) |
| **portal_users** | ✅ | ✅ | ✅ | Portal kullanıcıları |
| **portal_invitations** | ✅ | ✅ | ✅ | Davetiyeler |
| **portal_requests** | ✅ | ✅ | ✅ | Portal talepleri |
| **portal_request_states** | ✅ | ✅ | ❌ | Portal talep durumları |
| **portal_request_state_logs** | ✅ | ✅ | ❌ | Durum geçmişi |

---

## 🔄 Talep Akışı

### Müşteri Talep Oluşturduğunda:

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                         TALEP OLUŞTURMA AKIŞI                                │
└─────────────────────────────────────────────────────────────────────────────┘

Müşteri "Tasarım Talebi" veya "Teklif Talebi" oluşturur
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│  1. jobs tablosuna INSERT                                                    │
│     • job_no = YT25-1001 (otomatik oluşturulacak)                           │
│     • job_category_id = ? (tasarım/teklif kategorisi)                       │
│     • mold_maker_id = portal_user'ın company_id'si                          │
│     • mold_maker_ref_no = müşterinin referans kodu                          │
│     • user_id = firmaya atanmış satışçı (companies.sales_person_id)         │
└─────────────────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│  2. technical_datas tablosuna INSERT                                         │
│     • job_id = oluşturulan job'ın id'si                                     │
│     • parca_agirligi, et_kalinligi, malzeme...                              │
│     • kalip_x, kalip_y, kalip_d, kalip_e (L için)...                        │
│     • kalip_parca_sayisi (göz sayısı), meme_sayisi...                       │
└─────────────────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│  3. files tablosuna INSERT (dosya varsa)                                     │
│     • job_id = oluşturulan job'ın id'si                                     │
│     • baglanti_tablo_adi = 'jobs' veya 'technical_datas'                    │
│     • dosya_yolu = güvenli path (UUID ile)                                  │
└─────────────────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│  4. portal_requests tablosuna INSERT                                         │
│     • job_id = oluşturulan job'ın id'si                                     │
│     • portal_user_id = giriş yapan müşteri                                  │
│     • request_type = 1 (Tasarım) veya 2 (Teklif)                            │
│     • request_no = PR-2025-0001 (portal talep numarası)                     │
│     • current_state_id = 1 (Talep Alındı)                                   │
│     • Müşteri beklentileri, notlar vs.                                      │
└─────────────────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│  5. portal_request_state_logs tablosuna INSERT                               │
│     • İlk durum kaydı: "Talep Alındı"                                       │
└─────────────────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│  6. Bildirim & Mail (Sonraki aşama)                                          │
│     • Satışçıya bildirim                                                    │
│     • Tasarımcıya bildirim                                                  │
│     • Email gönderimi                                                        │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## 🔢 job_no Oluşturma Mantığı

```
Format: YT{YY}-{SIRA}

Örnekler:
- YT25-1000  (2025 yılı, 1000. iş)
- YT25-1001  (2025 yılı, 1001. iş)
- YT26-1     (2026 yılı, ilk iş)
- YT26-2     (2026 yılı, 2. iş)

Algoritma:
1. Mevcut yılı al (25, 26...)
2. O yıla ait son job_no'yu bul
3. Yoksa 1'den başla, varsa +1 yap
```

### JobNumberService.php

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class JobNumberService
{
    /**
     * Yeni job_no oluştur
     * Format: YT{YY}-{SIRA}
     */
    public function generate(): string
    {
        $year = date('y'); // 25, 26...
        $prefix = "YT{$year}-";
        
        // O yıla ait son numarayı bul
        $lastJob = DB::table('jobs')
            ->where('job_no', 'LIKE', $prefix . '%')
            ->orderByRaw("CAST(SUBSTRING(job_no, LEN(?) + 1, 10) AS INT) DESC", [$prefix])
            ->first();
        
        if ($lastJob) {
            // Mevcut numaradan devam et
            $lastNumber = (int) str_replace($prefix, '', $lastJob->job_no);
            $newNumber = $lastNumber + 1;
        } else {
            // Yılın ilk işi
            $newNumber = 1;
        }
        
        return $prefix . $newNumber;
    }
}
```

---

## 📋 Yeni Portal Tabloları

### 1. portal_users

```sql
CREATE TABLE portal_users (
    id BIGINT IDENTITY(1,1) PRIMARY KEY,
    contact_id BIGINT NOT NULL,
    company_id BIGINT NOT NULL,
    email NVARCHAR(100) NOT NULL UNIQUE,
    password NVARCHAR(255) NOT NULL,
    remember_token NVARCHAR(255) NULL,
    last_login_at DATETIME NULL,
    last_login_ip NVARCHAR(45) NULL,
    is_active SMALLINT DEFAULT 1,
    created_at DATETIME DEFAULT GETDATE(),
    updated_at DATETIME NULL,
    
    CONSTRAINT FK_portal_users_contact FOREIGN KEY (contact_id) REFERENCES contacts(id),
    CONSTRAINT FK_portal_users_company FOREIGN KEY (company_id) REFERENCES companies(id)
);

CREATE INDEX IX_portal_users_email ON portal_users(email);
CREATE INDEX IX_portal_users_company ON portal_users(company_id);
```

### 2. portal_invitations

```sql
CREATE TABLE portal_invitations (
    id BIGINT IDENTITY(1,1) PRIMARY KEY,
    contact_id BIGINT NOT NULL,
    company_id BIGINT NOT NULL,
    
    -- Davetiye bilgileri
    token NVARCHAR(100) NOT NULL UNIQUE,
    email NVARCHAR(100) NOT NULL,
    
    -- Gönderen (ERP user - satışçı)
    invited_by_user_id BIGINT NOT NULL,
    
    -- Tarihler
    sent_at DATETIME DEFAULT GETDATE(),
    expires_at DATETIME NOT NULL,
    accepted_at DATETIME NULL,
    
    -- Oluşturulan portal user (kabul edildiyse)
    portal_user_id BIGINT NULL,
    
    -- Durum: 1=Bekliyor, 2=Kabul Edildi, 3=Süresi Doldu, 4=İptal
    status SMALLINT DEFAULT 1,
    
    is_active SMALLINT DEFAULT 1,
    created_at DATETIME DEFAULT GETDATE(),
    updated_at DATETIME NULL,
    
    CONSTRAINT FK_portal_invitations_contact FOREIGN KEY (contact_id) REFERENCES contacts(id),
    CONSTRAINT FK_portal_invitations_company FOREIGN KEY (company_id) REFERENCES companies(id),
    CONSTRAINT FK_portal_invitations_invited_by FOREIGN KEY (invited_by_user_id) REFERENCES users(id),
    CONSTRAINT FK_portal_invitations_portal_user FOREIGN KEY (portal_user_id) REFERENCES portal_users(id)
);

CREATE INDEX IX_portal_invitations_token ON portal_invitations(token);
CREATE INDEX IX_portal_invitations_email ON portal_invitations(email);
```

### 3. portal_request_states

```sql
CREATE TABLE portal_request_states (
    id BIGINT IDENTITY(1,1) PRIMARY KEY,
    name NVARCHAR(50) NOT NULL,
    english_name NVARCHAR(50) NULL,
    color_class NVARCHAR(50) NULL,
    sort_order SMALLINT DEFAULT 0,
    aciklama NVARCHAR(255) NULL,
    is_active SMALLINT DEFAULT 1
);

-- Varsayılan durumlar
INSERT INTO portal_request_states (name, english_name, color_class, sort_order) VALUES
('Talep Alındı', 'Request Received', 'blue', 1),
('İnceleniyor', 'Under Review', 'yellow', 2),
('Çalışılıyor', 'In Progress', 'orange', 3),
('Revizyon Bekliyor', 'Pending Revision', 'purple', 4),
('Tamamlandı', 'Completed', 'green', 5),
('İptal Edildi', 'Cancelled', 'red', 6);
```

### 4. portal_requests

```sql
CREATE TABLE portal_requests (
    id BIGINT IDENTITY(1,1) PRIMARY KEY,
    
    -- Portal talep numarası
    request_no NVARCHAR(20) NOT NULL UNIQUE,
    
    -- İlişkiler
    portal_user_id BIGINT NOT NULL,
    company_id BIGINT NOT NULL,
    job_id BIGINT NOT NULL,                    -- jobs tablosuna FK
    
    -- Talep bilgileri
    request_type SMALLINT NOT NULL,            -- 1: Tasarım Talebi, 2: Teklif Talebi
    
    -- Müşteri referansları
    customer_reference_code NVARCHAR(100) NULL,  -- Müşterinin kendi referans kodu
    customer_mold_code NVARCHAR(100) NULL,       -- Müşterinin kalıp kodu
    
    -- Müşteri beklentileri / notları
    customer_notes NVARCHAR(MAX) NULL,
    expected_delivery_date DATE NULL,
    priority SMALLINT DEFAULT 2,               -- 1: Düşük, 2: Normal, 3: Yüksek, 4: Acil
    
    -- Portal'a özel ek alanlar (ERP'de karşılığı yok)
    kalip_z DECIMAL(10,2) NULL,                -- Z ölçüsü (technical_datas'ta yok)
    
    -- Portal durumu
    current_state_id BIGINT DEFAULT 1,
    
    -- Meta
    is_active SMALLINT DEFAULT 1,
    created_at DATETIME DEFAULT GETDATE(),
    updated_at DATETIME NULL,
    
    CONSTRAINT FK_portal_requests_portal_user FOREIGN KEY (portal_user_id) REFERENCES portal_users(id),
    CONSTRAINT FK_portal_requests_company FOREIGN KEY (company_id) REFERENCES companies(id),
    CONSTRAINT FK_portal_requests_job FOREIGN KEY (job_id) REFERENCES jobs(id),
    CONSTRAINT FK_portal_requests_state FOREIGN KEY (current_state_id) REFERENCES portal_request_states(id)
);

CREATE INDEX IX_portal_requests_request_no ON portal_requests(request_no);
CREATE INDEX IX_portal_requests_company ON portal_requests(company_id);
CREATE INDEX IX_portal_requests_job ON portal_requests(job_id);
CREATE INDEX IX_portal_requests_state ON portal_requests(current_state_id);
CREATE INDEX IX_portal_requests_created ON portal_requests(created_at DESC);
```

### 5. portal_request_state_logs

```sql
CREATE TABLE portal_request_state_logs (
    id BIGINT IDENTITY(1,1) PRIMARY KEY,
    portal_request_id BIGINT NOT NULL,
    portal_request_state_id BIGINT NOT NULL,
    
    aciklama NVARCHAR(500) NULL,
    
    -- Kim değiştirdi?
    changed_by_user_id BIGINT NULL,            -- ERP user değiştirdiyse
    changed_by_portal_user_id BIGINT NULL,     -- Portal user değiştirdiyse
    
    is_active SMALLINT DEFAULT 1,
    created_at DATETIME DEFAULT GETDATE(),
    
    CONSTRAINT FK_portal_state_logs_request FOREIGN KEY (portal_request_id) 
        REFERENCES portal_requests(id) ON DELETE CASCADE,
    CONSTRAINT FK_portal_state_logs_state FOREIGN KEY (portal_request_state_id) 
        REFERENCES portal_request_states(id),
    CONSTRAINT FK_portal_state_logs_user FOREIGN KEY (changed_by_user_id) 
        REFERENCES users(id),
    CONSTRAINT FK_portal_state_logs_portal_user FOREIGN KEY (changed_by_portal_user_id) 
        REFERENCES portal_users(id)
);

CREATE INDEX IX_portal_state_logs_request ON portal_request_state_logs(portal_request_id);
CREATE INDEX IX_portal_state_logs_created ON portal_request_state_logs(created_at DESC);
```

---

## 📁 Mevcut Tablolara INSERT Formatı

### jobs Tablosuna INSERT

```php
// Talep oluşturulduğunda jobs'a eklenecek veriler
$jobData = [
    'job_no' => $jobNumberService->generate(),  // YT25-1001
    'job_category_id' => 1,  // System Sales (sabit)
    
    // Müşteri bilgileri
    'mold_maker_id' => $portalUser->company_id,
    'mold_maker_contact_id' => $portalUser->contact_id,
    'mold_maker_ref_no' => $request->customer_reference_code,
    
    // Atanan satışçı (firmaya tanımlı)
    'user_id' => $portalUser->company->sales_person_id,
    
    // Açıklama
    'aciklama' => "Portal üzerinden oluşturuldu. Talep No: {$request->request_no}",
    
    // Diğer
    'is_active' => 1,
    'created_at' => now(),
    'updated_at' => now(),
];
```

### technical_datas Tablosuna INSERT

```php
// Teknik bilgiler - Mevcut tablo kolonlarına uygun
$technicalData = [
    'job_id' => $job->id,
    
    // Parça bilgileri
    'parca_agirligi' => $request->parca_agirligi,
    'et_kalinligi' => $request->et_kalinligi,
    'malzeme' => $request->malzeme,
    'malzeme_katki' => $request->katki_var_mi ? $request->katki_turu : null,
    'malzeme_katki_yuzdesi' => $request->katki_orani,
    
    // Kalıp ölçüleri
    'kalip_x' => $request->kalip_x,
    'kalip_y' => $request->kalip_y,
    // NOT: kalip_z mevcut tabloda yok, portal_requests'te tutulacak
    'kalip_d' => $request->kalip_d,
    'kalip_e' => $request->kalip_l,  // L ölçüsü kalip_e kolonunda
    
    // Meme bilgileri
    'kalip_parca_sayisi' => $request->goz_sayisi,
    'meme_sayisi' => $request->meme_sayisi,
    'tip_sekli' => $request->meme_tipi,  // 'parca' veya 'yolluk'
    
    // Diğer
    'is_active' => 1,
    'created_at' => now(),
    'updated_at' => now(),
];
```

### files Tablosuna INSERT

```php
// Dosya yükleme
$fileData = [
    'job_id' => $job->id,
    'baglanti_id' => $job->id,
    'baglanti_tablo_adi' => 'jobs',
    
    'dosya_adi' => $originalFileName,           // kalip-cizim.pdf
    'dosya_yolu' => $storagePath,               // portal/2025/01/uuid.pdf
    'extension' => $extension,                   // pdf
    'dosya_boyut' => $fileSize,
    'dosya_url' => null,                        // Güvenlik için URL yok
    
    'aciklama' => "Portal üzerinden yüklendi. Talep No: {$request->request_no}",
    'user_id' => null,                          // Portal user, ERP user değil
    
    'is_active' => 1,
    'created_at' => now(),
    'updated_at' => now(),
];
```

---

## 🔐 Dosya Güvenliği

### Storage Yapısı (NFS Mount)

```
/mnt/fileserver/portal/          ← .env: PORTAL_STORAGE_PATH
├── 2025/
│   ├── 01/
│   │   ├── a7f3b2c1-9d4e-4f5a-8b2c.pdf
│   │   ├── b8g4c3d2-0e5f-6g7h-9i3j.step
│   │   └── ...
│   └── 02/
│       └── ...
└── 2026/
    └── ...

# files tablosundaki dosya_yolu örneği:
# 2025/01/a7f3b2c1-9d4e-4f5a-8b2c.pdf (relative path)
```

### Dosya Erişim Akışı

```
❌ YANLIŞ - Doğrudan URL ile erişim
   https://fileserver/portal/2025/01/a7f3b2c1.pdf

✅ DOĞRU - API üzerinden erişim
   GET /api/files/{id}/download
   → JWT token kontrolü
   → Dosyanın bu kullanıcının firmasına ait olduğu kontrolü
   → Dosya stream olarak döner
```

### FileStorageService.php

```php
<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class FileStorageService
{
    protected string $basePath;
    
    public function __construct()
    {
        // NFS mount path - .env'den alınır
        $this->basePath = config('portal.upload.storage_path', '/mnt/fileserver/portal');
    }
    
    /**
     * Dosyayı güvenli şekilde kaydet
     */
    public function store(UploadedFile $file): array
    {
        $year = date('Y');
        $month = date('m');
        $uuid = Str::uuid();
        $extension = strtolower($file->getClientOriginalExtension());
        
        // Güvenli dosya adı
        $safeFileName = $uuid . '.' . $extension;
        $relativePath = "{$year}/{$month}";
        $fullDirectory = "{$this->basePath}/{$relativePath}";
        $fullPath = "{$fullDirectory}/{$safeFileName}";
        
        // Klasör yoksa oluştur
        if (!File::isDirectory($fullDirectory)) {
            File::makeDirectory($fullDirectory, 0755, true);
        }
        
        // Dosyayı kaydet
        $file->move($fullDirectory, $safeFileName);
        
        return [
            'original_name' => $file->getClientOriginalName(),
            'storage_path' => "{$relativePath}/{$safeFileName}",  // DB'ye kaydedilecek relative path
            'full_path' => $fullPath,                              // Tam dosya yolu
            'extension' => $extension,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ];
    }
    
    /**
     * Dosyanın tam yolunu döndür
     */
    public function getFullPath(string $relativePath): string
    {
        return "{$this->basePath}/{$relativePath}";
    }
    
    /**
     * Dosya var mı kontrolü
     */
    public function exists(string $relativePath): bool
    {
        return File::exists($this->getFullPath($relativePath));
    }
    
    /**
     * Dosyayı sil
     */
    public function delete(string $relativePath): bool
    {
        $fullPath = $this->getFullPath($relativePath);
        
        if (File::exists($fullPath)) {
            return File::delete($fullPath);
        }
        
        return false;
    }
    
    /**
     * Dosya erişim kontrolü
     */
    public function canAccess(PortalUser $user, ErpFile $file): bool
    {
        // Dosyanın bağlı olduğu job'ı kontrol et
        $job = Job::find($file->job_id);
        
        if (!$job) {
            return false;
        }
        
        // Job kullanıcının firmasına ait mi?
        return $job->mold_maker_id === $user->company_id;
    }
}
```

---

## 🛣️ API Endpoints

### Health Check

```
GET  /api/health                        # API durumu
GET  /api/health/db                     # DB bağlantı testi
```

### Authentication

```
POST /api/auth/login                    # Login
POST /api/auth/logout                   # Logout
POST /api/auth/refresh                  # Token yenile
GET  /api/auth/me                       # Mevcut kullanıcı bilgisi
```

### Invitations (Davetiye)

```
GET  /api/invitations/{token}           # Davetiye detayı (public)
POST /api/invitations/{token}/accept    # Daveti kabul et ve kayıt ol (public)
```

### Requests (Talepler)

```
GET    /api/requests                    # Talep listesi (kendi firması)
POST   /api/requests                    # Yeni talep oluştur
GET    /api/requests/{id}               # Talep detayı
PUT    /api/requests/{id}               # Talep güncelle (sadece bekleyen)
POST   /api/requests/{id}/cancel        # Talep iptal et
GET    /api/requests/{id}/history       # Durum geçmişi
```

### Files (Dosyalar)

```
POST   /api/requests/{id}/files         # Dosya yükle
GET    /api/files/{id}/download         # Dosya indir
DELETE /api/files/{id}                  # Dosya sil
```

### Lookup Data (Readonly)

```
GET    /api/states                      # Portal talep durumları
GET    /api/company                     # Kendi firma bilgisi
GET    /api/jobs                        # Firmaya ait mevcut işler
GET    /api/jobs/{job_no}               # İş detayı (job_no ile)
GET    /api/materials                   # Malzeme listesi (sabit)
GET    /api/additives                   # Katkı türleri (sabit)
```

---

## 📁 Proje Klasör Yapısı

```
/var/www/portal-api/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/
│   │   │       ├── AuthController.php
│   │   │       ├── InvitationController.php
│   │   │       ├── RequestController.php
│   │   │       ├── FileController.php
│   │   │       ├── CompanyController.php
│   │   │       ├── JobController.php
│   │   │       └── LookupController.php
│   │   ├── Middleware/
│   │   │   ├── ValidatePortalApiKey.php
│   │   │   └── LogApiRequests.php
│   │   ├── Requests/
│   │   │   ├── Auth/
│   │   │   │   └── LoginRequest.php
│   │   │   ├── Invitation/
│   │   │   │   └── AcceptInvitationRequest.php
│   │   │   └── Portal/
│   │   │       ├── StoreRequestRequest.php
│   │   │       └── UpdateRequestRequest.php
│   │   └── Resources/
│   │       ├── PortalUserResource.php
│   │       ├── InvitationResource.php
│   │       ├── RequestResource.php
│   │       ├── RequestCollection.php
│   │       ├── StateResource.php
│   │       ├── FileResource.php
│   │       ├── CompanyResource.php
│   │       └── JobResource.php
│   ├── Models/
│   │   │   # Yeni portal tabloları
│   │   ├── PortalUser.php
│   │   ├── PortalInvitation.php
│   │   ├── PortalRequest.php
│   │   ├── PortalRequestState.php
│   │   ├── PortalRequestStateLog.php
│   │   │
│   │   │   # Mevcut ERP tabloları
│   │   ├── Company.php
│   │   ├── Contact.php
│   │   ├── Job.php
│   │   ├── TechnicalData.php
│   │   ├── User.php
│   │   └── File.php
│   │
│   └── Services/
│       ├── JobNumberService.php
│       ├── RequestNumberService.php
│       ├── FileStorageService.php
│       └── InvitationService.php
│
├── config/
│   ├── auth.php
│   ├── database.php
│   └── portal.php
│
├── database/
│   └── migrations/
│       ├── 2025_01_01_000001_create_portal_users_table.php
│       ├── 2025_01_01_000002_create_portal_invitations_table.php
│       ├── 2025_01_01_000003_create_portal_request_states_table.php
│       ├── 2025_01_01_000004_create_portal_requests_table.php
│       └── 2025_01_01_000005_create_portal_request_state_logs_table.php
│
├── routes/
│   └── api.php
│
└── storage/
    └── app/
        └── ...               # Laravel varsayılan storage
                              # Dosyalar NFS mount'a gidecek:
                              # /mnt/fileserver/portal/
```

---

## ⚙️ Konfigürasyon

### config/portal.php

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API Güvenlik Ayarları
    |--------------------------------------------------------------------------
    */
    'api_key' => env('PORTAL_API_KEY'),
    
    'allowed_ips' => array_filter(
        explode(',', env('PORTAL_ALLOWED_IPS', ''))
    ),
    
    'rate_limit' => [
        'per_minute' => env('PORTAL_RATE_LIMIT', 60),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Dosya Yükleme Ayarları (NFS Mount)
    |--------------------------------------------------------------------------
    */
    'upload' => [
        'max_size' => env('PORTAL_UPLOAD_MAX_SIZE', 52428800), // 50MB
        'allowed_extensions' => [
            'pdf', 'jpg', 'jpeg', 'png', 
            'dwg', 'step', 'stp', 'iges', 'igs', 
            'ai', 'psd', 'zip', 'rar'
        ],
        'storage_path' => env('PORTAL_STORAGE_PATH', '/mnt/fileserver/portal'),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Numara Formatları
    |--------------------------------------------------------------------------
    */
    'job_number' => [
        'prefix' => 'YT',
        'year_format' => 'y',  // 25, 26...
    ],
    
    'request_number' => [
        'prefix' => 'PR',
        'year_format' => 'Y',  // 2025, 2026...
        'padding' => 4,        // PR-2025-0001
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Davetiye Ayarları
    |--------------------------------------------------------------------------
    */
    'invitation' => [
        'expires_in_days' => 7,
        'token_length' => 64,
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Talep Tipleri
    |--------------------------------------------------------------------------
    */
    'request_types' => [
        1 => 'Tasarım Talebi',
        2 => 'Teklif Talebi',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Öncelik Seviyeleri
    |--------------------------------------------------------------------------
    */
    'priorities' => [
        1 => 'Düşük',
        2 => 'Normal',
        3 => 'Yüksek',
        4 => 'Acil',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Malzeme Listesi (Sabit)
    |--------------------------------------------------------------------------
    */
    'materials' => [
        'ABS' => 'ABS',
        'PP' => 'PP (Polipropilen)',
        'PS' => 'PS (Polistiren)',
        'PA' => 'PA (Naylon)',
        'PC' => 'PC (Polikarbonat)',
        'POM' => 'POM (Asetal)',
        'PE' => 'PE (Polietilen)',
        'PET' => 'PET',
        'PMMA' => 'PMMA (Akrilik)',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Katkı Türleri (Sabit)
    |--------------------------------------------------------------------------
    */
    'additives' => [
        'glass_fiber' => 'Cam Elyaf',
        'talc' => 'Talc',
        'mineral' => 'Mineral',
        'carbon_fiber' => 'Karbon Elyaf',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Meme Tipleri (Sabit)
    |--------------------------------------------------------------------------
    */
    'nozzle_types' => [
        'parca' => 'Parçaya (Direct Gate)',
        'yolluk' => 'Yolluğa (Runner)',
    ],
];
```

### .env Örneği

```env
APP_NAME="Portal API"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.yudo.com.tr

#-------------------------------------------------
# SQL Server (DMZ üzerinden)
#-------------------------------------------------
DB_CONNECTION=sqlsrv
DB_HOST=192.168.x.x
DB_PORT=1433
DB_DATABASE=PRGERP
DB_USERNAME=portal_api_user
DB_PASSWORD=secure_password
DB_CHARSET=utf8
DB_ENCRYPT=no
DB_TRUST_SERVER_CERTIFICATE=true

#-------------------------------------------------
# JWT
#-------------------------------------------------
JWT_SECRET=your-jwt-secret
JWT_TTL=1440
JWT_REFRESH_TTL=20160

#-------------------------------------------------
# Portal Güvenlik
#-------------------------------------------------
PORTAL_API_KEY=your-secure-api-key-min-32-chars
PORTAL_ALLOWED_IPS=88.xxx.xxx.xxx

#-------------------------------------------------
# Dosya Storage (NFS Mount)
#-------------------------------------------------
PORTAL_UPLOAD_MAX_SIZE=52428800
PORTAL_STORAGE_PATH=/mnt/fileserver/portal

#-------------------------------------------------
# Rate Limiting
#-------------------------------------------------
PORTAL_RATE_LIMIT=60
```

---

## 🚀 Claude Code Başlangıç Komutu

```
Bu brief'e göre portal-api projesini oluştur.

Mimari:
- Bağımsız Laravel 11 API projesi
- SQL Server'a DMZ üzerinden bağlantı
- Hibrit yaklaşım: Mevcut ERP tablolarına (jobs, technical_datas, files) INSERT + Portal özel tablolar

Önemli noktalar:
1. job_no formatı: YT{YY}-{SIRA} (örn: YT25-1001)
2. Mevcut tablolara sadece INSERT (companies, contacts, users readonly)
3. Dosyalar güvenli path ile saklanacak (UUID)
4. Davetiye sistemi ile kullanıcı kaydı

Sırayla:
1. Laravel 11 projesi oluştur
2. Config dosyaları (database.php, portal.php, auth.php)
3. Migration dosyaları (portal_* tabloları)
4. Model'ler (hem portal hem ERP tabloları)
5. Service sınıfları (JobNumberService, FileStorageService, InvitationService)
6. Middleware'ler
7. Controller'lar
8. Route'lar
9. Request validation sınıfları
10. Resource sınıfları

Her adımda ne yaptığını açıkla.
```

---

## 📝 Önemli Notlar

1. **Hibrit Yaklaşım:** Mevcut ERP tablolarına (jobs, technical_datas, files) INSERT yapılacak + Portal'a özel tablolar (portal_requests vs.) da kullanılacak.

2. **job_no Formatı:** `YT{YY}-{SIRA}` - Her yıl sıfırdan başlar.

3. **job_category_id:** Tüm portal talepleri için `1` (System Sales) kullanılacak.

4. **Dosya Storage:** NFS mount üzerinden fileserver'a erişim. Path `.env`'de ayarlanabilir:
   ```
   PORTAL_STORAGE_PATH=/mnt/fileserver/portal
   ```

5. **Dosya Güvenliği:** UUID ile dosya adı, API üzerinden erişim kontrolü.

6. **Davetiye Sistemi:** ERP'deki satışçı davet gönderir, müşteri kabul edip kayıt olur.

7. **Readonly Tablolar:** companies, contacts, users - sadece okunur, asla yazılmaz.

8. **INSERT Tablolar:** jobs, technical_datas, files - yeni kayıt eklenir.

9. **Portal Tabloları:** portal_* - tam CRUD yetkisi.

10. **kalip_z Kolonu:** Mevcut `technical_datas` tablosunda yok. Sadece `portal_requests.kalip_z` kolonunda tutulacak. İlerleyen dönemlerde gerek kalmazsa kaldırılabilir.
