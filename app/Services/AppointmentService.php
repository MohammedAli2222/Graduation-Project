<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\DiagnosisStatus;
use App\Repositories\AppointmentRepository;
use App\Repositories\DiagnosisRepository;
use App\Repositories\PatientRepository;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;


class AppointmentService
{
    protected $appointmentRepo;
    protected $diagnosisRepo;
    protected $patientRepo;

    public function __construct(
        AppointmentRepository $appointmentRepo,
        DiagnosisRepository $diagnosisRepo,
        PatientRepository $patientRepo
    ) {
        $this->appointmentRepo = $appointmentRepo;
        $this->diagnosisRepo   = $diagnosisRepo;
        $this->patientRepo     = $patientRepo;
    }





    private function validateAppointmentTiming(string $dateString, int $studentId, int $departmentId, int $slotNumber): void
    {
        $date = Carbon::parse($dateString);

        if ($date->isFriday() || $date->isSaturday()) {
            throw ValidationException::withMessages([
                'appointment_date' => ['Appointments can only be booked on university working days (Sunday to Thursday).']
            ]);
        }

        $check = $this->appointmentRepo->hasConflict($studentId, $date->format('Y-m-d'), $slotNumber, $departmentId);

        if ($check['conflict']) {
            throw ValidationException::withMessages([
                'appointment_date' => [$check['message']]
            ]);
        }
    }
    /**
     * لوجيك حجز حالة مريض وإنشاء الموعد الأول
     */
    public function reserveCase(array $data)
    {
        return DB::transaction(function () use ($data) {
            $student = auth()->user();
            $profile = $student->studentProfile;

            if (!$profile) {
                throw new \Exception('Student academic profile not found.');
            }

            $diagnosis = $this->diagnosisRepo->findAvailableDiagnosis($data['diagnosis_id']);
            if (!$diagnosis) {
                throw ValidationException::withMessages(['diagnosis' => ['This case is no longer available for booking.']]);
            }

            $departmentId = $diagnosis->department_id;
            $startSlot = (int)$data['slot_number'];
            $dateOnly = Carbon::parse($data['appointment_date'])->format('Y-m-d');

            $slotsNeeded =  (int) $diagnosis->caseType->slots_needed;

            $existingCount = $this->appointmentRepo->getActiveAppointmentsCountForStudent($student->id, $dateOnly);

            if (($existingCount + $slotsNeeded) > 2) {
                throw ValidationException::withMessages([
                    'appointment_date' => ["Booking failed. This reservation requires {$slotsNeeded} slot(s), which will exceed your daily limit of 2 appointments. You currently have {$existingCount} appointment(s) on this day."]
                ]);
            }

            for ($i = 0; $i < $slotsNeeded; $i++) {
                $currentSlot = $startSlot + $i;

                if ($currentSlot > 4) {
                    throw ValidationException::withMessages([
                        'slot_number' => ['This complex case requires consecutive slots that exceed university working hours.']
                    ]);
                }

                $this->validateAppointmentTiming(
                    $data['appointment_date'],
                    $student->id,
                    $departmentId,
                    $currentSlot
                );
            }

            $studentYear     = is_object($profile->academic_year) ? (int) $profile->academic_year->value : (int) $profile->academic_year;
            $studentSemester = is_object($profile->semester) ? (int) $profile->semester->value : (int) $profile->semester;

            $isAllowed = $this->patientRepo->checkCaseBelongsToYearAndSemester($diagnosis->case_type_id, $studentYear, $studentSemester);
            if (!$isAllowed) {
                throw ValidationException::withMessages(['academic_standing' => ['Academic validation requirements failed for this case type.']]);
            }

            $createdAppointments = [];
            for ($i = 0; $i < $slotsNeeded; $i++) {
                $currentSlot = $startSlot + $i;

                $appointment = $this->appointmentRepo->create([
                    'patient_id'       => $diagnosis->patient_id,
                    'student_id'       => $student->id,
                    'diagnosis_id'     => $diagnosis->id,
                    'appointment_date' => $dateOnly,
                    'slot_number'      => $currentSlot,
                    'status'           => AppointmentStatus::SCHEDULED->value,
                ]);

                $createdAppointments[] = $appointment;
            }

            $diagnosis->update(['status' => DiagnosisStatus::RESERVED->value]);

            return $createdAppointments;
        });
    }
}
