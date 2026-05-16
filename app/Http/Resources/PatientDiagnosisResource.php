<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientDiagnosisResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'patient_info' => [
                'id' => $this->patient_id,
                'name' => $this->patient->full_name ?? 'N/A',
            ],
            'diagnosis_details' => [
                'case_type' => $this->caseType->name ?? 'N/A',
                'department' => $this->department->name ?? 'N/A',
                'final_diagnosis' => $this->final_diagnosis,
            ],
            'instructor_name' => ($this->instructor->first_name ?? 'N/A') . ' ' . ($this->instructor->last_name ?? ''),
            'status' => $this->status,
            'created_at' => $this->created_at->format('Y-m-d H:i'),
        ];
    }
}
