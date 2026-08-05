<?php

namespace App\Http\Resources\PatientHistory;

use App\Enums\DiagnosisStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HistoryDiagnosisResource extends JsonResource
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
            'patient' => [
                'name' => $this->patient?->full_name,
                'gender' => $this->patient?->gender,
                'phone' => $this->patient?->phone,
            ],
            'clinical_details' => [
                'case_type' => $this->caseType?->name,
                'department' => $this->department?->name,
                'final_diagnosis' => $this->final_diagnosis,
            ],

            // سبب الرفض يظهر فقط عندما تكون حالة التشخيص "مرفوضة"
            'rejection_reason' => $this->when(
                $this->status === DiagnosisStatus::REJECTED,
                $this->rejection_reason
            ),

            'created_at' => $this->created_at?->format('Y-m-d H:i'),
        ];
    }
}
