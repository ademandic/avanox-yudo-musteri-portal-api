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

            // Teknik veriler
            'technical_data' => $this->whenLoaded('technicalData', function () {
                $td = $this->technicalData;
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
                    'parca_gorselligi' => $td->parca_gorselligi,
                ] : null;
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
