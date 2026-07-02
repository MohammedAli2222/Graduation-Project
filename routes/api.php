<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\InstructorController;
use App\Http\Controllers\ReceptionistController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TreatmentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:strict_auth');

Route::get('/instructor/groups', [GroupController::class, 'index']);
Route::get('/students/groups', [GroupController::class, 'getGroupsByYear']);

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])->middleware('throttle:strict_auth');

    Route::middleware('role:receptionist|instructor')->group(function () {
        Route::get('/receptionistWaiting', [ReceptionistController::class, 'receptionistWaiting']);
    });

    Route::middleware('role:receptionist')->prefix('receptionist')->group(function () {
        Route::get('/stats', [ReceptionistController::class, 'stats']);
        Route::get('/patients/search', [ReceptionistController::class, 'search']);
        Route::post('/patients/store', [ReceptionistController::class, 'store']);
        Route::get('/patients/{id}', [ReceptionistController::class, 'show']);
        Route::post('/patients/update/{id}', [ReceptionistController::class, 'update']);
    });

    Route::middleware(['role:student'])->prefix('student')->group(function () {

        Route::post('/setup-courses', [StudentController::class, 'setupAcademicCourses']);
        Route::get('case-types', [StudentController::class, 'getCaseTypesDropdown']);

        Route::post('/update-profile', [AuthController::class, 'updateProfile']);

        Route::middleware(['ensure.courses.setup'])->group(function () {

            Route::get('case-types/{caseTypeId}/available-patients', [StudentController::class, 'getAvailablePatients']);
            Route::get('patient-case-details/{id}', [StudentController::class, 'getPatientCaseDetails']);

            Route::get('/my-courses', [StudentController::class, 'getMyCourses']);

            Route::post('/patients/store', [StudentController::class, 'store']);

            Route::post('appointments/book', [AppointmentController::class, 'bookCase']);

            Route::get('/appointments', [AppointmentController::class, 'getMyAppointments']);
            Route::get('/appointments/history', [AppointmentController::class, 'getAppointmentHistory']);

            Route::post('/treatments/start', [TreatmentController::class, 'startTreatment']);
            Route::post('/treatments/follow-up', [TreatmentController::class, 'bookFollowUp']);
            Route::post('/treatments/complete', [TreatmentController::class, 'completeTreatment']);

            Route::get('/patients/{patientId}/diagnoses', [StudentController::class, 'getPatientDiagnoses']);

            Route::get('/patients/{patient_id}/treatments/history', [TreatmentController::class, 'getPatientTreatmentHistory']);

            Route::patch('/appointments/{id}/cancel', [AppointmentController::class, 'cancelAppointment']);

            Route::post('/treatments/{id}/rollback', [TreatmentController::class, 'rollbackStartedTreatment']);

            Route::get('/dashboard/progress', [TreatmentController::class, 'getProgressStats']);
            Route::get('/dashboard/cases', [TreatmentController::class, 'getCasesList']);
        });
    });

    Route::middleware('role:instructor')->prefix('instructor')->group(function () {

        Route::get('/patients/student-pending', [InstructorController::class, 'studentPending']);

        Route::post('/diagnose', [InstructorController::class, 'diagnose']);

        Route::post('/patients/{id}/approve', [InstructorController::class, 'approve']);
        Route::post('/patients/{id}/reject', [InstructorController::class, 'reject']);

        Route::get('/treatments/pending', [InstructorController::class, 'getPendingTreatmentsList']);
        Route::get('/treatments/pending/{id}', [InstructorController::class, 'getTreatmentDetails']);

        Route::post('treatments/review', [InstructorController::class, 'reviewTreatment']);
    });
});
