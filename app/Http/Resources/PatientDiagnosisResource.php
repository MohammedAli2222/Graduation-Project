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
            'diagnosis_id' => $this->id,
            'patient_info' => [
                'id' => $this->patient_id,
                'name' => $this->patient->full_name ?? 'N/A',
                'gender' => $this->patient->gender ?? 'N/A',
                'phone' => $this->patient->phone ?? 'N/A',
                'birth_date' => $this->patient->birth_date ?? 'N/A',
                'address' => $this->patient->address ?? 'N/A',
            ],
            'diagnosis_details' => [
                'case_type' => $this->caseType->name ?? 'N/A',
                'department' => $this->department->name ?? 'N/A',
                'final_diagnosis' => $this->final_diagnosis,
                'status' => $this->status,
                'estimated_cost' =>$this->estimated_cost
            ],
            'diagnostic_instructor_name' => ($this->instructor->first_name ?? 'N/A') . ' ' . ($this->instructor->last_name ?? ''),
            'created_at' => $this->created_at->format('Y-m-d H:i'),
        ];
    }
}
