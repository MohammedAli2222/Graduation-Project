<?php

declare(strict_types=1);

namespace App\Http\Controllers\Hod;

use App\Http\Controllers\Controller;
use App\Http\Resources\hod\TreatmentResource;
use App\Services\hod\DepartmentHeadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

class DepartmentTreatmentController extends Controller
{
    /**
     * حقن خدمة رئيس القسم.
     */
    public function __construct(
        protected DepartmentHeadService $hodService
    ) {}

    /**
     * استعراض المعالجات المنجزة بأقصى أداء (متاح لـ HOD وللمعيد المفوض).
     *
     * @param Request $request
     * @return JsonResponse
     */
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

            $completedTreatments = $this->hodService->getCompletedTreatmentsForDepartment(
                $departmentId,
                $page,
                $perPage
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
}
