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

class CheckSingleSession
{
    protected PortalAuthService $authService;

    public function __construct(PortalAuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Handle an incoming request.
     * Tek oturum kontrolü yapar - başka cihazdan giriş yapılmışsa oturumu sonlandırır.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('api')->user();

        if (!$user) {
            return $next($request);
        }

        // Session ID header'dan al
        $sessionId = $request->header('X-Session-ID');

        if (!$sessionId) {
            // Session ID yoksa devam et (geriye uyumluluk)
            return $next($request);
        }

        // Session geçerli mi kontrol et
        if (!$this->authService->isSessionValid($user, $sessionId)) {
            // Oturumu sonlandır
            Auth::guard('api')->logout();

            return response()->json([
                'success' => false,
                'error' => 'session_invalidated',
                'message' => 'Başka bir cihazdan giriş yapıldı. Oturumunuz sonlandırıldı.',
            ], 401);
        }

        return $next($request);
    }
}
