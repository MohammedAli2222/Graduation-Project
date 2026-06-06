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
            'appointment_id'   => $this->id,
            'status'           => $this->status->value,
            'appointment_date' => $this->appointment_date->format('Y-m-d'),


            'slot_number'      => $this->slot_number,

            'slot_time'        => $this->getSlotTimeRange($this->slot_number),
            'patient' => [
                'id'   => $this->patient->id ?? null,
                'name' => $this->patient->full_name ?? 'N/A',
            ],

            'diagnosis_id' => $this->diagnosis_id,

            'created_at'   => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
    private function getSlotTimeRange(int $slotNumber): string
    {
        return match ($slotNumber) {
            1 => '08:00 AM - 10:00 AM',
            2 => '10:30 AM - 12:30 PM',
            3 => '01:00 PM - 03:00 PM',
            4 => '03:30 PM - 05:30 PM',
            default => 'Unknown Slot',
        };
    }
}
