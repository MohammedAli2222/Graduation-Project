<?php

namespace App\Actions\Treatment;

use App\Enums\DiagnosisStatus;
use App\Enums\TreatmentStatus;
use App\Events\TreatmentReviewedEvent;
use App\Models\Treatment;
use App\Repositories\TreatmentRepository;
use Exception;
use Illuminate\Support\Facades\DB;

class ReviewTreatmentAction
{
    protected TreatmentRepository $treatmentRepo;

    public function __construct(TreatmentRepository $treatmentRepo)
    {
        $this->treatmentRepo = $treatmentRepo;
    }

    public function execute(array $data, $user): Treatment
    {
        $treatment = $this->treatmentRepo->find($data['treatment_id']);

        $this->validateReview($treatment, $user);

        $treatment = DB::transaction(function () use ($data, $treatment, $user) {
            if ($data['action'] === 'approve') {
                $this->approveTreatment($treatment, $data);
            } else {
                $insId = $user->instructorProfile->id;
                $this->rejectTreatment($treatment, $data, $insId);
            }

            return $treatment->load([
                'media',
                'diagnosis.appointments.student',
                'diagnosis.appointments.patient',
                'instructor',
            ]);
        });

        // إطلاق الحدث بعد نجاح المعاملة لإشعار الطالب بنتيجة المراجعة دون تأخير استجابة الـ API الرئيسية
        TreatmentReviewedEvent::dispatch(
            $treatment,
            $data['action'] === 'approve' ? TreatmentStatus::COMPLETED->value : TreatmentStatus::REJECTED->value
        );

        return $treatment;
    }

    private function validateReview(?Treatment $treatment, $user): void
    {
        if (! $treatment) {
            throw new Exception('Treatment record not found.');
        }

        if ($treatment->status !== TreatmentStatus::WAITING_INSTRUCTOR_APPROVAL) {
            throw new Exception('This treatment cannot be reviewed.');
        }

        if (! $user->instructorProfile) {
            throw new Exception('Unauthorized! Profile must be an instructor.');
        }
    }

    private function approveTreatment(Treatment $treatment, array $data): void
    {
        $treatment->update([
            'status' => TreatmentStatus::COMPLETED->value,
            'instructor_id' => auth()->id(),
            'instructor_notes' => $data['instructor_notes'] ?? null,
            'end_date' => now(),
        ]);

        if ($treatment->diagnosis) {
            $treatment->diagnosis->update([
                'status' => DiagnosisStatus::FINISHED->value,
            ]);
        }
    }

    private function rejectTreatment(
        Treatment $treatment,
        array $data,
        int $instructorId
    ): void {
        $treatment->update([
            'status' => TreatmentStatus::REJECTED->value,
            'instructor_id' => $instructorId,
            'instructor_notes' => $data['instructor_notes'] ?? null,
        ]);
    }
}
