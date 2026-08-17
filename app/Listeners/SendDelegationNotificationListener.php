<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\InstructorDelegatedEvent;
use App\Services\FirebaseNotificationService;

class SendDelegationNotificationListener
{
    public function __construct(protected FirebaseNotificationService $notificationService) {}

    // إشعار المعيد بحصوله على صلاحية مراجعة الحالات السريرية من رئيس القسم
    public function handle(InstructorDelegatedEvent $event): void
    {
        $this->notificationService->sendNotificationToUser(
            $event->instructor->id,
            'صلاحيات إدارية جديدة 🔑',
            'لقد قام رئيس القسم بتفويضك بصلاحية مراجعة الحالات السريرية لطلاب القسم.',
            ['type' => 'instructor_delegated'],
            'instructor_delegated'
        );
    }
}
