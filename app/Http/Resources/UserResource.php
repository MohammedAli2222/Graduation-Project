<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $role = $this->getRoleNames()->first();

        $relation = $this->getProfileRelationName($role);

        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'role' => $role,
            'joined_at' => $this->created_at->format('Y-m-d h:i A'),

            'profile' => $this->when($relation && $this->relationLoaded($relation), function () use ($relation) {
                $profileData = $this->{$relation};

                if ($relation === 'departmentHeadProfile' && $profileData) {
                    return [
                        'id' => $profileData->id,
                        'department' => [
                            'id' => $profileData->department->id ?? null,
                            'name' => $profileData->department->name ?? 'N/A',
                        ],
                    ];
                }

                if ($relation === 'instructorProfile' && $profileData) {
                    return [
                        'id' => $profileData->id,
                        'phone' => $profileData->phone,
                        'specialty' => $profileData->specialty,
                        'specialty_year' => $profileData->specialty_year,
                        'groups' => $profileData->groups->map(function ($group) {
                            $yearText = $group->academic_year === 4 ? 'السنة الرابعة' : 'السنة الخامسة';

                            return $group->group_name.' - '.$yearText;
                        }),
                    ];
                }

                if ($relation === 'studentProfile' && $profileData) {
                    return [
                        'id' => $profileData->id,
                        'exam_number' => $profileData->exam_number,
                        'university' => $profileData->university,
                        'academic_year' => $profileData->academic_year,
                        'semester' => $profileData->semester,
                        'phone' => $profileData->phone,

                        'group_name' => $profileData->group->group_name ?? 'غير محدد',

                        'created_at' => $profileData->created_at->format('Y-m-d h:i A'),
                    ];
                }

                if ($relation === 'storeProfile' && $profileData) {
                    return [
                        'id' => $profileData->id,
                        'name' => $profileData->store_name,
                        'phone' => $profileData->store_phone,
                        'address' => $profileData->store_address,

                        'created_at' => $profileData->created_at->format('Y-m-d h:i A'),
                    ];
                }

                return $profileData;
            }),
        ];
    }


}
