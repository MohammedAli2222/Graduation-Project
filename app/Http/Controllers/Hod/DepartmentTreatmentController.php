<?php

declare(strict_types=1);

namespace App\Http\Controllers\Hod;

use App\Http\Controllers\Controller;
use App\Http\Resources\Hod\TreatmentResource;
use App\Services\Hod\DepartmentHeadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

class DepartmentTreatmentController extends Controller
{
    public function __construct(
        protected DepartmentHeadService $hodService
    ) {}

    public function completedTreatments(Request $request): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            $departmentId = $user->departmentHeadProfile?->department_id
                         ?? $user->instructorProfile?->department_id;

            if (! $departmentId) {
                return response_error(null, 403, 'غير مصرح لك: لم يتم ربط حسابك بقسم أكاديمي.');
            }

            $page = (int) $request->query('page', 1);
            $perPage = (int) $request->query('per_page', 15);
            $groupId = $request->query('group_id') ? (int) $request->query('group_id') : null;

            $completedTreatments = $this->hodService->getCompletedTreatmentsForDepartment(
                $departmentId,
                $page,
                $perPage,
                $groupId
            );

            $resourceData = TreatmentResource::collection($completedTreatments)->response()->getData(true);

            return response_success(
                $resourceData,
                200,
                'تم جلب المعالجات السريرية بنجاح.'
            );

        } catch (Exception $e) {
            Log::error("خطأ أثناء جلب المعالجات لرئيس القسم أو المعيد المفوض: " . $e->getMessage());
            return response_error(null, 500, 'حدث خطأ داخلي أثناء معالجة البيانات.');
        }
    }

    public function show(int $treatmentId): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            $departmentId = $user->departmentHeadProfile?->department_id
                         ?? $user->instructorProfile?->department_id;

            if (! $departmentId) {
                return response_error(null, 403, 'غير مصرح لك: لم يتم ربط حسابك بقسم أكاديمي.');
            }

            $treatment = $this->hodService->getTreatmentDetailForDepartment(
                $departmentId,
                $treatmentId
            );

            return response_success(new TreatmentResource($treatment), 200, 'تم جلب تفاصيل المعالجة بنجاح.');
        } catch (Exception $e) {
            $statusCode = ($e->getCode() >= 400 && $e->getCode() <= 500) ? $e->getCode() : 500;

            if ($statusCode === 500) {
                Log::error("خطأ داخلي أثناء جلب تفاصيل معالجة: " . $e->getMessage());
                return response_error(null, 500, 'حدث خطأ داخلي أثناء معالجة الطلب.');
            }

            return response_error(null, $statusCode, $e->getMessage());
        }
    }
}
