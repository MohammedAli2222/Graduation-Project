<?php

declare(strict_types=1);

namespace App\Http\Controllers\Hod;

use App\Http\Controllers\Controller;
use App\Services\Hod\DepartmentHeadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Exception;

class DepartmentDelegationController extends Controller
{
    public function __construct(
        protected DepartmentHeadService $hodService
    ) {}

    // القائمة مقصورة على المعيدين الذين قدّموا فعلاً طلب صلاحية موجَّه لقسم رئيس
    // القسم الحالي تحديداً (نفس معيار delegationRequests)، لا كل معيدي النظام
    public function instructorsList(): JsonResponse
    {
        try {
            $hodProfile = auth()->user()->departmentHeadProfile;

            if (! $hodProfile) {
                return response_error(null, 403, 'غير مصرح لك: حسابك لا يملك صلاحيات رئيس قسم.');
            }

            $instructors = $this->hodService->getInstructorsList($hodProfile->department_id);

            return response_success(
                $instructors,
                200,
                'تم جلب قائمة المعيدين بنجاح.'
            );
        } catch (Exception $e) {
            Log::error("خطأ أثناء جلب قائمة المعيدين: " . $e->getMessage());
            return response_error(null, 500, 'حدث خطأ داخلي أثناء معالجة البيانات.');
        }
    }

    public function delegationRequests(): JsonResponse
    {
        try {
            $hodProfile = auth()->user()->departmentHeadProfile;

            if (! $hodProfile) {
                return response_error(null, 403, 'غير مصرح لك: حسابك لا يملك صلاحيات رئيس قسم.');
            }

            $requests = $this->hodService->getDelegationRequests($hodProfile->department_id);

            return response_success(
                $requests,
                200,
                'تم جلب طلبات التفويض المعلَّقة بنجاح.'
            );
        } catch (Exception $e) {
            Log::error("خطأ أثناء جلب طلبات التفويض: " . $e->getMessage());
            return response_error(null, 500, 'حدث خطأ داخلي أثناء معالجة البيانات.');
        }
    }

    public function grantPermission(int $instructorId): JsonResponse
    {
        try {
            $hodProfile = auth()->user()->departmentHeadProfile;

            if (! $hodProfile) {
                return response_error(null, 403, 'غير مصرح لك: حسابك لا يملك صلاحيات رئيس قسم.');
            }

            $this->hodService->delegateViewTreatmentsToInstructor($instructorId, $hodProfile->department_id);

            return response_success(
                null,
                200,
                'تم تفويض الصلاحية للمعيد بنجاح.'
            );
        } catch (Exception $e) {
            $statusCode = in_array($e->getCode(), [403, 404], true) ? $e->getCode() : 500;

            if ($statusCode === 500) {
                Log::error("خطأ أثناء منح الصلاحية للمعيد: " . $e->getMessage());
                return response_error(null, 500, 'حدث خطأ داخلي أثناء معالجة الطلب.');
            }

            return response_error(null, $statusCode, $e->getMessage());
        }
    }

    public function rejectRequest(int $instructorId): JsonResponse
    {
        try {
            $hodProfile = auth()->user()->departmentHeadProfile;

            if (! $hodProfile) {
                return response_error(null, 403, 'غير مصرح لك: حسابك لا يملك صلاحيات رئيس قسم.');
            }

            $this->hodService->rejectDelegationRequest($instructorId, $hodProfile->department_id);

            return response_success(
                null,
                200,
                'تم رفض طلب التفويض.'
            );
        } catch (Exception $e) {
            $statusCode = ($e->getCode() === 404) ? 404 : 500;

            if ($statusCode === 500) {
                Log::error("خطأ أثناء رفض طلب التفويض: " . $e->getMessage());
                return response_error(null, 500, 'حدث خطأ داخلي أثناء معالجة الطلب.');
            }

            return response_error(null, $statusCode, $e->getMessage());
        }
    }

    public function revokePermission(int $instructorId): JsonResponse
    {
        try {
            $this->hodService->revokeViewTreatmentsFromInstructor($instructorId);

            return response_success(
                null,
                200,
                'تم سحب الصلاحية من المعيد بنجاح.'
            );
        } catch (Exception $e) {
            $statusCode = ($e->getCode() === 404) ? 404 : 500;

            if ($statusCode === 500) {
                Log::error("خطأ أثناء سحب الصلاحية من المعيد: " . $e->getMessage());
                return response_error(null, 500, 'حدث خطأ داخلي أثناء معالجة الطلب.');
            }

            return response_error(null, $statusCode, $e->getMessage());
        }
    }
}
