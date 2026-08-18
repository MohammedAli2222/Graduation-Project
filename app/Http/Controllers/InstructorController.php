<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApproveCaseRequest;
use App\Http\Requests\DiagnoseRequest;
use App\Http\Requests\ReferPatientRequest;
use App\Http\Requests\RejectCaseRequest;
use App\Http\Requests\ReviewTreatmentRequest;
use App\Http\Resources\PatientDiagnosisResource;
use App\Http\Resources\PatientResource;
use App\Http\Resources\TreatmentListResource;
use App\Http\Resources\TreatmentResource;
use App\Models\CaseType;
use App\Services\DiagnosisService;
use App\Actions\Treatment\ReviewTreatmentAction;
use App\Services\PatientService;
use App\Services\TreatmentService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InstructorController extends Controller
{
    public function __construct(protected DiagnosisService $diagnosisService, protected PatientService $patientservice, protected TreatmentService $treatmentService) {}


    private function getInstructorId()
    {
        $id = auth()->user()->instructorProfile?->id;
        if (! $id) {
            throw new Exception('Instructor profile not found.', 404);
        }

        return $id;
    }

    // مع ?department_id=X تُرجع فقط أنواع الحالات التابعة لذلك القسم؛ تُستخدم في
    // شاشة "تحويل المريض إلى قسم آخر" بعد اختيار القسم الهدف، بدل عرض كل الحالات.
    public function getCaseTypesDropdown(Request $request)
    {
        $departmentId = $request->query('department_id');

        $caseTypes = CaseType::with('course.department')
            ->when($departmentId, fn ($query) => $query->whereHas(
                'course',
                fn ($courseQuery) => $courseQuery->where('department_id', $departmentId)
            ))
            ->orderBy('name')
            ->get();

        return response_success(
            $caseTypes->map(fn (CaseType $caseType) => [
                'id' => $caseType->id,
                'name' => $caseType->name,
                'course' => $caseType->course
                    ? ['id' => $caseType->course->id, 'name' => $caseType->course->name]
                    : null,
                'department' => $caseType->course?->department
                    ? ['id' => $caseType->course->department->id, 'name' => $caseType->course->department->name]
                    : null,
            ]),
            200,
            'Case types retrieved successfully.'
        );
    }

    public function getStats(Request $request)
    {
        try {
            $stats = $this->diagnosisService->getInstructorStats($request->user()->id);
            return response_success($stats ,200 );
        } catch (\Exception $e) {
            return response_error(null ,400);
        }
    }

    public function diagnose(DiagnoseRequest $request)
    {
        try {
            $result = $this->diagnosisService->storeMultiple($request->validated(), Auth::id());

            return response_success(PatientDiagnosisResource::collection($result), 201, 'Diagnoses have been created successfully.');
        } catch (Exception $e) {
            return response_error(null, 422, $e->getMessage());
        }
    }

    // إحالة المريض سريرياً إلى قسم آخر بإضافة نوع حالة جديد يتبع لذلك القسم
    public function referPatient(ReferPatientRequest $request, int $patientId)
    {
        try {
            $diagnosis = $this->diagnosisService->referPatientToDepartment(
                $patientId,
                $request->validated(),
                Auth::id()
            );

            return response_success(new PatientDiagnosisResource($diagnosis), 201, 'Patient has been referred to the target department successfully.');
        } catch (ModelNotFoundException) {
            return response_error(null, 404, 'Patient or case type not found.');
        } catch (Exception $e) {
            return response_error(null, $e->getCode() ?: 400, $e->getMessage());
        }
    }

    public function approve(ApproveCaseRequest $request, $id)
    {
        $validatedData = $request->validated();

        try {
            $instructorId = $this->getInstructorId();

            $this->diagnosisService->approveCase(
                $id,
                $validatedData,
                auth()->id(),
                $instructorId
            );

            return response_success(null, 200, 'Case approved successfully.');
        } catch (ModelNotFoundException) {
            return response_error(null, 404, 'PatientDiagnose not found');
        } catch (Exception $e) {

            return response_error(null, $e->getCode() ?: 400, $e->getMessage());
        }
    }

    public function reject(RejectCaseRequest $request, $id)
    {

        $validatedData = $request->validated();

        try {

            $instructorId = $this->getInstructorId();

            $this->diagnosisService->rejectCase(
                $id,
                $validatedData,
                auth()->id(),
                $instructorId
            );

            return response_success(null, 200, 'Case has been rejected successfully.');
        } catch (ModelNotFoundException) {
            return response_error(null, 404, 'PatientDiagnose not found');
        } catch (Exception $e) {
            return response_error(null, 400, $e->getMessage());
        }
    }

    /**
     * Endpoint A — قائمة المرضى بانتظار *وضع خطة تشخيص*.
     *
     * تجمع مصدرَي الانتظار في نقطة واحدة مع إمكانية الفلترة:
     * - ?source=reception → مرضى الاستقبال بلا تشخيص بعد.
     * - ?source=student   → تشخيصات اقترحها طلاب المعيد وتنتظر الاعتماد.
     * - بدون المعامل      → الاثنان معاً مرتّبين زمنياً.
     *
     * هذه النقطة لا تُرجع أبداً حالات نُفِّذ علاجها؛ تلك في Endpoint B.
     */
    public function pendingDiagnoses(Request $request)
    {
        try {
            $instructorId = $this->getInstructorId();

            $patients = $this->patientservice->getPendingDiagnosisPatients(
                $instructorId,
                (string) $request->query('source', 'all'),
                (int) $request->query('per_page', 10)
            );

            return response_success([
                'patients' => PatientResource::collection($patients->items()),
                'pagination' => [
                    'total' => $patients->total(),
                    'count' => $patients->count(),
                    'per_page' => $patients->perPage(),
                    'current_page' => $patients->currentPage(),
                    'last_page' => $patients->lastPage(),
                ],
            ], 200, 'Patients pending diagnosis fetched successfully.');
        } catch (Exception $e) {
            return response_error(null, $e->getCode() ?: 400, $e->getMessage());
        }
    }

    /**
     * @deprecated نقطة قديمة تُبقي التطبيق الحالي يعمل؛ استخدم
     * /instructor/patients/pending-diagnoses?source=student بدلاً منها.
     */
    public function studentPending()
    {

        $instructorId = $this->getInstructorId();

        $patients = $this->patientservice->getPendingDiagnosisPatients($instructorId, 'student');

        return response_success([
            'patients' => PatientResource::collection($patients->items()),
            'pagination' => [
                'total' => $patients->total(),
                'count' => $patients->count(),
                'per_page' => $patients->perPage(),
                'current_page' => $patients->currentPage(),
                'last_page' => $patients->lastPage(),
            ],
        ], 200, 'Student pending requests fetched successfully.');
    }

    /**
     * Endpoint B — العلاجات المنجزة من الطلاب وتنتظر تقييم المعيد فقط.
     */
    public function getPendingTreatmentsList(Request $request)
    {
        try {
            $treatments = $this->treatmentService->getPendingTreatmentsListForInstructor($request->user());

            return response_success([
                'items' => TreatmentListResource::collection($treatments->items()),
                'pagination' => [
                    'current_page' => $treatments->currentPage(),
                    'last_page' => $treatments->lastPage(),
                    'total' => $treatments->total(),
                ],
            ], 200, 'List of pending treatments retrieved successfully.');
        } catch (Exception $e) {
            return response_error($e->getMessage(), 400);
        }
    }

    public function getTreatmentDetails(Request $request, $id)
    {
        try {
            $treatment = $this->treatmentService->getTreatmentDetailsForInstructor((int) $id, $request->user());

            return response_success(new TreatmentResource($treatment), 200, 'Treatment details with media retrieved successfully.');
        } catch (Exception $e) {
            return response_error($e->getMessage(), 400);
        }
    }


    public function reviewTreatment(ReviewTreatmentRequest $request, ReviewTreatmentAction $action)
    {
        try {
            $treatment = $action->execute(
                $request->validated(),
                $request->user()
            );

            $message = $request->validated()['action'] === 'approve'
                ? 'Treatment has been approved and marked as completed successfully.'
                : 'Treatment has been rejected. Notes have been sent back to the student.';

            return response_success(
                new TreatmentResource($treatment),
                200,
                $message
            );
        } catch (Exception $e) {
            return response_error(
                $e->getMessage(),
                400,
                'Failed to process instructor action.'
            );
        }
    }
}
