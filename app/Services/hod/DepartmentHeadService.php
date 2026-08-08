<?php

declare(strict_types=1);

namespace App\Services\hod;

use App\Enums\TreatmentStatus;
use App\Models\CaseType;
use App\Models\Course;
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
    public function __construct(
        protected TreatmentRepositoryInterface $treatmentRepo,
        protected CaseTypeRepositoryInterface $caseTypeRepo,
        protected InstructorRepositoryInterface $instructorRepo
    ) {}

    public function getCompletedTreatmentsForDepartment(int $departmentId, int $page, int $perPage = 15): LengthAwarePaginator
    {
        $cacheKey = "department_{$departmentId}_completed_treatments_page_{$page}_limit_{$perPage}";

        return Cache::tags(["department_{$departmentId}", 'treatments'])->remember(
            $cacheKey,
            now()->addMinutes(15),
            fn() => $this->treatmentRepo->getOptimizedCompletedTreatments($departmentId, $perPage)
        );
    }

    public function getTreatmentDetailForDepartment(int $hodDepartmentId, int $treatmentId): \App\Models\Treatment
    {
        $treatment = $this->treatmentRepo->findDetailedForDepartment($treatmentId);

        if (! $treatment) {
            throw new Exception('المعالجة غير موجودة.', 404);
        }

        if ($treatment->diagnosis?->department_id !== $hodDepartmentId) {
            throw new Exception('غير مصرح لك: هذه المعالجة تتبع لقسم آخر.', 403);
        }

        return $treatment;
    }

    public function getCaseTypesForDepartment(int $departmentId): Collection
    {
        return Cache::tags(["department_{$departmentId}", 'case_types'])->remember(
            "department_{$departmentId}_case_types",
            now()->addHours(2),
            fn() => $this->caseTypeRepo->getCaseTypesByDepartment($departmentId)
        );
    }

    public function getCoursesForDepartment(int $departmentId): Collection
    {
        return Course::query()
            ->where('department_id', $departmentId)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function createCaseType(int $hodDepartmentId, array $data): CaseType
    {
        $course = Course::find($data['course_id']);

        if (! $course) {
            throw new Exception('المقرر الدراسي غير موجود.', 404);
        }

        if ($course->department_id !== $hodDepartmentId) {
            throw new Exception('غير مصرح لك: لا يمكنك إضافة حالة سريرية لمقرر تابع لقسم آخر.', 403);
        }

        $caseType = $this->caseTypeRepo->create([
            'name' => $data['name'],
            'course_id' => $data['course_id'],
            'required_count' => $data['required_count'] ?? 1,
        ]);

        Cache::tags(["department_{$hodDepartmentId}", 'case_types'])->flush();

        return $caseType;
    }

    public function deleteCaseType(int $hodDepartmentId, int $caseTypeId): bool
    {
        $caseType = $this->caseTypeRepo->findByIdWithCourse($caseTypeId);

        if (! $caseType) {
            throw new Exception('نوع الحالة السريرية غير موجود.', 404);
        }

        if ($caseType->course->department_id !== $hodDepartmentId) {
            throw new Exception('غير مصرح لك: لا يمكنك حذف حالة سريرية تتبع لقسم آخر.', 403);
        }

        try {
            $isDeleted = $this->caseTypeRepo->delete($caseType);
        } catch (\Illuminate\Database\QueryException $e) {
            throw new Exception('لا يمكن حذف نوع الحالة لوجود حالات مرضية مرتبطة به.', 409);
        }

        if ($isDeleted) {
            Cache::tags(["department_{$hodDepartmentId}", 'case_types'])->flush();
        }

        return $isDeleted;
    }

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

    public function getInstructorsList(): Collection
    {
        return $this->instructorRepo->getAllInstructors();
    }

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

        if (! $instructor->hasPermissionTo($permission)) {
            $instructor->givePermissionTo($permission);
        }

        return true;
    }

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
