<?php

declare(strict_types=1);

namespace App\Actions\Treatment;

use App\Enums\AppointmentStatus;
use App\Enums\DiagnosisStatus;
use App\Enums\TreatmentStatus;
use App\Exceptions\AppointmentAlreadyAttendedException;
use App\Exceptions\UnauthorizedTreatmentException;
use App\Models\Appointment;
use App\Models\Treatment;
use App\Repositories\AppointmentRepository;
use App\Repositories\TreatmentRepository;
use App\Services\MediaService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

/**
 * الخطوة الأولى في دورة حياة الحالة: الطالب يحضر موعده ويبدأ العلاج.
 *
 * مسارَان:
 * - موعد أول (treatment_id = null) → إنشاء علاج جديد بحالة in_progress مع
 *   رفع صور ما قبل المعالجة إلزامياً.
 * - موعد متابعة (مرتبط بعلاج قائم) → تسجيل حضور فقط دون إنشاء علاج جديد.
 */
class StartTreatmentAction
{
    /** المنطقة الزمنية المعتمدة للعيادة في كل عمليات التاريخ والوقت. */
    private const CLINIC_TIMEZONE = 'Asia/Damascus';

    public function __construct(
        private readonly TreatmentRepository $treatmentRepo,
        private readonly AppointmentRepository $appointmentRepo,
        private readonly MediaService $mediaService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): Treatment
    {
        // القفل داخل المعاملة: lockForUpdate خارج transaction لا يحجز فعلياً،
        // فكان بإمكان طلبين متزامنين إنشاء علاجين لنفس الموعد.
        return DB::transaction(function () use ($data): Treatment {
            $appointment = Appointment::query()
                ->whereKey($data['appointment_id'])
                ->lockForUpdate()
                ->first();

            if (! $appointment) {
                throw new Exception('Appointment not found.', 404);
            }

            $this->validateAppointment($appointment);

            return $appointment->treatment_id !== null
                ? $this->handleFollowUp($appointment)
                : $this->createNewTreatment($appointment, $data);
        });
    }

    /**
     * موعد متابعة لعلاج قائم: نسجّل الحضور فقط، ولا نلمس حالة العلاج
     * لأنه ما يزال in_progress حتى ينهيه الطالب برفع صور ما بعد المعالجة.
     */
    private function handleFollowUp(Appointment $appointment): Treatment
    {
        $appointment->update(['status' => AppointmentStatus::ATTENDED->value]);

        return $appointment->treatment->load(['diagnosis', 'appointments']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createNewTreatment(Appointment $appointment, array $data): Treatment
    {
        // حارس ثانٍ بعد FormRequest: الـ Action قد تُستدعى من سياق آخر
        // (أمر console أو اختبار) لا يمرّ بطبقة التحقق.
        if (empty($data['before_images'])) {
            throw new Exception('Before-treatment images are required to start a new treatment.', 422);
        }

        $treatment = $this->treatmentRepo->create([
            'diagnosis_id' => $appointment->diagnosis_id,
            'instructor_id' => null,
            'status' => TreatmentStatus::IN_PROGRESS->value,
            'instructor_notes' => null,
            'rejection_reason' => null,
            'start_date' => Carbon::now(self::CLINIC_TIMEZONE),
        ]);

        $this->mediaService->upload($treatment, $data['before_images'], 'before_treatment_images');

        $this->linkAndCompleteAppointment($appointment, $treatment->id);

        // إبطال كاش إحصائيات الطالب: عدّاد "قيد المعالجة" في لوحة التحكم
        // يُحتسب من الحالات، فبدون التصفير يبقى قديماً حتى انتهاء مدة الكاش.
        $this->treatmentRepo->clearStudentProgressCache((int) $appointment->student_id);

        return $treatment->load(['diagnosis', 'appointments']);
    }

    private function linkAndCompleteAppointment(Appointment $appointment, int $treatmentId): void
    {
        $this->appointmentRepo->linkAppointmentsToTreatment(
            $appointment->student_id,
            $appointment->appointment_date,
            $appointment->diagnosis_id,
            $treatmentId
        );

        $appointment->update(['status' => AppointmentStatus::ATTENDED->value]);

        $appointment->diagnosis()->update([
            'status' => DiagnosisStatus::CONVERTED_TO_TREATMENT->value,
        ]);
    }

    private function validateAppointment(Appointment $appointment): void
    {
        if ((int) $appointment->student_id !== (int) auth()->id()) {
            throw new UnauthorizedTreatmentException();
        }

        if ($appointment->status === AppointmentStatus::ATTENDED) {
            throw new AppointmentAlreadyAttendedException();
        }

        if ($appointment->status === AppointmentStatus::CANCELLED) {
            throw new Exception('This appointment has been cancelled and cannot be started.', 422);
        }

        $this->validateTimeSlot($appointment);
    }

    /**
     * لا يُسمح ببدء العلاج إلا في يوم الموعد نفسه.
     *
     * ملاحظة: التحقق من الساعة ضمن الفترة معطّل عمداً لتسهيل الاختبار على
     * البيئة التجريبية؛ يكفي حالياً ضبط اليوم.
     */
    private function validateTimeSlot(Appointment $appointment): void
    {
        $now = Carbon::now(self::CLINIC_TIMEZONE);
        $appointmentDate = Carbon::parse($appointment->appointment_date, self::CLINIC_TIMEZONE);

        if (! $now->isSameDay($appointmentDate)) {
            throw new Exception(
                'Treatment can only be started on the scheduled date: '.$appointmentDate->format('Y-m-d'),
                422
            );
        }

        $slotsPerDay = (int) config('clinic.working_slots_per_day');

        if ($appointment->slot_number < 1 || $appointment->slot_number > $slotsPerDay) {
            throw new Exception('Invalid appointment slot.', 422);
        }
    }
}
