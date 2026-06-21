<?php

namespace App\Http\Controllers;

use App\Http\Requests\StudentStorePatientRequest;
use App\Http\Resources\CaseTypeResource;
use App\Http\Resources\CourseResource;
use App\Http\Resources\PatientDiagnosisDetailsResource;
use App\Http\Resources\PatientDiagnosisResource;
use App\Http\Resources\PatientResource;
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

        if (! $student) {
            return response_error(null, 404, 'Profile not found.');
        }

        $caseTypes = $this->courseService->getCaseTypesForDropdown($student);

        return response_success(
            CaseTypeResource::collection($caseTypes),
            200,
            'Available case types for dropdown retrieved successfully.'
        );
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

            $diagnosisData = [
                'case_type_id' => $validatedData['case_type_id'],
            ];

            $patient = $this->patientService->registerPatient(
                $validatedData,
                $request->file('images'),
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

    public function getAvailablePatients(int $caseTypeId)
    {
        try {
            $patients = $this->patientService->getAvailablePatientsByCaseType($caseTypeId);

            return response_success(
                [
                    "data" => PatientDiagnosisResource::collection($patients),
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

            // بدلاً من $e->getMessage() التي قد تكون كارثية، استخدم رسالة ثابتة
            return response_error(null, $statusCode, 'An internal server error occurred.');
        }
    }
}
