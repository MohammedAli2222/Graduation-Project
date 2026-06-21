<?php

namespace App\Http\Resources\PatientHistory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HistoryAppointmentResource extends JsonResource
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
            'status' => $this->status,
            'appointment_date' => $this->appointment_date->format('Y-m-d'),
            'slot_time' => $this->getSlotTimeRange(),
        ];
    }
}
