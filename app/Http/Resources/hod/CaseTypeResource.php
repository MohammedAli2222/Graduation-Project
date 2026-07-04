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

            'course'         => $this->whenLoaded('course', function () {
                return [
                    'id'   => $this->course->id,
                    'name' => $this->course->name,
                ];
            }),
        ];
    }
}
