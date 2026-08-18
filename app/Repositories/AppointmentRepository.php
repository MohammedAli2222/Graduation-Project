<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\AppointmentStatus;
use App\Enums\DiagnosisStatus;
use App\Enums\PatientStatus;
use App\Models\Appointment;
use App\Models\Department;
use App\Models\Patient;
use App\Models\PatientDiagnose;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class AppointmentRepository
{
    private const DEPARTMENT_CONFIG_CACHE_TTL_SECONDS = 86400;

    private function getCachedDepartment(int $departmentId): ?Department
    {
        return Cache::remember(
            "department_{$departmentId}_config",
            self::DEPARTMENT_CONFIG_CACHE_TTL_SECONDS,
            fn () => Department::find($departmentId)
        );
    }
    public function create(array $data)
    {
        return Appointment::create($data);
    }

    public function findById(int $appointmentId)
    {
        return Appointment::findorFail($appointmentId);
    }

    public function updateStatus(int $appointmentId, string $status): bool
    {
        return (bool) Appointment::where('id', $appointmentId)->update(['status' => $status]);
    }

    public function countAppointmentsForTreatment(int $treatmentId): int
    {
        return Appointment::where('treatment_id', $treatmentId)->count();
    }

    public function cancelCurrentAttendedAppointment(int $treatmentId): bool
    {
        return (bool) Appointment::where('treatment_id', $treatmentId)
            ->where('status', AppointmentStatus::ATTENDED->value)
            ->update(['status' => AppointmentStatus::CANCELLED->value]);
    }

    public function countActiveAppointmentsForDiagnosis(int $diagnosisId): int
    {
        return Appointment::where('diagnosis_id', $diagnosisId)
            ->where('status', '!=', AppointmentStatus::CANCELLED->value)
            ->count();
    }



    public function getActiveAppointmentsCountForStudent(int $studentId, string $date): int
    {
        return Appointment::where('student_id', $studentId)
            ->whereIn('status', [
                AppointmentStatus::SCHEDULED->value,
                AppointmentStatus::ATTENDED->value,
            ])
            ->whereDate('appointment_date', $date)
            ->count();
    }



    public function hasAppointmentWithPatient(int $studentId, int $patientId): bool
    {
        return Appointment::where('student_id', $studentId)
            ->whereHas('diagnosis', function ($query) use ($patientId) {
                $query->where('patient_id', $patientId);
            })
            ->exists();
    }

    public function getStudentAppointments(int $studentId)
    {
        $today = Carbon::now('Asia/Damascus')->format('Y-m-d');

        return Appointment::where('student_id', $studentId)
            ->where('status', AppointmentStatus::SCHEDULED->value)
            ->where('appointment_date', '>=', $today)
            ->addSelect([
                'first_appointment_id' => Appointment::select('id')
                    ->whereColumn('treatment_id', 'appointments.treatment_id')
                    ->orderBy('id', 'asc')
                    ->limit(1)
            ])
            ->with([
                'treatment:id,status',
                'diagnosis.caseType:id,name',
                'diagnosis.patient:id,full_name,phone',
                'diagnosis.department:id,name'
            ])
            ->orderBy('appointment_date', 'asc')
            ->orderBy('slot_number', 'asc')
            ->paginate(10);
    }

    public function getStudentAppointmentHistory(int $studentId)
    {
        $today = Carbon::now('Asia/Damascus')->format('Y-m-d');

        return Appointment::where('student_id', $studentId)
            ->where(function ($query) use ($studentId, $today) {
                $query->whereNotIn('status', [AppointmentStatus::SCHEDULED->value])
                    ->orWhere(function ($q) use ($studentId, $today) {
                        $q->where('student_id', $studentId)
                            ->where('status', AppointmentStatus::SCHEDULED->value)
                            ->where('appointment_date', '<', $today);
                    });
            })
            ->addSelect([
                'first_appointment_id' => Appointment::select('id')
                    ->whereColumn('treatment_id', 'appointments.treatment_id')
                    ->orderBy('id', 'asc')
                    ->limit(1)
            ])
            ->with([
                'treatment:id,status',
                'diagnosis.caseType:id,name',
                'diagnosis.patient:id,full_name,phone',
                'diagnosis.department:id,name'
            ])
            ->orderBy('appointment_date', 'desc')
            ->paginate(10);
    }

    public function hasConflict(int $studentId, string $dateString, int $slotNumber, int $departmentId): array
    {
        $dateOnly = Carbon::parse($dateString)->format('Y-m-d');

        $hasSlotConflict = Appointment::where('student_id', $studentId)
            ->whereIn('status', [AppointmentStatus::SCHEDULED->value, AppointmentStatus::ATTENDED->value])
            ->whereDate('appointment_date', $dateOnly)
            ->where('slot_number', $slotNumber)
            ->exists();

        if ($hasSlotConflict) {
            return [
                'conflict' => true,
                'message' => 'Time conflict! You already have another appointment booked in this exact time slot.',
            ];
        }

        $department = $this->getCachedDepartment($departmentId);
        $maxChairs = $department->total_chairs;

        $reservedChairsCount = Appointment::whereIn('status', [AppointmentStatus::SCHEDULED->value, AppointmentStatus::ATTENDED->value])
            ->whereDate('appointment_date', $dateOnly)
            ->where('slot_number', $slotNumber)
            ->whereHas('diagnosis', function ($query) use ($departmentId) {
                $query->where('department_id', $departmentId);
            })
            ->count();

        if ($reservedChairsCount >= $maxChairs) {
            return [
                'conflict' => true,
                'message' => "This time slot is fully booked. All {$maxChairs} dental chairs in this department are occupied.",
            ];
        }

        return ['conflict' => false, 'message' => ''];
    }

    public function linkAppointmentsToTreatment($studentId, $date, $diagnosisId, $treatmentId)
    {
        return Appointment::where('student_id', $studentId)
            ->where('appointment_date', $date)
            ->where('diagnosis_id', $diagnosisId)
            ->update([
                'treatment_id' => $treatmentId,
            ]);
    }

    public function updatePatientAvailabilityStatus(int $patientId)
    {
        $hasAvailableDiagnoses = PatientDiagnose::where('patient_id', $patientId)
            ->where('status', DiagnosisStatus::AVAILABLE->value)
            ->exists();

        $newStatus = $hasAvailableDiagnoses ? PatientStatus::AVAILABLE : PatientStatus::FULLY_RESERVED;

        Patient::where('id', $patientId)->update(['availability_status' => $newStatus->value]);
    }
}
