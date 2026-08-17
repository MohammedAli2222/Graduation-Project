<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentCourseStatusResource extends JsonResource
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
            'name' => $this->name,
            'year' => $this->year,
            'semester' => $this->semester,
            'department' => [
                'id' => $this->department->id,
                'name' => $this->department->name,
            ],

            'required_cases' => $this->caseTypes->map(function ($type) {
                return [
                    'id' => $type->id,
                    'name' => $type->name,
                    'required_count' => $type->required_count,
                ];
            }),

            'is_enrolled' => $this->is_enrolled,
            'can_enroll' => $this->can_enroll,
            'can_drop' => $this->can_drop,
            'lock_reason' => $this->lock_reason,
        ];
    }
}
