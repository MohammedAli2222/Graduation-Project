<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\InstructorController;
use App\Http\Controllers\ReceptionistController;
use App\Http\Controllers\StudentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::post('/register', [AuthController::class, 'register']);

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:strict_auth');



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


    Route::middleware('role:student')->prefix('student')->group(function () {

        Route::get('case-types', [StudentController::class, 'getCaseTypesDropdown']);


        Route::get('/my-courses', [StudentController::class, 'getMyCourses']);
        Route::post('/setup-courses', [StudentController::class, 'setupAcademicCourses']);

        Route::post('/patients/store', [StudentController::class, 'store']);
    });


    Route::middleware('role:instructor')->prefix('instructor')->group(function () {

        Route::get('/patients/student-pending', [InstructorController::class, 'studentPending']);

        Route::post('/diagnose', [InstructorController::class, 'diagnose']);

        Route::post('/patients/{id}/approve', [InstructorController::class, 'approve']);
        Route::post('/patients/{id}/reject', [InstructorController::class, 'reject']);
    });
});
