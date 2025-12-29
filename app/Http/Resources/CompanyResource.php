<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'short_name' => $this->short_name,
            'tax_number' => $this->tax_number,
            'tax_office' => $this->tax_office,
            'phone' => $this->phone,
            'fax' => $this->fax,
            'email' => $this->email,
            'website' => $this->web,
            'is_active' => (bool) $this->is_active,

            // Satışçı bilgisi
            'sales_person' => $this->whenLoaded('salesPerson', function () {
                return [
                    'id' => $this->salesPerson->id,
                    'name' => $this->salesPerson->full_name,
                    'email' => $this->salesPerson->email,
                    'phone' => $this->salesPerson->mobile_phone,
                ];
            }),

            // Firma yetkilileri
            'contacts' => $this->whenLoaded('contacts', function () {
                return $this->contacts->map(function ($contact) {
                    return [
                        'id' => $contact->id,
                        'name' => $contact->name,
                        'surname' => $contact->surname,
                        'full_name' => $contact->full_name,
                        'title' => $contact->title,
                        'email' => $contact->email,
                        'phone' => $contact->phone,
                        'mobile_phone' => $contact->mobile_phone,
                        'is_default' => (bool) $contact->default_contact,
                        'is_design_contact' => (bool) $contact->design_contact,
                    ];
                });
            }),
        ];
    }
}
