<?php

namespace App\Http\Resources\PatientHistory;

use App\Enums\TreatmentStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientTreatmentHistoryResource extends JsonResource
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

            'rejection_reason' => $this->when(
                $this->status === TreatmentStatus::REJECTED,
                $this->rejection_reason
            ),

            'start_date' => $this->start_date->format('Y-m-d H:i'),
            'end_date' => $this->end_date,
            'before_images' => $this->getMedia('before_treatment_images')->map(function ($media) {
                return [
                    'id' => $media->id,
                    'file_name' => $media->file_name,
                    'url' => $media->getUrl(),
                ];
            }),

            'after_images' => $this->getMedia('after_treatment_images')->map(function ($media) {
                return [
                    'id' => $media->id,
                    'file_name' => $media->file_name,
                    'url' => $media->getUrl(),
                ];
            }),

            'diagnosis' => new HistoryDiagnosisResource($this->whenLoaded('diagnosis')),
            'appointments' => HistoryAppointmentResource::collection($this->whenLoaded('appointments')),
        ];
    }
}
