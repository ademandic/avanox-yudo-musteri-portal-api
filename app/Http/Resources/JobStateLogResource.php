<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobStateLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'job_id' => $this->job_id,
            'state_id' => $this->job_state_id,
            'state_name' => $this->state?->name ?? $this->getDefaultStateName(),
            'state_english_name' => $this->state?->english_name,
            'aciklama' => $this->aciklama,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }

    /**
     * State bulunamazsa varsayılan isim
     */
    private function getDefaultStateName(): ?string
    {
        // Portal Request Received
        if ($this->job_state_id === 5) {
            return 'Portal Talebi Alındı';
        }
        return null;
    }
}
