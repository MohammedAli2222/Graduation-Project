<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\FcmTokenController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\Hod\DepartmentDelegationController;
use App\Http\Controllers\Hod\DepartmentRequirementController;
use App\Http\Controllers\Hod\DepartmentStatisticController;
use App\Http\Controllers\Hod\DepartmentTreatmentController;
use App\Http\Controllers\InstructorController;
use App\Http\Controllers\InstructorDelegationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReceptionistController;
use App\Http\Controllers\Store\StoreOrderController;
use App\Http\Controllers\Store\StoreProductController;
use App\Http\Controllers\Store\StorePromotionController;
use App\Http\Controllers\Store\StoreStatisticController;
use App\Http\Controllers\Student\BrowseStoreController;
use App\Http\Controllers\Student\MarketplaceBrowseController;
use App\Http\Controllers\Student\PromotionBrowseController;
use App\Http\Controllers\Student\StudentCartController;
use App\Http\Controllers\Student\StudentCheckoutController;
use App\Http\Controllers\Student\StudentProductController;
use App\Http\Controllers\Student\StudentPurchaseController;
use App\Http\Controllers\Student\StudentSellerBrowseController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TreatmentController;
use App\Http\Middleware\VerifyDeploymentSecret;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

/*
 * تشغيل أمر Artisan وإرجاع مخرجاته الحقيقية. بدون هذا الغلاف كان أي فشل يرتد
 * كـ 500 "Server Error" فقط (لأن APP_DEBUG=false في الإنتاج) بلا أي دليل على السبب.
 * set_time_limit(0) لأن الهجرات والـ Seeding قد تتجاوز مهلة PHP الافتراضية.
 */
$runArtisan = function (string $command, array $parameters, string $successMessage) {
    set_time_limit(0);

    try {
        Artisan::call($command, $parameters);

        return response()->json([
            'status' => 'success',
            'message' => $successMessage,
            'output' => trim(Artisan::output()),
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
            'command' => $command,
            'output' => trim(Artisan::output()),
        ], 500);
    }
};

Route::middleware([VerifyDeploymentSecret::class, 'throttle:10,1'])->prefix('deploy')->group(function () use ($runArtisan) {
    Route::post('/cache', fn () => $runArtisan('optimize:clear', [], 'System cache cleared successfully.'));

    Route::post('/migrate', fn () => $runArtisan('migrate', ['--force' => true], 'Database migrations executed successfully.'));

    Route::post('/refresh', fn () => $runArtisan('migrate:refresh', ['--force' => true], 'Database refreshed successfully.'));

    // migrate:fresh يُسقط كل الجداول مباشرةً بدل الاعتماد على دوال down()، فهو
    // الخيار الموثوق لإعادة البناء من الصفر. أضف seed=1 لتشغيل الـ Seeders بنفس الطلب.
    // ⚠️ يمسح كل بيانات قاعدة البيانات نهائياً.
    Route::post('/fresh', fn (Request $request) => $runArtisan(
        'migrate:fresh',
        array_filter(['--force' => true, '--seed' => $request->boolean('seed')]),
        'Database rebuilt from scratch successfully.'
    ));

    Route::post('/seed', fn () => $runArtisan('db:seed', ['--force' => true], 'Database seeded successfully.'));

    Route::post('/queue', fn () => $runArtisan('queue:work', ['--stop-when-empty' => true], 'Queue jobs processed successfully.'));

    Route::post('/storage', fn () => $runArtisan('storage:link', [], 'Storage symbolic link created successfully.'));
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:strict_auth');

Route::get('/instructor/groups', [GroupController::class, 'index']);
// عام وبلا مصادقة عمداً: بيانات مرجعية غير حساسة، ومطلوبة قبل تسجيل الدخول
// (مثلاً شاشة طلب تفويض المعيد لاختيار القسم)، بنفس نمط /instructor/groups.
Route::get('/departments', [DepartmentController::class, 'index']);
Route::get('/students/groups', [GroupController::class, 'getGroupsByYear']);

Route::middleware('auth:sanctum')->group(function () {

    /*
     * نُرجع المستخدم عبر UserResource تماماً كما يفعل AuthService::login، حتى
     * يحصل الفرونت على نفس البنية في الحالتين (role + profile) بدل بيانات
     * الموديل الخام. علاقة البروفايل تُحمَّل ديناميكياً حسب دور المستخدم، لأن
     * UserResource لا يُظهر مفتاح profile إلا إذا كانت العلاقة محمّلة فعلاً.
     */
    Route::get('/user', function (Request $request) {
        $user = $request->user();
        $role = $user->getRoleNames()->first();
        $relation = $user->getProfileEagerLoadPath($role);

        if ($relation) {
            $user->load($relation);
        }

        return response_success(new UserResource($user), 200, 'User data retrieved successfully.');
    });
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])->middleware('throttle:strict_auth');
    Route::post('/fcm-token', [FcmTokenController::class, 'store']);
    // عند تسجيل الخروج: يزيل توكن هذا الجهاز فقط، وضمن نطاق المستخدم الحالي حصراً
    Route::delete('/fcm-token', [FcmTokenController::class, 'destroy']);

    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::delete('/{id}', [NotificationController::class, 'destroy']);
    });

    Route::middleware('role:receptionist|instructor')->group(function () {
        Route::get('/receptionistWaiting', [ReceptionistController::class, 'receptionistWaiting']);
    });

    Route::middleware('role:receptionist')->prefix('receptionist')->group(function () {
        Route::get('/stats', [ReceptionistController::class, 'stats']);
        Route::get('/patients/search', [ReceptionistController::class, 'search']);
        Route::post('/patients/store', [ReceptionistController::class, 'store']);
        Route::get('/patients/{id}', [ReceptionistController::class, 'show']);
        Route::post('/patients/update/{id}', [ReceptionistController::class, 'update']);
        Route::post('/patients/{id}/new-visit', [ReceptionistController::class, 'newVisit']);
    });

    Route::middleware(['role:student'])->prefix('student')->group(function () {
        Route::post('/setup-courses', [StudentController::class, 'setupAcademicCourses']);
        // شاشة إعداد المقررات: تُستخدم قبل أن يكون للطالب أي مقررات مفعّلة، لذا
        // تبقى خارج مجموعة ensure.courses.setup كبقية مسارات إعداد المقررات.
        Route::get('/courses/setup-list', [StudentController::class, 'getCourseSetupList']);
        Route::post('/courses/enroll', [StudentController::class, 'enrollInCourses']);
        // سحب مقرر: يعالج أيضاً حالة الطالب المعيد للسنة ومقرر سبق نجاحه فيه.
        Route::post('/courses/drop', [StudentController::class, 'dropCourses']);
        Route::get('case-types', [StudentController::class, 'getCaseTypesDropdown']);
        Route::post('/update-profile', [AuthController::class, 'updateProfile']);

        Route::prefix('cart')->group(function () {
            Route::get('/', [StudentCartController::class, 'show']);
            Route::post('/add', [StudentCartController::class, 'add']);
            Route::put('/items/{cartItem}', [StudentCartController::class, 'updateQuantity']);
            Route::delete('/items/{cartItem}', [StudentCartController::class, 'remove']);
            Route::delete('/clear', [StudentCartController::class, 'clear']);
        });

        Route::post('/checkout', [StudentCheckoutController::class, 'checkout']);

        Route::prefix('purchases')->group(function () {
            Route::get('/', [StudentPurchaseController::class, 'index']);
            Route::get('/{order}', [StudentPurchaseController::class, 'show']);
        });

        Route::prefix('marketplace')->group(function () {
            Route::get('/my-products', [StudentProductController::class, 'index']);
            Route::post('/my-products', [StudentProductController::class, 'store']);
            Route::put('/my-products/{product}', [StudentProductController::class, 'update']);
            Route::delete('/my-products/{product}', [StudentProductController::class, 'destroy']);
        });

        Route::prefix('browse')->group(function () {
            Route::get('/products', [MarketplaceBrowseController::class, 'index']);
            Route::get('/products/{id}', [MarketplaceBrowseController::class, 'show']);

            Route::get('/stores', [BrowseStoreController::class, 'index']);
            Route::get('/stores/{id}/products', [BrowseStoreController::class, 'showStoreProducts']);

            Route::get('/student-products', [StudentSellerBrowseController::class, 'index']);
            Route::get('/student-products/{id}', [StudentSellerBrowseController::class, 'show']);

            Route::get('/promotions', [PromotionBrowseController::class, 'index']);
        });

        Route::middleware(['ensure.courses.setup'])->group(function () {
            Route::get('case-types/{caseTypeId}/available-patients', [StudentController::class, 'getAvailablePatients']);
            Route::get('patient-case-details/{id}', [StudentController::class, 'getPatientCaseDetails']);
            Route::get('/my-courses', [StudentController::class, 'getMyCourses']);
            Route::post('/patients/store', [StudentController::class, 'store']);
            Route::post('/patients/{patient_id}/diagnoses', [StudentController::class, 'storeExistingPatientDiagnosis']);
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
        Route::get('/case-types', [InstructorController::class, 'getCaseTypesDropdown']);

        
        Route::get('/patients/pending-diagnoses', [InstructorController::class, 'pendingDiagnoses']);

        // نقطة قديمة (deprecated) تُبقي الإصدارات الحالية من التطبيق تعمل.
        Route::get('/patients/student-pending', [InstructorController::class, 'studentPending']);
        Route::post('/diagnose', [InstructorController::class, 'diagnose']);
        Route::post('/patients/{id}/approve', [InstructorController::class, 'approve']);
        Route::post('/patients/{id}/reject', [InstructorController::class, 'reject']);
        Route::post('/patients/{id}/refer', [InstructorController::class, 'referPatient']);
        Route::get('/treatments/pending', [InstructorController::class, 'getPendingTreatmentsList']);
        Route::get('/treatments/pending/{id}', [InstructorController::class, 'getTreatmentDetails']);
        Route::post('treatments/review', [InstructorController::class, 'reviewTreatment']);

        Route::post('/delegation/request', [InstructorDelegationController::class, 'request']);
    });

    Route::prefix('hod')->middleware('role_or_permission:department_head|view-department-treatments')->group(function () {
        Route::get('/completed-treatments', [DepartmentTreatmentController::class, 'completedTreatments']);
    });

    Route::middleware('role:department_head')->prefix('hod')->group(function () {
        Route::get('/completed-treatments/{treatment}', [DepartmentTreatmentController::class, 'show']);

        Route::get('/case-types', [DepartmentRequirementController::class, 'indexCaseTypes']);
        Route::post('/case-types', [DepartmentRequirementController::class, 'store']);
        Route::delete('/case-types/{caseType}', [DepartmentRequirementController::class, 'destroy']);
        Route::post('/case-types/{caseType}/requirement', [DepartmentRequirementController::class, 'update']);
        Route::get('/courses', [DepartmentRequirementController::class, 'indexCourses']);

        Route::get('/instructors', [DepartmentDelegationController::class, 'instructorsList']);
        // المسار بلا تغيير عمداً (وليس /delegation/grant/{id}): لوحة الويب
        // المنشورة فعلياً تنادي هذا المسار تحديداً؛ المنطق فقط هو ما تحدَّث.
        Route::post('/instructors/{id}/delegate', [DepartmentDelegationController::class, 'grantPermission']);
        Route::post('/instructors/{id}/revoke', [DepartmentDelegationController::class, 'revokePermission']);

        Route::get('/delegation/requests', [DepartmentDelegationController::class, 'delegationRequests']);
        Route::post('/delegation/reject/{id}', [DepartmentDelegationController::class, 'rejectRequest']);

        Route::get('/statistics', [DepartmentStatisticController::class, 'index']);
    });

    Route::middleware(['role:store_owner'])->prefix('store')->group(function () {
        Route::get('/products', [StoreProductController::class, 'index']);
        Route::post('/products', [StoreProductController::class, 'store']);
        Route::put('/products/{product}', [StoreProductController::class, 'update']);
        Route::delete('/products/{product}', [StoreProductController::class, 'destroy']);
        Route::get('/products/{product}', [StoreProductController::class, 'show']);

        Route::get('/promotions', [StorePromotionController::class, 'index']);
        Route::get('/promotions/{promotion}', [StorePromotionController::class, 'show']);
        Route::post('/promotions', [StorePromotionController::class, 'store']);
        Route::put('/promotions/{promotion}', [StorePromotionController::class, 'update']);
        Route::delete('/promotions/{promotion}', [StorePromotionController::class, 'destroy']);

        Route::get('/statistics', [StoreStatisticController::class, 'index']);
        Route::post('/statistics/export', [StoreStatisticController::class, 'exportReport']);
    });

    Route::middleware(['role:store_owner|student'])->prefix('store')->group(function () {
        Route::get('/orders', [StoreOrderController::class, 'index']);
        Route::get('/orders/{order}', [StoreOrderController::class, 'show']);
        Route::patch('/orders/{order}/status', [StoreOrderController::class, 'updateStatus']);
    });
});

Route::get('/categories', [StoreProductController::class, 'getCategoriesDropdown']);
