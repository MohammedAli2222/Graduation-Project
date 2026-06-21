<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TreatmentResource extends JsonResource
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
            'diagnosis_id' => $this->diagnosis_id,
            'evaluating_instructor_id' => $this->instructor_id,
            'status' => $this->status,
            'rejection_reason' => $this->rejection_reason,
            'start_date' => $this->start_date ? $this->start_date->toDateTimeString() : null,
            'end_date' => $this->end_date ? $this->end_date->toDateTimeString() : null,

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

            'diagnosis' => new PatientDiagnosisResource($this->whenLoaded('diagnosis')),
            'appointments' => AppointmentResource::collection($this->whenLoaded('appointments')),
            'evaluating_instructor' => $this->instructor ? [
                'first_name' => $this->instructor->first_name,
                'last_name' => $this->instructor->last_name,
            ] : null,
        ];
    }
}
