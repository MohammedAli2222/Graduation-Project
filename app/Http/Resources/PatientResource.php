<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'patient_id'   => $this->id,
            'patient_code' => $this->patient_code,
            'full_name'    => $this->full_name,
            'phone'        => $this->phone,
            'med_history'  => $this->med_history,
            'status'       => $this->availability_status,
            'images'       => $this->getMedia('patient_records')->map(function($media) {
                return [
                    'id'   => $media->id,
                    'url'  => $media->getFullUrl(),
                ];
            }),
            'created_at'   => $this->created_at->format('Y-m-d H:i'),
        ];
    }
}
