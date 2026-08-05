<?php

namespace App\Actions\Treatment;

use App\Enums\AppointmentStatus;
use App\Enums\DiagnosisStatus;
use App\Enums\TreatmentStatus;
use App\Exceptions\AppointmentAlreadyAttendedException;
use App\Exceptions\UnauthorizedTreatmentException;
use App\Models\Appointment;
use App\Repositories\AppointmentRepository;
use App\Repositories\TreatmentRepository;
use App\Services\MediaService;
use Carbon\Carbon;
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

    public function execute(array $data): mixed
    {
        // جلب الموعد مع قفل للبيانات لضمان عدم حدوث تضارب
        $appointment = Appointment::where('id', $data['appointment_id'])->lockForUpdate()->first();

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

    private function handleFollowUp($appointment): mixed
    {
        $appointment->update([
            'status' => AppointmentStatus::ATTENDED->value,
        ]);

        return $appointment->treatment->load(['diagnosis', 'appointments']);
    }

    private function createNewTreatment($appointment, array $data): mixed
    {
        if (empty($data['before_images'])) {
            throw new Exception('Before-treatment images are required.');
        }

        $treatment = $this->treatmentRepo->create([
            'diagnosis_id' => $appointment->diagnosis_id,
            'instructor_id' => null,
            'status' => TreatmentStatus::IN_PROGRESS->value,
            'instructor_notes' => null,
            'start_date' => Carbon::now('Asia/Damascus'),
        ]);

        $this->mediaService->upload($treatment, $data['before_images'], 'before_treatment_images');

        $this->linkAndCompleteAppointment($appointment, $treatment->id);

        return $treatment->load(['diagnosis', 'appointments']);
    }

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

        // تحديث حالة التشخيص
        $appointment->diagnosis()->update([
            'status' => DiagnosisStatus::CONVERTED_TO_TREATMENT->value,
        ]);
    }

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
        $now = Carbon::now('Asia/Damascus');
        $appointmentDate = Carbon::parse($appointment->appointment_date, 'Asia/Damascus');

        if (!$now->isSameDay($appointmentDate)) {
            throw new Exception('Treatment can only be started on the scheduled date: ' . $appointmentDate->format('Y-m-d'));
        }

        // أوقات السلوتس الثابتة
        $slotTimes = [
            1 => ['start' => '08:00', 'end' => '10:00'],
            2 => ['start' => '10:30', 'end' => '12:30'],
            3 => ['start' => '13:00', 'end' => '15:00'],
            4 => ['start' => '15:30', 'end' => '17:30'],
        ];

        if (!isset($slotTimes[$appointment->slot_number])) {
            throw new Exception('Invalid appointment slot.');
        }

        $startTime = Carbon::createFromFormat('H:i', $slotTimes[$appointment->slot_number]['start'], 'Asia/Damascus');
        $endTime = Carbon::createFromFormat('H:i', $slotTimes[$appointment->slot_number]['end'], 'Asia/Damascus');

        // // السماح بالبدء من بداية السلوت وحتى نهايته
        // if ($now->lessThan($startTime) || $now->greaterThan($endTime)) {
        //     throw new Exception("Treatment can only be started between {$slotTimes[$appointment->slot_number]['start']} and {$slotTimes[$appointment->slot_number]['end']}.");
        // }
    }
}
