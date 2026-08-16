<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\EnrollmentStatus;
use App\Events\NewDiagnosesAvailableEvent;
use App\Models\CaseType;
use App\Services\FirebaseNotificationService;
use Illuminate\Support\Facades\DB;

class SendNewCaseNotificationListener
{
    public function __construct(protected FirebaseNotificationService $notificationService) {}

    // إشعار الطلاب المسجلين فعلياً في المقررات المرتبطة بأنواع الحالات المُضافة حديثاً
    public function handle(NewDiagnosesAvailableEvent $event): void
    {
        $caseTypeIds = collect($event->diagnoses)
            ->pluck('case_type_id')
            ->unique()
            ->values();

        if ($caseTypeIds->isEmpty()) {
            return;
        }

        // إيجاد المقررات الدراسية التي تتبع لها أنواع الحالات هذه
        $courseIds = CaseType::query()
            ->whereIn('id', $caseTypeIds)
            ->pluck('course_id')
            ->unique()
            ->values();

        if ($courseIds->isEmpty()) {
            return;
        }

        // ربط جدول التسجيل الأكاديمي بملفات الطلاب للحصول على معرّفات المستخدمين الفعليين
        // لأن عمود student_id في جدول التسجيل يشير إلى student_profiles وليس إلى users مباشرة
        $studentUserIds = DB::table('student_course_enrollments')
            ->join('student_profiles', 'student_profiles.id', '=', 'student_course_enrollments.student_id')
            ->whereIn('student_course_enrollments.course_id', $courseIds)
            ->where('student_course_enrollments.status', EnrollmentStatus::ACTIVE->value)
            ->distinct()
            ->pluck('student_profiles.user_id');

        foreach ($studentUserIds as $studentUserId) {
            $this->notificationService->sendNotificationToUser(
                (int) $studentUserId,
                'حالات مرضية جديدة متاحة 🚨',
                'تم إضافة حالات سريرية جديدة تناسب متطلبات مقرراتك الفعالة. سارع بحجزها!',
                ['type' => 'new_cases_available']
            );
        }
    }
}
