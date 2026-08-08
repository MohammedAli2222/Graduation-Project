<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\InstructorDelegatedEvent;
use App\Services\FirebaseNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

// يعمل ضمن قائمة انتظار (Queue) حتى لا يؤخر إرسال الإشعار استجابة الـ API الرئيسية
class SendDelegationNotificationListener implements ShouldQueue
{
    public function __construct(protected FirebaseNotificationService $notificationService) {}

    // إشعار المعيد بحصوله على صلاحية مراجعة الحالات السريرية من رئيس القسم
    public function handle(InstructorDelegatedEvent $event): void
    {
        $this->notificationService->sendNotificationToUser(
            $event->instructor->id,
            'صلاحيات إدارية جديدة 🔑',
            'لقد قام رئيس القسم بتفويضك بصلاحية مراجعة الحالات السريرية لطلاب القسم.',
            ['type' => 'instructor_delegated']
        );
    }
}
