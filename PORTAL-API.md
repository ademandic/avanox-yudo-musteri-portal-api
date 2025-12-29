# Portal API Projesi - Claude Code Brief

## 🎯 Proje Özeti

Müşteri portalı için bağımsız bir Laravel API uygulaması oluşturulacak. Bu API, uzak sunucudaki portal uygulaması ile müşterinin local sunucusundaki ERP veritabanı arasında köprü görevi görecek.

**API Domain:** `api.yudo.com.tr`
**Framework:** Laravel 11
**PHP Version:** 8.2+
**Database:** MySQL (mevcut ERP veritabanına bağlanacak)

---

## 📁 Proje Kurulumu

### Adım 1: Laravel Projesi Oluştur

```bash
composer create-project laravel/laravel portal-api
cd portal-api
```

### Adım 2: Gerekli Paketleri Kur

```bash
# JWT Authentication
composer require tymon/jwt-auth

# CORS desteği (Laravel 11'de dahili, config gerekebilir)
# API Rate Limiting için (Laravel dahili)

# Opsiyonel - API Dokümantasyonu
composer require dedoc/scramble
```

### Adım 3: JWT Kurulumu

```bash
php artisan jwt:secret
php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider"
```

---

## 🗄️ Database Şeması

Mevcut ERP veritabanına aşağıdaki tabloları ekle. Migration dosyaları oluşturulacak.

### Tablo 1: design_requests

```php
Schema::create('design_requests', function (Blueprint $table) {
    $table->id();
    $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
    $table->string('title');
    $table->text('description');
    $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
    $table->enum('status', ['pending', 'in_progress', 'revision', 'completed', 'cancelled'])->default('pending');
    $table->foreignId('assigned_designer_id')->nullable()->constrained('users')->nullOnDelete();
    $table->foreignId('assigned_sales_id')->nullable()->constrained('users')->nullOnDelete();
    $table->date('due_date')->nullable();
    $table->text('notes')->nullable();
    $table->timestamps();
    $table->softDeletes();
    
    $table->index(['customer_id', 'status']);
    $table->index('created_at');
});
```

### Tablo 2: design_request_files

```php
Schema::create('design_request_files', function (Blueprint $table) {
    $table->id();
    $table->foreignId('design_request_id')->constrained()->onDelete('cascade');
    $table->string('original_name');
    $table->string('stored_path');
    $table->string('mime_type');
    $table->unsignedBigInteger('size'); // bytes
    $table->enum('type', ['attachment', 'revision', 'final'])->default('attachment');
    $table->foreignId('uploaded_by')->nullable()->constrained('customers')->nullOnDelete();
    $table->timestamps();
    
    $table->index('design_request_id');
});
```

### Tablo 3: design_request_comments

```php
Schema::create('design_request_comments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('design_request_id')->constrained()->onDelete('cascade');
    $table->morphs('commentable'); // customer veya user olabilir
    $table->text('content');
    $table->timestamps();
    
    $table->index('design_request_id');
});
```

### Tablo 4: notifications (Laravel default + özelleştirme)

```php
// Laravel'in default notifications tablosu kullanılacak
php artisan notifications:table
php artisan migrate
```

### Mevcut Tablolara Ekleme: customers

```php
// Mevcut customers tablosuna migration
Schema::table('customers', function (Blueprint $table) {
    $table->boolean('portal_access')->default(false)->after('email');
    $table->string('portal_password')->nullable()->after('portal_access');
    $table->timestamp('portal_last_login')->nullable();
    $table->rememberToken();
});
```

---

## 📂 Proje Klasör Yapısı

```
portal-api/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/
│   │   │       ├── AuthController.php
│   │   │       ├── DesignRequestController.php
│   │   │       ├── FileController.php
│   │   │       └── NotificationController.php
│   │   ├── Middleware/
│   │   │   ├── ValidatePortalApiKey.php
│   │   │   ├── ValidateCustomerJwt.php
│   │   │   └── LogApiRequests.php
│   │   ├── Requests/
│   │   │   ├── LoginRequest.php
│   │   │   ├── StoreDesignRequestRequest.php
│   │   │   ├── UpdateDesignRequestRequest.php
│   │   │   └── UploadFileRequest.php
│   │   └── Resources/
│   │       ├── CustomerResource.php
│   │       ├── DesignRequestResource.php
│   │       ├── DesignRequestCollection.php
│   │       ├── FileResource.php
│   │       └── NotificationResource.php
│   ├── Models/
│   │   ├── Customer.php
│   │   ├── User.php
│   │   ├── DesignRequest.php
│   │   ├── DesignRequestFile.php
│   │   └── DesignRequestComment.php
│   ├── Notifications/
│   │   └── NewDesignRequestNotification.php
│   └── Services/
│       ├── FileStorageService.php
│       └── NotificationService.php
├── config/
│   └── portal.php
├── database/
│   └── migrations/
├── routes/
│   └── api.php
└── storage/
    └── app/
        └── design-files/  # Dosyaların saklanacağı yer
```

---

## 🔐 Güvenlik Katmanları

### Config: config/portal.php

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Portal API Güvenlik Ayarları
    |--------------------------------------------------------------------------
    */
    
    // Uzak portal sunucusundan gelen istekleri doğrulamak için API Key
    'api_key' => env('PORTAL_API_KEY'),
    
    // Sadece bu IP'lerden gelen isteklere izin ver (Hetzner sunucu IP'si)
    'allowed_ips' => array_filter(explode(',', env('PORTAL_ALLOWED_IPS', ''))),
    
    // JWT ayarları
    'jwt_ttl' => env('PORTAL_JWT_TTL', 1440), // dakika (24 saat)
    'jwt_refresh_ttl' => env('PORTAL_JWT_REFRESH_TTL', 20160), // dakika (14 gün)
    
    // Dosya upload ayarları
    'upload' => [
        'max_size' => env('PORTAL_UPLOAD_MAX_SIZE', 52428800), // 50MB
        'allowed_mimes' => [
            'image/jpeg',
            'image/png',
            'image/gif',
            'application/pdf',
            'application/postscript', // AI files
            'image/vnd.adobe.photoshop', // PSD
            'application/zip',
            'application/x-rar-compressed',
        ],
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'ai', 'psd', 'zip', 'rar'],
    ],
    
    // Rate limiting
    'rate_limit' => [
        'per_minute' => env('PORTAL_RATE_LIMIT', 60),
    ],
];
```

### Middleware: ValidatePortalApiKey

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidatePortalApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-Portal-Api-Key');
        
        // API Key kontrolü
        if (!$apiKey || $apiKey !== config('portal.api_key')) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid API key'
            ], 401);
        }
        
        // IP kontrolü (boş değilse)
        $allowedIps = config('portal.allowed_ips');
        if (!empty($allowedIps) && !in_array($request->ip(), $allowedIps)) {
            \Log::warning('Portal API: Unauthorized IP attempt', [
                'ip' => $request->ip(),
                'endpoint' => $request->path()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'IP not authorized'
            ], 403);
        }
        
        return $next($request);
    }
}
```

### .env Değişkenleri

```env
# Portal API Güvenlik
PORTAL_API_KEY=your-super-secret-api-key-min-32-chars
PORTAL_ALLOWED_IPS=88.xxx.xxx.xxx  # Hetzner sunucu IP'si
PORTAL_JWT_TTL=1440
PORTAL_JWT_REFRESH_TTL=20160

# Dosya Upload
PORTAL_UPLOAD_MAX_SIZE=52428800

# Rate Limiting
PORTAL_RATE_LIMIT=60
```

---

## 🛣️ API Routes

### routes/api.php

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DesignRequestController;
use App\Http\Controllers\Api\FileController;
use App\Http\Controllers\Api\NotificationController;

/*
|--------------------------------------------------------------------------
| Portal API Routes
|--------------------------------------------------------------------------
| Tüm route'lar /api prefix'i ile gelir
| Middleware: api, portal.api-key
*/

// Health check (API key gerektirmez)
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString()
    ]);
});

// API Key korumalı route'lar
Route::middleware(['portal.api-key'])->group(function () {
    
    /*
    |--------------------------------------------------------------------------
    | Authentication Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('auth')->group(function () {
        // Public auth routes
        Route::post('/login', [AuthController::class, 'login']);
        
        // Protected auth routes
        Route::middleware(['portal.jwt'])->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::post('/refresh', [AuthController::class, 'refresh']);
            Route::get('/me', [AuthController::class, 'me']);
        });
    });
    
    /*
    |--------------------------------------------------------------------------
    | Protected Routes (JWT Required)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['portal.jwt'])->group(function () {
        
        // Design Requests
        Route::apiResource('design-requests', DesignRequestController::class)->except(['destroy']);
        Route::post('design-requests/{designRequest}/cancel', [DesignRequestController::class, 'cancel']);
        
        // Files
        Route::post('design-requests/{designRequest}/files', [FileController::class, 'upload']);
        Route::get('files/{file}/download', [FileController::class, 'download']);
        Route::delete('files/{file}', [FileController::class, 'destroy']);
        
        // Notifications
        Route::get('notifications', [NotificationController::class, 'index']);
        Route::put('notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
        Route::put('notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    });
});
```

---

## 🎮 Controllers

### AuthController.php

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    /**
     * Müşteri login
     */
    public function login(LoginRequest $request)
    {
        $customer = Customer::where('email', $request->email)
            ->where('portal_access', true)
            ->first();
        
        if (!$customer || !Hash::check($request->password, $customer->portal_password)) {
            return response()->json([
                'success' => false,
                'message' => 'Geçersiz e-posta veya şifre'
            ], 401);
        }
        
        // JWT token oluştur
        $token = JWTAuth::fromUser($customer);
        
        // Son login güncelle
        $customer->update(['portal_last_login' => now()]);
        
        return response()->json([
            'success' => true,
            'data' => [
                'customer' => new CustomerResource($customer),
                'token' => $token,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60 // saniye
            ]
        ]);
    }
    
    /**
     * Logout
     */
    public function logout()
    {
        JWTAuth::invalidate(JWTAuth::getToken());
        
        return response()->json([
            'success' => true,
            'message' => 'Başarıyla çıkış yapıldı'
        ]);
    }
    
    /**
     * Token yenile
     */
    public function refresh()
    {
        $token = JWTAuth::refresh(JWTAuth::getToken());
        
        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60
            ]
        ]);
    }
    
    /**
     * Mevcut kullanıcı bilgisi
     */
    public function me()
    {
        $customer = auth()->user();
        
        return response()->json([
            'success' => true,
            'data' => new CustomerResource($customer)
        ]);
    }
}
```

### DesignRequestController.php

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDesignRequestRequest;
use App\Http\Requests\UpdateDesignRequestRequest;
use App\Http\Resources\DesignRequestResource;
use App\Http\Resources\DesignRequestCollection;
use App\Models\DesignRequest;
use App\Models\User;
use App\Notifications\NewDesignRequestNotification;
use Illuminate\Http\Request;

class DesignRequestController extends Controller
{
    /**
     * Müşterinin tasarım taleplerini listele
     */
    public function index(Request $request)
    {
        $customer = auth()->user();
        
        $requests = DesignRequest::where('customer_id', $customer->id)
            ->with(['files', 'assignedDesigner', 'assignedSales'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));
        
        return new DesignRequestCollection($requests);
    }
    
    /**
     * Yeni tasarım talebi oluştur
     */
    public function store(StoreDesignRequestRequest $request)
    {
        $customer = auth()->user();
        
        $designRequest = DesignRequest::create([
            'customer_id' => $customer->id,
            'title' => $request->title,
            'description' => $request->description,
            'priority' => $request->priority ?? 'medium',
            'due_date' => $request->due_date,
            'notes' => $request->notes,
            'status' => 'pending'
        ]);
        
        // Tasarımcı ve satışçılara bildirim gönder
        $this->notifyStaff($designRequest);
        
        return response()->json([
            'success' => true,
            'message' => 'Tasarım talebi oluşturuldu',
            'data' => new DesignRequestResource($designRequest)
        ], 201);
    }
    
    /**
     * Tek bir tasarım talebini göster
     */
    public function show(DesignRequest $designRequest)
    {
        $customer = auth()->user();
        
        // Sadece kendi taleplerini görebilir
        if ($designRequest->customer_id !== $customer->id) {
            return response()->json([
                'success' => false,
                'message' => 'Bu talebe erişim yetkiniz yok'
            ], 403);
        }
        
        $designRequest->load(['files', 'comments', 'assignedDesigner', 'assignedSales']);
        
        return response()->json([
            'success' => true,
            'data' => new DesignRequestResource($designRequest)
        ]);
    }
    
    /**
     * Tasarım talebini güncelle (sadece pending durumunda)
     */
    public function update(UpdateDesignRequestRequest $request, DesignRequest $designRequest)
    {
        $customer = auth()->user();
        
        if ($designRequest->customer_id !== $customer->id) {
            return response()->json([
                'success' => false,
                'message' => 'Bu talebe erişim yetkiniz yok'
            ], 403);
        }
        
        if ($designRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Sadece bekleyen talepler güncellenebilir'
            ], 422);
        }
        
        $designRequest->update($request->validated());
        
        return response()->json([
            'success' => true,
            'message' => 'Tasarım talebi güncellendi',
            'data' => new DesignRequestResource($designRequest)
        ]);
    }
    
    /**
     * Talebi iptal et
     */
    public function cancel(DesignRequest $designRequest)
    {
        $customer = auth()->user();
        
        if ($designRequest->customer_id !== $customer->id) {
            return response()->json([
                'success' => false,
                'message' => 'Bu talebe erişim yetkiniz yok'
            ], 403);
        }
        
        if (!in_array($designRequest->status, ['pending', 'in_progress'])) {
            return response()->json([
                'success' => false,
                'message' => 'Bu talep iptal edilemez'
            ], 422);
        }
        
        $designRequest->update(['status' => 'cancelled']);
        
        return response()->json([
            'success' => true,
            'message' => 'Tasarım talebi iptal edildi'
        ]);
    }
    
    /**
     * Personele bildirim gönder
     */
    private function notifyStaff(DesignRequest $designRequest)
    {
        // Tasarımcı rolündeki kullanıcılar
        $designers = User::where('role', 'designer')->get();
        
        // Satış rolündeki kullanıcılar
        $salesPeople = User::where('role', 'sales')->get();
        
        $notification = new NewDesignRequestNotification($designRequest);
        
        foreach ($designers as $designer) {
            $designer->notify($notification);
        }
        
        foreach ($salesPeople as $sales) {
            $sales->notify($notification);
        }
    }
}
```

### FileController.php

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadFileRequest;
use App\Http\Resources\FileResource;
use App\Models\DesignRequest;
use App\Models\DesignRequestFile;
use App\Services\FileStorageService;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    public function __construct(
        private FileStorageService $fileStorage
    ) {}
    
    /**
     * Dosya yükle
     */
    public function upload(UploadFileRequest $request, DesignRequest $designRequest)
    {
        $customer = auth()->user();
        
        if ($designRequest->customer_id !== $customer->id) {
            return response()->json([
                'success' => false,
                'message' => 'Bu talebe erişim yetkiniz yok'
            ], 403);
        }
        
        $uploadedFiles = [];
        
        foreach ($request->file('files') as $file) {
            $storedPath = $this->fileStorage->store($file, $designRequest->id);
            
            $fileRecord = DesignRequestFile::create([
                'design_request_id' => $designRequest->id,
                'original_name' => $file->getClientOriginalName(),
                'stored_path' => $storedPath,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'type' => $request->type ?? 'attachment',
                'uploaded_by' => $customer->id
            ]);
            
            $uploadedFiles[] = new FileResource($fileRecord);
        }
        
        return response()->json([
            'success' => true,
            'message' => count($uploadedFiles) . ' dosya yüklendi',
            'data' => $uploadedFiles
        ], 201);
    }
    
    /**
     * Dosya indir
     */
    public function download(DesignRequestFile $file)
    {
        $customer = auth()->user();
        
        // Dosyanın sahibi mi kontrol et
        if ($file->designRequest->customer_id !== $customer->id) {
            return response()->json([
                'success' => false,
                'message' => 'Bu dosyaya erişim yetkiniz yok'
            ], 403);
        }
        
        if (!Storage::exists($file->stored_path)) {
            return response()->json([
                'success' => false,
                'message' => 'Dosya bulunamadı'
            ], 404);
        }
        
        return Storage::download($file->stored_path, $file->original_name);
    }
    
    /**
     * Dosya sil
     */
    public function destroy(DesignRequestFile $file)
    {
        $customer = auth()->user();
        
        if ($file->designRequest->customer_id !== $customer->id) {
            return response()->json([
                'success' => false,
                'message' => 'Bu dosyaya erişim yetkiniz yok'
            ], 403);
        }
        
        // Sadece pending durumundaki taleplerden dosya silinebilir
        if ($file->designRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Bu talepteki dosyalar silinemez'
            ], 422);
        }
        
        $this->fileStorage->delete($file->stored_path);
        $file->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Dosya silindi'
        ]);
    }
}
```

---

## 📦 Models

### Customer.php (Güncelleme)

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Customer extends Authenticatable implements JWTSubject
{
    use Notifiable;
    
    protected $fillable = [
        'name',
        'email',
        'portal_access',
        'portal_password',
        'portal_last_login',
        // ... diğer mevcut alanlar
    ];
    
    protected $hidden = [
        'portal_password',
        'remember_token',
    ];
    
    protected $casts = [
        'portal_access' => 'boolean',
        'portal_last_login' => 'datetime',
    ];
    
    // JWT Methods
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }
    
    public function getJWTCustomClaims()
    {
        return [
            'type' => 'customer',
            'name' => $this->name,
            'email' => $this->email
        ];
    }
    
    // Relationships
    public function designRequests()
    {
        return $this->hasMany(DesignRequest::class);
    }
}
```

### DesignRequest.php

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DesignRequest extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'customer_id',
        'title',
        'description',
        'priority',
        'status',
        'assigned_designer_id',
        'assigned_sales_id',
        'due_date',
        'notes',
    ];
    
    protected $casts = [
        'due_date' => 'date',
    ];
    
    // Relationships
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    
    public function files()
    {
        return $this->hasMany(DesignRequestFile::class);
    }
    
    public function comments()
    {
        return $this->hasMany(DesignRequestComment::class);
    }
    
    public function assignedDesigner()
    {
        return $this->belongsTo(User::class, 'assigned_designer_id');
    }
    
    public function assignedSales()
    {
        return $this->belongsTo(User::class, 'assigned_sales_id');
    }
}
```

---

## ✅ Validation Requests

### StoreDesignRequestRequest.php

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDesignRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:5000',
            'priority' => 'nullable|in:low,medium,high',
            'due_date' => 'nullable|date|after:today',
            'notes' => 'nullable|string|max:2000',
        ];
    }
    
    public function messages(): array
    {
        return [
            'title.required' => 'Başlık zorunludur',
            'title.max' => 'Başlık en fazla 255 karakter olabilir',
            'description.required' => 'Açıklama zorunludur',
            'due_date.after' => 'Teslim tarihi bugünden sonra olmalıdır',
        ];
    }
}
```

---

## 🔔 Notifications

### NewDesignRequestNotification.php

```php
<?php

namespace App\Notifications;

use App\Models\DesignRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewDesignRequestNotification extends Notification
{
    use Queueable;
    
    public function __construct(
        public DesignRequest $designRequest
    ) {}
    
    public function via($notifiable): array
    {
        return ['database'];
    }
    
    public function toArray($notifiable): array
    {
        return [
            'design_request_id' => $this->designRequest->id,
            'title' => $this->designRequest->title,
            'customer_name' => $this->designRequest->customer->name,
            'priority' => $this->designRequest->priority,
            'message' => "Yeni tasarım talebi: {$this->designRequest->title}"
        ];
    }
}
```

---

## 🧪 Test Edilecekler

1. **Health Check:** `GET /api/health` - API key gerektirmeden çalışmalı
2. **Login:** `POST /api/auth/login` - Geçerli/geçersiz credentials
3. **JWT Validation:** Token olmadan protected route'lara erişim engellenmeli
4. **Design Request CRUD:** Tüm CRUD operasyonları
5. **Authorization:** Müşteri sadece kendi taleplerini görebilmeli
6. **File Upload:** Dosya yükleme, boyut/tip kontrolü
7. **Rate Limiting:** Dakikada maksimum istek sayısı

---

## 🚀 Deployment Checklist

- [ ] Laravel projesi oluşturuldu
- [ ] JWT paketi kuruldu ve yapılandırıldı
- [ ] Migration'lar yazıldı ve çalıştırıldı
- [ ] Model'ler ve ilişkiler tanımlandı
- [ ] Middleware'ler oluşturuldu
- [ ] Controller'lar yazıldı
- [ ] Route'lar tanımlandı
- [ ] Validation kuralları eklendi
- [ ] .env dosyası yapılandırıldı
- [ ] CORS ayarları yapıldı
- [ ] Rate limiting aktif
- [ ] SSL sertifikası kuruldu
- [ ] Nginx yapılandırması tamamlandı

---

## 📝 Notlar

1. **Mevcut ERP Veritabanı:** API, mevcut ERP'nin veritabanına bağlanacak. `customers` ve `users` tabloları zaten mevcut, sadece gerekli alanlar eklenecek.

2. **JWT Guard:** `config/auth.php` dosyasında customer guard'ı JWT ile yapılandırılacak.

3. **Dosya Depolama:** Dosyalar `storage/app/design-files/{design_request_id}/` altında saklanacak.

4. **IP Whitelist:** Production'da sadece Hetzner sunucu IP'sinden gelen isteklere izin verilecek.

5. **Logging:** Tüm API istekleri loglanacak (başarılı ve başarısız).
