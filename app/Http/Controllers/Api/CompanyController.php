<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CompanyResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class CompanyController extends Controller
{
    /**
     * Giriş yapan kullanıcının firma bilgileri
     * GET /api/company
     */
    public function show(): JsonResponse
    {
        $user = Auth::guard('api')->user();

        $company = $user->company()->with([
            'salesPerson',
            'contacts' => function ($query) {
                $query->active()->orderBy('name');
            }
        ])->first();

        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Firma bilgisi bulunamadı.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new CompanyResource($company),
        ]);
    }
}
