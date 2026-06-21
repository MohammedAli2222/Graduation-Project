<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'appointment_id' => $this->id,
            'status' => $this->status->value,
            'appointment_date' => $this->appointment_date->format('Y-m-d'),

            'slot_number' => $this->slot_number,

            'slot_time' => $this->getSlotTimeRange(),
            'patient' => [
                'id' => $this->patient->id ?? null,
                'name' => $this->patient->full_name ?? 'N/A',
            ],

            'treatment_id' => $this->treatment_id,

            'diagnosis_id' => $this->diagnosis_id,

            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
