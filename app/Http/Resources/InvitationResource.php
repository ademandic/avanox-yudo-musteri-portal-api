<?php

namespace App\Http\Resources;

use App\Models\PortalInvitation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvitationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'is_valid' => $this->isValid(),
            'is_expired' => $this->isExpired(),
            'is_accepted' => $this->isAccepted(),
            'sent_at' => $this->sent_at?->toISOString(),
            'expires_at' => $this->expires_at?->toISOString(),
            'accepted_at' => $this->accepted_at?->toISOString(),

            // İletişim kişisi bilgileri
            'contact' => $this->whenLoaded('contact', function () {
                return [
                    'id' => $this->contact->id,
                    'name' => $this->contact->name,
                    'surname' => $this->contact->surname,
                    'full_name' => $this->contact->full_name,
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

            // Daveti gönderen
            'invited_by' => $this->whenLoaded('invitedBy', function () {
                return [
                    'id' => $this->invitedBy->id,
                    'name' => $this->invitedBy->full_name,
                ];
            }),
        ];
    }
}
