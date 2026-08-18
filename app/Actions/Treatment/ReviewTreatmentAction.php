<?php

declare(strict_types=1);

namespace App\Actions\Treatment;

use App\Enums\DiagnosisStatus;
use App\Enums\TreatmentStatus;
use App\Events\TreatmentReviewedEvent;
use App\Models\Treatment;
use App\Models\User;
use App\Repositories\TreatmentRepository;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * الخطوة الأخيرة في دورة حياة الحالة: تقييم المعيد للعلاج المُنجَز.
 *
 * - الاعتماد (approve): الحالة تصبح completed والتشخيص finished، وعندها فقط
 *   تُحتسب ضمن متطلبات الطالب السريرية (العدّاد مشتق من الحالات المعتمدة).
 * - الرفض (reject): الحالة تصبح rejected مع سبب رفض إلزامي يُخزَّن في العمود
 *   rejection_reason ويعود للطالب في الرد.
 */
class ReviewTreatmentAction
{
    public function __construct(
        private readonly TreatmentRepository $treatmentRepo,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, User $user): Treatment
    {
        $isApproval = $data['action'] === 'approve';

        $treatment = DB::transaction(function () use ($data, $user, $isApproval): Treatment {
            // القفل داخل المعاملة يمنع مراجعتين متزامنتين لنفس الحالة
            // (اعتماد ورفض في آن واحد من معيدَين يشرفان على نفس المجموعة).
            $treatment = $this->treatmentRepo->findForUpdate((int) $data['treatment_id']);

            $this->validateReview($treatment, $user);

            $isApproval
                ? $this->approveTreatment($treatment, $data, $user)
                : $this->rejectTreatment($treatment, $data, $user);

            return $treatment->load([
                'media',
                'diagnosis.appointments.student',
                'diagnosis.appointments.patient',
                'instructor',
            ]);
        });

        // تصفير كاش إحصائيات الطالب بعد المراجعة: عدّاد الحالات المنجزة
        // مشتقّ من حالات العلاج، وبدون التصفير يبقى الطالب يرى رقماً قديماً
        // حتى انتهاء مدة الكاش رغم اعتماد حالته.
        $studentId = $this->treatmentRepo->getOwningStudentId($treatment);

        if ($studentId !== null) {
            $this->treatmentRepo->clearStudentProgressCache($studentId);

            // كاش قائمة أنواع الحالات (StudentCourseService::getCaseTypesForDropdown) مفتاحه
            // معرّف ملف الطالب (student_profiles.id) لا معرّف المستخدم، فنحتاج تحويلاً صريحاً هنا
            // بدل استخدام $studentId مباشرة (وإلا انمسح مفتاح لم يُخزَّن فيه شيء أصلاً).
            $studentProfileId = DB::table('student_profiles')->where('user_id', $studentId)->value('id');

            if ($studentProfileId !== null) {
                Cache::forget("case_types:student_{$studentProfileId}");
            }
        }

        // إطلاق الحدث بعد نجاح المعاملة لإشعار الطالب بنتيجة المراجعة
        // دون تأخير استجابة الـ API الرئيسية.
        TreatmentReviewedEvent::dispatch(
            $treatment,
            $isApproval
                ? TreatmentStatus::COMPLETED->value
                : TreatmentStatus::REJECTED->value
        );

        return $treatment;
    }

    private function validateReview(?Treatment $treatment, User $user): void
    {
        if (! $treatment) {
            throw new Exception('Treatment record not found.', 404);
        }

        // لا تُراجَع إلا حالة رفعها الطالب فعلاً وبانتظار التقييم؛ هذا يمنع
        // اعتماد حالة ما تزال in_progress أو إعادة مراجعة حالة محسومة.
        if ($treatment->status !== TreatmentStatus::WAITING_INSTRUCTOR_APPROVAL) {
            throw new Exception(
                'This treatment cannot be reviewed. Current status: '
                    .($treatment->status->value ?? (string) $treatment->status),
                422
            );
        }

        if (! $user->instructorProfile) {
            throw new Exception('Unauthorized! Profile must be an instructor.', 403);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function approveTreatment(Treatment $treatment, array $data, User $user): void
    {
        $treatment->update([
            'status' => TreatmentStatus::COMPLETED->value,
            // instructor_id مفتاح أجنبي على جدول users وليس instructor_profiles،
            // وكان الرفض يكتب معرّف البروفايل هنا فينتج ربط بمستخدم خاطئ.
            'instructor_id' => $user->id,
            'instructor_notes' => $data['instructor_notes'] ?? null,
            'rejection_reason' => null,
            'end_date' => now(),
        ]);

        if ($treatment->diagnosis) {
            $treatment->diagnosis->update([
                'status' => DiagnosisStatus::FINISHED->value,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function rejectTreatment(Treatment $treatment, array $data, User $user): void
    {
        $treatment->update([
            'status' => TreatmentStatus::REJECTED->value,
            'instructor_id' => $user->id,
            'instructor_notes' => $data['instructor_notes'] ?? null,
            // السبب الإلزامي الذي يعود للطالب ليعرف ما يجب تصحيحه.
            'rejection_reason' => $data['rejection_reason'],
        ]);
    }
}
