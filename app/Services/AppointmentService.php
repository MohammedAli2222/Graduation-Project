<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\DiagnosisStatus;
use App\Enums\PatientStatus;
use App\Models\Patient;
use App\Models\PatientDiagnose;
use App\Repositories\AppointmentRepository;
use App\Repositories\DiagnosisRepository;
use App\Repositories\PatientRepository;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
        $this->diagnosisRepo = $diagnosisRepo;
        $this->patientRepo = $patientRepo;
    }



    public function validateAppointmentTiming(string $dateString, int $studentId, int $departmentId, int $slotNumber): void
    {
        $date = Carbon::parse($dateString);

        if ($date->isFriday() || $date->isSaturday()) {
            throw ValidationException::withMessages([
                'appointment_date' => ['Appointments can only be booked on university working days (Sunday to Thursday).'],
            ]);
        }

        $check = $this->appointmentRepo->hasConflict($studentId, $date->format('Y-m-d'), $slotNumber, $departmentId);

        if ($check['conflict']) {
            throw ValidationException::withMessages([
                'appointment_date' => [$check['message']],
            ]);
        }
    }

    public function reserveCase(array $data)
    {
        $diagnosisId = $data['diagnosis_id'];

        $lock = Cache::lock('lock:diagnosis:' . $diagnosisId, 10);

        try {

            $lock->block(3);

            return DB::transaction(function () use ($data) {
                $student = auth()->user();
                $profile = $student->studentProfile;

                if (! $profile) {
                    throw new \Exception('Student academic profile not found.');
                }

                $diagnosis = $this->diagnosisRepo->findAvailableDiagnosis($data['diagnosis_id']);
                if (! $diagnosis) {
                    throw ValidationException::withMessages(['diagnosis' => ['This case is no longer available for booking.']]);
                }

                $departmentId = $diagnosis->department_id;
                $startSlot = (int) $data['slot_number'];
                $dateOnly = Carbon::parse($data['appointment_date'])->format('Y-m-d');

                $slotsNeeded = (int) $diagnosis->caseType->slots_needed;

                $existingCount = $this->appointmentRepo->getActiveAppointmentsCountForStudent($student->id, $dateOnly);

                if (($existingCount + $slotsNeeded) > 2) {
                    throw ValidationException::withMessages([
                        'appointment_date' => ["Booking failed. This reservation requires {$slotsNeeded} slot(s), which will exceed your daily limit of 2 appointments. You currently have {$existingCount} appointment(s) on this day."],
                    ]);
                }

                for ($i = 0; $i < $slotsNeeded; $i++) {
                    $currentSlot = $startSlot + $i;

                    if ($currentSlot > 4) {
                        throw ValidationException::withMessages([
                            'slot_number' => ['This complex case requires consecutive slots that exceed university working hours.'],
                        ]);
                    }

                    $this->validateAppointmentTiming(
                        $data['appointment_date'],
                        $student->id,
                        $departmentId,
                        $currentSlot
                    );
                }

                $isAllowed = $this->patientRepo->checkIfCaseBelongsToActiveCourse(
                    $diagnosis->case_type_id,
                    $profile->id
                );
                if (! $isAllowed) {
                    throw ValidationException::withMessages(['academic_standing' => ['Academic validation requirements failed for this case type.']]);
                }

                $createdAppointments = [];
                for ($i = 0; $i < $slotsNeeded; $i++) {
                    $currentSlot = $startSlot + $i;

                    $appointment = $this->appointmentRepo->create([
                        'patient_id' => $diagnosis->patient_id,
                        'student_id' => $student->id,
                        'diagnosis_id' => $diagnosis->id,
                        'treatment_id' => null,
                        'appointment_date' => $dateOnly,
                        'slot_number' => $currentSlot,
                        'status' => AppointmentStatus::SCHEDULED->value,
                    ]);

                    $createdAppointments[] = $appointment;
                }

                $diagnosis->update(['status' => DiagnosisStatus::RESERVED->value]);

                $this->updatePatientStatusIfAllDiagnosesReserved($diagnosis->patient_id);

                return $createdAppointments;
            });
        } catch (LockTimeoutException $e) {
            throw ValidationException::withMessages([
                'diagnosis' => ['This case is currently being processed by another student. Please try again in a few moments.'],
            ]);
        } finally {
            optional($lock)->release();
        }
    }

    public function getStudentAppointments(int $studentId)
    {
        return $this->appointmentRepo->getStudentAppointments($studentId);
    }

    public function getStudentAppointmentHistory(int $studentId)
    {
        return $this->appointmentRepo->getStudentAppointmentHistory($studentId);
    }

    public function updatePatientStatusIfAllDiagnosesReserved(int $patientId)
    {
        $hasAvailableDiagnoses = PatientDiagnose::where('patient_id', $patientId)
            ->where('status', DiagnosisStatus::AVAILABLE->value)
            ->exists();

        if (!$hasAvailableDiagnoses) {
            Patient::where('id', $patientId)
                ->update(['availability_status' => PatientStatus::FULLY_RESERVED->value]);
        }
    }
}
