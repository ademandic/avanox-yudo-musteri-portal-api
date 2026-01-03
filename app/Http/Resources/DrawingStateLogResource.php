<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DrawingStateLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'state_id' => $this->drawing_state_id,
            'state_name' => $this->whenLoaded('state', fn() => $this->state->name),
            'state_english_name' => $this->whenLoaded('state', fn() => $this->state->english_name),
            'state_class' => $this->whenLoaded('state', fn() => $this->state->class_name),
            'aciklama' => $this->aciklama,
            'tarih_saat' => $this->tarih_saat?->toISOString(),
        ];
    }
}
