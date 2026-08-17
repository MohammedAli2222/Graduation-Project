<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Exception;

class InstructorDelegationService
{
    public function requestDelegation(User $user, int $departmentId): void
    {
        $instructor = $user->instructorProfile;

        if (! $instructor) {
            throw new Exception('لم يتم العثور على ملفك الأكاديمي كمعيد.', 404);
        }

        if ((int) $instructor->department_id === $departmentId) {
            throw new Exception('أنت بالفعل ضمن هذا القسم.', 422);
        }

        $instructor->update(['requested_department_id' => $departmentId]);
    }
}
