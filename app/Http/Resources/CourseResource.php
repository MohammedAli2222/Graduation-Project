<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'year'        => $this->year,
            'semester'    => $this->semester,
            'attempts_count' => $this->attempts_count ?? 1,
            'department'  => [
                'id'   => $this->department->id,
                'name' => $this->department->name,
            ],

            'required_cases' => $this->caseTypes->map(function($type) {
                return [
                    'id' => $type->id,
                    'name' => $type->name,
                    'required_count' => $type->required_count, 
                ];
            }),
        ];
    }
}
