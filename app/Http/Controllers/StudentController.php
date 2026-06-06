<?php

namespace App\Http\Controllers;

use App\Enums\DiagnosisStatus;
use App\Http\Requests\StudentStorePatientRequest;
use App\Http\Resources\CaseTypeResource;
use App\Http\Resources\PatientResource;
use App\Services\PatientService;
use App\Services\StudentCourseService;
use App\Http\Resources\CourseResource;
use App\Http\Resources\PatientDiagnosisDetailsResource;
use App\Http\Resources\PatientDiagnosisResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

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

        if (!$student) {
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

        if (!$student) {
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

            $patient->load(['medicalHistory', 'diagnoses', 'media']);
            return response_success(new PatientResource($patient), 201, 'Patient request submitted successfully.');
        } catch (\Exception $e) {
            return response_error(null, 500, 'Something went wrong: ' . $e->getMessage());
        }
    }


    public function setupAcademicCourses(Request $request)
    {
        $student = $request->user()->studentProfile;

        $result = $this->courseService->autoEnrollStudentCourses($student);

        if ($result['status'] === 'waiting_next_semester') {
            return response()->json([
                'status'  => 200,
                'code'    => 'LOCK_STUDENT_APP',
                'message' => $result['message'],
                'data'    => []
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

            return response_success(PatientDiagnosisResource::collection($patients), 200, 'Available patients retrieved successfully.');
        } catch (\Exception $e) {
            return response_error(null, $e->getCode(), $e->getMessage());
        }
    }

    public function getPatientCaseDetails(int $id)
    {
        try {
            $diagnosis = $this->patientService->getDiagnosisDetails($id);

            $formattedDetails = (new PatientDiagnosisDetailsResource($diagnosis))->resolve();

            return response_success($formattedDetails, 200, 'Patient detailed profile retrieved successfully.');
        } catch (ModelNotFoundException $e) {
            return response_error(null, 404, 'The requested patient case layout was not found.');
        } catch (\Exception $e) {
            $code = intval($e->getCode());
            $statusCode = ($code >= 400 && $code <= 500) ? $code : 400;
            return response_error(null, $statusCode, $e->getMessage());
        }
    }
}
