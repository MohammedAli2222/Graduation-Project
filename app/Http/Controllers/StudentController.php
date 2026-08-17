<?php

namespace App\Http\Controllers;

use App\Http\Requests\DropStudentCoursesRequest;
use App\Http\Requests\EnrollStudentCoursesRequest;
use App\Http\Requests\StoreExistingPatientDiagnosisRequest;
use App\Http\Requests\StudentStorePatientRequest;
use App\Http\Resources\CaseTypeResource;
use App\Http\Resources\CourseResource;
use App\Http\Resources\PatientDiagnosisDetailsResource;
use App\Http\Resources\PatientDiagnosisResource;
use App\Http\Resources\PatientResource;
use App\Http\Resources\StudentCourseStatusResource;
use App\Http\Resources\StudentPatientDiagnosisResource;
use App\Services\PatientService;
use App\Services\StudentCourseService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class StudentController extends Controller
{
    protected PatientService $patientService;

    protected StudentCourseService $courseService;

    public function __construct(PatientService $patientService, StudentCourseService $courseService)
    {
        $this->patientService = $patientService;
        $this->courseService = $courseService;
    }

    public function getCaseTypesDropdown(Request $request)
    {
        $student = $request->user()->studentProfile;
        if (!$student) return response_error(null, 404, 'Profile not found.');

        $data = $this->courseService->getCaseTypesForDropdown($student);

        return response_success([
            'current_semester' => CaseTypeResource::collection($data['current']),
            'previous_semesters' => CaseTypeResource::collection($data['previous']),
        ], 200, 'Case types retrieved successfully.');
    }

    public function getMyCourses(Request $request)
    {
        $student = $request->user()->studentProfile;

        if (! $student) {
            return response_error(null, 403, 'This action is unauthorized. Student profile not found.');
        }

        $courses = $this->courseService->getActiveCoursesForStudent($student->id);

        return response_success(
            CourseResource::collection($courses),
            200,
            'Active academic courses retrieved successfully.'
        );
    }

    public function store(StudentStorePatientRequest $request)
    {

        try {
            $validatedData = $request->validated();

            $files = [
                'id_card'         => $request->file('id_card'),
                'clinical_images' => $request->file('clinical_images'),
                'x_ray_images'    => $request->file('x_ray_images'),
            ];

            $diagnosisData = [
                'case_type_ids' => $validatedData['case_type_ids'],
                'estimated_costs' => $request->input('estimated_costs'),
            ];

            $patient = $this->patientService->registerPatient(
                $validatedData,
                $files,
                $diagnosisData
            );


            return response_success(new PatientResource($patient), 201, 'Patient request submitted successfully.');
        } catch (ValidationException $e) {
            return response_error($e->errors(), 422, 'Validation failed.');
        } catch (\Exception $e) {
            $statusCode = is_numeric($e->getCode()) && $e->getCode() >= 400 && $e->getCode() < 600
                ? $e->getCode()
                : 500;

            return response_error(null, $statusCode, $e->getMessage());
        }
    }

    public function storeExistingPatientDiagnosis(StoreExistingPatientDiagnosisRequest $request, int $patientId)
    {
        try {
            $validatedData = $request->validated();

            $files = [
                'clinical_images' => $request->file('clinical_images'),
                'x_ray_images'    => $request->file('x_ray_images'),
            ];

            $patient = $this->patientService->addDiagnosesToExistingPatient(
                $patientId,
                $validatedData,
                $files
            );

            return response_success(new PatientResource($patient), 201, 'Diagnosis request submitted successfully.');
        } catch (ModelNotFoundException $e) {
            return response_error(null, 404, 'Patient not found.');
        } catch (ValidationException $e) {
            return response_error($e->errors(), 422, 'Validation failed.');
        } catch (\Exception $e) {
            $statusCode = is_numeric($e->getCode()) && $e->getCode() >= 400 && $e->getCode() < 600
                ? $e->getCode()
                : 500;

            return response_error(null, $statusCode, $e->getMessage());
        }
    }

    public function setupAcademicCourses(Request $request)
    {
        $student = $request->user()->studentProfile;

        $result = $this->courseService->autoEnrollStudentCourses($student);

        if ($result['status'] === 'waiting_next_semester') {
            return response()->json([
                'status' => 200,
                'code' => 'LOCK_STUDENT_APP',
                'message' => $result['message'],
                'data' => [],
            ], 200);
        }

        return response_success(
            ['enrolled_count' => $result['count']],
            200,
            $result['message']
        );
    }

    /**
     * شاشة إعداد المقررات: مقررات الفصل الحالي + المقررات المحمولة من فصول
     * سابقة، كل مقرر مع أعلام is_enrolled/can_enroll/can_drop/lock_reason.
     */
    public function getCourseSetupList(Request $request)
    {
        $student = $request->user()->studentProfile;

        if (! $student) {
            return response_error(null, 404, 'Student profile not found.');
        }

        $lists = $this->courseService->getCourseSetupList($student);

        return response_success([
            'current_semester_courses' => StudentCourseStatusResource::collection($lists['current_semester_courses']),
            'previous_semesters_courses' => StudentCourseStatusResource::collection($lists['previous_semesters_courses']),
        ], 200, 'Course setup list retrieved successfully.');
    }

    public function enrollInCourses(EnrollStudentCoursesRequest $request)
    {
        try {
            $student = $request->user()->studentProfile;

            if (! $student) {
                return response_error(null, 404, 'Student profile not found.');
            }

            $result = $this->courseService->manualEnrollCourses($student, $request->validated()['course_ids']);

            return response_success($result, 200, 'Courses enrolled successfully.');
        } catch (\Exception $e) {
            $statusCode = is_numeric($e->getCode()) && $e->getCode() >= 400 && $e->getCode() < 600
                ? $e->getCode()
                : 500;

            return response_error(null, $statusCode, $e->getMessage());
        }
    }

    /**
     * سحب مقررات من التسجيل الفعّال للطالب.
     *
     * يغطي الحالة الحدّية للطالب المعيد للسنة: مقرر سبق نجاحه فيه أُدرج
     * تلقائياً ضمن مقرراته، فيستطيع إزالته بدل إعادته بلا داعٍ.
     */
    public function dropCourses(DropStudentCoursesRequest $request)
    {
        try {
            $student = $request->user()->studentProfile;

            if (! $student) {
                return response_error(null, 404, 'Student profile not found.');
            }

            $result = $this->courseService->dropCourses($student, $request->validated()['course_ids']);

            return response_success($result, 200, 'Courses updated successfully.');
        } catch (\Exception $e) {
            $statusCode = is_numeric($e->getCode()) && $e->getCode() >= 400 && $e->getCode() < 600
                ? (int) $e->getCode()
                : 500;

            return response_error(null, $statusCode, $e->getMessage());
        }
    }

    public function getAvailablePatients(int $caseTypeId)
    {
        try {
            $patients = $this->patientService->getAvailablePatientsByCaseType($caseTypeId);

            return response_success(
                [
                    "data" => StudentPatientDiagnosisResource::collection($patients),
                    'pagination' => [
                        'total' => $patients->total(),
                        'count' => $patients->count(),
                        'per_page' => $patients->perPage(),
                        'current_page' => $patients->currentPage(),
                        'last_page' => $patients->lastPage(),
                    ],

                ],
                200,
                'Available patients retrieved successfully.'

            );
        } catch (\Exception $e) {

            $statusCode = is_numeric($e->getCode()) && $e->getCode() >= 400 && $e->getCode() < 600
                ? $e->getCode()
                : 500;

            return response_error(null, $statusCode, $e->getMessage());
        }
    }

    public function getPatientCaseDetails(int $id)
    {
        try {
            $diagnosis = $this->patientService->getDiagnosisDetails($id);

            $resource = new PatientDiagnosisDetailsResource($diagnosis);

            return response_success($resource, 200, 'Patient detailed profile retrieved successfully.');
        } catch (ModelNotFoundException $e) {
            return response_error(null, 404, 'The requested patient case layout was not found.');
        } catch (ValidationException $e) {
            return response_error($e->errors(), 422, 'Validation failed.');
        } catch (\Exception $e) {
            $statusCode = is_numeric($e->getCode()) && $e->getCode() >= 400 && $e->getCode() < 600
                ? $e->getCode()
                : 500;

            return response_error(null, $statusCode, 'An internal server error occurred.');
        }
    }


    public function getPatientDiagnoses(int $patientId)
    {
        try {
            $studentId = auth()->user()->studentProfile->id;
            $diagnoses = $this->patientService->getPatientDiagnoses($patientId, $studentId);

            return response_success(
                PatientDiagnosisResource::collection($diagnoses),
                200,
                'Patient diagnoses retrieved successfully.'
            );
        } catch (\Exception $e) {
            $statusCode = (int)$e->getCode();

            if ($statusCode < 100 || $statusCode > 599) {
                $statusCode = 500;
            }

            return response_error(null, $statusCode, $e->getMessage());
        }
    }
}
