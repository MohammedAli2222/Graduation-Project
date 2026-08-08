<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\Contracts\FcmTokenRepositoryInterface;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\MulticastSendReport;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Illuminate\Support\Facades\Log;
use Throwable;

class FirebaseNotificationService
{
    public function __construct(
        protected Messaging $messaging,
        protected FcmTokenRepositoryInterface $fcmTokenRepo
    ) {}

    // إرسال إشعار فوري لجميع أجهزة المستخدم المسجّلة (متعددة الأجهزة) عبر Firebase Cloud Messaging
    public function sendNotificationToUser(int $userId, string $title, string $body, array $data = []): void
    {
        $tokens = $this->fcmTokenRepo->getTokensForUser($userId);

        if ($tokens->isEmpty()) {
            return;
        }

        $message = CloudMessage::new()
            ->withNotification(FirebaseNotification::create($title, $body))
            ->withData($data);

        try {
            $report = $this->messaging->sendMulticast($message, $tokens->pluck('fcm_token')->all());

            $this->removeInvalidTokens($report);
        } catch (Throwable $e) {
            // لا يجب أن يوقف فشل الإرسال أي عملية أعمال أخرى، لذلك يتم تسجيل الخطأ فقط
            Log::error('فشل إرسال إشعار Firebase للمستخدم رقم ' . $userId . ': ' . $e->getMessage());
        }
    }

    // حذف التوكنات غير الصالحة (مثل الأجهزة التي أُلغي تثبيت التطبيق منها) حتى لا تتكرر محاولة إرسال فاشلة لها
    private function removeInvalidTokens(MulticastSendReport $report): void
    {
        foreach ($report->invalidTokens() as $invalidToken) {
            $this->fcmTokenRepo->deleteToken($invalidToken);
        }
    }
}
