<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\TreatmentCompletedByStudentEvent;
use App\Services\FirebaseNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

// يعمل ضمن قائمة انتظار (Queue) حتى لا يؤخر إرسال الإشعار استجابة الـ API الرئيسية
class SendInstructorNotificationListener implements ShouldQueue
{
    public function __construct(protected FirebaseNotificationService $notificationService) {}

    // إشعار المدرّس المسؤول عن الحالة بأن الطالب أنهى تنفيذ العلاج وينتظر مراجعته
    public function handle(TreatmentCompletedByStudentEvent $event): void
    {
        $treatment = $event->treatment;

        // المدرّس المسؤول عن الحالة هو نفسه المدرّس الذي شخّص الحالة أصلاً
        $instructorId = $treatment->diagnosis?->instructor_id;

        if (! $instructorId) {
            return;
        }

        $this->notificationService->sendNotificationToUser(
            $instructorId,
            'حالة جديدة بانتظار المراجعة',
            'قام أحد الطلاب بإنهاء تنفيذ العلاج، والحالة الآن بانتظار مراجعتك.',
            ['type' => 'treatment_completed_by_student', 'treatment_id' => (string) $treatment->id]
        );
    }
}
