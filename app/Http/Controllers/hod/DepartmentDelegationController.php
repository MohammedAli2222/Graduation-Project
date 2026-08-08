<?php

declare(strict_types=1);

namespace App\Http\Controllers\Hod;

use App\Http\Controllers\Controller;
use App\Services\hod\DepartmentHeadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Exception;

class DepartmentDelegationController extends Controller
{
    public function __construct(
        protected DepartmentHeadService $hodService
    ) {}

    public function instructorsList(): JsonResponse
    {
        try {
            $instructors = $this->hodService->getInstructorsList();

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

    public function grantPermission(int $instructorId): JsonResponse
    {
        try {
            $this->hodService->delegateViewTreatmentsToInstructor($instructorId);

            return response_success(
                null,
                200,
                'تم تفويض الصلاحية للمعيد بنجاح.'
            );
        } catch (Exception $e) {
            $statusCode = ($e->getCode() === 404) ? 404 : 500;

            if ($statusCode === 500) {
                Log::error("خطأ أثناء منح الصلاحية للمعيد: " . $e->getMessage());
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
