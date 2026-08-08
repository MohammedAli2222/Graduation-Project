<?php

declare(strict_types=1);

namespace App\Http\Resources\Hod;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TreatmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,

            'status'           => $this->status->value ?? $this->status,
            'start_date'       => $this->start_date?->format('Y-m-d H:i:s'),
            'end_date'         => $this->end_date?->format('Y-m-d H:i:s'),
            'instructor_notes' => $this->instructor_notes,
            'rejection_reason' => $this->rejection_reason,

            'diagnosis' => $this->whenLoaded('diagnosis', function () {
                return [
                    'id' => $this->diagnosis_id,
                    'case_type' => [
                        'id'   => $this->diagnosis->caseType?->id,
                        'name' => $this->diagnosis->caseType?->name,
                    ],
                    'patient' => [
                        'id'         => $this->diagnosis->patient?->id,
                        'first_name' => $this->diagnosis->patient?->first_name,
                        'last_name'  => $this->diagnosis->patient?->last_name,
                    ],
                    'student' => [
                        'id'         => $this->diagnosis->student?->id,
                        'first_name' => $this->diagnosis->student?->first_name,
                        'last_name'  => $this->diagnosis->student?->last_name,
                    ],
                ];
            }),

            'instructor' => $this->whenLoaded('instructor', function () {
                return [
                    'id'         => $this->instructor->id,
                    'first_name' => $this->instructor->first_name,
                    'last_name'  => $this->instructor->last_name,
                ];
            }),
        ];
    }
}
