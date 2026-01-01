<?php

/**
 * YUDO Customer Portal API
 *
 * @author    Avanox Bilişim Yazılım ve Danışmanlık LTD ŞTİ
 * @copyright 2025 Avanox Bilişim
 * @license   Proprietary - All rights reserved
 */

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'name' => $this->full_name ?? $this->name,
            'first_name' => $this->first_name,
            'surname' => $this->surname,
            'company_id' => $this->company_id,
            'is_company_admin' => $this->is_company_admin ?? false,
            'is_active' => $this->is_active ?? true,
            'portal_theme' => $this->portal_theme ?? 'light',
            'portal_language' => $this->portal_language ?? 'tr',
            'last_login_at' => $this->last_login_at?->toIso8601String(),
            'company' => $this->whenLoaded('company', function () {
                return new CompanyResource($this->company);
            }),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
