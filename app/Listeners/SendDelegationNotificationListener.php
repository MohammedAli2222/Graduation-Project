<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\InstructorDelegatedEvent;
use App\Services\FirebaseNotificationService;

class SendDelegationNotificationListener
{
    public function __construct(protected FirebaseNotificationService $notificationService) {}

    // إشعار المعيد بموافقة رئيس القسم على طلب الصلاحية الذي تقدّم به
    public function handle(InstructorDelegatedEvent $event): void
    {
        $this->notificationService->sendNotificationToUser(
            $event->instructor->id,
            'تمت الموافقة على طلب الصلاحية',
            'رئيس القسم وافق على طلب الصلاحية الخاص بك.',
            ['type' => 'delegation_approved'],
            'delegation_approved'
        );
    }
}
