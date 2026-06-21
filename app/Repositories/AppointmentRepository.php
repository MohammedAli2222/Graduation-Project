<?php

namespace App\Repositories;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Department;
use Carbon\Carbon;

class AppointmentRepository
{
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
        return Appointment::where('id', $appointmentId)->update(['status' => $status]);
    }

    public function countAppointmentsForTreatment(int $treatmentId): int
    {
        return Appointment::where('treatment_id', $treatmentId)->count();
    }

    public function cancelCurrentAttendedAppointment(int $treatmentId): bool
    {
        return Appointment::where('treatment_id', $treatmentId)
            ->where('status', AppointmentStatus::ATTENDED->value)
            ->update(['status' => AppointmentStatus::CANCELLED->value]);
    }

    public function countActiveAppointmentsForDiagnosis(int $diagnosisId): int
    {
        return Appointment::where('diagnosis_id', $diagnosisId)
            ->where('status', '!=', AppointmentStatus::CANCELLED->value)
            ->count();
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

        $department = Department::find($departmentId);
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

    public function linkAppointmentsToTreatment($studentId, $date, $diagnosisId, $treatmentId)
    {
        return Appointment::where('student_id', $studentId)
            ->where('appointment_date', $date)
            ->where('diagnosis_id', $diagnosisId)
            ->update([
                'treatment_id' => $treatmentId,
            ]);
    }

    public function hasAppointmentWithPatient(int $studentId, int $patientId): bool
    {
        return Appointment::where('student_id', $studentId)
            ->whereHas('diagnosis', function ($query) use ($patientId) {
                $query->where('patient_id', $patientId);
            })
            ->exists();
    }
}
