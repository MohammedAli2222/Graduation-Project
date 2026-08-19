<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\TreatmentCompletedByStudentEvent;
use App\Repositories\TreatmentRepository;
use App\Services\FirebaseNotificationService;

class SendInstructorNotificationListener
{
    public function __construct(
        protected FirebaseNotificationService $notificationService,
        protected TreatmentRepository $treatmentRepo,
    ) {}

    // إشعار المعيد المسؤول عن فئة الطالب بأن الطالب أنهى تنفيذ العلاج وينتظر مراجعته
    public function handle(TreatmentCompletedByStudentEvent $event): void
    {
        $treatment = $event->treatment;

        $studentId = $this->treatmentRepo->getOwningStudentId($treatment);

        // المعيد المسؤول يُحدَّد عبر إشراف فئة الطالب (group_instructor)، لا عبر
        // معيد التشخيص (diagnosis->instructor_id) الذي قد يكون شخصاً مختلفاً
        // كلياً ولا علاقة له بمتابعة تنفيذ العلاج الفعلي
        $instructorId = $studentId !== null
            ? $this->treatmentRepo->getResponsibleInstructorUserId($studentId)
            : null;

        if (! $instructorId) {
            return;
        }

        $this->notificationService->sendNotificationToUser(
            $instructorId,
            'طلب تقييم علاج جديد',
            'الطالب ارسل حالة جديدة بانتظار تقييمك.',
            ['type' => 'treatment_review', 'reference_id' => (string) $treatment->id],
            'treatment_review'
        );
    }
}
