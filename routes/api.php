<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\FileController;
use App\Http\Controllers\Api\InvitationController;
use App\Http\Controllers\Api\JobController;
use App\Http\Controllers\Api\LookupController;
use App\Http\Controllers\Api\RequestController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Portal API Routes
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| Public Health Check Routes (No Middleware)
|--------------------------------------------------------------------------
*/

// Health check - API key gerektirmez
Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
        'version' => '1.0.0',
        'environment' => config('app.env'),
    ]);
});

// Database bağlantı testi - API key gerektirmez (development için)
Route::get('/health/db', function () {
    try {
        \DB::connection()->getPdo();
        return response()->json([
            'success' => true,
            'database' => 'connected',
            'driver' => config('database.default'),
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'database' => 'disconnected',
            'error' => config('app.debug') ? $e->getMessage() : 'Connection failed',
        ], 500);
    }
});

/*
|--------------------------------------------------------------------------
| API Key Protected Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['portal.api-key', 'portal.log'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Public Routes (API Key required, JWT not required)
    |--------------------------------------------------------------------------
    */

    // Auth - Public (login, 2FA)
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/verify-2fa', [AuthController::class, 'verifyTwoFactor']);
        Route::post('/resend-2fa', [AuthController::class, 'resendTwoFactor']);
    });

    // Davetiye routes (kayıt olmadan erişilebilir)
    Route::prefix('invitations')->group(function () {
        Route::get('/{token}', [InvitationController::class, 'show']);
        Route::post('/{token}/accept', [InvitationController::class, 'accept']);
    });

    // Lookup routes (public - form dropdown'ları için)
    Route::prefix('lookups')->group(function () {
        Route::get('/all', [LookupController::class, 'all']);
        Route::get('/request-states', [LookupController::class, 'requestStates']);
        Route::get('/request-types', [LookupController::class, 'requestTypes']);
        Route::get('/priorities', [LookupController::class, 'priorities']);
        Route::get('/materials', [LookupController::class, 'materials']);
        Route::get('/additives', [LookupController::class, 'additives']);
        Route::get('/nozzle-types', [LookupController::class, 'nozzleTypes']);
    });

    // Dosya yükleme kuralları (public)
    Route::get('/files/upload-rules', [FileController::class, 'uploadRules']);

    /*
    |--------------------------------------------------------------------------
    | Protected Routes (API Key + JWT + Session Check)
    |--------------------------------------------------------------------------
    */
    Route::middleware([
        'auth:api',
        \App\Http\Middleware\CheckSessionTimeout::class,
        \App\Http\Middleware\CheckSingleSession::class,
    ])->group(function () {

        // Auth routes (authenticated)
        Route::prefix('auth')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::post('/refresh', [AuthController::class, 'refresh']);
            Route::get('/me', [AuthController::class, 'me']);
        });

        // Company - Kullanıcının firması
        Route::get('/company', [CompanyController::class, 'show']);

        // Jobs - Firmaya ait işler
        Route::prefix('jobs')->group(function () {
            Route::get('/', [JobController::class, 'index']);
            Route::get('/{jobNo}', [JobController::class, 'show']);
        });

        // Request (Talep) routes
        Route::prefix('requests')->group(function () {
            Route::get('/', [RequestController::class, 'index']);
            Route::post('/', [RequestController::class, 'store']);
            Route::get('/{id}', [RequestController::class, 'show'])->where('id', '[0-9]+');
            Route::put('/{id}', [RequestController::class, 'update'])->where('id', '[0-9]+');
            Route::post('/{id}/cancel', [RequestController::class, 'cancel'])->where('id', '[0-9]+');
            Route::get('/{id}/history', [RequestController::class, 'history'])->where('id', '[0-9]+');

            // Talep'e ait dosyalar
            Route::get('/{requestId}/files', [FileController::class, 'index'])->where('requestId', '[0-9]+');
            Route::post('/{requestId}/files', [FileController::class, 'store'])->where('requestId', '[0-9]+');
        });

        // File routes
        Route::prefix('files')->group(function () {
            Route::get('/{id}/download', [FileController::class, 'download'])
                ->where('id', '[0-9]+')
                ->name('files.download');
            Route::delete('/{id}', [FileController::class, 'destroy'])->where('id', '[0-9]+');
        });

        // User Management routes (Company Admin only)
        Route::prefix('users')->group(function () {
            Route::get('/', [UserController::class, 'index']);
            Route::post('/invite', [UserController::class, 'invite']);
            Route::post('/{id}/toggle-status', [UserController::class, 'toggleStatus'])->where('id', '[0-9]+');
            Route::delete('/{id}', [UserController::class, 'destroy'])->where('id', '[0-9]+');
            Route::delete('/invitations/{id}', [UserController::class, 'cancelInvitation'])->where('id', '[0-9]+');
        });

        // Settings routes
        Route::prefix('settings')->group(function () {
            Route::get('/', [SettingsController::class, 'index']);
            Route::put('/theme', [SettingsController::class, 'updateTheme']);
            Route::put('/language', [SettingsController::class, 'updateLanguage']);
            Route::put('/profile', [SettingsController::class, 'updateProfile']);
            Route::put('/password', [SettingsController::class, 'updatePassword']);
        });
    });
});
