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

    // إشعار الطلاب المسجلين فعلياً في المقررات المرتبطة بأنواع الحالات المُضافة حديثاً،
    // مع ذكر اسم/أسماء أنواع الحالات المتاحة فعلياً لكل طالب داخل نص الإشعار نفسه
    public function handle(NewDiagnosesAvailableEvent $event): void
    {
        $caseTypeIds = collect($event->diagnoses)
            ->pluck('case_type_id')
            ->unique()
            ->values();

        if ($caseTypeIds->isEmpty()) {
            return;
        }

        // اسم ومقرر كل نوع حالة مُضاف، مفهرَسة بمعرّف نوع الحالة
        $caseTypes = CaseType::query()
            ->whereIn('id', $caseTypeIds)
            ->get(['id', 'name', 'course_id'])
            ->keyBy('id');

        if ($caseTypes->isEmpty()) {
            return;
        }

        // أسماء أنواع الحالات مجمَّعة حسب المقرر، لأن الطالب يظهر إشرافه عبر
        // تسجيله بالمقرر لا بنوع الحالة مباشرة
        $caseTypeNamesByCourse = $caseTypes->groupBy('course_id')
            ->map(fn ($group) => $group->pluck('name')->unique()->values());

        $courseIds = $caseTypeNamesByCourse->keys();

        // ربط جدول التسجيل الأكاديمي بملفات الطلاب للحصول على معرّفات المستخدمين الفعليين
        // لأن عمود student_id في جدول التسجيل يشير إلى student_profiles وليس إلى users مباشرة
        $enrollments = DB::table('student_course_enrollments')
            ->join('student_profiles', 'student_profiles.id', '=', 'student_course_enrollments.student_id')
            ->whereIn('student_course_enrollments.course_id', $courseIds)
            ->where('student_course_enrollments.status', EnrollmentStatus::ACTIVE->value)
            ->select('student_profiles.user_id', 'student_course_enrollments.course_id')
            ->distinct()
            ->get();

        // معرّف الطالب => مجموعة أسماء أنواع الحالات المتاحة له تحديداً ضمن هذه الدفعة
        $namesByStudent = [];

        foreach ($enrollments as $enrollment) {
            $names = $caseTypeNamesByCourse->get($enrollment->course_id, collect())->all();

            $namesByStudent[$enrollment->user_id] = array_unique(array_merge(
                $namesByStudent[$enrollment->user_id] ?? [],
                $names
            ));
        }

        foreach ($namesByStudent as $studentUserId => $names) {
            $body = count($names) === 1
                ? 'أصبحت حالة سريرية جديدة متاحة: '.$names[0].'. سارع بحجزها!'
                : 'أصبحت حالات سريرية جديدة متاحة: '.implode('، ', $names).'. سارع بحجزها!';

            $this->notificationService->sendNotificationToUser(
                (int) $studentUserId,
                'حالات مرضية جديدة متاحة 🚨',
                $body,
                ['type' => 'new_cases_available', 'case_types' => implode(',', $names)],
                'new_cases_available'
            );
        }
    }
}
