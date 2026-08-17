<?php

declare(strict_types=1);

namespace App\Services\Hod;

use App\Enums\TreatmentStatus;
use App\Events\InstructorDelegatedEvent;
use App\Models\CaseType;
use App\Models\Course;
use App\Repositories\Contracts\TreatmentRepositoryInterface;
use App\Repositories\Contracts\CaseTypeRepositoryInterface;
use App\Repositories\Contracts\InstructorRepositoryInterface;
use App\Support\CacheVersion;
use Illuminate\Contracts\Pagination\Paginator;
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

    public function getCompletedTreatmentsForDepartment(int $departmentId, int $page, int $perPage = 15): Paginator
    {
        $cacheKey = "department_{$departmentId}_completed_treatments_page_{$page}_limit_{$perPage}";

        return Cache::remember(
            CacheVersion::taggedKey(["department_{$departmentId}", 'treatments'], $cacheKey),
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

    public function getCaseTypesForDepartment(int $departmentId, ?int $courseId = null): Collection
    {
        // نفس تحقق ملكية المقرر المستخدَم في getDepartmentStatistics: يمنع
        // الاطلاع على أنواع حالات مقرر تابع لقسم آخر
        if ($courseId !== null) {
            $course = Course::find($courseId);

            if (! $course) {
                throw new Exception('المقرر الدراسي غير موجود.', 404);
            }

            if ($course->department_id !== $departmentId) {
                throw new Exception('غير مصرح لك: هذا المقرر تابع لقسم آخر.', 403);
            }
        }

        // مفتاح الكاش يتضمن معرف المقرر (أو 'all' في حال غيابه) حتى لا تُخلَط
        // نتائج أنواع حالات القسم كاملاً مع أنواع حالات مقرر محدد ضمنه
        $cacheKey = "department_{$departmentId}_case_types_course_" . ($courseId ?? 'all');

        return Cache::remember(
            CacheVersion::taggedKey(["department_{$departmentId}", 'case_types'], $cacheKey),
            now()->addHours(2),
            fn() => $this->caseTypeRepo->getCaseTypesByDepartment($departmentId, $courseId)
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

        CacheVersion::bump("department_{$hodDepartmentId}", 'case_types');

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
            CacheVersion::bump("department_{$hodDepartmentId}", 'case_types');
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
            CacheVersion::bump("department_{$hodDepartmentId}", 'case_types');
        }

        return $isUpdated;
    }

    public function getDepartmentStatistics(int $departmentId, ?int $courseId = null): array
    {
        // إن تم تمرير معرف مقرر، نتحقق أنه موجود فعلاً وأنه تابع لنفس قسم رئيس
        // القسم الحالي، لمنع الاطلاع على إحصائيات مقرر ينتمي لقسم آخر
        if ($courseId !== null) {
            $course = Course::find($courseId);

            if (! $course) {
                throw new Exception('المقرر الدراسي غير موجود.', 404);
            }

            if ($course->department_id !== $departmentId) {
                throw new Exception('غير مصرح لك: هذا المقرر تابع لقسم آخر.', 403);
            }
        }

        // مفتاح الكاش يتضمن معرف المقرر (أو 'all' في حال غيابه) حتى لا تُخلَط
        // نتائج إحصائيات القسم كاملاً مع إحصائيات مقرر محدد ضمنه
        $cacheKey = "department_{$departmentId}_statistics_course_" . ($courseId ?? 'all');

        return Cache::remember(
            CacheVersion::taggedKey(["department_{$departmentId}", 'statistics'], $cacheKey),
            now()->addMinutes(30),
            function () use ($departmentId, $courseId) {
                $rawStats = $this->treatmentRepo->getDepartmentTreatmentStatistics($departmentId, $courseId);
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

    /**
     * معيدون لديهم طلب تفويض معلَّق موجَّه تحديداً لقسم رئيس القسم الحالي.
     */
    public function getDelegationRequests(int $hodDepartmentId): Collection
    {
        return $this->instructorRepo->getPendingDelegationRequests($hodDepartmentId);
    }

    /**
     * منح صلاحية عرض معالجات القسم لمعيد.
     *
     * مسارَان يبقيان معاً صالحَين:
     * - المعيد طلب الانضمام (requested_department_id مضبوط): لا يجوز قبول
     *   الطلب إلا من رئيس القسم المطلوب تحديداً؛ عزل بيانات صارم.
     * - منح مباشر من رئيس القسم بلا طلب سابق (السلوك الأصلي قبل هذه الميزة):
     *   يبقى يعمل كما كان، ويُسجَّل المعيد ضمن قسم رئيس القسم المانِح.
     */
    public function delegateViewTreatmentsToInstructor(int $instructorId, int $hodDepartmentId): bool
    {
        $instructor = $this->instructorRepo->findInstructorById($instructorId);

        if (! $instructor) {
            throw new Exception('المعيد المطلوب غير موجود في النظام.', 404);
        }

        $profile = $instructor->instructorProfile;
        $requestedDepartmentId = $profile?->requested_department_id;

        if ($requestedDepartmentId !== null && (int) $requestedDepartmentId !== $hodDepartmentId) {
            throw new Exception('غير مصرح لك: هذا الطلب موجَّه إلى قسم آخر.', 403);
        }

        $permission = Permission::firstOrCreate([
            'name' => 'view-department-treatments',
            'guard_name' => 'web'
        ]);

        if (! $instructor->hasPermissionTo($permission)) {
            $instructor->givePermissionTo($permission);
        }

        if ($profile) {
            $profile->update([
                'department_id' => $requestedDepartmentId ?? $hodDepartmentId,
                'requested_department_id' => null,
            ]);
        }

        // إطلاق الحدث لإشعار المعيد بحصوله على صلاحية جديدة دون تأخير استجابة الـ API الرئيسية
       // InstructorDelegatedEvent::dispatch($instructor);

        return true;
    }

    /**
     * رفض طلب تفويض معلَّق. لا يجوز إلا لرئيس القسم الذي وُجِّه إليه الطلب،
     * ولا معنى للرفض أصلاً إن لم يوجد طلب معلَّق فعلي (عزل بيانات + تحقّق حالة).
     */
    public function rejectDelegationRequest(int $instructorId, int $hodDepartmentId): bool
    {
        $instructor = $this->instructorRepo->findInstructorById($instructorId);

        if (! $instructor || ! $instructor->instructorProfile) {
            throw new Exception('المعيد المطلوب غير موجود في النظام.', 404);
        }

        $profile = $instructor->instructorProfile;

        if ($profile->requested_department_id === null || (int) $profile->requested_department_id !== $hodDepartmentId) {
            throw new Exception('لا يوجد طلب تفويض معلَّق من هذا المعيد لقسمك.', 404);
        }

        $profile->update(['requested_department_id' => null]);

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
