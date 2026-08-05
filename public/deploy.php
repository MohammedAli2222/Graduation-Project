<?php

// إعداد ترويسة الاستجابة لتكون بصيغة JSON
header('Content-Type: application/json');

// 1. التحقق من مفتاح الأمان لمنع الاستغلال
$secret = 'fdd1e7fc37037945b199ba383023275f0142831c';
if (!isset($_GET['secret']) || $_GET['secret'] !== $secret) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'تصريح مرفوض: مفتاح الأمان غير صحيح.']);
    exit;
}

// 2. استدعاء ملفات النظام الأساسية للارافل دون المرور بنظام التوجيه
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

// 3. تهيئة النواة للتعامل مع أوامر الأرتيزان
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// 4. تنفيذ أوامر الصيانة والتحديث
try {
    \Illuminate\Support\Facades\Artisan::call('optimize:clear');
    \Illuminate\Support\Facades\Artisan::call('migrate:refresh', ['--seed' => true, '--force' => true]);
    \Illuminate\Support\Facades\Artisan::call('storage:link');
    \Illuminate\Support\Facades\Artisan::call('queue:work', ['--once' => true, '--stop-when-empty' => true]);

    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'message' => 'تم تنفيذ جميع أوامر التحديث، المايجريشن، التخزين، والكوِيو بنجاح بطريقة معمارية نظيفة.'
    ]);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
