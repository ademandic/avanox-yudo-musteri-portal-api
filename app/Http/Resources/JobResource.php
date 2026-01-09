<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'job_no' => $this->job_no,
            'yudo_id_no' => $this->yudo_id_no,
            'mold_maker_ref_no' => $this->mold_maker_ref_no,
            'part_description' => $this->part_description,
            'related_job_no' => $this->related_job_no,
            'aciklama' => $this->aciklama,
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Teknik veriler (request_type'a göre doğru kaynaktan)
            'technical_data' => $this->when(
                $this->relationLoaded('technicalData') ||
                $this->relationLoaded('controllerTechnicalData') ||
                $this->relationLoaded('sparePartsTechnicalData') ||
                $this->relationLoaded('primaryTechnicalData'),
                function () {
                    // Request type'a göre doğru technical data'yı seç
                    $requestType = $this->portalRequest?->request_type ?? null;

                    $td = match ($requestType) {
                        4 => $this->controllerTechnicalData ?? $this->primaryTechnicalData,
                        5 => $this->sparePartsTechnicalData ?? $this->primaryTechnicalData,
                        default => $this->technicalData ?? $this->primaryTechnicalData,
                    };

                    return $td ? [
                    'id' => $td->id,
                    'teknik_data_tipi' => $td->teknik_data_tipi,
                    'teknik_data_adi' => $td->teknik_data_adi,
                    'parca_agirligi' => $td->parca_agirligi,
                    'et_kalinligi' => $td->et_kalinligi,
                    'malzeme' => $td->malzeme,
                    'malzeme_katki' => $td->malzeme_katki,
                    'malzeme_katki_yuzdesi' => $td->malzeme_katki_yuzdesi,
                    'kalip_x' => $td->kalip_x,
                    'kalip_y' => $td->kalip_y,
                    'kalip_d' => $td->kalip_d,
                    'kalip_e' => $td->kalip_e,
                    'kalip_ct' => $td->kalip_ct,
                    'kalip_st' => $td->kalip_st,
                    'kalip_ht' => $td->kalip_ht,
                    'kalip_en' => $td->kalip_en,
                    'kalip_boy' => $td->kalip_boy,
                    'kalip_parca_sayisi' => $td->kalip_parca_sayisi,
                    'meme_sayisi' => $td->meme_sayisi,
                    'meme_sayisi_2' => $td->meme_sayisi_2,
                    'sistem_adi' => $td->sistem_adi,
                    'sistem_adi_2' => $td->sistem_adi_2,
                    'meme_capi' => $td->meme_capi,
                    'meme_capi_2' => $td->meme_capi_2,
                    'tip_sekli' => $td->tip_sekli,
                    'tip_sekli_2' => $td->tip_sekli_2,
                    'parcada_konumu' => $td->parcada_konumu,
                    'parcada_konumu_2' => $td->parcada_konumu_2,
                    'double_injection' => (bool) $td->double_injection,
                    'ikinci_enj_yandan_mi' => (bool) $td->ikinci_enj_yandan_mi,
                    'blue_id_var_mi' => (bool) $td->blue_id_var_mi,
                    'blue_id_nereye' => $td->blue_id_nereye,
                    'renk_degisimi' => $td->renk_degisimi,
                    'parca_gorselligi' => $td->parca_gorselligi,
                    // Kontrol Cihazı alanları
                    'cihaz_tipi' => $td->cihaz_tipi,
                    'cihaz_bolg_sayisi' => $td->cihaz_bolg_sayisi,
                    'soket_tipi' => $td->soket_tipi,
                    'pim_baglanti_semasi' => $td->pim_baglanti_semasi,
                    'cihaz_kablo_uzunlugu' => $td->cihaz_kablo_uzunlugu,
                    // Yedek Parça alanı
                    'aciklama' => $td->aciklama,
                    // Drawing state logs (ERP)
                    'drawing_state_logs' => $td->relationLoaded('drawingStateLogs')
                        ? DrawingStateLogResource::collection($td->drawingStateLogs)
                        : [],
                ] : null;
            }),

            // Job state logs (ERP)
            'state_logs' => $this->relationLoaded('stateLogs')
                ? JobStateLogResource::collection($this->stateLogs)
                : [],

            // Offers with state logs (ERP)
            'offers' => $this->whenLoaded('offers', function () {
                return $this->offers->map(function ($offer) {
                    return [
                        'id' => $offer->id,
                        'offer_no' => $offer->offer_no ?? null,
                        'state_logs' => $offer->relationLoaded('stateLogs')
                            ? OfferStateLogResource::collection($offer->stateLogs)
                            : [],
                    ];
                });
            }),

            // Dosyalar
            'files' => $this->whenLoaded('files', function () {
                return FileResource::collection($this->files);
            }),

            // Portal talebi (varsa)
            'portal_request' => $this->whenLoaded('portalRequest', function () {
                return $this->portalRequest ? [
                    'id' => $this->portalRequest->id,
                    'request_no' => $this->portalRequest->request_no,
                    'request_type' => $this->portalRequest->request_type,
                    'request_type_label' => $this->portalRequest->type_label,
                    'customer_reference_code' => $this->portalRequest->customer_reference_code,
                    'customer_mold_code' => $this->portalRequest->customer_mold_code,
                    'customer_notes' => $this->portalRequest->customer_notes,
                    'priority' => $this->portalRequest->priority,
                    'priority_label' => $this->portalRequest->priority_label,
                    'is_editable' => $this->portalRequest->isEditable(),
                    'is_cancellable' => $this->portalRequest->isCancellable(),
                    'created_by' => $this->portalRequest->relationLoaded('portalUser') && $this->portalRequest->portalUser
                        ? [
                            'id' => $this->portalRequest->portalUser->id,
                            'name' => $this->portalRequest->portalUser->name,
                            'email' => $this->portalRequest->portalUser->email,
                        ]
                        : null,
                    'current_state' => $this->portalRequest->currentState ? [
                        'id' => $this->portalRequest->currentState->id,
                        'name' => $this->portalRequest->currentState->name,
                        'color_class' => $this->portalRequest->currentState->color_class,
                    ] : null,
                    'state_logs' => $this->portalRequest->relationLoaded('stateLogs')
                        ? $this->portalRequest->stateLogs->map(function ($log) {
                            return [
                                'id' => $log->id,
                                'state' => $log->state ? [
                                    'id' => $log->state->id,
                                    'name' => $log->state->name,
                                ] : null,
                                'aciklama' => $log->aciklama,
                                'created_at' => $log->created_at?->toISOString(),
                            ];
                        })
                        : [],
                    'created_at' => $this->portalRequest->created_at?->toISOString(),
                ] : null;
            }),
        ];
    }
}
