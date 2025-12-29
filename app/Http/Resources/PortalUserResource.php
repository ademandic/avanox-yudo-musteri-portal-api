<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PortalUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'full_name' => $this->full_name,
            'last_login_at' => $this->last_login_at?->toISOString(),
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),

            // İletişim kişisi bilgileri
            'contact' => $this->whenLoaded('contact', function () {
                return [
                    'id' => $this->contact->id,
                    'name' => $this->contact->name,
                    'surname' => $this->contact->surname,
                    'full_name' => $this->contact->full_name,
                    'title' => $this->contact->title,
                    'phone' => $this->contact->phone,
                    'mobile_phone' => $this->contact->mobile_phone,
                ];
            }),

            // Firma bilgileri
            'company' => $this->whenLoaded('company', function () {
                return [
                    'id' => $this->company->id,
                    'name' => $this->company->name,
                    'short_name' => $this->company->short_name,
                ];
            }),
        ];
    }
}
