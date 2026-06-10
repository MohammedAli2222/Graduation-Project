<?php

declare(strict_types=1);

namespace App\Services\hod;

use App\Enums\TreatmentStatus;
use App\Repositories\Contracts\TreatmentRepositoryInterface;
use App\Repositories\Contracts\CaseTypeRepositoryInterface;
use App\Repositories\Contracts\InstructorRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Exception;

class DepartmentHeadService
{
    /**
     * حقن واجهات المستودعات المطلوبة للخدمة.
     */
    public function __construct(
        protected TreatmentRepositoryInterface $treatmentRepo,
        protected CaseTypeRepositoryInterface $caseTypeRepo,
        protected InstructorRepositoryInterface $instructorRepo
    ) {}

    /**
     * جلب المعالجات المكتملة لقسم معين.
     */
    public function getCompletedTreatmentsForDepartment(int $departmentId, int $page, int $perPage = 15): LengthAwarePaginator
    {
        $cacheKey = "department_{$departmentId}_completed_treatments_page_{$page}_limit_{$perPage}";

        return Cache::tags(["department_{$departmentId}", 'treatments'])->remember(
            $cacheKey,
            now()->addMinutes(15),
            fn() => $this->treatmentRepo->getOptimizedCompletedTreatments($departmentId, $perPage)
        );
    }

    /**
     * جلب قائمة أنواع الحالات التابعة للقسم.
     */
    public function getCaseTypesForDepartment(int $departmentId): Collection
    {
        return Cache::tags(["department_{$departmentId}", 'case_types'])->remember(
            "department_{$departmentId}_case_types",
            now()->addHours(2),
            fn() => $this->caseTypeRepo->getCaseTypesByDepartment($departmentId)
        );
    }

    /**
     * تحديث عدد الحالات المطلوبة.
     */
    public function updateCaseRequirement(int $hodDepartmentId, int $caseTypeId, int $newCount): bool
    {
        $caseType = $this->caseTypeRepo->findByIdWithCourse($caseTypeId);

        if (! $caseType) {
            throw new Exception('نوع الحالة السريرية غير موجود.', 404);
        }

        if ($caseType->course->department_id !== $hodDepartmentId) {
            throw new Exception('غير مصرح لك: لا يمكنك تعديل متطلبات حالة سريرية تتبع لقسم آخر.', 403);
        }

        $isUpdated = $this->caseTypeRepo->updateRequiredCount($caseType, $newCount);

        if ($isUpdated) {
            Cache::tags(["department_{$hodDepartmentId}", 'case_types'])->flush();
        }

        return $isUpdated;
    }

    /**
     * جلب الإحصائيات الشاملة للقسم.
     */
    public function getDepartmentStatistics(int $departmentId): array
    {
        $cacheKey = "department_{$departmentId}_statistics";

        return Cache::tags(["department_{$departmentId}", 'statistics'])->remember(
            $cacheKey,
            now()->addMinutes(30),
            function () use ($departmentId) {
                $rawStats = $this->treatmentRepo->getDepartmentTreatmentStatistics($departmentId);
                $formattedStats = [];
                $total = 0;

                foreach (TreatmentStatus::cases() as $status) {
                    $count = $rawStats[$status->value] ?? 0;
                    $formattedStats[$status->value] = $count;
                    $total += $count;
                }

                $formattedStats['total_treatments'] = $total;
                return $formattedStats;
            }
        );
    }

    /**
     * جلب قائمة المعيدين لكي يختار منهم رئيس القسم من يريد تفويضه.
     */
    public function getInstructorsList(): Collection
    {
        return $this->instructorRepo->getAllInstructors();
    }

    /**
     * تفويض صلاحية استعراض حالات القسم لمعيد معين ديناميكياً.
     */
    public function delegateViewTreatmentsToInstructor(int $instructorId): bool
    {
        $instructor = $this->instructorRepo->findInstructorById($instructorId);

        if (! $instructor) {
            throw new Exception('المعيد المطلوب غير موجود في النظام.', 404);
        }

        $permission = Permission::firstOrCreate([
            'name' => 'view-department-treatments',
            'guard_name' => 'web'
        ]);

        // منح الصلاحية للمعيد
        if (! $instructor->hasPermissionTo($permission)) {
            $instructor->givePermissionTo($permission);
        }

        return true;
    }

    /**
     * سحب صلاحية استعراض حالات القسم من معيد معين.
     */
    public function revokeViewTreatmentsFromInstructor(int $instructorId): bool
    {
        $instructor = $this->instructorRepo->findInstructorById($instructorId);

        if (! $instructor) {
            throw new Exception('المعيد المطلوب غير موجود في النظام.', 404);
        }

        if ($instructor->hasPermissionTo('view-department-treatments')) {
            $instructor->revokePermissionTo('view-department-treatments');
        }

        return true;
    }
}
