<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\JobResource;
use App\Http\Resources\JobCollection;
use App\Models\ErpFile;
use App\Models\Job;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JobController extends Controller
{
    /**
     * Firmaya ait işler listesi
     * GET /api/jobs
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::guard('api')->user();

        $query = Job::with(['technicalData', 'portalRequest.currentState', 'portalRequest.portalUser'])
            ->forCompany($user->company_id)
            ->orderBy('created_at', 'desc');

        // Arama filtresi
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('job_no', 'LIKE', "%{$search}%")
                  ->orWhere('mold_maker_ref_no', 'LIKE', "%{$search}%")
                  ->orWhere('yudo_id_no', 'LIKE', "%{$search}%")
                  ->orWhere('part_description', 'LIKE', "%{$search}%");
            });
        }

        // Portal talebi olan/olmayan filtresi
        if ($request->has('has_portal_request')) {
            $hasRequest = filter_var($request->input('has_portal_request'), FILTER_VALIDATE_BOOLEAN);
            if ($hasRequest) {
                $query->whereHas('portalRequest');
            } else {
                $query->whereDoesntHave('portalRequest');
            }
        }

        $jobs = $query->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => new JobCollection($jobs),
        ]);
    }

    /**
     * İş detayı (job_no ile)
     * GET /api/jobs/{jobNo}
     */
    public function show(string $jobNo): JsonResponse
    {
        $user = Auth::guard('api')->user();

        $job = Job::with([
            'technicalData',
            'files' => function ($query) {
                $query->whereIn('baglanti_tablo_adi', ErpFile::PORTAL_VISIBLE_TYPES)
                    ->active()
                    ->orderBy('created_at', 'desc');
            },
            'portalRequest.currentState',
            'portalRequest.portalUser',
            'portalRequest.stateLogs.state'
        ])
            ->forCompany($user->company_id)
            ->where('job_no', $jobNo)
            ->first();

        if (!$job) {
            return response()->json([
                'success' => false,
                'message' => 'İş bulunamadı.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new JobResource($job),
        ]);
    }

    /**
     * İş detayı (ID ile)
     * GET /api/jobs/by-id/{id}
     */
    public function showById(int $id): JsonResponse
    {
        $user = Auth::guard('api')->user();

        $job = Job::with([
            'technicalData.drawingStateLogs' => function ($query) {
                $query->whereIn('drawing_state_id', \App\Models\DrawingState::PORTAL_VISIBLE_STATES)
                    ->with('state')
                    ->orderBy('tarih_saat', 'desc');
            },
            'files' => function ($query) {
                $query->whereIn('baglanti_tablo_adi', ErpFile::PORTAL_VISIBLE_TYPES)
                    ->active()
                    ->orderBy('created_at', 'desc');
            },
            'stateLogs' => function ($query) {
                $query->with('state')
                    ->orderBy('created_at', 'desc');
            },
            'offers.stateLogs' => function ($query) {
                $query->whereIn('offer_states_id', \App\Models\OfferState::PORTAL_VISIBLE_STATES)
                    ->with('state')
                    ->orderBy('tarih_saat', 'desc');
            },
            'portalRequest.currentState',
            'portalRequest.portalUser',
            'portalRequest.stateLogs.state'
        ])
            ->forCompany($user->company_id)
            ->where('id', $id)
            ->first();

        if (!$job) {
            return response()->json([
                'success' => false,
                'message' => 'İş bulunamadı.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new JobResource($job),
        ]);
    }
}
