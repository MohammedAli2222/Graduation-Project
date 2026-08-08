<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\DiagnosisStatus;
use App\Enums\OrderStatus;
use App\Enums\TreatmentStatus;
use App\Events\DiagnosisReviewedEvent;
use App\Events\OrderStatusUpdatedEvent;
use App\Events\TreatmentReviewedEvent;
use App\Services\FirebaseNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

// مستمع واحد يعالج جميع الإشعارات الموجهة للطالب لتفادي تكرار منطق الإرسال
// يعمل ضمن قائمة انتظار (Queue) حتى لا يؤخر إرسال الإشعار استجابة الـ API الرئيسية
class SendStudentNotificationListener implements ShouldQueue
{
    public function __construct(protected FirebaseNotificationService $notificationService) {}

    // إشعار الطالب بنتيجة مراجعة التشخيص الذي اقترحه
    public function handleDiagnosisReviewed(DiagnosisReviewedEvent $event): void
    {
        $diagnosis = $event->diagnosis;
        $studentId = $diagnosis->suggested_by_student_id;

        if (! $studentId) {
            return;
        }

        $isApproved = $event->status === DiagnosisStatus::AVAILABLE->value;

        $this->notificationService->sendNotificationToUser(
            $studentId,
            $isApproved ? 'تمت الموافقة على التشخيص' : 'تم رفض التشخيص',
            $isApproved
                ? 'تمت الموافقة على التشخيص الذي اقترحته، يمكنك الآن متابعة الحالة.'
                : 'تم رفض التشخيص الذي اقترحته، يرجى مراجعة سبب الرفض.',
            ['type' => 'diagnosis_reviewed', 'diagnosis_id' => (string) $diagnosis->id]
        );
    }

    // إشعار الطالب بنتيجة مراجعة العلاج الذي أنهاه (قبول أو رفض من المدرّس)
    public function handleTreatmentReviewed(TreatmentReviewedEvent $event): void
    {
        $treatment = $event->treatment;

        // نفس الطريقة المعتمدة في بقية الكود لتحديد الطالب المالك للحالة العلاجية
        $studentId = $treatment->appointments()->first()?->student_id;

        if (! $studentId) {
            return;
        }

        $isApproved = $event->status === TreatmentStatus::COMPLETED->value;

        $this->notificationService->sendNotificationToUser(
            $studentId,
            $isApproved ? 'تمت الموافقة على العلاج' : 'تم رفض العلاج',
            $isApproved
                ? 'تمت الموافقة على حالتك العلاجية بنجاح.'
                : 'تم رفض حالتك العلاجية، يرجى مراجعة ملاحظات المدرّس.',
            ['type' => 'treatment_reviewed', 'treatment_id' => (string) $treatment->id]
        );
    }

    // إشعار الطالب بتحديث حالة طلب الشراء الخاص به في المتجر
    public function handleOrderStatusUpdated(OrderStatusUpdatedEvent $event): void
    {
        $order = $event->order;

        $this->notificationService->sendNotificationToUser(
            $order->student_id,
            'تحديث حالة الطلب',
            'تم تحديث حالة طلبك رقم ' . $order->id . ' إلى: ' . $this->orderStatusLabel($order->status),
            ['type' => 'order_status_updated', 'order_id' => (string) $order->id]
        );
    }

    // ترجمة حالة الطلب إلى نص عربي مفهوم للطالب داخل الإشعار
    private function orderStatusLabel(OrderStatus $status): string
    {
        return match ($status) {
            OrderStatus::PENDING => 'قيد الانتظار',
            OrderStatus::PROCESSING => 'قيد التجهيز',
            OrderStatus::READY => 'جاهز للاستلام',
            OrderStatus::COMPLETED => 'مكتمل',
            OrderStatus::REJECTED => 'مرفوض',
        };
    }
}
