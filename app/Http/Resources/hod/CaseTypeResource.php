<?php

declare(strict_types=1);

namespace App\Http\Resources\hod;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CaseTypeResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'required_count' => $this->required_count,

            'completed_count'          => (int) ($this->completed_count ?? 0),
            'students_met_requirement' => (int) ($this->students_met_requirement ?? 0),
            'total_students'           => (int) ($this->total_students ?? 0),
            'progress_percentage'      => (int) ($this->progress_percentage ?? 0),

            'course'         => $this->whenLoaded('course', function () {
                return [
                    'id'   => $this->course->id,
                    'name' => $this->course->name,
                ];
            }),
        ];
    }
}
