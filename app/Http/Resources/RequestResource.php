<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'request_no' => $this->request_no,
            'request_type' => $this->request_type,
            'request_type_label' => $this->type_label,
            'priority' => $this->priority,
            'priority_label' => $this->priority_label,

            // Müşteri bilgileri
            'customer_reference_code' => $this->customer_reference_code,
            'customer_mold_code' => $this->customer_mold_code,
            'customer_notes' => $this->customer_notes,
            'expected_delivery_date' => $this->expected_delivery_date?->format('Y-m-d'),

            // Portal'a özel alanlar
            'kalip_z' => $this->kalip_z,

            // Durum bilgileri
            'current_state' => $this->whenLoaded('currentState', function () {
                return [
                    'id' => $this->currentState->id,
                    'name' => $this->currentState->name,
                    'english_name' => $this->currentState->english_name,
                    'color_class' => $this->currentState->color_class,
                ];
            }),

            // Düzenleme/iptal izinleri
            'is_editable' => $this->isEditable(),
            'is_cancellable' => $this->isCancellable(),

            // Oluşturan kullanıcı
            'created_by' => $this->whenLoaded('portalUser', function () {
                return [
                    'id' => $this->portalUser->id,
                    'name' => $this->portalUser->contact?->full_name ?? $this->portalUser->email,
                ];
            }),

            // Job bilgileri
            'job' => $this->whenLoaded('job', function () {
                return [
                    'id' => $this->job->id,
                    'job_no' => $this->job->job_no,

                    // Technical data
                    'technical_data' => $this->when($this->job->relationLoaded('technicalData'), function () {
                        $td = $this->job->technicalData;
                        return $td ? [
                            'id' => $td->id,
                            'parca_agirligi' => $td->parca_agirligi,
                            'et_kalinligi' => $td->et_kalinligi,
                            'malzeme' => $td->malzeme,
                            'malzeme_katki' => $td->malzeme_katki,
                            'malzeme_katki_yuzdesi' => $td->malzeme_katki_yuzdesi,
                            'kalip_x' => $td->kalip_x,
                            'kalip_y' => $td->kalip_y,
                            'kalip_d' => $td->kalip_d,
                            'kalip_e' => $td->kalip_e,
                            'kalip_parca_sayisi' => $td->kalip_parca_sayisi,
                            'meme_sayisi' => $td->meme_sayisi,
                            'tip_sekli' => $td->tip_sekli,
                            // Tasarım durumu geçmişi (ERP)
                            'drawing_state_logs' => $this->when(
                                $td->relationLoaded('drawingStateLogs'),
                                fn() => DrawingStateLogResource::collection($td->drawingStateLogs)
                            ),
                        ] : null;
                    }),

                    // Dosyalar
                    'files' => $this->when($this->job->relationLoaded('files'), function () {
                        return FileResource::collection($this->job->files);
                    }),

                    // Teklifler ve durumları (ERP)
                    'offers' => $this->when($this->job->relationLoaded('offers'), function () {
                        return $this->job->offers->map(function ($offer) {
                            return [
                                'id' => $offer->id,
                                'offer_no' => $offer->offer_no ?? null,
                                'state_logs' => $offer->relationLoaded('stateLogs')
                                    ? OfferStateLogResource::collection($offer->stateLogs)
                                    : [],
                            ];
                        });
                    }),

                    // Job state logları (ERP) - Her zaman döndür
                    'job_state_logs' => JobStateLogResource::collection($this->job->stateLogs ?? collect()),
                ];
            }),

            // Durum geçmişi
            'state_logs' => $this->whenLoaded('stateLogs', function () {
                return StateLogResource::collection($this->stateLogs);
            }),

            // Tarihler
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
