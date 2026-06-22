<?php

declare(strict_types=1);

namespace App\Http\Controllers\Hod;

use App\Http\Controllers\Controller;
use App\Services\hod\DepartmentHeadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

class DepartmentStatisticController extends Controller
{
    /**
     * حقن خدمة رئيس القسم.
     */
    public function __construct(
        protected DepartmentHeadService $hodService
    ) {}


    public function index(): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            $hodProfile = $user->departmentHeadProfile;

            if (! $hodProfile) {
                return response_error(null, 403, 'غير مصرح لك: حسابك لا يملك صلاحيات رئيس قسم.');
            }

            $statistics = $this->hodService->getDepartmentStatistics($hodProfile->department_id);

            return response_success(
                $statistics,
                200,
                'تم جلب الإحصائيات الخاصة بالقسم بنجاح.'
            );

        } catch (Exception $e) {
            Log::error("خطأ أثناء جلب إحصائيات القسم لرئيس القسم: " . $e->getMessage());
            return response_error(null, 500, 'حدث خطأ داخلي أثناء معالجة البيانات.');
        }
    }
}
