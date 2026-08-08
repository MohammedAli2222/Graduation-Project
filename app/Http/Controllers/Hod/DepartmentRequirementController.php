<?php

declare(strict_types=1);

namespace App\Http\Controllers\Hod;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hod\StoreCaseTypeRequest;
use App\Http\Requests\Hod\UpdateCaseRequirementRequest;
use App\Http\Resources\Hod\CaseTypeResource;
use App\Services\Hod\DepartmentHeadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

class DepartmentRequirementController extends Controller
{
    public function __construct(
        protected DepartmentHeadService $hodService
    ) {}

    public function update(UpdateCaseRequirementRequest $request, int $caseTypeId): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            $hodProfile = $user->departmentHeadProfile;

            if (! $hodProfile) {
                return response_error(null, 403, 'غير مصرح لك: حسابك لا يملك صلاحيات رئيس قسم.');
            }

            $validated = $request->validated();

            $this->hodService->updateCaseRequirement(
                $hodProfile->department_id,
                $caseTypeId,
                (int) $validated['required_count']
            );

            return response_success(
                null,
                200,
                'تم تحديث عدد الحالات المطلوبة بنجاح.'
            );
        } catch (Exception $e) {
            $statusCode = ($e->getCode() >= 400 && $e->getCode() <= 500) ? $e->getCode() : 500;

            if ($statusCode === 500) {
                Log::error("خطأ داخلي أثناء تحديث متطلبات القسم: " . $e->getMessage());
                return response_error(null, 500, 'حدث خطأ داخلي أثناء معالجة الطلب.');
            }

            return response_error(null, $statusCode, $e->getMessage());
        }
    }


    public function indexCaseTypes(Request $request): JsonResponse
    {
        // نفس منطق endpoint الإحصائيات: course_id اختياري، وإن وُجد نتحقق من
        // صيغته هنا فقط (integer وموجود فعلاً)، بينما التحقق من ملكيته لقسم
        // رئيس القسم الحالي يتم داخل طبقة الخدمة
        $validated = $request->validate([
            'course_id' => ['sometimes', 'nullable', 'integer', 'exists:courses,id'],
        ]);

        // نُحوّل الناتج صراحةً إلى int لأن قاعدة integer في validate() تتحقق من
        // الصيغة فقط ولا تُبدّل نوع القيمة، وقيم الاستعلام تصل دائماً كنصوص
        $courseId = isset($validated['course_id']) ? (int) $validated['course_id'] : null;

        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            $hodProfile = $user->departmentHeadProfile;

            if (! $hodProfile) {
                return response_error(null, 403, 'غير مصرح لك: حسابك لا يملك صلاحيات رئيس قسم.');
            }

            $caseTypes = $this->hodService->getCaseTypesForDepartment($hodProfile->department_id, $courseId);

            return response_success(
                CaseTypeResource::collection($caseTypes),
                200,
                'تم جلب أنواع الحالات التابعة لقسمك بنجاح.'
            );
        } catch (Exception $e) {
            // نُميّز بين استثناءات مقصودة (403/404 عند طلب مقرر تابع لقسم آخر)
            // وأخطاء داخلية حقيقية، بنفس منطق endpoint الإحصائيات
            $statusCode = ($e->getCode() >= 400 && $e->getCode() <= 500) ? $e->getCode() : 500;

            if ($statusCode === 500) {
                Log::error("خطأ أثناء جلب الحالات لرئيس القسم: " . $e->getMessage());
                return response_error(null, 500, 'حدث خطأ داخلي أثناء معالجة البيانات.');
            }

            return response_error(null, $statusCode, $e->getMessage());
        }
    }

    public function indexCourses(): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            $hodProfile = $user->departmentHeadProfile;

            if (! $hodProfile) {
                return response_error(null, 403, 'غير مصرح لك: حسابك لا يملك صلاحيات رئيس قسم.');
            }

            $courses = $this->hodService->getCoursesForDepartment($hodProfile->department_id);

            return response_success($courses, 200, 'تم جلب مقررات قسمك بنجاح.');
        } catch (Exception $e) {
            Log::error("خطأ أثناء جلب مقررات رئيس القسم: " . $e->getMessage());
            return response_error(null, 500, 'حدث خطأ داخلي أثناء معالجة البيانات.');
        }
    }

    public function store(StoreCaseTypeRequest $request): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            $hodProfile = $user->departmentHeadProfile;

            if (! $hodProfile) {
                return response_error(null, 403, 'غير مصرح لك: حسابك لا يملك صلاحيات رئيس قسم.');
            }

            $caseType = $this->hodService->createCaseType(
                $hodProfile->department_id,
                $request->validated()
            );

            return response_success(
                new CaseTypeResource($caseType->load('course:id,name')),
                201,
                'تم إضافة نوع الحالة بنجاح.'
            );
        } catch (Exception $e) {
            $statusCode = ($e->getCode() >= 400 && $e->getCode() <= 500) ? $e->getCode() : 500;

            if ($statusCode === 500) {
                Log::error("خطأ داخلي أثناء إضافة نوع حالة: " . $e->getMessage());
                return response_error(null, 500, 'حدث خطأ داخلي أثناء معالجة الطلب.');
            }

            return response_error(null, $statusCode, $e->getMessage());
        }
    }

    public function destroy(int $caseTypeId): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            $hodProfile = $user->departmentHeadProfile;

            if (! $hodProfile) {
                return response_error(null, 403, 'غير مصرح لك: حسابك لا يملك صلاحيات رئيس قسم.');
            }

            $this->hodService->deleteCaseType($hodProfile->department_id, $caseTypeId);

            return response_success(null, 200, 'تم حذف نوع الحالة بنجاح.');
        } catch (Exception $e) {
            $statusCode = ($e->getCode() >= 400 && $e->getCode() <= 500) ? $e->getCode() : 500;

            if ($statusCode === 500) {
                Log::error("خطأ داخلي أثناء حذف نوع حالة: " . $e->getMessage());
                return response_error(null, 500, 'حدث خطأ داخلي أثناء معالجة الطلب.');
            }

            return response_error(null, $statusCode, $e->getMessage());
        }
    }
}
