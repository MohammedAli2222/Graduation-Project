<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

Route::get('/run-deployment', function (Request $request): Response {
    $expectedSecret = config('app.deployment_secret');

    // نرفض الطلب صراحة إن لم يُضبط DEPLOYMENT_SECRET في .env على الخادم،
    // بدل الاعتماد على قيمة افتراضية ثابتة يمكن لأي شخص معرفتها من الكود المصدري
    if (empty($expectedSecret) || ! hash_equals($expectedSecret, (string) $request->input('secret'))) {
        return response()->json([
            'status' => 'error',
            'message' => 'تصريح مرفوض: مفتاح الأمان غير صحيح.'
        ], Response::HTTP_UNAUTHORIZED);
    }

    try {
        Artisan::call('optimize:clear');

        Artisan::call('migrate', [
            '--force' => true,
        ]);

        Artisan::call('storage:link');

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

