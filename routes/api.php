<?php

use App\Http\Controllers\Hod\DepartmentTreatmentController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\Hod\DepartmentDelegationController;
use App\Http\Controllers\Hod\DepartmentRequirementController;
use App\Http\Controllers\Hod\DepartmentStatisticController;
use App\Http\Controllers\InstructorController;
use App\Http\Controllers\ReceptionistController;
use App\Http\Controllers\Store\StoreOrderController;
use App\Http\Controllers\Store\StoreProductController;
use App\Http\Controllers\Store\StorePromotionController;
use App\Http\Controllers\Student\MarketplaceBrowseController;
use App\Http\Controllers\Student\BrowseStoreController;
use App\Http\Controllers\Student\StudentSellerBrowseController; // استدعاء متحكم سوق الطلاب
use App\Http\Controllers\Student\StudentCartController;
use App\Http\Controllers\Student\StudentCheckoutController;
use App\Http\Controllers\Student\StudentProductController;
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

        Route::prefix('cart')->group(function () {
            Route::get('/', [StudentCartController::class, 'show']);
            Route::post('/add', [StudentCartController::class, 'add']);
            Route::delete('/items/{cartItem}', [StudentCartController::class, 'remove']);
            Route::delete('/clear', [StudentCartController::class, 'clear']);
        });

        Route::post('/checkout', [StudentCheckoutController::class, 'checkout']);

        Route::prefix('marketplace')->group(function () {
            Route::get('/my-products', [StudentProductController::class, 'index']);
            Route::post('/my-products', [StudentProductController::class, 'store']);
            Route::put('/my-products/{product}', [StudentProductController::class, 'update']);
            Route::delete('/my-products/{product}', [StudentProductController::class, 'destroy']);
        });

        Route::prefix('browse')->group(function () {
            // السوق العام
            Route::get('/products', [MarketplaceBrowseController::class, 'index']);
            Route::get('/products/{id}', [MarketplaceBrowseController::class, 'show']);

            // تصفح المتاجر الرسمية
            Route::get('/stores', [BrowseStoreController::class, 'index']);
            Route::get('/stores/{id}/products', [BrowseStoreController::class, 'showStoreProducts']);

            // سوق الطلاب 
            Route::get('/student-products', [StudentSellerBrowseController::class, 'index']);
            Route::get('/student-products/{id}', [StudentSellerBrowseController::class, 'show']);
        });

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
        Route::get('/dashboard/stats', [InstructorController::class, 'getStats']);
        Route::get('/patients/student-pending', [InstructorController::class, 'studentPending']);
        Route::post('/diagnose', [InstructorController::class, 'diagnose']);
        Route::post('/patients/{id}/approve', [InstructorController::class, 'approve']);
        Route::post('/patients/{id}/reject', [InstructorController::class, 'reject']);
        Route::get('/treatments/pending', [InstructorController::class, 'getPendingTreatmentsList']);
        Route::get('/treatments/pending/{id}', [InstructorController::class, 'getTreatmentDetails']);
        Route::post('treatments/review', [InstructorController::class, 'reviewTreatment']);
    });

    Route::prefix('hod')->middleware('role_or_permission:department_head|view-department-treatments')->group(function () {
        Route::get('/completed-treatments', [DepartmentTreatmentController::class, 'completedTreatments']);
    });

    Route::middleware('role:department_head')->prefix('hod')->group(function () {
        Route::get('/case-types', [DepartmentRequirementController::class, 'indexCaseTypes']);
        Route::post('/case-types/{caseType}/requirement', [DepartmentRequirementController::class, 'update']);

        // [REQ-HOD-04]
        Route::get('/instructors', [DepartmentDelegationController::class, 'instructorsList']);
        Route::post('/instructors/{id}/delegate', [DepartmentDelegationController::class, 'grantPermission']);
        Route::post('/instructors/{id}/revoke', [DepartmentDelegationController::class, 'revokePermission']);

        // [REQ-HOD-05]
        Route::get('/statistics', [DepartmentStatisticController::class, 'index']);
    });

    Route::middleware(['role:store_owner'])->prefix('store')->group(function () {
        Route::get('/products', [StoreProductController::class, 'index']);
        Route::post('/products', [StoreProductController::class, 'store']);
        Route::put('/products/{product}', [StoreProductController::class, 'update']);
        Route::delete('/products/{product}', [StoreProductController::class, 'destroy']);
        Route::get('/products/{product}', [StoreProductController::class, 'show']);

        // مسارات العروض الترويجية (Promotions)
        Route::get('/promotions', [StorePromotionController::class, 'index']);
        Route::get('/promotions/{promotion}', [StorePromotionController::class, 'show']);
        Route::post('/promotions', [StorePromotionController::class, 'store']);
        Route::put('/promotions/{promotion}', [StorePromotionController::class, 'update']);
        Route::delete('/promotions/{promotion}', [StorePromotionController::class, 'destroy']);
    });


    Route::middleware(['role:store_owner|student'])->prefix('store')->group(function () {
        Route::get('/orders', [StoreOrderController::class, 'index']);
        Route::get('/orders/{order}', [StoreOrderController::class, 'show']);
        Route::patch('/orders/{order}/status', [StoreOrderController::class, 'updateStatus']);
    });
});

Route::get('/categories', [StoreProductController::class, 'getCategoriesDropdown']);
