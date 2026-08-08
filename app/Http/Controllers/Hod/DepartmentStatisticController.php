<?php

declare(strict_types=1);

namespace App\Http\Controllers\Hod;

use App\Http\Controllers\Controller;
use App\Services\Hod\DepartmentHeadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

class DepartmentStatisticController extends Controller
{
    public function __construct(
        protected DepartmentHeadService $hodService
    ) {}


    public function index(Request $request): JsonResponse
    {
        // نتحقق من course_id قبل أي منطق آخر: حقل اختياري، لكن إن وُجد يجب أن
        // يشير فعلاً لمقرر موجود في قاعدة البيانات. التحقق من ملكية هذا المقرر
        // لقسم رئيس القسم الحالي تحديداً يتم لاحقاً داخل طبقة الخدمة (Service)
        $validated = $request->validate([
            'course_id' => ['sometimes', 'nullable', 'integer', 'exists:courses,id'],
        ]);

        $courseId = $validated['course_id'] ?? null;

        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            $hodProfile = $user->departmentHeadProfile;

            if (! $hodProfile) {
                return response_error(null, 403, 'غير مصرح لك: حسابك لا يملك صلاحيات رئيس قسم.');
            }

            $statistics = $this->hodService->getDepartmentStatistics($hodProfile->department_id, $courseId);

            return response_success(
                $statistics,
                200,
                'تم جلب الإحصائيات الخاصة بالقسم بنجاح.'
            );

        } catch (Exception $e) {
            // نُميّز بين استثناءات مقصودة (403/404 مثلاً عند طلب مقرر تابع لقسم
            // آخر) وأخطاء داخلية حقيقية، تماماً كما في بقية دوال هذا الـ Controller
            $statusCode = ($e->getCode() >= 400 && $e->getCode() <= 500) ? $e->getCode() : 500;

            if ($statusCode === 500) {
                Log::error("خطأ أثناء جلب إحصائيات القسم لرئيس القسم: " . $e->getMessage());
                return response_error(null, 500, 'حدث خطأ داخلي أثناء معالجة البيانات.');
            }

            return response_error(null, $statusCode, $e->getMessage());
        }
    }
}
