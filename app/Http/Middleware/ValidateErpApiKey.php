<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class ValidateErpApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-Erp-Api-Key');

        // API Key kontrolü (timing-safe comparison)
        $configApiKey = config('portal.erp_api_key');
        if (!$apiKey || !$configApiKey || !hash_equals($configApiKey, $apiKey)) {
            Log::warning('ERP API: Invalid API key attempt', [
                'ip' => $request->ip(),
                'endpoint' => $request->path()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid ERP API key'
            ], 401);
        }

        return $next($request);
    }
}
