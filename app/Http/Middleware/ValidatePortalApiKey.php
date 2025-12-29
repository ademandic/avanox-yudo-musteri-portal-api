<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class ValidatePortalApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-Portal-Api-Key');

        // API Key kontrolü
        if (!$apiKey || $apiKey !== config('portal.api_key')) {
            Log::warning('Portal API: Invalid API key attempt', [
                'ip' => $request->ip(),
                'endpoint' => $request->path()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid API key'
            ], 401);
        }

        // IP kontrolü (yapılandırılmışsa)
        $allowedIps = config('portal.allowed_ips');
        if (!empty($allowedIps) && !in_array($request->ip(), $allowedIps)) {
            Log::warning('Portal API: Unauthorized IP attempt', [
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
