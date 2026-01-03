<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OfferStateLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'offer_id' => $this->offer_id,
            'state_id' => $this->offer_states_id,
            'state_name' => $this->whenLoaded('state', fn() => $this->state->name),
            'state_english_name' => $this->whenLoaded('state', fn() => $this->state->english_name),
            'aciklama' => $this->aciklama,
            'tarih_saat' => $this->tarih_saat?->toISOString(),
        ];
    }
}
