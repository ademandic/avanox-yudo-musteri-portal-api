<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PortalRequestState;
use Illuminate\Http\JsonResponse;

class LookupController extends Controller
{
    /**
     * Talep durumları
     * GET /api/lookups/request-states
     */
    public function requestStates(): JsonResponse
    {
        $states = PortalRequestState::active()
            ->ordered()
            ->get(['id', 'name', 'english_name', 'color_class']);

        return response()->json([
            'success' => true,
            'data' => $states,
        ]);
    }

    /**
     * Talep tipleri
     * GET /api/lookups/request-types
     */
    public function requestTypes(): JsonResponse
    {
        $types = collect(config('portal.request_types'))->map(function ($label, $id) {
            return ['id' => $id, 'name' => $label];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $types,
        ]);
    }

    /**
     * Öncelik seviyeleri
     * GET /api/lookups/priorities
     */
    public function priorities(): JsonResponse
    {
        $priorities = collect(config('portal.priorities'))->map(function ($label, $id) {
            return ['id' => $id, 'name' => $label];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $priorities,
        ]);
    }

    /**
     * Malzeme listesi
     * GET /api/lookups/materials
     */
    public function materials(): JsonResponse
    {
        $materials = collect(config('portal.materials'))->map(function ($label, $code) {
            return ['code' => $code, 'name' => $label];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $materials,
        ]);
    }

    /**
     * Katkı türleri
     * GET /api/lookups/additives
     */
    public function additives(): JsonResponse
    {
        $additives = collect(config('portal.additives'))->map(function ($label, $code) {
            return ['code' => $code, 'name' => $label];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $additives,
        ]);
    }

    /**
     * Meme tipleri
     * GET /api/lookups/nozzle-types
     */
    public function nozzleTypes(): JsonResponse
    {
        $types = collect(config('portal.nozzle_types'))->map(function ($label, $code) {
            return ['code' => $code, 'name' => $label];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $types,
        ]);
    }

    /**
     * Tüm lookup verileri (tek istekte)
     * GET /api/lookups/all
     */
    public function all(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'request_states' => PortalRequestState::active()->ordered()
                    ->get(['id', 'name', 'english_name', 'color_class']),
                'request_types' => collect(config('portal.request_types'))
                    ->map(fn($label, $id) => ['id' => $id, 'name' => $label])->values(),
                'priorities' => collect(config('portal.priorities'))
                    ->map(fn($label, $id) => ['id' => $id, 'name' => $label])->values(),
                'materials' => collect(config('portal.materials'))
                    ->map(fn($label, $code) => ['code' => $code, 'name' => $label])->values(),
                'additives' => collect(config('portal.additives'))
                    ->map(fn($label, $code) => ['code' => $code, 'name' => $label])->values(),
                'nozzle_types' => collect(config('portal.nozzle_types'))
                    ->map(fn($label, $code) => ['code' => $code, 'name' => $label])->values(),
            ],
        ]);
    }
}
