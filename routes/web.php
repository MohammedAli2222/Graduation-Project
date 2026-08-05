<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * مسار تنفيذي مباشر لإدارة الصيانة، التحديث، الـ Storage، والـ Queue.
 */
Route::get('/run-deployment', function (Request $request): Response {
    // التحقق من مفتاح الأمان لمنع أي استغلال خارجي للرابط
    if ($request->input('secret') !== config('app.deployment_secret', 'secure-token-here')) {
        return response()->json([
            'status' => 'error',
            'message' => 'تصريح مرفوض: مفتاح الأمان غير صحيح.'
        ], Response::HTTP_UNAUTHORIZED);
    }

    try {
        // 1. مسح وتفريغ كافة أنواع الكاش
        Artisan::call('optimize:clear');

        // 2. إعادة إنشاء قاعدة البيانات وتشغيل الـ Seeders
        Artisan::call('migrate:refresh', [
            '--seed' => true,
            '--force' => true,
        ]);

        // 3. إنشاء رابط التخزين
        Artisan::call('storage:link');

        // 4. تشغيل الـ Queue لمعالجة المهام المعلقة
        Artisan::call('queue:work', [
            '--once' => true,
            '--stop-when-empty' => true,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'تم تنفيذ جميع أوامر التحديث، المايجريشن، التخزين، والكوِيو بنجاح.',
        ], Response::HTTP_OK);

    } catch (\Throwable $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'حدث خطأ أثناء التنفيذ: ' . $e->getMessage(),
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
});
