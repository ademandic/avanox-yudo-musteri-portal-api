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
use App\Models\JobStateLog;
use App\Models\PortalRequest;
use App\Models\PortalRequestState;
use App\Models\PortalRequestStateLog;
use App\Models\TechnicalData;
use App\Models\TechnicalDataSystem;
use App\Services\ErpWebhookService;
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
        protected FileStorageService $fileStorageService,
        protected ErpWebhookService $erpWebhookService
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
                $company = $user->company;

                // Portal kullanıcısının email'i ile eşleşen contact'ı bul
                $contact = \App\Models\Contact::where('company_id', $user->company_id)
                    ->where('email', $user->email)
                    ->first();
                $contactId = $contact ? $contact->id : null;

                // Job kategori belirleme
                $jobCategoryId = match ((int) $request->request_type) {
                    PortalRequest::TYPE_CONTROLLER => 8, // Controller Sales
                    PortalRequest::TYPE_SPARE_PARTS => 2, // Spare Parts Sales
                    default => 1, // System Sales
                };

                $jobData = [
                    'job_no' => $jobNo,
                    'job_category_id' => $jobCategoryId,
                    'user_id' => $user->id,
                    'aciklama' => "Portal üzerinden oluşturuldu.",
                    'is_active' => 2,
                    'source' => Job::SOURCE_PORTAL,
                    'final_customer_id' => $user->company_id,
                    'final_customer_contact_id' => $contactId,
                    'final_customer_ref_no' => $request->customer_reference_code,
                    'related_yudo_id_no' => $request->related_yudo_id_no,
                ];

                if ($company->is_molder == 1) {
                    $jobData['molder_id'] = $user->company_id;
                    $jobData['molder_contact_id'] = $contactId;
                    $jobData['molder_ref_no'] = $request->customer_reference_code;
                }

                if ($company->is_mold_maker == 1) {
                    $jobData['mold_maker_id'] = $user->company_id;
                    $jobData['mold_maker_contact_id'] = $contactId;
                    $jobData['mold_maker_ref_no'] = $request->customer_reference_code;
                }

                $job = Job::create($jobData);

                // 2. TechnicalData oluştur - request_type'a göre farklı akış
                $requestType = (int) $request->request_type;
                $technicalData = null;

                if (in_array($requestType, [PortalRequest::TYPE_DESIGN, PortalRequest::TYPE_OFFER, PortalRequest::TYPE_BOTH])) {
                    // Sistem talepleri: Ana sistem + opsiyonel kontrol cihazı/yedek parça
                    $technicalData = $this->createSystemTechnicalData($request, $job);
                } elseif ($requestType === PortalRequest::TYPE_CONTROLLER) {
                    // Sadece Kontrol Cihazı talebi
                    $technicalData = $this->createControllerTechnicalData($request, $job);
                } elseif ($requestType === PortalRequest::TYPE_SPARE_PARTS) {
                    // Sadece Yedek Parça talebi
                    $technicalData = $this->createSparePartsTechnicalData($request, $job);
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

                // 5. İlk durum logu ekle (Portal state)
                PortalRequestStateLog::create([
                    'portal_request_id' => $portalRequest->id,
                    'portal_request_state_id' => PortalRequestState::STATE_RECEIVED,
                    'aciklama' => 'Talep oluşturuldu.',
                    'changed_by_portal_user_id' => $user->id,
                    'is_active' => 1,
                ]);

                // 6. ERP Job State log ekle
                JobStateLog::logPortalRequestReceived(
                    $job->id,
                    $user->id,
                    "Portal - Talep No: {$portalRequest->request_no}"
                );

                return $portalRequest;
            });

            // ERP'ye bildirim gönder (async - hata olsa bile talep oluşturulmuş olur)
            try {
                $this->erpWebhookService->notifyNewRequest([
                    'job_id' => $result->job_id,
                    'job_no' => $result->job->job_no,
                    'request_number' => $result->request_no,
                    'request_type' => $result->request_type,
                    'company_id' => $result->company_id,
                    'company_name' => $result->company->company_name ?? null,
                    'portal_user' => $user->full_name,
                ]);
            } catch (\Exception $webhookError) {
                \Log::warning('ERP webhook hatası (talep başarıyla oluşturuldu)', [
                    'request_no' => $result->request_no,
                    'error' => $webhookError->getMessage(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Talep başarıyla oluşturuldu.',
                'data' => new RequestResource($result->load([
                    'job.files',
                    'job.stateLogs.state',
                    'currentState'
                ])),
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
     * Sistem talepleri için TechnicalData oluştur (Tasarım/Teklif/İkisi)
     */
    private function createSystemTechnicalData(StoreRequestRequest $request, Job $job): TechnicalData
    {
        $openValveValue = null;
        if ($request->sistem_tipi === 'valvegate') {
            $openValveValue = 'VALVE';
        } elseif ($request->sistem_tipi === 'open_end') {
            $openValveValue = 'OPEN';
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
            'renk_degisimi' => $request->renk_degisimi,
            'parca_gorselligi' => $request->parca_gorselligi,
            'kalip_x' => $request->kalip_x,
            'kalip_y' => $request->kalip_y,
            'kalip_d' => $request->kalip_d,
            'kalip_e' => $request->kalip_e,
            'kalip_ct' => $request->kalip_ct,
            'kalip_st' => $request->kalip_st,
            'kalip_ht' => $request->kalip_ht,
            'kalip_en' => $request->kalip_en,
            'kalip_boy' => $request->kalip_boy,
            'double_injection' => $request->double_injection,
            'ikinci_enj_yandan_mi' => $request->ikinci_enj_yandan_mi,
            'blue_id_var_mi' => $request->blue_id_var_mi,
            'blue_id_nereye' => $request->blue_id_nereye,
            'kalip_parca_sayisi' => $request->goz_sayisi,
            'meme_sayisi' => $request->meme_sayisi,
            'tip_sekli' => $request->meme_tipi,
            'open_valve' => $openValveValue,
            'is_active' => 2,
        ]);

        // Ana Sistem için TechnicalDataSystem kaydı
        $parcadaKonumu = null;
        if ($request->meme_tipi === 'parca') {
            $parcadaKonumu = 'Parça üzerinde';
        } elseif ($request->meme_tipi === 'yolluk') {
            $parcadaKonumu = 'Soğuk Yolluk Üzerinde';
        }

        TechnicalDataSystem::create([
            'technical_data_id' => $technicalData->id,
            'baski_no' => 1,
            'parca_agirligi' => $request->parca_agirligi,
            'et_kalinligi' => $request->et_kalinligi,
            'malzeme' => $request->malzeme,
            'malzeme_katki' => $request->katki_var_mi ? $request->katki_turu : null,
            'malzeme_katki_yuzdesi' => $request->katki_orani,
            'renk_degisimi' => $request->renk_degisimi,
            'parca_gorselligi' => $request->parca_gorselligi,
            'meme_sayisi' => $request->meme_sayisi,
            'parcada_konumu' => $parcadaKonumu,
            'open_valve' => $openValveValue,
        ]);

        $nextPage = 2;

        // Opsiyonel Kontrol Cihazı
        if ($request->kontrol_cihazi_var_mi) {
            TechnicalData::create([
                'job_id' => $job->id,
                'page' => $nextPage,
                'teknik_data_tipi' => 1,
                'cihaz_tipi' => $request->cihaz_tipi,
                'cihaz_bolg_sayisi' => $request->bolge_sayisi,
                'soket_tipi' => $request->soket_tipi,
                'pim_baglanti_semasi' => $request->pim_baglanti_semasi,
                'cihaz_kablo_uzunlugu' => $request->cihaz_kablo_uzunlugu,
                'is_active' => 2,
            ]);
            $nextPage++;
        }

        // Opsiyonel Yedek Parça
        if ($request->yedek_parca_var_mi) {
            TechnicalData::create([
                'job_id' => $job->id,
                'page' => $nextPage,
                'teknik_data_tipi' => 2,
                'aciklama' => $request->yedek_parca_detay,
                'is_active' => 2,
            ]);
        }

        return $technicalData;
    }

    /**
     * Sadece Kontrol Cihazı talebi için TechnicalData oluştur
     */
    private function createControllerTechnicalData(StoreRequestRequest $request, Job $job): TechnicalData
    {
        return TechnicalData::create([
            'job_id' => $job->id,
            'page' => 2,
            'teknik_data_tipi' => 1, // Kontrol Cihazı
            'cihaz_tipi' => $request->cihaz_tipi,
            'cihaz_bolg_sayisi' => $request->bolge_sayisi,
            'soket_tipi' => $request->soket_tipi,
            'pim_baglanti_semasi' => $request->pim_baglanti_semasi,
            'cihaz_kablo_uzunlugu' => $request->cihaz_kablo_uzunlugu,
            'aciklama' => $request->customer_notes,
            'is_active' => 2,
        ]);
    }

    /**
     * Sadece Yedek Parça talebi için TechnicalData oluştur
     */
    private function createSparePartsTechnicalData(StoreRequestRequest $request, Job $job): TechnicalData
    {
        return TechnicalData::create([
            'job_id' => $job->id,
            'page' => 2,
            'teknik_data_tipi' => 2, // Yedek Parça
            'aciklama' => $request->yedek_parca_detay,
            'is_active' => 2,
        ]);
    }

    /**
     * Talep detayı
     * GET /api/requests/{id}
     */
    public function show(int $id): JsonResponse
    {
        \Log::info('RequestController::show - CALLED', ['id' => $id]);

        $user = Auth::guard('api')->user();

        $portalRequest = PortalRequest::with([
            'job.technicalData.drawingStateLogs' => function ($query) {
                $query->whereIn('drawing_state_id', \App\Models\DrawingState::PORTAL_VISIBLE_STATES)
                    ->with('state')
                    ->orderBy('tarih_saat', 'desc');
            },
            'job.offers.stateLogs' => function ($query) {
                $query->whereIn('offer_states_id', \App\Models\OfferState::PORTAL_VISIBLE_STATES)
                    ->with('state')
                    ->orderBy('tarih_saat', 'desc');
            },
            'job.stateLogs' => function ($query) {
                $query->with('state')
                    ->orderBy('created_at', 'desc');
            },
            'job.files',
            'currentState',
            'stateLogs.state',
            'portalUser.contact'
        ])
            ->forCompany($user->company_id)
            ->active()
            ->find($id);

        if (!$portalRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Talep bulunamadı.',
            ], 404);
        }

        // DEBUG: Job state logs kontrolü
        \Log::info('RequestController::show - Job State Logs Debug', [
            'request_id' => $id,
            'job_id' => $portalRequest->job?->id,
            'job_loaded' => $portalRequest->relationLoaded('job'),
            'stateLogs_loaded' => $portalRequest->job?->relationLoaded('stateLogs'),
            'stateLogs_count' => $portalRequest->job?->stateLogs?->count(),
            'stateLogs_data' => $portalRequest->job?->stateLogs?->toArray(),
        ]);

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
                // Portal request durumunu iptal olarak güncelle
                $portalRequest->update([
                    'current_state_id' => PortalRequestState::STATE_CANCELLED,
                ]);

                // State log kaydı oluştur
                PortalRequestStateLog::create([
                    'portal_request_id' => $portalRequest->id,
                    'portal_request_state_id' => PortalRequestState::STATE_CANCELLED,
                    'aciklama' => 'Talep müşteri tarafından iptal edildi.',
                    'changed_by_portal_user_id' => $user->id,
                    'is_active' => 1,
                ]);

                // İlişkili job'u pasif yap
                if ($portalRequest->job) {
                    $portalRequest->job->update(['is_active' => 0]);

                    // İlişkili technical_data'yı pasif yap
                    if ($portalRequest->job->technicalData) {
                        $portalRequest->job->technicalData->update(['is_active' => 0]);
                    }
                }
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
     * Hızlı düzenleme (10 dakika içinde)
     * POST /api/requests/{id}/quick-update
     */
    public function quickUpdate(Request $request, int $id): JsonResponse
    {
        $user = Auth::guard('api')->user();

        $portalRequest = PortalRequest::with(['job', 'technicalData'])
            ->forCompany($user->company_id)
            ->active()
            ->find($id);

        if (!$portalRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Talep bulunamadı.',
            ], 404);
        }

        // 10 dakikalık düzenleme penceresi kontrolü
        if (!$portalRequest->isInQuickEditWindow()) {
            return response()->json([
                'success' => false,
                'message' => 'Düzenleme süresi dolmuş. Talepler oluşturulduktan sonra sadece 10 dakika içinde düzenlenebilir.',
            ], 403);
        }

        try {
            $result = DB::transaction(function () use ($request, $portalRequest, $user) {
                // Portal request güncelle
                $portalRequest->update($request->only([
                    'customer_reference_code',
                    'customer_mold_code',
                    'customer_notes',
                ]));

                // Job güncelle
                if ($request->has('customer_reference_code')) {
                    $portalRequest->job->update([
                        'mold_maker_ref_no' => $request->customer_reference_code,
                        'final_customer_ref_no' => $request->customer_reference_code,
                    ]);
                }

                // Dosyalar varsa yükle
                if ($request->hasFile('files')) {
                    $job = $portalRequest->job;
                    $technicalData = $portalRequest->technicalData;

                    if (!$technicalData) {
                        throw new \Exception('Teknik veri bulunamadı.');
                    }

                    foreach ($request->file('files') as $file) {
                        $fileInfo = $this->fileStorageService->store(
                            $file,
                            $job->job_no,
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
                            'aciklama' => "Portal - Hızlı Düzenleme - Talep No: {$portalRequest->request_no}",
                            'user_id' => null,
                            'is_active' => 1,
                        ]);
                    }
                }

                return $portalRequest;
            });

            return response()->json([
                'success' => true,
                'message' => 'Talep başarıyla güncellendi.',
                'data' => new RequestResource($result->fresh([
                    'job.files',
                    'job.technicalData',
                    'job.controllerTechnicalData',
                    'job.sparePartsTechnicalData',
                    'currentState'
                ])),
            ]);

        } catch (\Exception $e) {
            \Log::error('Hızlı düzenleme hatası', [
                'request_id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Talep güncellenirken bir hata oluştu: ' . $e->getMessage(),
                'error' => $e->getMessage(),
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
