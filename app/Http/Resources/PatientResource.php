<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'patient_id' => $this->id,
            'patient_code' => $this->patient_code,
            'full_name' => $this->full_name,
            'gender' => $this->gender,
            'phone' => $this->phone,
            'birth_date' => $this->birth_date,
            'address' => $this->address,
            // preliminary_diagnosis مُبقى مؤقتاً للتوافق الخلفي مع شاشات الموبايل
            // القديمة؛ سيُحذف بعد أن تنتقل جميعها لقراءة chief_complaint.
            'chief_complaint' => $this->preliminary_diagnosis,
            'preliminary_diagnosis' => $this->preliminary_diagnosis,
            'status' => $this->availability_status,

            'id_card' => $this->whenLoaded('media', function () {
                return $this->getMedia('id_cards')->map(fn($media) => [
                    'id' => $media->id,
                    'url' => $media->getFullUrl(),
                ])->first();
            }),

            'clinical_images' => $this->when(!($this->relationLoaded('diagnoses') && $this->diagnoses->isNotEmpty()), function () {
                return $this->getMedia('clinical_images')->map(fn($media) => [
                    'id' => $media->id,
                    'url' => $media->getFullUrl(),
                ]);
            }),

            'x_ray_images' => $this->when(!($this->relationLoaded('diagnoses') && $this->diagnoses->isNotEmpty()), function () {
                return $this->getMedia('x_ray_images')->map(fn($media) => [
                    'id' => $media->id,
                    'url' => $media->getFullUrl(),
                ]);
            }),

            'medical_history' => $this->whenLoaded('medicalHistory', function () {
                return [
                    'general_diseases' => ['has' => (bool) $this->medicalHistory->has_general_diseases, 'details' => $this->medicalHistory->general_diseases_details],
                    'special_needs' => ['has' => (bool) $this->medicalHistory->is_special_needs, 'details' => $this->medicalHistory->special_needs_details],
                    'medications' => ['has' => (bool) $this->medicalHistory->takes_medications, 'details' => $this->medicalHistory->medications_details],
                    'allergies' => ['has' => (bool) $this->medicalHistory->has_allergies, 'details' => $this->medicalHistory->allergies_details],
                    'is_pregnant' => $this->gender === 'female' ? (bool) $this->medicalHistory->is_pregnant : null,
                ];
            }),

            'diagnoses' => $this->whenLoaded('diagnoses', function () {
                return $this->diagnoses->map(function ($diagnosis) {
                    return [
                        'diagnosis_id' => $diagnosis->id,
                        'case_type' => ['id' => $diagnosis->case_type_id, 'name' => $diagnosis->caseType?->name],
                        'department' => ['id' => $diagnosis->department_id, 'name' => $diagnosis->department?->name],
                        'status' => $diagnosis->status,
                        'final_diagnosis' => $diagnosis->final_diagnosis,
                        'estimated_cost' => (float) $diagnosis->estimated_cost,

                        'media' => [
                            'clinical_images' => $diagnosis->getMedia('clinical_images')->map(fn($media) => [
                                'id' => $media->id,
                                'url' => $media->getFullUrl(),
                            ]),
                            'x_ray_images' => $diagnosis->getMedia('x_ray_images')->map(fn($media) => [
                                'id' => $media->id,
                                'url' => $media->getFullUrl(),
                            ]),
                        ],
                    ];
                });
            }),

            'created_at' => $this->created_at?->format('Y-m-d H:i'),
        ];
    }


  
}
