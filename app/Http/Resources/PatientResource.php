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

            'patient_id' => $this->id,
            'patient_code' => $this->patient_code,
            'full_name' => $this->full_name,
            'gender' => $this->gender,
            'phone' => $this->phone,
            'preliminary_diagnosis' => $this->preliminary_diagnosis,
            'status' => $this->availability_status,

            'medical_history' => $this->whenLoaded('medicalHistory', function () {
                return [
                    'general_diseases' => [
                        'has' => (bool) $this->medicalHistory->has_general_diseases,
                        'details' => $this->medicalHistory->general_diseases_details,
                    ],
                    'special_needs' => [
                        'has' => (bool) $this->medicalHistory->is_special_needs,
                        'details' => $this->medicalHistory->special_needs_details,
                    ],
                    'medications' => [
                        'has' => (bool) $this->medicalHistory->takes_medications,
                        'details' => $this->medicalHistory->medications_details,
                    ],
                    'allergies' => [
                        'has' => (bool) $this->medicalHistory->has_allergies,
                        'details' => $this->medicalHistory->allergies_details,
                    ],
                ];
            }),

            'diagnoses' => $this->whenLoaded('diagnoses', function () {
                return $this->diagnoses->map(function ($diagnosis) {
                    return [
                        'diagnosis_id' => $diagnosis->id,
                        'case_type' => [
                            'id' => $diagnosis->case_type_id,
                            'name' => $diagnosis->caseType ? $diagnosis->caseType->name : null,
                        ],
                        'department' => [
                            'id' => $diagnosis->department_id,
                            'name' => $diagnosis->department ? $diagnosis->department->name : null,
                        ],

                        'suggested_by_student' => [
                            'id' => $diagnosis->suggested_by_student_id,
                            'full_name' => $diagnosis->student ? ($diagnosis->student->first_name).' '.($diagnosis->student->last_name) : null,
                        ],

                        'final_diagnosis' => $diagnosis->final_diagnosis,
                        'approval_status' => $diagnosis->status,
                        'rejection_reason' => $diagnosis->rejection_reason,
                        'created_at' => $diagnosis->created_at ? $diagnosis->created_at->format('Y-m-d H:i') : null,
                    ];
                });
            }),

            'images' => $this->media->map(function ($media) {
                return [
                    'id' => $media->id,
                    'url' => $media->getFullUrl(),
                    'type' => $media->mime_type,
                ];
            }),

            'created_at' => $this->created_at->format('Y-m-d H:i'),
        ];
    }
}
