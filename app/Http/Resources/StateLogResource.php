<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StateLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'description' => $this->aciklama,
            'changed_by' => $this->changed_by_name,
            'created_at' => $this->created_at?->toISOString(),

            // Durum bilgisi
            'state' => $this->whenLoaded('state', function () {
                return [
                    'id' => $this->state->id,
                    'name' => $this->state->name,
                    'english_name' => $this->state->english_name,
                    'color_class' => $this->state->color_class,
                ];
            }),
        ];
    }
}
