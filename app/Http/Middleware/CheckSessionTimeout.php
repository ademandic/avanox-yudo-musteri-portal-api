<?php

/**
 * YUDO Customer Portal API
 *
 * @author    Avanox Bilişim Yazılım ve Danışmanlık LTD ŞTİ
 * @copyright 2025 Avanox Bilişim
 * @license   Proprietary - All rights reserved
 */

namespace App\Http\Middleware;

use App\Services\PortalAuthService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckSessionTimeout
{
    protected PortalAuthService $authService;

    public function __construct(PortalAuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Handle an incoming request.
     * Session timeout (60 dk inaktivite) kontrolü yapar.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('api')->user();

        if (!$user) {
            return $next($request);
        }

        // Session timeout kontrolü
        if ($this->authService->isSessionTimedOut($user)) {
            // Oturumu sonlandır
            Auth::guard('api')->logout();

            return response()->json([
                'success' => false,
                'error' => 'session_timeout',
                'message' => 'Oturumunuz zaman aşımına uğradı. Lütfen tekrar giriş yapın.',
            ], 401);
        }

        // Aktiviteyi güncelle
        $this->authService->updateActivity($user);

        return $next($request);
    }
}
