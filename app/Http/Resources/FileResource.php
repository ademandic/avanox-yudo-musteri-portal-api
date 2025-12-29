<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'file_name' => $this->dosya_adi,
            'extension' => $this->extension,
            'size' => $this->dosya_boyut,
            'size_formatted' => $this->formatted_size,
            'description' => $this->aciklama,
            'download_url' => route('files.download', $this->id),
            'can_delete' => $this->baglanti_tablo_adi === 'portal_requests',
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
