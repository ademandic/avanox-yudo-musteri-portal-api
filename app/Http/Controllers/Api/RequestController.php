<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\StoreRequestRequest;
use App\Http\Requests\Portal\UpdateRequestRequest;
use App\Http\Resources\RequestResource;
use App\Http\Resources\RequestCollection;
use App\Http\Resources\StateLogResource;
use App\Models\ErpFile;
use App\Models\Job;
use App\Models\PortalRequest;
use App\Models\PortalRequestState;
use App\Models\PortalRequestStateLog;
use App\Models\TechnicalData;
use App\Services\FileStorageService;
use App\Services\JobNumberService;
use App\Services\RequestNumberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RequestController extends Controller
{
    public function __construct(
        protected JobNumberService $jobNumberService,
        protected RequestNumberService $requestNumberService,
        protected FileStorageService $fileStorageService
    ) {}

    /**
     * Talep listesi
     * GET /api/requests
     */
    public function index(): JsonResponse
    {
        $user = Auth::guard('api')->user();

        $requests = PortalRequest::with(['job', 'currentState', 'portalUser.contact'])
            ->forCompany($user->company_id)
            ->active()
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => new RequestCollection($requests),
        ]);
    }

    /**
     * Yeni talep oluştur
     * POST /api/requests
     */
    public function store(StoreRequestRequest $request): JsonResponse
    {
        $user = Auth::guard('api')->user();

        try {
            $result = DB::transaction(function () use ($request, $user) {
                // 1. Job oluştur
                $jobNo = $this->jobNumberService->generate();
                // Company tipine göre ilgili alanları belirle
                $company = $user->company;
                $jobData = [
                    'job_no' => $jobNo,
                    'job_category_id' => 1, // System Sales
                    'user_id' => $user->id, // Talebi oluşturan kullanıcı
                    'aciklama' => "Portal üzerinden oluşturuldu.",
                    'is_active' => 2, // 2 = aktif (ERP'de 1 = deaktif)
                    'source' => Job::SOURCE_PORTAL,
                    // Her durumda final_customer doldurulur
                    'final_customer_id' => $user->company_id,
                    'final_customer_contact_id' => $user->contact_id,
                    'final_customer_ref_no' => $request->customer_reference_code,
                ];

                // is_molder = 1 ise molder alanlarını doldur
                if ($company->is_molder == 1) {
                    $jobData['molder_id'] = $user->company_id;
                    $jobData['molder_contact_id'] = $user->contact_id;
                    $jobData['molder_ref_no'] = $request->customer_reference_code;
                }

                // is_mold_maker = 1 ise mold_maker alanlarını doldur
                if ($company->is_mold_maker == 1) {
                    $jobData['mold_maker_id'] = $user->company_id;
                    $jobData['mold_maker_contact_id'] = $user->contact_id;
                    $jobData['mold_maker_ref_no'] = $request->customer_reference_code;
                }

                $job = Job::create($jobData);

                // 2. Ana Sistem TechnicalData (page = 1)
                $openValveValue = null;
                if ($request->sistem_tipi === 'valvegate') {
                    $openValveValue = 'valve';
                } elseif ($request->sistem_tipi === 'open_end') {
                    $openValveValue = 'open';
                }

                $technicalData = TechnicalData::create([
                    'job_id' => $job->id,
                    'page' => 1,
                    'teknik_data_tipi' => 0, // Sıcak Yolluk Sistem Satışı
                    'parca_agirligi' => $request->parca_agirligi,
                    'et_kalinligi' => $request->et_kalinligi,
                    'malzeme' => $request->malzeme,
                    'malzeme_katki' => $request->katki_var_mi ? $request->katki_turu : null,
                    'malzeme_katki_yuzdesi' => $request->katki_orani,
                    'kalip_x' => $request->kalip_x,
                    'kalip_y' => $request->kalip_y,
                    'kalip_d' => $request->kalip_d,
                    'kalip_e' => $request->kalip_l,
                    'kalip_parca_sayisi' => $request->goz_sayisi,
                    'meme_sayisi' => $request->meme_sayisi,
                    'tip_sekli' => $request->meme_tipi,
                    'open_valve' => $openValveValue,
                    'is_active' => 2,
                ]);

                $nextPage = 2;

                // 3. Kontrol Cihazı TechnicalData (page = 2) - eğer talep edildiyse
                if ($request->kontrol_cihazi_var_mi) {
                    TechnicalData::create([
                        'job_id' => $job->id,
                        'page' => $nextPage,
                        'teknik_data_tipi' => 0,
                        'cihaz_tipi' => $request->cihaz_tipi,
                        'cihaz_bolg_sayisi' => $request->bolge_sayisi,
                        'soket_tipi' => $request->soket_tipi,
                        'pim_baglanti_semasi' => $request->pim_baglanti_semasi,
                        'cihaz_kablo_uzunlugu' => $request->cihaz_kablo_uzunlugu,
                        'is_active' => 2,
                    ]);
                    $nextPage++;
                }

                // 4. Yedek Parça TechnicalData (page = 2 veya 3) - eğer talep edildiyse
                if ($request->yedek_parca_var_mi) {
                    TechnicalData::create([
                        'job_id' => $job->id,
                        'page' => $nextPage,
                        'teknik_data_tipi' => 0,
                        'aciklama' => $request->yedek_parca_detay,
                        'is_active' => 2,
                    ]);
                }

                // 3. Portal Request oluştur
                $portalRequest = PortalRequest::create([
                    'request_no' => $this->requestNumberService->generate(),
                    'portal_user_id' => $user->id,
                    'company_id' => $user->company_id,
                    'job_id' => $job->id,
                    'technical_data_id' => $technicalData->id,
                    'request_type' => $request->request_type,
                    'customer_reference_code' => $request->customer_reference_code,
                    'customer_mold_code' => $request->customer_mold_code,
                    'customer_notes' => $request->customer_notes,
                    'expected_delivery_date' => $request->expected_delivery_date,
                    'priority' => $request->priority ?? 2,
                    'current_state_id' => PortalRequestState::STATE_RECEIVED,
                    'is_active' => 1,
                ]);

                // Job açıklamasını güncelle
                $job->update([
                    'aciklama' => "Portal üzerinden oluşturuldu. Talep No: {$portalRequest->request_no}",
                ]);

                // 4. Dosyalar varsa yükle
                if ($request->hasFile('files')) {
                    foreach ($request->file('files') as $file) {
                        $fileInfo = $this->fileStorageService->store(
                            $file,
                            $jobNo,
                            $technicalData->id
                        );

                        ErpFile::create([
                            'job_id' => $job->id,
                            'baglanti_id' => $technicalData->id,
                            'baglanti_tablo_adi' => 'technical_datas',
                            'dosya_adi' => $fileInfo['original_name'],
                            'dosya_yolu' => $fileInfo['relative_path'],
                            'extension' => $fileInfo['extension'],
                            'dosya_boyut' => $fileInfo['size'],
                            'aciklama' => "Portal - Talep No: {$portalRequest->request_no}",
                            'user_id' => null,
                            'is_active' => 1,
                        ]);
                    }
                }

                // 5. İlk durum logu ekle
                PortalRequestStateLog::create([
                    'portal_request_id' => $portalRequest->id,
                    'portal_request_state_id' => PortalRequestState::STATE_RECEIVED,
                    'aciklama' => 'Talep oluşturuldu.',
                    'changed_by_portal_user_id' => $user->id,
                    'is_active' => 1,
                ]);

                return $portalRequest;
            });

            return response()->json([
                'success' => true,
                'message' => 'Talep başarıyla oluşturuldu.',
                'data' => new RequestResource($result->load(['job.files', 'currentState'])),
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Talep oluşturma hatası', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Talep oluşturulurken bir hata oluştu.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Talep detayı
     * GET /api/requests/{id}
     */
    public function show(int $id): JsonResponse
    {
        $user = Auth::guard('api')->user();

        $portalRequest = PortalRequest::with(['job.technicalData', 'job.files', 'currentState', 'stateLogs.state', 'portalUser.contact'])
            ->forCompany($user->company_id)
            ->active()
            ->find($id);

        if (!$portalRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Talep bulunamadı.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new RequestResource($portalRequest),
        ]);
    }

    /**
     * Talep güncelle (sadece bekleyen)
     * PUT /api/requests/{id}
     */
    public function update(UpdateRequestRequest $request, int $id): JsonResponse
    {
        $user = Auth::guard('api')->user();

        $portalRequest = PortalRequest::forCompany($user->company_id)
            ->active()
            ->find($id);

        if (!$portalRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Talep bulunamadı.',
            ], 404);
        }

        if (!$portalRequest->isEditable()) {
            return response()->json([
                'success' => false,
                'message' => 'Bu talep artık düzenlenemez.',
            ], 403);
        }

        try {
            DB::transaction(function () use ($request, $portalRequest) {
                // Portal request güncelle
                $portalRequest->update($request->only([
                    'customer_reference_code',
                    'customer_mold_code',
                    'customer_notes',
                    'expected_delivery_date',
                    'priority',
                ]));

                // Job güncelle
                if ($request->has('customer_reference_code')) {
                    $portalRequest->job->update([
                        'mold_maker_ref_no' => $request->customer_reference_code,
                    ]);
                }

                // Technical data güncelle
                $technicalData = $portalRequest->job->technicalData;
                if ($technicalData) {
                    $technicalData->update($request->only([
                        'parca_agirligi',
                        'et_kalinligi',
                        'malzeme',
                        'kalip_x',
                        'kalip_y',
                        'kalip_d',
                        'goz_sayisi' => 'kalip_parca_sayisi',
                        'meme_sayisi',
                    ]));
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Talep başarıyla güncellendi.',
                'data' => new RequestResource($portalRequest->fresh(['job.technicalData', 'currentState'])),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Talep güncellenirken bir hata oluştu.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Talep iptal et
     * POST /api/requests/{id}/cancel
     */
    public function cancel(int $id): JsonResponse
    {
        $user = Auth::guard('api')->user();

        $portalRequest = PortalRequest::forCompany($user->company_id)
            ->active()
            ->find($id);

        if (!$portalRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Talep bulunamadı.',
            ], 404);
        }

        if (!$portalRequest->isCancellable()) {
            return response()->json([
                'success' => false,
                'message' => 'Bu talep iptal edilemez.',
            ], 403);
        }

        try {
            DB::transaction(function () use ($portalRequest, $user) {
                $portalRequest->update([
                    'current_state_id' => PortalRequestState::STATE_CANCELLED,
                ]);

                PortalRequestStateLog::create([
                    'portal_request_id' => $portalRequest->id,
                    'portal_request_state_id' => PortalRequestState::STATE_CANCELLED,
                    'aciklama' => 'Talep müşteri tarafından iptal edildi.',
                    'changed_by_portal_user_id' => $user->id,
                    'is_active' => 1,
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Talep başarıyla iptal edildi.',
                'data' => new RequestResource($portalRequest->fresh(['currentState'])),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Talep iptal edilirken bir hata oluştu.',
            ], 500);
        }
    }

    /**
     * Durum geçmişi
     * GET /api/requests/{id}/history
     */
    public function history(int $id): JsonResponse
    {
        $user = Auth::guard('api')->user();

        $portalRequest = PortalRequest::forCompany($user->company_id)
            ->active()
            ->find($id);

        if (!$portalRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Talep bulunamadı.',
            ], 404);
        }

        $logs = $portalRequest->stateLogs()
            ->with(['state', 'changedByUser', 'changedByPortalUser'])
            ->active()
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => StateLogResource::collection($logs),
        ]);
    }
}
