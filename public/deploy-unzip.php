<?php

/**
 * سكربت مستقل عن Laravel عمداً: بيشتغل *قبل* ما نضمن سلامة vendor/ على
 * السيرفر، فما بيصح يعتمد على vendor/autoload.php أو أي bootstrap لارافيل.
 * لهيك بيقرأ .env يدوياً بدل استخدام env()/config().
 */

$envPath = __DIR__ . '/../.env';
$expectedSecret = null;

if (is_readable($envPath)) {
    foreach (file($envPath) as $line) {
        if (preg_match('/^DEPLOYMENT_SECRET=(.*)$/', trim($line), $matches)) {
            $expectedSecret = trim($matches[1]);
            break;
        }
    }
}

header('Content-Type: application/json');

$providedSecret = $_GET['secret'] ?? '';

if (empty($expectedSecret) || ! hash_equals($expectedSecret, (string) $providedSecret)) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'تصريح مرفوض: مفتاح الأمان غير صحيح.']);
    exit;
}

$zipPath = __DIR__ . '/../vendor-deploy.zip';
$vendorPath = __DIR__ . '/../vendor';

if (! file_exists($zipPath)) {
    echo json_encode(['status' => 'skipped', 'message' => 'لا يوجد vendor-deploy.zip بانتظار الفك.']);
    exit;
}

if (! class_exists('ZipArchive')) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'إضافة PHP zip غير مفعّلة على هذا السيرفر.']);
    exit;
}

$zip = new ZipArchive();

if ($zip->open($zipPath) !== true) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'فشل فتح vendor-deploy.zip.']);
    exit;
}

$zip->extractTo($vendorPath);
$zip->close();
unlink($zipPath);

echo json_encode(['status' => 'success', 'message' => 'تم تحديث vendor/ بنجاح من الأرشيف.']);
