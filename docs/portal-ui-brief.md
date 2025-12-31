# Portal UI - API Entegrasyon Brief

## Amac

Mock data kullanan Portal UI'i gercek API'ye baglamak. Session-based authentication ile guvenli bir yapi kurmak.

---

## Mimari

```
+---------------------------------------------------------------------+
|                         PORTAL UI (Hetzner)                          |
|                         portal.yudo.com.tr                           |
+---------------------------------------------------------------------+
|                                                                      |
|  Browser --> Laravel Routes --> Controller --> API Service --> API   |
|                   |                                          |       |
|                   v                                          v       |
|              Blade View                              api.yudo.com.tr |
|              + Alpine.js                                             |
|                                                                      |
|  Session: [jwt_token, user_data, company_data]                       |
|                                                                      |
+---------------------------------------------------------------------+
```

---

## Olusturulacak Dosyalar

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Auth/
│   │   │   ├── LoginController.php
│   │   │   ├── LogoutController.php
│   │   │   └── InvitationController.php
│   │   ├── DashboardController.php
│   │   ├── RequestController.php
│   │   └── FileController.php
│   └── Middleware/
│       ├── PortalAuthenticate.php
│       └── RefreshTokenIfNeeded.php
├── Services/
│   ├── PortalApiService.php
│   └── AuthService.php
└── Exceptions/
    └── ApiException.php

config/
└── portal-api.php

resources/views/
├── auth/
│   ├── login.blade.php
│   └── invitation/
│       ├── accept.blade.php
│       └── expired.blade.php
├── layouts/
│   └── app.blade.php
├── dashboard.blade.php
├── requests/
│   ├── index.blade.php
│   ├── show.blade.php
│   └── create.blade.php
└── components/
    ├── alert.blade.php
    └── loading.blade.php

routes/
└── web.php
```

---

## Konfigurasyon

### config/portal-api.php

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API Base URL
    |--------------------------------------------------------------------------
    */
    'base_url' => env('PORTAL_API_URL', 'https://api.yudo.com.tr'),

    /*
    |--------------------------------------------------------------------------
    | API Key
    |--------------------------------------------------------------------------
    */
    'api_key' => env('PORTAL_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Timeout (seconds)
    |--------------------------------------------------------------------------
    */
    'timeout' => env('PORTAL_API_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | SSL Verify (production'da true olmali)
    |--------------------------------------------------------------------------
    */
    'verify_ssl' => env('PORTAL_API_VERIFY_SSL', true),

    /*
    |--------------------------------------------------------------------------
    | Session Keys
    |--------------------------------------------------------------------------
    */
    'session' => [
        'token' => 'portal_jwt_token',
        'token_expires' => 'portal_token_expires',
        'user' => 'portal_user',
        'company' => 'portal_company',
    ],
];
```

### .env Eklemeleri

```env
# Portal API
PORTAL_API_URL=https://api.yudo.com.tr
PORTAL_API_KEY=your-secure-api-key
PORTAL_API_TIMEOUT=30
PORTAL_API_VERIFY_SSL=false  # Self-signed cert icin, production'da true
```

---

## Services

### app/Services/PortalApiService.php

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use App\Exceptions\ApiException;

class PortalApiService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected int $timeout;
    protected bool $verifySSL;

    public function __construct()
    {
        $this->baseUrl = config('portal-api.base_url');
        $this->apiKey = config('portal-api.api_key');
        $this->timeout = config('portal-api.timeout');
        $this->verifySSL = config('portal-api.verify_ssl');
    }

    /**
     * HTTP Client olustur
     */
    protected function client(bool $withAuth = true)
    {
        $client = Http::baseUrl($this->baseUrl)
            ->timeout($this->timeout)
            ->withOptions(['verify' => $this->verifySSL])
            ->withHeaders([
                'X-Portal-Api-Key' => $this->apiKey,
                'Accept' => 'application/json',
            ]);

        if ($withAuth && $token = $this->getToken()) {
            $client = $client->withToken($token);
        }

        return $client;
    }

    /**
     * Session'dan JWT token al
     */
    public function getToken(): ?string
    {
        return Session::get(config('portal-api.session.token'));
    }

    /**
     * Token'i session'a kaydet
     *
     * @param string $token JWT token
     * @param int $expiresInMinutes Token suresi (dakika cinsinden)
     */
    public function setToken(string $token, int $expiresInMinutes = 1440): void
    {
        Session::put(config('portal-api.session.token'), $token);
        Session::put(config('portal-api.session.token_expires'), now()->addMinutes($expiresInMinutes));
    }

    /**
     * Session temizle
     */
    public function clearSession(): void
    {
        Session::forget([
            config('portal-api.session.token'),
            config('portal-api.session.token_expires'),
            config('portal-api.session.user'),
            config('portal-api.session.company'),
        ]);
    }

    /**
     * Token suresi dolmus mu?
     */
    public function isTokenExpired(): bool
    {
        $expires = Session::get(config('portal-api.session.token_expires'));

        if (!$expires) {
            return true;
        }

        // 5 dakika onceden expired say (refresh icin buffer)
        return now()->addMinutes(5)->greaterThan($expires);
    }

    /**
     * GET istegi
     */
    public function get(string $endpoint, array $params = [], bool $withAuth = true): array
    {
        return $this->request('GET', $endpoint, $params, $withAuth);
    }

    /**
     * POST istegi
     */
    public function post(string $endpoint, array $data = [], bool $withAuth = true): array
    {
        return $this->request('POST', $endpoint, $data, $withAuth);
    }

    /**
     * PUT istegi
     */
    public function put(string $endpoint, array $data = [], bool $withAuth = true): array
    {
        return $this->request('PUT', $endpoint, $data, $withAuth);
    }

    /**
     * DELETE istegi
     */
    public function delete(string $endpoint, bool $withAuth = true): array
    {
        return $this->request('DELETE', $endpoint, [], $withAuth);
    }

    /**
     * Dosya yukle
     *
     * NOT: API dosyalari 'files[]' formatiyla kabul ediyor (array)
     */
    public function uploadFile(string $endpoint, $file, array $data = []): array
    {
        try {
            $response = $this->client()
                ->attach('files[]', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
                ->post($endpoint, $data);

            return $this->handleResponse($response);
        } catch (\Exception $e) {
            throw new ApiException('Dosya yukleme hatasi: ' . $e->getMessage());
        }
    }

    /**
     * Dosya indir (raw response)
     */
    protected function downloadFileRaw(string $endpoint)
    {
        $response = $this->client()->get($endpoint);

        if ($response->failed()) {
            throw new ApiException('Dosya indirme hatasi');
        }

        return $response;
    }

    /**
     * Genel istek metodu
     */
    protected function request(string $method, string $endpoint, array $data = [], bool $withAuth = true): array
    {
        try {
            $client = $this->client($withAuth);

            $response = match ($method) {
                'GET' => $client->get($endpoint, $data),
                'POST' => $client->post($endpoint, $data),
                'PUT' => $client->put($endpoint, $data),
                'DELETE' => $client->delete($endpoint),
            };

            return $this->handleResponse($response);

        } catch (ApiException $e) {
            throw $e;
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new ApiException('API baglanti hatasi. Lutfen daha sonra tekrar deneyin.', 503);
        } catch (\Exception $e) {
            throw new ApiException('Beklenmeyen bir hata olustu: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Response isle
     */
    protected function handleResponse($response): array
    {
        $data = $response->json() ?? [];

        if ($response->successful()) {
            return $data;
        }

        // Hata durumlari
        $message = $data['message'] ?? 'API hatasi';
        $status = $response->status();

        match ($status) {
            401 => throw new ApiException('Oturum suresi doldu. Lutfen tekrar giris yapin.', 401),
            403 => throw new ApiException('Bu islem icin yetkiniz bulunmuyor.', 403),
            404 => throw new ApiException('Kayit bulunamadi.', 404),
            422 => throw new ApiException($message, 422, $data['errors'] ?? []),
            429 => throw new ApiException('Cok fazla istek gonderildi. Lutfen bekleyin.', 429),
            500 => throw new ApiException('Sunucu hatasi. Lutfen daha sonra tekrar deneyin.', 500),
            default => throw new ApiException($message, $status),
        };
    }

    // =========================================================================
    // AUTH ENDPOINTS
    // =========================================================================

    public function login(string $email, string $password): array
    {
        return $this->post('/api/auth/login', [
            'email' => $email,
            'password' => $password,
        ], false);
    }

    public function logout(): array
    {
        return $this->post('/api/auth/logout');
    }

    public function refreshToken(): array
    {
        return $this->post('/api/auth/refresh');
    }

    public function me(): array
    {
        return $this->get('/api/auth/me');
    }

    // =========================================================================
    // INVITATION ENDPOINTS
    // =========================================================================

    public function getInvitation(string $token): array
    {
        return $this->get("/api/invitations/{$token}", [], false);
    }

    public function acceptInvitation(string $token, string $password, string $passwordConfirmation): array
    {
        return $this->post("/api/invitations/{$token}/accept", [
            'password' => $password,
            'password_confirmation' => $passwordConfirmation,
        ], false);
    }

    // =========================================================================
    // COMPANY ENDPOINTS
    // =========================================================================

    public function getCompany(): array
    {
        return $this->get('/api/company');
    }

    // =========================================================================
    // JOBS ENDPOINTS
    // =========================================================================

    public function getJobs(array $params = []): array
    {
        return $this->get('/api/jobs', $params);
    }

    public function getJob(string $jobNo): array
    {
        return $this->get("/api/jobs/{$jobNo}");
    }

    // =========================================================================
    // REQUESTS ENDPOINTS
    // =========================================================================

    public function getRequests(array $params = []): array
    {
        return $this->get('/api/requests', $params);
    }

    public function getRequest(int $id): array
    {
        return $this->get("/api/requests/{$id}");
    }

    public function createRequest(array $data): array
    {
        return $this->post('/api/requests', $data);
    }

    public function updateRequest(int $id, array $data): array
    {
        return $this->put("/api/requests/{$id}", $data);
    }

    /**
     * Talep iptal et
     *
     * NOT: API reason parametresi kabul etmiyor.
     * Iptal sebebi otomatik olarak "Talep musteri tarafindan iptal edildi." olarak kaydedilir.
     */
    public function cancelRequest(int $id): array
    {
        return $this->post("/api/requests/{$id}/cancel");
    }

    public function getRequestHistory(int $id): array
    {
        return $this->get("/api/requests/{$id}/history");
    }

    // =========================================================================
    // FILES ENDPOINTS
    // =========================================================================

    public function getRequestFiles(int $requestId): array
    {
        return $this->get("/api/requests/{$requestId}/files");
    }

    /**
     * Talebe dosya yukle
     *
     * @param int $requestId Talep ID
     * @param mixed $file Yuklenecek dosya (UploadedFile)
     * @param string|null $description Dosya aciklamasi (opsiyonel)
     */
    public function uploadRequestFile(int $requestId, $file, string $description = null): array
    {
        $data = [];
        if ($description) {
            $data['description'] = $description;
        }

        return $this->uploadFile("/api/requests/{$requestId}/files", $file, $data);
    }

    public function downloadFile(int $fileId)
    {
        return $this->downloadFileRaw("/api/files/{$fileId}/download");
    }

    public function deleteFile(int $fileId): array
    {
        return $this->delete("/api/files/{$fileId}");
    }

    /**
     * Dosya yukleme kurallarini getir (public endpoint)
     */
    public function getUploadRules(): array
    {
        return $this->get('/api/files/upload-rules', [], false);
    }

    // =========================================================================
    // LOOKUP ENDPOINTS
    // =========================================================================

    public function getAllLookups(): array
    {
        return $this->get('/api/lookups/all', [], false);
    }

    public function getRequestStates(): array
    {
        return $this->get('/api/lookups/request-states', [], false);
    }

    public function getMaterials(): array
    {
        return $this->get('/api/lookups/materials', [], false);
    }

    public function getAdditives(): array
    {
        return $this->get('/api/lookups/additives', [], false);
    }

    public function getPriorities(): array
    {
        return $this->get('/api/lookups/priorities', [], false);
    }

    public function getRequestTypes(): array
    {
        return $this->get('/api/lookups/request-types', [], false);
    }

    public function getNozzleTypes(): array
    {
        return $this->get('/api/lookups/nozzle-types', [], false);
    }
}
```

### app/Services/AuthService.php

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Session;
use App\Exceptions\ApiException;

class AuthService
{
    protected PortalApiService $api;

    public function __construct(PortalApiService $api)
    {
        $this->api = $api;
    }

    /**
     * Giris yap
     */
    public function login(string $email, string $password): bool
    {
        try {
            $response = $this->api->login($email, $password);

            // Token'i kaydet
            $token = $response['data']['access_token'] ?? null;
            $expiresInSeconds = $response['data']['expires_in'] ?? 86400;

            if (!$token) {
                throw new ApiException('Token alinamadi');
            }

            // API saniye donduruyor, dakikaya cevir
            $expiresInMinutes = (int) ceil($expiresInSeconds / 60);
            $this->api->setToken($token, $expiresInMinutes);

            // Kullanici ve firma bilgilerini al ve kaydet
            $this->loadUserData();

            return true;

        } catch (ApiException $e) {
            throw $e;
        }
    }

    /**
     * Cikis yap
     */
    public function logout(): void
    {
        try {
            $this->api->logout();
        } catch (\Exception $e) {
            // API hatasi olsa bile session'i temizle
        }

        $this->api->clearSession();
    }

    /**
     * Token yenile
     */
    public function refreshToken(): bool
    {
        try {
            $response = $this->api->refreshToken();

            $token = $response['data']['access_token'] ?? null;
            $expiresInSeconds = $response['data']['expires_in'] ?? 86400;

            if ($token) {
                // API saniye donduruyor, dakikaya cevir
                $expiresInMinutes = (int) ceil($expiresInSeconds / 60);
                $this->api->setToken($token, $expiresInMinutes);
                return true;
            }

            return false;

        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Kullanici giris yapmis mi?
     */
    public function check(): bool
    {
        return $this->api->getToken() !== null;
    }

    /**
     * Kullanici bilgilerini session'a yukle
     */
    public function loadUserData(): void
    {
        try {
            // Kullanici bilgisi
            $userResponse = $this->api->me();
            $user = $userResponse['data'] ?? $userResponse;
            Session::put(config('portal-api.session.user'), $user);

            // Firma bilgisi
            $companyResponse = $this->api->getCompany();
            $company = $companyResponse['data'] ?? $companyResponse;
            Session::put(config('portal-api.session.company'), $company);

        } catch (\Exception $e) {
            // Hata olursa devam et
        }
    }

    /**
     * Session'daki kullanici bilgisi
     */
    public function user(): ?array
    {
        return Session::get(config('portal-api.session.user'));
    }

    /**
     * Session'daki firma bilgisi
     */
    public function company(): ?array
    {
        return Session::get(config('portal-api.session.company'));
    }
}
```

### app/Exceptions/ApiException.php

```php
<?php

namespace App\Exceptions;

use Exception;

class ApiException extends Exception
{
    protected array $errors;

    public function __construct(string $message = '', int $code = 0, array $errors = [])
    {
        parent::__construct($message, $code);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function isUnauthorized(): bool
    {
        return $this->code === 401;
    }

    public function isValidationError(): bool
    {
        return $this->code === 422;
    }
}
```

---

## Middleware

### app/Http/Middleware/PortalAuthenticate.php

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\AuthService;

class PortalAuthenticate
{
    protected AuthService $auth;

    public function __construct(AuthService $auth)
    {
        $this->auth = $auth;
    }

    public function handle(Request $request, Closure $next)
    {
        if (!$this->auth->check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }

            return redirect()->route('login')->with('error', 'Lutfen giris yapin.');
        }

        // View'lara user ve company bilgisini paylas
        view()->share('currentUser', $this->auth->user());
        view()->share('currentCompany', $this->auth->company());

        return $next($request);
    }
}
```

### app/Http/Middleware/RefreshTokenIfNeeded.php

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\PortalApiService;
use App\Services\AuthService;

class RefreshTokenIfNeeded
{
    protected PortalApiService $api;
    protected AuthService $auth;

    public function __construct(PortalApiService $api, AuthService $auth)
    {
        $this->api = $api;
        $this->auth = $auth;
    }

    public function handle(Request $request, Closure $next)
    {
        // Token var ve suresi dolmak uzere ise yenile
        if ($this->auth->check() && $this->api->isTokenExpired()) {
            if (!$this->auth->refreshToken()) {
                // Yenileme basarisiz, logout yap
                $this->auth->logout();

                return redirect()->route('login')
                    ->with('error', 'Oturum suresi doldu. Lutfen tekrar giris yapin.');
            }
        }

        return $next($request);
    }
}
```

### bootstrap/app.php Middleware Kayit

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'portal.auth' => \App\Http\Middleware\PortalAuthenticate::class,
        'portal.refresh' => \App\Http\Middleware\RefreshTokenIfNeeded::class,
    ]);
})
```

---

## Controllers

### app/Http/Controllers/Auth/LoginController.php

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use App\Exceptions\ApiException;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    protected AuthService $auth;

    public function __construct(AuthService $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Login sayfasi
     */
    public function showLoginForm()
    {
        if ($this->auth->check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    /**
     * Login islemi
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
        ], [
            'email.required' => 'E-posta adresi gereklidir.',
            'email.email' => 'Gecerli bir e-posta adresi girin.',
            'password.required' => 'Sifre gereklidir.',
            'password.min' => 'Sifre en az 6 karakter olmalidir.',
        ]);

        try {
            $this->auth->login($request->email, $request->password);

            return redirect()->intended(route('dashboard'))
                ->with('success', 'Hos geldiniz!');

        } catch (ApiException $e) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => $e->getMessage()]);
        }
    }
}
```

### app/Http/Controllers/Auth/LogoutController.php

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuthService;

class LogoutController extends Controller
{
    protected AuthService $auth;

    public function __construct(AuthService $auth)
    {
        $this->auth = $auth;
    }

    public function logout()
    {
        $this->auth->logout();

        return redirect()->route('login')
            ->with('success', 'Basariyla cikis yaptiniz.');
    }
}
```

### app/Http/Controllers/Auth/InvitationController.php

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\PortalApiService;
use App\Services\AuthService;
use App\Exceptions\ApiException;
use Illuminate\Http\Request;

class InvitationController extends Controller
{
    protected PortalApiService $api;
    protected AuthService $auth;

    public function __construct(PortalApiService $api, AuthService $auth)
    {
        $this->api = $api;
        $this->auth = $auth;
    }

    /**
     * Davetiye sayfasi
     */
    public function show(string $token)
    {
        try {
            $response = $this->api->getInvitation($token);
            $invitation = $response['data'] ?? $response;

            return view('auth.invitation.accept', compact('invitation', 'token'));

        } catch (ApiException $e) {
            if ($e->getCode() === 404 || $e->getCode() === 410) {
                return view('auth.invitation.expired');
            }

            return redirect()->route('login')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Davetiyeyi kabul et
     */
    public function accept(Request $request, string $token)
    {
        $request->validate([
            'password' => 'required|min:8|confirmed',
        ], [
            'password.required' => 'Sifre gereklidir.',
            'password.min' => 'Sifre en az 8 karakter olmalidir.',
            'password.confirmed' => 'Sifreler eslesmiyor.',
        ]);

        try {
            $response = $this->api->acceptInvitation(
                $token,
                $request->password,
                $request->password_confirmation
            );

            // Otomatik login yap
            $loginToken = $response['data']['access_token'] ?? null;
            $expiresInSeconds = $response['data']['expires_in'] ?? 86400;

            if ($loginToken) {
                // API saniye donduruyor, dakikaya cevir
                $expiresInMinutes = (int) ceil($expiresInSeconds / 60);
                $this->api->setToken($loginToken, $expiresInMinutes);
                $this->auth->loadUserData();

                return redirect()->route('dashboard')
                    ->with('success', 'Hesabiniz olusturuldu. Hos geldiniz!');
            }

            return redirect()->route('login')
                ->with('success', 'Hesabiniz olusturuldu. Giris yapabilirsiniz.');

        } catch (ApiException $e) {
            if ($e->isValidationError()) {
                return back()->withErrors($e->getErrors());
            }

            return back()->with('error', $e->getMessage());
        }
    }
}
```

### app/Http/Controllers/DashboardController.php

```php
<?php

namespace App\Http\Controllers;

use App\Services\PortalApiService;
use App\Exceptions\ApiException;

class DashboardController extends Controller
{
    protected PortalApiService $api;

    public function __construct(PortalApiService $api)
    {
        $this->api = $api;
    }

    public function index()
    {
        try {
            // Son talepler
            $requestsResponse = $this->api->getRequests(['per_page' => 5]);
            $requests = $requestsResponse['data'] ?? [];

            // Lookup'lar (durumlar)
            $statesResponse = $this->api->getRequestStates();
            $states = $statesResponse['data'] ?? [];

            return view('dashboard', compact('requests', 'states'));

        } catch (ApiException $e) {
            return view('dashboard', [
                'requests' => [],
                'states' => [],
                'error' => $e->getMessage(),
            ]);
        }
    }
}
```

### app/Http/Controllers/RequestController.php

```php
<?php

namespace App\Http\Controllers;

use App\Services\PortalApiService;
use App\Exceptions\ApiException;
use Illuminate\Http\Request;

class RequestController extends Controller
{
    protected PortalApiService $api;

    public function __construct(PortalApiService $api)
    {
        $this->api = $api;
    }

    /**
     * Talep listesi
     */
    public function index(Request $request)
    {
        try {
            $params = $request->only(['status', 'type', 'search', 'per_page', 'page']);
            $response = $this->api->getRequests($params);

            $requests = $response['data'] ?? [];
            $pagination = $response['meta'] ?? null;

            // Filtre icin lookup'lar
            $states = $this->api->getRequestStates()['data'] ?? [];
            $types = $this->api->getRequestTypes()['data'] ?? [];

            return view('requests.index', compact('requests', 'pagination', 'states', 'types'));

        } catch (ApiException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Talep detayi
     */
    public function show(int $id)
    {
        try {
            $response = $this->api->getRequest($id);
            $portalRequest = $response['data'] ?? $response;

            $history = $this->api->getRequestHistory($id)['data'] ?? [];
            $files = $this->api->getRequestFiles($id)['data'] ?? [];

            return view('requests.show', compact('portalRequest', 'history', 'files'));

        } catch (ApiException $e) {
            return redirect()->route('requests.index')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Yeni talep formu
     */
    public function create()
    {
        try {
            $lookups = $this->api->getAllLookups()['data'] ?? [];

            return view('requests.create', compact('lookups'));

        } catch (ApiException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Talep olustur
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'request_type' => 'required|in:1,2',
            'customer_reference_code' => 'nullable|string|max:100',
            'customer_mold_code' => 'nullable|string|max:100',
            'customer_notes' => 'nullable|string',
            'expected_delivery_date' => 'nullable|date',
            'priority' => 'required|in:1,2,3,4',
            'parca_agirligi' => 'nullable|numeric',
            'et_kalinligi' => 'nullable|numeric',
            'malzeme' => 'nullable|string',
            'katki_var_mi' => 'nullable|boolean',
            'katki_turu' => 'nullable|string',
            'katki_orani' => 'nullable|numeric',
            'kalip_x' => 'nullable|numeric',
            'kalip_y' => 'nullable|numeric',
            'kalip_z' => 'nullable|numeric',
            'kalip_d' => 'nullable|numeric',
            'kalip_l' => 'nullable|numeric',
            'goz_sayisi' => 'nullable|integer',
            'meme_sayisi' => 'nullable|integer',
            'meme_tipi' => 'nullable|string',
        ]);

        try {
            $response = $this->api->createRequest($validated);
            $newRequest = $response['data'] ?? $response;

            return redirect()->route('requests.show', $newRequest['id'])
                ->with('success', 'Talep basariyla olusturuldu.');

        } catch (ApiException $e) {
            if ($e->isValidationError()) {
                return back()->withInput()->withErrors($e->getErrors());
            }

            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Talep guncelle
     */
    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'customer_notes' => 'nullable|string',
            'expected_delivery_date' => 'nullable|date',
            'priority' => 'nullable|in:1,2,3,4',
        ]);

        try {
            $this->api->updateRequest($id, $validated);

            return redirect()->route('requests.show', $id)
                ->with('success', 'Talep guncellendi.');

        } catch (ApiException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Talep iptal et
     *
     * NOT: API reason parametresi kabul etmiyor.
     * Iptal sebebi otomatik olarak kaydedilir.
     */
    public function cancel(int $id)
    {
        try {
            $this->api->cancelRequest($id);

            return redirect()->route('requests.show', $id)
                ->with('success', 'Talep iptal edildi.');

        } catch (ApiException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
```

### app/Http/Controllers/FileController.php

```php
<?php

namespace App\Http\Controllers;

use App\Services\PortalApiService;
use App\Exceptions\ApiException;
use Illuminate\Http\Request;

class FileController extends Controller
{
    protected PortalApiService $api;

    public function __construct(PortalApiService $api)
    {
        $this->api = $api;
    }

    /**
     * Dosya yukle
     */
    public function store(Request $request, int $requestId)
    {
        $request->validate([
            'file' => 'required|file|max:51200', // 50MB
            'description' => 'nullable|string|max:500',
        ], [
            'file.required' => 'Dosya secmelisiniz.',
            'file.max' => 'Dosya boyutu en fazla 50MB olabilir.',
        ]);

        try {
            $this->api->uploadRequestFile(
                $requestId,
                $request->file('file'),
                $request->description
            );

            return back()->with('success', 'Dosya yuklendi.');

        } catch (ApiException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Dosya indir
     */
    public function download(int $id)
    {
        try {
            $response = $this->api->downloadFile($id);

            return response($response->body())
                ->header('Content-Type', $response->header('Content-Type'))
                ->header('Content-Disposition', $response->header('Content-Disposition'));

        } catch (ApiException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Dosya sil
     */
    public function destroy(int $id)
    {
        try {
            $this->api->deleteFile($id);

            return back()->with('success', 'Dosya silindi.');

        } catch (ApiException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
```

---

## Routes

### routes/web.php

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\InvitationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\FileController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

// Login
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:5,1');

// Davetiye (public)
Route::get('/invitation/{token}', [InvitationController::class, 'show'])->name('invitation.show');
Route::post('/invitation/{token}', [InvitationController::class, 'accept'])->name('invitation.accept');

/*
|--------------------------------------------------------------------------
| Protected Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['portal.auth', 'portal.refresh'])->group(function () {
    // Logout
    Route::post('/logout', [LogoutController::class, 'logout'])->name('logout');

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Talepler
    Route::get('/requests', [RequestController::class, 'index'])->name('requests.index');
    Route::get('/requests/create', [RequestController::class, 'create'])->name('requests.create');
    Route::post('/requests', [RequestController::class, 'store'])->name('requests.store');
    Route::get('/requests/{id}', [RequestController::class, 'show'])->name('requests.show');
    Route::put('/requests/{id}', [RequestController::class, 'update'])->name('requests.update');
    Route::post('/requests/{id}/cancel', [RequestController::class, 'cancel'])->name('requests.cancel');

    // Dosyalar
    Route::post('/requests/{requestId}/files', [FileController::class, 'store'])->name('files.store');
    Route::get('/files/{id}/download', [FileController::class, 'download'])->name('files.download');
    Route::delete('/files/{id}', [FileController::class, 'destroy'])->name('files.destroy');
});
```

---

## Views

### resources/views/auth/login.blade.php

```blade
@extends('layouts.guest')

@section('title', 'Giris Yap')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Musteri Portali
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Hesabiniza giris yapin
            </p>
        </div>

        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                {{ session('error') }}
            </div>
        @endif

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                {{ session('success') }}
            </div>
        @endif

        <form class="mt-8 space-y-6" action="{{ route('login') }}" method="POST">
            @csrf

            <div class="rounded-md shadow-sm -space-y-px">
                <div>
                    <label for="email" class="sr-only">E-posta</label>
                    <input id="email" name="email" type="email" required
                           class="appearance-none rounded-none relative block w-full px-3 py-2 border
                                  border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md
                                  focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm
                                  @error('email') border-red-500 @enderror"
                           placeholder="E-posta adresi"
                           value="{{ old('email') }}">
                </div>
                <div>
                    <label for="password" class="sr-only">Sifre</label>
                    <input id="password" name="password" type="password" required
                           class="appearance-none rounded-none relative block w-full px-3 py-2 border
                                  border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md
                                  focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                           placeholder="Sifre">
                </div>
            </div>

            @error('email')
                <p class="text-red-500 text-sm">{{ $message }}</p>
            @enderror

            <div>
                <button type="submit"
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent
                               text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700
                               focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Giris Yap
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
```

### resources/views/auth/invitation/accept.blade.php

```blade
@extends('layouts.guest')

@section('title', 'Davetiyeyi Kabul Et')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Hos Geldiniz!
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Hesabinizi olusturmak icin sifrenizi belirleyin
            </p>
        </div>

        <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
            <div class="text-sm text-blue-700">
                <p><strong>Firma:</strong> {{ $invitation['company']['name'] ?? '-' }}</p>
                <p><strong>E-posta:</strong> {{ $invitation['email'] ?? '-' }}</p>
            </div>
        </div>

        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                {{ session('error') }}
            </div>
        @endif

        <form class="mt-8 space-y-6" action="{{ route('invitation.accept', $token) }}" method="POST">
            @csrf

            <div class="space-y-4">
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Sifre</label>
                    <input id="password" name="password" type="password" required
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm
                                  focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm
                                  @error('password') border-red-500 @enderror"
                           placeholder="En az 8 karakter">
                    @error('password')
                        <p class="mt-1 text-red-500 text-sm">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Sifre Tekrar</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm
                                  focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                           placeholder="Sifrenizi tekrar girin">
                </div>
            </div>

            <div>
                <button type="submit"
                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md
                               shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700
                               focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Hesabi Olustur
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
```

### resources/views/auth/invitation/expired.blade.php

```blade
@extends('layouts.guest')

@section('title', 'Davetiye Gecersiz')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full text-center">
        <div class="text-red-500 mb-4">
            <svg class="mx-auto h-16 w-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        </div>
        <h2 class="text-2xl font-bold text-gray-900 mb-2">Davetiye Gecersiz</h2>
        <p class="text-gray-600 mb-6">
            Bu davetiye suresi dolmus veya daha once kullanilmis olabilir.
            Lutfen firmanizla iletisime gecin.
        </p>
        <a href="{{ route('login') }}" class="text-blue-600 hover:text-blue-500">
            Giris sayfasina don
        </a>
    </div>
</div>
@endsection
```

---

## Guvenlik Onlemleri

### 1. CSRF Korumasi (Laravel'de Varsayilan)

```blade
<form method="POST">
    @csrf
    ...
</form>
```

### 2. Rate Limiting

Login route'unda throttle middleware kullaniliyor:

```php
Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:5,1');
```

### 3. XSS Korumasi

Blade'de `{{ }}` kullanildiginda otomatik escape edilir:

```blade
{{-- GUVENLI - Escape edilir --}}
{{ $user->name }}

{{-- TEHLIKELI - Escape edilmez, sadece guvenli HTML icin kullan --}}
{!! $trustedHtml !!}
```

### 4. Session Guvenligi (.env)

```env
SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true  # Production'da true
SESSION_HTTP_ONLY=true
```

---

## Kurulum Adimlari

```bash
# 1. Config dosyasini olustur
php artisan make:config portal-api

# 2. .env'e degiskenleri ekle

# 3. Middleware kaydet (bootstrap/app.php)

# 4. Cache temizle
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# 5. Test et
php artisan serve
```

---

## Test Kontrol Listesi

```
[ ] Login sayfasi aciliyor
[ ] Yanlis sifre ile hata mesaji gosteriyor
[ ] Dogru sifre ile dashboard'a yonlendiriyor
[ ] Session'da token kaydediliyor
[ ] Protected sayfalara erisim calisiyor
[ ] Logout calisiyor
[ ] Davetiye sayfasi aciliyor
[ ] Davetiye kabul islemi calisiyor
[ ] Talep listesi yukleniyor
[ ] Talep olusturma calisiyor
[ ] Dosya yukleme calisiyor
[ ] API hatalarinda kullaniciya mesaj gosteriliyor
[ ] Token suresi dolunca otomatik refresh calisiyor
```

---

## API Referans Dokumani

Detayli API endpoint dokumantasyonu icin: [docs/portal.md](./portal.md)

---

## Yapilan Duzeltmeler (v2)

1. **`uploadRequestFile` metodu**: `aciklama` parametresi `description` olarak duzeltildi
2. **`InvitationController::accept`**: Token expiry degeri (`expires_in`) artik dogru sekilde hesaplaniyor
3. **`cancelRequest` metodu**: Gereksiz `reason` parametresi kaldirildi
4. **`RequestController::cancel`**: Gereksiz validation kaldirildi
5. **Token expiry hesaplamasi**: `ceil()` kullanilarak ust yuvarlama yapildi (1 saniye bile kalsa 1 dakika olarak sayilir)
