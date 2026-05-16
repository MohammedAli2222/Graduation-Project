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

            'patient_id'            => $this->id,
            'patient_code'          => $this->patient_code,
            'full_name'             => $this->full_name,
            'gender'                => $this->gender,
            'phone'                 => $this->phone,
            'preliminary_diagnosis' => $this->preliminary_diagnosis,
            'status'                => $this->availability_status,

            'medical_history' => $this->whenLoaded('medicalHistory', function () {
                return [
                    'general_diseases' => [
                        'has'     => (bool) $this->medicalHistory->has_general_diseases,
                        'details' => $this->medicalHistory->general_diseases_details,
                    ],
                    'special_needs' => [
                        'has'     => (bool) $this->medicalHistory->is_special_needs,
                        'details' => $this->medicalHistory->special_needs_details,
                    ],
                    'medications' => [
                        'has'     => (bool) $this->medicalHistory->takes_medications,
                        'details' => $this->medicalHistory->medications_details,
                    ],
                    'allergies' => [
                        'has'     => (bool) $this->medicalHistory->has_allergies,
                        'details' => $this->medicalHistory->allergies_details,
                    ],
                ];
            }),

            'images' => $this->media->map(function ($media) {
                return [
                    'id'   => $media->id,
                    'url'  => $media->getFullUrl(),
                    'type' => $media->mime_type,
                ];
            }),

            'created_at' => $this->created_at->format('Y-m-d H:i'),
        ];
    }
}
