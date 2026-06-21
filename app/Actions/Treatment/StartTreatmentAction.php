<?php

namespace App\Actions\Treatment;

use App\Enums\AppointmentStatus;
use App\Enums\DiagnosisStatus;
use App\Enums\TreatmentStatus;
use App\Exceptions\AppointmentAlreadyAttendedException;
use App\Exceptions\UnauthorizedTreatmentException;
use App\Repositories\AppointmentRepository;
use App\Repositories\TreatmentRepository;
use App\Services\MediaService;
use Exception;
use Illuminate\Support\Facades\DB;

class StartTreatmentAction
{
    protected TreatmentRepository $treatmentRepo;
    protected AppointmentRepository $appointmentRepo;
    protected MediaService $mediaService;

    public function __construct(
        TreatmentRepository $treatmentRepo,
        AppointmentRepository $appointmentRepo,
        MediaService $mediaService
    ) {
        $this->treatmentRepo = $treatmentRepo;
        $this->appointmentRepo = $appointmentRepo;
        $this->mediaService = $mediaService;
    }

    /**
     * تنفيذ عملية بدء الجلسة العلاجية الأولى أو اللاحقة.
     */
    public function execute(array $data): mixed
    {
        $appointment = $this->appointmentRepo->findById(
            $data['appointment_id']
        );

        if (! $appointment) {
            throw new Exception('Appointment not found.');
        }

        $this->validateAppointment($appointment);

        return DB::transaction(function () use ($data, $appointment) {
            if ($appointment->treatment_id !== null) {
                return $this->handleFollowUp($appointment);
            }

            return $this->createNewTreatment($appointment, $data);
        });
    }

    /**
     * التعامل مع جلسات المتابعة المتصلة بعلاج سابق.
     */
    private function handleFollowUp($appointment): mixed
    {
        $appointment->update([
            'status' => AppointmentStatus::ATTENDED->value,
        ]);

        return $appointment->treatment->load([
            'diagnosis',
            'appointments',
        ]);
    }

    /**
     * إنشاء سجل علاج جديد وربط الميديا والمواعيد.
     */
    private function createNewTreatment($appointment, array $data): mixed
    {
        if (! isset($data['before_images']) || count($data['before_images']) === 0) {
            throw new Exception('Before-treatment images are required.');
        }

        $treatment = $this->treatmentRepo->create([
            'diagnosis_id' => $appointment->diagnosis_id,
            'instructor_id' => null,
            'status' => TreatmentStatus::IN_PROGRESS->value,
            'instructor_notes' => null,
            'start_date' => now(),
        ]);

        $this->mediaService->upload(
            $treatment,
            $data['before_images'],
            'before_treatment_images'
        );

        $this->linkAndCompleteAppointment($appointment, $treatment->id);

        return $treatment->load(['diagnosis', 'appointments']);
    }

    /**
     * ربط وتحديث المواعيد وتشخيص الحالة.
     */
    private function linkAndCompleteAppointment($appointment, int $treatmentId): void
    {
        $this->appointmentRepo->linkAppointmentsToTreatment(
            $appointment->student_id,
            $appointment->appointment_date,
            $appointment->diagnosis_id,
            $treatmentId
        );

        $appointment->update([
            'status' => AppointmentStatus::ATTENDED->value,
        ]);

        $appointment->diagnosis()->update([
            'status' => DiagnosisStatus::CONVERTED_TO_TREATMENT->value,
        ]);
    }

    /**
     * التحقق من الصلاحية والحالة الحالية للموعد.
     */
    private function validateAppointment($appointment): void
    {
        if ($appointment->student_id !== auth()->id()) {
            throw new UnauthorizedTreatmentException();
        }

        if ($appointment->status === AppointmentStatus::ATTENDED) {
            throw new AppointmentAlreadyAttendedException();
        }

        $this->validateTimeSlot($appointment);
    }



    private function validateTimeSlot($appointment): void
    {
        $now = now();
        $appointmentDate = \Carbon\Carbon::parse($appointment->appointment_date);

        // 1. التحقق من التاريخ
        if (!$now->isSameDay($appointmentDate)) {
            throw new \Exception('Treatment can only be started on the scheduled date: ' . $appointmentDate->format('Y-m-d'));
        }

        // // 2. تعريف جدول الـ Slots
        // $slots = [
        //     1 => ['start' => '08:00', 'end' => '10:00'],
        //     2 => ['start' => '10:30', 'end' => '12:30'],
        //     3 => ['start' => '13:00', 'end' => '15:00'],
        //     4 => ['start' => '15:30', 'end' => '17:30'],
        // ];

        // if (!isset($slots[$appointment->slot_number])) {
        //     throw new \Exception('Invalid slot number.');
        // }

        // $slot = $slots[$appointment->slot_number];
        // $startTime = \Carbon\Carbon::createFromTimeString($slot['start']);
        // $endTime = \Carbon\Carbon::createFromTimeString($slot['end']);

        // // السماح بالبدء من بداية الـ Slot وحتى نهايته
        // // يمكنك إضافة سماحية (Buffer) إذا أردت
        // if ($now->lessThan($startTime) || $now->greaterThan($endTime)) {
        //     throw new \Exception('Treatment can only be started within the scheduled slot time (' . $slot['start'] . ' - ' . $slot['end'] . ').');
        // }
    }
}
