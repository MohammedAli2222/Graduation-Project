<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TreatmentListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $firstAppointment = $this->diagnosis?->appointments?->first();

        $student = $firstAppointment?->student;
        $patient = $firstAppointment?->patient;

        return [
            'id' => $this->id,
            'status' => $this->status,
            'start_date' => $this->start_date ? $this->start_date->toDateTimeString() : null,
            'student_name' => $student ? ($student->first_name.' '.$student->last_name) : 'N/A',
            'patient_name' => $patient->full_name ?? 'N/A',
            'case_type' => $this->diagnosis->CaseType->name ?? 'N/A',
        ];
    }
}
