<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientDiagnosisDetailsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'diagnosis_id' => $this->id,
            'patient_info' => [
                'id'     => $this->patient_id,
                'name'   => $this->patient->full_name ?? 'N/A',
                'gender' => $this->patient->gender ?? 'N/A',
                'phone'  => $this->patient->phone ?? 'N/A',
            ],
            'medical_history' => $this->patient->medicalHistory ? [
                'has_general_diseases'     => (bool) $this->patient->medicalHistory->has_general_diseases,
                'general_diseases_details' => $this->patient->medicalHistory->general_diseases_details ?? 'لا يوجد أمراض عامة',

                'is_special_needs'         => (bool) $this->patient->medicalHistory->is_special_needs,
                'special_needs_details'    => $this->patient->medicalHistory->special_needs_details ?? 'لا يوجد احتياجات خاصة',

                'takes_medications'        => (bool) $this->patient->medicalHistory->takes_medications,
                'medications_details'      => $this->patient->medicalHistory->medications_details ?? 'لا يتناول أدوية',

                'has_allergies'            => (bool) $this->patient->medicalHistory->has_allergies,
                'allergies_details'        => $this->patient->medicalHistory->allergies_details ?? 'لا يوجد حساسية',
            ] : null,

            'diagnosis_details' => [
                'case_type'       => $this->caseType->name ?? 'N/A',
                'department'      => $this->department->name ?? 'N/A',
                'final_diagnosis' => $this->final_diagnosis,
                'case_status'     => $this->status,
            ],
            'attachments' => ($this->patient && $this->patient->media) ? $this->patient->media->map(function($mediaItem) {
                return [
                    'id'         => $mediaItem->id,
                    'url'        => $mediaItem->getUrl(),
                ];
            }) : [],
            'instructor_name' => trim(($this->instructor->first_name ?? 'N/A') . ' ' . ($this->instructor->last_name ?? '')),
            'created_at'      => $this->created_at ? $this->created_at->format('Y-m-d H:i') : null,
        ];
    }
}
