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
        $isFollowUp = $this->treatment_id && ($this->id != $this->first_appointment_id);

        return [
            'appointment_id'   => $this->id,
            'status'           => $this->status->value,
            'appointment_date' => $this->appointment_date->format('Y-m-d'),

            'start_slot'       => $this->start_slot,
            'end_slot'         => $this->end_slot,
            'slots_count'      => $this->slots_count,

            'slot_time'        => $this->getSlotTimeRange(),

            'is_follow_up'     => (bool) $isFollowUp,
            'type_label'       => $isFollowUp ? '(Follow-up)' : '(Primary)',

            'patient' => [
                'id'   => $this->patient->id ?? null,
                'name' => $this->patient->full_name ?? 'N/A',
            ],

            'treatment' => $this->when($this->treatment_id, function () {
                return [
                    'id'     => $this->treatment_id,
                    'status' => $this->treatment->status->value ?? $this->treatment->status ?? 'in_progress',
                ];
            }, null),
            'diagnosis' => [
                'id'   => $this->diagnosis_id,
                'name' => $this->diagnosis->caseType->name ?? 'غير محدد',
                'final_diagnosis' => $this->diagnosis->final_diagnosis ?? 'لا يوجد تشخيص نهائي',
            ],

            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
