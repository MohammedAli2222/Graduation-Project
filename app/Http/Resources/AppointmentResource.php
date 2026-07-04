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

            // قمنا بتعديل هذه الحقول لتناسب الهيكلية الجديدة
            'start_slot'       => $this->start_slot,
            'end_slot'         => $this->end_slot,
            'slots_count'      => $this->slots_count,

            // نستخدم دالة getSlotTimeRange التي حدثناها في الموديل
            'slot_time'        => $this->getSlotTimeRange(),

            'patient' => [
                'id'   => $this->patient->id ?? null,
                'name' => $this->patient->full_name ?? 'N/A',
            ],

            'treatment_id' => $this->treatment_id,
            'diagnosis' => [
                'id'   => $this->diagnosis_id,
                'name' => $this->diagnosis->caseType->name ?? 'غير محدد',
                'final_diagnosis' => $this->diagnosis->final_diagnosis ?? 'لا يوجد تشخيص نهائي',
            ],

            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
