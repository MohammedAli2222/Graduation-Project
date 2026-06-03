<?php

namespace App\Http\Controllers;

use App\Http\Requests\EnrollCoursesRequest;
use App\Http\Requests\StudentStorePatientRequest;
use App\Http\Resources\CaseTypeResource;
use App\Http\Resources\PatientResource;
use App\Services\PatientService;
use App\Services\StudentCourseService;
use App\Http\Resources\CourseResource;
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

    /**
     * 🌟 التسجيل التلقائي الذكي للمواد بناءً على الحالة الأكاديمية للطالب
     */
    public function setupAcademicCourses(Request $request)
    {
        $student = $request->user()->studentProfile;

        // تشغيل نظام الـ Service المقسم والذكي اللي بنيناه
        $result = $this->courseService->autoEnrollStudentCourses($student);

        // [الحالة 2]: إذا الطالب مرفع كل مواد هاد الفصل ومطلوب منه الانتظار (قفل التطبيق)
        if ($result['status'] === 'waiting_next_semester') {
            return response()->json([
                'status'  => 200,
                'code'    => 'LOCK_STUDENT_APP', // كود صريح ليفهمه مطور الـ Flutter / React لقفل الواجهات
                'message' => $result['message'],
                'data'    => []
            ], 200);
        }

        // [الحالة 1 و 3]: تم التسجيل التلقائي بنجاح (سواء مواد كاملة أو مواد الرسوب فقط)
        return response_success(
            ['enrolled_count' => $result['count']],
            200,
            $result['message']
        );
    }
}
